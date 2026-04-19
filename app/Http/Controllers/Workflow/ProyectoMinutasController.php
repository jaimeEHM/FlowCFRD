<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMinute;
use App\Services\AuditLogger;
use App\Support\WorkflowRealtime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProyectoMinutasController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $projectIds = Project::queryForUser($user)->pluck('id');

        $minutesQuery = ProjectMinute::query()
            ->whereIn('project_id', $projectIds)
            ->with(['project:id,name,code', 'createdBy:id,name']);

        if ($request->filled('project_id')) {
            $pid = (int) $request->input('project_id');
            if ($projectIds->contains($pid)) {
                $minutesQuery->where('project_id', $pid);
            }
        }

        $minutes = $minutesQuery->orderByDesc('held_at')->get();

        $projects = Project::queryForUser($user)->orderBy('name')->get(['id', 'name', 'code']);

        $filterProjectId = $request->filled('project_id')
            ? (int) $request->input('project_id')
            : null;

        $workspaceProject = null;
        if ($filterProjectId !== null && $projectIds->contains($filterProjectId)) {
            $workspaceProject = Project::queryForUser($user)
                ->whereKey($filterProjectId)
                ->first(['id', 'name', 'code']);
        } elseif ($projects->isNotEmpty()) {
            $workspaceProject = $projects->first();
        }

        return Inertia::render('proyecto/Minutas', [
            'minutes' => $minutes,
            'projects' => $projects,
            'filter_project_id' => $filterProjectId,
            'workspace_project' => $workspaceProject,
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'held_at' => 'required|date',
        ]);

        $project = Project::query()->findOrFail($validated['project_id']);
        if (! Project::userMayAccess($request->user(), $project)) {
            abort(403);
        }

        $validated['created_by_id'] = $request->user()->id;

        $minute = ProjectMinute::query()->create($validated);

        $auditLogger->log('project_minute.created', $minute, ['title' => $minute->title]);

        $minute->refresh();
        WorkflowRealtime::projectMinuteCreated($minute);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Minuta registrada.']);

        return redirect()->route('proyecto.minutas');
    }
}
