<?php

namespace Tests\Feature;

use App\Models\Prize;
use App\Models\Sweepstake;
use App\Models\SweepstakeDraw;
use App\Models\SweepstakeMember;
use App\Models\SweepstakePot;
use App\Models\SweepstakePotTeam;
use App\Models\SweepstakeTeam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SweepstakeDrawPrizeRequirementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_page_shows_settings_and_prizes_before_draw_and_warns_without_prizes(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2);

        $this->actingAs($admin)
            ->get(route('sweepstakes.show', ['sweepstake' => $sweepstake, 'tab' => 'draw-results']))
            ->assertOk()
            ->assertSeeInOrder(['Settings &amp; Prizes', 'Draw &amp; Results'], false)
            ->assertSee('data-active-tab="draw-results"', false)
            ->assertSee('Add at least one prize before running the draw.')
            ->assertSee('Prizes help entrants understand what they are playing for.')
            ->assertSee('Add prizes')
            ->assertDontSee('Run ranked pot draw');
    }

    public function test_auto_pot_draw_is_blocked_until_a_prize_exists(): void
    {
        Mail::fake();

        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2);
        $this->createMember($sweepstake, 'Alice Adams');
        $this->createMember($sweepstake, 'Bob Brown');

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.store', $sweepstake), [
                'leftover_team_strategy' => SweepstakeDraw::LEFTOVER_STRATEGY_REMOVE_LOWEST_RANKED,
                'tab' => 'draw-results',
            ])
            ->assertRedirect(route('sweepstakes.show', ['sweepstake' => $sweepstake, 'tab' => 'draw-results']))
            ->assertSessionHasErrors('draw');

        $this->assertDatabaseCount('sweepstake_draws', 0);

        $this->createPrize($sweepstake);

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.store', $sweepstake), [
                'leftover_team_strategy' => SweepstakeDraw::LEFTOVER_STRATEGY_REMOVE_LOWEST_RANKED,
                'tab' => 'draw-results',
            ])
            ->assertRedirect(route('sweepstakes.show', ['sweepstake' => $sweepstake, 'tab' => 'draw-results']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('sweepstake_draws', 1);
        $this->assertDatabaseCount('team_assignments', 2);
    }

    public function test_custom_pot_draw_is_blocked_until_a_prize_exists(): void
    {
        Mail::fake();

        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2, potMode: Sweepstake::POT_MODE_CUSTOM);
        $this->createMember($sweepstake, 'Alice Adams');
        $this->createMember($sweepstake, 'Bob Brown');
        $pot = $this->createPot($sweepstake, 'Seeds');

        $sweepstake->sweepstakeTeams()
            ->orderBy('sort_order')
            ->get()
            ->each(fn (SweepstakeTeam $team, int $index): SweepstakePotTeam => SweepstakePotTeam::create([
                'sweepstake_pot_id' => $pot->id,
                'sweepstake_team_id' => $team->id,
                'position' => $index + 1,
            ]));

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.store', $sweepstake), [
                'tab' => 'draw-results',
            ])
            ->assertRedirect(route('sweepstakes.show', ['sweepstake' => $sweepstake, 'tab' => 'draw-results']))
            ->assertSessionHasErrors('draw');

        $this->assertDatabaseCount('sweepstake_draws', 0);

        $this->createPrize($sweepstake);

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.store', $sweepstake), [
                'tab' => 'draw-results',
            ])
            ->assertRedirect(route('sweepstakes.show', ['sweepstake' => $sweepstake, 'tab' => 'draw-results']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('sweepstake_draws', 1);
        $this->assertDatabaseCount('team_assignments', 2);
    }

    public function test_rerun_is_blocked_safely_if_prizes_are_missing(): void
    {
        Mail::fake();

        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2);
        $this->createMember($sweepstake, 'Alice Adams');
        $this->createMember($sweepstake, 'Bob Brown');
        $prize = $this->createPrize($sweepstake);

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.store', $sweepstake), [
                'leftover_team_strategy' => SweepstakeDraw::LEFTOVER_STRATEGY_REMOVE_LOWEST_RANKED,
                'tab' => 'draw-results',
            ])
            ->assertSessionHasNoErrors();

        $prize->delete();

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.rerun', $sweepstake), [
                'reason' => 'Testing missing prize safety',
                'tab' => 'draw-results',
            ])
            ->assertRedirect(route('sweepstakes.show', ['sweepstake' => $sweepstake, 'tab' => 'draw-results']))
            ->assertSessionHasErrors('draw');

        $this->assertDatabaseCount('sweepstake_draws', 1);
    }

    private function createUser(string $email): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => $email,
            'password' => 'password',
        ]);
    }

    private function createSweepstake(
        User $admin,
        int $teamCount,
        string $potMode = Sweepstake::POT_MODE_AUTO,
    ): Sweepstake {
        $sweepstake = Sweepstake::create([
            'user_id' => $admin->id,
            'name' => 'Office Sweepstake',
            'slug' => 'office-'.uniqid(),
            'join_code' => strtoupper(substr(uniqid(), -8)),
            'status' => Sweepstake::STATUS_OPEN,
            'draw_mode' => Sweepstake::DRAW_MODE_RANKED_POTS,
            'pot_mode' => $potMode,
            'leftover_rule' => Sweepstake::LEFTOVER_REMOVE_LOWEST_RANKED,
        ]);

        foreach (range(1, $teamCount) as $index) {
            $team = Team::create([
                'name' => "Team {$index}",
                'country_code' => sprintf('D%02d', $index),
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

    private function createMember(Sweepstake $sweepstake, string $name): SweepstakeMember
    {
        return SweepstakeMember::create([
            'sweepstake_id' => $sweepstake->id,
            'name' => $name,
            'email' => str($name)->slug()->append('@example.test')->toString(),
            'join_token' => 'token-'.uniqid(),
            'source' => SweepstakeMember::SOURCE_MANUAL,
        ]);
    }

    private function createPot(Sweepstake $sweepstake, string $name): SweepstakePot
    {
        return SweepstakePot::create([
            'sweepstake_id' => $sweepstake->id,
            'name' => $name,
            'position' => 1,
            'teams_per_entrant' => 1,
        ]);
    }

    private function createPrize(Sweepstake $sweepstake): Prize
    {
        return Prize::create([
            'sweepstake_id' => $sweepstake->id,
            'position' => 1,
            'label' => 'Winner',
            'amount' => 20,
        ]);
    }
}
