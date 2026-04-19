<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Models\Project;
use App\Services\AuditLogger;
use App\Support\WorkflowRealtime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $projects = Project::queryForUser($user)
            ->with(['jefeProyecto:id,name,email'])
            ->orderByDesc('updated_at')
            ->get();

        return ProjectResource::collection($projects);
    }

    public function show(Request $request, Project $project): ProjectResource
    {
        if (! Project::userMayAccessIncludingColaborador($request->user(), $project)) {
            abort(403);
        }

        $project->load(['jefeProyecto:id,name,email', 'createdBy:id,name']);

        return new ProjectResource($project);
    }

    public function store(Request $request, AuditLogger $auditLogger): JsonResponse
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
        $project->load(['jefeProyecto:id,name,email']);

        $auditLogger->log('project.created', $project, ['name' => $project->name]);

        $project->refresh();
        WorkflowRealtime::project($project, 'created');

        $response = (new ProjectResource($project))->toResponse($request);
        $response->setStatusCode(201);

        return $response;
    }

    public function update(Request $request, Project $project, AuditLogger $auditLogger): JsonResponse
    {
        if (! Project::userMayAccess($request->user(), $project)) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:64|unique:projects,code,'.$project->id,
            'description' => 'nullable|string',
            'carta_inicio_at' => 'nullable|date',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'status' => 'sometimes|required|string|in:'.implode(',', Project::STATUSES),
            'jefe_proyecto_id' => 'nullable|exists:users,id',
        ]);

        foreach (['carta_inicio_at', 'starts_at', 'ends_at', 'code', 'description', 'jefe_proyecto_id'] as $nullable) {
            if (array_key_exists($nullable, $validated) && $validated[$nullable] === '') {
                $validated[$nullable] = null;
            }
        }

        $project->fill($validated);
        $dirty = $project->getDirty();
        $project->save();

        if ($dirty !== []) {
            $auditLogger->log('project.updated', $project, $dirty);
            $project->refresh();
            WorkflowRealtime::project($project, 'updated');
        }

        $project->load(['jefeProyecto:id,name,email']);

        return (new ProjectResource($project))->toResponse($request);
    }
}
