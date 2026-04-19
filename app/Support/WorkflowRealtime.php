<?php

namespace App\Support;

use App\Events\WorkflowProjectChanged;
use App\Events\WorkflowProjectMinuteCreated;
use App\Events\WorkflowSkillValidationChanged;
use App\Events\WorkflowTaskChanged;
use App\Models\Project;
use App\Models\ProjectMinute;
use App\Models\SkillValidation;
use App\Models\Task;
use App\Services\ProjectCompletionMonitor;
use App\Services\WorkflowNotificationService;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/**
 * Notificaciones en BD + emisión WebSocket (Reverb) para el dominio Workflow.
 * Si el servidor de broadcast no responde (p. ej. Reverb no está en marcha), se registra un warning y la petición HTTP sigue OK.
 * Se usa `Broadcast::queue()` (no `broadcast()`): el helper `broadcast()` difiere el envío al `__destruct` de `PendingBroadcast` y el fallo escapa al try/catch.
 */
final class WorkflowRealtime
{
    /**
     * @param  'created'|'updated'  $action
     */
    public static function task(Task $task, string $action = 'updated'): void
    {
        app(WorkflowNotificationService::class)->notifyTaskChanged($task, $action);

        self::broadcastUnlessDisabled(new WorkflowTaskChanged($task, $action));

        app(ProjectCompletionMonitor::class)->afterTaskChanged($task);
    }

    /**
     * @param  'created'|'updated'  $action
     */
    public static function project(Project $project, string $action = 'updated'): void
    {
        app(WorkflowNotificationService::class)->notifyProjectChanged($project, $action);

        self::broadcastUnlessDisabled(new WorkflowProjectChanged($project, $action));
    }

    public static function skillValidation(SkillValidation $skillValidation, string $action = 'updated'): void
    {
        app(WorkflowNotificationService::class)->notifySkillValidationChanged($skillValidation, $action);

        self::broadcastUnlessDisabled(new WorkflowSkillValidationChanged($skillValidation, $action));
    }

    public static function projectMinuteCreated(ProjectMinute $minute): void
    {
        app(WorkflowNotificationService::class)->notifyProjectMinuteCreated($minute);

        self::broadcastUnlessDisabled(new WorkflowProjectMinuteCreated($minute));
    }

    private static function broadcastUnlessDisabled(ShouldBroadcast $event): void
    {
        if (config('broadcasting.default') === 'null') {
            return;
        }

        try {
            Broadcast::queue($event);
        } catch (\Throwable $e) {
            Log::warning('Workflow broadcast no entregado (¿Reverb detenido o host incorrecto?): '.$e->getMessage());
        }
    }
}
