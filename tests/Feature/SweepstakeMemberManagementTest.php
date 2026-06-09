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

class SweepstakeMemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manually_add_an_entrant_to_their_sweepstake(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin);

        $response = $this->actingAs($admin)->post(route('sweepstakes.members.store', $sweepstake), [
            'name' => 'Kyle Ferguson',
            'email' => 'Kyle@ElementSeven.co',
            'is_paid' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sweepstake_members', [
            'sweepstake_id' => $sweepstake->id,
            'name' => 'Kyle Ferguson',
            'email' => 'kyle@elementseven.co',
            'source' => SweepstakeMember::SOURCE_MANUAL,
            'is_paid' => true,
        ]);
    }

    public function test_admin_can_edit_and_remove_an_entrant_before_the_draw(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin);
        $member = $this->createMember($sweepstake, 'Original Name');

        $this->actingAs($admin)
            ->patch(route('sweepstakes.members.update', [$sweepstake, $member]), [
                'name' => 'Updated Name',
                'email' => 'updated@example.test',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sweepstake_members', [
            'id' => $member->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.test',
        ]);

        $this->actingAs($admin)
            ->delete(route('sweepstakes.members.destroy', [$sweepstake, $member]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('sweepstake_members', [
            'id' => $member->id,
        ]);
    }

    public function test_admin_cannot_manually_add_an_entrant_to_someone_elses_sweepstake(): void
    {
        $owner = $this->createUser('owner@example.test');
        $otherAdmin = $this->createUser('other@example.test');
        $sweepstake = $this->createSweepstake($owner);

        $this->actingAs($otherAdmin)
            ->post(route('sweepstakes.members.store', $sweepstake), [
                'name' => 'Intruder',
                'email' => 'intruder@example.test',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('sweepstake_members', [
            'sweepstake_id' => $sweepstake->id,
            'email' => 'intruder@example.test',
        ]);
    }

    public function test_admin_cannot_access_someone_elses_sweepstake_admin_page(): void
    {
        $owner = $this->createUser('owner@example.test');
        $otherAdmin = $this->createUser('other@example.test');
        $sweepstake = $this->createSweepstake($owner);

        $this->actingAs($otherAdmin)
            ->get(route('sweepstakes.show', $sweepstake))
            ->assertForbidden();
    }

    public function test_public_joined_entrants_are_marked_as_joined_by_link(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin);

        $response = $this->post(route('join.store', $sweepstake->join_code), [
            'name' => 'Link Entrant',
            'email' => 'link@example.test',
        ]);

        $this->assertDatabaseHas('sweepstake_members', [
            'sweepstake_id' => $sweepstake->id,
            'name' => 'Link Entrant',
            'email' => 'link@example.test',
            'source' => SweepstakeMember::SOURCE_JOIN_LINK,
            'is_paid' => false,
        ]);

        $member = SweepstakeMember::where('email', 'link@example.test')->firstOrFail();

        $response->assertRedirect(route('entrants.show', $member->join_token));
    }

    public function test_admin_cannot_add_more_than_forty_eight_entrants(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 48);

        foreach (range(1, 48) as $index) {
            $this->createMember($sweepstake, "Entrant {$index}");
        }

        $this->actingAs($admin)
            ->from(route('sweepstakes.show', $sweepstake))
            ->post(route('sweepstakes.members.store', $sweepstake), [
                'name' => 'Entrant 49',
                'email' => 'entrant49@example.test',
            ])
            ->assertRedirect(route('sweepstakes.show', ['sweepstake' => $sweepstake, 'tab' => 'entrants']))
            ->assertSessionHasErrors('member');

        $this->assertDatabaseMissing('sweepstake_members', [
            'sweepstake_id' => $sweepstake->id,
            'email' => 'entrant49@example.test',
        ]);
    }

    public function test_public_join_flow_cannot_exceed_entrant_capacity(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 2);

        $this->createMember($sweepstake, 'First Entrant');
        $this->createMember($sweepstake, 'Second Entrant');

        $this->from(route('join.show', $sweepstake->join_code))
            ->post(route('join.store', $sweepstake->join_code), [
                'name' => 'Third Entrant',
                'email' => 'third@example.test',
            ])
            ->assertRedirect(route('join.show', $sweepstake->join_code))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseMissing('sweepstake_members', [
            'sweepstake_id' => $sweepstake->id,
            'email' => 'third@example.test',
        ]);
    }

    public function test_admin_can_mark_an_entrant_paid_and_unpaid(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin);
        $member = $this->createMember($sweepstake, 'Entrant');

        $this->actingAs($admin)
            ->patch(route('sweepstakes.members.payment.update', [$sweepstake, $member]), [
                'is_paid' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertTrue($member->fresh()->is_paid);
        $this->assertNotNull($member->fresh()->paid_at);

        $this->actingAs($admin)
            ->patch(route('sweepstakes.members.payment.update', [$sweepstake, $member]), [
                'is_paid' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertFalse($member->fresh()->is_paid);
        $this->assertNull($member->fresh()->paid_at);
    }

    public function test_entrants_cannot_be_added_after_the_draw(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 4);
        $this->createMember($sweepstake, 'First Entrant');
        $this->createMember($sweepstake, 'Second Entrant');

        app(RunRankedPotDraw::class)->handle($sweepstake);

        $this->actingAs($admin)
            ->from(route('sweepstakes.show', $sweepstake))
            ->post(route('sweepstakes.members.store', $sweepstake), [
                'name' => 'Late Entrant',
                'email' => 'late@example.test',
            ])
            ->assertRedirect(route('sweepstakes.show', ['sweepstake' => $sweepstake, 'tab' => 'entrants']))
            ->assertSessionHasErrors('member');

        $this->assertDatabaseMissing('sweepstake_members', [
            'sweepstake_id' => $sweepstake->id,
            'email' => 'late@example.test',
        ]);
    }

    public function test_entrants_cannot_be_removed_after_the_draw(): void
    {
        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 4);
        $first = $this->createMember($sweepstake, 'First Entrant');
        $this->createMember($sweepstake, 'Second Entrant');

        app(RunRankedPotDraw::class)->handle($sweepstake);

        $this->actingAs($admin)
            ->from(route('sweepstakes.show', $sweepstake))
            ->delete(route('sweepstakes.members.destroy', [$sweepstake, $first]))
            ->assertRedirect(route('sweepstakes.show', ['sweepstake' => $sweepstake, 'tab' => 'entrants']))
            ->assertSessionHasErrors('member');

        $this->assertDatabaseHas('sweepstake_members', [
            'id' => $first->id,
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

    private function createSweepstake(User $admin, int $teamCount = 48): Sweepstake
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

        if ($teamCount > 0) {
            foreach (range(1, $teamCount) as $index) {
                $team = Team::create([
                    'name' => "Team {$index}",
                    'country_code' => sprintf('M%02d', $index),
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

    private function createMember(Sweepstake $sweepstake, string $name): SweepstakeMember
    {
        return SweepstakeMember::create([
            'sweepstake_id' => $sweepstake->id,
            'name' => $name,
            'email' => Str($name)->slug()->append('@example.test')->toString(),
            'join_token' => 'token-'.uniqid(),
            'source' => SweepstakeMember::SOURCE_MANUAL,
        ]);
    }
}
