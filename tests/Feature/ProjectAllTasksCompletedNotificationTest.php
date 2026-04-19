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

test('al completar la última tarea se notifica al PMO que todas las tareas están hechas', function (): void {
    Notification::fake();

    $pmo = User::factory()->create();
    $pmo->assignRole('pmo');

    $jefe = User::factory()->create();
    $jefe->assignRole('jefe_proyecto');

    $project = Project::factory()->create([
        'jefe_proyecto_id' => $jefe->id,
    ]);

    $t1 = Task::factory()->create([
        'project_id' => $project->id,
        'status' => Task::STATUS_PENDIENTE,
    ]);
    $t2 = Task::factory()->create([
        'project_id' => $project->id,
        'status' => Task::STATUS_PENDIENTE,
    ]);

    Sanctum::actingAs($jefe);

    $this->patchJson("/api/v1/tasks/{$t1->id}", [
        'status' => Task::STATUS_HECHA,
    ])->assertOk();

    Notification::assertNotSentTo($pmo, WorkflowActivityNotification::class, function (WorkflowActivityNotification $n): bool {
        return $n->kind === 'project.all_tasks_completed';
    });

    $this->patchJson("/api/v1/tasks/{$t2->id}", [
        'status' => Task::STATUS_HECHA,
    ])->assertOk();

    Notification::assertSentTo($pmo, WorkflowActivityNotification::class, function (WorkflowActivityNotification $n): bool {
        return $n->kind === 'project.all_tasks_completed';
    });

    $project->refresh();
    expect($project->completion_notified_at)->not->toBeNull();
});

test('si se reabre una tarea se limpia el aviso y al volver a completar todo se notifica de nuevo', function (): void {
    Notification::fake();

    $pmo = User::factory()->create();
    $pmo->assignRole('pmo');

    $jefe = User::factory()->create();
    $jefe->assignRole('jefe_proyecto');

    $project = Project::factory()->create([
        'jefe_proyecto_id' => $jefe->id,
    ]);

    $t1 = Task::factory()->create([
        'project_id' => $project->id,
        'status' => Task::STATUS_PENDIENTE,
    ]);
    $t2 = Task::factory()->create([
        'project_id' => $project->id,
        'status' => Task::STATUS_PENDIENTE,
    ]);

    Sanctum::actingAs($jefe);

    $this->patchJson("/api/v1/tasks/{$t1->id}", [
        'status' => Task::STATUS_HECHA,
    ])->assertOk();
    $this->patchJson("/api/v1/tasks/{$t2->id}", [
        'status' => Task::STATUS_HECHA,
    ])->assertOk();

    $project->refresh();
    expect($project->completion_notified_at)->not->toBeNull();

    $this->patchJson("/api/v1/tasks/{$t1->id}", [
        'status' => Task::STATUS_PENDIENTE,
    ])->assertOk();

    $project->refresh();
    expect($project->completion_notified_at)->toBeNull();

    Notification::fake();

    $this->patchJson("/api/v1/tasks/{$t1->id}", [
        'status' => Task::STATUS_HECHA,
    ])->assertOk();

    Notification::assertSentTo($pmo, WorkflowActivityNotification::class, function (WorkflowActivityNotification $n): bool {
        return $n->kind === 'project.all_tasks_completed';
    });
});
