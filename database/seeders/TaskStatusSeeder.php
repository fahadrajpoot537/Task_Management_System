<?php

namespace Database\Seeders;

use App\Models\TaskStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaskStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'Pending', 'color' => 'warning', 'is_default' => true],
            ['name' => 'In Progress', 'color' => 'primary', 'is_default' => true],
            ['name' => 'Submit for Approval', 'color' => 'info', 'is_default' => true],
            ['name' => 'Complete', 'color' => 'success', 'is_default' => true],
        ];

        foreach ($statuses as $status) {
            TaskStatus::create($status);
        }
    }
}
