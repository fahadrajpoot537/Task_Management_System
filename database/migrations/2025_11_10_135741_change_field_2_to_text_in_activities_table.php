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
            // Change field_2 from string to text to store HTML content
            if (Schema::hasColumn('activities', 'field_2')) {
                $table->text('field_2')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // Revert field_2 back to string
            if (Schema::hasColumn('activities', 'field_2')) {
                $table->string('field_2')->nullable()->change();
            }
        });
    }
};
