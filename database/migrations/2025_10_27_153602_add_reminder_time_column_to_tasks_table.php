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
        // Check if column doesn't exist, then add it
        if (!Schema::hasColumn('tasks', 'reminder_time')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dateTime('reminder_time')->nullable()->after('due_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the column if it exists
        if (Schema::hasColumn('tasks', 'reminder_time')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('reminder_time');
            });
        }
    }
};
