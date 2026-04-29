<?php

namespace App\Support;

use App\Models\WorkflowSetting;

final class WorkflowTransversalSettings
{
    private const KEY_WORKLOAD = 'workload_thresholds';

    /**
     * @return array{tasks_per_day: int, alert_days: int, danger_days: int, overload_days: int}
     */
    public static function workload(): array
    {
        $defaults = [
            'tasks_per_day' => 3,
            'alert_days' => 5,
            'danger_days' => 8,
            'overload_days' => 12,
        ];

        $row = WorkflowSetting::query()->where('key', self::KEY_WORKLOAD)->first();
        if ($row === null || ! is_array($row->value)) {
            return $defaults;
        }

        $value = $row->value;
        $tasksPerDay = max(1, (int) ($value['tasks_per_day'] ?? $defaults['tasks_per_day']));
        $alertDays = max(1, (int) ($value['alert_days'] ?? $defaults['alert_days']));
        $dangerDays = max($alertDays + 1, (int) ($value['danger_days'] ?? $defaults['danger_days']));
        $overloadDays = max($dangerDays + 1, (int) ($value['overload_days'] ?? $defaults['overload_days']));

        return [
            'tasks_per_day' => $tasksPerDay,
            'alert_days' => $alertDays,
            'danger_days' => $dangerDays,
            'overload_days' => $overloadDays,
        ];
    }

    /**
     * @param  array{tasks_per_day: int, alert_days: int, danger_days: int, overload_days: int}  $data
     */
    public static function saveWorkload(array $data): void
    {
        WorkflowSetting::query()->updateOrCreate(
            ['key' => self::KEY_WORKLOAD],
            ['value' => $data],
        );
    }
}

