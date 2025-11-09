<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::query()->get();

        foreach ($projects as $project) {
            Task::factory(rand(3, 10))->create([
                'project_id' => $project->id,
            ]);
        }
    }
}
