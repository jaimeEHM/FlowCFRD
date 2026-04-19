<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class PmoIndicadoresController extends Controller
{
    public function __invoke(): Response
    {
        $projectsByStatus = Project::query()
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $tasksByStatus = Task::query()
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return Inertia::render('pmo/Indicadores', [
            'usuarios_total' => User::query()->count(),
            'proyectos_total' => Project::query()->count(),
            'tareas_total' => Task::query()->count(),
            'tareas_urgentes_pendientes' => Task::query()
                ->where('is_urgent', true)
                ->where('validation_status', Task::VALIDATION_PENDIENTE)
                ->count(),
            'projects_by_status' => $projectsByStatus,
            'tasks_by_status' => $tasksByStatus,
        ]);
    }
}
