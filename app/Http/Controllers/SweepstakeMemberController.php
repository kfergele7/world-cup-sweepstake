<?php

namespace App\Http\Controllers;

use App\Models\Sweepstake;
use App\Models\SweepstakeMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SweepstakeMemberController extends Controller
{
    public function update(Request $request, Sweepstake $sweepstake, SweepstakeMember $member): RedirectResponse
    {
        $this->ensureAdmin($request, $sweepstake);
        abort_unless($member->sweepstake_id === $sweepstake->id, 404);

        if ($sweepstake->isLockedForChanges()) {
            return back()->withErrors([
                'member' => 'Member payment status is locked after the draw.',
            ]);
        }

        $attributes = $request->validate([
            'is_paid' => ['required', 'boolean'],
        ]);

        $isPaid = (bool) $attributes['is_paid'];

        $member->update([
            'is_paid' => $isPaid,
            'paid_at' => $isPaid ? now() : null,
        ]);

        return back()->with('status', 'Member payment status updated.');
    }

    private function ensureAdmin(Request $request, Sweepstake $sweepstake): void
    {
        abort_unless($sweepstake->user_id === $request->user()->id, 403);
    }
}
