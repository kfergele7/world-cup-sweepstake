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
        Schema::table('sweepstake_draws', function (Blueprint $table) {
            $table->string('leftover_strategy')->nullable()->after('rerun_of_draw_id');
            $table->unsignedSmallInteger('selected_team_count')->nullable()->after('leftover_strategy');
            $table->unsignedSmallInteger('base_teams_per_member')->nullable()->after('selected_team_count');
            $table->unsignedSmallInteger('leftover_team_count')->nullable()->after('base_teams_per_member');
            $table->string('cancelled_reason')->nullable()->after('leftover_team_count');
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sweepstake_draws', function (Blueprint $table) {
            $table->dropColumn([
                'leftover_strategy',
                'selected_team_count',
                'base_teams_per_member',
                'leftover_team_count',
                'cancelled_reason',
                'cancelled_at',
            ]);
        });
    }
};
