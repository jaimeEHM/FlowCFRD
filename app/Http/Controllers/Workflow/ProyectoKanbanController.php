<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
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
        $project = $projectId
            ? Project::queryForUser($user)->whereKey($projectId)->first()
            : $projects->first();

        if ($project === null) {
            return Inertia::render('proyecto/Kanban', [
                'project' => null,
                'projects' => $projects,
                'groups' => [],
                'statuses' => Task::STATUSES,
                'peopleOptions' => [],
            ]);
        }

        $groups = ProyectoKanbanPayload::groupsForProject($project);
        $peopleOptions = ProyectoKanbanPayload::peopleOptions();

        return Inertia::render('proyecto/Kanban', [
            'project' => $project->only(['id', 'name', 'code']),
            'projects' => $projects,
            'groups' => $groups,
            'statuses' => Task::STATUSES,
            'peopleOptions' => $peopleOptions,
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
            'orders.*.status' => 'required|string|in:'.implode(',', Task::STATUSES),
            'orders.*.task_group_id' => 'required|exists:task_groups,id',
            'orders.*.kanban_order' => 'required|integer|min:0',
        ]);

        $project = Project::queryForUser($user)->whereKey($validated['project_id'])->firstOrFail();

        $groupIds = TaskGroup::query()->where('project_id', $project->id)->pluck('id')->all();

        DB::transaction(function () use ($validated, $project, $groupIds): void {
            foreach ($validated['orders'] as $row) {
                $task = Task::query()->whereKey($row['task_id'])->where('project_id', $project->id)->firstOrFail();
                if (! in_array((int) $row['task_group_id'], array_map('intval', $groupIds), true)) {
                    abort(422, 'Grupo inválido para este proyecto.');
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
            'status' => 'sometimes|required|string|in:'.implode(',', Task::STATUSES),
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
}
