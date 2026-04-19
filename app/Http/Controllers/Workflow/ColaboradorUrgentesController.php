<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ColaboradorUrgentesController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tasks = Task::query()
            ->where('assignee_id', $request->user()->id)
            ->where('is_urgent', true)
            ->with(['project:id,name,code'])
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('colaborador/Urgentes', [
            'tasks' => $tasks,
        ]);
    }
}
