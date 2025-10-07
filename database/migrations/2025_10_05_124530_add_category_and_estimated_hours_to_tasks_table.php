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
            $table->string('category')->default('general')->after('priority');
            $table->integer('estimated_hours')->nullable()->after('duration');
            $table->integer('actual_hours')->nullable()->after('estimated_hours');
            $table->timestamp('started_at')->nullable()->after('actual_hours');
            $table->timestamp('completed_at')->nullable()->after('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['category', 'estimated_hours', 'actual_hours', 'started_at', 'completed_at']);
        });
    }
};
