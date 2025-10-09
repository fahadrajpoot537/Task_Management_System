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
        Schema::table('attachments', function (Blueprint $table) {
            $table->unsignedBigInteger('comment_id')->nullable()->after('task_id');
            $table->bigInteger('file_size')->nullable()->after('file_name');
            
            $table->foreign('comment_id')->references('id')->on('task_note_comments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropForeign(['comment_id']);
            $table->dropColumn(['comment_id', 'file_size']);
        });
    }
};
