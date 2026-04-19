<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PmoProyectosController extends Controller
{
    public function index(): Response
    {
        $projects = Project::query()
            ->with(['jefeProyecto:id,name,email', 'createdBy:id,name'])
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('pmo/Proyectos', [
            'projects' => $projects,
            'statuses' => Project::STATUSES,
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:64|unique:projects,code',
            'description' => 'nullable|string',
            'carta_inicio_at' => 'nullable|date',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'status' => 'required|string|in:'.implode(',', Project::STATUSES),
            'jefe_proyecto_id' => 'nullable|exists:users,id',
        ]);

        foreach (['carta_inicio_at', 'starts_at', 'ends_at', 'code', 'description', 'jefe_proyecto_id'] as $nullable) {
            if (array_key_exists($nullable, $validated) && $validated[$nullable] === '') {
                $validated[$nullable] = null;
            }
        }

        $validated['created_by_id'] = $request->user()->id;

        $project = Project::query()->create($validated);

        $auditLogger->log('project.created', $project, ['name' => $project->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proyecto creado correctamente.']);

        return redirect()->route('pmo.proyectos');
    }
}
