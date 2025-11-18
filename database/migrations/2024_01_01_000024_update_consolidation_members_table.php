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
        Schema::table('consolidation_members', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['user_id']);
        });

        Schema::table('consolidation_members', function (Blueprint $table) {
            // Make user_id required (not nullable)
            $table->foreignId('user_id')->nullable(false)->change();

            // Remove standalone fields since we'll use user data
            $table->dropColumn(['name', 'email', 'phone']);
        });

        Schema::table('consolidation_members', function (Blueprint $table) {
            // Recreate the foreign key constraint with CASCADE instead of SET NULL
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consolidation_members', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['user_id']);
        });

        Schema::table('consolidation_members', function (Blueprint $table) {
            // Add back the columns
            $table->string('name')->after('consolidator_id');
            $table->string('email')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');

            // Make user_id nullable again
            $table->foreignId('user_id')->nullable()->change();
        });

        Schema::table('consolidation_members', function (Blueprint $table) {
            // Recreate the foreign key constraint with SET NULL
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }
};

