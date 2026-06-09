<?php

namespace Tests\Feature;

use App\Models\Sweepstake;
use App\Models\SweepstakePot;
use App\Models\SweepstakePotTeam;
use App\Models\SweepstakeTeam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Run custom pot draw')
            ->assertSee('Custom pots')
            ->assertSee('Team pot assignments')
            ->assertSee('Unassigned')
            ->assertSee('Create at least one custom pot before running the draw.')
            ->assertDontSee('Leftover teams');
    }

    public function test_owning_admin_can_manage_custom_pots_and_assign_selected_teams(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 4);
        $teams = $sweepstake->sweepstakeTeams()->orderBy('sort_order')->get();

        $this->actingAs($admin)
            ->post(route('sweepstakes.pots.store', $sweepstake), [
                'name' => 'Original seeds',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $firstPot = SweepstakePot::where('sweepstake_id', $sweepstake->id)->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('sweepstakes.pots.update', [$sweepstake, $firstPot]), [
                'name' => 'Top seeds',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('Top seeds', $firstPot->fresh()->name);

        $this->actingAs($admin)
            ->post(route('sweepstakes.pots.store', $sweepstake), [
                'name' => 'Second seeds',
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

    private function createSweepstake(User $admin, int $teamCount): Sweepstake
    {
        $sweepstake = Sweepstake::create([
            'user_id' => $admin->id,
            'name' => 'Office Sweepstake',
            'slug' => 'office-'.uniqid(),
            'join_code' => strtoupper(substr(uniqid(), -8)),
            'status' => Sweepstake::STATUS_OPEN,
            'draw_mode' => Sweepstake::DRAW_MODE_RANKED_POTS,
            'pot_mode' => Sweepstake::POT_MODE_CUSTOM,
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

    private function createPot(Sweepstake $sweepstake, string $name): SweepstakePot
    {
        return SweepstakePot::create([
            'sweepstake_id' => $sweepstake->id,
            'name' => $name,
            'position' => (int) $sweepstake->pots()->max('position') + 1,
        ]);
    }
}
