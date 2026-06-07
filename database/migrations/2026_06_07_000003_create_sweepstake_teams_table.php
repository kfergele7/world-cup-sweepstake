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
        Schema::create('sweepstake_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sweepstake_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_included')->default(true);
            $table->boolean('is_removed')->default(false);
            $table->string('removed_reason')->nullable();
            $table->unsignedSmallInteger('pot_number')->nullable();
            $table->unsignedSmallInteger('sort_order')->nullable();
            $table->timestamps();

            $table->unique(['sweepstake_id', 'team_id']);
            $table->index(['sweepstake_id', 'is_included', 'is_removed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sweepstake_teams');
    }
};
