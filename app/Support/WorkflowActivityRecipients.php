<?php

namespace App\Support;

use App\Models\Project;
use App\Models\ProjectMinute;
use App\Models\SkillValidation;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Usuarios destinatarios de actividad Workflow (broadcast + notificaciones BD).
 * Misma lógica que los eventos ShouldBroadcast.
 */
final class WorkflowActivityRecipients
{
    /**
     * @return Collection<int, int>
     */
    public static function forTask(Task $task): Collection
    {
        $task->loadMissing([
            'project:id,name,jefe_proyecto_id,created_by_id',
            'collaborators:id',
        ]);

        $ids = collect();

        if ($task->assignee_id) {
            $ids->push((int) $task->assignee_id);
        }

        foreach ($task->collaborators as $user) {
            $ids->push((int) $user->id);
        }

        $project = $task->project;
        if ($project !== null) {
            if ($project->jefe_proyecto_id) {
                $ids->push((int) $project->jefe_proyecto_id);
            }
            if ($project->created_by_id) {
                $ids->push((int) $project->created_by_id);
            }
        }

        return $ids->unique()->filter()->values();
    }

    /**
     * Jefe, creador y colaboradores con tareas en el proyecto.
     *
     * @return Collection<int, int>
     */
    public static function forProject(Project $project): Collection
    {
        $ids = collect();

        if ($project->jefe_proyecto_id) {
            $ids->push((int) $project->jefe_proyecto_id);
        }
        if ($project->created_by_id) {
            $ids->push((int) $project->created_by_id);
        }

        $assignees = Task::query()
            ->where('project_id', $project->id)
            ->whereNotNull('assignee_id')
            ->pluck('assignee_id');

        $collabIds = DB::table('task_collaborators')
            ->join('tasks', 'tasks.id', '=', 'task_collaborators.task_id')
            ->where('tasks.project_id', $project->id)
            ->pluck('task_collaborators.user_id');

        return $ids->merge($assignees)->merge($collabIds)->unique()->filter()->values();
    }

    /**
     * Roles de oficina PMO y administración (alertas de cartera).
     *
     * @return Collection<int, int>
     */
    public static function forPmoAndAdmin(): Collection
    {
        return User::query()
            ->role(['pmo', 'admin'])
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    /**
     * @return Collection<int, int>
     */
    public static function forSkillValidation(SkillValidation $skillValidation): Collection
    {
        return collect([
            $skillValidation->subject_user_id,
            $skillValidation->validator_user_id,
        ])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * Autor de la minuta, jefe, creador del proyecto, integrantes con tareas.
     *
     * @return Collection<int, int>
     */
    public static function forProjectMinute(ProjectMinute $minute): Collection
    {
        $minute->loadMissing([
            'project:id,name,jefe_proyecto_id,created_by_id',
        ]);

        $ids = collect();

        if ($minute->created_by_id) {
            $ids->push((int) $minute->created_by_id);
        }

        $project = $minute->project;
        if ($project === null) {
            return $ids->unique()->filter()->values();
        }

        if ($project->jefe_proyecto_id) {
            $ids->push((int) $project->jefe_proyecto_id);
        }
        if ($project->created_by_id) {
            $ids->push((int) $project->created_by_id);
        }

        $assignees = Task::query()
            ->where('project_id', $project->id)
            ->whereNotNull('assignee_id')
            ->pluck('assignee_id');

        $collabIds = DB::table('task_collaborators')
            ->join('tasks', 'tasks.id', '=', 'task_collaborators.task_id')
            ->where('tasks.project_id', $project->id)
            ->pluck('task_collaborators.user_id');

        return $ids->merge($assignees)->merge($collabIds)->unique()->filter()->values();
    }
}
