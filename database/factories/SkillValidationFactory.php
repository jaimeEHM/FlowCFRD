<?php

namespace Database\Factories;

use App\Models\Skill;
use App\Models\SkillValidation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SkillValidation>
 */
class SkillValidationFactory extends Factory
{
    protected $model = SkillValidation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'skill_id' => Skill::factory(),
            'subject_user_id' => User::factory(),
            'validator_user_id' => User::factory(),
            'status' => fake()->randomElement(['pendiente', 'aprobada', 'rechazada']),
            'comment' => fake()->optional(0.4)->sentence(),
        ];
    }
}
