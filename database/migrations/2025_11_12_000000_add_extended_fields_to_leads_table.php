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
        Schema::table('leads', function (Blueprint $table) {
            // Lead Type and Progress
            $table->string('lead_type', 100)->nullable()->after('status_id');
            $table->string('progress', 50)->nullable()->after('lead_type');
            
            // Permissions
            $table->boolean('permission_to_call')->default(false)->after('progress');
            $table->boolean('permission_to_text')->default(false)->after('permission_to_call');
            $table->boolean('permission_to_email')->default(false)->after('permission_to_text');
            $table->boolean('permission_to_mail')->default(false)->after('permission_to_email');
            $table->boolean('permission_to_fax')->default(false)->after('permission_to_mail');
            
            // Site Information
            $table->string('site_id', 50)->nullable()->after('permission_to_fax');
            $table->string('site_name', 100)->nullable()->after('site_id');
            
            // User Information
            $table->string('user_id', 50)->nullable()->after('site_name');
            $table->string('user_name', 100)->nullable()->after('user_id');
            
            // Buyer Information
            $table->string('buyer_id', 50)->nullable()->after('user_name');
            $table->string('buyer_name', 100)->nullable()->after('buyer_id');
            $table->string('buyer_reference', 100)->nullable()->after('buyer_name');
            
            // Introducer Information
            $table->string('introducer_id', 50)->nullable()->after('buyer_reference');
            $table->string('introducer_name', 100)->nullable()->after('introducer_id');
            $table->string('introducer_reference', 100)->nullable()->after('introducer_name');
            
            // Cost and Value
            $table->decimal('cost', 15, 2)->nullable()->after('introducer_reference');
            $table->decimal('value', 15, 2)->nullable()->after('cost');
            
            // IP Address
            $table->string('ip_address', 45)->nullable()->after('value');
            
            // Marketing Information
            $table->string('marketing_source', 100)->nullable()->after('ip_address');
            $table->string('marketing_medium', 100)->nullable()->after('marketing_source');
            $table->string('marketing_term', 100)->nullable()->after('marketing_medium');
            
            // Transfer Information
            $table->datetime('transfer_date_time')->nullable()->after('marketing_term');
            $table->boolean('transfer_successful')->default(false)->after('transfer_date_time');
            
            // XML Information
            $table->text('xml_post')->nullable()->after('transfer_successful');
            $table->text('xml_response')->nullable()->after('xml_post');
            $table->datetime('xml_date_time')->nullable()->after('xml_response');
            $table->integer('xml_fails')->default(0)->after('xml_date_time');
            $table->string('xml_result', 100)->nullable()->after('xml_fails');
            $table->string('xml_reference', 100)->nullable()->after('xml_result');
            
            // Appointment Information
            $table->datetime('appointment_date_time')->nullable()->after('xml_reference');
            $table->text('appointment_notes')->nullable()->after('appointment_date_time');
            
            // Return Information
            $table->string('return_status', 50)->nullable()->after('appointment_notes');
            $table->datetime('return_date_time')->nullable()->after('return_status');
            $table->text('return_reason')->nullable()->after('return_date_time');
            $table->datetime('return_decision_date_time')->nullable()->after('return_reason');
            $table->string('return_decision_user', 100)->nullable()->after('return_decision_date_time');
            $table->text('return_decision_information')->nullable()->after('return_decision_user');
            
            // Last Note Information
            $table->datetime('last_note_date_time')->nullable()->after('return_decision_information');
            
            // Task and Workflow
            $table->boolean('task_exists')->default(false)->after('last_note_date_time');
            $table->boolean('workflow_exists')->default(false)->after('task_exists');
            
            // Additional Name Fields
            $table->string('full_name', 200)->nullable()->after('workflow_exists');
            $table->string('job_title', 100)->nullable()->after('full_name');
            
            // Additional Contact Fields
            $table->string('fax', 50)->nullable()->after('alternative_phone');
            $table->text('address2')->nullable()->after('address');
            $table->text('address3')->nullable()->after('address2');
            $table->string('contact_time', 50)->nullable()->after('postcode');
            
            // Data Fields (Data1-Data50) - Use TEXT to avoid row size issues
            for ($i = 1; $i <= 50; $i++) {
                $table->text("data{$i}")->nullable();
            }
            
            // Type Fields (Type1-Type50) - Use smaller VARCHAR
            for ($i = 1; $i <= 50; $i++) {
                $table->string("type{$i}", 100)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Drop all the added columns
            $columns = [
                'lead_type', 'progress',
                'permission_to_call', 'permission_to_text', 'permission_to_email', 'permission_to_mail', 'permission_to_fax',
                'site_id', 'site_name',
                'user_id', 'user_name',
                'buyer_id', 'buyer_name', 'buyer_reference',
                'introducer_id', 'introducer_name', 'introducer_reference',
                'cost', 'value', 'ip_address',
                'marketing_source', 'marketing_medium', 'marketing_term',
                'transfer_date_time', 'transfer_successful',
                'xml_post', 'xml_response', 'xml_date_time', 'xml_fails', 'xml_result', 'xml_reference',
                'appointment_date_time', 'appointment_notes',
                'return_status', 'return_date_time', 'return_reason', 'return_decision_date_time', 'return_decision_user', 'return_decision_information',
                'last_note_date_time',
                'task_exists', 'workflow_exists',
                'full_name', 'job_title',
                'fax', 'address2', 'address3', 'contact_time',
            ];
            
            // Add Data and Type fields
            for ($i = 1; $i <= 50; $i++) {
                $columns[] = "data{$i}";
                $columns[] = "type{$i}";
            }
            
            $table->dropColumn($columns);
        });
    }
};

