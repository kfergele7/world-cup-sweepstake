<?php

namespace Tests\Feature;

use App\Actions\RunRankedPotDraw;
use App\Models\Sweepstake;
use App\Models\SweepstakeMember;
use App\Models\SweepstakeTeam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SweepstakeSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owning_admin_can_update_entry_fee_from_zero_to_a_positive_amount(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, entryFee: 0);

        $this->actingAs($admin)
            ->patch(route('sweepstakes.settings.update', $sweepstake), [
                'sweepstake_name' => $sweepstake->name,
                'entry_fee' => '12.50',
                'currency' => 'GBP',
                'status' => Sweepstake::STATUS_OPEN,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $sweepstake->refresh();

        $this->assertSame('12.50', $sweepstake->entry_fee);
        $this->assertSame('GBP', $sweepstake->currency);
    }

    public function test_owning_admin_can_update_sweepstake_name_and_status_before_draw(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, entryFee: 5);

        $this->actingAs($admin)
            ->patch(route('sweepstakes.settings.update', $sweepstake), [
                'sweepstake_name' => 'Family World Cup Sweepstake',
                'entry_fee' => '10',
                'currency' => 'gbp',
                'status' => Sweepstake::STATUS_DRAFT,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $sweepstake->refresh();

        $this->assertSame('Family World Cup Sweepstake', $sweepstake->name);
        $this->assertSame('10.00', $sweepstake->entry_fee);
        $this->assertSame('GBP', $sweepstake->currency);
        $this->assertSame(Sweepstake::STATUS_DRAFT, $sweepstake->status);
    }

    public function test_another_logged_in_user_cannot_update_someone_elses_sweepstake_settings(): void
    {
        $owner = $this->createUser('owner@example.test');
        $otherAdmin = $this->createUser('other@example.test');
        $sweepstake = $this->createSweepstake($owner, entryFee: 0);

        $this->actingAs($otherAdmin)
            ->patch(route('sweepstakes.settings.update', $sweepstake), [
                'sweepstake_name' => 'Hijacked Sweepstake',
                'entry_fee' => '20',
                'currency' => 'GBP',
                'status' => Sweepstake::STATUS_OPEN,
            ])
            ->assertForbidden();

        $sweepstake->refresh();

        $this->assertSame('Office Sweepstake', $sweepstake->name);
        $this->assertSame('0.00', $sweepstake->entry_fee);
    }

    public function test_guests_cannot_update_sweepstake_settings(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, entryFee: 0);

        $this->patch(route('sweepstakes.settings.update', $sweepstake), [
            'sweepstake_name' => 'Guest Update',
            'entry_fee' => '20',
            'currency' => 'GBP',
            'status' => Sweepstake::STATUS_OPEN,
        ])->assertRedirect(route('login'));

        $this->assertSame('0.00', $sweepstake->fresh()->entry_fee);
    }

    public function test_negative_entry_fees_are_rejected(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, entryFee: 0);

        $this->actingAs($admin)
            ->from(route('sweepstakes.show', $sweepstake))
            ->patch(route('sweepstakes.settings.update', $sweepstake), [
                'sweepstake_name' => $sweepstake->name,
                'entry_fee' => '-1',
                'currency' => 'GBP',
                'status' => Sweepstake::STATUS_OPEN,
            ])
            ->assertRedirect(route('sweepstakes.show', $sweepstake))
            ->assertSessionHasErrors('entry_fee');

        $this->assertSame('0.00', $sweepstake->fresh()->entry_fee);
    }

    public function test_settings_cannot_be_edited_after_the_draw(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, entryFee: 0, teamCount: 4);
        $this->createEntrant($sweepstake, 'First Entrant');
        $this->createEntrant($sweepstake, 'Second Entrant');

        app(RunRankedPotDraw::class)->handle($sweepstake);

        $this->actingAs($admin)
            ->from(route('sweepstakes.show', $sweepstake))
            ->patch(route('sweepstakes.settings.update', $sweepstake), [
                'sweepstake_name' => 'After Draw Update',
                'entry_fee' => '20',
                'currency' => 'GBP',
                'status' => Sweepstake::STATUS_OPEN,
            ])
            ->assertRedirect(route('sweepstakes.show', $sweepstake))
            ->assertSessionHasErrors('settings');

        $sweepstake->refresh();

        $this->assertSame('Office Sweepstake', $sweepstake->name);
        $this->assertSame('0.00', $sweepstake->entry_fee);
        $this->assertSame(Sweepstake::STATUS_DRAWN, $sweepstake->status);
    }

    private function createUser(string $email): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => $email,
            'password' => 'password',
        ]);
    }

    private function createSweepstake(User $admin, int|float $entryFee, int $teamCount = 0): Sweepstake
    {
        $sweepstake = Sweepstake::create([
            'user_id' => $admin->id,
            'name' => 'Office Sweepstake',
            'slug' => 'office-'.uniqid(),
            'join_code' => strtoupper(substr(uniqid(), -8)),
            'entry_fee' => $entryFee,
            'currency' => 'GBP',
            'status' => Sweepstake::STATUS_OPEN,
            'draw_mode' => Sweepstake::DRAW_MODE_RANKED_POTS,
            'leftover_rule' => Sweepstake::LEFTOVER_REMOVE_LOWEST_RANKED,
        ]);

        if ($teamCount > 0) {
            foreach (range(1, $teamCount) as $index) {
                $team = Team::create([
                    'name' => "Team {$index}",
                    'country_code' => sprintf('S%02d', $index),
                    'fifa_ranking' => $index,
                    'qualified_for_2026' => true,
                ]);

                SweepstakeTeam::create([
                    'sweepstake_id' => $sweepstake->id,
                    'team_id' => $team->id,
                    'sort_order' => $index,
                ]);
            }
        }

        return $sweepstake;
    }

    private function createEntrant(Sweepstake $sweepstake, string $name): SweepstakeMember
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
