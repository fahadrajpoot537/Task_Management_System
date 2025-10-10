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

        // Update existing data to convert 'weekly' and 'monthly' to 'recurring'
        DB::statement("UPDATE tasks SET nature_of_task = 'recurring' WHERE nature_of_task IN ('weekly', 'monthly')");

        // Now set the enum constraint
        Schema::table('tasks', function (Blueprint $table) {
            $table->enum('nature_of_task', ['daily', 'recurring'])->default('daily')->change();
            
            // Add is_recurring_active column to control recurring task generation
            $table->boolean('is_recurring_active')->default(true)->after('is_recurring');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Revert nature_of_task to original enum values
            $table->enum('nature_of_task', ['daily', 'weekly', 'monthly'])->default('daily')->change();
            
            // Drop the is_recurring_active column
            $table->dropColumn('is_recurring_active');
        });
    }
};
