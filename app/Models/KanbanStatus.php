<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class KanbanStatus extends Model
{
    protected $fillable = [
        'project_id',
        'key',
        'label',
        'position',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return Collection<int, self>
     */
    public static function effectiveForProject(Project $project): Collection
    {
        $projectStatuses = self::query()
            ->where('project_id', $project->id)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        if ($projectStatuses->isNotEmpty()) {
            return $projectStatuses;
        }

        return self::query()
            ->whereNull('project_id')
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    public static function makeKey(string $label): string
    {
        return Str::of($label)->lower()->trim()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->value();
    }
}

