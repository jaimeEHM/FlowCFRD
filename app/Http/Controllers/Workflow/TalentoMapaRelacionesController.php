<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TalentoMapaRelacionesController extends Controller
{
    public function __invoke(): Response
    {
        $edges = Task::query()
            ->whereNotNull('assignee_id')
            ->join('users', 'users.id', '=', 'tasks.assignee_id')
            ->join('projects', 'projects.id', '=', 'tasks.project_id')
            ->select([
                'tasks.assignee_id as user_id',
                'tasks.project_id',
                'users.name as user_name',
                'projects.name as project_name',
                DB::raw('count(*) as tareas_vinculadas'),
            ])
            ->groupBy(
                'tasks.assignee_id',
                'tasks.project_id',
                'users.name',
                'projects.name',
            )
            ->orderBy('users.name')
            ->orderBy('projects.name')
            ->get();

        $nodesUsers = $edges->pluck('user_id')->unique()->values();
        $nodesProjects = $edges->pluck('project_id')->unique()->values();

        return Inertia::render('talento/MapaRelaciones', [
            'edges' => $edges,
            'nodes_summary' => [
                'usuarios' => $nodesUsers->count(),
                'proyectos' => $nodesProjects->count(),
                'relaciones' => $edges->count(),
            ],
        ]);
    }
}
