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
        // First, modify the column to allow any string temporarily
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('nature_of_task')->change();
        });

        // Update existing data to convert 'recurring' to 'weekly' for existing recurring tasks
        // This is a safe default since most recurring tasks are weekly
        DB::statement("UPDATE tasks SET nature_of_task = 'weekly' WHERE nature_of_task = 'recurring'");

        // Now set the new enum constraint with extended values
        Schema::table('tasks', function (Blueprint $table) {
            $table->enum('nature_of_task', ['daily', 'weekly', 'monthly', 'until_stop'])->default('daily')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Convert back to the previous enum values
            $table->string('nature_of_task')->change();
        });

        // Convert weekly, monthly, until_stop back to recurring
        DB::statement("UPDATE tasks SET nature_of_task = 'recurring' WHERE nature_of_task IN ('weekly', 'monthly', 'until_stop')");

        // Revert to the previous enum constraint
        Schema::table('tasks', function (Blueprint $table) {
            $table->enum('nature_of_task', ['daily', 'recurring'])->default('daily')->change();
        });
    }
};