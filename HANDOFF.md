# Handoff

## Current State

The repository now contains a Laravel 13, Vue 3, Vite and Tailwind foundation for SweepKit, a private football sweepstake app. The app is scaffolded directly in the repository root, with admin auth, dashboard routes, sweepstake creation, editable sweepstake settings, public joining, cleaned-up manual entrant management, paid/unpaid entrant toggles, bulk per-sweepstake team removal/restoration, prize setup, ranked pot draw execution, draw result emails, controlled draw re-runs, grouped admin draw history and private entrant result pages.

The ranked pot draw is implemented and covered by automated feature tests. Draws now include all entrants, with paid/unpaid kept as an admin tracking field. Local SQLite has been migrated and seeded.

This pass rebranded visible app surfaces to SweepKit, added brand colour tokens and reusable SweepKit UI classes, introduced a text wordmark component, and polished the header, dashboard, admin detail page, entrant cards, team selection, draw results/history, public entrant page, auth pages and empty/status states. The repo and folder names intentionally remain unchanged.

## Files And Areas Touched

- Laravel app scaffold and dependency files: `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `vite.config.js`.
- App configuration and ignores: `.env.example`, `.gitignore`, `README.md`, `config/app.php`.
- Models: `User`, `Sweepstake`, `SweepstakeDraw`, `SweepstakeMember`, `Team`, `SweepstakeTeam`, `TeamAssignment`, `Prize`.
- Migrations for sweepstakes, draw versions, entrants, entrant source, teams, sweepstake teams, assignments and prizes.
- Seeders: `DatabaseSeeder`, `TeamSeeder`.
- Draw logic: `app/Actions/RunRankedPotDraw.php`, `app/Exceptions/DrawException.php`.
- Mail: `app/Mail/DrawResultsReady.php`, `resources/views/mail/draw-results-ready.blade.php`.
- Controllers and routes for auth, dashboard, sweepstake settings management, joining, tokenised entrant result pages, teams, entrants, prizes, first draw and reasoned draw re-runs.
- Brand tokens and component classes: `resources/css/app.css`.
- Basic Blade views plus a small Vue dashboard stats component, text wordmark component and lightweight bulk-team selected-count JS.
- Tests: `tests/Feature/RunRankedPotDrawTest.php`, `tests/Feature/SweepstakeDrawNotificationTest.php`, `tests/Feature/SweepstakeMemberManagementTest.php`, `tests/Feature/SweepstakeSettingsTest.php`, `tests/Feature/SweepstakeTeamManagementTest.php`, `tests/Feature/SweepstakeResultsTest.php`.
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

Current passing test result: 42 tests, 205 assertions.

## Known Issues Or Blockers

- Draw re-runs currently re-randomise the locked/current entrant and team setup only; there is no reset/reopen setup flow yet.
- There is no separate PIN entry route yet, although entrant source values still support `pin`.
- Admin auth is intentionally minimal and does not include password reset/email verification.
- Draw result emails are sent synchronously through Laravel's configured mailer for the MVP.
- Team seed rankings are a working April 2026 dataset and should be refreshed from FIFA before production launch.
- The admin settings, team selection and entrant UI are usable but still basic; dedicated edit pages/modals, search or select-all helpers may be nicer later.
- The final logo asset has not been chosen yet; the header uses a text wordmark placeholder component.

## Recommended Next Steps

1. Add a clear reset/reopen setup flow with audit/history expectations for cases where admins need to change entrants or teams after a draw.
2. Add a dedicated PIN entry flow if PIN joining remains part of the intended MVP.
3. Add richer admin management for team search, select all/none, copy-link affordances and wider tournament configuration.
4. Consider extracting policies or form request classes once route/controller surface grows further.
5. Add browser-level feature tests for the main admin, bulk team, re-run and entrant result flows.
6. Add the final SweepKit logo asset and favicon once the brand mark is chosen.
7. Refresh team rankings and group metadata from an authoritative source before launch.

## Local browser check

Kyle confirmed the app is running locally at http://127.0.0.1:8001 before the next hardening task.
