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
        Schema::create('training_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_enrollment_id')->constrained()->onDelete('cascade');
            $table->integer('lesson_number');
            $table->date('attendance_date');
            $table->boolean('is_present')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['training_enrollment_id', 'lesson_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_attendances');
    }
};

