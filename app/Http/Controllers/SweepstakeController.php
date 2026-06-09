<?php

namespace App\Http\Controllers;

use App\Models\Sweepstake;
use App\Models\SweepstakeDraw;
use App\Models\SweepstakeTeam;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
            'pot_mode' => ['nullable', Rule::in([
                Sweepstake::POT_MODE_AUTO,
                Sweepstake::POT_MODE_CUSTOM,
            ])],
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
                'pot_mode' => $attributes['pot_mode'] ?? Sweepstake::POT_MODE_AUTO,
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
                ->orderBy('created_at')
                ->orderBy('id'),
            'draws' => fn ($query) => $query
                ->with([
                    'assignments.member',
                    'assignments.team',
                ])
                ->orderBy('version_number'),
            'sweepstakeTeams.team',
            'sweepstakeTeams.potAssignment',
            'pots.potTeams.sweepstakeTeam.team',
            'prizes',
        ]);

        $activeDraw = $sweepstake->draws->firstWhere('status', SweepstakeDraw::STATUS_ACTIVE);

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
        $memberCount = $sweepstake->members->count();
        $selectedTeamCount = $selectedTeams->count();
        $leftoverTeamCount = $memberCount > 0 ? $selectedTeamCount % $memberCount : 0;
        $baseTeamsPerMember = $memberCount > 0 ? intdiv($selectedTeamCount, max($memberCount, 1)) : 0;

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
            'activeDraw' => $activeDraw,
            'draws' => $sweepstake->draws,
            'drawAssignmentCount' => $activeDraw?->assignments->count() ?? 0,
            'memberCount' => $memberCount,
            'selectedTeamCount' => $selectedTeamCount,
            'leftoverTeamCount' => $leftoverTeamCount,
            'baseTeamsPerMember' => $baseTeamsPerMember,
            'customPotWarnings' => $this->customPotWarnings($sweepstake, $selectedTeams, $memberCount),
            'entrantCapacity' => $sweepstake->maximumEntrants(),
            'latestCancelledDraw' => $sweepstake->draws
                ->sortByDesc('version_number')
                ->firstWhere('status', SweepstakeDraw::STATUS_CANCELLED),
            'totalPrizePayout' => (float) $sweepstake->prizes->sum('amount'),
            'expectedEntryPot' => (float) $sweepstake->entry_fee * $memberCount,
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
            'pot_mode' => ['required', Rule::in([
                Sweepstake::POT_MODE_AUTO,
                Sweepstake::POT_MODE_CUSTOM,
            ])],
        ], [
            'sweepstake_name.required' => 'Please enter a sweepstake name.',
            'entry_fee.required' => 'Please enter an entry fee.',
            'entry_fee.numeric' => 'Entry fee must be a valid amount.',
            'entry_fee.min' => 'Entry fee cannot be negative.',
            'currency.size' => 'Currency must be a three-letter code, such as GBP.',
            'status.in' => 'Status can only be Draft or Open before the draw.',
            'pot_mode.in' => 'Choose Auto pots or Custom pots before running the draw.',
        ]);

        $sweepstake->update([
            'name' => $attributes['sweepstake_name'],
            'entry_fee' => $attributes['entry_fee'],
            'currency' => Str::upper($attributes['currency']),
            'status' => $attributes['status'],
            'pot_mode' => $attributes['pot_mode'],
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

    /**
     * @param  Collection<int, SweepstakeTeam>  $selectedTeams
     * @return array<int, string>
     */
    private function customPotWarnings(Sweepstake $sweepstake, Collection $selectedTeams, int $memberCount): array
    {
        if ($sweepstake->pot_mode !== Sweepstake::POT_MODE_CUSTOM) {
            return [];
        }

        $warnings = [];

        if ($sweepstake->pots->isEmpty()) {
            $warnings[] = 'Create at least one custom pot before running the draw.';
        }

        $selectedTeamIds = $selectedTeams->pluck('id')->unique()->values();
        $assignedTeamIds = $sweepstake->pots
            ->flatMap(fn ($pot) => $pot->potTeams)
            ->pluck('sweepstake_team_id')
            ->unique()
            ->values();

        $invalidAssignmentCount = $assignedTeamIds->diff($selectedTeamIds)->count();

        if ($invalidAssignmentCount > 0) {
            $warnings[] = 'Some custom pot assignments point to teams that are no longer included. Save assignments to clear them.';
        }

        $unassignedCount = $selectedTeamIds->diff($assignedTeamIds)->count();

        if ($unassignedCount > 0) {
            $verb = $unassignedCount === 1 ? 'is' : 'are';
            $warnings[] = "{$unassignedCount} included ".Str::plural('team', $unassignedCount)." {$verb} not assigned to a custom pot.";
        }

        if ($memberCount > 0) {
            foreach ($sweepstake->pots as $pot) {
                $includedPotTeamCount = $pot->potTeams
                    ->filter(fn ($potTeam): bool => (bool) $potTeam->sweepstakeTeam?->is_included && ! $potTeam->sweepstakeTeam?->is_removed)
                    ->count();

                if ($includedPotTeamCount !== $memberCount) {
                    $warnings[] = "{$pot->name} has {$includedPotTeamCount} ".Str::plural('team', $includedPotTeamCount)."; it needs exactly {$memberCount}.";
                }
            }
        }

        return $warnings;
    }
}
