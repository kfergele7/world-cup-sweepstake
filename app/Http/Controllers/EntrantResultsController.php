<?php

namespace App\Http\Controllers;

use App\Models\SweepstakeDraw;
use App\Models\SweepstakeMember;
use App\Models\TeamAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class EntrantResultsController extends Controller
{
    public function __invoke(Request $request, string $joinToken): View
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
            'fullDrawResults' => $activeDraw ? $this->fullDrawResults($member, $activeDraw) : collect(),
            'canViewAdminLinks' => $request->user()?->id === $member->sweepstake->user_id,
            'assignments' => ($activeDraw?->assignments ?? collect())
                ->sortBy(fn ($assignment): string => sprintf('%03d-%08d', $assignment->pot_number ?? 0, $assignment->id))
                ->values(),
        ]);
    }

    private function fullDrawResults(SweepstakeMember $member, SweepstakeDraw $activeDraw): Collection
    {
        $assignmentsByMember = TeamAssignment::query()
            ->where('sweepstake_draw_id', $activeDraw->id)
            ->with('team')
            ->get()
            ->groupBy('sweepstake_member_id');

        return $member->sweepstake
            ->members()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (SweepstakeMember $entrant): array => [
                'member' => $entrant,
                'assignments' => ($assignmentsByMember->get($entrant->id) ?? collect())
                    ->sortBy(fn (TeamAssignment $assignment): string => sprintf('%03d-%08d', $assignment->pot_number ?? 0, $assignment->id))
                    ->values(),
            ]);
    }
}
