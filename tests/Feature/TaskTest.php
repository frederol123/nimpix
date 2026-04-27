<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;
    private Workflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->workflow = Workflow::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_can_list_tasks(): void
    {
        Task::factory()->count(3)->create(['workflow_id' => $this->workflow->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_task(): void
    {
        $data = [
            'workflow_id' => $this->workflow->id,
            'name' => 'New Task',
            'description' => 'Task description',
            'status' => 'pending',
            'position' => 0,
            'due_at' => now()->addDay()->toISOString(),
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/tasks', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Task');

        $this->assertDatabaseHas('tasks', ['name' => 'New Task']);
    }

    public function test_create_task_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/tasks', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workflow_id', 'name', 'status']);
    }

    public function test_create_task_validates_workflow_exists(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/tasks', [
                'workflow_id' => 999,
                'name' => 'Task',
                'status' => 'pending',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workflow_id']);
    }

    public function test_create_task_validates_workflow_belongs_to_user(): void
    {
        $otherWorkflow = Workflow::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/tasks', [
                'workflow_id' => $otherWorkflow->id,
                'name' => 'Task',
                'status' => 'pending',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workflow_id']);
    }

    public function test_can_show_task(): void
    {
        $task = Task::factory()->create(['workflow_id' => $this->workflow->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', $task->name)
            ->assertJsonPath('data.workflow.id', $this->workflow->id);
    }

    public function test_show_returns_404_for_missing_task(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/tasks/999');

        $response->assertStatus(404);
    }

    public function test_show_returns_403_for_other_users_task(): void
    {
        $otherWorkflow = Workflow::factory()->create(['user_id' => $this->otherUser->id]);
        $task = Task::factory()->create(['workflow_id' => $otherWorkflow->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_can_update_task(): void
    {
        $task = Task::factory()->create([
            'workflow_id' => $this->workflow->id,
            'status' => 'pending',
        ]);

        $data = ['status' => 'in_progress'];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/tasks/{$task->id}", $data);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_update_returns_404_for_missing_task(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/tasks/999', ['status' => 'in_progress']);

        $response->assertStatus(404);
    }

    public function test_can_delete_task(): void
    {
        $task = Task::factory()->create(['workflow_id' => $this->workflow->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted($task);
    }

    public function test_delete_returns_404_for_missing_task(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/tasks/999');

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_access_tasks(): void
    {
        $this->getJson('/api/tasks')->assertStatus(401);
        $this->postJson('/api/tasks', [
            'workflow_id' => $this->workflow->id,
            'name' => 'Task',
            'status' => 'pending',
        ])->assertStatus(401);
        $this->getJson('/api/tasks/1')->assertStatus(401);
        $this->putJson('/api/tasks/1', ['status' => 'in_progress'])->assertStatus(401);
        $this->deleteJson('/api/tasks/1')->assertStatus(401);
    }
}
