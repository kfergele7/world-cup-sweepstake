<?php

namespace App\Http\Controllers;

use App\Models\Sweepstake;
use App\Models\SweepstakeMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
                'name' => 'This sweepstake has already been drawn and is no longer accepting entrants.',
            ]);
        }

        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('sweepstake_members', 'email')->where('sweepstake_id', $sweepstake->id),
            ],
        ], [
            'email.unique' => 'This email has already joined this sweepstake.',
        ]);

        $member = SweepstakeMember::create([
            'sweepstake_id' => $sweepstake->id,
            'name' => $attributes['name'],
            'email' => isset($attributes['email']) ? Str::lower($attributes['email']) : null,
            'join_token' => Str::random(40),
            'source' => SweepstakeMember::SOURCE_JOIN_LINK,
        ]);

        return redirect()->route('entrants.show', $member->join_token)
            ->with('status', "Thanks {$member->name}. You are entered in the sweepstake.");
    }
}
