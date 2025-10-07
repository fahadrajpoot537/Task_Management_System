<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Add new foreign key columns
            $table->foreignId('priority_id')->nullable()->constrained('task_priorities')->onDelete('set null');
            $table->foreignId('category_id')->nullable()->constrained('task_categories')->onDelete('set null');
            $table->foreignId('status_id')->nullable()->constrained('task_statuses')->onDelete('set null');
            
            // Add estimated_hours column if it doesn't exist
            if (!Schema::hasColumn('tasks', 'estimated_hours')) {
                $table->decimal('estimated_hours', 8, 2)->nullable();
            }
            
            // Add actual_hours column if it doesn't exist
            if (!Schema::hasColumn('tasks', 'actual_hours')) {
                $table->decimal('actual_hours', 8, 2)->nullable();
            }
            
            // Add started_at and completed_at columns if they don't exist
            if (!Schema::hasColumn('tasks', 'started_at')) {
                $table->timestamp('started_at')->nullable();
            }
            if (!Schema::hasColumn('tasks', 'completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['priority_id']);
            $table->dropForeign(['category_id']);
            $table->dropForeign(['status_id']);
            $table->dropColumn(['priority_id', 'category_id', 'status_id']);
        });
    }
};