<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ColaboradorMisTareasController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tasks = Task::query()
            ->where('assignee_id', $request->user()->id)
            ->with(['project:id,name,code'])
            ->orderBy('status')
            ->orderBy('due_date')
            ->get();

        return Inertia::render('colaborador/MisTareas', [
            'tasks' => $tasks,
        ]);
    }
}
