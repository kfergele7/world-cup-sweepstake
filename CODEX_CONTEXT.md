# World Cup Sweepstake App Codex Context

## Product Overview

World Cup Sweepstake App lets an admin create and manage a private 2026 FIFA World Cup sweepstake.

Admins can create a sweepstake, share a private join link or PIN-style code, track entrants, add entrants manually, mark entrants as paid, choose which teams are included, set prize payouts and run a fair draw. Entrants can join from the public link without creating a full user account in the MVP, then use a private tokenised link to view their own assigned teams after the draw. Entrants with email addresses are notified when a draw or reasoned re-run is completed.

## Stack

- Laravel 13
- Vue 3
- Vite
- Tailwind CSS 4
- SQLite locally by default
- PHPUnit for tests

## Core Draw Logic

The default draw mode is `ranked_pots`.

The ranked pot draw flow is:

1. Load selected teams for the sweepstake only.
2. Sort by FIFA ranking or strength, strongest first.
3. Count all entrants in the sweepstake.
4. Calculate `teams_per_member = floor(selected_team_count / member_count)`.
5. Calculate `usable_team_count = teams_per_member * member_count`.
6. Remove leftovers from the bottom of the rankings by default.
7. Split usable teams into pots where each pot contains one team per entrant.
8. Randomly assign one team from each pot to each entrant.
9. Persist assignments against an active draw version and lock the sweepstake as drawn.

Example: 7 entrants and 48 selected teams means 6 teams per entrant, 42 teams used, 6 lowest-ranked leftovers removed, then 6 pots of 7 teams.

The current implementation is `App\Actions\RunRankedPotDraw`. Re-runs require a plain-text reason, supersede the previous active draw and preserve previous assignments in draw history.

## Core Models

- `User`: authenticated sweepstake admin.
- `Sweepstake`: admin-owned sweepstake with join code, entry fee, status, draw mode and draw metadata.
- `SweepstakeDraw`: per-sweepstake draw version with version number, active/superseded status, optional re-run reason and run timestamp.
- `SweepstakeMember`: non-account entrant record with name, optional email, source, paid state and optional admin marker.
- `Team`: global master team record.
- `SweepstakeTeam`: per-sweepstake inclusion/removal state for a global team.
- `TeamAssignment`: persisted draw result tied to a specific `SweepstakeDraw`.
- `Prize`: per-sweepstake prize payout row.

The master team seed lives in `Database\Seeders\TeamSeeder`. It contains a working 48-team 2026 list with April 2026 rankings. Source references used during setup were FIFA's qualified teams page and World Cup Wiki's 2026 team/ranking summary. Refresh rankings from FIFA before production launch or any user-facing claim of current accuracy.

## Important Rules

- Use all entrants in the sweepstake for the MVP draw, whether paid or unpaid.
- Treat paid/unpaid as an admin tracking field only at this stage.
- Record entrant source as `manual`, `join_link` or `pin`.
- Allow the owning admin to edit sweepstake name, entry fee, currency and draft/open status before the draw.
- Require at least 2 entrants before a draw.
- Require enough selected teams for all entrants.
- Every entrant must receive the same number of teams.
- Remove leftovers from the lowest-ranked teams by default.
- Do not allow duplicate team assignments within the same draw version.
- Allow a controlled re-run only with a required reason; keep setup locked and re-randomise the current included entrants/teams.
- Preserve previous draw assignments and mark older draw versions as superseded.
- After a draw, lock sweepstake settings, entrant adds, edits, removals, payment changes, team selection and prize changes.
- Send draw result emails to entrants who have an email address, using Laravel's configured mailer.
- Warn when prize payouts exceed the collected paid-entry pot.
- Team removal and restoration must be scoped to a sweepstake through `sweepstake_teams`, never by mutating the global team row.
- Public entrant result pages must use `join_token`, not incremental entrant IDs, and must not expose entrant emails, other entrants' details or admin-only controls.

## Admin Journey

1. Register or sign in as an admin.
2. Create a sweepstake from the dashboard.
3. Edit basic sweepstake settings such as name, entry fee, currency and draft/open status before the draw.
4. Share the join link or join code.
5. Review joined entrants, add offline entrants manually and mark paid entrants.
6. Remove entrants before the draw if needed.
7. Bulk remove or restore teams for that sweepstake.
8. Add prize payouts.
9. Run the ranked pot draw.
10. Review persisted assignments grouped by entrant and copy private entrant view links if needed.
11. If needed, re-run the draw with a clear reason; the previous draw remains visible as superseded history.

## Entrant Journey

1. Open the public join link.
2. Enter name and optional email.
3. Land on a private entrant page backed by their `join_token`.
4. Before the draw, see a waiting message.
5. After the draw, view only their own assigned teams from the same private link.
6. If the draw is re-run, see their own draw history and the reason without seeing other entrants' private details.

## Codex Workflow Expectations

- Start tasks by checking `pwd`, `git status`, `git remote -v` and recent history.
- Pull from the remote when appropriate.
- Inspect actual render paths before UI edits: route, controller/view, layout, JS/Vue entrypoint, component.
- Keep changes scoped to the requested task.
- Use UK English in app copy and documentation.
- Avoid committing `.env`, SQLite DB files, `node_modules`, `vendor`, secrets, local machine config or unexpected generated files.
- Maintain this file and `HANDOFF.md` as the project evolves.
- End substantial tasks by updating `HANDOFF.md`, running practical checks, showing `git status`, committing and pushing when valid.
