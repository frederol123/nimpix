<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'workflow_id' => Workflow::factory(),
            'name' => fake()->unique()->word(),
            'description' => fake()->sentence(),
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed']),
            'position' => fake()->numberBetween(0, 100),
            'due_at' => fake()->optional()->dateTimeBetween('now', '+30 days'),
        ];
    }
}
