<?php

namespace Tests\Feature;

use App\Mail\DrawResultsReady;
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

class SweepstakeDrawNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_running_a_first_draw_emails_entrants_with_email_addresses(): void
    {
        Mail::fake();

        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 6);
        $alice = $this->createMember($sweepstake, 'Alice Adams', 'alice@example.test', 'alice-token');
        $this->createMember($sweepstake, 'No Email', null, 'no-email-token');
        $this->createMember($sweepstake, 'Cara Clark', 'cara@example.test', 'cara-token');

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.store', $sweepstake))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        Mail::assertSent(DrawResultsReady::class, 2);
        Mail::assertSent(DrawResultsReady::class, function (DrawResultsReady $mail) use ($alice): bool {
            if ($mail->member->email !== $alice->email || $mail->assignments->isEmpty()) {
                return false;
            }

            $html = $mail->render();

            return str_contains($html, 'Office Sweepstake')
                && str_contains($html, $mail->assignments->first()->team->name)
                && str_contains($html, route('entrants.show', $alice->join_token))
                && ! str_contains($html, 'no-email@example.test')
                && ! str_contains($html, 'cara@example.test');
        });
    }

    public function test_admin_can_rerun_draw_with_required_reason_and_send_updated_emails(): void
    {
        Mail::fake();

        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 4);
        $this->createMember($sweepstake, 'Alice Adams', 'alice@example.test', 'alice-token');
        $this->createMember($sweepstake, 'Bob Brown', 'bob@example.test', 'bob-token');

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.store', $sweepstake))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $firstDraw = SweepstakeDraw::where('sweepstake_id', $sweepstake->id)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('sweepstakes.draw.rerun', $sweepstake), [
                'reason' => 'Ryan was missed from the entrant list',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $secondDraw = SweepstakeDraw::where('sweepstake_id', $sweepstake->id)
            ->where('version_number', 2)
            ->firstOrFail();

        $this->assertSame(SweepstakeDraw::STATUS_SUPERSEDED, $firstDraw->fresh()->status);
        $this->assertSame(SweepstakeDraw::STATUS_ACTIVE, $secondDraw->status);
        $this->assertSame('Ryan was missed from the entrant list', $secondDraw->reason);
        $this->assertSame($firstDraw->id, $secondDraw->rerun_of_draw_id);
        $this->assertSame(4, TeamAssignment::where('sweepstake_draw_id', $firstDraw->id)->count());
        $this->assertSame(4, TeamAssignment::where('sweepstake_draw_id', $secondDraw->id)->count());

        Mail::assertSent(DrawResultsReady::class, 4);
        Mail::assertSent(DrawResultsReady::class, function (DrawResultsReady $mail): bool {
            return $mail->draw->version_number === 2
                && $mail->draw->reason === 'Ryan was missed from the entrant list'
                && str_contains($mail->render(), 'Reason for re-running');
        });
    }

    public function test_rerun_draw_requires_a_reason(): void
    {
        Mail::fake();

        $admin = $this->createUser('admin@example.test');
        $sweepstake = $this->createSweepstake($admin, teamCount: 4);
        $this->createMember($sweepstake, 'Alice Adams', 'alice@example.test', 'alice-token');
        $this->createMember($sweepstake, 'Bob Brown', 'bob@example.test', 'bob-token');

        $this->actingAs($admin)->post(route('sweepstakes.draw.store', $sweepstake));

        $this->actingAs($admin)
            ->from(route('sweepstakes.show', $sweepstake))
            ->post(route('sweepstakes.draw.rerun', $sweepstake), [
                'reason' => '',
            ])
            ->assertRedirect(route('sweepstakes.show', $sweepstake))
            ->assertSessionHasErrors('reason');

        $this->assertSame(1, SweepstakeDraw::where('sweepstake_id', $sweepstake->id)->count());
    }

    public function test_another_admin_cannot_rerun_someone_elses_draw(): void
    {
        Mail::fake();

        $owner = $this->createUser('owner@example.test');
        $otherAdmin = $this->createUser('other@example.test');
        $sweepstake = $this->createSweepstake($owner, teamCount: 4);
        $this->createMember($sweepstake, 'Alice Adams', 'alice@example.test', 'alice-token');
        $this->createMember($sweepstake, 'Bob Brown', 'bob@example.test', 'bob-token');

        $this->actingAs($owner)->post(route('sweepstakes.draw.store', $sweepstake));

        $this->actingAs($otherAdmin)
            ->post(route('sweepstakes.draw.rerun', $sweepstake), [
                'reason' => 'Trying to interfere',
            ])
            ->assertForbidden();

        $this->assertSame(1, SweepstakeDraw::where('sweepstake_id', $sweepstake->id)->count());
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
                'country_code' => sprintf('N%02d', $index),
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

    private function createMember(Sweepstake $sweepstake, string $name, ?string $email, string $token): SweepstakeMember
    {
        return SweepstakeMember::create([
            'sweepstake_id' => $sweepstake->id,
            'name' => $name,
            'email' => $email,
            'join_token' => $token,
            'source' => SweepstakeMember::SOURCE_MANUAL,
        ]);
    }
}
