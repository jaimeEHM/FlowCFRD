<?php

use App\Models\Project;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

test('PMO puede actualizar nombre y estado de proyecto vía PATCH web', function (): void {
    $user = User::factory()->create();
    $user->assignRole('pmo');

    $project = Project::factory()->create([
        'name' => 'Proyecto seed',
        'status' => Project::STATUS_BORRADOR,
    ]);

    $this->actingAs($user)
        ->patch(route('pmo.proyectos.update', $project), [
            'name' => 'Proyecto editado tablero',
            'status' => Project::STATUS_ACTIVO,
        ])
        ->assertRedirect();

    $project->refresh();

    expect($project->name)->toBe('Proyecto editado tablero')
        ->and($project->status)->toBe(Project::STATUS_ACTIVO);
});

test('PMO puede editar tablero macro: código, fechas y jefe de proyecto', function (): void {
    $pmo = User::factory()->create();
    $pmo->assignRole('pmo');

    $jefe = User::factory()->create();
    $jefe->assignRole('jefe_proyecto');

    $project = Project::factory()->create([
        'code' => null,
        'starts_at' => null,
        'ends_at' => null,
        'carta_inicio_at' => null,
        'jefe_proyecto_id' => null,
    ]);

    $response = $this->actingAs($pmo)
        ->patch(route('pmo.proyectos.update', $project), [
            'code' => 'CFRD-01',
            'starts_at' => '2026-01-10',
            'ends_at' => '2026-06-30',
            'carta_inicio_at' => '2026-01-05',
            'jefe_proyecto_id' => $jefe->id,
        ]);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();

    $project->refresh();

    expect($project->code)->toBe('CFRD-01')
        ->and($project->jefe_proyecto_id)->toBe($jefe->id)
        ->and($project->starts_at?->format('Y-m-d'))->toBe('2026-01-10')
        ->and($project->ends_at?->format('Y-m-d'))->toBe('2026-06-30')
        ->and($project->carta_inicio_at?->format('Y-m-d'))->toBe('2026-01-05');
});

test('PMO no puede asignar como jefe a un usuario sin rol jefe_proyecto', function (): void {
    $pmo = User::factory()->create();
    $pmo->assignRole('pmo');

    $otro = User::factory()->create();
    $otro->assignRole('colaborador');

    $project = Project::factory()->create();

    $this->actingAs($pmo)
        ->patch(route('pmo.proyectos.update', $project), [
            'jefe_proyecto_id' => $otro->id,
        ])
        ->assertSessionHasErrors('jefe_proyecto_id');
});

test('PMO recibe error si fin es anterior al inicio', function (): void {
    $pmo = User::factory()->create();
    $pmo->assignRole('pmo');

    $project = Project::factory()->create([
        'starts_at' => '2026-03-01',
        'ends_at' => '2026-12-01',
    ]);

    $this->actingAs($pmo)
        ->patch(route('pmo.proyectos.update', $project), [
            'ends_at' => '2026-01-01',
        ])
        ->assertSessionHasErrors('ends_at');
});
