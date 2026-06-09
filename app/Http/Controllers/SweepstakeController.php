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
        $customPotSummaries = $this->customPotSummaries($sweepstake, $memberCount);

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
            'customPotSummaries' => $customPotSummaries,
            'customPotWarnings' => $this->customPotWarnings($sweepstake, $customPotSummaries),
            'unassignedCustomTeamCount' => $this->unassignedCustomTeamCount($sweepstake, $selectedTeams),
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
            return $this->redirectToSweepstakeTab($request, $sweepstake, 'settings-prizes')->withErrors([
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

        return $this->redirectToSweepstakeTab($request, $sweepstake, 'settings-prizes')
            ->with('status', 'Sweepstake settings saved.');
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

    private function customPotSummaries(Sweepstake $sweepstake, int $memberCount): Collection
    {
        if ($sweepstake->pot_mode !== Sweepstake::POT_MODE_CUSTOM) {
            return collect();
        }

        return $sweepstake->pots
            ->values()
            ->map(function ($pot, int $index) use ($memberCount): array {
                $eligibleTeamCount = $pot->potTeams
                    ->filter(fn ($potTeam): bool => $potTeam->sweepstakeTeam
                        && $potTeam->sweepstakeTeam->sweepstake_id === $pot->sweepstake_id
                        && $potTeam->sweepstakeTeam->is_included
                        && ! $potTeam->sweepstakeTeam->is_removed)
                    ->count();
                $teamsPerEntrant = max(0, (int) $pot->teams_per_entrant);
                $neededTeamCount = $memberCount * $teamsPerEntrant;
                $drawnTeamCount = $teamsPerEntrant > 0
                    ? min($eligibleTeamCount, $neededTeamCount)
                    : 0;

                return [
                    'id' => $pot->id,
                    'number' => $index + 1,
                    'name' => $pot->name,
                    'position' => $pot->position,
                    'teams_per_entrant' => $teamsPerEntrant,
                    'assigned_team_count' => $eligibleTeamCount,
                    'needed_team_count' => $neededTeamCount,
                    'drawn_team_count' => $drawnTeamCount,
                    'unused_team_count' => max($eligibleTeamCount - $drawnTeamCount, 0),
                    'is_active' => $teamsPerEntrant > 0,
                    'has_enough_teams' => $teamsPerEntrant === 0 || $eligibleTeamCount >= $neededTeamCount,
                ];
            });
    }

    /**
     * @param  Collection<int, SweepstakeTeam>  $selectedTeams
     */
    private function unassignedCustomTeamCount(Sweepstake $sweepstake, Collection $selectedTeams): int
    {
        $selectedTeamIds = $selectedTeams->pluck('id')->unique()->values();
        $assignedTeamIds = $sweepstake->pots
            ->flatMap(fn ($pot) => $pot->potTeams)
            ->filter(fn ($potTeam): bool => $potTeam->sweepstakeTeam
                && $potTeam->sweepstakeTeam->is_included
                && ! $potTeam->sweepstakeTeam->is_removed)
            ->pluck('sweepstake_team_id')
            ->unique()
            ->values();

        return $selectedTeamIds->diff($assignedTeamIds)->count();
    }

    /**
     * @param  Collection<int, array<string, int|string|bool>>  $customPotSummaries
     * @return array<int, string>
     */
    private function customPotWarnings(Sweepstake $sweepstake, Collection $customPotSummaries): array
    {
        if ($sweepstake->pot_mode !== Sweepstake::POT_MODE_CUSTOM) {
            return [];
        }

        $warnings = [];

        if ($customPotSummaries->isEmpty()) {
            $warnings[] = 'Create at least one custom pot before running the draw.';
        }

        if ($customPotSummaries->isNotEmpty() && $customPotSummaries->where('is_active', true)->isEmpty()) {
            $warnings[] = 'At least one custom pot must give entrants teams.';
        }

        foreach ($customPotSummaries->where('is_active', true) as $summary) {
            if ($summary['has_enough_teams']) {
                continue;
            }

            $warnings[] = "{$summary['name']} has {$summary['assigned_team_count']} ".Str::plural('team', $summary['assigned_team_count'])." and needs {$summary['needed_team_count']} ".Str::plural('team', $summary['needed_team_count'])." to give each entrant {$summary['teams_per_entrant']} ".Str::plural('team', $summary['teams_per_entrant']).'.';
        }

        return $warnings;
    }
}
