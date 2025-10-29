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
        // Check if reminder_time column exists and modify it
        if (Schema::hasColumn('tasks', 'reminder_time')) {
            DB::statement('ALTER TABLE tasks MODIFY COLUMN reminder_time DATETIME NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to time type
        if (Schema::hasColumn('tasks', 'reminder_time')) {
            DB::statement('ALTER TABLE tasks MODIFY COLUMN reminder_time TIME NULL');
        }
    }
};
