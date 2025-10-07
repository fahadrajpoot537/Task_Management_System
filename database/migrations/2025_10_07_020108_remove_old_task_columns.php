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
            // Remove the old string columns that conflict with the new foreign key relationships
            $table->dropColumn(['status', 'priority', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Add back the old columns if needed to rollback
            $table->string('status')->nullable();
            $table->string('priority')->nullable();
            $table->string('category')->nullable();
        });
    }
};