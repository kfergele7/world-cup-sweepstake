<?php

namespace App\Http\Controllers;

use App\Models\Sweepstake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PrizeController extends Controller
{
    public function store(Request $request, Sweepstake $sweepstake): RedirectResponse
    {
        $this->ensureAdmin($request, $sweepstake);

        if ($sweepstake->isLockedForChanges()) {
            return back()->withErrors([
                'prize' => 'Prizes are locked after the draw.',
            ]);
        }

        $attributes = $request->validate([
            'position' => ['required', 'integer', 'min:1', 'max:48'],
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        $sweepstake->prizes()->updateOrCreate(
            ['position' => $attributes['position']],
            ['label' => $attributes['label'], 'amount' => $attributes['amount']]
        );

        $message = 'Prize saved.';

        if ((float) $sweepstake->prizes()->sum('amount') > $sweepstake->collectedPot()) {
            $message .= ' Prize payouts currently exceed the collected entry pot.';
        }

        return back()->with('status', $message);
    }

    private function ensureAdmin(Request $request, Sweepstake $sweepstake): void
    {
        abort_unless($sweepstake->user_id === $request->user()->id, 403);
    }
}
