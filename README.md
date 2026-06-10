# SweepKit

Laravel and Vue app for running private football sweepstakes with fair auto-pot or custom-pot draws.

## Stack

- Laravel 13
- Vue 3
- Vite
- Tailwind CSS 4
- SQLite for local development
- PHPUnit for automated tests

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
```

Run the test suite with:

```bash
composer test
```

Build frontend assets with:

```bash
npm run build
```

## Current Foundation

The app currently includes SweepKit branding, a compact global footer, public Privacy Policy and Terms pages, admin authentication, a dashboard, sweepstake creation, a tabbed sweepstake admin screen with tab-aware redirects, editable sweepstake settings, public join links with copy buttons, cleaned-up manual entrant management, entrant payment toggles, bulk per-sweepstake team selection, Auto pots, flexible Custom pots, editable prizes, explicit leftover-team draw options for Auto pots, draw result emails, controlled draw re-runs with reasons, active draw cancellation/reopen setup, draw history and private entrant result pages with breadcrumbs.

Admins must add at least one prize before running or re-running a draw. Entrant private result pages show the entrant's own teams first, then the full active draw results by entrant name and team without exposing emails, tokens or admin controls.

The draw service lives at `app/Actions/RunRankedPotDraw.php` and is covered by feature tests in `tests/Feature/RunRankedPotDrawTest.php`. Custom pot management is covered in `tests/Feature/SweepstakePotManagementTest.php`. Custom pots use `teams_per_entrant`; admins can bulk move selected teams into a pot or back to Unassigned; extra teams in a pot and unassigned included teams are ignored for the custom draw, while removed teams are never drawn. Auto-fill by ranking is not implemented yet.
