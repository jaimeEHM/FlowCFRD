<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Carga de tareas abiertas por responsable, apilada por proyecto (cartera) o por estado (proyecto).
 */
final class PmoPortfolioWorkloadData
{
    /**
     * @param  Collection<int, Project>  $projects
     * @return array{
     *     mode: string,
     *     people: list<array{id: int, name: string, total: int}>,
     *     stacks: list<array{label: string, sub?: string|null}>,
     *     matrix: list<list<int>>,
     *     heatmap_max: int,
     *     chart: array{labels: list<string>, datasets: list<array{label: string, data: list<int>, backgroundColor: string}>},
     *     summary: array{people_count: int, tasks_open_assigned: int},
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

        $userNames = User::query()
            ->whereIn('id', $orderedUserIds)
            ->pluck('name', 'id');

        $people = [];
        foreach ($orderedUserIds as $uid) {
            $people[] = [
                'id' => (int) $uid,
                'name' => (string) ($userNames[$uid] ?? ('#'.$uid)),
                'total' => (int) ($totalsByUser[$uid] ?? 0),
            ];
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
            ],
        ];
    }

    /**
     * @return array{
     *     mode: string,
     *     people: list<array{id: int, name: string, total: int}>,
     *     stacks: list<array{label: string, sub?: string|null}>,
     *     matrix: list<list<int>>,
     *     heatmap_max: int,
     *     chart: array{labels: list<string>, datasets: list<array{label: string, data: list<int>, backgroundColor: string}>},
     *     summary: array{people_count: int, tasks_open_assigned: int},
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
            'summary' => ['people_count' => 0, 'tasks_open_assigned' => 0],
        ];
    }
}
