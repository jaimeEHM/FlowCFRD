<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as OAuthUser;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('google redirect sin client id redirige a login con mensaje', function () {
    Config::set('services.google.client_id', null);

    $response = $this->get(route('google.redirect'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status');
});

test('google callback sin client id redirige a login', function () {
    Config::set('services.google.client_id', null);

    $response = $this->get(route('google.callback'));

    $response->assertRedirect(route('login'));
});

test('google callback rechaza email fuera del dominio cfrd', function () {
    Config::set('services.google.client_id', 'test-id');
    Config::set('workflow.cfrd_email_domain', 'cfrd.cl');
    Config::set('workflow.cfrd_email_domains', ['cfrd.cl', 'udec.cl']);

    $oauthUser = Mockery::mock(OAuthUser::class);
    $oauthUser->shouldReceive('getId')->andReturn('gid-1');
    $oauthUser->shouldReceive('getEmail')->andReturn('persona@gmail.com');
    $oauthUser->shouldReceive('getAvatar')->andReturn(null);

    Socialite::shouldReceive('driver->user')->andReturn($oauthUser);

    $response = $this->get(route('google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status');
});

test('google callback crea usuario si no existe y asigna rol colaborador', function () {
    Config::set('services.google.client_id', 'test-id');
    Config::set('workflow.cfrd_email_domain', 'cfrd.cl');
    Config::set('workflow.cfrd_email_domains', ['cfrd.cl', 'udec.cl']);

    $oauthUser = Mockery::mock(OAuthUser::class);
    $oauthUser->shouldReceive('getId')->andReturn('gid-2');
    $oauthUser->shouldReceive('getEmail')->andReturn('noexiste@cfrd.cl');
    $oauthUser->shouldReceive('getAvatar')->andReturn(null);
    $oauthUser->shouldReceive('getName')->andReturn('Usuario Nuevo');

    Socialite::shouldReceive('driver->user')->andReturn($oauthUser);

    $response = $this->get(route('google.callback'));

    $response->assertRedirect(route('dashboard', absolute: false));

    $created = User::query()->where('email', 'noexiste@cfrd.cl')->first();
    expect($created)->not->toBeNull();
    $this->assertAuthenticatedAs($created);
    expect($created?->hasRole('colaborador'))->toBeTrue();
});

test('google callback con usuario cfrd existente inicia sesión', function () {
    Config::set('services.google.client_id', 'test-id');
    Config::set('workflow.cfrd_email_domain', 'cfrd.cl');
    Config::set('workflow.cfrd_email_domains', ['cfrd.cl', 'udec.cl']);

    $user = User::factory()->create([
        'email' => 'integracion@cfrd.cl',
        'password' => bcrypt('unused-for-oauth'),
    ]);
    Role::findOrCreate('admin', 'web');
    $user->assignRole('admin');

    $oauthUser = Mockery::mock(OAuthUser::class);
    $oauthUser->shouldReceive('getId')->andReturn('google-sub-99');
    $oauthUser->shouldReceive('getEmail')->andReturn('integracion@cfrd.cl');
    $oauthUser->shouldReceive('getAvatar')->andReturn('https://lh3.google.com/photo');
    $oauthUser->shouldReceive('getName')->andReturn('Integración CFRD');

    Socialite::shouldReceive('driver->user')->andReturn($oauthUser);

    $response = $this->get(route('google.callback'));

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);

    $user->refresh();
    expect($user->google_id)->toBe('google-sub-99')
        ->and($user->avatar)->toBe('https://lh3.google.com/photo');
});

test('google callback con email cfrd.cl encuentra usuario sembrado solo con udec.cl', function () {
    Config::set('services.google.client_id', 'test-id');
    Config::set('workflow.cfrd_email_domain', 'cfrd.cl');
    Config::set('workflow.cfrd_email_domains', ['cfrd.cl', 'udec.cl']);

    $user = User::factory()->create([
        'email' => 'mismo.local@udec.cl',
        'password' => bcrypt('unused-for-oauth'),
    ]);
    Role::findOrCreate('colaborador', 'web');
    $user->assignRole('colaborador');

    $oauthUser = Mockery::mock(OAuthUser::class);
    $oauthUser->shouldReceive('getId')->andReturn('google-sub-100');
    $oauthUser->shouldReceive('getEmail')->andReturn('mismo.local@cfrd.cl');
    $oauthUser->shouldReceive('getAvatar')->andReturn(null);
    $oauthUser->shouldReceive('getName')->andReturn('Mismo Local');

    Socialite::shouldReceive('driver->user')->andReturn($oauthUser);

    $response = $this->get(route('google.callback'));

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
});

test('google token login sin client id redirige a login con mensaje', function () {
    Config::set('services.google.client_id', null);

    $response = $this->post(route('google.token'), [
        'id_token' => 'dummy-token',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status');
    $this->assertGuest();
});

test('google token login rechaza dominio no permitido', function () {
    Config::set('services.google.client_id', 'test-client-id.apps.googleusercontent.com');
    Config::set('workflow.cfrd_email_domains', ['cfrd.cl', 'udec.cl']);

    Http::fake([
        'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
            'aud' => 'test-client-id.apps.googleusercontent.com',
            'sub' => 'google-sub-200',
            'email' => 'externo@gmail.com',
            'email_verified' => 'true',
        ], 200),
    ]);

    $response = $this->post(route('google.token'), [
        'id_token' => 'dummy-token',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status');
    $this->assertGuest();
});

test('google token login autentica usuario existente', function () {
    Config::set('services.google.client_id', 'test-client-id.apps.googleusercontent.com');
    Config::set('workflow.cfrd_email_domains', ['cfrd.cl', 'udec.cl']);

    $user = User::factory()->create([
        'email' => 'integracion@cfrd.cl',
    ]);
    Role::findOrCreate('pmo', 'web');
    $user->assignRole('pmo');

    Http::fake([
        'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
            'aud' => 'test-client-id.apps.googleusercontent.com',
            'sub' => 'google-sub-201',
            'email' => 'integracion@cfrd.cl',
            'email_verified' => 'true',
            'picture' => 'https://lh3.googleusercontent.com/photo',
        ], 200),
    ]);

    $response = $this->post(route('google.token'), [
        'id_token' => 'dummy-token',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);

    $user->refresh();
    expect($user->google_id)->toBe('google-sub-201')
        ->and($user->avatar)->toBe('https://lh3.googleusercontent.com/photo');
});

test('google token login crea usuario nuevo con rol colaborador', function () {
    Config::set('services.google.client_id', 'test-client-id.apps.googleusercontent.com');
    Config::set('workflow.cfrd_email_domains', ['cfrd.cl', 'udec.cl']);

    Http::fake([
        'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
            'aud' => 'test-client-id.apps.googleusercontent.com',
            'sub' => 'google-sub-300',
            'email' => 'nuevo.usuario@cfrd.cl',
            'name' => 'Nuevo Usuario',
            'email_verified' => 'true',
        ], 200),
    ]);

    $response = $this->post(route('google.token'), [
        'id_token' => 'dummy-token',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $created = User::query()->where('email', 'nuevo.usuario@cfrd.cl')->first();
    expect($created)->not->toBeNull();
    $this->assertAuthenticatedAs($created);
    expect($created?->hasRole('colaborador'))->toBeTrue();
});
