<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    public const STATUS_BACKLOG = 'backlog';

    public const STATUS_PENDIENTE = 'pendiente';

    public const STATUS_EN_CURSO = 'en_curso';

    public const STATUS_REVISION = 'revision';

    public const STATUS_HECHA = 'hecha';

    /**
     * @var list<string>
     */
    public const STATUSES = [
        self::STATUS_BACKLOG,
        self::STATUS_PENDIENTE,
        self::STATUS_EN_CURSO,
        self::STATUS_REVISION,
        self::STATUS_HECHA,
    ];

    public const VALIDATION_PENDIENTE = 'pendiente';

    public const VALIDATION_APROBADA = 'aprobada';

    public const VALIDATION_RECHAZADA = 'rechazada';

    protected $fillable = [
        'project_id',
        'task_group_id',
        'title',
        'description',
        'status',
        'is_urgent',
        'backlog_order',
        'kanban_order',
        'assignee_id',
        'due_date',
        'created_by_id',
        'validation_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_urgent' => 'boolean',
            'due_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function taskGroup(): BelongsTo
    {
        return $this->belongsTo(TaskGroup::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Colaboradores adicionales (el responsable principal sigue siendo assignee_id).
     *
     * @return BelongsToMany<User, self>
     */
    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_collaborators')
            ->withTimestamps()
            ->select('users.*');
    }
}
