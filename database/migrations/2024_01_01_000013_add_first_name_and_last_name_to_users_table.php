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
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
        });

        // Migrate existing data: split name into first_name and last_name
        DB::table('users')->whereNotNull('name')->get()->each(function ($user) {
            $nameParts = explode(' ', trim($user->name), 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reconstruct name from first_name and last_name before dropping columns
        DB::table('users')->whereNotNull('first_name')->orWhereNotNull('last_name')->get()->each(function ($user) {
            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            if (empty($fullName)) {
                $fullName = $user->name; // Fallback to existing name if available
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update(['name' => $fullName]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};

