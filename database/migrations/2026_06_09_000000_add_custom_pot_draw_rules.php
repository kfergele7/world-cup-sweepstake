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
        Schema::table('sweepstakes', function (Blueprint $table) {
            $table->string('pot_mode')->default('auto_pots')->after('draw_mode');
        });

        Schema::table('sweepstake_draws', function (Blueprint $table) {
            $table->string('pot_mode')->default('auto_pots')->after('rerun_of_draw_id');
        });

        Schema::create('sweepstake_pots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sweepstake_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('position')->default(1);
            $table->timestamps();

            $table->index(['sweepstake_id', 'position']);
        });

        Schema::create('sweepstake_pot_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sweepstake_pot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sweepstake_team_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->nullable();
            $table->timestamps();

            $table->unique('sweepstake_team_id');
            $table->index(['sweepstake_pot_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sweepstake_pot_teams');
        Schema::dropIfExists('sweepstake_pots');

        Schema::table('sweepstake_draws', function (Blueprint $table) {
            $table->dropColumn('pot_mode');
        });

        Schema::table('sweepstakes', function (Blueprint $table) {
            $table->dropColumn('pot_mode');
        });
    }
};
