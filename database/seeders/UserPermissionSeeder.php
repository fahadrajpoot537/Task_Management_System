<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This assigns permissions directly to users based on their roles.
     */
    public function run(): void
    {
        $allPermissions = Permission::all();
        
        // Get all users with their roles
        $users = User::with('role')->get();
        
        foreach ($users as $user) {
            if (!$user->role) {
                continue;
            }
            
            $roleName = $user->role->name;
            $permissionsToAssign = [];
            
            switch ($roleName) {
                case 'super_admin':
                    // Super admin gets all permissions
                    $permissionsToAssign = $allPermissions->pluck('id')->toArray();
                    break;
                    
                case 'admin':
                    // Admin gets most permissions except user/role/permission management
                    $adminPermissions = Permission::whereIn('name', [
                        // Projects
                        'create_project', 'view_all_projects', 'edit_project', 'delete_project',
                        // Tasks
                        'create_task', 'view_all_tasks', 'edit_task', 'delete_task', 'assign_task',
                        // Task management
                        'manage_task_categories', 'manage_task_priorities', 'manage_task_statuses',
                        // Attendance
                        'manage_attendance', 'view_attendance',
                        // Chat
                        'manage_chat', 'send_message', 'create_channel',
                        // Salary
                        'manage_salary', 'view_salary',
                        // Employment
                        'manage_employment', 'view_employment',
                        // Probation
                        'manage_probation',
                        // Teams
                        'manage_teams',
                        // Logs
                        'view_logs',
                    ])->get();
                    $permissionsToAssign = $adminPermissions->pluck('id')->toArray();
                    break;
                    
                case 'manager':
                    // Manager gets project and task management for their team
                    $managerPermissions = Permission::whereIn('name', [
                        // Projects
                        'create_project', 'view_all_projects', 'edit_project',
                        // Tasks
                        'create_task', 'view_all_tasks', 'edit_task', 'assign_task',
                        // Attendance (view only)
                        'view_attendance',
                        // Chat
                        'send_message', 'create_channel',
                        // Employment (view only)
                        'view_employment',
                    ])->get();
                    $permissionsToAssign = $managerPermissions->pluck('id')->toArray();
                    break;
                    
                case 'employee':
                    // Employee gets basic permissions
                    $employeePermissions = Permission::whereIn('name', [
                        // Projects
                        'create_project',
                        // Tasks
                        'create_task', 'view_all_tasks', 'edit_task',
                        // Chat
                        'send_message',
                        // Attendance (view own)
                        'view_attendance',
                    ])->get();
                    $permissionsToAssign = $employeePermissions->pluck('id')->toArray();
                    break;
            }
            
            // Sync permissions to user (this will replace any existing permissions)
            if (!empty($permissionsToAssign)) {
                $user->permissions()->sync($permissionsToAssign);
            }
        }
    }
}

