<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::query()->get();

        foreach ($users as $user) {
            Project::factory(rand(2, 5))->create([
                'user_id' => $user->id,
            ]);
        }
    }
}
