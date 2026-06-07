<?php

namespace App\Actions;

use App\Exceptions\DrawException;
use App\Models\Sweepstake;
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
     *     used_teams: Collection<int, SweepstakeTeam>,
     *     leftover_teams: Collection<int, SweepstakeTeam>,
     *     pots: Collection<int, array{number: int, teams: Collection<int, SweepstakeTeam>}>
     * }
     */
    public function buildPlan(Sweepstake $sweepstake): array
    {
        $members = $sweepstake->paidMembers()
            ->orderBy('id')
            ->get();

        if ($members->count() < 2) {
            throw new DrawException('At least two paid members are required before running the draw.');
        }

        $selectedTeams = $this->rankedSelectedTeams($sweepstake);
        $memberCount = $members->count();

        if ($selectedTeams->count() < $memberCount) {
            throw new DrawException('There are not enough selected teams for the number of paid members.');
        }

        $teamsPerMember = intdiv($selectedTeams->count(), $memberCount);

        if ($teamsPerMember < 1) {
            throw new DrawException('Each paid member must receive at least one team.');
        }

        $usableTeamCount = $teamsPerMember * $memberCount;
        $usedTeams = $selectedTeams->take($usableTeamCount)->values();
        $leftoverTeams = $selectedTeams->slice($usableTeamCount)->values();

        $pots = $usedTeams
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
            'used_teams' => $usedTeams,
            'leftover_teams' => $leftoverTeams,
            'pots' => $pots,
        ];
    }

    /**
     * @return Collection<int, TeamAssignment>
     */
    public function handle(Sweepstake $sweepstake): Collection
    {
        return DB::transaction(function () use ($sweepstake): Collection {
            $sweepstake = Sweepstake::query()
                ->lockForUpdate()
                ->findOrFail($sweepstake->id);

            if ($sweepstake->assignments()->exists() || $sweepstake->drawn_at !== null) {
                throw new DrawException('This sweepstake has already been drawn.');
            }

            if ($sweepstake->draw_mode !== Sweepstake::DRAW_MODE_RANKED_POTS) {
                throw new DrawException('Only ranked pot draws are supported in the MVP.');
            }

            if ($sweepstake->leftover_rule !== Sweepstake::LEFTOVER_REMOVE_LOWEST_RANKED) {
                throw new DrawException('Only removing the lowest ranked leftover teams is supported in the MVP.');
            }

            $plan = $this->buildPlan($sweepstake);
            $assignments = collect();
            $assignedAt = now();

            foreach ($plan['pots'] as $pot) {
                $members = $plan['members']->shuffle()->values();
                $teams = $pot['teams']->shuffle()->values();

                foreach ($teams as $index => $sweepstakeTeam) {
                    $assignments->push(TeamAssignment::create([
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

            foreach ($plan['leftover_teams'] as $sweepstakeTeam) {
                $sweepstakeTeam->forceFill([
                    'is_included' => false,
                    'is_removed' => true,
                    'removed_reason' => 'Removed as a lowest-ranked leftover team during the draw.',
                ])->save();
            }

            $sweepstake->forceFill([
                'status' => Sweepstake::STATUS_DRAWN,
                'teams_per_member' => $plan['teams_per_member'],
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
}
