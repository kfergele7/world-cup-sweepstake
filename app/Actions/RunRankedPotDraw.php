<?php

namespace App\Actions;

use App\Exceptions\DrawException;
use App\Models\Sweepstake;
use App\Models\SweepstakeDraw;
use App\Models\SweepstakeMember;
use App\Models\SweepstakeTeam;
use App\Models\TeamAssignment;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RunRankedPotDraw
{
    /**
     * @return array{
     *     members: EloquentCollection<int, SweepstakeMember>,
     *     selected_team_count: int,
     *     member_count: int,
     *     teams_per_member: int,
     *     usable_team_count: int,
     *     leftover_team_count: int,
     *     leftover_strategy: ?string,
     *     pot_mode: string,
     *     used_teams: Collection<int, SweepstakeTeam>,
     *     leftover_teams: Collection<int, SweepstakeTeam>,
     *     custom_pot_summary: ?array<int, array<string, int|string>>,
     *     pots: Collection<int, array{number: int, name: ?string, teams_per_entrant: int, assigned_team_count: int, drawn_team_count: int, unused_team_count: int, teams: Collection<int, SweepstakeTeam>}>
     * }
     */
    public function buildPlan(Sweepstake $sweepstake, string $leftoverStrategy = SweepstakeDraw::LEFTOVER_STRATEGY_REMOVE_LOWEST_RANKED): array
    {
        if (! in_array($leftoverStrategy, $this->leftoverStrategies(), true)) {
            throw new DrawException('Choose how to handle leftover teams before running the draw.');
        }

        $members = $sweepstake->entrants()
            ->orderBy('id')
            ->get();

        if ($members->count() > Sweepstake::MAX_ENTRANTS) {
            throw new DrawException('A sweepstake can have up to 48 entrants.');
        }

        if ($members->count() < 2) {
            throw new DrawException('Add at least two entrants before running the draw.');
        }

        if ($sweepstake->pot_mode === Sweepstake::POT_MODE_CUSTOM) {
            return $this->buildCustomPotPlan($sweepstake, $members);
        }

        $selectedTeams = $this->rankedSelectedTeams($sweepstake);
        $memberCount = $members->count();

        if ($selectedTeams->count() < $memberCount) {
            throw new DrawException("You currently have {$memberCount} entrants but only {$selectedTeams->count()} teams available. Remove entrants or restore teams before running the draw.");
        }

        $teamsPerMember = intdiv($selectedTeams->count(), $memberCount);

        if ($teamsPerMember < 1) {
            throw new DrawException('Each entrant must receive at least one team.');
        }

        $usableTeamCount = $teamsPerMember * $memberCount;
        $leftoverTeams = $selectedTeams->slice($usableTeamCount)->values();
        $usedTeams = $leftoverStrategy === SweepstakeDraw::LEFTOVER_STRATEGY_ASSIGN_RANDOMLY
            ? $selectedTeams->values()
            : $selectedTeams->take($usableTeamCount)->values();
        $basePotTeams = $selectedTeams->take($usableTeamCount)->values();

        $pots = $basePotTeams
            ->chunk($memberCount)
            ->values()
            ->map(fn (Collection $teams, int $index): array => [
                'number' => $index + 1,
                'name' => null,
                'teams_per_entrant' => 1,
                'assigned_team_count' => $teams->count(),
                'drawn_team_count' => $teams->count(),
                'unused_team_count' => 0,
                'teams' => $teams->values(),
            ]);

        return [
            'members' => $members,
            'selected_team_count' => $selectedTeams->count(),
            'member_count' => $memberCount,
            'teams_per_member' => $teamsPerMember,
            'usable_team_count' => $usableTeamCount,
            'leftover_team_count' => $leftoverTeams->count(),
            'leftover_strategy' => $leftoverStrategy,
            'pot_mode' => Sweepstake::POT_MODE_AUTO,
            'used_teams' => $usedTeams,
            'leftover_teams' => $leftoverTeams,
            'custom_pot_summary' => null,
            'pots' => $pots,
        ];
    }

    /**
     * @return Collection<int, TeamAssignment>
     */
    public function handle(
        Sweepstake $sweepstake,
        ?string $rerunReason = null,
        string $leftoverStrategy = SweepstakeDraw::LEFTOVER_STRATEGY_REMOVE_LOWEST_RANKED,
    ): Collection {
        return DB::transaction(function () use ($sweepstake, $rerunReason, $leftoverStrategy): Collection {
            $sweepstake = Sweepstake::query()
                ->lockForUpdate()
                ->findOrFail($sweepstake->id);

            $activeDraw = $sweepstake->draws()
                ->where('status', SweepstakeDraw::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            $reason = $rerunReason ? trim($rerunReason) : null;
            $reason = $reason === '' ? null : $reason;

            if ($activeDraw && $reason === null) {
                throw new DrawException('This sweepstake has already been drawn.');
            }

            if ($sweepstake->draw_mode !== Sweepstake::DRAW_MODE_RANKED_POTS) {
                throw new DrawException('Only ranked pot draws are supported in the MVP.');
            }

            if ($sweepstake->leftover_rule !== Sweepstake::LEFTOVER_REMOVE_LOWEST_RANKED) {
                throw new DrawException('Only removing the lowest ranked leftover teams is supported in the MVP.');
            }

            $plan = $this->buildPlan($sweepstake, $leftoverStrategy);
            $usesCustomPots = $plan['pot_mode'] === Sweepstake::POT_MODE_CUSTOM;
            $assignments = collect();
            $assignedAt = now();
            $nextVersionNumber = (int) $sweepstake->draws()->max('version_number') + 1;

            if ($activeDraw) {
                $activeDraw->update([
                    'status' => SweepstakeDraw::STATUS_SUPERSEDED,
                ]);
            }

            $draw = SweepstakeDraw::create([
                'sweepstake_id' => $sweepstake->id,
                'version_number' => $nextVersionNumber,
                'status' => SweepstakeDraw::STATUS_ACTIVE,
                'reason' => $reason,
                'ran_at' => $assignedAt,
                'rerun_of_draw_id' => $activeDraw?->id,
                'pot_mode' => $plan['pot_mode'],
                'custom_pot_summary' => $usesCustomPots ? $plan['custom_pot_summary'] : null,
                'leftover_strategy' => $usesCustomPots ? null : $leftoverStrategy,
                'selected_team_count' => $plan['selected_team_count'],
                'base_teams_per_member' => $plan['teams_per_member'],
                'leftover_team_count' => $plan['leftover_team_count'],
            ]);

            foreach ($plan['pots'] as $pot) {
                $members = $plan['members']->shuffle()->values();
                $teams = $pot['teams']->shuffle()->values();
                $teamsPerEntrant = $pot['teams_per_entrant'] ?? 1;
                $teamIndex = 0;

                foreach ($members as $member) {
                    for ($slot = 0; $slot < $teamsPerEntrant; $slot++) {
                        $sweepstakeTeam = $teams[$teamIndex] ?? null;
                        $teamIndex++;

                        if (! $sweepstakeTeam) {
                            continue;
                        }

                        $assignments->push(TeamAssignment::create([
                            'sweepstake_draw_id' => $draw->id,
                            'sweepstake_id' => $sweepstake->id,
                            'sweepstake_member_id' => $member->id,
                            'team_id' => $sweepstakeTeam->team_id,
                            'pot_number' => $pot['number'],
                            'assigned_at' => $assignedAt,
                        ]));

                        $sweepstakeTeam->forceFill([
                            'pot_number' => $pot['number'],
                            'sort_order' => $sweepstakeTeam->team->fifa_ranking,
                        ])->save();
                    }
                }
            }

            if (! $usesCustomPots && $leftoverStrategy === SweepstakeDraw::LEFTOVER_STRATEGY_ASSIGN_RANDOMLY) {
                $members = $plan['members']->shuffle()->take($plan['leftover_team_count'])->values();
                $teams = $plan['leftover_teams']->shuffle()->values();

                foreach ($teams as $index => $sweepstakeTeam) {
                    $assignments->push(TeamAssignment::create([
                        'sweepstake_draw_id' => $draw->id,
                        'sweepstake_id' => $sweepstake->id,
                        'sweepstake_member_id' => $members[$index]->id,
                        'team_id' => $sweepstakeTeam->team_id,
                        'pot_number' => $plan['teams_per_member'] + 1,
                        'assigned_at' => $assignedAt,
                    ]));

                    $sweepstakeTeam->forceFill([
                        'pot_number' => $plan['teams_per_member'] + 1,
                        'sort_order' => $sweepstakeTeam->team->fifa_ranking,
                    ])->save();
                }
            } elseif (! $usesCustomPots) {
                foreach ($plan['leftover_teams'] as $sweepstakeTeam) {
                    $sweepstakeTeam->forceFill([
                        'is_included' => false,
                        'is_removed' => true,
                        'removed_reason' => 'Removed as a lowest-ranked leftover team during the draw.',
                    ])->save();
                }
            }

            $sweepstake->forceFill([
                'status' => Sweepstake::STATUS_DRAWN,
                'teams_per_member' => ! $usesCustomPots && $leftoverStrategy === SweepstakeDraw::LEFTOVER_STRATEGY_ASSIGN_RANDOMLY
                    ? null
                    : $plan['teams_per_member'],
                'drawn_at' => $assignedAt,
            ])->save();

            return $assignments;
        });
    }

    /**
     * @param  EloquentCollection<int, SweepstakeMember>  $members
     * @return array{
     *     members: EloquentCollection<int, SweepstakeMember>,
     *     selected_team_count: int,
     *     member_count: int,
     *     teams_per_member: int,
     *     usable_team_count: int,
     *     leftover_team_count: int,
     *     leftover_strategy: null,
     *     pot_mode: string,
     *     used_teams: Collection<int, SweepstakeTeam>,
     *     leftover_teams: Collection<int, SweepstakeTeam>,
     *     custom_pot_summary: array<int, array<string, int|string>>,
     *     pots: Collection<int, array{number: int, name: string, teams_per_entrant: int, assigned_team_count: int, drawn_team_count: int, unused_team_count: int, teams: Collection<int, SweepstakeTeam>}>
     * }
     */
    private function buildCustomPotPlan(Sweepstake $sweepstake, EloquentCollection $members): array
    {
        $selectedTeams = $this->rankedSelectedTeams($sweepstake);
        $memberCount = $members->count();

        if ($selectedTeams->count() < $memberCount) {
            throw new DrawException("You currently have {$memberCount} entrants but only {$selectedTeams->count()} teams available. Remove entrants or restore teams before running the draw.");
        }

        $pots = $sweepstake->pots()
            ->with([
                'potTeams.sweepstakeTeam.team',
            ])
            ->get();

        if ($pots->isEmpty()) {
            throw new DrawException('Create at least one custom pot before running the draw.');
        }

        $plannedPots = $pots
            ->values()
            ->map(function ($pot, int $index) use ($memberCount): array {
                $teamsPerEntrant = max(0, (int) $pot->teams_per_entrant);
                $eligibleTeams = $pot->potTeams
                    ->filter(fn ($potTeam): bool => $potTeam->sweepstakeTeam
                        && $potTeam->sweepstakeTeam->sweepstake_id === $pot->sweepstake_id
                        && $potTeam->sweepstakeTeam->is_included
                        && ! $potTeam->sweepstakeTeam->is_removed)
                    ->sortBy(fn ($potTeam): string => sprintf('%05d-%08d', $potTeam->position ?? 99999, $potTeam->id))
                    ->map(fn ($potTeam): SweepstakeTeam => $potTeam->sweepstakeTeam)
                    ->values();
                $neededTeamCount = $memberCount * $teamsPerEntrant;

                if ($teamsPerEntrant > 0 && $eligibleTeams->count() < $neededTeamCount) {
                    throw new DrawException("{$pot->name} has {$eligibleTeams->count()} ".str('team')->plural($eligibleTeams->count())." and needs {$neededTeamCount} ".str('team')->plural($neededTeamCount)." to give each entrant {$teamsPerEntrant} ".str('team')->plural($teamsPerEntrant).'.');
                }

                $drawnTeams = $teamsPerEntrant > 0
                    ? $eligibleTeams->shuffle()->take($neededTeamCount)->values()
                    : collect();

                return [
                    'number' => $index + 1,
                    'name' => $pot->name,
                    'teams_per_entrant' => $teamsPerEntrant,
                    'assigned_team_count' => $eligibleTeams->count(),
                    'drawn_team_count' => $drawnTeams->count(),
                    'unused_team_count' => max($eligibleTeams->count() - $drawnTeams->count(), 0),
                    'teams' => $drawnTeams,
                ];
            });
        $activePots = $plannedPots
            ->filter(fn (array $pot): bool => $pot['teams_per_entrant'] > 0)
            ->values();

        if ($activePots->isEmpty()) {
            throw new DrawException('At least one custom pot must give entrants teams.');
        }

        $teamsPerMember = $activePots->sum('teams_per_entrant');
        $drawnTeams = $activePots
            ->flatMap(fn (array $pot): Collection => $pot['teams'])
            ->values();
        $unusedTeams = $plannedPots->sum('unused_team_count');
        $customPotSummary = $plannedPots
            ->map(fn (array $pot): array => [
                'number' => $pot['number'],
                'name' => $pot['name'],
                'teams_per_entrant' => $pot['teams_per_entrant'],
                'assigned_team_count' => $pot['assigned_team_count'],
                'drawn_team_count' => $pot['drawn_team_count'],
                'unused_team_count' => $pot['unused_team_count'],
            ])
            ->values()
            ->all();

        return [
            'members' => $members,
            'selected_team_count' => $plannedPots->sum('assigned_team_count'),
            'member_count' => $memberCount,
            'teams_per_member' => $teamsPerMember,
            'usable_team_count' => $drawnTeams->count(),
            'leftover_team_count' => $unusedTeams,
            'leftover_strategy' => null,
            'pot_mode' => Sweepstake::POT_MODE_CUSTOM,
            'used_teams' => $drawnTeams,
            'leftover_teams' => collect(),
            'custom_pot_summary' => $customPotSummary,
            'pots' => $activePots,
        ];
    }

    /**
     * @return Collection<int, SweepstakeTeam>
     */
    private function rankedSelectedTeams(Sweepstake $sweepstake): Collection
    {
        return $sweepstake->selectedSweepstakeTeams()
            ->with('team')
            ->get()
            ->sort(function (SweepstakeTeam $first, SweepstakeTeam $second): int {
                return [
                    $first->team->fifa_ranking ?? PHP_INT_MAX,
                    $first->sort_order ?? PHP_INT_MAX,
                    $first->team->name,
                ] <=> [
                    $second->team->fifa_ranking ?? PHP_INT_MAX,
                    $second->sort_order ?? PHP_INT_MAX,
                    $second->team->name,
                ];
            })
            ->values();
    }

    /**
     * @return array<int, string>
     */
    private function leftoverStrategies(): array
    {
        return [
            SweepstakeDraw::LEFTOVER_STRATEGY_REMOVE_LOWEST_RANKED,
            SweepstakeDraw::LEFTOVER_STRATEGY_ASSIGN_RANDOMLY,
        ];
    }
}
