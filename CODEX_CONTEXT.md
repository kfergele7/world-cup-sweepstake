# SweepKit Codex Context

## Product Overview

SweepKit lets an admin create and manage a private 2026 FIFA World Cup sweepstake.

Admins can create a sweepstake, share a private join link or PIN-style code, track entrants, add entrants manually, mark entrants as paid, choose which teams are included, choose Auto pots or Custom pots, edit prize payouts and run a fair draw. Entrants can join from the public link without creating a full user account in the MVP, then use a private tokenised link to view their own assigned teams after the draw. Entrants with email addresses are notified when a draw, reasoned re-run or cancellation is completed.

Public policy pages live at `/privacy` and `/terms`, with a beta feedback page at `/feedback`. The shared app layout includes a compact footer linking to the Privacy Policy, Terms and Element Seven.

## Stack

- Laravel 13
- Vue 3
- Vite
- Tailwind CSS 4
- SQLite locally by default
- PHPUnit for tests

## Brand Direction

- Product name: SweepKit.
- Repo/folder name remains `world-cup-sweepstake` for now.
- Header currently uses a text wordmark component at `resources/views/components/wordmark.blade.php` so it can be replaced with a final logo later.
- Core brand colours are deep navy (`#06152D`), pitch green (`#16B84E`), electric blue (`#1687E8`), soft off-white backgrounds and light grey borders.
- UI should feel like a clean, trustworthy sports SaaS product, not a betting site.

## Core Draw Logic

The default draw mode is `ranked_pots`, with `pot_mode` defaulting to `auto_pots`.

The Auto pots draw flow is:

1. Load selected teams for the sweepstake only.
2. Sort by FIFA ranking or strength, strongest first.
3. Count all entrants in the sweepstake.
4. Calculate `teams_per_member = floor(selected_team_count / member_count)`.
5. Calculate `usable_team_count = teams_per_member * member_count`.
6. If selected teams do not divide evenly by entrants, require the admin to choose a leftover strategy:
   - randomly assign leftover teams, which uses all selected teams but gives some entrants one extra team;
   - remove the lowest-ranked leftover teams for an even draw.
7. Split usable teams into pots where each pot contains one team per entrant.
8. Randomly assign one team from each pot to each entrant.
9. Persist assignments against an active draw version, record the leftover strategy and lock the sweepstake as drawn.

Example: 7 entrants and 48 selected teams means 6 teams per entrant, with 6 leftover teams. The admin can either use all 48 teams with 6 entrants receiving one extra team, or remove the 6 lowest-ranked teams and run an even 42-team draw.

Custom pots use admin-created `SweepstakePot` rows and `SweepstakePotTeam` assignments. Each pot has a `teams_per_entrant` setting. Admins can bulk-select included teams and move them to a pot or back to Unassigned, while keeping individual per-team dropdowns for fine-tuning. A custom draw ignores unassigned included teams, ignores removed teams, shuffles each active pot and draws `entrant_count * teams_per_entrant` teams from that pot. Extra assigned teams in a pot are left unused, not removed. At least one custom pot must have `teams_per_entrant > 0`, and active pots must have enough eligible assigned teams for the configured count.

The current implementation is `App\Actions\RunRankedPotDraw`. Re-runs require a plain-text reason, supersede the previous active draw and preserve previous assignments in draw history. Cancelling the active draw requires a reason, marks that draw as cancelled and reopens setup without deleting previous assignments.

## Core Models

- `User`: authenticated sweepstake admin.
- `Sweepstake`: admin-owned sweepstake with join code, entry fee, status, draw mode, pot mode and draw metadata.
- `SweepstakeDraw`: per-sweepstake draw version with version number, active/superseded/cancelled status, optional re-run/cancellation reasons, pot mode, leftover strategy metadata and run timestamp.
- `SweepstakeMember`: non-account entrant record with name, optional email, source, paid state and optional admin marker.
- `Team`: global master team record.
- `SweepstakeTeam`: per-sweepstake inclusion/removal state for a global team.
- `SweepstakePot`: admin-created custom pot for one sweepstake.
- `SweepstakePotTeam`: custom pot assignment for one included `SweepstakeTeam`.
- `TeamAssignment`: persisted draw result tied to a specific `SweepstakeDraw`.
- `Prize`: per-sweepstake prize payout row.

The master team seed lives in `Database\Seeders\TeamSeeder`. It contains a working 48-team 2026 list with April 2026 rankings. Source references used during setup were FIFA's qualified teams page and World Cup Wiki's 2026 team/ranking summary. Refresh rankings from FIFA before production launch or any user-facing claim of current accuracy.

## Important Rules

