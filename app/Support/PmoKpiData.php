<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Métricas agregadas para KPI PMO (tablero macro e indicadores).
 */
final class PmoKpiData
{
    /**
     * @return array{
     *     usuarios_total: int,
     *     proyectos_total: int,
     *     tareas_total: int,
     *     tareas_abiertas: int,
     *     tareas_urgentes_pendientes: int,
     *     projects_by_status: array<string, int>,
     *     tasks_by_status: array<string, int>,
     *     tasks_abiertas_por_responsable: array<string, int>,
     * }
     */
    public static function snapshot(): array
    {
        $projectsByStatus = Project::query()
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $tasksByStatus = Task::query()
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $openTasksByUser = Task::query()
            ->whereNot('status', Task::STATUS_HECHA)
            ->whereNotNull('assignee_id')
            ->selectRaw('assignee_id, count(*) as c')
            ->groupBy('assignee_id')
            ->orderByDesc('c')
            ->limit(12)
            ->get();

        $userIds = $openTasksByUser->pluck('assignee_id')->all();
        $names = User::query()->whereIn('id', $userIds)->pluck('name', 'id');

        $tasksAbiertasPorResponsable = [];
        foreach ($openTasksByUser as $row) {
            $label = $names[$row->assignee_id] ?? ('#'.$row->assignee_id);
            $tasksAbiertasPorResponsable[$label] = (int) $row->c;
        }

        return [
            'usuarios_total' => User::query()->count(),
            'proyectos_total' => Project::query()->count(),
            'tareas_total' => Task::query()->count(),
            'tareas_abiertas' => Task::query()->whereNot('status', Task::STATUS_HECHA)->count(),
            'tareas_urgentes_pendientes' => Task::query()
                ->where('is_urgent', true)
                ->where('validation_status', Task::VALIDATION_PENDIENTE)
                ->count(),
            'projects_by_status' => $projectsByStatus->all(),
            'tasks_by_status' => $tasksByStatus->all(),
            'tasks_abiertas_por_responsable' => $tasksAbiertasPorResponsable,
        ];
    }

    /**
     * Métricas limitadas a un proyecto (tablero macro con `project_id` y segmento KPI).
     *
     * @return array{
     *     usuarios_total: int,
     *     proyectos_total: int,
     *     tareas_total: int,
     *     tareas_abiertas: int,
     *     tareas_urgentes_pendientes: int,
     *     projects_by_status: array<string, int>,
     *     tasks_by_status: array<string, int>,
     *     tasks_abiertas_por_responsable: array<string, int>,
     * }
     */
    public static function snapshotForProject(Project $project): array
    {
        $pid = $project->id;

        $projectsByStatus = collect([$project->status => 1]);

        $tasksByStatus = Task::query()
            ->where('project_id', $pid)
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $openTasksByUser = Task::query()
            ->where('project_id', $pid)
            ->whereNot('status', Task::STATUS_HECHA)
            ->whereNotNull('assignee_id')
            ->selectRaw('assignee_id, count(*) as c')
            ->groupBy('assignee_id')
            ->orderByDesc('c')
            ->limit(12)
            ->get();

        $userIds = $openTasksByUser->pluck('assignee_id')->all();
        $names = User::query()->whereIn('id', $userIds)->pluck('name', 'id');

        $tasksAbiertasPorResponsable = [];
        foreach ($openTasksByUser as $row) {
            $label = $names[$row->assignee_id] ?? ('#'.$row->assignee_id);
            $tasksAbiertasPorResponsable[$label] = (int) $row->c;
        }

        $taskIds = Task::query()->where('project_id', $pid)->pluck('id');
        $assigneeIds = Task::query()
            ->where('project_id', $pid)
            ->whereNotNull('assignee_id')
            ->pluck('assignee_id');
        $collabIds = $taskIds->isEmpty()
            ? collect()
            : DB::table('task_collaborators')->whereIn('task_id', $taskIds)->pluck('user_id');

        $usuariosInvolucrados = $assigneeIds
            ->merge($collabIds)
            ->unique()
            ->filter(fn ($id) => $id !== null && $id !== 0)
            ->count();

        return [
            'usuarios_total' => $usuariosInvolucrados,
            'proyectos_total' => 1,
            'tareas_total' => Task::query()->where('project_id', $pid)->count(),
            'tareas_abiertas' => Task::query()
                ->where('project_id', $pid)
                ->whereNot('status', Task::STATUS_HECHA)
                ->count(),
            'tareas_urgentes_pendientes' => Task::query()
                ->where('project_id', $pid)
                ->where('is_urgent', true)
                ->where('validation_status', Task::VALIDATION_PENDIENTE)
                ->count(),
            'projects_by_status' => $projectsByStatus->all(),
            'tasks_by_status' => $tasksByStatus->all(),
            'tasks_abiertas_por_responsable' => $tasksAbiertasPorResponsable,
        ];
    }
}
