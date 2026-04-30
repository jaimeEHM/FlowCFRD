<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CoordinacionEquiposCargaController extends Controller
{
    public function __invoke(): Response
    {
        $users = User::query()
            ->withCount(['assignedTasks as tareas_abiertas' => fn ($q) => $q->where('status', '!=', Task::STATUS_HECHA)])
            ->with(['roles', 'areas:id,name', 'coordinatedAreas:id,name,coordinator_user_id'])
            ->orderBy('name')
            ->get()
            ->map(function (User $u) {
                $nombre = Str::contains($u->name, ' — ')
                    ? (string) strstr($u->name, ' — ', true)
                    : $u->name;

                return [
                    'id' => $u->id,
                    'nombre' => $nombre,
                    'name' => $u->name,
                    'cargo' => $u->cargo,
                    'email' => $u->email,
                    'avatar' => $u->avatar,
                    'roles' => $u->getRoleNames()->values()->all(),
                    'areas' => $u->areas
                        ->pluck('name')
                        ->merge($u->coordinatedAreas->pluck('name'))
                        ->unique()
                        ->sort()
                        ->values()
                        ->all(),
                    'area_ids' => $u->areas->pluck('id')->values()->all(),
                    'tareas_abiertas' => $u->tareas_abiertas,
                ];
            });

        $areaOptions = Area::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Area $area) => [
                'id' => $area->id,
                'name' => $area->name,
            ])
            ->values()
            ->all();

        return Inertia::render('coordinacion/EquiposCarga', [
            'users' => $users,
            'area_options' => $areaOptions,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'cargo' => ['nullable', 'string', 'max:120'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
        ]);

        $user->update([
            'cargo' => $validated['cargo'] ?? null,
        ]);

        if (($validated['area_id'] ?? null) !== null) {
            $user->areas()->sync([(int) $validated['area_id']]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Persona actualizada correctamente.']);

        return back();
    }

    public function suspendAreas(User $user): RedirectResponse
    {
        $user->areas()->detach();

        Area::query()
            ->where('coordinator_user_id', $user->id)
            ->update(['coordinator_user_id' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Asignaciones de área suspendidas para la persona.']);

        return back();
    }

    public function reassignArea(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'area_id' => ['required', 'integer', 'exists:areas,id'],
        ]);

        $user->areas()->sync([(int) $validated['area_id']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Área reasignada correctamente.']);

        return back();
    }
}
