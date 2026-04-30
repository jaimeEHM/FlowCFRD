<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\KanbanStatus;
use App\Models\Task;
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

        $defaults = [
            ['key' => Task::STATUS_PENDIENTE, 'label' => 'Pendiente', 'position' => 1],
            ['key' => Task::STATUS_EN_CURSO, 'label' => 'En curso', 'position' => 2],
            ['key' => Task::STATUS_REVISION, 'label' => 'Revisión', 'position' => 3],
        ];
        foreach ($defaults as $status) {
            KanbanStatus::query()->firstOrCreate(
                ['project_id' => null, 'key' => $status['key']],
                ['label' => $status['label'], 'position' => $status['position']],
            );
        }

        return Inertia::render('sistema/ConfiguracionTransversal', [
            'workloadThresholds' => WorkflowTransversalSettings::workload(),
            'kanbanDefaultStatuses' => KanbanStatus::query()
                ->whereNull('project_id')
                ->orderBy('position')
                ->orderBy('id')
                ->get(['id', 'key', 'label', 'position']),
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
            'parallel_alert_projects' => 'required|integer|min:1|max:10',
            'parallel_danger_projects' => 'required|integer|min:1|max:20',
        ]);

        $alert = (int) $validated['alert_days'];
        $danger = max($alert + 1, (int) $validated['danger_days']);
        $overload = max($danger + 1, (int) $validated['overload_days']);
        $parallelAlert = (int) $validated['parallel_alert_projects'];
        $parallelDanger = max($parallelAlert + 1, (int) $validated['parallel_danger_projects']);

        WorkflowTransversalSettings::saveWorkload([
            'tasks_per_day' => (int) $validated['tasks_per_day'],
            'alert_days' => $alert,
            'danger_days' => $danger,
            'overload_days' => $overload,
            'parallel_alert_projects' => $parallelAlert,
            'parallel_danger_projects' => $parallelDanger,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Configuración transversal actualizada.']);

        return back();
    }

    public function storeKanbanDefaultStatus(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['admin', 'pmo']), 403);

        $validated = $request->validate([
            'label' => 'required|string|max:100',
        ]);
        $key = KanbanStatus::makeKey($validated['label']);
        if ($key === '' || in_array($key, [Task::STATUS_BACKLOG, Task::STATUS_HECHA], true)) {
            return back()->withErrors(['label' => 'Nombre inválido para estado.']);
        }
        $max = (int) KanbanStatus::query()->whereNull('project_id')->max('position');
        KanbanStatus::query()->updateOrCreate(
            ['project_id' => null, 'key' => $key],
            ['label' => $validated['label'], 'position' => $max + 1],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Estado inicial Kanban agregado.']);

        return back();
    }

    public function updateKanbanDefaultStatus(Request $request, KanbanStatus $status): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['admin', 'pmo']), 403);
        abort_unless($status->project_id === null, 404);

        $validated = $request->validate([
            'label' => 'required|string|max:100',
        ]);
        $key = KanbanStatus::makeKey($validated['label']);
        if ($key === '' || in_array($key, [Task::STATUS_BACKLOG, Task::STATUS_HECHA], true)) {
            return back()->withErrors(['label' => 'Nombre inválido para estado.']);
        }

        if ($key !== $status->key) {
            $exists = KanbanStatus::query()
                ->whereNull('project_id')
                ->where('key', $key)
                ->exists();
            if ($exists) {
                return back()->withErrors(['label' => 'Ya existe un estado inicial con ese nombre.']);
            }
        }

        $status->update(['label' => $validated['label'], 'key' => $key]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Estado inicial Kanban actualizado.']);

        return back();
    }

    public function destroyKanbanDefaultStatus(Request $request, KanbanStatus $status): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['admin', 'pmo']), 403);
        abort_unless($status->project_id === null, 404);

        $status->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Estado inicial Kanban eliminado.']);

        return back();
    }

    public function reorderKanbanDefaultStatuses(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(['admin', 'pmo']), 403);

        $validated = $request->validate([
            'status_ids' => 'required|array|min:1',
            'status_ids.*' => 'required|integer|exists:kanban_statuses,id',
        ]);

        $ids = collect($validated['status_ids'])->map(fn ($id) => (int) $id)->values();
        $allowed = KanbanStatus::query()->whereNull('project_id')->whereIn('id', $ids->all())->pluck('id');
        if ($allowed->count() !== $ids->count()) {
            return back()->withErrors(['status_ids' => 'Solo se pueden reordenar estados transversales.']);
        }

        foreach ($ids as $index => $id) {
            KanbanStatus::query()->whereKey($id)->update(['position' => $index + 1]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Orden transversal de estados Kanban actualizado.']);

        return back();
    }
}

