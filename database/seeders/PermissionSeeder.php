<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'create_project'],
            ['name' => 'view_all_projects'],
            ['name' => 'edit_project'],
            ['name' => 'delete_project'],
            ['name' => 'create_task'],
            ['name' => 'view_all_tasks'],
            ['name' => 'edit_task'],
            ['name' => 'delete_task'],
            ['name' => 'assign_task'],
            ['name' => 'manage_users'],
            ['name' => 'manage_roles'],
            ['name' => 'manage_permissions'],
            ['name' => 'view_logs'],
            ['name' => 'manage_teams'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}
