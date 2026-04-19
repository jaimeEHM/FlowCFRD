<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as OAuthUser;

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

test('google callback sin usuario en base redirige con mensaje', function () {
    Config::set('services.google.client_id', 'test-id');
    Config::set('workflow.cfrd_email_domain', 'cfrd.cl');
    Config::set('workflow.cfrd_email_domains', ['cfrd.cl', 'udec.cl']);

    $oauthUser = Mockery::mock(OAuthUser::class);
    $oauthUser->shouldReceive('getId')->andReturn('gid-2');
    $oauthUser->shouldReceive('getEmail')->andReturn('noexiste@cfrd.cl');
    $oauthUser->shouldReceive('getAvatar')->andReturn(null);

    Socialite::shouldReceive('driver->user')->andReturn($oauthUser);

    $response = $this->get(route('google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status');
});

test('google callback con usuario cfrd existente inicia sesión', function () {
    Config::set('services.google.client_id', 'test-id');
    Config::set('workflow.cfrd_email_domain', 'cfrd.cl');
    Config::set('workflow.cfrd_email_domains', ['cfrd.cl', 'udec.cl']);

    $user = User::factory()->create([
        'email' => 'integracion@cfrd.cl',
        'password' => bcrypt('unused-for-oauth'),
    ]);

    $oauthUser = Mockery::mock(OAuthUser::class);
    $oauthUser->shouldReceive('getId')->andReturn('google-sub-99');
    $oauthUser->shouldReceive('getEmail')->andReturn('integracion@cfrd.cl');
    $oauthUser->shouldReceive('getAvatar')->andReturn('https://lh3.google.com/photo');

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

    $oauthUser = Mockery::mock(OAuthUser::class);
    $oauthUser->shouldReceive('getId')->andReturn('google-sub-100');
    $oauthUser->shouldReceive('getEmail')->andReturn('mismo.local@cfrd.cl');
    $oauthUser->shouldReceive('getAvatar')->andReturn(null);

    Socialite::shouldReceive('driver->user')->andReturn($oauthUser);

    $response = $this->get(route('google.callback'));

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
});
