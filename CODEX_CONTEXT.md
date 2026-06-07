# World Cup Sweepstake App Codex Context

## Product Overview

World Cup Sweepstake App lets an admin create and manage a private 2026 FIFA World Cup sweepstake.

Admins can create a sweepstake, share a private join link or PIN-style code, track members, mark members as paid, choose which teams are included, set prize payouts and run a fair draw. Members can join from the public link without creating a full user account in the MVP.

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
3. Count paid members as confirmed members.
4. Calculate `teams_per_member = floor(selected_team_count / member_count)`.
5. Calculate `usable_team_count = teams_per_member * member_count`.
6. Remove leftovers from the bottom of the rankings by default.
7. Split usable teams into pots where each pot contains one team per member.
8. Randomly assign one team from each pot to each paid member.
9. Persist assignments and lock the sweepstake as drawn.

Example: 7 paid members and 48 selected teams means 6 teams per member, 42 teams used, 6 lowest-ranked leftovers removed, then 6 pots of 7 teams.

The current implementation is `App\Actions\RunRankedPotDraw`.

## Core Models

- `User`: authenticated sweepstake admin.
- `Sweepstake`: admin-owned sweepstake with join code, entry fee, status, draw mode and draw metadata.
- `SweepstakeMember`: non-account entrant record with name, optional email, paid state and optional admin marker.
- `Team`: global master team record.
- `SweepstakeTeam`: per-sweepstake inclusion/removal state for a global team.
- `TeamAssignment`: persisted draw result.
- `Prize`: per-sweepstake prize payout row.

The master team seed lives in `Database\Seeders\TeamSeeder`. It contains a working 48-team 2026 list with April 2026 rankings. Source references used during setup were FIFA's qualified teams page and World Cup Wiki's 2026 team/ranking summary. Refresh rankings from FIFA before production launch or any user-facing claim of current accuracy.

## Important Rules

- Use paid members as confirmed draw entrants.
- Require at least 2 paid members before a draw.
- Require enough selected teams for all paid members.
- Every paid member must receive the same number of teams.
- Remove leftovers from the lowest-ranked teams by default.
- Do not allow duplicate team assignments.
- Do not allow a second draw without an explicit reset flow.
- After a draw, lock member payment changes, team selection and prize changes.
- Warn when prize payouts exceed the collected paid-entry pot.
- Team removal must be scoped to a sweepstake through `sweepstake_teams`, never by mutating the global team row.

## Admin Journey

1. Register or sign in as an admin.
2. Create a sweepstake from the dashboard.
3. Share the join link or join code.
4. Review joined members and mark paid entrants.
5. Remove or restore teams for that sweepstake.
6. Add prize payouts.
7. Run the ranked pot draw.
8. Review persisted assignments.

## Member Journey

1. Open the public join link.
2. Enter name and optional email.
3. Wait for the admin to mark payment as received.
4. After the draw, view assigned teams in a later member-facing results flow.

## Codex Workflow Expectations

- Start tasks by checking `pwd`, `git status`, `git remote -v` and recent history.
- Pull from the remote when appropriate.
- Inspect actual render paths before UI edits: route, controller/view, layout, JS/Vue entrypoint, component.
- Keep changes scoped to the requested task.
- Use UK English in app copy and documentation.
- Avoid committing `.env`, SQLite DB files, `node_modules`, `vendor`, secrets, local machine config or unexpected generated files.
- Maintain this file and `HANDOFF.md` as the project evolves.
- End substantial tasks by updating `HANDOFF.md`, running practical checks, showing `git status`, committing and pushing when valid.
