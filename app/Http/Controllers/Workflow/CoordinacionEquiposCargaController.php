<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CoordinacionEquiposCargaController extends Controller
{
    public function __invoke(): Response
    {
        $users = User::query()
            ->withCount(['assignedTasks as tareas_abiertas' => fn ($q) => $q->where('status', '!=', Task::STATUS_HECHA)])
            ->with('roles')
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
                    'tareas_abiertas' => $u->tareas_abiertas,
                ];
            });

        return Inertia::render('coordinacion/EquiposCarga', [
            'users' => $users,
        ]);
    }
}
