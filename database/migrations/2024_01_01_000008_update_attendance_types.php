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
        // Update the enum values for attendance_type
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return; // SQLite doesn't support MODIFY COLUMN / ENUM
        }
        DB::statement("ALTER TABLE attendances MODIFY COLUMN attendance_type ENUM('cell_group', 'service', 'event', 'crossover', 'wildsons', 'sunday_service') DEFAULT 'sunday_service'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }
        DB::statement("ALTER TABLE attendances MODIFY COLUMN attendance_type ENUM('cell_group', 'service', 'event') DEFAULT 'cell_group'");
    }
};
