<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_tasks_for_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $tasks = Task::factory(3)->create(['project_id' => $project->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/tasks");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_cannot_get_tasks_for_other_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/tasks");

        $response->assertStatus(403);
    }

    public function test_user_can_filter_tasks_by_status(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Planned,
        ]);
        Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::InProgress,
        ]);
        Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Done,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/tasks?status=in_progress");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'in_progress');
    }

    public function test_user_can_filter_tasks_by_assignee(): void
    {
        $user = User::factory()->create();
        $assignee1 = User::factory()->create();
        $assignee2 = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Task::factory()->create([
            'project_id' => $project->id,
            'assignee_id' => $assignee1->id,
        ]);
        Task::factory(2)->create([
            'project_id' => $project->id,
            'assignee_id' => $assignee2->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/tasks?assignee_id={$assignee2->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_filter_tasks_by_exact_due_date(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $targetDate = now()->addDays(5)->format('Y-m-d');

        Task::factory()->create([
            'project_id' => $project->id,
            'due_date' => $targetDate,
        ]);
        Task::factory()->create([
            'project_id' => $project->id,
            'due_date' => now()->addDays(10)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/tasks?due_date={$targetDate}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.due_date', $targetDate);
    }

    public function test_user_can_filter_tasks_by_due_date_range(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Task::factory()->create([
            'project_id' => $project->id,
            'due_date' => now()->addDays(5)->format('Y-m-d'),
        ]);
        Task::factory()->create([
            'project_id' => $project->id,
            'due_date' => now()->addDays(10)->format('Y-m-d'),
        ]);
        Task::factory()->create([
            'project_id' => $project->id,
            'due_date' => now()->addDays(15)->format('Y-m-d'),
        ]);
        Task::factory()->create([
            'project_id' => $project->id,
            'due_date' => now()->addDays(20)->format('Y-m-d'),
        ]);

        $dateFrom = now()->addDays(8)->format('Y-m-d');
        $dateTo = now()->addDays(16)->format('Y-m-d');

        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/tasks?due_date_from={$dateFrom}&due_date_to={$dateTo}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_create_task_in_their_project(): void
    {
        $user = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'Test Task',
                'description' => 'Test description',
                'status' => 'planned',
                'due_date' => now()->addDays(7)->format('Y-m-d'),
                'assignee_id' => $assignee->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Test Task')
            ->assertJsonPath('data.status', 'planned');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'project_id' => $project->id,
        ]);
    }

    public function test_user_cannot_create_task_in_other_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
            ->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'Test Task',
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_create_task_with_attachments(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($user)
            ->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'Test Task',
                'attachments' => [$file],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.attachments.0.file_name', 'document.pdf');

        $task = Task::query()->where('title', 'Test Task')->first();
        $this->assertCount(1, $task->getMedia('attachments'));
    }

    public function test_user_can_view_task_in_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonPath('data.title', $task->title);
    }

    public function test_user_cannot_view_task_in_other_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $otherUser->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_task_in_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Planned,
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/tasks/{$task->id}", [
                'title' => 'Updated Task',
                'status' => 'in_progress',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Task')
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Task',
            'status' => 'in_progress',
        ]);
    }

    public function test_user_cannot_update_task_in_other_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $otherUser->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($user)
            ->putJson("/api/tasks/{$task->id}", [
                'title' => 'Updated Task',
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_update_task_attachments(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);

        $oldFile = UploadedFile::fake()->create('old-document.pdf', 100);
        $task->addMedia($oldFile)->toMediaCollection('attachments');

        $newFile = UploadedFile::fake()->create('new-document.pdf', 100);

        $response = $this->actingAs($user)
            ->putJson("/api/tasks/{$task->id}", [
                'title' => $task->title,
                'attachments' => [$newFile],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.attachments.0.file_name', 'new-document.pdf');

        $task->refresh();
        $this->assertCount(1, $task->getMedia('attachments'));
        $this->assertEquals('new-document.pdf', $task->getFirstMedia('attachments')->file_name);
    }

    public function test_user_can_delete_task_in_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_user_cannot_delete_task_in_other_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $otherUser->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_deleting_task_removes_attachments(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);

        $file = UploadedFile::fake()->create('file.pdf', 100);
        $task->addMedia($file)->toMediaCollection('attachments');

        $mediaId = $task->getFirstMedia('attachments')->id;

        $response = $this->actingAs($user)
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('media', [
            'id' => $mediaId,
        ]);
    }

    public function test_user_cannot_create_task_with_invalid_status(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'Test Task',
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_user_cannot_create_task_with_past_due_date(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'Test Task',
                'due_date' => now()->subDays(1)->format('Y-m-d'),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    }

    public function test_notification_sent_when_task_created_with_assignee(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'Test Task',
                'description' => 'Test description',
                'status' => 'planned',
                'assignee_id' => $assignee->id,
            ]);

        $response->assertStatus(201);

        Notification::assertSentTo(
            [$assignee],
            TaskCreatedNotification::class,
            function (TaskCreatedNotification $notification) use ($assignee): bool {
                return $notification->task->assignee_id === $assignee->id;
            }
        );
    }

    public function test_notification_not_sent_when_task_created_without_assignee(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'Test Task',
                'description' => 'Test description',
                'status' => 'planned',
            ]);

        $response->assertStatus(201);

        Notification::assertNothingSent();
    }
}
