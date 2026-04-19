<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

test('POST /api/v1/auth/token rechaza credenciales inválidas', function (): void {
    $response = $this->postJson('/api/v1/auth/token', [
        'email' => 'nope@test.com',
        'password' => 'wrong',
        'device_name' => 'test',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email']);
});

test('POST /api/v1/auth/token emite bearer token', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('secret'),
    ]);
    $user->assignRole('pmo');

    $response = $this->postJson('/api/v1/auth/token', [
        'email' => $user->email,
        'password' => 'secret',
        'device_name' => 'phpunit',
    ]);

    $response->assertOk()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email', 'cargo', 'avatar', 'roles'],
        ]);
});

test('GET /api/v1/user requiere autenticación', function (): void {
    $this->getJson('/api/v1/user')->assertUnauthorized();
});

test('GET /api/v1/user devuelve perfil con Sanctum', function (): void {
    $user = User::factory()->create();
    $user->assignRole('coordinador');

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/user')
        ->assertOk()
        ->assertJsonPath('email', $user->email)
        ->assertJsonPath('roles.0', 'coordinador')
        ->assertJsonStructure([
            'id',
            'name',
            'email',
            'cargo',
            'avatar',
            'roles',
        ]);
});

test('GET /api/v1/my-tasks incluye proyecto con etiqueta y estado', function (): void {
    $jefe = User::factory()->create();
    $jefe->assignRole('jefe_proyecto');

    $colab = User::factory()->create();
    $colab->assignRole('colaborador');

    $project = Project::factory()->create([
        'jefe_proyecto_id' => $jefe->id,
        'name' => 'Sitio web demo',
        'code' => 'WEB-01',
        'status' => Project::STATUS_ACTIVO,
    ]);

    Task::factory()->create([
        'project_id' => $project->id,
        'assignee_id' => $colab->id,
        'title' => 'Tarea API',
    ]);

    Sanctum::actingAs($colab);

    $this->getJson('/api/v1/my-tasks')
        ->assertOk()
        ->assertJsonPath('data.0.project.display_label', '[WEB-01] Sitio web demo')
        ->assertJsonPath('data.0.project.status', Project::STATUS_ACTIVO);
});

test('GET /api/v1/my-tasks incluye colaboradores adicionales', function (): void {
    $jefe = User::factory()->create();
    $jefe->assignRole('jefe_proyecto');

    $colab = User::factory()->create();
    $colab->assignRole('colaborador');

    $otro = User::factory()->create();
    $otro->assignRole('colaborador');

    $project = Project::factory()->create([
        'jefe_proyecto_id' => $jefe->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assignee_id' => $colab->id,
        'title' => 'Tarea con equipo',
    ]);

    $task->collaborators()->sync([$otro->id]);

    Sanctum::actingAs($colab);

    $this->getJson('/api/v1/my-tasks')
        ->assertOk()
        ->assertJsonPath('data.0.collaborators.0.id', $otro->id)
        ->assertJsonPath('data.0.collaborators.0.name', $otro->name);
});

test('GET /api/v1/projects lista según rol PMO', function (): void {
    $user = User::factory()->create();
    $user->assignRole('pmo');

    Project::factory()->count(2)->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/projects')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('POST /api/v1/projects crea proyecto', function (): void {
    $user = User::factory()->create();
    $user->assignRole('pmo');

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/projects', [
        'name' => 'API Test',
        'status' => Project::STATUS_BORRADOR,
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'API Test');

    expect(Project::query()->where('name', 'API Test')->exists())->toBeTrue();
});

test('PATCH /api/v1/tasks/{task} actualiza estado para jefe con acceso', function (): void {
    $jefe = User::factory()->create();
    $jefe->assignRole('jefe_proyecto');

    $project = Project::factory()->create([
        'jefe_proyecto_id' => $jefe->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'status' => Task::STATUS_PENDIENTE,
    ]);

    Sanctum::actingAs($jefe);

    $this->patchJson("/api/v1/tasks/{$task->id}", [
        'status' => Task::STATUS_EN_CURSO,
    ])
        ->assertOk()
        ->assertJsonPath('data.status', Task::STATUS_EN_CURSO);
});

test('colaborador puede PATCH su tarea asignada', function (): void {
    $jefe = User::factory()->create();
    $jefe->assignRole('jefe_proyecto');

    $colab = User::factory()->create();
    $colab->assignRole('colaborador');

    $project = Project::factory()->create([
        'jefe_proyecto_id' => $jefe->id,
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assignee_id' => $colab->id,
        'status' => Task::STATUS_PENDIENTE,
    ]);

    Sanctum::actingAs($colab);

    $this->patchJson("/api/v1/tasks/{$task->id}", [
        'status' => Task::STATUS_EN_CURSO,
    ])->assertOk();
});

test('PATCH /api/v1/projects/{project} actualiza proyecto para PMO', function (): void {
    $user = User::factory()->create();
    $user->assignRole('pmo');

    $project = Project::factory()->create([
        'name' => 'Nombre previo',
        'status' => Project::STATUS_BORRADOR,
    ]);

    Sanctum::actingAs($user);

    $this->patchJson("/api/v1/projects/{$project->id}", [
        'name' => 'Nombre API',
        'status' => Project::STATUS_ACTIVO,
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Nombre API')
        ->assertJsonPath('data.status', Project::STATUS_ACTIVO);

    expect($project->fresh()->name)->toBe('Nombre API');
});

test('colaborador no lista proyectos en índice PMO', function (): void {
    $colab = User::factory()->create();
    $colab->assignRole('colaborador');

    Sanctum::actingAs($colab);

    $this->getJson('/api/v1/projects')->assertForbidden();
});

test('POST /api/v1/broadcasting/auth requiere autenticación', function (): void {
    $this->postJson('/api/v1/broadcasting/auth', [
        'socket_id' => '1.2',
        'channel_name' => 'private-App.Models.User.1',
    ])->assertUnauthorized();
});

test('POST /api/v1/broadcasting/auth autoriza canal privado con Sanctum', function (): void {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-app-key',
        'broadcasting.connections.reverb.secret' => 'test-app-secret',
        'broadcasting.connections.reverb.app_id' => 'test-app',
        'broadcasting.connections.reverb.options' => [
            'host' => 'localhost',
            'port' => 8080,
            'scheme' => 'http',
            'useTLS' => false,
        ],
    ]);
    Broadcast::purge();
    require base_path('routes/channels.php');

    $user = User::factory()->create();
    $user->assignRole('colaborador');
    $plainToken = $user->createToken('phpunit-broadcast')->plainTextToken;

    $this->withToken($plainToken)
        ->postJson('/api/v1/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-App.Models.User.'.$user->id,
        ])
        ->assertOk()
        ->assertJsonStructure(['auth']);
});

test('POST /api/v1/broadcasting/auth rechaza canal de otro usuario', function (): void {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-app-key',
        'broadcasting.connections.reverb.secret' => 'test-app-secret',
        'broadcasting.connections.reverb.app_id' => 'test-app',
        'broadcasting.connections.reverb.options' => [
            'host' => 'localhost',
            'port' => 8080,
            'scheme' => 'http',
            'useTLS' => false,
        ],
    ]);
    Broadcast::purge();
    require base_path('routes/channels.php');

    $user = User::factory()->create();
    $other = User::factory()->create();
    $user->assignRole('colaborador');
    $plainToken = $user->createToken('phpunit-broadcast-deny')->plainTextToken;

    $this->withToken($plainToken)
        ->postJson('/api/v1/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-App.Models.User.'.$other->id,
        ])->assertForbidden();
});
