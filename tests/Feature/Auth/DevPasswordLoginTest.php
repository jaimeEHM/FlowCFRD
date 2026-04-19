<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('solo el correo de desarrollo puede iniciar sesión por contraseña', function () {
    Config::set('workflow.dev_password_login_email', 'admin@cfrd.cl');

    User::factory()->create([
        'email' => 'admin@cfrd.cl',
        'password' => Hash::make('cf753rd/'),
    ]);

    User::factory()->create([
        'email' => 'pmo@cfrd.cl',
        'password' => Hash::make('cf753rd/'),
    ]);

    $this->post(route('login.store'), [
        'email' => 'pmo@cfrd.cl',
        'password' => 'cf753rd/',
    ]);

    $this->assertGuest();
});

test('admin de desarrollo puede iniciar sesión por contraseña', function () {
    Config::set('workflow.dev_password_login_email', 'admin@cfrd.cl');

    User::factory()->create([
        'email' => 'admin@cfrd.cl',
        'password' => Hash::make('cf753rd/'),
    ]);

    $response = $this->post(route('login.store'), [
        'email' => 'admin@cfrd.cl',
        'password' => 'cf753rd/',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
