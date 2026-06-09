<?php

namespace App\Http\Controllers;

use App\Models\Prize;
use App\Models\Sweepstake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrizeController extends Controller
{
    public function store(Request $request, Sweepstake $sweepstake): RedirectResponse
    {
        $this->ensureAdmin($request, $sweepstake);

        if ($sweepstake->isLockedForChanges()) {
            return $this->redirectToSweepstakeTab($request, $sweepstake, 'settings-prizes')->withErrors([
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

        return $this->redirectToSweepstakeTab($request, $sweepstake, 'settings-prizes')
            ->with('status', $message);
    }

    public function update(Request $request, Sweepstake $sweepstake): RedirectResponse
    {
        $this->ensureAdmin($request, $sweepstake);

        if ($sweepstake->isLockedForChanges()) {
            return $this->redirectToSweepstakeTab($request, $sweepstake, 'settings-prizes')->withErrors([
                'prize' => 'Prizes are locked after the draw.',
            ]);
        }

        $attributes = $request->validate([
            'prizes' => ['required', 'array'],
            'prizes.*.id' => ['required', 'integer'],
            'prizes.*.position' => ['required', 'integer', 'min:1', 'max:48', 'distinct'],
            'prizes.*.label' => ['required', 'string', 'max:255'],
            'prizes.*.amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ], [
            'prizes.*.label.required' => 'Prize labels cannot be empty.',
            'prizes.*.amount.min' => 'Prize amount cannot be negative.',
        ]);

        DB::transaction(function () use ($attributes, $sweepstake): void {
            $prizes = collect($attributes['prizes'])
                ->mapWithKeys(fn (array $prizeAttributes): array => [
                    (int) $prizeAttributes['id'] => $sweepstake->prizes()
                        ->whereKey($prizeAttributes['id'])
                        ->firstOrFail(),
                ]);

            $prizes->each(function (Prize $prize): void {
                $prize->update(['position' => 1000 + $prize->id]);
            });

            foreach ($attributes['prizes'] as $prizeAttributes) {
                $prizes[(int) $prizeAttributes['id']]->update([
                    'position' => $prizeAttributes['position'],
                    'label' => $prizeAttributes['label'],
                    'amount' => $prizeAttributes['amount'],
                ]);
            }
        });

        return $this->redirectToSweepstakeTab($request, $sweepstake, 'settings-prizes')
            ->with('status', $this->prizeStatusMessage($sweepstake));
    }

    public function destroy(Request $request, Sweepstake $sweepstake, Prize $prize): RedirectResponse
    {
        $this->ensureAdmin($request, $sweepstake);
        abort_unless($prize->sweepstake_id === $sweepstake->id, 404);

        if ($sweepstake->isLockedForChanges()) {
            return $this->redirectToSweepstakeTab($request, $sweepstake, 'settings-prizes')->withErrors([
                'prize' => 'Prizes are locked after the draw.',
            ]);
        }

        $prize->delete();

        return $this->redirectToSweepstakeTab($request, $sweepstake, 'settings-prizes')
            ->with('status', 'Prize removed.');
    }

    private function ensureAdmin(Request $request, Sweepstake $sweepstake): void
    {
        abort_unless($sweepstake->user_id === $request->user()->id, 403);
    }

    private function prizeStatusMessage(Sweepstake $sweepstake): string
    {
        $message = 'Prizes saved.';

        if ((float) $sweepstake->prizes()->sum('amount') > $sweepstake->collectedPot()) {
            $message .= ' Prize payouts currently exceed the collected entry pot.';
        }

        return $message;
    }
}
