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
        Schema::create('consolidation_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('consolidator_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('type')->default('first_timer'); // first_timer, second_timer, third_timer, fourth_timer, vip
            $table->string('status')->default('not_contacted'); // not_contacted, contacted, in_progress, follow_up_scheduled, completed
            $table->text('interest')->nullable();
            $table->text('notes')->nullable();
            $table->text('next_action')->nullable();
            $table->date('added_at');
            $table->date('contacted_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('type');
            $table->index('consolidator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consolidation_members');
    }
};

