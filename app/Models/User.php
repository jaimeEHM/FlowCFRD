<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'cargo',
    'email',
    'password',
    'google_id',
    'avatar',
    'avatar_position_x',
    'avatar_position_y',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * @var list<string>
     */
    protected $appends = [
        'role_slugs',
    ];

    /**
     * Slugs de roles Spatie expuestos al front (Inertia).
     *
     * @return array<int, string>
     */
    public function getRoleSlugsAttribute(): array
    {
        return $this->getRoleNames()->values()->all();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'avatar_position_x' => 'integer',
            'avatar_position_y' => 'integer',
        ];
    }

    public function projectsAsJefe(): HasMany
    {
        return $this->hasMany(Project::class, 'jefe_proyecto_id');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    /**
     * Tareas donde participa como colaborador adicional (no responsable principal).
     *
     * @return BelongsToMany<Task, self>
     */
    public function tasksAsCollaborator(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_collaborators')->withTimestamps();
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)->withPivot('level');
    }

    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class)->withTimestamps();
    }

    public function coordinatedAreas(): HasMany
    {
        return $this->hasMany(Area::class, 'coordinator_user_id');
    }

    /**
     * Busca usuario por correo o por el mismo local-part en dominios CFRD/UdeC configurados.
     */
    public static function findForCfrdEmail(string $email): ?self
    {
        $email = strtolower(trim($email));
        if ($email === '' || ! str_contains($email, '@')) {
            return null;
        }

        $local = strstr($email, '@', true);
        if ($local === false || $local === '') {
            return null;
        }

        /** @var list<string> $domains */
        $domains = config('workflow.cfrd_email_domains', []);
        if ($domains === []) {
            $domains = [(string) config('workflow.cfrd_email_domain', 'cfrd.cl')];
        }

        $candidates = collect($domains)
            ->map(fn (string $d) => $local.'@'.$d)
            ->unique()
            ->values()
            ->all();

        return static::query()->whereIn('email', $candidates)->first();
    }
}
