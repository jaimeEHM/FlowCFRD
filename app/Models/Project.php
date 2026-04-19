<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    public const STATUS_BORRADOR = 'borrador';

    public const STATUS_ACTIVO = 'activo';

    public const STATUS_EN_PAUSA = 'en_pausa';

    public const STATUS_CERRADO = 'cerrado';

    /**
     * @var list<string>
     */
    public const STATUSES = [
        self::STATUS_BORRADOR,
        self::STATUS_ACTIVO,
        self::STATUS_EN_PAUSA,
        self::STATUS_CERRADO,
    ];

    protected $fillable = [
        'name',
        'code',
        'description',
        'carta_inicio_at',
        'starts_at',
        'ends_at',
        'status',
        'jefe_proyecto_id',
        'created_by_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'carta_inicio_at' => 'date',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function jefeProyecto(): BelongsTo
    {
        return $this->belongsTo(User::class, 'jefe_proyecto_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function minutes(): HasMany
    {
        return $this->hasMany(ProjectMinute::class);
    }

    /**
     * Proyectos visibles según rol: PMO/coordinación/admin ven cartera; jefe de proyecto solo los suyos.
     */
    public static function queryForUser(User $user): Builder
    {
        $q = static::query();

        if ($user->hasRole(['admin', 'pmo', 'coordinador'])) {
            return $q;
        }

        if ($user->hasRole('jefe_proyecto')) {
            return $q->where('jefe_proyecto_id', $user->id);
        }

        return $q->whereRaw('0 = 1');
    }

    public static function userMayAccess(User $user, self $project): bool
    {
        if ($user->hasRole(['admin', 'pmo', 'coordinador'])) {
            return true;
        }

        if ($user->hasRole('jefe_proyecto')) {
            return (int) $project->jefe_proyecto_id === (int) $user->id;
        }

        return false;
    }
}
