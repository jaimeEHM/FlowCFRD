<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Services\AuditLogger;
use App\Support\WorkflowRealtime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CoordinacionBacklogController extends Controller
{
    public function index(): Response
    {
        $tasks = Task::query()
            ->with(['project:id,name,code', 'assignee:id,name,avatar'])
            ->orderBy('project_id')
            ->orderBy('backlog_order')
            ->orderBy('id')
            ->get();

        $projects = Project::query()->orderBy('name')->get(['id', 'name', 'code']);

        return Inertia::render('coordinacion/BacklogTareas', [
            'tasks' => $tasks,
            'projects' => $projects,
            'statuses' => Task::STATUSES,
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_urgent' => 'sometimes|boolean',
        ]);

        $validated['is_urgent'] = $request->boolean('is_urgent');
        $validated['status'] = Task::STATUS_BACKLOG;
        $validated['created_by_id'] = $request->user()->id;
        if ($validated['is_urgent']) {
            $validated['validation_status'] = Task::VALIDATION_PENDIENTE;
        }

        $project = Project::query()->findOrFail($validated['project_id']);
        $group = TaskGroup::ensureGeneral($project);
        $validated['task_group_id'] = $group->id;
        $maxOrder = (int) Task::query()
            ->where('task_group_id', $group->id)
            ->where('status', Task::STATUS_BACKLOG)
            ->max('kanban_order');
        $validated['kanban_order'] = $maxOrder + 1;

        $task = Task::query()->create($validated);

        $auditLogger->log('task.created', $task, ['title' => $task->title]);

        $task->refresh();
        WorkflowRealtime::task($task, 'created');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tarea añadida al backlog.']);

        return redirect()->route('coordinacion.backlog-tareas');
    }
}
