<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds message_id field to activities table to prevent duplicate email imports.
     * The message_id is the unique identifier from the email header.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // Add message_id column to store unique email message identifier
            // This prevents importing the same email multiple times
            if (!Schema::hasColumn('activities', 'message_id')) {
                $table->string('message_id', 500)->nullable()->unique()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            if (Schema::hasColumn('activities', 'message_id')) {
                $table->dropColumn('message_id');
            }
        });
    }
};

