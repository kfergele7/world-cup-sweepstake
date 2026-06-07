<?php

namespace App\Http\Controllers;

use App\Models\Sweepstake;
use App\Models\SweepstakeTeam;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SweepstakeController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'entry_fee' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $sweepstake = DB::transaction(function () use ($request, $attributes): Sweepstake {
            $sweepstake = Sweepstake::create([
                'user_id' => $request->user()->id,
                'name' => $attributes['name'],
                'slug' => $this->uniqueSlug($attributes['name']),
                'join_code' => $this->uniqueJoinCode(),
                'entry_fee' => $attributes['entry_fee'] ?? 0,
                'currency' => strtoupper($attributes['currency'] ?? 'GBP'),
                'status' => Sweepstake::STATUS_OPEN,
                'draw_mode' => Sweepstake::DRAW_MODE_RANKED_POTS,
                'leftover_rule' => Sweepstake::LEFTOVER_REMOVE_LOWEST_RANKED,
            ]);

            Team::query()
                ->where('qualified_for_2026', true)
                ->orderBy('fifa_ranking')
                ->orderBy('name')
                ->get()
                ->each(fn (Team $team): SweepstakeTeam => SweepstakeTeam::create([
                    'sweepstake_id' => $sweepstake->id,
                    'team_id' => $team->id,
                    'sort_order' => $team->fifa_ranking,
                ]));

            return $sweepstake;
        });

        return redirect()->route('sweepstakes.show', $sweepstake)
            ->with('status', 'Sweepstake created. Share the join link when you are ready.');
    }

    public function show(Request $request, Sweepstake $sweepstake): View
    {
        $this->ensureAdmin($request, $sweepstake);

        $sweepstake->load([
            'members' => fn ($query) => $query
                ->with('assignments.team')
                ->orderBy('created_at')
                ->orderBy('id'),
            'sweepstakeTeams.team',
            'prizes',
        ]);

        $selectedTeams = $sweepstake->sweepstakeTeams
            ->filter(fn (SweepstakeTeam $sweepstakeTeam): bool => $sweepstakeTeam->is_included && ! $sweepstakeTeam->is_removed)
            ->sort(function (SweepstakeTeam $first, SweepstakeTeam $second): int {
                return [
                    $first->team->fifa_ranking ?? PHP_INT_MAX,
                    $first->team->name,
                ] <=> [
                    $second->team->fifa_ranking ?? PHP_INT_MAX,
                    $second->team->name,
                ];
            })
            ->values();

        return view('sweepstakes.show', [
            'sweepstake' => $sweepstake,
            'selectedTeams' => $selectedTeams,
            'removedTeams' => $sweepstake->sweepstakeTeams
                ->where('is_removed', true)
                ->sort(function (SweepstakeTeam $first, SweepstakeTeam $second): int {
                    return [
                        $first->team->fifa_ranking ?? PHP_INT_MAX,
                        $first->team->name,
                    ] <=> [
                        $second->team->fifa_ranking ?? PHP_INT_MAX,
                        $second->team->name,
                    ];
                })
                ->values(),
            'drawAssignmentCount' => $sweepstake->members->sum(fn ($member): int => $member->assignments->count()),
            'prizeWarning' => $this->prizeWarning($sweepstake),
        ]);
    }

    public function update(Request $request, Sweepstake $sweepstake): RedirectResponse
    {
        $this->ensureAdmin($request, $sweepstake);

        if ($sweepstake->isLockedForChanges()) {
            return back()->withErrors([
                'settings' => 'Sweepstake settings are locked after the draw.',
            ]);
        }

        $attributes = $request->validate([
            'sweepstake_name' => ['required', 'string', 'max:255'],
            'entry_fee' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'currency' => ['required', 'string', 'size:3'],
            'status' => ['required', Rule::in([
                Sweepstake::STATUS_DRAFT,
                Sweepstake::STATUS_OPEN,
            ])],
        ], [
            'sweepstake_name.required' => 'Please enter a sweepstake name.',
            'entry_fee.required' => 'Please enter an entry fee.',
            'entry_fee.numeric' => 'Entry fee must be a valid amount.',
            'entry_fee.min' => 'Entry fee cannot be negative.',
            'currency.size' => 'Currency must be a three-letter code, such as GBP.',
            'status.in' => 'Status can only be Draft or Open before the draw.',
        ]);

        $sweepstake->update([
            'name' => $attributes['sweepstake_name'],
            'entry_fee' => $attributes['entry_fee'],
            'currency' => Str::upper($attributes['currency']),
            'status' => $attributes['status'],
        ]);

        return back()->with('status', 'Sweepstake settings saved.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'sweepstake';

        do {
            $slug = $base.'-'.Str::lower(Str::random(6));
        } while (Sweepstake::where('slug', $slug)->exists());

        return $slug;
    }

    private function uniqueJoinCode(): string
    {
        do {
            $joinCode = Str::upper(Str::random(8));
        } while (Sweepstake::where('join_code', $joinCode)->exists());

        return $joinCode;
    }

    private function ensureAdmin(Request $request, Sweepstake $sweepstake): void
    {
        abort_unless($sweepstake->user_id === $request->user()->id, 403);
    }

    private function prizeWarning(Sweepstake $sweepstake): ?string
    {
        $totalPrizes = (float) $sweepstake->prizes->sum('amount');

        if ($totalPrizes > $sweepstake->collectedPot()) {
            return 'Prize payouts exceed the currently collected entry pot.';
        }

        return null;
    }
}
