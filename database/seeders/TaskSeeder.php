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
                'nature_of_task' => 'daily',
                'is_recurring' => false,
                'is_recurring_active' => false,
            ],
            [
                'title' => 'Implement Authentication',
                'description' => 'Set up user login and registration system',
                'status_id' => $statuses->where('name', 'In Progress')->first()->id,
                'priority_id' => $priorities->where('name', 'High')->first()->id,
                'category_id' => $categories->where('name', 'Development')->first()->id,
                'estimated_hours' => 12,
                'due_date' => now()->addDays(5),
                'nature_of_task' => 'daily',
                'is_recurring' => false,
                'is_recurring_active' => false,
            ],
            [
                'title' => 'Write API Documentation',
                'description' => 'Document all API endpoints and their usage',
                'status_id' => $statuses->where('name', 'Pending')->first()->id,
                'priority_id' => $priorities->where('name', 'Medium')->first()->id,
                'category_id' => $categories->where('name', 'Documentation')->first()->id,
                'estimated_hours' => 6,
                'due_date' => now()->addDays(7),
                'nature_of_task' => 'daily',
                'is_recurring' => false,
                'is_recurring_active' => false,
            ],
            [
                'title' => 'Daily Standup Meeting',
                'description' => 'Attend daily standup meeting with the team',
                'status_id' => $statuses->where('name', 'Complete')->first()->id,
                'priority_id' => $priorities->where('name', 'High')->first()->id,
                'category_id' => $categories->where('name', 'Meeting')->first()->id,
                'estimated_hours' => 1,
                'due_date' => now()->subDays(1),
                'completed_at' => now()->subDays(1),
                'nature_of_task' => 'recurring',
                'is_recurring' => true,
                'is_recurring_active' => true,
            ],
            [
                'title' => 'Code Review',
                'description' => 'Review pull requests and provide feedback',
                'status_id' => $statuses->where('name', 'In Progress')->first()->id,
                'priority_id' => $priorities->where('name', 'Medium')->first()->id,
                'category_id' => $categories->where('name', 'Development')->first()->id,
                'estimated_hours' => 2,
                'due_date' => now()->addDays(1),
                'nature_of_task' => 'recurring',
                'is_recurring' => true,
                'is_recurring_active' => true,
            ],
            [
                'title' => 'Database Backup',
                'description' => 'Perform daily database backup',
                'status_id' => $statuses->where('name', 'Complete')->first()->id,
                'priority_id' => $priorities->where('name', 'High')->first()->id,
                'category_id' => $categories->where('name', 'Maintenance')->first()->id,
                'estimated_hours' => 1,
                'due_date' => now()->subDays(2),
                'completed_at' => now()->subDays(2),
                'nature_of_task' => 'recurring',
                'is_recurring' => true,
                'is_recurring_active' => true,
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
                'nature_of_task' => $taskData['nature_of_task'],
                'is_recurring' => $taskData['is_recurring'],
                'is_recurring_active' => $taskData['is_recurring_active'],
            ]);
        }

        $this->command->info('Sample tasks created successfully!');
    }
}