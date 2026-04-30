<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\WorkflowRealtime;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as SymfonyResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PmoProyectosController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(Project::STATUSES)],
            'sort' => ['nullable', Rule::in(['name', 'status', 'carta_inicio_at', 'updated_at'])],
            'dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $q = isset($validated['q']) ? trim((string) $validated['q']) : '';
        $status = $validated['status'] ?? null;
        $sort = $validated['sort'] ?? 'updated_at';
        $dir = $validated['dir'] ?? 'desc';

        $projects = Project::query()
            ->with(['jefeProyecto:id,name,email', 'createdBy:id,name'])
            ->withCount([
                'tasks as tasks_total',
                'tasks as tasks_abiertas' => fn (Builder $query) => $query->where('status', '!=', \App\Models\Task::STATUS_HECHA),
                'members',
            ])
            ->when($q !== '', function (Builder $query) use ($q): void {
                $query->where(function (Builder $inner) use ($q): void {
                    $inner
                        ->where('name', 'like', '%'.$q.'%')
                        ->orWhere('code', 'like', '%'.$q.'%')
                        ->orWhereHas('jefeProyecto', fn (Builder $jefe) => $jefe->where('name', 'like', '%'.$q.'%'));
                });
            })
            ->when($status !== null && $status !== '', fn (Builder $query) => $query->where('status', $status))
            ->orderBy($sort, $dir)
            ->paginate(15)
            ->withQueryString();
        $projects->through(function (Project $project): array {
            return [
                'id' => $project->id,
                'name' => $project->name,
                'code' => $project->code,
                'status' => $project->status,
                'carta_inicio_at' => optional($project->carta_inicio_at)?->toDateString(),
                'starts_at' => optional($project->starts_at)?->toDateString(),
                'ends_at' => optional($project->ends_at)?->toDateString(),
                'updated_at' => optional($project->updated_at)?->toDateTimeString(),
                'tasks_total' => (int) ($project->tasks_total ?? 0),
                'tasks_abiertas' => (int) ($project->tasks_abiertas ?? 0),
                'members_count' => (int) ($project->members_count ?? 0),
                'has_acta' => $project->acta_constitucion_path !== null && $project->acta_constitucion_path !== '',
                'jefe_proyecto' => $project->jefeProyecto !== null
                    ? ['name' => $project->jefeProyecto->name, 'email' => $project->jefeProyecto->email]
                    : null,
                'created_by' => $project->createdBy !== null ? ['name' => $project->createdBy->name] : null,
            ];
        });

        return Inertia::render('pmo/Proyectos', [
            'projects' => $projects,
            'statuses' => Project::STATUSES,
            'filters' => [
                'q' => $q,
                'status' => $status,
                'sort' => $sort,
                'dir' => $dir,
            ],
        ]);
    }

    public function downloadActaConstitucion(Project $project): SymfonyResponse
    {
        abort_unless(request()->user()?->hasRole(['admin', 'pmo']), 403);
        abort_if($project->acta_constitucion_path === null || $project->acta_constitucion_path === '', 404);
        abort_unless(Storage::disk('local')->exists($project->acta_constitucion_path), 404);

        return Storage::disk('local')->download(
            $project->acta_constitucion_path,
            $project->acta_constitucion_original_name ?? 'acta-constitucion.pdf',
        );
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
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'integer|exists:users,id',
            'acta_constitucion' => 'nullable|file|mimes:pdf,doc,docx|max:15360',
        ]);

        $actaFile = $request->file('acta_constitucion');
        if (array_key_exists('acta_constitucion', $validated)) {
            unset($validated['acta_constitucion']);
        }

        foreach (['carta_inicio_at', 'starts_at', 'ends_at', 'code', 'description', 'jefe_proyecto_id'] as $nullable) {
            if (array_key_exists($nullable, $validated) && $validated[$nullable] === '') {
                $validated[$nullable] = null;
            }
        }

        $validated['created_by_id'] = $request->user()->id;

        $memberIds = collect((array) ($validated['member_ids'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
        if (! empty($validated['jefe_proyecto_id'])) {
            $memberIds->push((int) $validated['jefe_proyecto_id']);
        }
        $memberIds = $memberIds->unique()->values()->all();
        unset($validated['member_ids']);

        $project = Project::query()->create($validated);
        $project->members()->syncWithoutDetaching($memberIds);

        if ($actaFile instanceof UploadedFile) {
            $this->storeActaConstitucionFile($project, $actaFile);
        }

        $auditLogger->log('project.created', $project, ['name' => $project->name]);

        $project->refresh();
        WorkflowRealtime::project($project, 'created');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proyecto creado correctamente.']);

        return back();
    }

    public function update(
        Request $request,
        Project $project,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $this->normalizeEmptyStrings($request, [
            'code', 'carta_inicio_at', 'starts_at', 'ends_at', 'description', 'jefe_proyecto_id',
        ]);

        $jefeIdsPermitidos = User::query()->role('jefe_proyecto')->pluck('id')->all();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:64|unique:projects,code,'.$project->id,
            'description' => 'nullable|string',
            'carta_inicio_at' => 'nullable|date',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'status' => 'sometimes|required|string|in:'.implode(',', Project::STATUSES),
            'jefe_proyecto_id' => ['nullable', Rule::in($jefeIdsPermitidos)],
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'integer|exists:users,id',
            'acta_constitucion' => 'nullable|file|mimes:pdf,doc,docx|max:15360',
            'remove_acta_constitucion' => 'sometimes|boolean',
        ]);

        $removeActa = $request->boolean('remove_acta_constitucion');
        $actaFile = $request->file('acta_constitucion');
        $memberIds = collect((array) ($validated['member_ids'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
        if (! empty($validated['jefe_proyecto_id'])) {
            $memberIds->push((int) $validated['jefe_proyecto_id']);
        }
        $memberIds = $memberIds->unique()->values()->all();
        unset($validated['member_ids'], $validated['acta_constitucion'], $validated['remove_acta_constitucion']);

        $this->assertProjectDateRange($project, $validated);

        $project->fill($validated);

        if ($removeActa) {
            $this->deleteActaConstitucionFile($project);
            $project->acta_constitucion_path = null;
            $project->acta_constitucion_original_name = null;
        }

        $dirty = $project->getDirty();
        $project->save();
        $project->members()->sync($memberIds);

        if ($actaFile instanceof UploadedFile) {
            $this->storeActaConstitucionFile($project, $actaFile);
        }

        $project->refresh();

        if ($dirty !== [] || $removeActa || $actaFile instanceof UploadedFile) {
            $auditLogger->log(
                'project.updated',
                $project,
                $dirty !== [] ? $dirty : ['acta_constitucion' => true],
            );
            WorkflowRealtime::project($project, 'updated');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proyecto actualizado.']);

        return back();
    }

    /**
     * @param  list<string>  $keys
     */
    private function normalizeEmptyStrings(Request $request, array $keys): void
    {
        $data = $request->all();

        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }

        $request->merge($data);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function assertProjectDateRange(Project $project, array $validated): void
    {
        $starts = array_key_exists('starts_at', $validated)
            ? ($validated['starts_at'] !== null ? Carbon::parse($validated['starts_at']) : null)
            : $project->starts_at;
        $ends = array_key_exists('ends_at', $validated)
            ? ($validated['ends_at'] !== null ? Carbon::parse($validated['ends_at']) : null)
            : $project->ends_at;

        if ($starts !== null && $ends !== null && $ends->lt($starts)) {
            throw ValidationException::withMessages([
                'ends_at' => 'La fecha de fin debe ser igual o posterior al inicio.',
            ]);
        }
    }

    private function storeActaConstitucionFile(Project $project, UploadedFile $file): void
    {
        $this->deleteActaConstitucionFile($project);
        $path = $file->store('project-actas/'.$project->id, 'local');
        $project->forceFill([
            'acta_constitucion_path' => $path,
            'acta_constitucion_original_name' => $file->getClientOriginalName(),
        ])->save();
    }

    private function deleteActaConstitucionFile(Project $project): void
    {
        if ($project->acta_constitucion_path === null || $project->acta_constitucion_path === '') {
            return;
        }
        if (Storage::disk('local')->exists($project->acta_constitucion_path)) {
            Storage::disk('local')->delete($project->acta_constitucion_path);
        }
    }
}
