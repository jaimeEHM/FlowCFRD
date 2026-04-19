<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'is_urgent' => $this->is_urgent,
            'backlog_order' => $this->backlog_order,
            'assignee_id' => $this->assignee_id,
            'due_date' => $this->due_date?->toIso8601String(),
            'created_by_id' => $this->created_by_id,
            'validation_status' => $this->validation_status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'assignee' => $this->whenLoaded('assignee', fn () => [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
                'email' => $this->assignee->email,
            ]),
            'collaborators' => $this->whenLoaded('collaborators', fn () => $this->collaborators
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                ])
                ->values()
                ->all()),
            'project' => $this->whenLoaded('project', function () {
                $code = $this->project->code;
                $name = $this->project->name;
                $displayLabel = ($code !== null && $code !== '')
                    ? '['.$code.'] '.$name
                    : $name;

                return [
                    'id' => $this->project->id,
                    'name' => $name,
                    'code' => $this->project->code,
                    'status' => $this->project->status,
                    'display_label' => $displayLabel,
                ];
            }),
        ];
    }
}
