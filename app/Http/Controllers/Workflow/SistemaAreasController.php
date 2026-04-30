<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SistemaAreasController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user()?->hasRole(['admin', 'pmo', 'coordinador']), 403);

        $areas = Area::query()
            ->withCount('users')
            ->with(['users:id,name,cargo,email', 'coordinator:id,name,email,cargo'])
            ->orderBy('name')
            ->get()
            ->map(fn (Area $a) => [
                'id' => $a->id,
                'name' => $a->name,
                'slug' => $a->slug,
                'description' => $a->description,
                'is_active' => (bool) $a->is_active,
                'coordinator_user_id' => $a->coordinator_user_id,
                'coordinator' => $a->coordinator !== null ? [
                    'id' => $a->coordinator->id,
                    'name' => $a->coordinator->name,
                    'email' => $a->coordinator->email,
                    'cargo' => $a->coordinator->cargo,
                ] : null,
                'users_count' => (int) $a->users_count,
                'users' => $a->users->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'cargo' => $u->cargo,
                    'email' => $u->email,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        $coordinators = User::query()
            ->role('coordinador')
            ->orderBy('name')
            ->get(['id', 'name', 'cargo', 'email'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'cargo' => $u->cargo,
                'email' => $u->email,
            ])
            ->values()
            ->all();

        $users = User::query()
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'coordinador'))
            ->with('areas:id')
            ->orderBy('name')
            ->get(['id', 'name', 'cargo', 'email'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'cargo' => $u->cargo,
                'email' => $u->email,
                'area_ids' => $u->areas->pluck('id')->values()->all(),
            ])
            ->values()
            ->all();

        return Inertia::render('sistema/Areas', [
            'areas' => $areas,
            'users' => $users,
            'coordinators' => $coordinators,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['admin', 'pmo', 'coordinador']), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:areas,name'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'coordinator_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (($validated['coordinator_user_id'] ?? null) !== null) {
            $isCoordinator = User::query()
                ->role('coordinador')
                ->whereKey((int) $validated['coordinator_user_id'])
                ->exists();
            if (! $isCoordinator) {
                return back()->withErrors([
                    'coordinator_user_id' => 'La persona seleccionada debe tener rol coordinador.',
                ]);
            }
        }

        $area = Area::query()->create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'coordinator_user_id' => $validated['coordinator_user_id'] ?? null,
        ]);

        if (($validated['coordinator_user_id'] ?? null) !== null) {
            $area->users()->syncWithoutDetaching([(int) $validated['coordinator_user_id']]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Área creada.']);

        return back();
    }

    public function update(Request $request, Area $area): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['admin', 'pmo', 'coordinador']), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('areas', 'name')->ignore($area->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'coordinator_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (($validated['coordinator_user_id'] ?? null) !== null) {
            $isCoordinator = User::query()
                ->role('coordinador')
                ->whereKey((int) $validated['coordinator_user_id'])
                ->exists();
            if (! $isCoordinator) {
                return back()->withErrors([
                    'coordinator_user_id' => 'La persona seleccionada debe tener rol coordinador.',
                ]);
            }
        }

        $area->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) $validated['is_active'],
            'coordinator_user_id' => $validated['coordinator_user_id'] ?? null,
        ]);

        if (($validated['coordinator_user_id'] ?? null) !== null) {
            $area->users()->syncWithoutDetaching([(int) $validated['coordinator_user_id']]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Área actualizada.']);

        return back();
    }

    public function destroy(Request $request, Area $area): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['admin', 'pmo', 'coordinador']), 403);

        $area->users()->detach();
        $area->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Área eliminada.']);

        return back();
    }

    public function syncUsers(Request $request, Area $area): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['admin', 'pmo', 'coordinador']), 403);

        $validated = $request->validate([
            'user_ids' => ['array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $userIds = collect($validated['user_ids'] ?? [])->map(fn ($id) => (int) $id);
        if ($area->coordinator_user_id !== null) {
            $userIds->push((int) $area->coordinator_user_id);
        }

        $area->users()->sync($userIds->unique()->values()->all());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Personas del área actualizadas.']);

        return back();
    }
}

