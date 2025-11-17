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
        // Drop existing unique constraint on email first
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Make email nullable (users can be created with just name)
            $table->string('email')->nullable()->change();

            // Make password nullable (for invited users who haven't set password yet)
            $table->string('password')->nullable()->change();

            // Add invitation fields
            $table->string('invitation_token')->nullable()->unique()->after('password');
            $table->timestamp('invited_at')->nullable()->after('invitation_token');
            $table->foreignId('invited_by')->nullable()->after('invited_at')->constrained('users')->onDelete('set null');
            $table->boolean('is_active')->default(false)->after('invited_by');
        });

        // Re-add unique constraint on email (MySQL allows NULL in unique constraints)
        Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['invited_by']);
            $table->dropColumn(['invitation_token', 'invited_at', 'invited_by', 'is_active']);
            $table->string('password')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
        });
    }
};

