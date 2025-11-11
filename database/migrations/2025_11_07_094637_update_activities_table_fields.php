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
            // Drop old columns if they exist
            if (Schema::hasColumn('activities', 'activity_type')) {
                $table->dropColumn('activity_type');
            }
            if (Schema::hasColumn('activities', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('activities', 'activity_date')) {
                $table->dropColumn('activity_date');
            }
            
            // Add new columns
            if (!Schema::hasColumn('activities', 'date')) {
                $table->date('date')->nullable()->after('lead_id');
            }
            if (!Schema::hasColumn('activities', 'type')) {
                $table->string('type')->nullable()->after('date');
            }
            if (!Schema::hasColumn('activities', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null')->after('created_by');
            }
            if (!Schema::hasColumn('activities', 'field_1')) {
                $table->string('field_1')->nullable()->after('assigned_to');
            }
            if (!Schema::hasColumn('activities', 'field_2')) {
                $table->string('field_2')->nullable()->after('field_1');
            }
            if (!Schema::hasColumn('activities', 'email')) {
                $table->string('email')->nullable()->after('field_2');
            }
            if (!Schema::hasColumn('activities', 'bcc')) {
                $table->text('bcc')->nullable()->after('email');
            }
            if (!Schema::hasColumn('activities', 'cc')) {
                $table->text('cc')->nullable()->after('bcc');
            }
            if (!Schema::hasColumn('activities', 'phone')) {
                $table->string('phone')->nullable()->after('cc');
            }
            if (!Schema::hasColumn('activities', 'actioned')) {
                $table->boolean('actioned')->default(false)->after('phone');
            }
            if (!Schema::hasColumn('activities', 'due_date')) {
                $table->date('due_date')->nullable()->after('actioned');
            }
            if (!Schema::hasColumn('activities', 'end_date')) {
                $table->date('end_date')->nullable()->after('due_date');
            }
            if (!Schema::hasColumn('activities', 'priority')) {
                $table->string('priority')->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('activities', 'file')) {
                $table->string('file')->nullable()->after('priority');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // Drop new columns
            $columns = ['date', 'type', 'assigned_to', 'field_1', 'field_2', 
                'email', 'bcc', 'cc', 'phone', 'actioned', 
                'due_date', 'end_date', 'priority', 'file'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('activities', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Restore old columns
            if (!Schema::hasColumn('activities', 'activity_type')) {
                $table->string('activity_type')->nullable();
            }
            if (!Schema::hasColumn('activities', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('activities', 'activity_date')) {
                $table->dateTime('activity_date')->nullable();
            }
        });
    }
};
