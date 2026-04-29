<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\TaskGroup;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('workflow:transversal-group {state : on|off|status} {--no-move : Al desactivar, no mueve tareas a General}')]
#[Description('Activa/desactiva el segmento Línea transversal y, al desactivar, mueve tareas a General.')]
class WorkflowToggleTransversalGroupCommand extends Command
{
    public function handle(): int
    {
        $state = strtolower((string) $this->argument('state'));
        if (! in_array($state, ['on', 'off', 'status'], true)) {
            $this->error('Estado inválido. Usa: on, off o status.');

            return self::INVALID;
        }

        $currentEnabled = (bool) config('workflow.transversal_group.enabled', false);
        $groupName = (string) config('workflow.transversal_group.name', 'Línea transversal');

        if ($state === 'status') {
            $this->line('WORKFLOW_TRANSVERSAL_GROUP_ENABLED: '.($currentEnabled ? 'true' : 'false'));
            $this->line('Nombre segmento transversal: '.$groupName);

            return self::SUCCESS;
        }

        $targetEnabled = $state === 'on';
        $this->updateEnvFlag($targetEnabled);

        if (! $targetEnabled && ! $this->option('no-move')) {
            $moved = $this->moveTransversalTasksToGeneral($groupName);
            $this->info("Tareas movidas desde {$groupName} a General: {$moved}");
        }

        $this->info('Configuración actualizada en .env: WORKFLOW_TRANSVERSAL_GROUP_ENABLED='.(($targetEnabled) ? 'true' : 'false'));
        $this->warn('Recarga configuración (reinicia contenedor o ejecuta php artisan optimize:clear).');

        return self::SUCCESS;
    }

    private function moveTransversalTasksToGeneral(string $groupName): int
    {
        return DB::transaction(function () use ($groupName): int {
            $moved = 0;
            $transversalGroups = TaskGroup::query()
                ->where('name', $groupName)
                ->get();

            foreach ($transversalGroups as $transversal) {
                $general = TaskGroup::ensureGeneral($transversal->project);

                $tasks = Task::query()
                    ->where('task_group_id', $transversal->id)
                    ->orderBy('status')
                    ->orderBy('kanban_order')
                    ->orderBy('id')
                    ->get();

                foreach ($tasks as $task) {
                    $maxOrder = (int) Task::query()
                        ->where('task_group_id', $general->id)
                        ->where('status', $task->status)
                        ->max('kanban_order');

                    $task->task_group_id = $general->id;
                    $task->kanban_order = $maxOrder + 1;
                    $task->save();
                    $moved++;
                }
            }

            return $moved;
        });
    }

    private function updateEnvFlag(bool $enabled): void
    {
        $path = base_path('.env');
        $value = $enabled ? 'true' : 'false';
        $line = "WORKFLOW_TRANSVERSAL_GROUP_ENABLED={$value}";

        $content = file_exists($path) ? (string) file_get_contents($path) : '';

        if ($content === '') {
            file_put_contents($path, $line.PHP_EOL);

            return;
        }

        if (preg_match('/^WORKFLOW_TRANSVERSAL_GROUP_ENABLED=.*/m', $content) === 1) {
            $content = (string) preg_replace('/^WORKFLOW_TRANSVERSAL_GROUP_ENABLED=.*/m', $line, $content);
        } else {
            $content = rtrim($content).PHP_EOL.$line.PHP_EOL;
        }

        file_put_contents($path, $content);
    }
}
