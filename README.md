# SweepKit

Laravel and Vue app for running private football sweepstakes with fair ranked-pot draws.

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

The app currently includes SweepKit branding, admin authentication, a dashboard, sweepstake creation, editable sweepstake settings, public join links with copy buttons, cleaned-up manual entrant management, entrant payment toggles, bulk per-sweepstake team selection, editable prizes, ranked pot draw execution, explicit leftover-team draw options, draw result emails, controlled draw re-runs with reasons, active draw cancellation/reopen setup, sidebar draw history and private entrant result pages with breadcrumbs.

The ranked pot draw service lives at `app/Actions/RunRankedPotDraw.php` and is covered by feature tests in `tests/Feature/RunRankedPotDrawTest.php`.
