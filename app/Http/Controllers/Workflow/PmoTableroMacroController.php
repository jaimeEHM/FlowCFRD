<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Inertia\Inertia;
use Inertia\Response;

class PmoTableroMacroController extends Controller
{
    public function __invoke(): Response
    {
        $projects = Project::query()
            ->with('jefeProyecto:id,name')
            ->withCount(['tasks as tasks_abiertas' => fn ($q) => $q->where('status', '!=', Task::STATUS_HECHA)])
            ->orderBy('name')
            ->get();

        return Inertia::render('pmo/TableroMacro', [
            'projects' => $projects,
        ]);
    }
}
