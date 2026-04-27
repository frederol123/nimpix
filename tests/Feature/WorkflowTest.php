<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    public function test_can_list_workflows(): void
    {
        Workflow::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/workflows');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_workflow(): void
    {
        $data = [
            'name' => 'New Workflow',
            'description' => 'Test description',
            'status' => 'draft',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/workflows', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Workflow')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('workflows', [
            'name' => 'New Workflow',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_create_workflow_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/workflows', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'status']);
    }

    public function test_can_show_workflow(): void
    {
        $workflow = Workflow::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/workflows/{$workflow->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', $workflow->name);
    }

    public function test_show_returns_404_for_missing_workflow(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/workflows/999');

        $response->assertStatus(404);
    }

    public function test_show_returns_403_for_other_users_workflow(): void
    {
        $workflow = Workflow::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/workflows/{$workflow->id}");

        $response->assertStatus(403);
    }

    public function test_can_update_workflow(): void
    {
        $workflow = Workflow::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $data = ['status' => 'active'];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/workflows/{$workflow->id}", $data);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'active');
    }

    public function test_update_returns_404_for_missing_workflow(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/workflows/999', ['status' => 'active']);

        $response->assertStatus(404);
    }

    public function test_can_delete_workflow(): void
    {
        $workflow = Workflow::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/workflows/{$workflow->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted($workflow);
    }

    public function test_delete_returns_404_for_missing_workflow(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/workflows/999');

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_access_workflows(): void
    {
        $this->getJson('/api/workflows')->assertStatus(401);
        $this->postJson('/api/workflows', [
            'name' => 'Test',
            'status' => 'draft',
        ])->assertStatus(401);
        $this->getJson('/api/workflows/1')->assertStatus(401);
        $this->putJson('/api/workflows/1', ['status' => 'active'])->assertStatus(401);
        $this->deleteJson('/api/workflows/1')->assertStatus(401);
    }

    public function test_force_deleting_workflow_cascades_to_tasks(): void
    {
        $workflow = Workflow::factory()->create(['user_id' => $this->user->id]);
        $task = Task::factory()->create(['workflow_id' => $workflow->id]);

        $workflow->forceDelete();

        $this->assertDatabaseMissing('workflows', ['id' => $workflow->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}
