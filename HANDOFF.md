# Handoff

## Current State

The repository now contains a Laravel 13, Vue 3, Vite and Tailwind foundation for the World Cup Sweepstake App. The app is scaffolded directly in the repository root, with admin auth, dashboard routes, sweepstake creation, public joining, paid/unpaid member toggles, per-sweepstake team removal/restoration, prize setup and ranked pot draw execution.

The ranked pot draw is implemented and covered by automated feature tests. Local SQLite has been migrated and seeded.

## Files And Areas Touched

- Laravel app scaffold and dependency files: `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `vite.config.js`.
- App configuration and ignores: `.env.example`, `.gitignore`, `README.md`.
- Models: `User`, `Sweepstake`, `SweepstakeMember`, `Team`, `SweepstakeTeam`, `TeamAssignment`, `Prize`.
- Migrations for sweepstakes, members, teams, sweepstake teams, assignments and prizes.
- Seeders: `DatabaseSeeder`, `TeamSeeder`.
- Draw logic: `app/Actions/RunRankedPotDraw.php`, `app/Exceptions/DrawException.php`.
- Controllers and routes for auth, dashboard, sweepstake management, joining, teams, members, prizes and draw execution.
- Basic Blade views plus a small Vue dashboard stats component.
- Tests: `tests/Feature/RunRankedPotDrawTest.php`.
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

Current passing test result: 7 tests, 32 assertions.

## Known Issues Or Blockers

- No explicit reset draw flow exists yet, so a drawn sweepstake cannot be redrawn without a future reset feature.
- Member-facing results are not fully built; joined members can submit details, but there is no tokenised results page yet.
- Admin auth is intentionally minimal and does not include password reset/email verification.
- Team seed rankings are a working April 2026 dataset and should be refreshed from FIFA before production launch.
- UI is intentionally basic for this foundation task.

## Recommended Next Steps

1. Build the member-facing results page using `join_token`.
2. Add a safe draw reset flow with audit/history expectations.
3. Add richer admin management pages for editing entry fees, statuses and bulk team selection.
4. Add policies or form request classes once route/controller surface grows.
5. Add browser-level feature tests for the main admin and join flows.
6. Refresh team rankings and group metadata from an authoritative source before launch.
