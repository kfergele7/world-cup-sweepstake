<?php

namespace App\Http\Controllers;

use App\Models\Sweepstake;
use App\Models\SweepstakeTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SweepstakeTeamController extends Controller
{
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

        return back()->with('status', 'Team selection updated.');
    }

    private function ensureAdmin(Request $request, Sweepstake $sweepstake): void
    {
        abort_unless($sweepstake->user_id === $request->user()->id, 403);
    }
}
