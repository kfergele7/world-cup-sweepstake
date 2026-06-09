<?php

namespace Tests\Feature;

use App\Actions\RunRankedPotDraw;
use App\Models\Sweepstake;
use App\Models\SweepstakeMember;
use App\Models\SweepstakePot;
use App\Models\SweepstakePotTeam;
use App\Models\SweepstakeTeam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SweepstakePotManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_page_shows_custom_pot_manager_when_custom_pots_are_selected(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2);

        $this->actingAs($admin)
            ->get(route('sweepstakes.show', $sweepstake))
            ->assertOk()
            ->assertSee('Add at least one prize before running the draw.')
            ->assertDontSee('Run custom pot draw')
            ->assertSee('Overview')
            ->assertSee('Entrants')
            ->assertSee('Teams')
            ->assertSee('Pots')
            ->assertSee('Draw &amp; Results', false)
            ->assertSee('Settings &amp; Prizes', false)
            ->assertSee('Custom pots')
            ->assertSee('Team pot assignments')
            ->assertSee('Select multiple teams and move them into a pot in one go.')
            ->assertSee('Move selected teams')
            ->assertSee('Move to Unassigned')
            ->assertSee('Clear selection')
            ->assertSee('Unassigned')
            ->assertSee('Only teams assigned to a custom pot are included in a custom draw.')
            ->assertSee('Create at least one custom pot before running the draw.')
            ->assertDontSee('Leftover teams');
    }

    public function test_auto_pots_mode_shows_a_simple_pots_explanation(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2, potMode: Sweepstake::POT_MODE_AUTO);

        $this->actingAs($admin)
            ->get(route('sweepstakes.show', $sweepstake))
            ->assertOk()
            ->assertSee('SweepKit will create pots automatically using stored rankings.')
            ->assertSee('Switch to Custom pots in settings')
            ->assertDontSee('Team pot assignments');
    }

    public function test_custom_pot_summary_shows_how_many_teams_will_be_drawn_and_left_out(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 9);
        $this->createMember($sweepstake, 'First Entrant');
        $this->createMember($sweepstake, 'Second Entrant');
        $this->createMember($sweepstake, 'Third Entrant');
        $pot = $this->createPot($sweepstake, 'Favourites', teamsPerEntrant: 1);

        $sweepstake->sweepstakeTeams()
            ->orderBy('sort_order')
            ->get()
            ->each(fn (SweepstakeTeam $sweepstakeTeam, int $index): SweepstakePotTeam => SweepstakePotTeam::create([
                'sweepstake_pot_id' => $pot->id,
                'sweepstake_team_id' => $sweepstakeTeam->id,
                'position' => $index + 1,
            ]));

        $this->actingAs($admin)
            ->get(route('sweepstakes.show', $sweepstake))
            ->assertOk()
            ->assertSee('9 teams assigned')
            ->assertSee('1 team per entrant')
            ->assertSee('3 teams will be drawn and 6 will be left out.');
    }

    public function test_owning_admin_can_manage_custom_pots_and_assign_selected_teams(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 4);
        $teams = $sweepstake->sweepstakeTeams()->orderBy('sort_order')->get();

        $this->actingAs($admin)
            ->post(route('sweepstakes.pots.store', $sweepstake), [
                'name' => 'Original seeds',
                'teams_per_entrant' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $firstPot = SweepstakePot::where('sweepstake_id', $sweepstake->id)->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('sweepstakes.pots.update', [$sweepstake, $firstPot]), [
                'name' => 'Top seeds',
                'position' => 2,
                'teams_per_entrant' => 2,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $firstPot->refresh();

        $this->assertSame('Top seeds', $firstPot->name);
        $this->assertSame(2, $firstPot->position);
        $this->assertSame(2, $firstPot->teams_per_entrant);

        $this->actingAs($admin)
            ->post(route('sweepstakes.pots.store', $sweepstake), [
                'name' => 'Second seeds',
                'teams_per_entrant' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $secondPot = SweepstakePot::where('sweepstake_id', $sweepstake->id)
            ->where('name', 'Second seeds')
            ->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('sweepstakes.pots.assignments', $sweepstake), [
                'assignments' => [
                    $teams[0]->id => $firstPot->id,
                    $teams[1]->id => $firstPot->id,
                    $teams[2]->id => $secondPot->id,
                    $teams[3]->id => '',
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sweepstake_pot_teams', [
            'sweepstake_pot_id' => $firstPot->id,
            'sweepstake_team_id' => $teams[0]->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('sweepstake_pot_teams', [
            'sweepstake_pot_id' => $firstPot->id,
            'sweepstake_team_id' => $teams[1]->id,
            'position' => 2,
        ]);
        $this->assertDatabaseMissing('sweepstake_pot_teams', [
            'sweepstake_team_id' => $teams[3]->id,
        ]);
    }

    public function test_admin_can_bulk_assign_selected_teams_to_a_custom_pot_and_run_draw(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 4);
        $this->createMember($sweepstake, 'First Entrant');
        $this->createMember($sweepstake, 'Second Entrant');
        $pot = $this->createPot($sweepstake, 'Top seeds');
        $teams = $sweepstake->sweepstakeTeams()->orderBy('sort_order')->take(2)->get();

        $this->actingAs($admin)
            ->patch(route('sweepstakes.pots.bulk-assignments', $sweepstake), [
                'team_ids' => $teams->pluck('id')->all(),
                'target_pot_id' => $pot->id,
                'tab' => 'pots',
            ])
            ->assertRedirect(route('sweepstakes.show', ['sweepstake' => $sweepstake, 'tab' => 'pots']));

        foreach ($teams as $index => $team) {
            $this->assertDatabaseHas('sweepstake_pot_teams', [
                'sweepstake_pot_id' => $pot->id,
                'sweepstake_team_id' => $team->id,
                'position' => $index + 1,
            ]);
        }

        app(RunRankedPotDraw::class)->handle($sweepstake);

        $this->assertSame(2, $sweepstake->assignments()->count());
    }

    public function test_admin_can_bulk_move_selected_teams_back_to_unassigned(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 3);
        $pot = $this->createPot($sweepstake, 'Seeds');
        $teams = $sweepstake->sweepstakeTeams()->orderBy('sort_order')->take(2)->get();

        foreach ($teams as $index => $team) {
            SweepstakePotTeam::create([
                'sweepstake_pot_id' => $pot->id,
                'sweepstake_team_id' => $team->id,
                'position' => $index + 1,
            ]);
        }

        $this->actingAs($admin)
            ->patch(route('sweepstakes.pots.bulk-assignments', $sweepstake), [
                'team_ids' => [$teams[0]->id],
                'target_pot_id' => '',
                'tab' => 'pots',
            ])
            ->assertRedirect(route('sweepstakes.show', ['sweepstake' => $sweepstake, 'tab' => 'pots']));

        $this->assertDatabaseMissing('sweepstake_pot_teams', [
            'sweepstake_team_id' => $teams[0]->id,
        ]);
        $this->assertDatabaseHas('sweepstake_pot_teams', [
            'sweepstake_team_id' => $teams[1]->id,
        ]);
    }

    public function test_another_admin_and_guests_cannot_bulk_assign_custom_pot_teams(): void
    {
        $owner = $this->createUser('owner@example.test');
        $otherAdmin = $this->createUser('other@example.test');
        $sweepstake = $this->createSweepstake($owner, teamCount: 2);
        $pot = $this->createPot($sweepstake, 'Seeds');
        $team = $sweepstake->sweepstakeTeams()->firstOrFail();

        $this->patch(route('sweepstakes.pots.bulk-assignments', $sweepstake), [
            'team_ids' => [$team->id],
            'target_pot_id' => $pot->id,
            'tab' => 'pots',
        ])->assertRedirect(route('login'));

        $this->actingAs($otherAdmin)
            ->patch(route('sweepstakes.pots.bulk-assignments', $sweepstake), [
                'team_ids' => [$team->id],
                'target_pot_id' => $pot->id,
                'tab' => 'pots',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('sweepstake_pot_teams', [
            'sweepstake_team_id' => $team->id,
        ]);
    }

    public function test_removed_teams_cannot_be_assigned_through_bulk_actions(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2);
        $pot = $this->createPot($sweepstake, 'Seeds');
        $removedTeam = $sweepstake->sweepstakeTeams()->orderBy('sort_order')->firstOrFail();

        $removedTeam->update([
            'is_included' => false,
            'is_removed' => true,
            'removed_reason' => 'Removed by admin',
        ]);

        $this->actingAs($admin)
            ->patch(route('sweepstakes.pots.bulk-assignments', $sweepstake), [
                'team_ids' => [$removedTeam->id],
                'target_pot_id' => $pot->id,
                'tab' => 'pots',
            ])
            ->assertRedirect(route('sweepstakes.show', ['sweepstake' => $sweepstake, 'tab' => 'pots']))
            ->assertSessionHasErrors('custom_pots');

        $this->assertDatabaseMissing('sweepstake_pot_teams', [
            'sweepstake_team_id' => $removedTeam->id,
        ]);
    }

    public function test_bulk_pot_assignment_is_locked_after_active_draw_and_reopens_after_cancellation(): void
    {
        Mail::fake();

        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2, potMode: Sweepstake::POT_MODE_AUTO);
        $this->createMember($sweepstake, 'First Entrant');
        $this->createMember($sweepstake, 'Second Entrant');
        $pot = $this->createPot($sweepstake, 'Seeds');
        $team = $sweepstake->sweepstakeTeams()->firstOrFail();

        app(RunRankedPotDraw::class)->handle($sweepstake);

        $this->actingAs($admin)
            ->patch(route('sweepstakes.pots.bulk-assignments', $sweepstake), [
                'team_ids' => [$team->id],
                'target_pot_id' => $pot->id,
                'tab' => 'pots',
            ])
            ->assertRedirect(route('sweepstakes.show', ['sweepstake' => $sweepstake, 'tab' => 'pots']))
            ->assertSessionHasErrors('custom_pots');

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.cancel', $sweepstake), [
                'reason' => 'Setup reopened',
                'tab' => 'draw-results',
            ])
            ->assertRedirect(route('sweepstakes.show', ['sweepstake' => $sweepstake, 'tab' => 'draw-results']));

        $this->actingAs($admin)
            ->patch(route('sweepstakes.pots.bulk-assignments', $sweepstake), [
                'team_ids' => [$team->id],
                'target_pot_id' => $pot->id,
                'tab' => 'pots',
            ])
            ->assertRedirect(route('sweepstakes.show', ['sweepstake' => $sweepstake, 'tab' => 'pots']));

        $this->assertDatabaseHas('sweepstake_pot_teams', [
            'sweepstake_pot_id' => $pot->id,
            'sweepstake_team_id' => $team->id,
        ]);
    }

    public function test_empty_custom_pots_can_be_deleted_but_non_empty_pots_are_kept(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2);
        $team = $sweepstake->sweepstakeTeams()->firstOrFail();
        $filledPot = $this->createPot($sweepstake, 'Filled pot');
        $emptyPot = $this->createPot($sweepstake, 'Empty pot');

        SweepstakePotTeam::create([
            'sweepstake_pot_id' => $filledPot->id,
            'sweepstake_team_id' => $team->id,
            'position' => 1,
        ]);

        $this->actingAs($admin)
            ->delete(route('sweepstakes.pots.destroy', [$sweepstake, $filledPot]))
            ->assertRedirect()
            ->assertSessionHasErrors('custom_pots');

        $this->assertDatabaseHas('sweepstake_pots', [
            'id' => $filledPot->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('sweepstakes.pots.destroy', [$sweepstake, $emptyPot]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('sweepstake_pots', [
            'id' => $emptyPot->id,
        ]);
    }

    public function test_removed_teams_cannot_be_assigned_to_custom_pots(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2);
        $pot = $this->createPot($sweepstake, 'Seeds');
        $removedTeam = $sweepstake->sweepstakeTeams()->orderBy('sort_order')->firstOrFail();

        $removedTeam->update([
            'is_included' => false,
            'is_removed' => true,
            'removed_reason' => 'Removed by admin',
        ]);

        $this->actingAs($admin)
            ->patch(route('sweepstakes.pots.assignments', $sweepstake), [
                'assignments' => [
                    $removedTeam->id => $pot->id,
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('custom_pots');

        $this->assertDatabaseMissing('sweepstake_pot_teams', [
            'sweepstake_team_id' => $removedTeam->id,
        ]);
    }

    public function test_another_admin_cannot_manage_someone_elses_custom_pots(): void
    {
        $owner = $this->createUser('owner@example.test');
        $otherAdmin = $this->createUser('other@example.test');
        $sweepstake = $this->createSweepstake($owner, teamCount: 2);

        $this->actingAs($otherAdmin)
            ->post(route('sweepstakes.pots.store', $sweepstake), [
                'name' => 'Hijacked pot',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('sweepstake_pots', [
            'sweepstake_id' => $sweepstake->id,
            'name' => 'Hijacked pot',
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

    private function createSweepstake(User $admin, int $teamCount, string $potMode = Sweepstake::POT_MODE_CUSTOM): Sweepstake
    {
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
                'country_code' => sprintf('P%02d', $index),
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

    private function createPot(Sweepstake $sweepstake, string $name, int $teamsPerEntrant = 1): SweepstakePot
    {
        return SweepstakePot::create([
            'sweepstake_id' => $sweepstake->id,
            'name' => $name,
            'position' => (int) $sweepstake->pots()->max('position') + 1,
            'teams_per_entrant' => $teamsPerEntrant,
        ]);
    }
}
