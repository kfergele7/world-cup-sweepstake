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

The app currently includes SweepKit branding, private beta positioning, a compact global footer, public Privacy Policy, Terms and feedback pages, admin authentication, a dashboard, sweepstake creation, a tabbed sweepstake admin screen with tab-aware redirects, editable sweepstake settings, public join links with copy buttons, cleaned-up manual entrant management, entrant payment toggles, bulk per-sweepstake team selection, Auto pots, flexible Custom pots, editable prizes, explicit leftover-team draw options for Auto pots, draw result emails, controlled draw re-runs with reasons, active draw cancellation/reopen setup, draw history and private entrant result pages with breadcrumbs.

Admins must add at least one prize before running or re-running a draw. Entrant private result pages show the entrant's own teams first, then the full active draw results by entrant name and team without exposing emails, tokens or admin controls.

The draw service lives at `app/Actions/RunRankedPotDraw.php` and is covered by feature tests in `tests/Feature/RunRankedPotDrawTest.php`. Custom pot management is covered in `tests/Feature/SweepstakePotManagementTest.php`. Custom pots use `teams_per_entrant`; admins can bulk move selected teams into a pot or back to Unassigned; extra teams in a pot and unassigned included teams are ignored for the custom draw, while removed teams are never drawn. Auto-fill by ranking is not implemented yet.

## Deployment Checklist

For the fuller WHM/cPanel runbook, including server layout, `public_html` symlink checks, production `.env` placeholders, Mailgun DNS and route-cache caveats, see [`docs/deployment.md`](docs/deployment.md).

Before using SweepKit outside local development:

- Set `APP_ENV=production`.
- Set `APP_DEBUG=false`.
- Set `APP_URL` to the real HTTPS domain. Email links use this value.
- Configure `MAIL_MAILER`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` and related mail settings for production.
- Mailgun transport packages are installed; keep real Mailgun domain, endpoint and API secret in the production `.env` only.
- Set `SUPPORT_EMAIL` to the support or feedback inbox shown on `/feedback`.
- Use a production database, not the local SQLite file.
- Run `php artisan migrate --force`.
- Build assets with `npm run build`.
- Serve the app over HTTPS.
- Confirm database backups and restore expectations.
- Do not commit `.env`, database files, secrets or credentials.
- Seed and review team/ranking data before launch.
- Confirm `/privacy`, `/terms` and `/feedback` render correctly.
- Test draw emails after deployment and confirm entrant links use the production domain.
- Confirm public join links and private entrant result links work on the production domain.

There is no self-service sweepstake deletion flow yet. For beta, admins can remove entrants before a draw or after cancelling/reopening setup; full sweepstake/data removal should be handled by the site owner until a tested destructive delete flow is added.
