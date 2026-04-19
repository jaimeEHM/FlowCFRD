<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class PmoGanttController extends Controller
{
    public function __invoke(): Response
    {
        $projects = Project::query()
            ->whereNotNull('starts_at')
            ->orderBy('starts_at')
            ->get(['id', 'name', 'code', 'starts_at', 'ends_at', 'status']);

        return Inertia::render('pmo/Gantt', [
            'projects' => $projects,
        ]);
    }
}
