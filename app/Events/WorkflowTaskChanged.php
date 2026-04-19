<?php

namespace App\Events;

use App\Models\Task;
use App\Support\WorkflowActivityRecipients;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class WorkflowTaskChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  'created'|'updated'  $action
     */
    public function __construct(
        public Task $task,
        public string $action = 'updated',
    ) {
        $this->task->loadMissing([
            'project:id,name,jefe_proyecto_id,created_by_id',
        ]);
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return $this->recipientUserIds()
            ->map(fn (int $id) => new PrivateChannel('App.Models.User.'.$id))
            ->values()
            ->all();
    }

    public function broadcastWhen(): bool
    {
        return $this->recipientUserIds()->isNotEmpty();
    }

    public function broadcastAs(): string
    {
        return 'workflow.task.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'task' => [
                'id' => $this->task->id,
                'project_id' => $this->task->project_id,
                'title' => $this->task->title,
                'status' => $this->task->status,
                'assignee_id' => $this->task->assignee_id,
            ],
            'project' => $this->task->project ? [
                'id' => $this->task->project->id,
                'name' => $this->task->project->name,
            ] : null,
        ];
    }

    /**
     * @return Collection<int, int>
     */
    private function recipientUserIds(): Collection
    {
        return WorkflowActivityRecipients::forTask($this->task);
    }
}
