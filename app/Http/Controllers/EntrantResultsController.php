<?php

namespace App\Http\Controllers;

use App\Models\SweepstakeDraw;
use App\Models\SweepstakeMember;
use Illuminate\View\View;

class EntrantResultsController extends Controller
{
    public function __invoke(string $joinToken): View
    {
        $member = SweepstakeMember::query()
            ->where('join_token', $joinToken)
            ->with('sweepstake')
            ->firstOrFail();

        $draws = $member->sweepstake
            ->draws()
            ->with([
                'assignments' => fn ($query) => $query
                    ->where('sweepstake_member_id', $member->id)
                    ->with('team'),
            ])
            ->orderBy('version_number')
            ->get();

        $activeDraw = $draws->firstWhere('status', SweepstakeDraw::STATUS_ACTIVE);

        return view('entrants.show', [
            'member' => $member,
            'sweepstake' => $member->sweepstake,
            'activeDraw' => $activeDraw,
            'draws' => $draws,
            'assignments' => ($activeDraw?->assignments ?? collect())
                ->sortBy(fn ($assignment): string => sprintf('%03d-%08d', $assignment->pot_number ?? 0, $assignment->id))
                ->values(),
        ]);
    }
}
