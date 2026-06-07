<?php

use App\Models\Sweepstake;
use App\Models\TeamAssignment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sweepstake_draws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sweepstake_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('status')->default('active');
            $table->string('reason')->nullable();
            $table->timestamp('ran_at');
            $table->foreignId('rerun_of_draw_id')->nullable()->constrained('sweepstake_draws')->nullOnDelete();
            $table->timestamps();

            $table->unique(['sweepstake_id', 'version_number']);
            $table->index(['sweepstake_id', 'status']);
        });

        Schema::table('team_assignments', function (Blueprint $table) {
            $table->dropUnique(['sweepstake_id', 'team_id']);
            $table->dropUnique(['sweepstake_member_id', 'team_id']);
        });

        Schema::table('team_assignments', function (Blueprint $table) {
            $table->foreignId('sweepstake_draw_id')
                ->nullable()
                ->after('id')
                ->constrained('sweepstake_draws')
                ->cascadeOnDelete();
        });

        Sweepstake::query()
            ->whereHas('assignments')
            ->with('assignments')
            ->get()
            ->each(function (Sweepstake $sweepstake): void {
                $ranAt = $sweepstake->drawn_at
                    ?? $sweepstake->assignments->min('assigned_at')
                    ?? now();

                $drawId = DB::table('sweepstake_draws')->insertGetId([
                    'sweepstake_id' => $sweepstake->id,
                    'version_number' => 1,
                    'status' => 'active',
                    'reason' => null,
                    'ran_at' => $ranAt,
                    'rerun_of_draw_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                TeamAssignment::query()
                    ->where('sweepstake_id', $sweepstake->id)
                    ->update(['sweepstake_draw_id' => $drawId]);
            });

        Schema::table('team_assignments', function (Blueprint $table) {
            $table->unique(['sweepstake_draw_id', 'team_id']);
            $table->unique(['sweepstake_draw_id', 'sweepstake_member_id', 'team_id'], 'team_assignments_draw_member_team_unique');
            $table->index(['sweepstake_draw_id', 'sweepstake_member_id'], 'team_assignments_draw_member_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_assignments', function (Blueprint $table) {
            $table->dropUnique(['sweepstake_draw_id', 'team_id']);
            $table->dropUnique('team_assignments_draw_member_team_unique');
            $table->dropIndex('team_assignments_draw_member_index');
            $table->dropConstrainedForeignId('sweepstake_draw_id');
        });

        Schema::table('team_assignments', function (Blueprint $table) {
            $table->unique(['sweepstake_id', 'team_id']);
            $table->unique(['sweepstake_member_id', 'team_id']);
        });

        Schema::dropIfExists('sweepstake_draws');
    }
};
