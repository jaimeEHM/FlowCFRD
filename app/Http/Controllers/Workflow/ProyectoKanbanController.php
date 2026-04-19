<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProyectoKanbanController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $projects = Project::queryForUser($user)->orderBy('name')->get(['id', 'name', 'code']);

        $projectId = $request->integer('project_id');
        $project = $projectId
            ? Project::queryForUser($user)->whereKey($projectId)->first()
            : $projects->first();

        if ($project === null) {
            return Inertia::render('proyecto/Kanban', [
                'project' => null,
                'projects' => $projects,
                'columns' => [],
                'statuses' => Task::STATUSES,
            ]);
        }

        $tasks = Task::query()
            ->where('project_id', $project->id)
            ->with(['assignee:id,name,avatar'])
            ->orderBy('id')
            ->get()
            ->groupBy('status');

        $columns = [];
        foreach (Task::STATUSES as $status) {
            $columns[$status] = ($tasks->get($status) ?? collect())->values()->all();
        }

        return Inertia::render('proyecto/Kanban', [
            'project' => $project,
            'projects' => $projects,
            'columns' => $columns,
            'statuses' => Task::STATUSES,
        ]);
    }

    public function updateTask(Request $request, Task $task, AuditLogger $auditLogger): RedirectResponse
    {
        if (! Project::userMayAccess($request->user(), $task->project)) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|string|in:'.implode(',', Task::STATUSES),
        ]);

        $task->update(['status' => $request->string('status')->toString()]);

        $auditLogger->log('task.status_changed', $task, ['status' => $task->status]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Estado de tarea actualizado.']);

        return redirect()->route('proyecto.kanban', ['project_id' => $task->project_id]);
    }
}
