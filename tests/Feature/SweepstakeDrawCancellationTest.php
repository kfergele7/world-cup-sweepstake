<?php

namespace Tests\Feature;

use App\Mail\DrawCancelled;
use App\Models\Sweepstake;
use App\Models\SweepstakeDraw;
use App\Models\SweepstakeMember;
use App\Models\SweepstakeTeam;
use App\Models\Team;
use App\Models\TeamAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SweepstakeDrawCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_cancel_active_draw_with_required_reason_and_reopen_setup(): void
    {
        Mail::fake();

        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 4);
        $this->createMember($sweepstake, 'Alice Adams', 'alice@example.test');
        $this->createMember($sweepstake, 'Bob Brown', 'bob@example.test');

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.store', $sweepstake), [
                'leftover_team_strategy' => SweepstakeDraw::LEFTOVER_STRATEGY_REMOVE_LOWEST_RANKED,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $activeDraw = SweepstakeDraw::where('sweepstake_id', $sweepstake->id)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.cancel', $sweepstake), [
                'reason' => 'Forgot an entrant',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(SweepstakeDraw::STATUS_CANCELLED, $activeDraw->fresh()->status);
        $this->assertSame('Forgot an entrant', $activeDraw->fresh()->cancelled_reason);
        $this->assertSame(Sweepstake::STATUS_OPEN, $sweepstake->fresh()->status);
        $this->assertFalse($sweepstake->fresh()->activeDraw()->exists());
        $this->assertSame(4, TeamAssignment::where('sweepstake_draw_id', $activeDraw->id)->count());

        Mail::assertSent(DrawCancelled::class, 2);
    }

    public function test_cancelling_without_a_reason_is_rejected(): void
    {
        Mail::fake();

        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 4);
        $this->createMember($sweepstake, 'Alice Adams');
        $this->createMember($sweepstake, 'Bob Brown');

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.store', $sweepstake), [
                'leftover_team_strategy' => SweepstakeDraw::LEFTOVER_STRATEGY_REMOVE_LOWEST_RANKED,
            ]);

        $this->actingAs($admin)
            ->from(route('sweepstakes.show', $sweepstake))
            ->post(route('sweepstakes.draw.cancel', $sweepstake), [
                'reason' => '',
            ])
            ->assertRedirect(route('sweepstakes.show', $sweepstake))
            ->assertSessionHasErrors('reason');

        $this->assertSame(SweepstakeDraw::STATUS_ACTIVE, SweepstakeDraw::firstOrFail()->status);
    }

    public function test_another_admin_cannot_cancel_someone_elses_draw(): void
    {
        Mail::fake();

        $owner = $this->createUser('owner@example.test');
        $otherAdmin = $this->createUser('other@example.test');
        $sweepstake = $this->createSweepstake($owner, teamCount: 4);
        $this->createMember($sweepstake, 'Alice Adams');
        $this->createMember($sweepstake, 'Bob Brown');

        $this->actingAs($owner)
            ->post(route('sweepstakes.draw.store', $sweepstake), [
                'leftover_team_strategy' => SweepstakeDraw::LEFTOVER_STRATEGY_REMOVE_LOWEST_RANKED,
            ]);

        $this->actingAs($otherAdmin)
            ->post(route('sweepstakes.draw.cancel', $sweepstake), [
                'reason' => 'Trying to interfere',
            ])
            ->assertForbidden();

        $this->assertSame(SweepstakeDraw::STATUS_ACTIVE, SweepstakeDraw::firstOrFail()->status);
    }

    public function test_admin_can_add_entrant_and_run_new_draw_after_cancellation(): void
    {
        Mail::fake();

        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 4);
        $this->createMember($sweepstake, 'Alice Adams');
        $this->createMember($sweepstake, 'Bob Brown');

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.store', $sweepstake), [
                'leftover_team_strategy' => SweepstakeDraw::LEFTOVER_STRATEGY_REMOVE_LOWEST_RANKED,
            ]);

        $firstDraw = SweepstakeDraw::firstOrFail();

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.cancel', $sweepstake), [
                'reason' => 'Forgot Cara',
            ]);

        $this->actingAs($admin)
            ->post(route('sweepstakes.members.store', $sweepstake), [
                'name' => 'Cara Clark',
                'email' => 'cara@example.test',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.store', $sweepstake), [
                'leftover_team_strategy' => SweepstakeDraw::LEFTOVER_STRATEGY_REMOVE_LOWEST_RANKED,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $secondDraw = SweepstakeDraw::where('version_number', 2)->firstOrFail();

        $this->assertSame(SweepstakeDraw::STATUS_CANCELLED, $firstDraw->fresh()->status);
        $this->assertSame(SweepstakeDraw::STATUS_ACTIVE, $secondDraw->status);
        $this->assertSame(2, SweepstakeDraw::where('sweepstake_id', $sweepstake->id)->count());
        $this->assertDatabaseHas('sweepstake_members', [
            'sweepstake_id' => $sweepstake->id,
            'email' => 'cara@example.test',
        ]);
    }

    public function test_leftover_team_strategy_must_be_chosen_when_team_count_does_not_divide_evenly(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 10);
        $this->createMember($sweepstake, 'Alice Adams');
        $this->createMember($sweepstake, 'Bob Brown');
        $this->createMember($sweepstake, 'Cara Clark');

        $this->actingAs($admin)
            ->from(route('sweepstakes.show', $sweepstake))
            ->post(route('sweepstakes.draw.store', $sweepstake))
            ->assertRedirect(route('sweepstakes.show', ['sweepstake' => $sweepstake, 'tab' => 'draw-results']))
            ->assertSessionHasErrors('draw');

        $this->assertDatabaseCount('sweepstake_draws', 0);
    }

    public function test_draw_is_allowed_when_there_is_exactly_one_team_per_entrant(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2);
        $this->createMember($sweepstake, 'Alice Adams');
        $this->createMember($sweepstake, 'Bob Brown');

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.store', $sweepstake), [
                'leftover_team_strategy' => SweepstakeDraw::LEFTOVER_STRATEGY_REMOVE_LOWEST_RANKED,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('team_assignments', 2);
    }

    public function test_draw_history_page_shows_cancelled_reason(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 4);
        $this->createMember($sweepstake, 'Alice Adams');
        $this->createMember($sweepstake, 'Bob Brown');

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.store', $sweepstake), [
                'leftover_team_strategy' => SweepstakeDraw::LEFTOVER_STRATEGY_REMOVE_LOWEST_RANKED,
            ]);

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.cancel', $sweepstake), [
                'reason' => 'Forgot an entrant',
            ]);

        $this->actingAs($admin)
            ->get(route('sweepstakes.show', $sweepstake))
            ->assertOk()
            ->assertSee('Draw history')
            ->assertSee('Cancelled')
            ->assertSee('Cancellation reason: Forgot an entrant')
            ->assertSee('The previous draw was cancelled. Setup is open again.');
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
                'country_code' => sprintf('C%02d', $index),
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

    private function createMember(Sweepstake $sweepstake, string $name, ?string $email = null): SweepstakeMember
    {
        return SweepstakeMember::create([
            'sweepstake_id' => $sweepstake->id,
            'name' => $name,
            'email' => $email ?? str($name)->slug()->append('@example.test')->toString(),
            'join_token' => 'token-'.uniqid(),
            'source' => SweepstakeMember::SOURCE_MANUAL,
        ]);
    }
}
