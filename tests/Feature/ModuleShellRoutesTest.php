<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('las rutas shell de módulos responden para usuario con rol admin', function (string $routeName) {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user);

    $this->followingRedirects()->get(route($routeName))->assertOk();
})->with([
    'pmo.tablero-macro',
    'pmo.proyectos',
    'pmo.indicadores',
    'pmo.gantt',
    'pmo.kanban-macro',
    'pmo.carga-equipo',
    'coordinacion.equipos-carga',
    'coordinacion.backlog-tareas',
    'coordinacion.validacion-avances',
    'proyecto.kanban',
    'proyecto.minutas',
    'colaborador.mis-tareas',
    'colaborador.urgentes',
    'talento.matriz-skills',
    'talento.mapa-relaciones',
    'sistema.auditoria',
    'sistema.notificaciones',
    'sistema.lrs',
]);
