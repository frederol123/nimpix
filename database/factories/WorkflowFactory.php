<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workflow>
 */
class WorkflowFactory extends Factory
{
    protected $model = Workflow::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->word(),
            'description' => fake()->sentence(),
            'status' => fake()->randomElement(['draft', 'active', 'completed']),
        ];
    }
}
