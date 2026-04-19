<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\PmoMacroVisibilityService;
use App\Support\PmoKpiData;
use App\Support\PmoPortfolioWorkloadData;
use App\Support\ProyectoKanbanPayload;
use App\Support\ProyectoTaskListPayload;
use App\Support\TaskGanttRows;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PmoTableroMacroController extends Controller
{
    public function __invoke(Request $request, PmoMacroVisibilityService $visibility): Response|RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $segment = $request->query('segment', 'cartera');
        if (! in_array($segment, ['cartera', 'kpi', 'gantt', 'lista', 'kanban', 'carga'], true)) {
            $segment = 'cartera';
        }

        $tabs = $visibility->visibleTabSegmentsFor($user);
        if ($segment === 'kpi' && ! $tabs['kpi']) {
            return redirect()->route('pmo.tablero-macro');
        }
        if ($segment === 'gantt' && ! $tabs['gantt']) {
            return redirect()->route('pmo.tablero-macro');
        }
        if ($segment === 'lista' && ! $tabs['lista']) {
            return redirect()->route('pmo.tablero-macro');
        }
        if ($segment === 'kanban' && ! $tabs['kanban']) {
            return redirect()->route('pmo.tablero-macro');
        }
        if ($segment === 'carga' && ! $tabs['carga']) {
            return redirect()->route('pmo.tablero-macro');
        }

        $projects = Project::queryForUser($user)
            ->with('jefeProyecto:id,name')
            ->withCount([
                'tasks as tasks_total',
                'tasks as tasks_abiertas' => fn ($q) => $q->where('status', '!=', Task::STATUS_HECHA),
            ])
            ->orderBy('name')
            ->get();

        $selectedProjectId = null;
        if ($request->filled('project_id')) {
            $pid = (int) $request->query('project_id');
            if ($projects->contains('id', $pid)) {
                $selectedProjectId = $pid;
            }
        }

        $canEditCarteraFull = $user->hasRole(['admin', 'pmo']);

        $jefeOptions = $canEditCarteraFull
            ? User::query()
                ->role('jefe_proyecto')
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        $kpiProject = $selectedProjectId !== null
            ? $projects->firstWhere('id', $selectedProjectId)
            : null;
        $kpi = $kpiProject !== null
            ? PmoKpiData::snapshotForProject($kpiProject)
            : PmoKpiData::snapshot();

        $ganttTaskQuery = Task::query()
            ->with('project:id,name,code')
            ->whereIn('project_id', $projects->pluck('id'));

        if ($selectedProjectId !== null) {
            $ganttTaskQuery->where('project_id', $selectedProjectId);
        }

        $ganttTasks = $ganttTaskQuery
            ->orderBy('project_id')
            ->orderBy('id')
            ->get();

        $ganttProjects = TaskGanttRows::fromTasks(
            $ganttTasks,
            $selectedProjectId === null,
        );

        $mapProject = fn (Project $p): array => [
            'id' => $p->id,
            'name' => $p->name,
            'code' => $p->code,
            'description' => $p->description,
            'status' => $p->status,
            'carta_inicio_at' => $p->carta_inicio_at?->format('Y-m-d'),
            'starts_at' => $p->starts_at?->format('Y-m-d'),
            'ends_at' => $p->ends_at?->format('Y-m-d'),
            'jefe_proyecto' => $p->jefeProyecto !== null
                ? ['id' => $p->jefeProyecto->id, 'name' => $p->jefeProyecto->name]
                : null,
            'acta_constitucion' => $p->acta_constitucion_path !== null && $p->acta_constitucion_path !== ''
                ? [
                    'download_url' => route('pmo.proyectos.acta-constitucion', $p),
                    'original_name' => $p->acta_constitucion_original_name,
                ]
                : null,
            'tasks_abiertas' => (int) $p->tasks_abiertas_count,
            'tasks_total' => (int) $p->tasks_total_count,
        ];

        $selectedProject = $selectedProjectId !== null
            ? $mapProject($projects->firstWhere('id', $selectedProjectId))
            : null;

        $listaTaskGroups = [];
        $listaTasks = [];
        $listaPeopleOptions = [];
        $listaPortfolioMode = false;
        $kanbanBoards = [];
        $kanbanPeopleOptions = [];
        $kanbanPortfolioMode = false;
        if ($segment === 'kanban') {
            $people = ProyectoKanbanPayload::peopleOptions();
            $kanbanPeopleOptions = $people->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'avatar' => $u->avatar,
            ])->values()->all();
            if ($selectedProjectId !== null) {
                $pModel = $projects->firstWhere('id', $selectedProjectId);
                if ($pModel !== null) {
                    $kanbanBoards[] = [
                        'project' => $pModel->only(['id', 'name', 'code']),
                        'groups' => ProyectoKanbanPayload::groupsForProject($pModel),
                    ];
                }
            } elseif ($projects->isNotEmpty()) {
                $kanbanPortfolioMode = true;
                foreach ($projects as $pModel) {
                    $kanbanBoards[] = [
                        'project' => $pModel->only(['id', 'name', 'code']),
                        'groups' => ProyectoKanbanPayload::groupsForProject($pModel),
                    ];
                }
            }
        }

        $portfolioWorkload = PmoPortfolioWorkloadData::empty();
        if ($segment === 'carga') {
            $portfolioWorkload = PmoPortfolioWorkloadData::build($projects, $selectedProjectId);
        }

        if ($segment === 'lista') {
            if ($selectedProjectId !== null) {
                $pModel = $projects->firstWhere('id', $selectedProjectId);
                if ($pModel !== null) {
                    $listaPayload = ProyectoTaskListPayload::forProject($pModel);
                    $listaTaskGroups = $listaPayload['taskGroups'];
                    $listaTasks = $listaPayload['tasks'];
                    $listaPeopleOptions = $listaPayload['peopleOptions'];
                }
            } elseif ($projects->isNotEmpty()) {
                $listaPayload = ProyectoTaskListPayload::forPortfolio($projects);
                $listaTaskGroups = $listaPayload['taskGroups'];
                $listaTasks = $listaPayload['tasks'];
                $listaPeopleOptions = $listaPayload['peopleOptions'];
                $listaPortfolioMode = true;
            }
        }

        return Inertia::render('pmo/TableroMacro', [
            'activeSegment' => $segment,
            'projects' => $projects->map($mapProject),
            'selectedProjectId' => $selectedProjectId,
            'selectedProject' => $selectedProject,
            'statuses' => Project::STATUSES,
            'jefeOptions' => $jefeOptions,
            'ganttProjects' => $ganttProjects,
            'visibleTabSegments' => $tabs,
            'canEditCarteraFull' => $canEditCarteraFull,
            'listaTaskGroups' => $listaTaskGroups,
            'listaTasks' => $listaTasks,
            'listaPeopleOptions' => $listaPeopleOptions,
            'listaPortfolioMode' => $listaPortfolioMode,
            'kanbanBoards' => $kanbanBoards,
            'kanbanPeopleOptions' => $kanbanPeopleOptions,
            'kanbanPortfolioMode' => $kanbanPortfolioMode,
            'portfolioWorkload' => $portfolioWorkload,
            'taskStatuses' => Task::STATUSES,
            ...$kpi,
        ]);
    }
}
