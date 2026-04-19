<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMinute;
use App\Services\AuditLogger;
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

        $minutes = ProjectMinute::query()
            ->whereIn('project_id', $projectIds)
            ->with(['project:id,name,code', 'createdBy:id,name'])
            ->orderByDesc('held_at')
            ->get();

        $projects = Project::queryForUser($user)->orderBy('name')->get(['id', 'name', 'code']);

        return Inertia::render('proyecto/Minutas', [
            'minutes' => $minutes,
            'projects' => $projects,
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

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Minuta registrada.']);

        return redirect()->route('proyecto.minutas');
    }
}
