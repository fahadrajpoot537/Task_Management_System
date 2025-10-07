<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $managerRole = Role::where('name', 'manager')->first();
        $employeeRole = Role::where('name', 'employee')->first();

        $allPermissions = Permission::all();

        // Super Admin gets all permissions
        $superAdminRole->permissions()->attach($allPermissions->pluck('id'));

        // Admin gets most permissions except user management
        $adminPermissions = Permission::whereIn('name', [
            'create_project', 'view_all_projects', 'edit_project', 'delete_project',
            'create_task', 'view_all_tasks', 'edit_task', 'delete_task', 'assign_task',
            'view_logs'
        ])->get();
        $adminRole->permissions()->attach($adminPermissions->pluck('id'));

        // Manager gets project and task management for their team
        $managerPermissions = Permission::whereIn('name', [
            'create_project', 'view_all_projects', 'edit_project',
            'create_task', 'view_all_tasks', 'edit_task', 'assign_task'
        ])->get();
        $managerRole->permissions()->attach($managerPermissions->pluck('id'));

        // Employee gets basic permissions
        $employeePermissions = Permission::whereIn('name', [
            'create_project', 'create_task', 'view_all_tasks', 'edit_task'
        ])->get();
        $employeeRole->permissions()->attach($employeePermissions->pluck('id'));
    }
}
