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
        // First, clean up any existing duplicates
        // Keep only the most recent active discipleship for each disciple
        $duplicates = DB::table('discipleships')
            ->select('disciple_id', DB::raw('COUNT(*) as count'))
            ->where('status', 'active')
            ->groupBy('disciple_id')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            // Get all active discipleships for this disciple, ordered by most recent
            $activeDiscipleships = DB::table('discipleships')
                ->where('disciple_id', $duplicate->disciple_id)
                ->where('status', 'active')
                ->orderBy('updated_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            // Keep the first (most recent) one, deactivate the rest
            if ($activeDiscipleships->count() > 1) {
                $toKeep = $activeDiscipleships->first();
                $toDeactivate = $activeDiscipleships->skip(1);

                foreach ($toDeactivate as $discipleship) {
                    DB::table('discipleships')
                        ->where('id', $discipleship->id)
                        ->update(['status' => 'inactive']);
                }
            }
        }

        // Add a unique index on (disciple_id) where status = 'active'
        // MySQL doesn't support partial unique indexes directly, so we'll use a workaround
        // We'll create a unique index on a generated column that's NULL when inactive
        Schema::table('discipleships', function (Blueprint $table) {
            // Add a generated column that contains disciple_id only when status is 'active'
            // This allows us to create a unique constraint on active discipleships only
            $table->unsignedBigInteger('active_disciple_id')->nullable()->after('disciple_id');
        });

        // Update the column to be disciple_id when active, NULL when inactive
        DB::statement('
            UPDATE discipleships
            SET active_disciple_id = CASE
                WHEN status = "active" THEN disciple_id
                ELSE NULL
            END
        ');

        // Create a trigger to automatically update active_disciple_id when status changes
        DB::statement('
            CREATE TRIGGER update_active_disciple_id_on_insert
            BEFORE INSERT ON discipleships
            FOR EACH ROW
            SET NEW.active_disciple_id = CASE
                WHEN NEW.status = "active" THEN NEW.disciple_id
                ELSE NULL
            END
        ');

        DB::statement('
            CREATE TRIGGER update_active_disciple_id_on_update
            BEFORE UPDATE ON discipleships
            FOR EACH ROW
            SET NEW.active_disciple_id = CASE
                WHEN NEW.status = "active" THEN NEW.disciple_id
                ELSE NULL
            END
        ');

        // Add unique index on active_disciple_id (this will only enforce uniqueness for active records)
        // NULL values are ignored in unique indexes, so only active records will be checked
        Schema::table('discipleships', function (Blueprint $table) {
            $table->unique('active_disciple_id', 'discipleships_active_disciple_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discipleships', function (Blueprint $table) {
            $table->dropUnique('discipleships_active_disciple_id_unique');
            $table->dropColumn('active_disciple_id');
        });

        DB::statement('DROP TRIGGER IF EXISTS update_active_disciple_id_on_insert');
        DB::statement('DROP TRIGGER IF EXISTS update_active_disciple_id_on_update');
    }
};

