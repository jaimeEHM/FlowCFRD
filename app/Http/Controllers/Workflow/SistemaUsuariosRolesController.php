<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SistemaUsuariosRolesController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user()?->hasRole(['admin', 'pmo']), 403);

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'cargo', 'email', 'created_at'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'cargo' => $u->cargo,
                'email' => $u->email,
                'roles' => $u->getRoleNames()->values()->all(),
                'created_at' => optional($u->created_at)?->toDateString(),
            ])
            ->values()
            ->all();

        return Inertia::render('sistema/UsuariosRoles', [
            'users' => $users,
            'available_roles' => RoleSeeder::ROLE_NAMES,
        ]);
    }

    public function update(Request $request, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['admin', 'pmo']), 403);

        $validated = $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in(RoleSeeder::ROLE_NAMES)],
        ]);

        $roles = array_values(array_unique($validated['roles']));
        $before = $user->getRoleNames()->values()->all();
        $user->syncRoles($roles);

        $auditLogger->log('user.roles_updated', $user, [
            'before' => $before,
            'after' => $roles,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Roles actualizados correctamente.']);

        return back();
    }
}

