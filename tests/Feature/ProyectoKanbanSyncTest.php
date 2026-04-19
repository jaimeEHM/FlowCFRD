<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

test('sync kanban persiste estado, segmento y orden', function (): void {
    $jefe = User::factory()->create();
    $jefe->assignRole('jefe_proyecto');

    $project = Project::factory()->create([
        'jefe_proyecto_id' => $jefe->id,
    ]);

    $g1 = TaskGroup::ensureGeneral($project);
    $g2 = TaskGroup::query()->create([
        'project_id' => $project->id,
        'name' => 'Otro',
        'color' => '#111',
        'position' => 1,
    ]);

    $t1 = Task::factory()->create([
        'project_id' => $project->id,
        'task_group_id' => $g1->id,
        'status' => Task::STATUS_BACKLOG,
        'kanban_order' => 0,
    ]);
    $t2 = Task::factory()->create([
        'project_id' => $project->id,
        'task_group_id' => $g1->id,
        'status' => Task::STATUS_BACKLOG,
        'kanban_order' => 1,
    ]);

    $this->actingAs($jefe)
        ->patch(route('proyecto.kanban.sync'), [
            'project_id' => $project->id,
            'orders' => [
                [
                    'task_id' => $t1->id,
                    'status' => Task::STATUS_PENDIENTE,
                    'task_group_id' => $g2->id,
                    'kanban_order' => 0,
                ],
                [
                    'task_id' => $t2->id,
                    'status' => Task::STATUS_EN_CURSO,
                    'task_group_id' => $g2->id,
                    'kanban_order' => 0,
                ],
            ],
        ])
        ->assertRedirect();

    $t1->refresh();
    $t2->refresh();

    expect($t1->status)->toBe(Task::STATUS_PENDIENTE)
        ->and((int) $t1->task_group_id)->toBe($g2->id)
        ->and($t1->kanban_order)->toBe(0)
        ->and($t2->status)->toBe(Task::STATUS_EN_CURSO)
        ->and((int) $t2->task_group_id)->toBe($g2->id);
});

test('PATCH tarea actualiza descripción y colaboradores', function (): void {
    $jefe = User::factory()->create();
    $jefe->assignRole('jefe_proyecto');
    $c1 = User::factory()->create();
    $c1->assignRole('colaborador');

    $project = Project::factory()->create([
        'jefe_proyecto_id' => $jefe->id,
    ]);
    $group = TaskGroup::ensureGeneral($project);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'task_group_id' => $group->id,
        'assignee_id' => $jefe->id,
    ]);

    $this->actingAs($jefe)
        ->patch(route('proyecto.tareas.update', $task), [
            'description' => 'Texto largo',
            'collaborator_ids' => [$c1->id],
        ])
        ->assertRedirect();

    $task->refresh();
    $task->load('collaborators');
    expect($task->description)->toBe('Texto largo');
    expect($task->collaborators->pluck('id')->all())->toContain($c1->id);
});
