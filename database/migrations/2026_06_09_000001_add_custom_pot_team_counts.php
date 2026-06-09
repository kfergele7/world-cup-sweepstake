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
        Schema::table('sweepstake_pots', function (Blueprint $table) {
            $table->unsignedSmallInteger('teams_per_entrant')->default(1)->after('position');
        });

        Schema::table('sweepstake_draws', function (Blueprint $table) {
            $table->json('custom_pot_summary')->nullable()->after('pot_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sweepstake_draws', function (Blueprint $table) {
            $table->dropColumn('custom_pot_summary');
        });

        Schema::table('sweepstake_pots', function (Blueprint $table) {
            $table->dropColumn('teams_per_entrant');
        });
    }
};
