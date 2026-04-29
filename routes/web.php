<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Workflow\ColaboradorMisTareasController;
use App\Http\Controllers\Workflow\ColaboradorUrgentesController;
use App\Http\Controllers\Workflow\CoordinacionBacklogController;
use App\Http\Controllers\Workflow\CoordinacionEquiposCargaController;
use App\Http\Controllers\Workflow\CoordinacionValidacionController;
use App\Http\Controllers\Workflow\PmoMacroVisibilityController;
use App\Http\Controllers\Workflow\PmoProyectosController;
use App\Http\Controllers\Workflow\PmoTableroMacroController;
use App\Http\Controllers\Workflow\ProyectoKanbanController;
use App\Http\Controllers\Workflow\ProyectoMinutasController;
use App\Http\Controllers\Workflow\ProyectoWorkspaceController;
use App\Http\Controllers\Workflow\SistemaAuditoriaController;
use App\Http\Controllers\Workflow\SistemaConfiguracionTransversalController;
use App\Http\Controllers\Workflow\SistemaLrsController;
use App\Http\Controllers\Workflow\SistemaNotificacionesController;
use App\Http\Controllers\Workflow\SistemaUsuariosRolesController;
use App\Http\Controllers\Workflow\TalentoMapaRelacionesController;
use App\Http\Controllers\Workflow\TalentoMatrizSkillsController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('auth/google', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
    Route::post('auth/google/token', [GoogleAuthController::class, 'tokenLogin'])->name('google.token');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:admin|pmo|coordinador|jefe_proyecto|colaborador')->prefix('pmo')->name('pmo.')->group(function () {
        Route::get('tablero-macro', PmoTableroMacroController::class)->name('tablero-macro');
        Route::get('indicadores', fn () => redirect()->route('pmo.tablero-macro', ['segment' => 'kpi']))->name('indicadores');
        Route::get('gantt', fn () => redirect()->route('pmo.tablero-macro', ['segment' => 'gantt']))->name('gantt');
        Route::get('calendario-macro', fn () => redirect()->route('pmo.tablero-macro', ['segment' => 'calendario']))->name('calendario-macro');
        Route::get('kanban-macro', fn () => redirect()->route('pmo.tablero-macro', ['segment' => 'kanban']))->name('kanban-macro');
        Route::get('carga-equipo', fn () => redirect()->route('pmo.tablero-macro', ['segment' => 'carga']))->name('carga-equipo');
    });

    Route::middleware('role:admin|pmo')->prefix('pmo')->name('pmo.')->group(function () {
        Route::post('tablero-macro/visibilidad', [PmoMacroVisibilityController::class, 'update'])->name('tablero-macro.visibilidad');
        Route::get('proyectos', [PmoProyectosController::class, 'index'])->name('proyectos');
        Route::post('proyectos', [PmoProyectosController::class, 'store'])->name('proyectos.store');
        Route::get('proyectos/{project}/acta-constitucion', [PmoProyectosController::class, 'downloadActaConstitucion'])->name('proyectos.acta-constitucion');
        Route::patch('proyectos/{project}', [PmoProyectosController::class, 'update'])->name('proyectos.update');
    });

    Route::middleware('role:admin|coordinador|pmo')->prefix('coordinacion')->name('coordinacion.')->group(function () {
        Route::get('equipos-carga', CoordinacionEquiposCargaController::class)->name('equipos-carga');
        Route::get('backlog-tareas', [CoordinacionBacklogController::class, 'index'])->name('backlog-tareas');
        Route::post('backlog-tareas', [CoordinacionBacklogController::class, 'store'])->name('backlog-tareas.store');
        Route::get('validacion-avances', [CoordinacionValidacionController::class, 'index'])->name('validacion-avances');
        Route::patch('validacion-avances/tareas/{task}', [CoordinacionValidacionController::class, 'updateTask'])->name('validacion-avances.tareas.update');
        Route::patch('validacion-avances/skills/{skill_validation}', [CoordinacionValidacionController::class, 'updateSkillValidation'])->name('validacion-avances.skills.update');
    });

    Route::middleware('role:admin|pmo|coordinador|jefe_proyecto')->prefix('proyecto')->name('proyecto.')->group(function () {
        Route::get('tabla', [ProyectoWorkspaceController::class, 'tabla'])->name('tabla');
        Route::get('cronograma', [ProyectoWorkspaceController::class, 'cronograma'])->name('cronograma');
        Route::get('calendario', [ProyectoWorkspaceController::class, 'calendario'])->name('calendario');
        Route::get('kanban', [ProyectoKanbanController::class, 'index'])->name('kanban');
        Route::patch('kanban/orden', [ProyectoKanbanController::class, 'syncKanban'])->name('kanban.sync');
        Route::post('task-groups', [ProyectoKanbanController::class, 'storeTaskGroup'])->name('task-groups.store');
        Route::post('tareas', [ProyectoKanbanController::class, 'storeTask'])->name('tareas.store');
        Route::patch('tareas/{task}', [ProyectoKanbanController::class, 'updateTask'])->name('tareas.update');
        Route::get('minutas', [ProyectoMinutasController::class, 'index'])->name('minutas');
        Route::post('minutas', [ProyectoMinutasController::class, 'store'])->name('minutas.store');
    });

    Route::middleware('role:admin|colaborador')->prefix('colaborador')->name('colaborador.')->group(function () {
        Route::get('mis-tareas', ColaboradorMisTareasController::class)->name('mis-tareas');
        Route::get('urgentes', ColaboradorUrgentesController::class)->name('urgentes');
    });

    Route::middleware('role:admin|pmo|coordinador|jefe_proyecto|colaborador')->prefix('talento')->name('talento.')->group(function () {
        Route::get('matriz-skills', TalentoMatrizSkillsController::class)->name('matriz-skills');
        Route::get('mapa-relaciones', TalentoMapaRelacionesController::class)->name('mapa-relaciones');
    });

    Route::prefix('sistema')->name('sistema.')->group(function () {
        Route::middleware('role:admin|pmo')->group(function () {
            Route::get('auditoria', SistemaAuditoriaController::class)->name('auditoria');
            Route::get('configuracion-transversal', SistemaConfiguracionTransversalController::class)->name('configuracion-transversal');
            Route::patch('configuracion-transversal', [SistemaConfiguracionTransversalController::class, 'update'])->name('configuracion-transversal.update');
            Route::get('usuarios-roles', SistemaUsuariosRolesController::class)->name('usuarios-roles');
            Route::patch('usuarios-roles/{user}', [SistemaUsuariosRolesController::class, 'update'])->name('usuarios-roles.update');
            Route::get('lrs', SistemaLrsController::class)->name('lrs');
        });
        Route::middleware('role:admin|pmo|coordinador|jefe_proyecto|colaborador')->group(function () {
            Route::get('notificaciones', SistemaNotificacionesController::class)->name('notificaciones');
        });
    });
});

require __DIR__.'/settings.php';
