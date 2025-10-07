<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'super_admin',
                'description' => 'Full system access with ability to manage all roles and permissions',
                'hierarchy_level' => 1,
                'is_system_role' => true,
                'color' => 'danger',
            ],
            [
                'name' => 'admin',
                'description' => 'Administrative access with ability to manage users and create custom roles',
                'hierarchy_level' => 2,
                'is_system_role' => true,
                'color' => 'warning',
            ],
            [
                'name' => 'manager',
                'description' => 'Team management role with ability to manage team members and projects',
                'hierarchy_level' => 3,
                'is_system_role' => true,
                'color' => 'info',
            ],
            [
                'name' => 'employee',
                'description' => 'Basic user role with access to assigned tasks and projects',
                'hierarchy_level' => 4,
                'is_system_role' => true,
                'color' => 'success',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
