<?php

use App\Models\Project;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('colaborador no puede acceder a cartera PMO pero sí al tablero macro', function () {
    $user = User::factory()->create();
    $user->assignRole('colaborador');
    $this->actingAs($user);

    $this->get(route('pmo.proyectos'))->assertForbidden();
    $this->get(route('pmo.tablero-macro'))->assertOk();
    $this->get(route('pmo.indicadores'))->assertRedirect(route('pmo.tablero-macro', ['segment' => 'kpi']));
});

test('colaborador no puede acceder a coordinación ni proyecto kanban', function () {
    $user = User::factory()->create();
    $user->assignRole('colaborador');
    $this->actingAs($user);

    $this->get(route('coordinacion.backlog-tareas'))->assertForbidden();
    $this->get(route('proyecto.kanban'))->assertForbidden();
});

test('jefe de proyecto accede al tablero macro pero no a cartera PMO ni colaborador sin rol', function () {
    $user = User::factory()->create();
    $user->assignRole('jefe_proyecto');
    $this->actingAs($user);

    $this->get(route('pmo.proyectos'))->assertForbidden();
    $this->get(route('pmo.tablero-macro'))->assertOk();
    $this->get(route('colaborador.mis-tareas'))->assertForbidden();
});

test('pmo accede a cartera y a coordinación', function () {
    $user = User::factory()->create();
    $user->assignRole('pmo');
    $this->actingAs($user);

    $this->get(route('pmo.proyectos'))->assertOk();
    $this->get(route('coordinacion.equipos-carga'))->assertOk();
});

test('jefe de proyecto solo ve proyectos donde es jefe en queryForUser', function () {
    $jefeA = User::factory()->create();
    $jefeA->assignRole('jefe_proyecto');
    $jefeB = User::factory()->create();
    $jefeB->assignRole('jefe_proyecto');

    $pA = Project::factory()->create([
        'jefe_proyecto_id' => $jefeA->id,
        'created_by_id' => $jefeA->id,
    ]);
    $pB = Project::factory()->create([
        'jefe_proyecto_id' => $jefeB->id,
        'created_by_id' => $jefeB->id,
    ]);

    $idsA = Project::queryForUser($jefeA)->pluck('id')->all();

    expect($idsA)->toContain($pA->id)->not->toContain($pB->id);
});
