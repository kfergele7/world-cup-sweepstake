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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('country_code', 3)->unique();
            $table->string('flag')->nullable();
            $table->unsignedSmallInteger('fifa_ranking')->nullable();
            $table->decimal('ranking_points', 8, 2)->nullable();
            $table->boolean('qualified_for_2026')->default(true);
            $table->string('confederation')->nullable();
            $table->timestamps();

            $table->index(['qualified_for_2026', 'fifa_ranking']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
