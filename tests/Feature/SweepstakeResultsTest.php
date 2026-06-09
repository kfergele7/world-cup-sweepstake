<?php

namespace Tests\Feature;

use App\Models\Sweepstake;
use App\Models\SweepstakeDraw;
use App\Models\SweepstakeMember;
use App\Models\SweepstakeTeam;
use App\Models\Team;
use App\Models\TeamAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SweepstakeResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owning_admin_can_view_grouped_draw_results_after_the_draw(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 4);
        $alice = $this->createMember($sweepstake, 'Alice Adams', isPaid: true);
        $bob = $this->createMember($sweepstake, 'Bob Brown', source: SweepstakeMember::SOURCE_JOIN_LINK);
        $teams = Team::orderBy('id')->get();

        $this->drawSweepstake($sweepstake, [
            [$alice, $teams[0], 1],
            [$bob, $teams[1], 1],
            [$alice, $teams[2], 2],
            [$bob, $teams[3], 2],
        ]);

        $this->actingAs($admin)
            ->get(route('sweepstakes.show', $sweepstake))
            ->assertOk()
            ->assertSee('Draw results')
            ->assertSee('Active draw #1 results grouped by entrant.')
            ->assertSee('Alice Adams has 2 teams')
            ->assertSee('Bob Brown has 2 teams')
            ->assertSee('Team 1')
            ->assertSee('Pot 1')
            ->assertSee('Paid')
            ->assertSee('Joined by link');
    }

    public function test_another_admin_cannot_view_someone_elses_admin_results(): void
    {
        $owner = $this->createUser('owner@example.test');
        $otherAdmin = $this->createUser('other@example.test');
        $sweepstake = $this->createSweepstake($owner, teamCount: 2);

        $this->actingAs($otherAdmin)
            ->get(route('sweepstakes.show', $sweepstake))
            ->assertForbidden();
    }

    public function test_admin_page_shows_setup_guidance_before_the_draw(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2);
        $this->createMember($sweepstake, 'Alice Adams');

        $this->actingAs($admin)
            ->get(route('sweepstakes.show', $sweepstake))
            ->assertOk()
            ->assertSee('The draw has not been run yet.')
            ->assertSee('Run the draw to assign teams to entrants.')
            ->assertDontSee('Alice Adams has 1 team');
    }

    public function test_entrant_cards_show_a_private_button_without_plain_long_token_url(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2);
        $member = $this->createMember($sweepstake, 'Manual Entrant', token: 'manual-private-token');

        $this->actingAs($admin)
            ->get(route('sweepstakes.show', $sweepstake))
            ->assertOk()
            ->assertSee('View team page')
            ->assertSee('Copy link')
            ->assertSee('data-copy-button', false)
            ->assertSee('data-manage-toggle', false)
            ->assertSee('Manage')
            ->assertSee('Cancel')
            ->assertDontSeeText(route('entrants.show', $member->join_token))
            ->assertSee(route('entrants.show', $member->join_token), false);
    }

    public function test_link_joined_entrant_can_view_their_private_waiting_page_by_secure_token(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2);
        $member = $this->createMember(
            $sweepstake,
            'Link Entrant',
            email: 'link@example.test',
            token: 'link-private-token',
            source: SweepstakeMember::SOURCE_JOIN_LINK,
        );

        $this->get(route('entrants.show', $member->join_token))
            ->assertOk()
            ->assertSee($sweepstake->name)
            ->assertSee('Entrant teams')
            ->assertSee('Link Entrant')
            ->assertSeeText("You're entered. Your teams will appear here after the draw.")
            ->assertDontSee('Dashboard')
            ->assertDontSee('link@example.test')
            ->assertDontSee('Run ranked pot draw');
    }

    public function test_entrant_private_page_shows_only_their_own_assigned_teams_after_the_draw(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2);
        $alice = $this->createMember($sweepstake, 'Alice Adams', email: 'alice@example.test', token: 'alice-token');
        $bob = $this->createMember($sweepstake, 'Bob Brown', email: 'bob@example.test', token: 'bob-token');
        $teams = Team::orderBy('id')->get();

        $this->drawSweepstake($sweepstake, [
            [$alice, $teams[0], 1],
            [$bob, $teams[1], 1],
        ]);

        $this->get(route('entrants.show', $alice->join_token))
            ->assertOk()
            ->assertSee('Your teams are ready')
            ->assertSee('Active draw #1')
            ->assertSee('Team 1')
            ->assertSee('Flag 1')
            ->assertSee('Pot 1')
            ->assertDontSee('Team 2')
            ->assertDontSee('alice@example.test')
            ->assertDontSee('bob@example.test')
            ->assertDontSee('Run ranked pot draw')
            ->assertDontSee('Private entrant view');
    }

    public function test_admin_and_entrant_can_see_draw_history_after_a_rerun(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2);
        $alice = $this->createMember($sweepstake, 'Alice Adams', email: 'alice@example.test', token: 'alice-token');
        $bob = $this->createMember($sweepstake, 'Bob Brown', email: 'bob@example.test', token: 'bob-token');
        $teams = Team::orderBy('id')->get();

        $firstDraw = $this->drawSweepstake($sweepstake, [
            [$alice, $teams[0], 1],
            [$bob, $teams[1], 1],
        ]);

        $firstDraw->update(['status' => SweepstakeDraw::STATUS_SUPERSEDED]);

        $this->drawSweepstake($sweepstake, [
            [$alice, $teams[1], 1],
            [$bob, $teams[0], 1],
        ], versionNumber: 2, reason: 'Ryan was missed from the entrant list');

        $this->actingAs($admin)
            ->get(route('sweepstakes.show', $sweepstake))
            ->assertOk()
            ->assertSee('Draw history')
            ->assertSee('Draw #1')
            ->assertSee('Superseded')
            ->assertSee('Draw #2')
            ->assertSee('Active draw')
            ->assertSee('Draw rule: Auto pots')
            ->assertSee('Reason: Ryan was missed from the entrant list')
            ->assertSee('Bob Brown');

        $this->get(route('entrants.show', $alice->join_token))
            ->assertOk()
            ->assertSee('Draw history')
            ->assertSee('Superseded')
            ->assertSee('Active draw')
            ->assertSee('Draw rule: Auto pots')
            ->assertSee('Reason: Ryan was missed from the entrant list')
            ->assertSee('Team 2')
            ->assertDontSee('bob@example.test')
            ->assertDontSee('Bob Brown');
    }

    public function test_unknown_entrant_token_returns_not_found(): void
    {
        $this->get(route('entrants.show', 'unknown-token'))->assertNotFound();
    }

    public function test_manually_added_entrant_can_use_their_private_view_token(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2);
        $member = $this->createMember($sweepstake, 'Manual Entrant', token: 'manual-token');

        $this->get(route('entrants.show', $member->join_token))
            ->assertOk()
            ->assertSee('Manual Entrant')
            ->assertSeeText("You're entered. Your teams will appear here after the draw.");
    }

    public function test_authenticated_owner_gets_admin_breadcrumb_links_on_entrant_page(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2);
        $member = $this->createMember($sweepstake, 'Manual Entrant', token: 'manual-token');

        $this->actingAs($admin)
            ->get(route('entrants.show', $member->join_token))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee(route('sweepstakes.show', $sweepstake), false)
            ->assertSee('Entrant teams');
    }

    public function test_team_names_render_flags_from_country_codes_and_unknown_codes_do_not_break(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2);
        $alice = $this->createMember($sweepstake, 'Alice Adams', token: 'alice-token');
        $bob = $this->createMember($sweepstake, 'Bob Brown', token: 'bob-token');
        $teams = Team::orderBy('id')->get();

        $teams[0]->update([
            'name' => 'Argentina',
            'country_code' => 'ARG',
            'flag' => null,
        ]);
        $teams[1]->update([
            'name' => 'Unknown Team',
            'country_code' => 'ZZZ',
            'flag' => null,
        ]);

        $this->drawSweepstake($sweepstake, [
            [$alice, $teams[0]->fresh(), 1],
            [$bob, $teams[1]->fresh(), 1],
        ]);

        $this->get(route('entrants.show', $alice->join_token))
            ->assertOk()
            ->assertSee('🇦🇷')
            ->assertSee('Argentina')
            ->assertDontSee('Unknown Team');

        $this->get(route('entrants.show', $bob->join_token))
            ->assertOk()
            ->assertSee('Unknown Team');
    }

    public function test_admin_page_includes_confirmation_hooks_for_important_actions(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 4);
        $this->createMember($sweepstake, 'Alice Adams');
        $this->createMember($sweepstake, 'Bob Brown');

        $this->actingAs($admin)
            ->get(route('sweepstakes.show', $sweepstake))
            ->assertOk()
            ->assertSee('data-confirm-form', false)
            ->assertSee('data-confirm-title="Run draw"', false)
            ->assertSee('data-confirm-title="Remove entrant"', false)
            ->assertSee('data-confirm-title="Remove selected teams"', false);
    }

    private function createUser(string $email): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => $email,
            'password' => 'password',
        ]);
    }

    private function createSweepstake(User $admin, int $teamCount): Sweepstake
    {
        $sweepstake = Sweepstake::create([
            'user_id' => $admin->id,
            'name' => 'Office Sweepstake',
            'slug' => 'office-'.uniqid(),
            'join_code' => strtoupper(substr(uniqid(), -8)),
            'status' => Sweepstake::STATUS_OPEN,
            'draw_mode' => Sweepstake::DRAW_MODE_RANKED_POTS,
            'leftover_rule' => Sweepstake::LEFTOVER_REMOVE_LOWEST_RANKED,
        ]);

        foreach (range(1, $teamCount) as $index) {
            $team = Team::create([
                'name' => "Team {$index}",
                'country_code' => sprintf('R%02d', $index),
                'flag' => "Flag {$index}",
                'fifa_ranking' => $index,
                'qualified_for_2026' => true,
            ]);

            SweepstakeTeam::create([
                'sweepstake_id' => $sweepstake->id,
                'team_id' => $team->id,
                'sort_order' => $index,
            ]);
        }

        return $sweepstake;
    }

    private function createMember(
        Sweepstake $sweepstake,
        string $name,
        ?string $email = null,
        ?string $token = null,
        string $source = SweepstakeMember::SOURCE_MANUAL,
        bool $isPaid = false,
    ): SweepstakeMember {
        return SweepstakeMember::create([
            'sweepstake_id' => $sweepstake->id,
            'name' => $name,
            'email' => $email ?? str($name)->slug()->append('@example.test')->toString(),
            'join_token' => $token ?? 'token-'.uniqid(),
            'source' => $source,
            'is_paid' => $isPaid,
            'paid_at' => $isPaid ? now() : null,
        ]);
    }

    /**
     * @param  array<int, array{0: SweepstakeMember, 1: Team, 2: int}>  $assignments
     */
    private function drawSweepstake(Sweepstake $sweepstake, array $assignments, int $versionNumber = 1, ?string $reason = null): SweepstakeDraw
    {
        $assignedAt = now();
        $draw = SweepstakeDraw::create([
            'sweepstake_id' => $sweepstake->id,
            'version_number' => $versionNumber,
            'status' => SweepstakeDraw::STATUS_ACTIVE,
            'reason' => $reason,
            'ran_at' => $assignedAt,
        ]);

        foreach ($assignments as [$member, $team, $potNumber]) {
            TeamAssignment::create([
                'sweepstake_draw_id' => $draw->id,
                'sweepstake_id' => $sweepstake->id,
                'sweepstake_member_id' => $member->id,
                'team_id' => $team->id,
                'pot_number' => $potNumber,
                'assigned_at' => $assignedAt,
            ]);
        }

        $sweepstake->forceFill([
            'status' => Sweepstake::STATUS_DRAWN,
            'teams_per_member' => collect($assignments)
                ->groupBy(fn (array $assignment): int => $assignment[0]->id)
                ->max(fn ($memberAssignments): int => $memberAssignments->count()),
            'drawn_at' => $assignedAt,
        ])->save();

        return $draw;
    }
}
