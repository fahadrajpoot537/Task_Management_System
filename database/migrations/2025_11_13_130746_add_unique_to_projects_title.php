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
        // First, handle any duplicate titles (case-insensitive)
        // Keep the first occurrence and append a suffix to duplicates
        $duplicates = DB::table('projects')
            ->select(DB::raw('LOWER(title) as lower_title, COUNT(*) as count'))
            ->groupBy('lower_title')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $projects = DB::table('projects')
                ->whereRaw('LOWER(title) = ?', [strtolower($dup->lower_title)])
                ->orderBy('id')
                ->get();

            // Keep the first one, rename the rest
            $counter = 1;
            foreach ($projects as $index => $project) {
                if ($index > 0) {
                    $newTitle = $project->title . ' (' . $counter . ')';
                    // Make sure the new title is also unique
                    while (DB::table('projects')->where('title', $newTitle)->exists()) {
                        $counter++;
                        $newTitle = $project->title . ' (' . $counter . ')';
                    }
                    DB::table('projects')
                        ->where('id', $project->id)
                        ->update(['title' => $newTitle]);
                    $counter++;
                }
            }
        }

        // Now add the unique constraint
        Schema::table('projects', function (Blueprint $table) {
            $table->unique('title', 'projects_title_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique('projects_title_unique');
        });
    }
};
