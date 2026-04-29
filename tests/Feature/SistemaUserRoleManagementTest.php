<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('pmo puede ver gestion de usuarios y roles', function () {
    Role::findOrCreate('pmo', 'web');
    $pmo = User::factory()->create();
    $pmo->assignRole('pmo');

    $this->actingAs($pmo)
        ->get(route('sistema.usuarios-roles'), [
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
        ])
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', route('sistema.usuarios-roles'));
});

test('colaborador no puede ver gestion de usuarios y roles', function () {
    Role::findOrCreate('colaborador', 'web');
    $colab = User::factory()->create();
    $colab->assignRole('colaborador');

    $this->actingAs($colab)
        ->get(route('sistema.usuarios-roles'))
        ->assertForbidden();
});

test('admin o pmo pueden actualizar roles de un usuario', function () {
    Role::findOrCreate('pmo', 'web');
    Role::findOrCreate('coordinador', 'web');
    Role::findOrCreate('colaborador', 'web');

    $actor = User::factory()->create();
    $actor->assignRole('pmo');

    $target = User::factory()->create();
    $target->assignRole('colaborador');

    $this->actingAs($actor)
        ->patch(route('sistema.usuarios-roles.update', $target), [
            'roles' => ['coordinador'],
        ])
        ->assertRedirect();

    $target->refresh();
    expect($target->hasRole('coordinador'))->toBeTrue()
        ->and($target->hasRole('colaborador'))->toBeFalse();
});

