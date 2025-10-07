<?php

namespace Database\Seeders;

use App\Models\TaskCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaskCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Development', 'icon' => 'bi-code-slash', 'color' => 'primary', 'is_default' => true],
            ['name' => 'Design', 'icon' => 'bi-palette', 'color' => 'info', 'is_default' => true],
            ['name' => 'Testing', 'icon' => 'bi-bug', 'color' => 'warning', 'is_default' => true],
            ['name' => 'Documentation', 'icon' => 'bi-file-text', 'color' => 'success', 'is_default' => true],
            ['name' => 'Meeting', 'icon' => 'bi-people', 'color' => 'dark', 'is_default' => true],
            ['name' => 'General', 'icon' => 'bi-list-task', 'color' => 'secondary', 'is_default' => true],
        ];

        foreach ($categories as $category) {
            TaskCategory::create($category);
        }
    }
}
