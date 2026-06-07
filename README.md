# World Cup Sweepstake App

Laravel and Vue app for running private 2026 FIFA World Cup sweepstakes.

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

The app currently includes admin authentication, a dashboard, sweepstake creation, editable sweepstake settings, public join links, manual entrant management, entrant payment toggles, bulk per-sweepstake team selection, prize setup, ranked pot draw execution, grouped admin draw results and private entrant result pages.

The ranked pot draw service lives at `app/Actions/RunRankedPotDraw.php` and is covered by feature tests in `tests/Feature/RunRankedPotDrawTest.php`.
