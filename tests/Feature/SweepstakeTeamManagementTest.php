<?php

namespace Tests\Feature;

use App\Actions\RunRankedPotDraw;
use App\Models\Sweepstake;
use App\Models\SweepstakeMember;
use App\Models\SweepstakeTeam;
use App\Models\Team;
use App\Models\TeamAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SweepstakeTeamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owning_admin_can_bulk_remove_and_restore_teams(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 5);
        $teams = $sweepstake->sweepstakeTeams()->orderBy('id')->take(2)->get();

        $this->actingAs($admin)
            ->patch(route('sweepstakes.teams.bulk.update', $sweepstake), [
                'action' => 'remove',
                'team_ids' => $teams->pluck('id')->all(),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        foreach ($teams as $team) {
            $this->assertDatabaseHas('sweepstake_teams', [
                'id' => $team->id,
                'is_included' => false,
                'is_removed' => true,
                'removed_reason' => 'Removed by admin',
            ]);
        }

        $this->actingAs($admin)
            ->patch(route('sweepstakes.teams.bulk.update', $sweepstake), [
                'action' => 'restore',
                'team_ids' => [$teams->first()->id],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sweepstake_teams', [
            'id' => $teams->first()->id,
            'is_included' => true,
            'is_removed' => false,
            'removed_reason' => null,
        ]);
    }

    public function test_removed_teams_are_not_included_in_the_draw(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 5);
        $this->createMember($sweepstake, 'First Entrant');
        $this->createMember($sweepstake, 'Second Entrant');

        $removedTeam = $sweepstake->sweepstakeTeams()->orderBy('id')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('sweepstakes.teams.bulk.update', $sweepstake), [
                'action' => 'remove',
                'team_ids' => [$removedTeam->id],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        app(RunRankedPotDraw::class)->handle($sweepstake->fresh());

        $this->assertDatabaseMissing('team_assignments', [
            'sweepstake_id' => $sweepstake->id,
            'team_id' => $removedTeam->team_id,
        ]);

        $this->assertSame(4, TeamAssignment::where('sweepstake_id', $sweepstake->id)->count());
    }

    public function test_another_admin_cannot_bulk_remove_teams_from_someone_elses_sweepstake(): void
    {
        $owner = $this->createUser('owner@example.test');
        $otherAdmin = $this->createUser('other@example.test');
        $sweepstake = $this->createSweepstake($owner, teamCount: 3);
        $team = $sweepstake->sweepstakeTeams()->firstOrFail();

        $this->actingAs($otherAdmin)
            ->patch(route('sweepstakes.teams.bulk.update', $sweepstake), [
                'action' => 'remove',
                'team_ids' => [$team->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('sweepstake_teams', [
            'id' => $team->id,
            'is_included' => true,
            'is_removed' => false,
        ]);
    }

    public function test_guests_cannot_bulk_remove_teams(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 3);
        $team = $sweepstake->sweepstakeTeams()->firstOrFail();

        $this->patch(route('sweepstakes.teams.bulk.update', $sweepstake), [
            'action' => 'remove',
            'team_ids' => [$team->id],
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('sweepstake_teams', [
            'id' => $team->id,
            'is_included' => true,
            'is_removed' => false,
        ]);
    }

    public function test_team_selection_cannot_be_changed_after_the_draw(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 4);
        $this->createMember($sweepstake, 'First Entrant');
        $this->createMember($sweepstake, 'Second Entrant');

        app(RunRankedPotDraw::class)->handle($sweepstake);

        $team = $sweepstake->sweepstakeTeams()->firstOrFail();

        $this->actingAs($admin)
            ->from(route('sweepstakes.show', $sweepstake))
            ->patch(route('sweepstakes.teams.bulk.update', $sweepstake), [
                'action' => 'remove',
                'team_ids' => [$team->id],
            ])
            ->assertRedirect(route('sweepstakes.show', $sweepstake))
            ->assertSessionHasErrors('team');

        $this->assertDatabaseHas('sweepstake_teams', [
            'id' => $team->id,
            'is_included' => true,
            'is_removed' => false,
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
            'leftover_rule' => Sweepstake::LEFTOVER_REMOVE_LOWEST_RANKED,
        ]);

        foreach (range(1, $teamCount) as $index) {
            $team = Team::create([
                'name' => "Team {$index}",
                'country_code' => sprintf('B%02d', $index),
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
