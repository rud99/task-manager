<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use App\TaskStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(TaskStatus::cases())->value,
            'due_date' => fake()->optional(0.7)->dateTimeBetween('now', '+30 days'),
            'assignee_id' => fake()->optional(0.8)->randomElement(User::query()->pluck('id')->toArray()),
            'attachment' => null,
        ];
    }
}
