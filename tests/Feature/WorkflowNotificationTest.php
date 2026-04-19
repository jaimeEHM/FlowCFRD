<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\WorkflowActivityNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

test('PATCH tarea envía notificación al asignatario cuando actúa el jefe', function (): void {
    Notification::fake();

    $jefe = User::factory()->create();
    $jefe->assignRole('jefe_proyecto');

    $colab = User::factory()->create();
    $colab->assignRole('colaborador');

    $project = Project::factory()->create([
        'jefe_proyecto_id' => $jefe->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assignee_id' => $colab->id,
        'status' => Task::STATUS_PENDIENTE,
    ]);

    Sanctum::actingAs($jefe);

    $this->patchJson("/api/v1/tasks/{$task->id}", [
        'status' => Task::STATUS_EN_CURSO,
    ])->assertOk();

    Notification::assertSentTo($colab, WorkflowActivityNotification::class, function (WorkflowActivityNotification $n): bool {
        return $n->kind === 'task.updated';
    });
});

test('el actor no recibe su propia notificación de tarea', function (): void {
    Notification::fake();

    $jefe = User::factory()->create();
    $jefe->assignRole('jefe_proyecto');

    $project = Project::factory()->create([
        'jefe_proyecto_id' => $jefe->id,
        'created_by_id' => $jefe->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assignee_id' => $jefe->id,
        'status' => Task::STATUS_PENDIENTE,
    ]);

    Sanctum::actingAs($jefe);

    $this->patchJson("/api/v1/tasks/{$task->id}", [
        'status' => Task::STATUS_EN_CURSO,
    ])->assertOk();

    Notification::assertNothingSent();
});

test('colaborador actualiza su tarea y el jefe recibe notificación', function (): void {
    Notification::fake();

    $jefe = User::factory()->create();
    $jefe->assignRole('jefe_proyecto');

    $colab = User::factory()->create();
    $colab->assignRole('colaborador');

    $project = Project::factory()->create([
        'jefe_proyecto_id' => $jefe->id,
        'created_by_id' => $jefe->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assignee_id' => $colab->id,
        'status' => Task::STATUS_PENDIENTE,
    ]);

    Sanctum::actingAs($colab);

    $this->patchJson("/api/v1/tasks/{$task->id}", [
        'status' => Task::STATUS_EN_CURSO,
    ])->assertOk();

    Notification::assertSentTo($jefe, WorkflowActivityNotification::class);
});
