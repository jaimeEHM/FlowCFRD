<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Carga de tareas abiertas por responsable, apilada por proyecto (cartera) o por estado (proyecto).
 */
final class PmoPortfolioWorkloadData
{
    private static function displayName(string $name): string
    {
        if (Str::contains($name, ' — ')) {
            return (string) strstr($name, ' — ', true);
        }
        if (Str::contains($name, ' - ')) {
            return (string) strstr($name, ' - ', true);
        }

        return $name;
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return array{
     *     mode: string,
     *     people: list<array{id: int, name: string, total: int, projects_count: int, max_daily_load: float, max_parallel_projects: int}>,
     *     stacks: list<array{label: string, sub?: string|null}>,
     *     matrix: list<list<int>>,
     *     heatmap_max: int,
     *     chart: array{labels: list<string>, datasets: list<array{label: string, data: list<int>, backgroundColor: string}>},
     *     summary: array{people_count: int, tasks_open_assigned: int, daily_alerts: int, parallel_alerts: int},
     *     alerts: array{
     *         daily: list<array{user_id: int, name: string, peak_date: string, max_daily_load: float, capacity_per_day: int, over_capacity_days: int, severity: string}>,
     *         parallel: list<array{user_id: int, name: string, peak_date: string, max_parallel_projects: int, projects_at_peak: list<string>, severity: string}>
     *     },
     * }
     */
    public static function build(Collection $projects, ?int $selectedProjectId): array
    {
        if ($projects->isEmpty()) {
            return self::empty();
        }

        $projectIds = $projects->pluck('id');

        $q = Task::query()
            ->whereIn('project_id', $projectIds)
            ->whereNot('status', Task::STATUS_HECHA)
            ->whereNotNull('assignee_id');

        if ($selectedProjectId !== null) {
            $q->where('project_id', $selectedProjectId);
        }

        if ($selectedProjectId === null) {
            $rows = (clone $q)
                ->selectRaw('assignee_id, project_id, count(*) as c')
                ->groupBy('assignee_id', 'project_id')
                ->get();
            $mode = 'portfolio';
        } else {
            $rows = (clone $q)
                ->selectRaw('assignee_id, status, count(*) as c')
                ->groupBy('assignee_id', 'status')
                ->get();
            $mode = 'project';
        }

        if ($rows->isEmpty()) {
            return self::empty();
        }

        $totalsByUser = $rows->groupBy('assignee_id')->map(fn (Collection $g) => (int) $g->sum('c'))->sortDesc();
        $orderedUserIds = $totalsByUser->keys()->take(45)->values()->all();

        // Incluye colaboradores para alertas de paralelismo y carga diaria efectiva.
        $collaboratorRows = DB::table('task_collaborators')
            ->join('tasks', 'tasks.id', '=', 'task_collaborators.task_id')
            ->whereIn('tasks.project_id', $projectIds)
            ->when($selectedProjectId !== null, fn ($query) => $query->where('tasks.project_id', $selectedProjectId))
            ->where('tasks.status', '!=', Task::STATUS_HECHA)
            ->selectRaw('task_collaborators.user_id as user_id, tasks.project_id as project_id, count(*) as c')
            ->groupBy('task_collaborators.user_id', 'tasks.project_id')
            ->get();

        $taskRowsByUserProject = DB::table('tasks')
            ->whereIn('project_id', $projectIds)
            ->where('status', '!=', Task::STATUS_HECHA)
            ->whereNotNull('assignee_id')
            ->when($selectedProjectId !== null, fn ($query) => $query->where('project_id', $selectedProjectId))
            ->selectRaw('assignee_id as user_id, project_id, count(*) as c')
            ->groupBy('assignee_id', 'project_id')
            ->get();

        /** @var array<int, array<int, float>> $loadByUserProject */
        $loadByUserProject = [];
        foreach ($taskRowsByUserProject as $r) {
            $uid = (int) $r->user_id;
            $pid = (int) $r->project_id;
            $loadByUserProject[$uid][$pid] = ($loadByUserProject[$uid][$pid] ?? 0) + (float) $r->c;
        }
        // Peso 0.5 para colaboración para evitar sobreestimar frente a responsable principal.
        foreach ($collaboratorRows as $r) {
            $uid = (int) $r->user_id;
            $pid = (int) $r->project_id;
            $loadByUserProject[$uid][$pid] = ($loadByUserProject[$uid][$pid] ?? 0) + (((float) $r->c) * 0.5);
        }

        if ($loadByUserProject !== []) {
            $orderedUserIds = collect($orderedUserIds)
                ->merge(array_keys($loadByUserProject))
                ->unique()
                ->take(60)
                ->values()
                ->all();
        }

        $userNames = User::query()
            ->whereIn('id', $orderedUserIds)
            ->pluck('name', 'id');

        $people = [];
        $workloadThresholds = WorkflowTransversalSettings::workload();
        $capacityPerDay = max(1, (int) ($workloadThresholds['tasks_per_day'] ?? 3));
        $parallelAlertThreshold = max(1, (int) ($workloadThresholds['parallel_alert_projects'] ?? 2));
        $parallelDangerThreshold = max($parallelAlertThreshold + 1, (int) ($workloadThresholds['parallel_danger_projects'] ?? 3));
        $dailyAlerts = [];
        $parallelAlerts = [];

        foreach ($orderedUserIds as $uid) {
            $projectsCount = (clone $q)
                ->where('assignee_id', $uid)
                ->distinct('project_id')
                ->count('project_id');

            $daily = self::calculateDailyLoadSignals(
                (int) $uid,
                $projects,
                $loadByUserProject[(int) $uid] ?? [],
                $capacityPerDay,
                $parallelAlertThreshold,
                $parallelDangerThreshold,
            );

            $people[] = [
                'id' => (int) $uid,
                'name' => self::displayName((string) ($userNames[$uid] ?? ('#'.$uid))),
                'total' => (int) ($totalsByUser[$uid] ?? 0),
                'projects_count' => (int) $projectsCount,
                'max_daily_load' => round($daily['max_daily_load'], 2),
                'max_parallel_projects' => (int) $daily['max_parallel_projects'],
            ];

            if ($daily['is_daily_alert']) {
                $dailyAlerts[] = [
                    'user_id' => (int) $uid,
                    'name' => self::displayName((string) ($userNames[$uid] ?? ('#'.$uid))),
                    'peak_date' => $daily['peak_date'],
                    'max_daily_load' => round($daily['max_daily_load'], 2),
                    'capacity_per_day' => $capacityPerDay,
                    'over_capacity_days' => $daily['over_capacity_days'],
                    'severity' => $daily['daily_severity'],
                ];
            }

            if ($daily['is_parallel_alert']) {
                $parallelAlerts[] = [
                    'user_id' => (int) $uid,
                    'name' => self::displayName((string) ($userNames[$uid] ?? ('#'.$uid))),
                    'peak_date' => $daily['peak_date_parallel'],
                    'max_parallel_projects' => $daily['max_parallel_projects'],
                    'projects_at_peak' => $daily['projects_at_peak'],
                    'severity' => $daily['parallel_severity'],
                ];
            }
        }

        $stackProjectIds = [];
        $statusOrder = [];

        if ($mode === 'portfolio') {
            $stackProjectIds = $rows->pluck('project_id')->unique()->values()->all();
            usort($stackProjectIds, function (int $a, int $b) use ($projects): int {
                $na = $projects->firstWhere('id', $a)?->name ?? '';
                $nb = $projects->firstWhere('id', $b)?->name ?? '';

                return strcasecmp((string) $na, (string) $nb);
            });
        } else {
            $statusOrder = array_values(array_filter(
                Task::STATUSES,
                fn (string $st) => $rows->where('status', $st)->isNotEmpty(),
            ));
        }

        $stacks = [];
        if ($mode === 'portfolio') {
            foreach ($stackProjectIds as $pid) {
                $p = $projects->firstWhere('id', $pid);
                $stacks[] = [
                    'label' => $p !== null ? $p->name : 'Proyecto #'.$pid,
                    'sub' => $p?->code,
                ];
            }
        } else {
            foreach ($statusOrder as $st) {
                $stacks[] = [
                    'label' => str_replace('_', ' ', $st),
                    'sub' => null,
                ];
            }
        }

        $matrix = [];
        foreach ($orderedUserIds as $uid) {
            $row = [];
            if ($mode === 'portfolio') {
                foreach ($stackProjectIds as $pid) {
                    $row[] = (int) $rows
                        ->where('assignee_id', $uid)
                        ->where('project_id', $pid)
                        ->sum('c');
                }
            } else {
                foreach ($statusOrder as $st) {
                    $row[] = (int) $rows
                        ->where('assignee_id', $uid)
                        ->where('status', $st)
                        ->sum('c');
                }
            }
            $matrix[] = $row;
        }

        $heatmapMax = 1;
        foreach ($matrix as $row) {
            foreach ($row as $v) {
                $heatmapMax = max($heatmapMax, $v);
            }
        }

        $palette = [
            '#003366', '#1e5a8e', '#2d6a9f', '#0f766e', '#7c3aed',
            '#b45309', '#be123c', '#0369a1', '#15803d', '#a16207',
            '#4338ca', '#0e7490', '#c2410c', '#9d174d', '#166534',
        ];

        $datasets = [];
        $stackCount = count($stacks);
        for ($i = 0; $i < $stackCount; $i++) {
            $col = [];
            foreach ($orderedUserIds as $ui => $_uid) {
                $col[] = $matrix[$ui][$i] ?? 0;
            }
            $datasets[] = [
                'label' => $stacks[$i]['label'],
                'data' => $col,
                'backgroundColor' => $palette[$i % count($palette)],
            ];
        }

        $chart = [
            'labels' => array_map(fn (array $p) => $p['name'], $people),
            'datasets' => $datasets,
        ];

        return [
            'mode' => $mode,
            'people' => $people,
            'stacks' => $stacks,
            'matrix' => $matrix,
            'heatmap_max' => $heatmapMax,
            'chart' => $chart,
            'summary' => [
                'people_count' => count($people),
                'tasks_open_assigned' => (int) $rows->sum('c'),
                'daily_alerts' => count($dailyAlerts),
                'parallel_alerts' => count($parallelAlerts),
            ],
            'alerts' => [
                'daily' => collect($dailyAlerts)->sortByDesc('max_daily_load')->values()->all(),
                'parallel' => collect($parallelAlerts)->sortByDesc('max_parallel_projects')->values()->all(),
            ],
        ];
    }

    /**
     * @param  array<int, float>  $projectLoads
     * @return array{
     *   max_daily_load: float,
     *   max_parallel_projects: int,
     *   over_capacity_days: int,
     *   peak_date: string,
     *   peak_date_parallel: string,
     *   projects_at_peak: list<string>,
     *   is_daily_alert: bool,
     *   is_parallel_alert: bool,
     *   daily_severity: string,
     *   parallel_severity: string
     * }
     */
    private static function calculateDailyLoadSignals(
        int $userId,
        Collection $projects,
        array $projectLoads,
        int $capacityPerDay,
        int $parallelAlertThreshold,
        int $parallelDangerThreshold,
    ): array
    {
        $dailyLoad = [];
        $dailyParallel = [];
        $dailyProjects = [];

        foreach ($projectLoads as $projectId => $projectTaskLoad) {
            if ($projectTaskLoad <= 0) {
                continue;
            }
            /** @var Project|null $project */
            $project = $projects->firstWhere('id', (int) $projectId);
            if ($project === null) {
                continue;
            }

            $start = $project->starts_at ?? $project->carta_inicio_at ?? now();
            $end = $project->ends_at ?? ($start instanceof Carbon ? $start->copy()->addDays(14) : now()->addDays(14));

            $startDate = Carbon::parse($start)->startOfDay();
            $endDate = Carbon::parse($end)->endOfDay();
            if ($endDate->lt($startDate)) {
                [$startDate, $endDate] = [$endDate->copy(), $startDate->copy()];
            }

            $days = self::businessDaysInclusive($startDate, $endDate);
            $dailyContribution = $projectTaskLoad / max(1, $days);

            foreach (self::iterateBusinessDays($startDate, $endDate) as $day) {
                $key = $day->toDateString();
                $dailyLoad[$key] = ($dailyLoad[$key] ?? 0.0) + $dailyContribution;
                $dailyParallel[$key] = ($dailyParallel[$key] ?? 0) + 1;
                $dailyProjects[$key][] = $project->name;
            }
        }

        if ($dailyLoad === []) {
            return [
                'max_daily_load' => 0.0,
                'max_parallel_projects' => 0,
                'over_capacity_days' => 0,
                'peak_date' => now()->toDateString(),
                'peak_date_parallel' => now()->toDateString(),
                'projects_at_peak' => [],
                'is_daily_alert' => false,
                'is_parallel_alert' => false,
                'daily_severity' => 'normal',
                'parallel_severity' => 'normal',
            ];
        }

        $maxDailyLoad = max($dailyLoad);
        $peakDate = collect($dailyLoad)->sortDesc()->keys()->first();
        $maxParallel = max($dailyParallel ?: [0]);
        $peakDateParallel = collect($dailyParallel)->sortDesc()->keys()->first();
        $overCapacityDays = collect($dailyLoad)->filter(fn ($v) => $v > $capacityPerDay)->count();

        $dailySeverity = $maxDailyLoad >= ($capacityPerDay * 1.5)
            ? 'peligro'
            : ($maxDailyLoad > $capacityPerDay ? 'alerta' : 'normal');

        $parallelSeverity = $maxParallel >= $parallelDangerThreshold
            ? 'peligro'
            : ($maxParallel >= $parallelAlertThreshold ? 'alerta' : 'normal');

        return [
            'max_daily_load' => (float) $maxDailyLoad,
            'max_parallel_projects' => (int) $maxParallel,
            'over_capacity_days' => (int) $overCapacityDays,
            'peak_date' => (string) $peakDate,
            'peak_date_parallel' => (string) $peakDateParallel,
            'projects_at_peak' => array_values(array_unique($dailyProjects[(string) $peakDateParallel] ?? [])),
            'is_daily_alert' => $maxDailyLoad > $capacityPerDay,
            'is_parallel_alert' => $maxParallel >= $parallelAlertThreshold,
            'daily_severity' => $dailySeverity,
            'parallel_severity' => $parallelSeverity,
        ];
    }

    /**
     * @return list<Carbon>
     */
    private static function iterateBusinessDays(Carbon $start, Carbon $end): array
    {
        $days = [];
        $cursor = $start->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();
        while ($cursor->lte($endDay)) {
            if ($cursor->isWeekday()) {
                $days[] = $cursor->copy();
            }
            $cursor->addDay();
        }

        return $days;
    }

    private static function businessDaysInclusive(Carbon $start, Carbon $end): int
    {
        return max(1, count(self::iterateBusinessDays($start, $end)));
    }

    /**
     * @return array{
     *     mode: string,
     *     people: list<array{id: int, name: string, total: int, projects_count: int, max_daily_load: float, max_parallel_projects: int}>,
     *     stacks: list<array{label: string, sub?: string|null}>,
     *     matrix: list<list<int>>,
     *     heatmap_max: int,
     *     chart: array{labels: list<string>, datasets: list<array{label: string, data: list<int>, backgroundColor: string}>},
     *     summary: array{people_count: int, tasks_open_assigned: int, daily_alerts: int, parallel_alerts: int},
     *     alerts: array{
     *         daily: list<array{user_id: int, name: string, peak_date: string, max_daily_load: float, capacity_per_day: int, over_capacity_days: int, severity: string}>,
     *         parallel: list<array{user_id: int, name: string, peak_date: string, max_parallel_projects: int, projects_at_peak: list<string>, severity: string}>
     *     },
     * }
     */
    public static function empty(): array
    {
        return [
            'mode' => 'empty',
            'people' => [],
            'stacks' => [],
            'matrix' => [],
            'heatmap_max' => 1,
            'chart' => ['labels' => [], 'datasets' => []],
            'summary' => ['people_count' => 0, 'tasks_open_assigned' => 0, 'daily_alerts' => 0, 'parallel_alerts' => 0],
            'alerts' => ['daily' => [], 'parallel' => []],
        ];
    }
}
