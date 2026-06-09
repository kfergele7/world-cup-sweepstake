# Handoff

## Current State

The repository now contains a Laravel 13, Vue 3, Vite and Tailwind foundation for SweepKit, a private football sweepstake app. The app is scaffolded directly in the repository root, with admin auth, dashboard routes, sweepstake creation, a tabbed sweepstake admin screen, editable sweepstake settings, public joining, cleaned-up manual entrant management, paid/unpaid entrant toggles, bulk per-sweepstake team removal/restoration, Auto pots, flexible Custom pots, editable prizes, draw result/cancellation emails, controlled draw re-runs, active draw cancellation/reopen setup, draw history and private entrant result pages.

Auto and Custom pot draws are implemented and covered by automated feature tests. Draws now include all entrants, with paid/unpaid kept as an admin tracking field. Local SQLite has been migrated and seeded.

The previous pass fixed football-specific national flag display so England, Scotland, Wales and Northern Ireland use compact safe text labels instead of the broken black-flag emoji, while standard country codes still render normal flag emoji.

The previous pass added draw-rule selection between Auto pots and Custom pots. This pass made Custom pots more flexible: each pot now has `teams_per_entrant`, broad pots can have extra teams, unassigned included teams are ignored instead of blocking the draw, removed teams are never drawn and custom draw history stores a per-pot summary of assigned/drawn/unused teams. The sweepstake admin page is now organised into tabs: Overview, Entrants, Teams, Pots, Draw & Results and Settings & Prizes.

## Files And Areas Touched

- Laravel app scaffold and dependency files: `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `vite.config.js`.
- App configuration and ignores: `.env.example`, `.gitignore`, `README.md`, `config/app.php`.
- Models: `User`, `Sweepstake`, `SweepstakeDraw`, `SweepstakeMember`, `Team`, `SweepstakeTeam`, `SweepstakePot`, `SweepstakePotTeam`, `TeamAssignment`, `Prize`.
- Migrations for sweepstakes, draw versions and draw strategy/cancellation metadata, entrants, entrant source, teams, sweepstake teams, custom pots, custom pot team counts, assignments and prizes.
- Seeders: `DatabaseSeeder`, `TeamSeeder`.
- Draw logic: `app/Actions/RunRankedPotDraw.php`, `app/Exceptions/DrawException.php`.
- Mail: `app/Mail/DrawResultsReady.php`, `app/Mail/DrawCancelled.php`, `resources/views/mail/draw-results-ready.blade.php`, `resources/views/mail/draw-cancelled.blade.php`.
- Controllers and routes for auth, dashboard, sweepstake settings management, joining, tokenised entrant result pages, teams, custom pots, entrants, editable prizes, first draw, reasoned draw re-runs and active draw cancellation.
- Brand tokens and component classes: `resources/css/app.css`.
- Basic Blade views plus a small Vue dashboard stats component, text wordmark component, team-name/copy-button components and lightweight JS for admin tabs, bulk counts, copy feedback, Manage/Cancel toggles, smooth scroll and confirmation modals.
- Tests: `tests/Feature/RunRankedPotDrawTest.php`, `tests/Feature/SweepstakeDrawCancellationTest.php`, `tests/Feature/SweepstakeDrawNotificationTest.php`, `tests/Feature/SweepstakeMemberManagementTest.php`, `tests/Feature/SweepstakePotManagementTest.php`, `tests/Feature/SweepstakePrizeManagementTest.php`, `tests/Feature/SweepstakeSettingsTest.php`, `tests/Feature/SweepstakeTeamManagementTest.php`, `tests/Feature/SweepstakeResultsTest.php`.
- Flag helper tests: `tests/Unit/TeamFlagTest.php`.
- Project notes: `CODEX_CONTEXT.md`, `HANDOFF.md`.

## Setup Steps

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
```

For this working tree, Composer dependencies were installed during scaffold creation, npm dependencies were installed, and the local SQLite database was migrated and seeded.

## Checks Run

