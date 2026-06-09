<?php

namespace App\Http\Controllers;

use App\Models\Sweepstake;
use App\Models\SweepstakePot;
use App\Models\SweepstakePotTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SweepstakePotController extends Controller
{
    public function store(Request $request, Sweepstake $sweepstake): RedirectResponse
    {
        $this->ensureAdmin($request, $sweepstake);

        if ($sweepstake->isLockedForChanges()) {
            return $this->redirectToSweepstakeTab($request, $sweepstake, 'pots')->withErrors([
                'custom_pots' => 'Custom pots are locked after the draw.',
            ]);
        }

        $attributes = $request->validate([
            'name' => ['nullable', 'string', 'max:80'],
            'teams_per_entrant' => ['required', 'integer', 'min:0', 'max:48'],
        ]);

        $position = (int) $sweepstake->pots()->max('position') + 1;
        $name = trim($attributes['name'] ?? '') ?: "Pot {$position}";

        $sweepstake->pots()->create([
            'name' => $name,
            'position' => $position,
            'teams_per_entrant' => $attributes['teams_per_entrant'],
        ]);

        return $this->redirectToSweepstakeTab($request, $sweepstake, 'pots')
            ->with('status', 'Custom pot created.');
    }

    public function update(Request $request, Sweepstake $sweepstake, SweepstakePot $pot): RedirectResponse
    {
        $this->ensureAdmin($request, $sweepstake);
        abort_unless($pot->sweepstake_id === $sweepstake->id, 404);

        if ($sweepstake->isLockedForChanges()) {
            return $this->redirectToSweepstakeTab($request, $sweepstake, 'pots')->withErrors([
                'custom_pots' => 'Custom pots are locked after the draw.',
            ]);
        }

        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'position' => ['required', 'integer', 'min:1', 'max:99'],
            'teams_per_entrant' => ['required', 'integer', 'min:0', 'max:48'],
        ], [
            'name.required' => 'Please enter a pot name.',
            'teams_per_entrant.required' => 'Please enter how many teams each entrant gets from this pot.',
            'teams_per_entrant.integer' => 'Teams per entrant must be a whole number.',
        ]);

        $pot->update([
            'name' => trim($attributes['name']),
            'position' => $attributes['position'],
            'teams_per_entrant' => $attributes['teams_per_entrant'],
        ]);

        return $this->redirectToSweepstakeTab($request, $sweepstake, 'pots')
            ->with('status', 'Custom pot saved.');
    }

    public function destroy(Request $request, Sweepstake $sweepstake, SweepstakePot $pot): RedirectResponse
    {
        $this->ensureAdmin($request, $sweepstake);
        abort_unless($pot->sweepstake_id === $sweepstake->id, 404);

        if ($sweepstake->isLockedForChanges()) {
            return $this->redirectToSweepstakeTab($request, $sweepstake, 'pots')->withErrors([
                'custom_pots' => 'Custom pots are locked after the draw.',
            ]);
        }

        if ($pot->potTeams()->exists()) {
            return $this->redirectToSweepstakeTab($request, $sweepstake, 'pots')->withErrors([
                'custom_pots' => 'Only empty custom pots can be deleted.',
            ]);
        }

        $pot->delete();

        return $this->redirectToSweepstakeTab($request, $sweepstake, 'pots')
            ->with('status', 'Custom pot deleted.');
    }

    public function assignments(Request $request, Sweepstake $sweepstake): RedirectResponse
    {
        $this->ensureAdmin($request, $sweepstake);

        if ($sweepstake->isLockedForChanges()) {
            return $this->redirectToSweepstakeTab($request, $sweepstake, 'pots')->withErrors([
                'custom_pots' => 'Custom pot assignments are locked after the draw.',
            ]);
        }

        $attributes = $request->validate([
            'assignments' => ['nullable', 'array'],
            'assignments.*' => ['nullable', 'integer', 'min:1'],
        ], [
            'assignments.*.integer' => 'Choose a valid custom pot for each team.',
        ]);

        $assignments = collect($attributes['assignments'] ?? []);

        if ($assignments->keys()->contains(fn (int|string $teamId): bool => ! ctype_digit((string) $teamId))) {
            return $this->redirectToSweepstakeTab($request, $sweepstake, 'pots')->withErrors([
                'custom_pots' => 'Only included teams can be assigned to custom pots.',
            ]);
        }

        $selectedTeamIds = $sweepstake->selectedSweepstakeTeams()
            ->orderBy('id')
            ->pluck('id');

        $requestedTeamIds = $assignments
            ->keys()
            ->map(fn (int|string $teamId): int => (int) $teamId)
            ->unique()
            ->values();

        if ($requestedTeamIds->diff($selectedTeamIds)->isNotEmpty()) {
            return $this->redirectToSweepstakeTab($request, $sweepstake, 'pots')->withErrors([
                'custom_pots' => 'Only included teams can be assigned to custom pots.',
            ]);
        }

        $requestedPotIds = $assignments
            ->filter(fn (mixed $potId): bool => filled($potId))
            ->map(fn (mixed $potId): int => (int) $potId)
            ->unique()
            ->values();

        $validPotIds = $sweepstake->pots()
            ->whereIn('id', $requestedPotIds->all())
            ->pluck('id');

        abort_unless($requestedPotIds->diff($validPotIds)->isEmpty(), 404);

        DB::transaction(function () use ($assignments, $selectedTeamIds, $sweepstake): void {
            $sweepstakePotIds = $sweepstake->pots()->pluck('id');

            SweepstakePotTeam::query()
                ->whereIn('sweepstake_pot_id', $sweepstakePotIds->all())
                ->delete();

            $positions = [];

            foreach ($selectedTeamIds as $selectedTeamId) {
                $potId = $assignments->get((string) $selectedTeamId)
                    ?? $assignments->get($selectedTeamId);

                if (! filled($potId)) {
                    continue;
                }

                $potId = (int) $potId;
                $positions[$potId] = ($positions[$potId] ?? 0) + 1;

                SweepstakePotTeam::create([
                    'sweepstake_pot_id' => $potId,
                    'sweepstake_team_id' => $selectedTeamId,
                    'position' => $positions[$potId],
                ]);
            }
        });

        return $this->redirectToSweepstakeTab($request, $sweepstake, 'pots')
            ->with('status', 'Custom pot assignments saved.');
    }

    public function bulkAssignments(Request $request, Sweepstake $sweepstake): RedirectResponse
    {
        $this->ensureAdmin($request, $sweepstake);

        if ($sweepstake->isLockedForChanges()) {
            return $this->redirectToSweepstakeTab($request, $sweepstake, 'pots')->withErrors([
                'custom_pots' => 'Custom pot assignments are locked after the draw.',
            ]);
        }

        $attributes = $request->validate([
            'team_ids' => ['required', 'array', 'min:1'],
            'team_ids.*' => ['integer', 'min:1'],
            'target_pot_id' => ['nullable', 'integer', 'min:1'],
        ], [
            'team_ids.required' => 'Select at least one team first.',
            'team_ids.min' => 'Select at least one team first.',
            'target_pot_id.integer' => 'Choose a valid custom pot.',
        ]);

        $teamIds = collect($attributes['team_ids'])
            ->map(fn (mixed $teamId): int => (int) $teamId)
            ->unique()
            ->values();

        $selectedTeams = $sweepstake->selectedSweepstakeTeams()
            ->whereIn('id', $teamIds->all())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id');

        if ($selectedTeams->count() !== $teamIds->count()) {
            return $this->redirectToSweepstakeTab($request, $sweepstake, 'pots')->withErrors([
                'custom_pots' => 'Only included teams can be assigned to custom pots.',
            ]);
        }

        $targetPotId = filled($attributes['target_pot_id'] ?? null)
            ? (int) $attributes['target_pot_id']
            : null;

        if ($targetPotId !== null) {
            abort_unless($sweepstake->pots()->whereKey($targetPotId)->exists(), 404);
        }

        DB::transaction(function () use ($selectedTeams, $targetPotId): void {
            SweepstakePotTeam::query()
                ->whereIn('sweepstake_team_id', $selectedTeams->all())
                ->delete();

            if ($targetPotId === null) {
                return;
            }

            $position = (int) SweepstakePotTeam::query()
                ->where('sweepstake_pot_id', $targetPotId)
                ->max('position');

            foreach ($selectedTeams as $selectedTeamId) {
                $position++;

                SweepstakePotTeam::create([
                    'sweepstake_pot_id' => $targetPotId,
                    'sweepstake_team_id' => $selectedTeamId,
                    'position' => $position,
                ]);
            }
        });

        return $this->redirectToSweepstakeTab($request, $sweepstake, 'pots')
            ->with('status', $targetPotId === null
                ? 'Selected teams moved to Unassigned.'
                : 'Selected teams moved to the custom pot.');
    }

    private function ensureAdmin(Request $request, Sweepstake $sweepstake): void
    {
        abort_unless($sweepstake->user_id === $request->user()->id, 403);
    }
}
