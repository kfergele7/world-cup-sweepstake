# Handoff

## Current State

The repository now contains a Laravel 13, Vue 3, Vite and Tailwind foundation for SweepKit, a private football sweepstake app. The app is scaffolded directly in the repository root, with admin auth, dashboard routes, sweepstake creation, a tabbed sweepstake admin screen with tab-aware redirects, editable sweepstake settings, public joining, cleaned-up manual entrant management, paid/unpaid entrant toggles, bulk per-sweepstake team removal/restoration, Auto pots, flexible Custom pots, bulk custom-pot team assignment, editable prizes, draw result/cancellation emails, controlled draw re-runs, active draw cancellation/reopen setup, draw history and private entrant result pages.

Auto and Custom pot draws are implemented and covered by automated feature tests. Draws now include all entrants, with paid/unpaid kept as an admin tracking field. Local SQLite has been migrated and seeded.

The previous pass fixed football-specific national flag display so England, Scotland, Wales and Northern Ireland use compact safe text labels instead of the broken black-flag emoji, while standard country codes still render normal flag emoji.

The previous pass added draw-rule selection between Auto pots and Custom pots, then made Custom pots more flexible: each pot has `teams_per_entrant`, broad pots can have extra teams, unassigned included teams are ignored instead of blocking the draw, removed teams are never drawn and custom draw history stores a per-pot summary of assigned/drawn/unused teams. The next pass improved the tabbed admin UX: tab state now persists via `?tab=...`, hidden form fields and a flashed `active_tab`; tab hover/active styling has accessible contrast; and the Custom pots tab now has a bulk move workflow for assigning selected teams to a pot or back to Unassigned.

This pass reordered admin tabs to put Settings & Prizes before Draw & Results, made at least one prize mandatory before first draws and re-runs, added a no-prize warning/CTA in the draw panel and updated entrant private pages so they show the entrant's own teams first followed by the full active draw results without emails, tokens or admin controls.

## Files And Areas Touched

- Laravel app scaffold and dependency files: `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `vite.config.js`.
- App configuration and ignores: `.env.example`, `.gitignore`, `README.md`, `config/app.php`.
- Models: `User`, `Sweepstake`, `SweepstakeDraw`, `SweepstakeMember`, `Team`, `SweepstakeTeam`, `SweepstakePot`, `SweepstakePotTeam`, `TeamAssignment`, `Prize`.
- Migrations for sweepstakes, draw versions and draw strategy/cancellation metadata, entrants, entrant source, teams, sweepstake teams, custom pots, custom pot team counts, assignments and prizes.
- Seeders: `DatabaseSeeder`, `TeamSeeder`.
- Draw logic: `app/Actions/RunRankedPotDraw.php`, `app/Exceptions/DrawException.php`.
- Mail: `app/Mail/DrawResultsReady.php`, `app/Mail/DrawCancelled.php`, `resources/views/mail/draw-results-ready.blade.php`, `resources/views/mail/draw-cancelled.blade.php`.
- Controllers and routes for auth, dashboard, sweepstake settings management, joining, tokenised entrant result pages, teams, custom pots, bulk pot assignment, entrants, editable prizes, first draw, reasoned draw re-runs and active draw cancellation.
- Brand tokens and component classes: `resources/css/app.css`.
- Basic Blade views plus a small Vue dashboard stats component, text wordmark component, team-name/copy-button components and lightweight JS for admin tabs, bulk counts, custom pot bulk selection, copy feedback, Manage/Cancel toggles, smooth scroll and confirmation modals.
- Tests: `tests/Feature/RunRankedPotDrawTest.php`, `tests/Feature/SweepstakeAdminTabPersistenceTest.php`, `tests/Feature/SweepstakeDrawCancellationTest.php`, `tests/Feature/SweepstakeDrawNotificationTest.php`, `tests/Feature/SweepstakeDrawPrizeRequirementTest.php`, `tests/Feature/SweepstakeMemberManagementTest.php`, `tests/Feature/SweepstakePotManagementTest.php`, `tests/Feature/SweepstakePrizeManagementTest.php`, `tests/Feature/SweepstakeSettingsTest.php`, `tests/Feature/SweepstakeTeamManagementTest.php`, `tests/Feature/SweepstakeResultsTest.php`.
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

Current passing test result: 95 tests, 561 assertions.

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

Tab persistence and bulk custom-pot assignment pass checks:

- `php artisan test tests/Feature/SweepstakeAdminTabPersistenceTest.php` passed: 7 tests, 66 assertions.
- `php artisan test tests/Feature/SweepstakePotManagementTest.php` passed: 12 tests, 80 assertions.
- `php artisan test` passed: 91 tests, 515 assertions.
- `composer test` passed: 91 tests, 515 assertions.
- `npm run build` passed.
- `./vendor/bin/pint` passed.
- `php artisan route:list` passed and shows 33 routes, including `sweepstakes.pots.bulk-assignments`.
- Browser smoke check at `http://127.0.0.1:8001/` loaded the SweepKit landing page in the in-app Browser.
- `git diff --check` passed.

Prize gate and entrant full-results pass checks:

- `php artisan test tests/Feature/SweepstakeDrawPrizeRequirementTest.php tests/Feature/SweepstakeResultsTest.php tests/Feature/SweepstakePotManagementTest.php` passed: 28 tests, 205 assertions.
- `php artisan test` passed: 95 tests, 561 assertions.
- `composer test` passed: 95 tests, 561 assertions.
- `npm run build` passed.
- `./vendor/bin/pint` passed.
- `php artisan route:list` passed and shows 33 routes.
- `git diff --check` passed.
- In-app Browser connection dropped during the local smoke check, so a direct local HTTP check was used instead: `curl --max-time 5 -s -o /dev/null -w "%{http_code} %{url_effective}\n" http://127.0.0.1:8001/` returned `200 http://127.0.0.1:8001/`.

## Known Issues Or Blockers

- Browser-level authenticated admin verification is limited in the current Codex thread because the in-app Browser did not submit the login form, though public page smoke testing works and authenticated admin flows are covered by feature tests.
- There is no separate PIN entry route yet, although entrant source values still support `pin`.
- Admin auth is intentionally minimal and does not include password reset/email verification.
- Draw result emails are sent synchronously through Laravel's configured mailer for the MVP.
- Team seed rankings are a working April 2026 dataset and should be refreshed from FIFA before production launch.
- The admin settings, team selection and entrant UI are usable but still basic; dedicated edit pages/modals, search or select-all helpers may be nicer later.
- Custom pot setup now supports bulk move actions and individual dropdown fine-tuning, but does not yet include auto-fill by ranking or drag/drop.
- Admin tabs are lightweight Blade/JavaScript tabs backed by the `tab` query parameter and flashed tab state. Without JavaScript the sections remain available as normal page content.
- The final logo asset has not been chosen yet; the header uses a text wordmark placeholder component.

## Recommended Next Steps

1. Add a dedicated PIN entry flow if PIN joining remains part of the intended MVP.
2. Add auto-fill by ranking for Custom pots if admins want a faster ranked starting point.
3. Add richer admin management for team search, select all/none and wider tournament configuration.
4. Consider adding browser-level feature tests for copy feedback, confirmation modals and the Manage/Cancel layout once a browser runner is available.
5. Consider extracting policies or form request classes once route/controller surface grows further.
6. Add the final SweepKit logo asset and favicon once the brand mark is chosen.
7. Refresh team rankings and group metadata from an authoritative source before launch.

## Local browser check

Kyle confirmed the app is running locally at http://127.0.0.1:8001 before the next hardening task.
