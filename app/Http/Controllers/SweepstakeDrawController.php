<?php

namespace App\Http\Controllers;

use App\Actions\RunRankedPotDraw;
use App\Exceptions\DrawException;
use App\Mail\DrawResultsReady;
use App\Models\Sweepstake;
use App\Models\SweepstakeDraw;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class SweepstakeDrawController extends Controller
{
    public function store(Request $request, Sweepstake $sweepstake, RunRankedPotDraw $draw): RedirectResponse
    {
        abort_unless($sweepstake->user_id === $request->user()->id, 403);

        try {
            $draw->handle($sweepstake);
        } catch (DrawException $exception) {
            return back()->withErrors([
                'draw' => $exception->getMessage(),
            ]);
        }

        $this->sendResultEmails($this->activeDraw($sweepstake));

        return back()->with('status', 'Ranked pot draw completed. Entrants with email addresses have been notified.');
    }

    public function rerun(Request $request, Sweepstake $sweepstake, RunRankedPotDraw $draw): RedirectResponse
    {
        abort_unless($sweepstake->user_id === $request->user()->id, 403);

        if (! $sweepstake->activeDraw()->exists()) {
            return back()->withErrors([
                'draw' => 'Run the first draw before re-running it.',
            ]);
        }

        $attributes = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'reason.required' => 'Please enter a reason for re-running the draw.',
        ]);

        try {
            $draw->handle($sweepstake, $attributes['reason']);
        } catch (DrawException $exception) {
            return back()->withErrors([
                'draw' => $exception->getMessage(),
            ]);
        }

        $this->sendResultEmails($this->activeDraw($sweepstake));

        return back()->with('status', 'Draw re-run completed. Entrants with email addresses have been notified.');
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
}
