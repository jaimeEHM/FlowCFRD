<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['project.created', 'task.updated', 'login']),
            'auditable_type' => null,
            'auditable_id' => null,
            'properties' => ['demo' => true],
            'ip_address' => fake()->ipv4(),
            'created_at' => now(),
        ];
    }
}
