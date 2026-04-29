<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Support\ProyectoTaskListPayload;
use App\Support\TaskGanttRows;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Vistas unificadas por proyecto (misma cartera de datos, distinta presentación).
 */
class ProyectoWorkspaceController extends Controller
{
    public function tabla(Request $request): Response
    {
        [$project, $projects] = $this->resolveProject($request);

        if ($project === null) {
            return Inertia::render('proyecto/Tabla', [
                'project' => null,
                'projects' => $projects,
                'taskGroups' => [],
                'tasks' => [],
                'statuses' => Task::STATUSES,
                'peopleOptions' => [],
            ]);
        }

        $payload = ProyectoTaskListPayload::forProject($project);

        return Inertia::render('proyecto/Tabla', [
            'project' => $project->only(['id', 'name', 'code', 'status']),
            'projects' => $projects,
            'taskGroups' => $payload['taskGroups'],
            'tasks' => $payload['tasks'],
            'statuses' => Task::STATUSES,
            'peopleOptions' => $payload['peopleOptions'],
        ]);
    }

    public function cronograma(Request $request): Response
    {
        [$project, $projects] = $this->resolveProject($request);

        if ($project === null) {
            return Inertia::render('proyecto/Cronograma', [
                'project' => null,
                'projects' => $projects,
                'ganttProjects' => [],
            ]);
        }

        $tasks = Task::query()
            ->where('project_id', $project->id)
            ->with('project:id,name,code')
            ->orderBy('id')
            ->get();

        $ganttProjects = TaskGanttRows::fromTasks($tasks, false);

        return Inertia::render('proyecto/Cronograma', [
            'project' => $project->only(['id', 'name', 'code']),
            'projects' => $projects,
            'ganttProjects' => $ganttProjects,
        ]);
    }

    public function calendario(Request $request): Response
    {
        [$project, $projects] = $this->resolveProject($request);

        $view = (string) $request->input('view', 'month');
        if (! in_array($view, ['day', 'week', 'month'], true)) {
            $view = 'month';
        }
        $anchor = Carbon::parse((string) $request->input('date', now()->toDateString()));
        [$start, $end, $label] = $this->calendarRange($view, $anchor);

        $tasksByDay = [];
        if ($project !== null) {
            $tasks = Task::query()
                ->where('project_id', $project->id)
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
                ->with(['assignee:id,name'])
                ->orderBy('due_date')
                ->get();

            foreach ($tasks as $task) {
                $key = $task->due_date->format('Y-m-d');
                if (! isset($tasksByDay[$key])) {
                    $tasksByDay[$key] = [];
                }
                $tasksByDay[$key][] = [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status,
                    'project_id' => $task->project_id,
                    'assignee' => $task->assignee ? ['name' => $task->assignee->name] : null,
                ];
            }
        }

        return Inertia::render('proyecto/Calendario', [
            'project' => $project?->only(['id', 'name', 'code']),
            'projects' => $projects,
            'calendar' => [
                'view' => $view,
                'date' => $anchor->toDateString(),
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'label' => $label,
                'tasks_by_day' => $tasksByDay,
            ],
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function calendarRange(string $view, Carbon $anchor): array
    {
        if ($view === 'day') {
            $start = $anchor->copy()->startOfDay();
            $end = $anchor->copy()->endOfDay();

            return [$start, $end, $anchor->translatedFormat('d \\d\\e F \\d\\e Y')];
        }
        if ($view === 'week') {
            $start = $anchor->copy()->startOfWeek(Carbon::MONDAY);
            $end = $anchor->copy()->endOfWeek(Carbon::FRIDAY);

            return [$start, $end, $start->translatedFormat('d M').' - '.$end->translatedFormat('d M Y')];
        }

        $start = $anchor->copy()->startOfMonth();
        $end = $anchor->copy()->endOfMonth();

        return [$start, $end, $start->translatedFormat('F Y')];
    }

    /**
     * @return array{0: Project|null, 1: Collection<int, Project>}
     */
    private function resolveProject(Request $request): array
    {
        $user = $request->user();
        $projects = Project::queryForUser($user)->orderBy('name')->get(['id', 'name', 'code']);

        if ($request->filled('project_id')) {
            $project = Project::queryForUser($user)
                ->whereKey((int) $request->input('project_id'))
                ->first();

            return [$project, $projects];
        }

        if ($projects->isEmpty()) {
            return [null, $projects];
        }

        $project = Project::queryForUser($user)->whereKey($projects->first()->id)->first();

        return [$project, $projects];
    }
}
