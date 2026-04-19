<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Pruebas: tareas asignadas a admin@cfrd.cl en varios proyectos (flujo mis tareas / escritorio / rol colaborador).
 * Idempotente: elimina tareas previas con título prefijado "[Demo admin]" del mismo asignatario y las vuelve a crear.
 */
class AdminCfrdDemoTasksSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@cfrd.cl';

    private const TITLE_PREFIX = '[Demo admin]';

    public function run(): void
    {
        $admin = User::query()->where('email', self::ADMIN_EMAIL)->first();
        if ($admin === null) {
            $this->command?->warn('AdminCfrdDemoTasksSeeder: no existe '.self::ADMIN_EMAIL.' (ejecute CfrdDevUserSeeder).');

            return;
        }

        if (! $admin->hasRole('colaborador')) {
            $admin->assignRole('colaborador');
        }

        Task::query()
            ->where('assignee_id', $admin->id)
            ->where('title', 'like', self::TITLE_PREFIX.'%')
            ->delete();

        $pmo = User::query()->where('email', 'marcospalma@udec.cl')->first()
            ?? User::query()->where('email', 'pmartinez@udec.cl')->first()
            ?? $admin;

        /** @var list<array{code: string, tasks: list<array{title: string, status: string, is_urgent?: bool}>}> $blocks */
        $blocks = [
            [
                'code' => 'PRJ-CFRD-001',
                'tasks' => [
                    ['title' => self::TITLE_PREFIX.' Revisión checklist accesibilidad entregable', 'status' => Task::STATUS_EN_CURSO],
                    ['title' => self::TITLE_PREFIX.' Sincronizar feedback PMO con backlog', 'status' => Task::STATUS_PENDIENTE],
                ],
            ],
            [
                'code' => 'PRJ-CFRD-003',
                'tasks' => [
                    ['title' => self::TITLE_PREFIX.' Validar copy y microcopy módulo salud', 'status' => Task::STATUS_REVISION],
                ],
            ],
            [
                'code' => 'PRJ-CFRD-007',
                'tasks' => [
                    ['title' => self::TITLE_PREFIX.' Smoke test flujo tareas asignadas (escritorio)', 'status' => Task::STATUS_BACKLOG],
                ],
            ],
        ];

        $created = 0;

        foreach ($blocks as $block) {
            $project = Project::query()->where('code', $block['code'])->first();
            if ($project === null) {
                $this->command?->warn("AdminCfrdDemoTasksSeeder: proyecto {$block['code']} no existe (¿WorkflowDomainSeeder con JSON?).");

                continue;
            }

            foreach ($block['tasks'] as $i => $t) {
                $isUrgent = (bool) ($t['is_urgent'] ?? false);

                Task::query()->create([
                    'project_id' => $project->id,
                    'title' => $t['title'],
                    'description' => 'Sembrada por AdminCfrdDemoTasksSeeder (entorno de pruebas).',
                    'status' => $t['status'],
                    'is_urgent' => $isUrgent,
                    'backlog_order' => $i,
                    'assignee_id' => $admin->id,
                    'due_date' => now()->addWeeks(1 + $i),
                    'created_by_id' => $pmo->id,
                    'validation_status' => $isUrgent ? Task::VALIDATION_PENDIENTE : null,
                ]);
                $created++;
            }
        }

        if ($created === 0) {
            $this->command?->warn('AdminCfrdDemoTasksSeeder: no se crearon tareas (sin proyectos demo).');

            return;
        }

        $this->command?->info("AdminCfrdDemoTasksSeeder: {$created} tarea(s) demo para ".self::ADMIN_EMAIL.' (rol colaborador asegurado).');
    }
}
