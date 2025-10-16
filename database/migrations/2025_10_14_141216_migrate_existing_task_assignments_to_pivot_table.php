<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing task assignments to the new pivot table
        DB::statement("
            INSERT INTO task_assignments (task_id, user_id, assigned_by_user_id, assigned_at, created_at, updated_at)
            SELECT 
                id as task_id,
                assigned_to_user_id as user_id,
                assigned_by_user_id,
                created_at as assigned_at,
                created_at,
                updated_at
            FROM tasks 
            WHERE assigned_to_user_id IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear the task_assignments table
        DB::table('task_assignments')->truncate();
    }
};