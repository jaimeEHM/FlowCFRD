<?php

namespace App\Events;

use App\Models\SkillValidation;
use App\Support\WorkflowActivityRecipients;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class WorkflowSkillValidationChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  'updated'  $action
     */
    public function __construct(
        public SkillValidation $skillValidation,
        public string $action = 'updated',
    ) {
        $this->skillValidation->loadMissing([
            'skill:id,name',
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
        return 'workflow.skill_validation.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'skill_validation' => [
                'id' => $this->skillValidation->id,
                'skill_id' => $this->skillValidation->skill_id,
                'status' => $this->skillValidation->status,
                'subject_user_id' => $this->skillValidation->subject_user_id,
                'validator_user_id' => $this->skillValidation->validator_user_id,
            ],
            'skill' => $this->skillValidation->skill ? [
                'name' => $this->skillValidation->skill->name,
            ] : null,
        ];
    }

    /**
     * @return Collection<int, int>
     */
    private function recipientUserIds(): Collection
    {
        return WorkflowActivityRecipients::forSkillValidation($this->skillValidation);
    }
}
