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
        // Change nature_of_task from enum to string to allow flexible values
        DB::statement('ALTER TABLE tasks MODIFY COLUMN nature_of_task VARCHAR(50) NOT NULL DEFAULT "daily"');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to enum (will fail if data doesn't match)
        DB::statement('ALTER TABLE tasks MODIFY COLUMN nature_of_task ENUM("daily", "recurring") NOT NULL DEFAULT "daily"');
    }
};
