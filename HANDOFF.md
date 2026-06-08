# Handoff

## Current State

The repository now contains a Laravel 13, Vue 3, Vite and Tailwind foundation for SweepKit, a private football sweepstake app. The app is scaffolded directly in the repository root, with admin auth, dashboard routes, sweepstake creation, editable sweepstake settings, public joining, cleaned-up manual entrant management, paid/unpaid entrant toggles, bulk per-sweepstake team removal/restoration, editable prizes, ranked pot draw execution, draw result/cancellation emails, controlled draw re-runs, active draw cancellation/reopen setup, sidebar draw history and private entrant result pages.

The ranked pot draw is implemented and covered by automated feature tests. Draws now include all entrants, with paid/unpaid kept as an admin tracking field. Local SQLite has been migrated and seeded.

This pass fixed football-specific national flag display so England, Scotland, Wales and Northern Ireland use compact safe text labels instead of the broken black-flag emoji, while standard country codes still render normal flag emoji.

## Files And Areas Touched

- Laravel app scaffold and dependency files: `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `vite.config.js`.
- App configuration and ignores: `.env.example`, `.gitignore`, `README.md`, `config/app.php`.
- Models: `User`, `Sweepstake`, `SweepstakeDraw`, `SweepstakeMember`, `Team`, `SweepstakeTeam`, `TeamAssignment`, `Prize`.
- Migrations for sweepstakes, draw versions and draw strategy/cancellation metadata, entrants, entrant source, teams, sweepstake teams, assignments and prizes.
- Seeders: `DatabaseSeeder`, `TeamSeeder`.
- Draw logic: `app/Actions/RunRankedPotDraw.php`, `app/Exceptions/DrawException.php`.
- Mail: `app/Mail/DrawResultsReady.php`, `app/Mail/DrawCancelled.php`, `resources/views/mail/draw-results-ready.blade.php`, `resources/views/mail/draw-cancelled.blade.php`.
- Controllers and routes for auth, dashboard, sweepstake settings management, joining, tokenised entrant result pages, teams, entrants, editable prizes, first draw, reasoned draw re-runs and active draw cancellation.
- Brand tokens and component classes: `resources/css/app.css`.
- Basic Blade views plus a small Vue dashboard stats component, text wordmark component, team-name/copy-button components and lightweight JS for bulk counts, copy feedback, Manage/Cancel toggles, smooth scroll and confirmation modals.
- Tests: `tests/Feature/RunRankedPotDrawTest.php`, `tests/Feature/SweepstakeDrawCancellationTest.php`, `tests/Feature/SweepstakeDrawNotificationTest.php`, `tests/Feature/SweepstakeMemberManagementTest.php`, `tests/Feature/SweepstakePrizeManagementTest.php`, `tests/Feature/SweepstakeSettingsTest.php`, `tests/Feature/SweepstakeTeamManagementTest.php`, `tests/Feature/SweepstakeResultsTest.php`.
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
- Attempted authenticated browser smoke test for the entrant UI; the in-app Browser loaded the app but text entry was blocked by a missing virtual clipboard in the browser plugin. Authenticated flows are covered by Laravel feature tests.
- Attempted to discover the in-app Browser control tool for this pass, but it was not exposed in this thread. Browser-level verification was limited to automated Laravel feature tests and `npm run build`.

Current passing test result: 65 tests, 318 assertions.

## Known Issues Or Blockers

- Browser-level visual verification was not available in the current Codex thread because the in-app Browser control tool was not exposed.
- There is no separate PIN entry route yet, although entrant source values still support `pin`.
- Admin auth is intentionally minimal and does not include password reset/email verification.
- Draw result emails are sent synchronously through Laravel's configured mailer for the MVP.
- Team seed rankings are a working April 2026 dataset and should be refreshed from FIFA before production launch.
- The admin settings, team selection and entrant UI are usable but still basic; dedicated edit pages/modals, search or select-all helpers may be nicer later.
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
