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
        Schema::table('activities', function (Blueprint $table) {
            if (Schema::hasColumn('activities', 'assigned_by')) {
                // Drop the foreign key constraint first
                $table->dropForeign(['assigned_by']);
            }
        });
        
        // Use DB statement to rename the column (more compatible across database systems)
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE activities CHANGE assigned_by assigned_to BIGINT UNSIGNED NULL');
        
        Schema::table('activities', function (Blueprint $table) {
            // Re-add the foreign key constraint with the new column name
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            if (Schema::hasColumn('activities', 'assigned_to')) {
                // Drop the foreign key constraint first
                $table->dropForeign(['assigned_to']);
            }
        });
        
        // Use DB statement to rename the column back
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE activities CHANGE assigned_to assigned_by BIGINT UNSIGNED NULL');
        
        Schema::table('activities', function (Blueprint $table) {
            // Re-add the foreign key constraint with the old column name
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
        });
    }
};
