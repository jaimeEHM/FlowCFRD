<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'code' => strtoupper(fake()->unique()->bothify('PRJ-####')),
            'description' => fake()->optional(0.7)->paragraph(),
            'carta_inicio_at' => fake()->optional(0.8)->dateTimeBetween('-3 months', 'now'),
            'starts_at' => fake()->optional(0.9)->dateTimeBetween('-2 months', '+1 month'),
            'ends_at' => fake()->optional(0.8)->dateTimeBetween('+1 month', '+1 year'),
            'status' => fake()->randomElement(Project::STATUSES),
            'jefe_proyecto_id' => User::factory(),
            'created_by_id' => User::factory(),
        ];
    }
}
