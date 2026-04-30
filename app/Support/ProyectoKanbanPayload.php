<?php

namespace App\Support;

use App\Models\Project;
use App\Models\KanbanStatus;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Grupos/columnas Kanban y opciones de personas (Kanban proyecto y tablero macro).
 */
final class ProyectoKanbanPayload
{
    /**
     * @return list<array{
     *     id: int,
     *     name: string,
     *     color: string,
     *     position: int,
     *     columns: array<string, list<array<string, mixed>>>,
     *     progress_percent: int
     * }>
     */
    public static function groupsForProject(Project $project): array
    {
        $project->load(['taskGroups' => fn ($q) => $q->orderBy('position')->orderBy('id')]);

        if ($project->taskGroups->isEmpty()) {
            TaskGroup::ensureGeneral($project);
            $project->load(['taskGroups' => fn ($q) => $q->orderBy('position')->orderBy('id')]);
        }

        $transversalEnabled = (bool) config('workflow.transversal_group.enabled', false);
        $transversalName = (string) config('workflow.transversal_group.name', 'Línea transversal');
        $taskGroups = $project->taskGroups->filter(
            fn (TaskGroup $group) => $transversalEnabled || $group->name !== $transversalName
        )->values();

        $tasks = Task::query()
            ->where('project_id', $project->id)
            ->with([
                'assignee:id,name,avatar',
                'collaborators:id,name,avatar',
            ])
            ->orderBy('kanban_order')
            ->orderBy('id')
            ->get();

        $statusKeys = KanbanStatus::effectiveForProject($project)->pluck('key')->values()->all();
        if ($statusKeys === []) {
            $statusKeys = [Task::STATUS_PENDIENTE, Task::STATUS_EN_CURSO, Task::STATUS_REVISION];
        }
        $fullStatuses = array_merge([Task::STATUS_BACKLOG], $statusKeys, [Task::STATUS_HECHA]);

        return $taskGroups->map(function (TaskGroup $group) use ($tasks, $fullStatuses) {
            $groupTasks = $tasks->where('task_group_id', $group->id);
            $columns = [];
            foreach ($fullStatuses as $status) {
                $columns[$status] = $groupTasks->where('status', $status)->values()->all();
            }
            $total = $groupTasks->count();
            $hechas = $groupTasks->where('status', Task::STATUS_HECHA)->count();

            return [
                'id' => $group->id,
                'name' => $group->name,
                'color' => $group->color,
                'position' => $group->position,
                'columns' => $columns,
                'progress_percent' => $total > 0 ? (int) round(($hechas / $total) * 100) : 0,
            ];
        })->values()->all();
    }

    /**
     * @return Collection<int, User>
     */
    public static function peopleOptions(): Collection
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'pmo', 'coordinador', 'jefe_proyecto', 'colaborador']))
            ->orderBy('name')
            ->get(['id', 'name', 'avatar']);
    }

    /**
     * Personas disponibles en un proyecto:
     * - miembros asignados al proyecto
     * - jefe de proyecto
     * - personas ya usadas en tareas del proyecto (responsable/colaborador)
     *
     * @return Collection<int, User>
     */
    public static function peopleOptionsForProject(Project $project): Collection
    {
        $memberIds = $project->members()->pluck('users.id');

        $taskAssigneeIds = Task::query()
            ->where('project_id', $project->id)
            ->whereNotNull('assignee_id')
            ->pluck('assignee_id');

        $taskCollaboratorIds = DB::table('task_collaborators')
            ->join('tasks', 'tasks.id', '=', 'task_collaborators.task_id')
            ->where('tasks.project_id', $project->id)
            ->pluck('task_collaborators.user_id');

        $eligibleIds = $memberIds
            ->merge($taskAssigneeIds)
            ->merge($taskCollaboratorIds)
            ->when(
                $project->jefe_proyecto_id !== null,
                fn (Collection $c) => $c->push((int) $project->jefe_proyecto_id),
            )
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($eligibleIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $eligibleIds->all())
            ->orderBy('name')
            ->get(['id', 'name', 'avatar']);
    }
}
