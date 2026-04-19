<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Datos de la vista «lista de tareas» por proyecto (Tabla y tablero macro).
 */
final class ProyectoTaskListPayload
{
    /**
     * @return array{
     *     taskGroups: list<array{id: int, name: string, color: string, position: int, progress_percent: int}>,
     *     tasks: list<array<string, mixed>>,
     *     peopleOptions: Collection<int, User>
     * }
     */
    public static function forProject(Project $project): array
    {
        $project->load(['taskGroups' => fn ($q) => $q->orderBy('position')->orderBy('id')]);

        if ($project->taskGroups->isEmpty()) {
            TaskGroup::ensureGeneral($project);
            $project->load(['taskGroups' => fn ($q) => $q->orderBy('position')->orderBy('id')]);
        }

        $tasks = Task::query()
            ->where('project_id', $project->id)
            ->with(['assignee:id,name,avatar', 'collaborators:id,name,avatar'])
            ->orderBy('task_group_id')
            ->orderBy('kanban_order')
            ->orderBy('id')
            ->get();

        $peopleOptions = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'pmo', 'coordinador', 'jefe_proyecto', 'colaborador']))
            ->orderBy('name')
            ->get(['id', 'name', 'avatar']);

        $taskGroups = $project->taskGroups->map(function (TaskGroup $group) use ($tasks, $project) {
            $groupTasks = $tasks->where('task_group_id', $group->id);
            $total = $groupTasks->count();
            $hechas = $groupTasks->where('status', Task::STATUS_HECHA)->count();

            return [
                'id' => $group->id,
                'name' => $group->name,
                'color' => $group->color,
                'position' => $group->position,
                'progress_percent' => $total > 0 ? (int) round(($hechas / $total) * 100) : 0,
                'project_id' => (int) $project->id,
            ];
        })->values()->all();

        $tasksPayload = $tasks->map(function (Task $t) use ($project) {
            return [
                'id' => $t->id,
                'project_id' => (int) $project->id,
                'title' => $t->title,
                'description' => $t->description,
                'status' => $t->status,
                'due_date' => $t->due_date?->format('Y-m-d'),
                'task_group_id' => $t->task_group_id,
                'assignee' => $t->assignee
                    ? [
                        'id' => $t->assignee->id,
                        'name' => $t->assignee->name,
                        'avatar' => $t->assignee->avatar,
                    ]
                    : null,
                'collaborators' => $t->collaborators
                    ->map(fn ($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                        'avatar' => $c->avatar,
                    ])
                    ->values()
                    ->all(),
            ];
        })->values()->all();

        return [
            'taskGroups' => $taskGroups,
            'tasks' => $tasksPayload,
            'peopleOptions' => $peopleOptions,
        ];
    }

    /**
     * Todas las tareas de todos los proyectos visibles (tablero macro → lista sin proyecto enfocado).
     *
     * @return array{
     *     taskGroups: list<array<string, mixed>>,
     *     tasks: list<array<string, mixed>>,
     *     peopleOptions: Collection<int, User>
     * }
     */
    public static function forPortfolio(Collection $projects): array
    {
        if ($projects->isEmpty()) {
            return [
                'taskGroups' => [],
                'tasks' => [],
                'peopleOptions' => User::query()
                    ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'pmo', 'coordinador', 'jefe_proyecto', 'colaborador']))
                    ->orderBy('name')
                    ->get(['id', 'name', 'avatar']),
            ];
        }

        foreach ($projects as $project) {
            TaskGroup::ensureGeneral($project);
        }

        $projectIds = $projects->pluck('id');

        $projectsOrdered = Project::query()
            ->whereIn('id', $projectIds)
            ->with(['taskGroups' => fn ($q) => $q->orderBy('position')->orderBy('id')])
            ->orderBy('name')
            ->get();

        $tasks = Task::query()
            ->whereIn('project_id', $projectIds)
            ->with(['assignee:id,name,avatar', 'collaborators:id,name,avatar'])
            ->orderBy('project_id')
            ->orderBy('task_group_id')
            ->orderBy('kanban_order')
            ->orderBy('id')
            ->get();

        $peopleOptions = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'pmo', 'coordinador', 'jefe_proyecto', 'colaborador']))
            ->orderBy('name')
            ->get(['id', 'name', 'avatar']);

        $taskGroups = [];
        $position = 0;
        foreach ($projectsOrdered as $project) {
            foreach ($project->taskGroups as $group) {
                $groupTasks = $tasks->where('task_group_id', $group->id);
                $total = $groupTasks->count();
                $hechas = $groupTasks->where('status', Task::STATUS_HECHA)->count();

                $taskGroups[] = [
                    'id' => $group->id,
                    'name' => $project->name.' — '.$group->name,
                    'color' => $group->color,
                    'position' => $position++,
                    'progress_percent' => $total > 0 ? (int) round(($hechas / $total) * 100) : 0,
                    'project_id' => (int) $project->id,
                ];
            }
        }

        $tasksPayload = $tasks->map(function (Task $t) {
            return [
                'id' => $t->id,
                'project_id' => (int) $t->project_id,
                'title' => $t->title,
                'description' => $t->description,
                'status' => $t->status,
                'due_date' => $t->due_date?->format('Y-m-d'),
                'task_group_id' => $t->task_group_id,
                'assignee' => $t->assignee
                    ? [
                        'id' => $t->assignee->id,
                        'name' => $t->assignee->name,
                        'avatar' => $t->assignee->avatar,
                    ]
                    : null,
                'collaborators' => $t->collaborators
                    ->map(fn ($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                        'avatar' => $c->avatar,
                    ])
                    ->values()
                    ->all(),
            ];
        })->values()->all();

        return [
            'taskGroups' => $taskGroups,
            'tasks' => $tasksPayload,
            'peopleOptions' => $peopleOptions,
        ];
    }
}
