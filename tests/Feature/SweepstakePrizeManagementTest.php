<?php

namespace Tests\Feature;

use App\Actions\RunRankedPotDraw;
use App\Models\Prize;
use App\Models\Sweepstake;
use App\Models\SweepstakeDraw;
use App\Models\SweepstakeMember;
use App\Models\SweepstakeTeam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SweepstakePrizeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_edit_prize_labels_positions_and_amounts_before_draw(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin);
        $winner = $this->createPrize($sweepstake, 1, 'Winner', 100);
        $runnerUp = $this->createPrize($sweepstake, 2, 'Runner-up', 50);

        $this->actingAs($admin)
            ->patch(route('sweepstakes.prizes.update', $sweepstake), [
                'prizes' => [
                    $winner->id => [
                        'id' => $winner->id,
                        'position' => 2,
                        'label' => 'Runner-up',
                        'amount' => 60,
                    ],
                    $runnerUp->id => [
                        'id' => $runnerUp->id,
                        'position' => 1,
                        'label' => 'Winner',
                        'amount' => 120,
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('prizes', [
            'id' => $winner->id,
            'position' => 2,
            'label' => 'Runner-up',
            'amount' => 60,
        ]);
        $this->assertDatabaseHas('prizes', [
            'id' => $runnerUp->id,
            'position' => 1,
            'label' => 'Winner',
            'amount' => 120,
        ]);
    }

    public function test_admin_can_remove_prize_before_draw(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin);
        $prize = $this->createPrize($sweepstake, 1, 'Winner', 100);

        $this->actingAs($admin)
            ->delete(route('sweepstakes.prizes.destroy', [$sweepstake, $prize]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('prizes', ['id' => $prize->id]);
    }

    public function test_admin_cannot_edit_another_admins_prizes(): void
    {
        $owner = $this->createUser('owner@example.test');
        $otherAdmin = $this->createUser('other@example.test');
        $sweepstake = $this->createSweepstake($owner);
        $prize = $this->createPrize($sweepstake, 1, 'Winner', 100);

        $this->actingAs($otherAdmin)
            ->patch(route('sweepstakes.prizes.update', $sweepstake), [
                'prizes' => [
                    $prize->id => [
                        'id' => $prize->id,
                        'position' => 1,
                        'label' => 'Changed',
                        'amount' => 10,
                    ],
                ],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('prizes', [
            'id' => $prize->id,
            'label' => 'Winner',
        ]);
    }

    public function test_guests_cannot_edit_prizes(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin);
        $prize = $this->createPrize($sweepstake, 1, 'Winner', 100);

        $this->patch(route('sweepstakes.prizes.update', $sweepstake), [
            'prizes' => [
                $prize->id => [
                    'id' => $prize->id,
                    'position' => 1,
                    'label' => 'Changed',
                    'amount' => 10,
                ],
            ],
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('prizes', [
            'id' => $prize->id,
            'label' => 'Winner',
        ]);
    }

    public function test_negative_prize_amounts_and_empty_labels_are_rejected(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin);

        $this->actingAs($admin)
            ->from(route('sweepstakes.show', $sweepstake))
            ->post(route('sweepstakes.prizes.store', $sweepstake), [
                'position' => 1,
                'label' => '',
                'amount' => -10,
            ])
            ->assertRedirect(route('sweepstakes.show', $sweepstake))
            ->assertSessionHasErrors(['label', 'amount']);
    }

    public function test_prize_editing_is_locked_after_active_draw_and_reopens_after_cancellation(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 4);
        $prize = $this->createPrize($sweepstake, 1, 'Winner', 100);
        $this->createMember($sweepstake, 'Alice');
        $this->createMember($sweepstake, 'Bob');

        app(RunRankedPotDraw::class)->handle($sweepstake);

        $this->actingAs($admin)
            ->from(route('sweepstakes.show', $sweepstake))
            ->patch(route('sweepstakes.prizes.update', $sweepstake), [
                'prizes' => [
                    $prize->id => [
                        'id' => $prize->id,
                        'position' => 1,
                        'label' => 'Changed',
                        'amount' => 120,
                    ],
                ],
            ])
            ->assertRedirect(route('sweepstakes.show', ['sweepstake' => $sweepstake, 'tab' => 'settings-prizes']))
            ->assertSessionHasErrors('prize');

        $sweepstake->activeDraw()->firstOrFail()->update([
            'status' => SweepstakeDraw::STATUS_CANCELLED,
            'cancelled_reason' => 'Forgot an entrant',
            'cancelled_at' => now(),
        ]);
        $sweepstake->refresh()->forceFill([
            'status' => Sweepstake::STATUS_OPEN,
            'teams_per_member' => null,
            'drawn_at' => null,
        ])->save();

        $this->actingAs($admin)
            ->patch(route('sweepstakes.prizes.update', $sweepstake), [
                'prizes' => [
                    $prize->id => [
                        'id' => $prize->id,
                        'position' => 1,
                        'label' => 'Changed',
                        'amount' => 120,
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('prizes', [
            'id' => $prize->id,
            'label' => 'Changed',
            'amount' => 120,
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

    private function createSweepstake(User $admin, int $teamCount = 4): Sweepstake
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
                'country_code' => "P{$index}X",
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

    private function createPrize(Sweepstake $sweepstake, int $position, string $label, float $amount): Prize
    {
        return Prize::create([
            'sweepstake_id' => $sweepstake->id,
            'position' => $position,
            'label' => $label,
            'amount' => $amount,
        ]);
    }
}
