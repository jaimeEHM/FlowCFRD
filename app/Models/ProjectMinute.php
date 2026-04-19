<?php

namespace App\Models;

use Database\Factories\ProjectMinuteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMinute extends Model
{
    /** @use HasFactory<ProjectMinuteFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'body',
        'held_at',
        'created_by_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'held_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
