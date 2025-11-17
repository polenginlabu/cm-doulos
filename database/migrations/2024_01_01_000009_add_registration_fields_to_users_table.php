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
            $table->foreignId('cell_leader_id')->nullable()->after('cell_group_id')->constrained('users')->onDelete('set null');
            $table->foreignId('network_leader_id')->nullable()->after('cell_leader_id')->constrained('users')->onDelete('set null');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('network_leader_id');
            $table->boolean('is_primary_leader')->default(false)->after('gender');
            $table->boolean('is_super_admin')->default(false)->after('is_primary_leader');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['cell_leader_id']);
            $table->dropForeign(['network_leader_id']);
            $table->dropColumn(['cell_leader_id', 'network_leader_id', 'gender', 'is_primary_leader', 'is_super_admin']);
        });
    }
};

