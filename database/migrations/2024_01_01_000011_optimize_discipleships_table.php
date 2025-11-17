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
        Schema::table('discipleships', function (Blueprint $table) {
            // Add indexes for performance
            // These indexes will significantly speed up queries filtering by mentor_id, disciple_id, and status
            try {
                $table->index('mentor_id');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }

            try {
                $table->index('disciple_id');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }

            try {
                $table->index('status');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }

            // Composite indexes for common query patterns
            try {
                $table->index(['mentor_id', 'status'], 'discipleships_mentor_id_status_index');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }

            try {
                $table->index(['disciple_id', 'status'], 'discipleships_disciple_id_status_index');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discipleships', function (Blueprint $table) {
            try {
                $table->dropIndex('discipleships_mentor_id_index');
            } catch (\Exception $e) {
                // Index might not exist, skip
            }

            try {
                $table->dropIndex('discipleships_disciple_id_index');
            } catch (\Exception $e) {
                // Index might not exist, skip
            }

            try {
                $table->dropIndex('discipleships_status_index');
            } catch (\Exception $e) {
                // Index might not exist, skip
            }

            try {
                $table->dropIndex('discipleships_mentor_id_status_index');
            } catch (\Exception $e) {
                // Index might not exist, skip
            }

            try {
                $table->dropIndex('discipleships_disciple_id_status_index');
            } catch (\Exception $e) {
                // Index might not exist, skip
            }
        });
    }
};

