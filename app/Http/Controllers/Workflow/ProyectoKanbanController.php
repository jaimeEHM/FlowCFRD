<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\KanbanStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Services\AuditLogger;
use App\Support\ProyectoKanbanPayload;
use App\Support\WorkflowRealtime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProyectoKanbanController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $projects = Project::queryForUser($user)->orderBy('name')->get(['id', 'name', 'code']);

        $projectId = $request->integer('project_id');
        $focusTaskId = $request->integer('focus_task_id');
        $project = $projectId
            ? Project::queryForUser($user)->whereKey($projectId)->first()
            : $projects->first();

        if ($project === null) {
            return Inertia::render('proyecto/Kanban', [
                'project' => null,
                'projects' => $projects,
                'groups' => [],
                'statuses' => Task::STATUSES,
                'statusOptions' => [],
                'peopleOptions' => [],
                'focusedTaskId' => null,
            ]);
        }

        $resolvedFocusTaskId = null;
        if ($focusTaskId > 0) {
            $resolvedFocusTaskId = Task::query()
                ->where('project_id', $project->id)
                ->whereKey($focusTaskId)
                ->value('id');
        }

        $groups = ProyectoKanbanPayload::groupsForProject($project);
        $peopleOptions = ProyectoKanbanPayload::peopleOptionsForProject($project);
        $statusOptions = $this->statusOptions($project);

        return Inertia::render('proyecto/Kanban', [
            'project' => $project->only(['id', 'name', 'code']),
            'projects' => $projects,
            'groups' => $groups,
            'statuses' => array_map(fn (array $s) => $s['key'], $statusOptions),
            'statusOptions' => $statusOptions,
            'peopleOptions' => $peopleOptions,
            'focusedTaskId' => $resolvedFocusTaskId,
        ]);
    }

    public function storeTaskGroup(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:128',
            'color' => 'nullable|string|max:32',
        ]);

        $project = Project::queryForUser($user)->whereKey($validated['project_id'])->firstOrFail();

        $max = (int) TaskGroup::query()->where('project_id', $project->id)->max('position');

        TaskGroup::query()->create([
            'project_id' => $project->id,
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#64748b',
            'position' => $max + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Segmento creado.']);

        return redirect()->back(302, [], route('proyecto.kanban', ['project_id' => $project->id]));
    }

    public function storeTask(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'task_group_id' => 'required|exists:task_groups,id',
            'title' => 'required|string|max:255',
        ]);

        $project = Project::queryForUser($user)->whereKey($validated['project_id'])->firstOrFail();
        $group = TaskGroup::query()->whereKey($validated['task_group_id'])->where('project_id', $project->id)->firstOrFail();

        $maxOrder = (int) Task::query()
            ->where('task_group_id', $group->id)
            ->where('status', Task::STATUS_BACKLOG)
            ->max('kanban_order');

        $task = Task::query()->create([
            'project_id' => $project->id,
            'task_group_id' => $group->id,
            'title' => $validated['title'],
            'status' => Task::STATUS_BACKLOG,
            'backlog_order' => 0,
            'kanban_order' => $maxOrder + 1,
            'created_by_id' => $user->id,
        ]);

        $auditLogger->log('task.created', $task, ['title' => $task->title]);
        $task->refresh();
        WorkflowRealtime::task($task, 'created');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tarea creada.']);

        return redirect()->back(302, [], route('proyecto.kanban', ['project_id' => $project->id]));
    }

    public function syncKanban(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'orders' => 'required|array|min:1',
            'orders.*.task_id' => 'required|exists:tasks,id',
            'orders.*.status' => 'required|string',
            'orders.*.task_group_id' => 'required|exists:task_groups,id',
            'orders.*.kanban_order' => 'required|integer|min:0',
        ]);

        $project = Project::queryForUser($user)->whereKey($validated['project_id'])->firstOrFail();
        $allowedStatuses = $this->allowedStatusesForProject($project);

        $groupIds = TaskGroup::query()->where('project_id', $project->id)->pluck('id')->all();

        DB::transaction(function () use ($validated, $project, $groupIds, $allowedStatuses): void {
            foreach ($validated['orders'] as $row) {
                $task = Task::query()->whereKey($row['task_id'])->where('project_id', $project->id)->firstOrFail();
                if (! in_array((int) $row['task_group_id'], array_map('intval', $groupIds), true)) {
                    abort(422, 'Grupo inválido para este proyecto.');
                }
                if (! in_array((string) $row['status'], $allowedStatuses, true)) {
                    abort(422, 'Estado inválido para este proyecto.');
                }

                $task->fill([
                    'status' => $row['status'],
                    'task_group_id' => $row['task_group_id'],
                    'kanban_order' => $row['kanban_order'],
                ]);
                if ($task->isDirty()) {
                    $task->save();
                    $task->refresh();
                    WorkflowRealtime::task($task, 'updated');
                }
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tablero actualizado.']);

        return redirect()->back(302, [], route('proyecto.kanban', ['project_id' => $project->id]));
    }

    public function updateTask(Request $request, Task $task, AuditLogger $auditLogger): RedirectResponse
    {
        if (! Project::userMayAccess($request->user(), $task->project)) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'sometimes|required|string',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'assignee_id' => 'nullable|exists:users,id',
            'task_group_id' => 'nullable|exists:task_groups,id',
            'due_date' => 'nullable|date',
            'collaborator_ids' => 'sometimes|array',
            'collaborator_ids.*' => 'exists:users,id',
        ]);

        if (isset($validated['task_group_id'])) {
            $g = TaskGroup::query()->whereKey($validated['task_group_id'])->where('project_id', $task->project_id)->first();
            if ($g === null) {
                return back()->withErrors(['task_group_id' => 'El segmento no pertenece a este proyecto.']);
            }
        }

        if (isset($validated['status'])) {
            $allowedStatuses = $this->allowedStatusesForProject($task->project);
            if (! in_array((string) $validated['status'], $allowedStatuses, true)) {
                return back()->withErrors(['status' => 'El estado no está habilitado para este proyecto.']);
            }
        }

        $syncCollaborators = $request->has('collaborator_ids');
        $collabIds = $syncCollaborators ? $request->input('collaborator_ids', []) : null;
        unset($validated['collaborator_ids']);

        $task->fill($validated);
        $dirty = $task->getDirty();
        $task->save();

        if ($syncCollaborators && is_array($collabIds)) {
            $unique = array_values(array_unique(array_map('intval', $collabIds)));
            if ($task->assignee_id !== null) {
                $unique = array_values(array_filter($unique, fn (int $id) => $id !== (int) $task->assignee_id));
            }
            $task->collaborators()->sync($unique);
        }

        if ($dirty !== [] || $syncCollaborators) {
            if ($dirty !== []) {
                $auditLogger->log('task.updated', $task, $dirty);
            }
            $task->refresh();
            WorkflowRealtime::task($task, 'updated');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tarea actualizada.']);

        return redirect()->back(302, [], route('proyecto.kanban', ['project_id' => $task->project_id]));
    }

    public function storeStatus(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'label' => 'required|string|max:100',
        ]);

        $project = Project::queryForUser($user)->whereKey($validated['project_id'])->firstOrFail();
        $key = KanbanStatus::makeKey($validated['label']);
        if ($key === '' || in_array($key, [Task::STATUS_BACKLOG, Task::STATUS_HECHA], true)) {
            return back()->withErrors(['label' => 'Nombre inválido para estado.']);
        }

        $maxPosition = (int) KanbanStatus::query()->where('project_id', $project->id)->max('position');
        KanbanStatus::query()->updateOrCreate(
            ['project_id' => $project->id, 'key' => $key],
            ['label' => $validated['label'], 'position' => $maxPosition + 1],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Estado Kanban agregado.']);

        return redirect()->back(302, [], route('proyecto.kanban', ['project_id' => $project->id]));
    }

    public function updateStatus(Request $request, Project $project, KanbanStatus $status): RedirectResponse
    {
        $user = $request->user();
        $project = Project::queryForUser($user)->whereKey($project->id)->firstOrFail();
        abort_unless((int) $status->project_id === (int) $project->id, 404);

        $validated = $request->validate([
            'label' => 'required|string|max:100',
        ]);
        $key = KanbanStatus::makeKey($validated['label']);
        if ($key === '' || in_array($key, [Task::STATUS_BACKLOG, Task::STATUS_HECHA], true)) {
            return back()->withErrors(['label' => 'Nombre inválido para estado.']);
        }

        if ($key !== $status->key) {
            Task::query()
                ->where('project_id', $project->id)
                ->where('status', $status->key)
                ->update(['status' => $key]);
        }

        $status->update(['label' => $validated['label'], 'key' => $key]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Estado Kanban actualizado.']);

        return redirect()->back(302, [], route('proyecto.kanban', ['project_id' => $project->id]));
    }

    public function destroyStatus(Request $request, Project $project, KanbanStatus $status): RedirectResponse
    {
        $user = $request->user();
        $project = Project::queryForUser($user)->whereKey($project->id)->firstOrFail();
        abort_unless((int) $status->project_id === (int) $project->id, 404);

        Task::query()
            ->where('project_id', $project->id)
            ->where('status', $status->key)
            ->update(['status' => Task::STATUS_BACKLOG]);

        $status->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Estado Kanban eliminado (tareas movidas a Backlog).']);

        return redirect()->back(302, [], route('proyecto.kanban', ['project_id' => $project->id]));
    }

    public function reorderStatuses(Request $request, Project $project): RedirectResponse
    {
        $user = $request->user();
        $project = Project::queryForUser($user)->whereKey($project->id)->firstOrFail();

        $validated = $request->validate([
            'statuses' => 'required|array|min:1',
            'statuses.*.key' => 'required|string|max:64',
            'statuses.*.label' => 'required|string|max:100',
        ]);

        $incoming = collect($validated['statuses'])
            ->map(fn (array $row) => [
                'key' => KanbanStatus::makeKey((string) $row['key']),
                'label' => trim((string) $row['label']),
            ])
            ->filter(fn (array $row) => $row['key'] !== '' && ! in_array($row['key'], [Task::STATUS_BACKLOG, Task::STATUS_HECHA], true))
            ->unique('key')
            ->values();

        if ($incoming->isEmpty()) {
            return back()->withErrors(['statuses' => 'Debes mantener al menos un estado intermedio.']);
        }

        DB::transaction(function () use ($project, $incoming): void {
            foreach ($incoming as $index => $row) {
                KanbanStatus::query()->updateOrCreate(
                    ['project_id' => $project->id, 'key' => $row['key']],
                    ['label' => $row['label'], 'position' => $index + 1],
                );
            }

            KanbanStatus::query()
                ->where('project_id', $project->id)
                ->whereNotIn('key', $incoming->pluck('key')->all())
                ->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Orden de estados Kanban actualizado.']);

        return redirect()->back(302, [], route('proyecto.kanban', ['project_id' => $project->id]));
    }

    /**
     * @return list<string>
     */
    private function allowedStatusesForProject(Project $project): array
    {
        return array_map(fn (array $s) => $s['key'], $this->statusOptions($project));
    }

    /**
     * @return list<array{id:int|null,key:string,label:string,is_system:bool,is_transversal:bool}>
     */
    private function statusOptions(Project $project): array
    {
        $effective = KanbanStatus::effectiveForProject($project);
        if ($effective->isEmpty()) {
            $custom = collect([
                ['id' => null, 'key' => Task::STATUS_PENDIENTE, 'label' => 'Pendiente', 'is_system' => false, 'is_transversal' => true],
                ['id' => null, 'key' => Task::STATUS_EN_CURSO, 'label' => 'En curso', 'is_system' => false, 'is_transversal' => true],
                ['id' => null, 'key' => Task::STATUS_REVISION, 'label' => 'Revisión', 'is_system' => false, 'is_transversal' => true],
            ])->all();
        } else {
            $custom = $effective
            ->map(fn (KanbanStatus $s) => [
                'id' => $s->id,
                'key' => $s->key,
                'label' => $s->label,
                'is_system' => false,
                'is_transversal' => $s->project_id === null,
            ])
            ->values()
            ->all();
        }

        return array_merge(
            [
                ['id' => null, 'key' => Task::STATUS_BACKLOG, 'label' => 'Backlog', 'is_system' => true, 'is_transversal' => false],
            ],
            $custom,
            [
                ['id' => null, 'key' => Task::STATUS_HECHA, 'label' => 'Hecha', 'is_system' => true, 'is_transversal' => false],
            ],
        );
    }
}
