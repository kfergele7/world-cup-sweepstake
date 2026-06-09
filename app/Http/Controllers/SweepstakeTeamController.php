<?php

namespace App\Http\Controllers;

use App\Models\Sweepstake;
use App\Models\SweepstakePotTeam;
use App\Models\SweepstakeTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SweepstakeTeamController extends Controller
{
    public function bulkUpdate(Request $request, Sweepstake $sweepstake): RedirectResponse
    {
        $this->ensureAdmin($request, $sweepstake);

        if ($sweepstake->isLockedForChanges()) {
            return back()->withErrors([
                'team' => 'Team selection is locked after the draw.',
            ]);
        }

        $attributes = $request->validate([
            'action' => ['required', Rule::in(['remove', 'restore'])],
            'team_ids' => ['required', 'array', 'min:1'],
            'team_ids.*' => ['integer', 'min:1'],
        ], [
            'team_ids.required' => 'Select at least one team first.',
            'team_ids.min' => 'Select at least one team first.',
            'action.in' => 'Choose whether to remove or restore the selected teams.',
        ]);

        $teamIds = collect($attributes['team_ids'])
            ->map(fn (mixed $teamId): int => (int) $teamId)
            ->unique()
            ->values();

        $teams = $sweepstake->sweepstakeTeams()->whereIn('id', $teamIds->all());

        abort_unless($teams->count() === $teamIds->count(), 404);

        $isRestore = $attributes['action'] === 'restore';

        $teams->update([
            'is_included' => $isRestore,
            'is_removed' => ! $isRestore,
            'removed_reason' => $isRestore ? null : 'Removed by admin',
        ]);

        if (! $isRestore) {
            SweepstakePotTeam::query()
                ->whereIn('sweepstake_team_id', $teamIds->all())
                ->delete();
        }

        return back()->with('status', $isRestore ? 'Selected teams restored.' : 'Selected teams removed.');
    }

    public function update(Request $request, Sweepstake $sweepstake, SweepstakeTeam $sweepstakeTeam): RedirectResponse
    {
        $this->ensureAdmin($request, $sweepstake);
        abort_unless($sweepstakeTeam->sweepstake_id === $sweepstake->id, 404);

        if ($sweepstake->isLockedForChanges()) {
            return back()->withErrors([
                'team' => 'Team selections are locked after the draw.',
            ]);
        }

        $attributes = $request->validate([
            'is_included' => ['required', 'boolean'],
            'removed_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $isIncluded = (bool) $attributes['is_included'];

        $sweepstakeTeam->update([
            'is_included' => $isIncluded,
            'is_removed' => ! $isIncluded,
            'removed_reason' => $isIncluded ? null : ($attributes['removed_reason'] ?? 'Removed by admin'),
        ]);

        if (! $isIncluded) {
            $sweepstakeTeam->potAssignment()->delete();
        }

        return back()->with('status', 'Team selection updated.');
    }

    private function ensureAdmin(Request $request, Sweepstake $sweepstake): void
    {
        abort_unless($sweepstake->user_id === $request->user()->id, 403);
    }
}
