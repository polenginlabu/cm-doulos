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
            $table->string('phone')->nullable()->after('email');
            $table->date('date_of_birth')->nullable()->after('phone');
            $table->enum('attendance_status', ['1st', '2nd', '3rd', '4th', 'regular'])->default('1st')->after('date_of_birth');
            $table->date('first_attendance_date')->nullable()->after('attendance_status');
            $table->date('last_attendance_date')->nullable()->after('first_attendance_date');
            $table->integer('total_attendances')->default(0)->after('last_attendance_date');
            $table->foreignId('cell_group_id')->nullable()->after('total_attendances')->constrained('cell_groups')->onDelete('set null');
            $table->text('notes')->nullable()->after('cell_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['cell_group_id']);
            $table->dropColumn([
                'phone',
                'date_of_birth',
                'attendance_status',
                'first_attendance_date',
                'last_attendance_date',
                'total_attendances',
                'cell_group_id',
                'notes',
            ]);
        });
    }
};

