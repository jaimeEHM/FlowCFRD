<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Support\WorkflowTransversalSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SistemaConfiguracionTransversalController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user()?->hasRole(['admin', 'pmo']), 403);

        return Inertia::render('sistema/ConfiguracionTransversal', [
            'workloadThresholds' => WorkflowTransversalSettings::workload(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['admin', 'pmo']), 403);

        $validated = $request->validate([
            'tasks_per_day' => 'required|integer|min:1|max:20',
            'alert_days' => 'required|integer|min:1|max:60',
            'danger_days' => 'required|integer|min:1|max:90',
            'overload_days' => 'required|integer|min:1|max:120',
        ]);

        $alert = (int) $validated['alert_days'];
        $danger = max($alert + 1, (int) $validated['danger_days']);
        $overload = max($danger + 1, (int) $validated['overload_days']);

        WorkflowTransversalSettings::saveWorkload([
            'tasks_per_day' => (int) $validated['tasks_per_day'],
            'alert_days' => $alert,
            'danger_days' => $danger,
            'overload_days' => $overload,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Configuración transversal actualizada.']);

        return back();
    }
}

