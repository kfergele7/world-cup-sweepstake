<?php

namespace Tests\Feature;

use App\Models\Prize;
use App\Models\Sweepstake;
use App\Models\SweepstakeMember;
use App\Models\SweepstakePot;
use App\Models\SweepstakeTeam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SweepstakeAdminTabPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_page_can_open_with_a_tab_query_or_flash_state(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2);

        $this->actingAs($admin)
            ->get(route('sweepstakes.show', ['sweepstake' => $sweepstake, 'tab' => 'pots']))
            ->assertOk()
            ->assertSee('data-active-tab="pots"', false)
            ->assertSee('aria-current="page"', false);

        $this->withSession(['active_tab' => 'teams'])
            ->actingAs($admin)
            ->get(route('sweepstakes.show', $sweepstake))
            ->assertOk()
            ->assertSee('data-active-tab="teams"', false);
    }

    public function test_entrant_actions_redirect_back_to_the_entrants_tab(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 4);

        $this->actingAs($admin)
            ->post(route('sweepstakes.members.store', $sweepstake), [
                'name' => 'First Entrant',
                'email' => 'first@example.test',
                'tab' => 'entrants',
            ])
            ->assertRedirect($this->tabUrl($sweepstake, 'entrants'))
            ->assertSessionHasNoErrors();

        $member = SweepstakeMember::where('sweepstake_id', $sweepstake->id)->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('sweepstakes.members.update', [$sweepstake, $member]), [
                'name' => 'Updated Entrant',
                'email' => 'updated@example.test',
                'tab' => 'entrants',
            ])
            ->assertRedirect($this->tabUrl($sweepstake, 'entrants'))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->patch(route('sweepstakes.members.payment.update', [$sweepstake, $member]), [
                'is_paid' => '1',
                'tab' => 'entrants',
            ])
            ->assertRedirect($this->tabUrl($sweepstake, 'entrants'))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->delete(route('sweepstakes.members.destroy', [$sweepstake, $member]), [
                'tab' => 'entrants',
            ])
            ->assertRedirect($this->tabUrl($sweepstake, 'entrants'))
            ->assertSessionHasNoErrors();
    }

    public function test_team_actions_redirect_back_to_the_teams_tab(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2);
        $team = $sweepstake->sweepstakeTeams()->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('sweepstakes.teams.bulk.update', $sweepstake), [
                'action' => 'remove',
                'team_ids' => [$team->id],
                'tab' => 'teams',
            ])
            ->assertRedirect($this->tabUrl($sweepstake, 'teams'))
            ->assertSessionHasNoErrors();
    }

    public function test_pot_actions_redirect_back_to_the_pots_tab(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 4);
        $teams = $sweepstake->sweepstakeTeams()->orderBy('sort_order')->get();

        $this->actingAs($admin)
            ->post(route('sweepstakes.pots.store', $sweepstake), [
                'name' => 'Seeds',
                'teams_per_entrant' => 1,
                'tab' => 'pots',
            ])
            ->assertRedirect($this->tabUrl($sweepstake, 'pots'))
            ->assertSessionHasNoErrors();

        $pot = SweepstakePot::where('sweepstake_id', $sweepstake->id)->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('sweepstakes.pots.update', [$sweepstake, $pot]), [
                'name' => 'Top seeds',
                'position' => 1,
                'teams_per_entrant' => 1,
                'tab' => 'pots',
            ])
            ->assertRedirect($this->tabUrl($sweepstake, 'pots'))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->patch(route('sweepstakes.pots.bulk-assignments', $sweepstake), [
                'team_ids' => [$teams[0]->id, $teams[1]->id],
                'target_pot_id' => $pot->id,
                'tab' => 'pots',
            ])
            ->assertRedirect($this->tabUrl($sweepstake, 'pots'))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->patch(route('sweepstakes.pots.assignments', $sweepstake), [
                'assignments' => [
                    $teams[0]->id => $pot->id,
                    $teams[1]->id => '',
                ],
                'tab' => 'pots',
            ])
            ->assertRedirect($this->tabUrl($sweepstake, 'pots'))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->patch(route('sweepstakes.pots.bulk-assignments', $sweepstake), [
                'team_ids' => [$teams[0]->id],
                'target_pot_id' => '',
                'tab' => 'pots',
            ])
            ->assertRedirect($this->tabUrl($sweepstake, 'pots'))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->delete(route('sweepstakes.pots.destroy', [$sweepstake, $pot]), [
                'tab' => 'pots',
            ])
            ->assertRedirect($this->tabUrl($sweepstake, 'pots'))
            ->assertSessionHasNoErrors();
    }

    public function test_settings_and_prize_actions_redirect_back_to_settings_and_prizes_tab(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2);
        $prize = Prize::create([
            'sweepstake_id' => $sweepstake->id,
            'position' => 1,
            'label' => 'Winner',
            'amount' => 20,
        ]);

        $this->actingAs($admin)
            ->patch(route('sweepstakes.settings.update', $sweepstake), [
                'sweepstake_name' => 'Office Sweepstake',
                'entry_fee' => '5',
                'currency' => 'GBP',
                'status' => Sweepstake::STATUS_OPEN,
                'pot_mode' => Sweepstake::POT_MODE_CUSTOM,
                'tab' => 'settings-prizes',
            ])
            ->assertRedirect($this->tabUrl($sweepstake, 'settings-prizes'))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('sweepstakes.prizes.store', $sweepstake), [
                'position' => 2,
                'label' => 'Runner-up',
                'amount' => 10,
                'tab' => 'settings-prizes',
            ])
            ->assertRedirect($this->tabUrl($sweepstake, 'settings-prizes'))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->patch(route('sweepstakes.prizes.update', $sweepstake), [
                'prizes' => [
                    $prize->id => [
                        'id' => $prize->id,
                        'position' => 1,
                        'label' => 'Champion',
                        'amount' => 30,
                    ],
                ],
                'tab' => 'settings-prizes',
            ])
            ->assertRedirect($this->tabUrl($sweepstake, 'settings-prizes'))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->delete(route('sweepstakes.prizes.destroy', [$sweepstake, $prize]), [
                'tab' => 'settings-prizes',
            ])
            ->assertRedirect($this->tabUrl($sweepstake, 'settings-prizes'))
            ->assertSessionHasNoErrors();
    }

    public function test_draw_actions_redirect_back_to_the_draw_results_tab(): void
    {
        Mail::fake();

        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 4, potMode: Sweepstake::POT_MODE_AUTO);
        $this->createMember($sweepstake, 'First Entrant');
        $this->createMember($sweepstake, 'Second Entrant');

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.store', $sweepstake), [
                'tab' => 'draw-results',
            ])
            ->assertRedirect($this->tabUrl($sweepstake, 'draw-results'))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.rerun', $sweepstake), [
                'reason' => 'Correcting a setup mistake',
                'tab' => 'draw-results',
            ])
            ->assertRedirect($this->tabUrl($sweepstake, 'draw-results'))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.cancel', $sweepstake), [
                'reason' => 'Need to add one more entrant',
                'tab' => 'draw-results',
            ])
            ->assertRedirect($this->tabUrl($sweepstake, 'draw-results'))
            ->assertSessionHasNoErrors();
    }

    public function test_validation_errors_can_show_the_relevant_tab_from_old_input(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2);

        $this->actingAs($admin)
            ->from(route('sweepstakes.show', $sweepstake))
            ->post(route('sweepstakes.members.store', $sweepstake), [
                'name' => '',
                'email' => 'not-an-email',
                'tab' => 'entrants',
            ])
            ->assertRedirect(route('sweepstakes.show', $sweepstake))
            ->assertSessionHasErrors(['name', 'email']);

        $this->actingAs($admin)
            ->get(route('sweepstakes.show', $sweepstake))
            ->assertOk()
            ->assertSee('data-active-tab="entrants"', false);
    }

    private function tabUrl(Sweepstake $sweepstake, string $tab): string
    {
        return route('sweepstakes.show', [
            'sweepstake' => $sweepstake,
            'tab' => $tab,
        ]);
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
        string $potMode = Sweepstake::POT_MODE_CUSTOM,
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
}
