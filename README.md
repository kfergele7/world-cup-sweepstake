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

The app currently includes SweepKit branding, admin authentication, a dashboard, sweepstake creation, editable sweepstake settings, public join links, cleaned-up manual entrant management, entrant payment toggles, bulk per-sweepstake team selection, prize setup, ranked pot draw execution, draw result emails, controlled draw re-runs with reasons, grouped admin draw history and private entrant result pages.

The ranked pot draw service lives at `app/Actions/RunRankedPotDraw.php` and is covered by feature tests in `tests/Feature/RunRankedPotDrawTest.php`.