- `composer create-project laravel/laravel /private/tmp/world-cup-sweepstake-scaffold-519e4026 --no-interaction`
- `npm install`
- `php artisan migrate`
- `php artisan db:seed`
- `php artisan test`
- `php artisan migrate`
- `npm run build`
- `./vendor/bin/pint`
- `composer test`
- `php artisan route:list`
- `git diff --check`
- `pwd`
- `git status --short --branch`
- `git pull`
- `git log --oneline -5`
- `git remote -v`
- `./vendor/bin/pint`
- `composer test`
- `php artisan route:list`
- `npm run build`
- `git diff --check`
- `php artisan test`
- `php artisan migrate:fresh --seed`
- Recreated ignored local dev admin user after fresh migration: `kyle@elementseven.co`.
- Browser smoke test at `http://127.0.0.1:8001`: landing page renders, registration form works, dashboard renders and Vue stats mount; current branding is SweepKit.
- Attempted authenticated browser smoke tests for the admin UI; the in-app Browser loaded the app and the login form, but form submission did not navigate. Authenticated flows are covered by Laravel feature tests.

Current passing test result: 79 tests, 418 assertions.

Custom pot pass checks:

- `php artisan migrate` applied `2026_06_09_000000_add_custom_pot_draw_rules`.
- `php artisan test` passed: 75 tests, 386 assertions.
- `composer test` passed: 75 tests, 386 assertions.
- `npm run build` passed.
- `./vendor/bin/pint` passed.
- `php artisan route:list` passed and shows 32 routes, including the custom pot routes.
- `git diff --check` passed.
- Browser smoke check at `http://127.0.0.1:8001/` loaded the SweepKit landing page in the in-app Browser.

Flexible custom pot/tab pass checks:

- `php artisan migrate` applied `2026_06_09_000001_add_custom_pot_team_counts`.
- `php artisan test` passed: 79 tests, 418 assertions.
- `composer test` passed: 79 tests, 418 assertions.
- `npm run build` passed.
- `./vendor/bin/pint` passed.
- `php artisan route:list` passed and shows 32 routes.
- Browser smoke check at `http://127.0.0.1:8001/` loaded the SweepKit landing page in the in-app Browser.
- Attempted authenticated browser smoke check for the tabbed admin UI; the login form loaded, but browser automation did not submit/navigate. The tabbed admin UI is covered by `SweepstakePotManagementTest`.
- `git diff --check` passed.

## Known Issues Or Blockers

- Browser-level authenticated admin verification is limited in the current Codex thread because the in-app Browser did not submit the login form, though public page smoke testing works and authenticated admin flows are covered by feature tests.
- There is no separate PIN entry route yet, although entrant source values still support `pin`.
- Admin auth is intentionally minimal and does not include password reset/email verification.
- Draw result emails are sent synchronously through Laravel's configured mailer for the MVP.
- Team seed rankings are a working April 2026 dataset and should be refreshed from FIFA before production launch.
- The admin settings, team selection and entrant UI are usable but still basic; dedicated edit pages/modals, search or select-all helpers may be nicer later.
- Custom pot setup is intentionally simple: one select per included team, no drag/drop or bulk assignment tools yet.
- Admin tabs are lightweight Blade/JavaScript hash tabs. Without JavaScript the sections remain available as normal page content.
- The final logo asset has not been chosen yet; the header uses a text wordmark placeholder component.

## Recommended Next Steps

1. Add a dedicated PIN entry flow if PIN joining remains part of the intended MVP.
2. Add richer admin management for team search, select all/none and wider tournament configuration.
3. Consider adding browser-level feature tests for copy feedback, confirmation modals and the Manage/Cancel layout once a browser runner is available.
4. Consider extracting policies or form request classes once route/controller surface grows further.
5. Add the final SweepKit logo asset and favicon once the brand mark is chosen.
6. Refresh team rankings and group metadata from an authoritative source before launch.

## Local browser check

Kyle confirmed the app is running locally at http://127.0.0.1:8001 before the next hardening task.