- Use all entrants in the sweepstake for the MVP draw, whether paid or unpaid.
- A sweepstake can have up to 48 entrants.
- There must be at least one drawable team available for every entrant; adding entrants is capped by included team count and custom draws validate against assigned active custom pot teams.
- Treat paid/unpaid as an admin tracking field only at this stage.
- Record entrant source as `manual`, `join_link` or `pin`.
- Allow the owning admin to edit sweepstake name, entry fee, currency and draft/open status before the draw.
- Require at least 2 entrants before a draw.
- Require enough selected teams for all entrants.
- Require at least one prize before the first draw or a draw re-run.
- Allow the owning admin to switch between Auto pots and Custom pots while setup is open; lock the choice while an active draw exists, then allow changes again after cancellation/reopen.
- If teams divide evenly by entrants, every entrant receives the same number of teams.
- If teams do not divide evenly by entrants, the admin must explicitly choose whether to randomly assign leftover teams or remove the lowest-ranked leftover teams for an even draw.
- Custom pots require each active pot to have enough eligible assigned teams for its `teams_per_entrant` value.
- Unassigned included teams are ignored in Custom pots mode and are not silently removed.
- Extra assigned teams in a custom pot are left unused.
- At least one custom pot must give entrants teams.
- Removing a team from a sweepstake clears any custom pot assignment for that sweepstake team.
- Bulk custom pot assignment is owner-only, uses included non-removed sweepstake teams only and is locked while an active draw exists.
- Do not allow duplicate team assignments within the same draw version.
- Allow a controlled re-run only with a required reason; keep setup locked and re-randomise the current included entrants/teams.
- Allow the active draw to be cancelled with a required reason; setup reopens, the cancelled draw stays in history and a new draw can be run after changes.
- Preserve previous draw assignments and mark older draw versions as superseded.
- After an active draw, lock sweepstake settings, entrant adds, edits, removals, payment changes, team selection and prize changes until the active draw is cancelled/reopened.
- Send draw result emails to entrants who have an email address, using Laravel's configured mailer.
- Warn when prize payouts exceed the collected paid-entry pot.
- Prize rows can be added, edited, reordered and removed before a draw, with total prize payout shown against collected and expected pots.
- SweepKit does not process payments; entry fees, prize amounts and paid/unpaid status are organiser-managed tracking fields only.
- Email templates should generate absolute entrant links from `APP_URL`; production deployments must set the real HTTPS domain before sending beta emails.
- Team names should render with `Team::displayFlag()` where a stored flag or safe country-code mapping exists; unknown codes render without a flag.
- Admin pages should expose copy buttons for public join/private entrant links and avoid showing long raw URLs as visible text.
- The global app footer should remain compact and subtle, with the Privacy Policy link and Element Seven credit visible across public, auth, entrant and admin pages.
- Keep private beta positioning calm and clear, with `/feedback` available through the configured `SUPPORT_EMAIL` placeholder.
- Team removal and restoration must be scoped to a sweepstake through `sweepstake_teams`, never by mutating the global team row.
- Public entrant result pages must use `join_token`, not incremental entrant IDs. After an active draw they show the entrant's own teams first, then the full active draw results by entrant name and team, without exposing emails, tokens or admin-only controls.
- Entrants can be removed before a draw or after cancelling/reopening setup. There is not yet a self-service sweepstake delete flow; site-owner data removal should be handled manually until a tested destructive flow exists.
- Team ranking data is seeded and must be reviewed before wider launch or user-facing claims of current accuracy.
- WHM/cPanel deployment notes live in `docs/deployment.md`. Production should serve `/home/sweepkit/laravel/public` via `/home/sweepkit/public_html`, never the Laravel root. Do not enable `php artisan route:cache` until the closure route in `routes/web.php` has been refactored and route caching has been tested. Mailgun production sending requires the appropriate Symfony Mailgun transport packages or a different configured Laravel mailer.

## Admin Journey

1. Register or sign in as an admin.
2. Create a sweepstake from the dashboard.
3. Edit basic sweepstake settings such as name, entry fee, currency, draft/open status and draw rule before the draw.
4. Share the join link or join code.
5. Review joined entrants, add offline entrants manually and mark paid entrants.
6. Remove entrants before the draw if needed.
7. Bulk remove or restore teams for that sweepstake.
8. Choose Auto pots or Custom pots while setup is open.
9. If Custom pots is selected, create pots, set each pot's teams per entrant and bulk move selected teams into pots or Unassigned.
10. Add or edit prize payouts.
11. Choose a leftover team strategy when needed for Auto pots and run the draw. Draw actions stay locked until at least one prize exists.
12. Review persisted assignments grouped by entrant and copy private entrant view links if needed.
13. If needed, re-run the draw with a clear reason; the previous draw remains visible as superseded history.
14. If setup was wrong after a draw, cancel the active draw with a clear reason, make changes and run a new draw.

The sweepstake admin page is organised into tabs: Overview, Entrants, Teams, Pots, Settings & Prizes and Draw & Results. Tab state is persisted with a `tab` query parameter, hidden form fields and a one-request flashed `active_tab`, so admins land back on the relevant tab after submissions and validation errors.

## Entrant Journey

1. Open the public join link.
2. Enter name and optional email.
3. Land on a private entrant page backed by their `join_token`.
4. Before the draw, see a waiting message.
5. After the draw, view their own assigned teams first from the same private link.
6. Review the full active draw results by entrant name and team, without emails, tokens or admin controls.
7. If the draw is re-run or cancelled, see their own draw history and the reason without seeing other entrants' private details.

## Codex Workflow Expectations

- Start tasks by checking `pwd`, `git status`, `git remote -v` and recent history.
- Pull from the remote when appropriate.
- Inspect actual render paths before UI edits: route, controller/view, layout, JS/Vue entrypoint, component.
- Keep changes scoped to the requested task.
- Use UK English in app copy and documentation.
- Avoid committing `.env`, SQLite DB files, `node_modules`, `vendor`, secrets, local machine config or unexpected generated files.
- Maintain this file and `HANDOFF.md` as the project evolves.
- End substantial tasks by updating `HANDOFF.md`, running practical checks, showing `git status`, committing and pushing when valid.
