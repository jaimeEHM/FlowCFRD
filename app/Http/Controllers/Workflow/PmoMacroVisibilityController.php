<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\PmoMacroVisibilityRule;
use App\Support\PmoMacroHubCatalog;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PmoMacroVisibilityController extends Controller
{
    /**
     * Overrides de visibilidad por ítem. Para restaurar el valor por defecto del catálogo,
     * envía `null` o `[]` para ese ítem.
     */
    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['admin', 'pmo']), 403);

        $allowedKeys = PmoMacroHubCatalog::allItemKeys();
        $roleNames = RoleSeeder::ROLE_NAMES;

        $validated = $request->validate([
            'overrides' => 'required|array',
            'overrides.*' => 'nullable|array',
            'overrides.*.*' => ['string', Rule::in($roleNames)],
        ]);

        $incomingKeys = array_keys($validated['overrides']);
        $unknown = array_diff($incomingKeys, $allowedKeys);
        if ($unknown !== []) {
            throw ValidationException::withMessages([
                'overrides' => 'Claves no reconocidas: '.implode(', ', $unknown),
            ]);
        }

        $missing = array_diff($allowedKeys, $incomingKeys);
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'overrides' => 'Faltan claves en overrides: '.implode(', ', $missing),
            ]);
        }

        foreach ($allowedKeys as $key) {
            if (! array_key_exists($key, $validated['overrides'])) {
                continue;
            }
            $roles = $validated['overrides'][$key];
            if ($roles === null) {
                PmoMacroVisibilityRule::query()->where('item_key', $key)->delete();

                continue;
            }
            $roles = array_values(array_unique($roles));
            if ($roles === []) {
                PmoMacroVisibilityRule::query()->where('item_key', $key)->delete();

                continue;
            }
            PmoMacroVisibilityRule::query()->updateOrCreate(
                ['item_key' => $key],
                ['allowed_roles' => $roles],
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Visibilidad del tablero macro actualizada.']);

        return back();
    }
}
