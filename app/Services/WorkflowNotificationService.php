<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectMinute;
use App\Models\SkillValidation;
use App\Models\Task;
use App\Models\User;
use App\Notifications\WorkflowActivityNotification;
use App\Support\WorkflowActivityRecipients;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

final class WorkflowNotificationService
{
    /**
     * @param  'created'|'updated'  $action
     */
    public function notifyTaskChanged(Task $task, string $action): void
    {
        $task->loadMissing(['project:id,name']);
        $ids = WorkflowActivityRecipients::forTask($task);
        $title = $action === 'created' ? 'Nueva tarea' : 'Tarea actualizada';
        $projectName = $task->project?->name ?? 'Proyecto';
        $body = "{$task->title} · {$projectName}";

        $this->sendToUsers($ids, new WorkflowActivityNotification(
            "task.{$action}",
            $title,
            $body,
            [
                'task_id' => $task->id,
                'project_id' => $task->project_id,
                'action' => $action,
            ],
        ));
    }

    /**
     * @param  'created'|'updated'  $action
     */
    public function notifyProjectChanged(Project $project, string $action): void
    {
        $ids = WorkflowActivityRecipients::forProject($project);
        $title = $action === 'created' ? 'Nuevo proyecto' : 'Proyecto actualizado';
        $body = ($project->code ? "[{$project->code}] " : '').$project->name;

        $this->sendToUsers($ids, new WorkflowActivityNotification(
            "project.{$action}",
            $title,
            $body,
            [
                'project_id' => $project->id,
                'action' => $action,
            ],
        ));
    }

    /**
     * @param  'updated'  $action
     */
    public function notifySkillValidationChanged(SkillValidation $skillValidation, string $action): void
    {
        $skillValidation->loadMissing(['skill:id,name']);
        $ids = WorkflowActivityRecipients::forSkillValidation($skillValidation);
        $skillName = $skillValidation->skill?->name ?? 'Skill';
        $title = 'Validación de skill';
        $body = "{$skillName} · estado: {$skillValidation->status}";

        $this->sendToUsers($ids, new WorkflowActivityNotification(
            "skill_validation.{$action}",
            $title,
            $body,
            [
                'skill_validation_id' => $skillValidation->id,
                'skill_id' => $skillValidation->skill_id,
                'status' => $skillValidation->status,
            ],
        ));
    }

    public function notifyProjectMinuteCreated(ProjectMinute $minute): void
    {
        $minute->loadMissing(['project:id,name']);
        $ids = WorkflowActivityRecipients::forProjectMinute($minute);
        $projectName = $minute->project?->name ?? 'Proyecto';
        $title = 'Nueva minuta de proyecto';
        $body = "{$minute->title} · {$projectName}";

        $this->sendToUsers($ids, new WorkflowActivityNotification(
            'project_minute.created',
            $title,
            $body,
            [
                'minute_id' => $minute->id,
                'project_id' => $minute->project_id,
            ],
        ));
    }

    /**
     * Cuando todas las tareas del proyecto pasan a «hecha» (y hay al menos una tarea).
     * Los destinatarios PMO/admin sí reciben la alerta aunque hayan sido quien movió la última tarea.
     */
    public function notifyPmoAllTasksCompleted(Project $project): void
    {
        $ids = WorkflowActivityRecipients::forPmoAndAdmin();
        $title = 'Todas las tareas del proyecto están hechas';
        $body = ($project->code ? "[{$project->code}] " : '').$project->name;

        $this->sendToUsersAlways($ids, new WorkflowActivityNotification(
            'project.all_tasks_completed',
            $title,
            $body,
            [
                'project_id' => $project->id,
            ],
        ));
    }

    /**
     * @param  Collection<int, int>  $userIds
     */
    private function sendToUsers(Collection $userIds, WorkflowActivityNotification $notification): void
    {
        $actorId = Auth::id();

        foreach ($userIds as $userId) {
            if ($actorId !== null && (int) $userId === (int) $actorId) {
                continue;
            }
            $user = User::query()->find($userId);
            if ($user !== null) {
                $user->notify($notification);
            }
        }
    }

    /**
     * Igual que {@see sendToUsers}, pero no omite al usuario autenticado (alertas operativas al PMO).
     *
     * @param  Collection<int, int>  $userIds
     */
    private function sendToUsersAlways(Collection $userIds, WorkflowActivityNotification $notification): void
    {
        foreach ($userIds as $userId) {
            $user = User::query()->find($userId);
            if ($user !== null) {
                $user->notify($notification);
            }
        }
    }
}
