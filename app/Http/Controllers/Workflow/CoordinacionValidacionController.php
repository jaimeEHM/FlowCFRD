<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\SkillValidation;
use App\Models\Task;
use App\Services\AuditLogger;
use App\Support\WorkflowRealtime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CoordinacionValidacionController extends Controller
{
    public function index(): Response
    {
        $tasks = Task::query()
            ->where('is_urgent', true)
            ->where('validation_status', Task::VALIDATION_PENDIENTE)
            ->with(['project:id,name', 'assignee:id,name,avatar'])
            ->orderByDesc('updated_at')
            ->get();

        $skillValidations = SkillValidation::query()
            ->where('status', 'pendiente')
            ->with(['skill:id,name', 'subject:id,name,avatar', 'validator:id,name,avatar'])
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('coordinacion/ValidacionAvances', [
            'urgent_tasks' => $tasks,
            'skill_validations' => $skillValidations,
        ]);
    }

    public function updateTask(Request $request, Task $task, AuditLogger $auditLogger): RedirectResponse
    {
        $request->validate([
            'validation_status' => 'required|string|in:'.Task::VALIDATION_APROBADA.','.Task::VALIDATION_RECHAZADA,
        ]);

        if ($task->validation_status !== Task::VALIDATION_PENDIENTE) {
            abort(422, 'La tarea ya no está pendiente de validación.');
        }

        $task->update([
            'validation_status' => $request->string('validation_status')->toString(),
        ]);

        $auditLogger->log('task.validation_resolved', $task);

        $task->refresh();
        WorkflowRealtime::task($task, 'updated');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Validación de tarea registrada.']);

        return redirect()->route('coordinacion.validacion-avances');
    }

    public function updateSkillValidation(Request $request, SkillValidation $skillValidation, AuditLogger $auditLogger): RedirectResponse
    {
        $request->validate([
            'status' => 'required|string|in:aprobada,rechazada',
            'comment' => 'nullable|string|max:2000',
        ]);

        if ($skillValidation->status !== 'pendiente') {
            abort(422, 'Esta validación ya fue procesada.');
        }

        $skillValidation->update([
            'status' => $request->string('status')->toString(),
            'comment' => $request->input('comment'),
        ]);

        $auditLogger->log('skill_validation.resolved', $skillValidation);

        $skillValidation->refresh();
        WorkflowRealtime::skillValidation($skillValidation, 'updated');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Validación de skill actualizada.']);

        return redirect()->route('coordinacion.validacion-avances');
    }
}
