# Handoff

## Current State

The repository now contains a Laravel 13, Vue 3, Vite and Tailwind foundation for the World Cup Sweepstake App. The app is scaffolded directly in the repository root, with admin auth, dashboard routes, sweepstake creation, editable sweepstake settings, public joining, manual entrant management, paid/unpaid entrant toggles, per-sweepstake team removal/restoration, prize setup and ranked pot draw execution.

The ranked pot draw is implemented and covered by automated feature tests. Draws now include all entrants, with paid/unpaid kept as an admin tracking field. Local SQLite has been migrated and seeded.

This pass added a clear admin settings form on the sweepstake detail page so the owning admin can edit name, entry fee, currency and draft/open status before the draw. Settings changes are locked after draw.

## Files And Areas Touched

- Laravel app scaffold and dependency files: `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `vite.config.js`.
- App configuration and ignores: `.env.example`, `.gitignore`, `README.md`.
- Models: `User`, `Sweepstake`, `SweepstakeMember`, `Team`, `SweepstakeTeam`, `TeamAssignment`, `Prize`.
- Migrations for sweepstakes, entrants, entrant source, teams, sweepstake teams, assignments and prizes.
- Seeders: `DatabaseSeeder`, `TeamSeeder`.
- Draw logic: `app/Actions/RunRankedPotDraw.php`, `app/Exceptions/DrawException.php`.
- Controllers and routes for auth, dashboard, sweepstake settings management, joining, teams, entrants, prizes and draw execution.
- Basic Blade views plus a small Vue dashboard stats component.
- Tests: `tests/Feature/RunRankedPotDrawTest.php`, `tests/Feature/SweepstakeMemberManagementTest.php`, `tests/Feature/SweepstakeSettingsTest.php`.
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
- Browser smoke test at `http://127.0.0.1:8001`: landing page renders, registration form works, dashboard renders, Vue stats mount, page title is `World Cup Sweepstake`.
- `php artisan migrate:fresh --seed`
- Recreated ignored local dev admin user after fresh migration: `kyle@elementseven.co`.
- Attempted authenticated browser smoke test for the entrant UI; the in-app Browser loaded the app but text entry was blocked by a missing virtual clipboard in the browser plugin. Authenticated flows are covered by Laravel feature tests.

Current passing test result: 23 tests, 98 assertions.

## Known Issues Or Blockers

- No explicit reset draw flow exists yet, so a drawn sweepstake cannot be redrawn without a future reset feature.
- Entrant-facing results are not fully built; joined entrants can submit details, but there is no tokenised results page yet.
- Admin auth is intentionally minimal and does not include password reset/email verification.
- Team seed rankings are a working April 2026 dataset and should be refreshed from FIFA before production launch.
- The admin settings and entrant UI are usable but still basic; dedicated edit pages/modals may be nicer later.

## Recommended Next Steps

1. Build the entrant-facing results page using `join_token`.
2. Add a safe draw reset flow with audit/history expectations.
3. Add richer admin management pages for bulk team selection and wider tournament configuration.
4. Consider extracting policies or form request classes once route/controller surface grows further.
5. Add browser-level feature tests for the main admin and join flows.
6. Refresh team rankings and group metadata from an authoritative source before launch.

## Local browser check

Kyle confirmed the app is running locally at http://127.0.0.1:8001 before the next hardening task.
