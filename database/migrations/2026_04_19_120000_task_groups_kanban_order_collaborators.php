<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 32)->default('#64748b');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('task_group_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('kanban_order')->default(0)->after('backlog_order');
        });

        Schema::create('task_collaborators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'user_id']);
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("
                INSERT INTO task_groups (project_id, name, color, \"position\", created_at, updated_at)
                SELECT id, 'General', '#64748b', 0, NOW(), NOW()
                FROM projects
            ");
            DB::statement('
                UPDATE tasks t
                SET task_group_id = tg.id
                FROM task_groups tg
                WHERE tg.project_id = t.project_id AND tg.name = \'General\'
            ');
        } else {
            $pids = DB::table('projects')->pluck('id');
            foreach ($pids as $pid) {
                $gid = DB::table('task_groups')->insertGetId([
                    'project_id' => $pid,
                    'name' => 'General',
                    'color' => '#64748b',
                    'position' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('tasks')->where('project_id', $pid)->update(['task_group_id' => $gid]);
            }
        }

        $this->backfillKanbanOrder();
    }

    private function backfillKanbanOrder(): void
    {
        $tasks = DB::table('tasks')
            ->orderBy('task_group_id')
            ->orderBy('status')
            ->orderBy('id')
            ->get(['id', 'task_group_id', 'status']);

        $bucket = [];
        foreach ($tasks as $row) {
            $k = $row->task_group_id.'|'.$row->status;
            if (! isset($bucket[$k])) {
                $bucket[$k] = 0;
            }
            DB::table('tasks')->where('id', $row->id)->update(['kanban_order' => $bucket[$k]]);
            $bucket[$k]++;
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['task_group_id']);
            $table->dropColumn(['task_group_id', 'kanban_order']);
        });

        Schema::dropIfExists('task_collaborators');
        Schema::dropIfExists('task_groups');
    }
};
