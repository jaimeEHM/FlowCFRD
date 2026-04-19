<?php

namespace App\Support;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Convierte tareas en filas para frappe-gantt (inicio/fin derivados de due_date o created_at).
 */
final class TaskGanttRows
{
    /**
     * @return list<array{
     *     id: int,
     *     name: string,
     *     starts_at: string,
     *     ends_at: string,
     *     status: string,
     *     project_id: int,
     *     project_name: string,
     *     project_code: string|null,
     *     task_title: string
     * }>
     */
    public static function fromTasks(Collection $tasks, bool $withProjectPrefix): array
    {
        return $tasks
            ->map(fn (Task $task) => self::row($task, $withProjectPrefix))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     starts_at: string,
     *     ends_at: string,
     *     status: string,
     *     project_id: int,
     *     project_name: string,
     *     project_code: string|null,
     *     task_title: string
     * }|null
     */
    public static function row(Task $task, bool $withProjectPrefix): ?array
    {
        $project = $task->relationLoaded('project') ? $task->project : $task->project()->first(['id', 'name', 'code']);
        if ($project === null) {
            return null;
        }

        $start = $task->due_date !== null
            ? $task->due_date->copy()->startOfDay()
            : Carbon::parse($task->created_at)->startOfDay();

        $end = $task->due_date !== null
            ? $task->due_date->copy()->startOfDay()->addDay()
            : $start->copy()->addDays(7);

        if ($end->lte($start)) {
            $end = $start->copy()->addDay();
        }

        $taskTitle = $task->title;
        $name = $withProjectPrefix
            ? $project->name.' — '.$taskTitle
            : $taskTitle;

        return [
            'id' => $task->id,
            'name' => $name,
            'starts_at' => $start->format('Y-m-d'),
            'ends_at' => $end->format('Y-m-d'),
            'status' => $task->status,
            'project_id' => (int) $task->project_id,
            'project_name' => $project->name,
            'project_code' => $project->code,
            'task_title' => $taskTitle,
        ];
    }
}
