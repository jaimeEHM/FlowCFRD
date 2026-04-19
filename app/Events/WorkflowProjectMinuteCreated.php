<?php

namespace App\Events;

use App\Models\ProjectMinute;
use App\Support\WorkflowActivityRecipients;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class WorkflowProjectMinuteCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ProjectMinute $minute,
    ) {
        $this->minute->loadMissing([
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
        return 'workflow.project_minute.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'minute' => [
                'id' => $this->minute->id,
                'project_id' => $this->minute->project_id,
                'title' => $this->minute->title,
                'held_at' => $this->minute->held_at?->toIso8601String(),
                'created_by_id' => $this->minute->created_by_id,
            ],
            'project' => $this->minute->project ? [
                'id' => $this->minute->project->id,
                'name' => $this->minute->project->name,
            ] : null,
        ];
    }

    /**
     * @return Collection<int, int>
     */
    private function recipientUserIds(): Collection
    {
        return WorkflowActivityRecipients::forProjectMinute($this->minute);
    }
}
