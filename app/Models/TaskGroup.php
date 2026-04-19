<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskGroup extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'color',
        'position',
    ];

    /**
     * Grupo por defecto para un proyecto (p. ej. tras crear proyecto nuevo).
     */
    public static function ensureGeneral(Project $project): self
    {
        return static::query()->firstOrCreate(
            ['project_id' => $project->id, 'name' => 'General'],
            ['color' => '#64748b', 'position' => 0],
        );
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
