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
            $table->enum('nature_of_task', ['daily', 'weekly', 'monthly'])->default('daily')->after('estimated_hours');
            $table->boolean('is_recurring')->default(false)->after('nature_of_task');
            $table->integer('parent_task_id')->nullable()->after('is_recurring');
            $table->timestamp('next_recurrence_date')->nullable()->after('parent_task_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['nature_of_task', 'is_recurring', 'parent_task_id', 'next_recurrence_date']);
        });
    }
};
