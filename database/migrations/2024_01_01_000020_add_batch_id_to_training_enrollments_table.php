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
        Schema::table('training_enrollments', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->after('training_id')->constrained('training_batches')->onDelete('cascade');
            // Keep training_id for backward compatibility, but batch_id will be the primary reference
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_enrollments', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
            $table->dropColumn('batch_id');
        });
    }
};

