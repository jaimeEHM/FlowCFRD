<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\User;
use Illuminate\Support\Collection;

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

        return $taskGroups->map(function (TaskGroup $group) use ($tasks) {
            $groupTasks = $tasks->where('task_group_id', $group->id);
            $columns = [];
            foreach (Task::STATUSES as $status) {
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
}
