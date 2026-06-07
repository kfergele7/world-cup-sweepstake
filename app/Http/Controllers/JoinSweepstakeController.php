<?php

namespace App\Http\Controllers;

use App\Models\Sweepstake;
use App\Models\SweepstakeMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class JoinSweepstakeController extends Controller
{
    public function show(string $joinCode): View
    {
        $sweepstake = Sweepstake::where('join_code', Str::upper($joinCode))->firstOrFail();

        return view('join.show', [
            'sweepstake' => $sweepstake,
        ]);
    }

    public function store(Request $request, string $joinCode): RedirectResponse
    {
        $sweepstake = Sweepstake::where('join_code', Str::upper($joinCode))->firstOrFail();

        if ($sweepstake->isLockedForChanges()) {
            return back()->withErrors([
                'name' => 'This sweepstake has already been drawn and is no longer accepting members.',
            ]);
        }

        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $member = SweepstakeMember::create([
            'sweepstake_id' => $sweepstake->id,
            'name' => $attributes['name'],
            'email' => $attributes['email'] ?? null,
            'join_token' => Str::random(40),
        ]);

        return redirect()->route('join.show', $sweepstake->join_code)
            ->with('status', "Thanks {$member->name}. The admin can now mark you as paid.");
    }
}
