<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\WorkflowRealtime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    public function indexByProject(Request $request, Project $project): AnonymousResourceCollection|JsonResponse
    {
        if (! Project::userMayAccessIncludingColaborador($request->user(), $project)) {
            abort(403);
        }

        $query = Task::query()
            ->where('project_id', $project->id)
            ->with([
                'assignee:id,name,email',
                'collaborators:id,name,email',
            ]);

        if ($this->isColaboradorSinElevacion($request->user())) {
            $query->where('assignee_id', $request->user()->id);
        }

        $tasks = $query->orderBy('id')->get();

        return TaskResource::collection($tasks);
    }

    public function myIndex(Request $request): AnonymousResourceCollection
    {
        $tasks = Task::query()
            ->where('assignee_id', $request->user()->id)
            ->with([
                'project:id,name,code,status',
                'collaborators:id,name,email',
            ])
            ->orderBy('status')
            ->orderBy('due_date')
            ->get();

        return TaskResource::collection($tasks);
    }

    public function update(Request $request, Task $task, AuditLogger $auditLogger): TaskResource|JsonResponse
    {
        $user = $request->user();

        if (! $this->userMayUpdateTask($user, $task)) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|string|in:'.implode(',', Task::STATUSES),
        ]);

        $task->update(['status' => $request->string('status')->toString()]);

        $auditLogger->log('task.status_changed', $task, ['status' => $task->status]);

        $task->refresh();
        WorkflowRealtime::task($task, 'updated');

        $task->load([
            'assignee:id,name,email',
            'project:id,name,code,status',
            'collaborators:id,name,email',
        ]);

        return new TaskResource($task);
    }

    private function userMayUpdateTask(User $user, Task $task): bool
    {
        if (Project::userMayAccess($user, $task->project)) {
            return true;
        }

        return $user->hasRole('colaborador')
            && (int) $task->assignee_id === (int) $user->id;
    }

    private function isColaboradorSinElevacion(User $user): bool
    {
        if ($user->hasAnyRole(['admin', 'pmo', 'coordinador', 'jefe_proyecto'])) {
            return false;
        }

        return $user->hasRole('colaborador');
    }
}
