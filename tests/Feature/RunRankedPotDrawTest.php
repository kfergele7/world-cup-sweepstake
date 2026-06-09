<?php

namespace Tests\Feature;

use App\Actions\RunRankedPotDraw;
use App\Exceptions\DrawException;
use App\Models\Sweepstake;
use App\Models\SweepstakeDraw;
use App\Models\SweepstakeMember;
use App\Models\SweepstakePot;
use App\Models\SweepstakePotTeam;
use App\Models\SweepstakeTeam;
use App\Models\Team;
use App\Models\TeamAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RunRankedPotDrawTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_ranked_pots_and_removes_lowest_ranked_leftovers(): void
    {
        $sweepstake = $this->createSweepstake(memberCount: 7, teamCount: 48);

        $plan = app(RunRankedPotDraw::class)->buildPlan($sweepstake);

        $this->assertSame(7, $plan['member_count']);
        $this->assertSame(48, $plan['selected_team_count']);
        $this->assertSame(6, $plan['teams_per_member']);
        $this->assertSame(42, $plan['usable_team_count']);
        $this->assertSame(6, $plan['leftover_team_count']);
        $this->assertCount(6, $plan['pots']);

        $this->assertSame(range(1, 7), $plan['pots']->first()['teams']->map(fn (SweepstakeTeam $team) => $team->team->fifa_ranking)->all());
        $this->assertSame(range(36, 42), $plan['pots']->last()['teams']->map(fn (SweepstakeTeam $team) => $team->team->fifa_ranking)->all());
        $this->assertSame(range(43, 48), $plan['leftover_teams']->map(fn (SweepstakeTeam $team) => $team->team->fifa_ranking)->all());
    }

    public function test_it_assigns_one_team_from_each_pot_to_every_entrant(): void
    {
        $sweepstake = $this->createSweepstake(memberCount: 3, teamCount: 10, paidMemberCount: 1);

        $assignments = app(RunRankedPotDraw::class)->handle($sweepstake);
        $sweepstake->refresh();

        $this->assertCount(9, $assignments);
        $this->assertSame(Sweepstake::STATUS_DRAWN, $sweepstake->status);
        $this->assertSame(3, $sweepstake->teams_per_member);
        $this->assertNotNull($sweepstake->drawn_at);
        $this->assertDatabaseHas('sweepstake_draws', [
            'sweepstake_id' => $sweepstake->id,
            'version_number' => 1,
            'status' => SweepstakeDraw::STATUS_ACTIVE,
            'reason' => null,
            'pot_mode' => Sweepstake::POT_MODE_AUTO,
            'leftover_strategy' => SweepstakeDraw::LEFTOVER_STRATEGY_REMOVE_LOWEST_RANKED,
        ]);
        $this->assertDatabaseCount('team_assignments', 9);

        $sweepstake->members->each(function (SweepstakeMember $member): void {
            $this->assertSame(3, $member->assignments()->count());
        });

        $this->assertTrue(TeamAssignment::where('sweepstake_id', $sweepstake->id)
            ->whereNotNull('sweepstake_draw_id')
            ->exists());

        foreach (range(1, 3) as $potNumber) {
            $assignmentsForPot = TeamAssignment::where('pot_number', $potNumber)->get();

            $this->assertSame(3, $assignmentsForPot->count());
            $this->assertSame(3, $assignmentsForPot->pluck('sweepstake_member_id')->unique()->count());
        }

        $this->assertDatabaseHas('sweepstake_teams', [
            'sweepstake_id' => $sweepstake->id,
            'is_included' => false,
            'is_removed' => true,
            'removed_reason' => 'Removed as a lowest-ranked leftover team during the draw.',
        ]);
    }

    public function test_it_rejects_draws_with_fewer_than_two_entrants(): void
    {
        $sweepstake = $this->createSweepstake(memberCount: 1, teamCount: 4);

        $this->expectException(DrawException::class);
        $this->expectExceptionMessage('Add at least two entrants');

        app(RunRankedPotDraw::class)->handle($sweepstake);
    }

    public function test_it_rejects_draws_when_there_are_not_enough_selected_teams(): void
    {
        $sweepstake = $this->createSweepstake(memberCount: 3, teamCount: 2);

        $this->expectException(DrawException::class);
        $this->expectExceptionMessage('Remove entrants or restore teams before running the draw.');

        app(RunRankedPotDraw::class)->handle($sweepstake);
    }

    public function test_it_assigns_leftover_teams_randomly_when_requested(): void
    {
        $sweepstake = $this->createSweepstake(memberCount: 3, teamCount: 10);

        $assignments = app(RunRankedPotDraw::class)->handle(
            $sweepstake,
            leftoverStrategy: SweepstakeDraw::LEFTOVER_STRATEGY_ASSIGN_RANDOMLY,
        );

        $this->assertCount(10, $assignments);
        $this->assertSame(10, TeamAssignment::where('sweepstake_id', $sweepstake->id)->count());
        $this->assertSame(0, $sweepstake->sweepstakeTeams()->where('is_removed', true)->count());

        $memberAssignmentCounts = TeamAssignment::where('sweepstake_id', $sweepstake->id)
            ->get()
            ->groupBy('sweepstake_member_id')
            ->map->count()
            ->sort()
            ->values()
            ->all();

        $this->assertSame([3, 3, 4], $memberAssignmentCounts);
        $this->assertDatabaseHas('sweepstake_draws', [
            'sweepstake_id' => $sweepstake->id,
            'leftover_strategy' => SweepstakeDraw::LEFTOVER_STRATEGY_ASSIGN_RANDOMLY,
            'selected_team_count' => 10,
            'base_teams_per_member' => 3,
            'leftover_team_count' => 1,
        ]);
    }

    public function test_custom_pot_draw_uses_only_the_configured_number_of_teams_from_broad_pots(): void
    {
        $sweepstake = $this->createSweepstake(memberCount: 3, teamCount: 22);
        $sweepstake->update([
            'pot_mode' => Sweepstake::POT_MODE_CUSTOM,
        ]);
        $teams = $sweepstake->sweepstakeTeams()->orderBy('sort_order')->get();

        $this->createPot($sweepstake, 'Favourites', $teams->take(9), teamsPerEntrant: 1);
        $this->createPot($sweepstake, 'Strong contenders', $teams->slice(9, 9)->values(), teamsPerEntrant: 1);
        $this->createPot($sweepstake, 'Outsiders', $teams->slice(18, 4)->values(), teamsPerEntrant: 1);

        $assignments = app(RunRankedPotDraw::class)->handle($sweepstake->fresh());
        $sweepstake->refresh();

        $this->assertCount(9, $assignments);
        $this->assertSame(Sweepstake::STATUS_DRAWN, $sweepstake->status);
        $this->assertSame(3, $sweepstake->teams_per_member);
        $this->assertSame(0, $sweepstake->sweepstakeTeams()->where('is_removed', true)->count());
        $this->assertDatabaseHas('sweepstake_draws', [
            'sweepstake_id' => $sweepstake->id,
            'version_number' => 1,
            'status' => SweepstakeDraw::STATUS_ACTIVE,
            'pot_mode' => Sweepstake::POT_MODE_CUSTOM,
            'leftover_strategy' => null,
            'selected_team_count' => 22,
            'base_teams_per_member' => 3,
            'leftover_team_count' => 13,
        ]);

        $sweepstake->members->each(function (SweepstakeMember $member): void {
            $this->assertSame(3, $member->assignments()->count());
        });

        foreach ([1, 2, 3] as $potNumber) {
            $assignmentsForPot = TeamAssignment::where('sweepstake_id', $sweepstake->id)
                ->where('pot_number', $potNumber)
                ->get();

            $this->assertSame(3, $assignmentsForPot->count());
            $this->assertSame(3, $assignmentsForPot->pluck('sweepstake_member_id')->unique()->count());
        }

        $this->assertSame(9, TeamAssignment::where('sweepstake_id', $sweepstake->id)->pluck('team_id')->unique()->count());
        $this->assertNotNull(SweepstakeDraw::where('sweepstake_id', $sweepstake->id)->firstOrFail()->custom_pot_summary);
    }

    public function test_custom_pot_draw_gives_each_entrant_the_configured_number_from_each_pot(): void
    {
        $sweepstake = $this->createSweepstake(memberCount: 3, teamCount: 12);
        $sweepstake->update([
            'pot_mode' => Sweepstake::POT_MODE_CUSTOM,
        ]);
        $teams = $sweepstake->sweepstakeTeams()->orderBy('sort_order')->get();

        $this->createPot($sweepstake, 'Premium', $teams->take(7), teamsPerEntrant: 2);
        $this->createPot($sweepstake, 'Long shots', $teams->slice(7, 3)->values(), teamsPerEntrant: 1);

        app(RunRankedPotDraw::class)->handle($sweepstake->fresh());

        $memberAssignments = TeamAssignment::where('sweepstake_id', $sweepstake->id)->get();

        $memberAssignments
            ->groupBy('sweepstake_member_id')
            ->each(function ($assignments): void {
                $this->assertSame(3, $assignments->count());
                $this->assertSame(2, $assignments->where('pot_number', 1)->count());
                $this->assertSame(1, $assignments->where('pot_number', 2)->count());
            });
    }

    public function test_custom_pot_draw_rejects_active_pots_without_enough_teams(): void
    {
        $sweepstake = $this->createSweepstake(memberCount: 3, teamCount: 4);
        $sweepstake->update([
            'pot_mode' => Sweepstake::POT_MODE_CUSTOM,
        ]);
        $teams = $sweepstake->sweepstakeTeams()->orderBy('sort_order')->get();

        $this->createPot($sweepstake, 'Short pot', $teams, teamsPerEntrant: 2);

        $this->expectException(DrawException::class);
        $this->expectExceptionMessage('Short pot has 4 teams and needs 6 teams');

        app(RunRankedPotDraw::class)->handle($sweepstake->fresh());
    }

    public function test_custom_pot_draw_requires_at_least_one_active_pot(): void
    {
        $sweepstake = $this->createSweepstake(memberCount: 2, teamCount: 4);
        $sweepstake->update([
            'pot_mode' => Sweepstake::POT_MODE_CUSTOM,
        ]);
        $teams = $sweepstake->sweepstakeTeams()->orderBy('sort_order')->get();

        $this->createPot($sweepstake, 'Inactive pot', $teams, teamsPerEntrant: 0);

        $this->expectException(DrawException::class);
        $this->expectExceptionMessage('At least one custom pot must give entrants teams.');

        app(RunRankedPotDraw::class)->handle($sweepstake->fresh());
    }

    public function test_unassigned_included_teams_do_not_block_custom_pots_or_get_removed(): void
    {
        $sweepstake = $this->createSweepstake(memberCount: 2, teamCount: 5);
        $sweepstake->update([
            'pot_mode' => Sweepstake::POT_MODE_CUSTOM,
        ]);
        $teams = $sweepstake->sweepstakeTeams()->orderBy('sort_order')->get();
        $unassignedTeam = $teams->last();

        $this->createPot($sweepstake, 'Only pot', $teams->take(2), teamsPerEntrant: 1);

        app(RunRankedPotDraw::class)->handle($sweepstake->fresh());

        $this->assertDatabaseMissing('team_assignments', [
            'sweepstake_id' => $sweepstake->id,
            'team_id' => $unassignedTeam->team_id,
        ]);
        $this->assertDatabaseHas('sweepstake_teams', [
            'id' => $unassignedTeam->id,
            'is_included' => true,
            'is_removed' => false,
        ]);
    }

    public function test_custom_pot_draw_ignores_removed_assigned_teams(): void
    {
        $sweepstake = $this->createSweepstake(memberCount: 2, teamCount: 4);
        $sweepstake->update([
            'pot_mode' => Sweepstake::POT_MODE_CUSTOM,
        ]);
        $teams = $sweepstake->sweepstakeTeams()->orderBy('sort_order')->get();
        $removedTeam = $teams->last();

        $removedTeam->update([
            'is_included' => false,
            'is_removed' => true,
            'removed_reason' => 'Removed by admin',
        ]);

        $this->createPot($sweepstake, 'Valid pot', collect([$teams[0], $teams[1], $removedTeam]), teamsPerEntrant: 1);

        app(RunRankedPotDraw::class)->handle($sweepstake->fresh());

        $this->assertDatabaseMissing('team_assignments', [
            'sweepstake_id' => $sweepstake->id,
            'team_id' => $removedTeam->team_id,
        ]);
        $this->assertSame(2, TeamAssignment::where('sweepstake_id', $sweepstake->id)->count());
    }

    public function test_it_rejects_a_second_draw_without_reset(): void
    {
        $sweepstake = $this->createSweepstake(memberCount: 2, teamCount: 4);
        $draw = app(RunRankedPotDraw::class);

        $draw->handle($sweepstake);

        $this->expectException(DrawException::class);
        $this->expectExceptionMessage('already been drawn');

        $draw->handle($sweepstake->fresh());
    }

    public function test_it_can_rerun_a_draw_with_a_reason_and_keep_previous_assignments(): void
    {
        $sweepstake = $this->createSweepstake(memberCount: 2, teamCount: 4);
        $draw = app(RunRankedPotDraw::class);

        $draw->handle($sweepstake);
        $firstDraw = SweepstakeDraw::where('sweepstake_id', $sweepstake->id)->firstOrFail();

        $draw->handle($sweepstake->fresh(), 'Correcting a test draw');
        $secondDraw = SweepstakeDraw::where('sweepstake_id', $sweepstake->id)
            ->where('version_number', 2)
            ->firstOrFail();

        $this->assertSame(SweepstakeDraw::STATUS_SUPERSEDED, $firstDraw->fresh()->status);
        $this->assertSame(SweepstakeDraw::STATUS_ACTIVE, $secondDraw->status);
        $this->assertSame('Correcting a test draw', $secondDraw->reason);
        $this->assertSame($firstDraw->id, $secondDraw->rerun_of_draw_id);
        $this->assertSame(4, TeamAssignment::where('sweepstake_draw_id', $firstDraw->id)->count());
        $this->assertSame(4, TeamAssignment::where('sweepstake_draw_id', $secondDraw->id)->count());
    }

    public function test_it_does_not_assign_removed_teams(): void
    {
        $sweepstake = $this->createSweepstake(memberCount: 2, teamCount: 5);

        $removedTeam = $sweepstake->sweepstakeTeams()
            ->whereHas('team', fn ($query) => $query->where('fifa_ranking', 1))
            ->firstOrFail();

        $removedTeam->update([
            'is_included' => false,
            'is_removed' => true,
            'removed_reason' => 'Removed by admin',
        ]);

        app(RunRankedPotDraw::class)->handle($sweepstake);

        $this->assertDatabaseMissing('team_assignments', [
            'sweepstake_id' => $sweepstake->id,
            'team_id' => $removedTeam->team_id,
        ]);

        $this->assertSame(4, TeamAssignment::where('sweepstake_id', $sweepstake->id)->count());
    }

    public function test_manual_and_link_joined_entrants_are_drawn_the_same_way(): void
    {
        $sweepstake = $this->createSweepstake(memberCount: 0, teamCount: 6);

        SweepstakeMember::create([
            'sweepstake_id' => $sweepstake->id,
            'name' => 'Manual paid',
            'email' => 'manual-paid@example.test',
            'join_token' => 'manual-paid-token',
            'source' => SweepstakeMember::SOURCE_MANUAL,
            'is_paid' => true,
            'paid_at' => now(),
        ]);

        SweepstakeMember::create([
            'sweepstake_id' => $sweepstake->id,
            'name' => 'Manual unpaid',
            'email' => 'manual-unpaid@example.test',
            'join_token' => 'manual-unpaid-token',
            'source' => SweepstakeMember::SOURCE_MANUAL,
            'is_paid' => false,
        ]);

        SweepstakeMember::create([
            'sweepstake_id' => $sweepstake->id,
            'name' => 'Link unpaid',
            'email' => 'link-unpaid@example.test',
            'join_token' => 'link-unpaid-token',
            'source' => SweepstakeMember::SOURCE_JOIN_LINK,
            'is_paid' => false,
        ]);

        app(RunRankedPotDraw::class)->handle($sweepstake);

        $sweepstake->members->each(function (SweepstakeMember $member): void {
            $this->assertSame(2, $member->assignments()->count());
        });
    }

    private function createSweepstake(int $memberCount, int $teamCount, ?int $paidMemberCount = null): Sweepstake
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin'.uniqid().'@example.test',
            'password' => 'password',
        ]);

        $sweepstake = Sweepstake::create([
            'user_id' => $user->id,
            'name' => 'Office Sweepstake',
            'slug' => 'office-'.uniqid(),
            'join_code' => strtoupper(substr(uniqid(), -8)),
            'status' => Sweepstake::STATUS_OPEN,
            'draw_mode' => Sweepstake::DRAW_MODE_RANKED_POTS,
            'leftover_rule' => Sweepstake::LEFTOVER_REMOVE_LOWEST_RANKED,
        ]);

        $paidMemberCount ??= $memberCount;

        if ($memberCount > 0) {
            foreach (range(1, $memberCount) as $index) {
                SweepstakeMember::create([
                    'sweepstake_id' => $sweepstake->id,
                    'name' => "Member {$index}",
                    'email' => "member{$index}@example.test",
                    'join_token' => "token-{$sweepstake->id}-{$index}",
                    'source' => $index % 2 === 0 ? SweepstakeMember::SOURCE_JOIN_LINK : SweepstakeMember::SOURCE_MANUAL,
                    'is_paid' => $index <= $paidMemberCount,
                    'paid_at' => $index <= $paidMemberCount ? now() : null,
                ]);
            }
        }

        foreach (range(1, $teamCount) as $index) {
            $team = Team::create([
                'name' => "Team {$index}",
                'country_code' => sprintf('T%02d', $index),
                'fifa_ranking' => $index,
                'qualified_for_2026' => true,
            ]);

            SweepstakeTeam::create([
                'sweepstake_id' => $sweepstake->id,
                'team_id' => $team->id,
                'sort_order' => $index,
            ]);
        }

        return $sweepstake->fresh();
    }

    private function createPot(Sweepstake $sweepstake, string $name, iterable $teams, int $teamsPerEntrant = 1): SweepstakePot
    {
        $pot = SweepstakePot::create([
            'sweepstake_id' => $sweepstake->id,
            'name' => $name,
            'position' => (int) $sweepstake->pots()->max('position') + 1,
            'teams_per_entrant' => $teamsPerEntrant,
        ]);

        foreach (collect($teams)->values() as $index => $sweepstakeTeam) {
            SweepstakePotTeam::create([
                'sweepstake_pot_id' => $pot->id,
                'sweepstake_team_id' => $sweepstakeTeam->id,
                'position' => $index + 1,
            ]);
        }

        return $pot;
    }
}
