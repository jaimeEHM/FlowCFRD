<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectMinute;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectMinute>
 */
class ProjectMinuteFactory extends Factory
{
    protected $model = ProjectMinute::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => 'Reunión '.fake()->date('Y-m-d'),
            'body' => fake()->paragraphs(3, true),
            'held_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'created_by_id' => User::factory(),
        ];
    }
}
