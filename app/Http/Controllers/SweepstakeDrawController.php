<?php

namespace App\Http\Controllers;

use App\Actions\RunRankedPotDraw;
use App\Exceptions\DrawException;
use App\Mail\DrawCancelled;
use App\Mail\DrawResultsReady;
use App\Models\Sweepstake;
use App\Models\SweepstakeDraw;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class SweepstakeDrawController extends Controller
{
    public function store(Request $request, Sweepstake $sweepstake, RunRankedPotDraw $draw): RedirectResponse
    {
        abort_unless($sweepstake->user_id === $request->user()->id, 403);

        if (! $this->hasPrizes($sweepstake)) {
            return $this->redirectToSweepstakeTab($request, $sweepstake, 'draw-results')->withErrors([
                'draw' => 'Add at least one prize before running the draw.',
            ]);
        }

        $strategy = $this->validatedLeftoverStrategy($request);

        try {
            $this->ensureStrategyWasChosenWhenNeeded($draw, $sweepstake, $strategy);
            $draw->handle($sweepstake, leftoverStrategy: $strategy ?? SweepstakeDraw::LEFTOVER_STRATEGY_REMOVE_LOWEST_RANKED);
        } catch (DrawException $exception) {
            return $this->redirectToSweepstakeTab($request, $sweepstake, 'draw-results')->withErrors([
                'draw' => $exception->getMessage(),
            ]);
        }

        $this->sendResultEmails($this->activeDraw($sweepstake));

        return $this->redirectToSweepstakeTab($request, $sweepstake, 'draw-results')
            ->with('status', $sweepstake->pot_mode === Sweepstake::POT_MODE_CUSTOM
                ? 'Custom pot draw completed. Entrants with email addresses have been notified.'
                : 'Ranked pot draw completed. Entrants with email addresses have been notified.');
    }

    public function rerun(Request $request, Sweepstake $sweepstake, RunRankedPotDraw $draw): RedirectResponse
    {
        abort_unless($sweepstake->user_id === $request->user()->id, 403);

        if (! $sweepstake->activeDraw()->exists()) {
            return $this->redirectToSweepstakeTab($request, $sweepstake, 'draw-results')->withErrors([
                'draw' => 'Run the first draw before re-running it.',
            ]);
        }

        if (! $this->hasPrizes($sweepstake)) {
            return $this->redirectToSweepstakeTab($request, $sweepstake, 'draw-results')->withErrors([
                'draw' => 'Add at least one prize before running the draw.',
            ]);
        }

        $attributes = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'leftover_team_strategy' => ['nullable', Rule::in($this->leftoverStrategies())],
        ], [
            'reason.required' => 'Please enter a reason for re-running the draw.',
            'leftover_team_strategy.in' => 'Choose how to handle leftover teams before running the draw.',
        ]);

        try {
            $strategy = $attributes['leftover_team_strategy'] ?? null;

            $this->ensureStrategyWasChosenWhenNeeded($draw, $sweepstake, $strategy);
            $draw->handle(
                $sweepstake,
                $attributes['reason'],
                $strategy ?? SweepstakeDraw::LEFTOVER_STRATEGY_REMOVE_LOWEST_RANKED,
            );
        } catch (DrawException $exception) {
            return $this->redirectToSweepstakeTab($request, $sweepstake, 'draw-results')->withErrors([
                'draw' => $exception->getMessage(),
            ]);
        }

        $this->sendResultEmails($this->activeDraw($sweepstake));

        return $this->redirectToSweepstakeTab($request, $sweepstake, 'draw-results')
            ->with('status', 'Draw re-run completed. Entrants with email addresses have been notified.');
    }

    public function cancel(Request $request, Sweepstake $sweepstake): RedirectResponse
    {
        abort_unless($sweepstake->user_id === $request->user()->id, 403);

        $attributes = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'reason.required' => 'Please enter a reason for cancelling the draw.',
        ]);

        try {
            $cancelledDraw = DB::transaction(function () use ($attributes, $sweepstake): SweepstakeDraw {
                $sweepstake = Sweepstake::query()
                    ->lockForUpdate()
                    ->findOrFail($sweepstake->id);

                $activeDraw = $sweepstake->draws()
                    ->where('status', SweepstakeDraw::STATUS_ACTIVE)
                    ->lockForUpdate()
                    ->first();

                if (! $activeDraw) {
                    throw new DrawException('There is no active draw to cancel.');
                }

                $activeDraw->update([
                    'status' => SweepstakeDraw::STATUS_CANCELLED,
                    'cancelled_reason' => trim($attributes['reason']),
                    'cancelled_at' => now(),
                ]);

                $sweepstake->forceFill([
                    'status' => Sweepstake::STATUS_OPEN,
                    'teams_per_member' => null,
                    'drawn_at' => null,
                ])->save();

                return $activeDraw->fresh([
                    'sweepstake',
                    'sweepstake.members',
                ]);
            });
        } catch (DrawException $exception) {
            return $this->redirectToSweepstakeTab($request, $sweepstake, 'draw-results')->withErrors([
                'draw' => $exception->getMessage(),
            ]);
        }

        $this->sendCancellationEmails($cancelledDraw);

        return $this->redirectToSweepstakeTab($request, $sweepstake, 'draw-results')
            ->with('status', 'The previous draw was cancelled. Setup is open again.');
    }

    private function activeDraw(Sweepstake $sweepstake): SweepstakeDraw
    {
        return $sweepstake->fresh()
            ->activeDraw()
            ->with([
                'sweepstake',
                'assignments.member',
                'assignments.team',
            ])
            ->firstOrFail();
    }

    private function hasPrizes(Sweepstake $sweepstake): bool
    {
        return $sweepstake->prizes()->exists();
    }

    private function sendResultEmails(SweepstakeDraw $draw): void
    {
        $draw->assignments
            ->groupBy('sweepstake_member_id')
            ->each(function (Collection $assignments) use ($draw): void {
                $member = $assignments->first()->member;

                if (! $member->email) {
                    return;
                }

                Mail::to($member->email)->send(new DrawResultsReady(
                    $draw,
                    $member,
                    $assignments
                        ->sortBy(fn ($assignment): string => sprintf('%03d-%08d', $assignment->pot_number ?? 0, $assignment->id))
                        ->values(),
                ));
            });
    }

    private function sendCancellationEmails(SweepstakeDraw $draw): void
    {
        $draw->sweepstake
            ->members()
            ->whereNotNull('email')
            ->get()
            ->each(fn ($member): mixed => Mail::to($member->email)->send(new DrawCancelled($draw, $member)));
    }

    private function validatedLeftoverStrategy(Request $request): ?string
    {
        $attributes = $request->validate([
            'leftover_team_strategy' => ['nullable', Rule::in($this->leftoverStrategies())],
        ], [
            'leftover_team_strategy.in' => 'Choose how to handle leftover teams before running the draw.',
        ]);

        return $attributes['leftover_team_strategy'] ?? null;
    }

    private function ensureStrategyWasChosenWhenNeeded(RunRankedPotDraw $draw, Sweepstake $sweepstake, ?string $strategy): void
    {
        $plan = $draw->buildPlan($sweepstake, $strategy ?? SweepstakeDraw::LEFTOVER_STRATEGY_REMOVE_LOWEST_RANKED);

        if ($plan['leftover_team_count'] > 0 && $strategy === null) {
            throw new DrawException("With {$plan['member_count']} entrants and {$plan['selected_team_count']} teams, everyone can receive {$plan['teams_per_member']} teams each. There will be {$plan['leftover_team_count']} teams left over. Choose how to handle the leftover teams before running the draw.");
        }
    }

    /**
     * @return array<int, string>
     */
    private function leftoverStrategies(): array
    {
        return [
            SweepstakeDraw::LEFTOVER_STRATEGY_REMOVE_LOWEST_RANKED,
            SweepstakeDraw::LEFTOVER_STRATEGY_ASSIGN_RANDOMLY,
        ];
    }
}
