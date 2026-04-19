<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;

final class ProjectCompletionMonitor
{
    public function __construct(
        private WorkflowNotificationService $notifications,
    ) {}

    public function afterTaskChanged(Task $task): void
    {
        $projectId = $task->project_id;
        if ($projectId === null) {
            return;
        }

        $project = Project::query()->find($projectId);
        if ($project === null) {
            return;
        }

        $total = Task::query()->where('project_id', $projectId)->count();
        $open = Task::query()
            ->where('project_id', $projectId)
            ->where('status', '!=', Task::STATUS_HECHA)
            ->count();

        if ($total === 0) {
            $this->clearNotifiedIfNeeded($project);

            return;
        }

        if ($open > 0) {
            $this->clearNotifiedIfNeeded($project);

            return;
        }

        if ($project->completion_notified_at !== null) {
            return;
        }

        $this->notifications->notifyPmoAllTasksCompleted($project);
        $project->forceFill(['completion_notified_at' => now()])->save();
    }

    private function clearNotifiedIfNeeded(Project $project): void
    {
        if ($project->completion_notified_at === null) {
            return;
        }

        $project->forceFill(['completion_notified_at' => null])->save();
    }
}
