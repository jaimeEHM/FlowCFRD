<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectMinute;
use App\Models\Skill;
use App\Models\SkillValidation;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\Support\CfrdEquipoConfig;
use Database\Seeders\Support\CfrdProyectosWebConfig;
use Illuminate\Database\Seeder;

class WorkflowDomainSeeder extends Seeder
{
    public function run(): void
    {
        $v = CfrdEquipoConfig::read()['vinculacion_demo'];

        $admin = $this->userByEmail(CfrdEquipoConfig::emailOr($v, 'admin_email', 'dbordon@udec.cl'));
        $pmo = $this->userByEmail(CfrdEquipoConfig::emailOr($v, 'pmo_email', 'marcospalma@udec.cl'));
        $coord = $this->userByEmail(CfrdEquipoConfig::emailOr($v, 'coordinador_email', 'pmartinez@udec.cl'));
        $jefe = $this->userByEmail(CfrdEquipoConfig::emailOr($v, 'jefe_proyecto_principal_email', 'marcospalma@udec.cl'));
        $colab = $this->userByEmail(CfrdEquipoConfig::emailOr($v, 'colaborador_principal_email', 'fsuazo@udec.cl'));
        $colab2Email = CfrdEquipoConfig::emailOr($v, 'colaborador_secundario_email', 'aalcaino@udec.cl');
        $colab2 = $this->userByEmail($colab2Email);

        if (! $jefe || ! $pmo || ! $colab || ! $admin) {
            $this->command?->warn('WorkflowDomainSeeder: faltan usuarios CFRD; ejecute CfrdDevUserSeeder antes.');

            return;
        }

        $skills = collect([
            ['name' => 'Gestión de proyectos', 'slug' => 'gestion-proyectos', 'description' => 'Planificación y seguimiento'],
            ['name' => 'Comunicación efectiva', 'slug' => 'comunicacion-efectiva', 'description' => 'Presentación y documentación'],
            ['name' => 'Análisis de datos', 'slug' => 'analisis-datos', 'description' => 'Herramientas cuantitativas'],
        ])->map(fn (array $s) => Skill::query()->firstOrCreate(['slug' => $s['slug']], $s));

        $colab->skills()->syncWithoutDetaching([
            $skills[0]->id => ['level' => 3],
            $skills[1]->id => ['level' => 4],
        ]);

        if ($colab2 && $colab2->id !== $colab->id) {
            $colab2->skills()->syncWithoutDetaching([
                $skills[1]->id => ['level' => 2],
            ]);
        }

        $proyectosDef = CfrdProyectosWebConfig::proyectos();
        if ($proyectosDef === []) {
            $this->command?->warn('WorkflowDomainSeeder: no hay proyectos en cfrd_proyectos_web.json; omitiendo cartera demo.');

            SkillValidation::query()->create([
                'skill_id' => $skills[0]->id,
                'subject_user_id' => $colab->id,
                'validator_user_id' => $coord?->id ?? $pmo->id,
                'status' => 'pendiente',
                'comment' => null,
            ]);

            return;
        }

        $primerProyecto = null;

        foreach ($proyectosDef as $i => $def) {
            if (! is_array($def)) {
                continue;
            }

            $code = $def['code'] ?? null;
            $name = $def['name'] ?? null;
            $description = $def['description'] ?? '';
            $status = $def['status'] ?? Project::STATUS_ACTIVO;
            $jefeEmail = $def['jefe_email'] ?? null;
            $createdByEmail = $def['created_by_email'] ?? null;

            if (! is_string($code) || ! is_string($name) || ! is_string($jefeEmail) || ! is_string($createdByEmail)) {
                continue;
            }

            $jefeP = $this->userByEmail($jefeEmail);
            $creator = $this->userByEmail($createdByEmail);
            if ($jefeP === null || $creator === null) {
                $this->command?->warn("WorkflowDomainSeeder: omitiendo {$code} (usuario jefe o creador no encontrado).");

                continue;
            }

            if (! in_array($status, Project::STATUSES, true)) {
                $status = Project::STATUS_ACTIVO;
            }

            $offset = (int) $i;
            $project = Project::query()->create([
                'name' => $name,
                'code' => $code,
                'description' => is_string($description) ? $description : '',
                'carta_inicio_at' => now()->subMonths(max(1, 12 - $offset))->startOfMonth(),
                'starts_at' => now()->subMonths(max(0, 10 - $offset)),
                'ends_at' => now()->addMonths(6 + ($offset % 4)),
                'status' => $status,
                'jefe_proyecto_id' => $jefeP->id,
                'created_by_id' => $creator->id,
            ]);

            if ($primerProyecto === null) {
                $primerProyecto = $project;
            }

            $tareas = $def['tareas'] ?? [];
            if (! is_array($tareas)) {
                continue;
            }

            foreach ($tareas as $t) {
                if (! is_array($t)) {
                    continue;
                }
                $title = $t['title'] ?? null;
                $assigneeEmail = $t['assignee_email'] ?? null;
                $taskStatus = $t['status'] ?? Task::STATUS_BACKLOG;
                $isUrgent = (bool) ($t['is_urgent'] ?? false);
                $backlogOrder = (int) ($t['backlog_order'] ?? 0);

                if (! is_string($title) || ! is_string($assigneeEmail)) {
                    continue;
                }

                if (! in_array($taskStatus, Task::STATUSES, true)) {
                    $taskStatus = Task::STATUS_BACKLOG;
                }

                $assignee = $this->userByEmail($assigneeEmail);
                if ($assignee === null) {
                    continue;
                }

                Task::query()->create([
                    'project_id' => $project->id,
                    'title' => $title,
                    'description' => null,
                    'status' => $taskStatus,
                    'is_urgent' => $isUrgent,
                    'backlog_order' => $backlogOrder,
                    'assignee_id' => $assignee->id,
                    'due_date' => now()->addWeeks(2 + ($offset % 3)),
                    'created_by_id' => $pmo->id,
                    'validation_status' => $isUrgent ? Task::VALIDATION_PENDIENTE : null,
                ]);
            }
        }

        SkillValidation::query()->create([
            'skill_id' => $skills[0]->id,
            'subject_user_id' => $colab->id,
            'validator_user_id' => $coord?->id ?? $pmo->id,
            'status' => 'pendiente',
            'comment' => null,
        ]);

        if ($primerProyecto !== null) {
            ProjectMinute::query()->create([
                'project_id' => $primerProyecto->id,
                'title' => 'Comité de seguimiento cartera CFRD',
                'body' => "Alineación con proyectos publicados en cfrd.udec.cl/proyectos-cfrd/.\nPriorizar entregables trimestrales y riesgos cross-proyecto.",
                'held_at' => now()->subDays(5),
                'created_by_id' => $jefe->id,
            ]);
        }

        $pEnglish = Project::query()->where('code', 'PRJ-CFRD-007')->first();
        if ($pEnglish !== null) {
            ProjectMinute::query()->create([
                'project_id' => $pEnglish->id,
                'title' => 'Revisión UX contenidos inglés',
                'body' => "Acuerdos:\n- Calendario editorial Q2.\n- Métricas de participación.",
                'held_at' => now()->subDays(12),
                'created_by_id' => $pmo->id,
            ]);
        }
    }

    private function userByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }
}
