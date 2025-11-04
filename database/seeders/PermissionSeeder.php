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
            // Project permissions
            ['name' => 'create_project', 'display_name' => 'Create Project'],
            ['name' => 'view_all_projects', 'display_name' => 'View All Projects'],
            ['name' => 'edit_project', 'display_name' => 'Edit Project'],
            ['name' => 'delete_project', 'display_name' => 'Delete Project'],
            
            // Task permissions
            ['name' => 'create_task', 'display_name' => 'Create Task'],
            ['name' => 'view_all_tasks', 'display_name' => 'View All Tasks'],
            ['name' => 'edit_task', 'display_name' => 'Edit Task'],
            ['name' => 'delete_task', 'display_name' => 'Delete Task'],
            ['name' => 'assign_task', 'display_name' => 'Assign Task'],
            
            // User & Role management permissions
            ['name' => 'manage_users', 'display_name' => 'Manage Users'],
            ['name' => 'manage_roles', 'display_name' => 'Manage Roles'],
            ['name' => 'manage_permissions', 'display_name' => 'Manage Permissions'],
            
            // Team permissions
            ['name' => 'manage_teams', 'display_name' => 'Manage Teams'],
            
            // Attendance permissions
            ['name' => 'manage_attendance', 'display_name' => 'Manage Attendance'],
            ['name' => 'view_attendance', 'display_name' => 'View Attendance'],
            
            // Chat permissions
            ['name' => 'manage_chat', 'display_name' => 'Manage Chat'],
            ['name' => 'send_message', 'display_name' => 'Send Message'],
            ['name' => 'create_channel', 'display_name' => 'Create Channel'],
            
            // Salary permissions
            ['name' => 'manage_salary', 'display_name' => 'Manage Salary'],
            ['name' => 'view_salary', 'display_name' => 'View Salary'],
            
            // Employment permissions
            ['name' => 'manage_employment', 'display_name' => 'Manage Employment'],
            ['name' => 'view_employment', 'display_name' => 'View Employment'],
            
            // Probation permissions
            ['name' => 'manage_probation', 'display_name' => 'Manage Probation'],
            
            // Settings permissions
            ['name' => 'manage_settings', 'display_name' => 'Manage Settings'],
            
            // Task management permissions
            ['name' => 'manage_task_categories', 'display_name' => 'Manage Task Categories'],
            ['name' => 'manage_task_priorities', 'display_name' => 'Manage Task Priorities'],
            ['name' => 'manage_task_statuses', 'display_name' => 'Manage Task Statuses'],
            
            // Zkteco permissions
            ['name' => 'manage_zkteco', 'display_name' => 'Manage Zkteco'],
            ['name' => 'sync_zkteco', 'display_name' => 'Sync Zkteco'],
            
            // Logs permissions
            ['name' => 'view_logs', 'display_name' => 'View Logs'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], $permission);
        }
    }
}
