<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use App\Models\TaskStatus;
use App\Models\TaskPriority;
use App\Models\TaskCategory;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first project
        $project = Project::first();
        if (!$project) {
            $this->command->info('No projects found. Please run ProjectSeeder first.');
            return;
        }

        // Get users
        $users = User::take(3)->get();
        if ($users->count() < 2) {
            $this->command->info('Not enough users found. Please run UserSeeder first.');
            return;
        }

        // Get statuses, priorities, and categories
        $statuses = TaskStatus::all();
        $priorities = TaskPriority::all();
        $categories = TaskCategory::all();

        if ($statuses->isEmpty() || $priorities->isEmpty() || $categories->isEmpty()) {
            $this->command->info('Please run TaskStatusSeeder, TaskPrioritySeeder, and TaskCategorySeeder first.');
            return;
        }

        // Create sample tasks
        $tasks = [
            [
                'title' => 'Design User Interface',
                'description' => 'Create wireframes and mockups for the main dashboard',
                'status_id' => $statuses->where('name', 'Pending')->first()->id,
                'priority_id' => $priorities->where('name', 'High')->first()->id,
                'category_id' => $categories->where('name', 'Design')->first()->id,
                'estimated_hours' => 8,
                'due_date' => now()->addDays(3),
            ],
            [
                'title' => 'Implement Authentication',
                'description' => 'Set up user login and registration system',
                'status_id' => $statuses->where('name', 'In Progress')->first()->id,
                'priority_id' => $priorities->where('name', 'High')->first()->id,
                'category_id' => $categories->where('name', 'Development')->first()->id,
                'estimated_hours' => 12,
                'due_date' => now()->addDays(5),
            ],
            [
                'title' => 'Write API Documentation',
                'description' => 'Document all API endpoints and their usage',
                'status_id' => $statuses->where('name', 'Pending')->first()->id,
                'priority_id' => $priorities->where('name', 'Medium')->first()->id,
                'category_id' => $categories->where('name', 'Documentation')->first()->id,
                'estimated_hours' => 6,
                'due_date' => now()->addDays(7),
            ],
            [
                'title' => 'Database Optimization',
                'description' => 'Optimize database queries and add indexes',
                'status_id' => $statuses->where('name', 'Submit for Approval')->first()->id,
                'priority_id' => $priorities->where('name', 'Medium')->first()->id,
                'category_id' => $categories->where('name', 'Development')->first()->id,
                'estimated_hours' => 10,
                'due_date' => now()->addDays(2),
            ],
            [
                'title' => 'User Testing',
                'description' => 'Conduct user testing sessions and gather feedback',
                'status_id' => $statuses->where('name', 'Complete')->first()->id,
                'priority_id' => $priorities->where('name', 'Low')->first()->id,
                'category_id' => $categories->where('name', 'Testing')->first()->id,
                'estimated_hours' => 4,
                'due_date' => now()->subDays(1),
                'completed_at' => now()->subDays(1),
            ],
        ];

        foreach ($tasks as $taskData) {
            Task::create([
                'project_id' => $project->id,
                'title' => $taskData['title'],
                'description' => $taskData['description'],
                'status_id' => $taskData['status_id'],
                'priority_id' => $taskData['priority_id'],
                'category_id' => $taskData['category_id'],
                'estimated_hours' => $taskData['estimated_hours'],
                'due_date' => $taskData['due_date'],
                'assigned_to_user_id' => $users->random()->id,
                'assigned_by_user_id' => $users->first()->id,
                'completed_at' => $taskData['completed_at'] ?? null,
            ]);
        }

        $this->command->info('Sample tasks created successfully!');
    }
}