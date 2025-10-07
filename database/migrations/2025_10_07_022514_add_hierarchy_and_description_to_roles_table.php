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
        Schema::table('roles', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->integer('hierarchy_level')->default(0)->after('description');
            $table->boolean('is_system_role')->default(false)->after('hierarchy_level');
            $table->string('color', 20)->default('secondary')->after('is_system_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['description', 'hierarchy_level', 'is_system_role', 'color']);
        });
    }
};
