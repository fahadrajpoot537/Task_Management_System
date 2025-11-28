<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Changes field_2 from VARCHAR to TEXT to accommodate longer content.
     * Handles existing data that may exceed VARCHAR limits.
     */
    public function up(): void
    {
        if (Schema::hasColumn('activities', 'field_2')) {
            // Get current column type
            $columnInfo = DB::select("SHOW COLUMNS FROM `activities` WHERE Field = 'field_2'");
            
            if (!empty($columnInfo)) {
                $columnType = strtolower($columnInfo[0]->Type ?? '');
                
                // Only alter if it's not already TEXT or LONGTEXT
                if (strpos($columnType, 'text') === false) {
                    // Get current SQL mode to restore later
                    $result = DB::select("SELECT @@SESSION.sql_mode as mode");
                    $originalMode = $result[0]->mode ?? '';
                    
                    try {
                        // Disable strict mode temporarily to allow conversion
                        // This prevents MySQL from validating data length before allowing ALTER
                        DB::statement("SET SESSION sql_mode = ''");
                        
                        // Change column type from VARCHAR to TEXT
                        // This will preserve all existing data, even if it exceeds VARCHAR limits
                        DB::statement('ALTER TABLE `activities` MODIFY COLUMN `field_2` TEXT NULL CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                    } catch (\Exception $e) {
                        // Log the error but don't fail the migration
                        // The column might already be TEXT or the conversion might have succeeded
                        \Log::warning('Error changing field_2 to TEXT: ' . $e->getMessage());
                        
                        // Try one more time with a simpler statement
                        try {
                            DB::statement('ALTER TABLE `activities` MODIFY `field_2` TEXT NULL');
                        } catch (\Exception $e2) {
                            \Log::error('Failed to change field_2 to TEXT: ' . $e2->getMessage());
                            throw $e2;
                        }
                    } finally {
                        // Always restore original SQL mode
                        if ($originalMode) {
                            DB::statement("SET SESSION sql_mode = '{$originalMode}'");
                        }
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('activities', 'field_2')) {
            // Revert field_2 back to VARCHAR(255)
            // Note: This may truncate data if any field_2 values exceed 255 characters
            DB::statement('ALTER TABLE `activities` MODIFY COLUMN `field_2` VARCHAR(255) NULL');
        }
    }
};
