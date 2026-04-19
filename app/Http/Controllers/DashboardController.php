<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\SkillValidation;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $roles = $user->getRoleNames()->values();
        $has = fn (string ...$names): bool => $roles->intersect($names)->isNotEmpty();

        $blocks = [];

        if ($has('admin')) {
            $blocks[] = [
                'id' => 'admin',
                'title' => 'Administración',
                'subtitle' => 'Visión global del sistema y trazabilidad.',
                'metrics' => [
                    $this->metric('Usuarios registrados', (string) User::query()->count(), route('pmo.indicadores'), 'neutral'),
                    $this->metric('Auditoría', 'Abrir', route('sistema.auditoria'), 'accent'),
                ],
                'items' => [],
            ];
        }

        if ($has('pmo') || $has('admin')) {
            $projectsByStatus = Project::query()
                ->selectRaw('status, count(*) as c')
                ->groupBy('status')
                ->pluck('c', 'status');

            $openTasks = Task::query()->where('status', '!=', Task::STATUS_HECHA)->count();
            $urgentPending = Task::query()
                ->where('is_urgent', true)
                ->where('validation_status', Task::VALIDATION_PENDIENTE)
                ->count();

            $portfolioRows = Project::query()
                ->with(['jefeProyecto:id,name,avatar'])
                ->withCount(['tasks as open_tasks' => fn ($q) => $q->where('status', '!=', Task::STATUS_HECHA)])
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get()
                ->map(fn (Project $p) => [
                    'title' => $p->name,
                    'meta' => ($p->jefeProyecto?->name ?? 'Sin jefe').' · '.$p->open_tasks.' tareas abiertas',
                    'href' => route('proyecto.kanban', ['project_id' => $p->id]),
                    'status_label' => $p->status,
                    'status_tone' => 'neutral',
                ])
                ->all();

            $blocks[] = [
                'id' => 'pmo',
                'title' => 'Cartera PMO',
                'subtitle' => 'Referencia tipo Monday: tablero macro, prioridades y estado por iniciativa.',
                'metrics' => [
                    $this->metric('Proyectos', (string) Project::query()->count(), route('pmo.proyectos'), 'primary'),
                    $this->metric('Tareas abiertas', (string) $openTasks, route('pmo.indicadores'), 'primary'),
                    $this->metric('Urgentes pendientes de validación', (string) $urgentPending, route('coordinacion.validacion-avances'), $urgentPending > 0 ? 'warning' : 'neutral'),
                    $this->metric('Tablero macro', 'Abrir', route('pmo.tablero-macro'), 'accent'),
                ],
                'items' => $portfolioRows,
                'extra' => [
                    'projects_by_status' => $projectsByStatus,
                ],
            ];
        }

        if ($has('coordinador')) {
            $pendingUrgent = Task::query()
                ->where('is_urgent', true)
                ->where('validation_status', Task::VALIDATION_PENDIENTE)
                ->count();
            $pendingSkills = SkillValidation::query()->where('status', 'pendiente')->count();
            $backlogCount = Task::query()->where('status', Task::STATUS_BACKLOG)->count();

            $validationItems = Task::query()
                ->where('is_urgent', true)
                ->where('validation_status', Task::VALIDATION_PENDIENTE)
                ->with(['project:id,name', 'assignee:id,name,avatar'])
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get()
                ->map(fn (Task $t) => [
                    'title' => $t->title,
                    'meta' => ($t->project?->name ?? 'Proyecto').' · '.($t->assignee?->name ?? 'Sin asignar'),
                    'href' => route('coordinacion.validacion-avances'),
                    'status_label' => 'Validar',
                    'status_tone' => 'warning',
                ])
                ->all();

            $blocks[] = [
                'id' => 'coordinacion',
                'title' => 'Coordinación',
                'subtitle' => 'Cola de validación y backlog — similar a “My work” + tableros de equipo en Monday.',
                'metrics' => [
                    $this->metric('Urgentes a validar', (string) $pendingUrgent, route('coordinacion.validacion-avances'), $pendingUrgent > 0 ? 'warning' : 'neutral'),
                    $this->metric('Skills pendientes', (string) $pendingSkills, route('coordinacion.validacion-avances'), $pendingSkills > 0 ? 'warning' : 'neutral'),
                    $this->metric('Tareas en backlog', (string) $backlogCount, route('coordinacion.backlog-tareas'), 'primary'),
                    $this->metric('Equipos y carga', 'Abrir', route('coordinacion.equipos-carga'), 'accent'),
                ],
                'items' => $validationItems,
            ];
        }

        if ($has('jefe_proyecto')) {
            $myProjectIds = Project::query()
                ->where('jefe_proyecto_id', $user->id)
                ->pluck('id');

            $myProjectsCount = $myProjectIds->count();
            $tasksInMyProjects = Task::query()
                ->whereIn('project_id', $myProjectIds)
                ->where('status', '!=', Task::STATUS_HECHA)
                ->count();

            $jefeItems = Task::query()
                ->whereIn('project_id', $myProjectIds)
                ->where('status', '!=', Task::STATUS_HECHA)
                ->with(['project:id,name'])
                ->orderByRaw('CASE WHEN is_urgent = 1 THEN 0 ELSE 1 END')
                ->orderBy('due_date')
                ->limit(6)
                ->get()
                ->map(fn (Task $t) => [
                    'title' => $t->title,
                    'meta' => $t->project?->name ?? 'Proyecto',
                    'href' => route('proyecto.kanban', ['project_id' => $t->project_id]),
                    'status_label' => $t->status,
                    'status_tone' => $t->is_urgent ? 'warning' : 'neutral',
                ])
                ->all();

            $blocks[] = [
                'id' => 'jefe_proyecto',
                'title' => 'Tus tableros de ejecución',
                'subtitle' => 'Kanban y minutas por proyecto — analogía a boards y grupos en Monday.',
                'metrics' => [
                    $this->metric('Proyectos a tu cargo', (string) $myProjectsCount, route('pmo.proyectos'), 'primary'),
                    $this->metric('Tareas abiertas', (string) $tasksInMyProjects, route('proyecto.kanban'), 'primary'),
                    $this->metric('Kanban', 'Abrir', route('proyecto.kanban'), 'accent'),
                    $this->metric('Minutas', 'Abrir', route('proyecto.minutas'), 'accent'),
                ],
                'items' => $jefeItems,
            ];
        }

        if ($has('colaborador')) {
            $myOpen = Task::query()
                ->where('assignee_id', $user->id)
                ->where('status', '!=', Task::STATUS_HECHA)
                ->count();
            $myUrgent = Task::query()
                ->where('assignee_id', $user->id)
                ->where('is_urgent', true)
                ->where('status', '!=', Task::STATUS_HECHA)
                ->count();

            $myItems = Task::query()
                ->where('assignee_id', $user->id)
                ->where('status', '!=', Task::STATUS_HECHA)
                ->with(['project:id,name'])
                ->orderByRaw('CASE WHEN is_urgent = 1 THEN 0 ELSE 1 END')
                ->orderBy('due_date')
                ->limit(8)
                ->get()
                ->map(fn (Task $t) => [
                    'title' => $t->title,
                    'meta' => ($t->project?->name ?? 'Proyecto').($t->due_date ? ' · '.$t->due_date->format('d/m/Y') : ''),
                    'href' => route('colaborador.mis-tareas'),
                    'status_label' => $t->status,
                    'status_tone' => $t->is_urgent ? 'warning' : 'neutral',
                ])
                ->all();

            $blocks[] = [
                'id' => 'colaborador',
                'title' => 'Mi trabajo',
                'subtitle' => 'Prioridades personales — equivalente a “My work” / bandeja en Monday.',
                'metrics' => [
                    $this->metric('Tareas abiertas', (string) $myOpen, route('colaborador.mis-tareas'), 'primary'),
                    $this->metric('Urgentes', (string) $myUrgent, route('colaborador.urgentes'), $myUrgent > 0 ? 'warning' : 'neutral'),
                    $this->metric('Mis tareas', 'Abrir', route('colaborador.mis-tareas'), 'accent'),
                ],
                'items' => $myItems,
            ];
        }

        if ($blocks === []) {
            $blocks[] = [
                'id' => 'sin_rol',
                'title' => 'Bienvenido',
                'subtitle' => 'Aún no tienes un rol de workflow reconocido o no aplica contenido personalizado. Usa el mapa de módulos o contacta a un administrador.',
                'metrics' => [],
                'items' => [],
            ];
        }

        return Inertia::render('Dashboard', [
            'greeting' => [
                'title' => $this->greetingTitle($user->name),
                'subtitle' => 'Tableros, grupos e ítems con estado — inspirado en Monday.com como referencia de UX para este workflow CFRD.',
            ],
            'blocks' => $blocks,
        ]);
    }

    private function greetingTitle(string $name): string
    {
        $h = (int) now()->format('G');
        $prefix = match (true) {
            $h < 12 => 'Buenos días',
            $h < 20 => 'Buenas tardes',
            default => 'Buenas noches',
        };

        return $prefix.', '.$name;
    }

    /**
     * @return array{key: string, label: string, value: string, href: string, tone: string}
     */
    private function metric(string $label, string $value, string $href, string $tone): array
    {
        return [
            'key' => md5($label.$href),
            'label' => $label,
            'value' => $value,
            'href' => $href,
            'tone' => $tone,
        ];
    }
}
