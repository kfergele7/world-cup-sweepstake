<?php

namespace App\Http\Controllers;

use App\Models\SweepstakeMember;
use Illuminate\View\View;

class EntrantResultsController extends Controller
{
    public function __invoke(string $joinToken): View
    {
        $member = SweepstakeMember::query()
            ->where('join_token', $joinToken)
            ->with([
                'sweepstake',
                'assignments.team',
            ])
            ->firstOrFail();

        return view('entrants.show', [
            'member' => $member,
            'sweepstake' => $member->sweepstake,
            'assignments' => $member->assignments
                ->sortBy(fn ($assignment): string => sprintf('%03d-%08d', $assignment->pot_number ?? 0, $assignment->id))
                ->values(),
        ]);
    }
}
