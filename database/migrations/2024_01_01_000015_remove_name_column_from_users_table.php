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
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
        });

        // Reconstruct name from first_name and last_name
        \Illuminate\Support\Facades\DB::table('users')
            ->whereNotNull('first_name')
            ->orWhereNotNull('last_name')
            ->get()
            ->each(function ($user) {
                $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                if (!empty($fullName)) {
                    \Illuminate\Support\Facades\DB::table('users')
                        ->where('id', $user->id)
                        ->update(['name' => $fullName]);
                }
            });
    }
};

