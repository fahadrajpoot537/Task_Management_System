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
        if (!Schema::hasTable('leads')) {
            return; // Skip if leads table doesn't exist
        }
        
        Schema::table('leads', function (Blueprint $table) {
            // Drop the old status column if it exists
            if (Schema::hasColumn('leads', 'status')) {
                $table->dropColumn('status');
            }
        });
        
        // Add status_id column in a separate operation to avoid issues
        if (Schema::hasTable('leads') && !Schema::hasColumn('leads', 'status_id')) {
            Schema::table('leads', function (Blueprint $table) {
                // Check if statuses table exists before adding foreign key
                if (Schema::hasTable('statuses')) {
                    $table->foreignId('status_id')->nullable()->after('note')->constrained('statuses')->onDelete('set null');
                } else {
                    $table->foreignId('status_id')->nullable()->after('note');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Drop status_id foreign key and column
            if (Schema::hasColumn('leads', 'status_id')) {
                $table->dropForeign(['status_id']);
                $table->dropColumn('status_id');
            }
            
            // Restore status column
            if (!Schema::hasColumn('leads', 'status')) {
                $table->string('status')->nullable()->after('note');
            }
        });
    }
};
