<?php

namespace Database\Seeders;

use App\Models\TaskPriority;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaskPrioritySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $priorities = [
            ['name' => 'Low', 'color' => 'success', 'is_default' => true],
            ['name' => 'Medium', 'color' => 'warning', 'is_default' => true],
            ['name' => 'High', 'color' => 'danger', 'is_default' => true],
        ];

        foreach ($priorities as $priority) {
            TaskPriority::create($priority);
        }
    }
}
