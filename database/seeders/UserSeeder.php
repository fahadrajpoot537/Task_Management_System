<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $managerRole = Role::where('name', 'manager')->first();
        $employeeRole = Role::where('name', 'employee')->first();

        // Create Super Admin
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
            'role_id' => $superAdminRole->id,
        ]);

        // Create Manager
        $manager = User::create([
            'name' => 'John Manager',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'role_id' => $managerRole->id,
        ]);

        // Create Employees
        $employee1 = User::create([
            'name' => 'Jane Employee',
            'email' => 'employee1@example.com',
            'password' => Hash::make('password'),
            'role_id' => $employeeRole->id,
            'manager_id' => $manager->id,
        ]);

        $employee2 = User::create([
            'name' => 'Bob Employee',
            'email' => 'employee2@example.com',
            'password' => Hash::make('password'),
            'role_id' => $employeeRole->id,
            'manager_id' => $manager->id,
        ]);

        $employee3 = User::create([
            'name' => 'Alice Employee',
            'email' => 'employee3@example.com',
            'password' => Hash::make('password'),
            'role_id' => $employeeRole->id,
        ]);
    }
}
