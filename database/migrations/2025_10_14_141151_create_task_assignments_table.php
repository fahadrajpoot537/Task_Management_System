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
        Schema::create('task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_by_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();
            
            // Ensure unique combination of task and user
            $table->unique(['task_id', 'user_id']);
            
            // Add indexes for better performance
            $table->index(['task_id']);
            $table->index(['user_id']);
            $table->index(['assigned_by_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_assignments');
    }
};