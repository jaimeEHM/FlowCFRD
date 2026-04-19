<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Broadcasting\BroadcastController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API (prefijo /api aplicado por el framework)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function (): void {
    Route::post('auth/token', [AuthTokenController::class, 'store'])
        ->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum'])->group(function (): void {
        /** Echo/Reverb: autorización de canales privados con Bearer (escritorio Electron). */
        Route::match(['get', 'post'], 'broadcasting/auth', [BroadcastController::class, 'authenticate']);

        Route::get('user', [AuthTokenController::class, 'user']);

        Route::get('my-tasks', [TaskController::class, 'myIndex'])
            ->middleware(['role:admin|pmo|coordinador|jefe_proyecto|colaborador']);

        Route::get('projects', [ProjectController::class, 'index'])
            ->middleware(['role:admin|pmo|coordinador|jefe_proyecto']);
        Route::post('projects', [ProjectController::class, 'store'])
            ->middleware(['role:admin|pmo|coordinador']);
        Route::get('projects/{project}', [ProjectController::class, 'show'])
            ->middleware(['role:admin|pmo|coordinador|jefe_proyecto|colaborador']);

        Route::patch('projects/{project}', [ProjectController::class, 'update'])
            ->middleware(['role:admin|pmo|coordinador|jefe_proyecto']);

        Route::get('projects/{project}/tasks', [TaskController::class, 'indexByProject'])
            ->middleware(['role:admin|pmo|coordinador|jefe_proyecto|colaborador']);

        Route::patch('tasks/{task}', [TaskController::class, 'update'])
            ->middleware(['role:admin|pmo|coordinador|jefe_proyecto|colaborador']);
    });
});
