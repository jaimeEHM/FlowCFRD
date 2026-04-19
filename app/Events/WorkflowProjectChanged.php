<?php

namespace App\Events;

use App\Models\Project;
use App\Support\WorkflowActivityRecipients;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class WorkflowProjectChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  'created'|'updated'  $action
     */
    public function __construct(
        public Project $project,
        public string $action = 'updated',
    ) {}

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
        return 'workflow.project.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'project' => [
                'id' => $this->project->id,
                'name' => $this->project->name,
                'status' => $this->project->status,
            ],
        ];
    }

    /**
     * @return Collection<int, int>
     */
    private function recipientUserIds(): Collection
    {
        return WorkflowActivityRecipients::forProject($this->project);
    }
}
