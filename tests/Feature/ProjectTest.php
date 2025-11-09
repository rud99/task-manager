<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_their_projects(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $userProjects = Project::factory(3)->create(['user_id' => $user->id]);
        $otherProjects = Project::factory(2)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
            ->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $userProjects[0]->id);
    }

    public function test_unauthenticated_user_cannot_get_projects(): void
    {
        $response = $this->getJson('/api/projects');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_project(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/projects', [
                'name' => 'Test Project',
                'description' => 'Test description',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'description', 'user_id', 'created_at', 'updated_at'],
            ])
            ->assertJsonPath('data.name', 'Test Project');

        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_create_project_without_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/projects', [
                'description' => 'Test description',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_user_can_view_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $project->id)
            ->assertJsonPath('data.name', $project->name);
    }

    public function test_user_cannot_view_other_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->putJson("/api/projects/{$project->id}", [
                'name' => 'Updated Project',
                'description' => 'Updated description',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Project');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Project',
        ]);
    }

    public function test_user_cannot_update_other_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
            ->putJson("/api/projects/{$project->id}", [
                'name' => 'Updated Project',
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_user_cannot_delete_other_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
        ]);
    }
}
