<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional(0.5)->paragraph(),
            'status' => fake()->randomElement(Task::STATUSES),
            'is_urgent' => fake()->boolean(15),
            'backlog_order' => fake()->numberBetween(0, 100),
            'assignee_id' => fake()->boolean(70) ? User::factory() : null,
            'due_date' => fake()->optional(0.6)->dateTimeBetween('now', '+3 months'),
            'created_by_id' => User::factory(),
            'validation_status' => null,
        ];
    }
}
