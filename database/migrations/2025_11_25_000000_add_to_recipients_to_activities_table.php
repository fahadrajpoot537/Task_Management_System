<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds 'to' field to activities table to store email recipients (To field).
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // Add 'to' column to store email recipients
            if (!Schema::hasColumn('activities', 'to')) {
                $table->text('to')->nullable()->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            if (Schema::hasColumn('activities', 'to')) {
                $table->dropColumn('to');
            }
        });
    }
};

