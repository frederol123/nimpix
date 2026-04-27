<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $workflows = Workflow::factory()
            ->count(3)
            ->for($user)
            ->sequence(
                ['name' => 'Project Alpha', 'status' => 'active'],
                ['name' => 'Project Beta', 'status' => 'draft'],
                ['name' => 'Project Gamma', 'status' => 'completed'],
            )
            ->create();

        foreach ($workflows as $workflow) {
            Task::factory()
                ->count(5)
                ->for($workflow)
                ->sequence(fn ($sequence) => [
                    'position' => $sequence->index,
                    'status' => fake()->randomElement(['pending', 'in_progress', 'completed']),
                ])
                ->create();
        }
    }
}

