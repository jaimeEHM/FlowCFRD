<?php

use App\Models\User;
use Database\Seeders\CfrdDevUserSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\WorkflowDomainSeeder;

test('los seeders CFRD asignan roles esperados', function () {
    $this->seed(RoleSeeder::class);
    $this->seed(CfrdDevUserSeeder::class);

    expect(User::query()->where('email', 'dbordon@udec.cl')->first())
        ->not->toBeNull()
        ->hasRole('admin')->toBeTrue();

    expect(User::query()->where('email', 'marcospalma@udec.cl')->first())
        ->hasRole('pmo')->toBeTrue();
});

test('usuario admin autenticado accede a módulos workflow', function () {
    $this->seed(RoleSeeder::class);
    $this->seed(CfrdDevUserSeeder::class);
    $this->seed(WorkflowDomainSeeder::class);

    $admin = User::query()->where('email', 'dbordon@udec.cl')->firstOrFail();
    $this->actingAs($admin);

    $this->get(route('pmo.proyectos'))->assertOk();
});
