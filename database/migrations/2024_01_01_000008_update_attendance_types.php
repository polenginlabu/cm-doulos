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
        DB::statement("ALTER TABLE attendances MODIFY COLUMN attendance_type ENUM('cell_group', 'service', 'event', 'crossover', 'wildsons', 'sunday_service') DEFAULT 'sunday_service'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE attendances MODIFY COLUMN attendance_type ENUM('cell_group', 'service', 'event') DEFAULT 'cell_group'");
    }
};

