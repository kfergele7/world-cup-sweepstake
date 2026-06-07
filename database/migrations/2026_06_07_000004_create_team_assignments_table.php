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
        Schema::create('team_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sweepstake_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sweepstake_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('pot_number')->nullable();
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->unique(['sweepstake_id', 'team_id']);
            $table->unique(['sweepstake_member_id', 'team_id']);
            $table->index(['sweepstake_id', 'sweepstake_member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_assignments');
    }
};
