<?php

namespace App\Http\Controllers;

use App\Actions\RunRankedPotDraw;
use App\Exceptions\DrawException;
use App\Models\Sweepstake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        return back()->with('status', 'Ranked pot draw completed.');
    }
}
