<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds in_reply_to field to activities table for email threading.
     * This allows replies to reference the original message_id while
     * maintaining unique message_id for each email.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            if (!Schema::hasColumn('activities', 'in_reply_to')) {
                $table->string('in_reply_to', 500)->nullable()->after('message_id');
                $table->index('in_reply_to'); // Add index for faster queries when finding thread
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            if (Schema::hasColumn('activities', 'in_reply_to')) {
                $table->dropIndex(['in_reply_to']);
                $table->dropColumn('in_reply_to');
            }
        });
    }
};
