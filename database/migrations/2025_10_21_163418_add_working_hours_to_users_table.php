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
        Schema::table('users', function (Blueprint $table) {
            $table->time('check_in_time')->nullable()->after('last_seen');
            $table->time('check_out_time')->nullable()->after('check_in_time');
            $table->string('zkteco_uid')->nullable()->after('check_out_time'); // To link with ZKTeco device UID
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['check_in_time', 'check_out_time', 'zkteco_uid']);
        });
    }
};