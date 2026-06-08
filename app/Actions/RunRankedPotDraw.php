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
     *     leftover_strategy: string,
     *     used_teams: Collection<int, SweepstakeTeam>,
     *     leftover_teams: Collection<int, SweepstakeTeam>,
     *     pots: Collection<int, array{number: int, teams: Collection<int, SweepstakeTeam>}>
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
            'used_teams' => $usedTeams,
            'leftover_teams' => $leftoverTeams,
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
                'leftover_strategy' => $leftoverStrategy,
                'selected_team_count' => $plan['selected_team_count'],
                'base_teams_per_member' => $plan['teams_per_member'],
                'leftover_team_count' => $plan['leftover_team_count'],
            ]);

            foreach ($plan['pots'] as $pot) {
                $members = $plan['members']->shuffle()->values();
                $teams = $pot['teams']->shuffle()->values();

                foreach ($teams as $index => $sweepstakeTeam) {
                    $assignments->push(TeamAssignment::create([
                        'sweepstake_draw_id' => $draw->id,
                        'sweepstake_id' => $sweepstake->id,
                        'sweepstake_member_id' => $members[$index]->id,
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

            if ($leftoverStrategy === SweepstakeDraw::LEFTOVER_STRATEGY_ASSIGN_RANDOMLY) {
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
            } else {
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
                'teams_per_member' => $leftoverStrategy === SweepstakeDraw::LEFTOVER_STRATEGY_ASSIGN_RANDOMLY
                    ? null
                    : $plan['teams_per_member'],
                'drawn_at' => $assignedAt,
            ])->save();

            return $assignments;
        });
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
