# Handoff

## Current State

The repository now contains a Laravel 13, Vue 3, Vite and Tailwind foundation for SweepKit, a private football sweepstake app. The app is scaffolded directly in the repository root, with admin auth, dashboard routes, sweepstake creation, a tabbed sweepstake admin screen with tab-aware redirects, editable sweepstake settings, public joining, cleaned-up manual entrant management, paid/unpaid entrant toggles, bulk per-sweepstake team removal/restoration, Auto pots, flexible Custom pots, bulk custom-pot team assignment, editable prizes, draw result/cancellation emails, controlled draw re-runs, active draw cancellation/reopen setup, draw history and private entrant result pages.

Auto and Custom pot draws are implemented and covered by automated feature tests. Draws now include all entrants, with paid/unpaid kept as an admin tracking field. Local SQLite has been migrated and seeded.

The previous pass fixed football-specific national flag display so England, Scotland, Wales and Northern Ireland use compact safe text labels instead of the broken black-flag emoji, while standard country codes still render normal flag emoji.

The previous pass added draw-rule selection between Auto pots and Custom pots, then made Custom pots more flexible: each pot has `teams_per_entrant`, broad pots can have extra teams, unassigned included teams are ignored instead of blocking the draw, removed teams are never drawn and custom draw history stores a per-pot summary of assigned/drawn/unused teams. The next pass improved the tabbed admin UX: tab state now persists via `?tab=...`, hidden form fields and a flashed `active_tab`; tab hover/active styling has accessible contrast; and the Custom pots tab now has a bulk move workflow for assigning selected teams to a pot or back to Unassigned.

This pass reordered admin tabs to put Settings & Prizes before Draw & Results, made at least one prize mandatory before first draws and re-runs, added a no-prize warning/CTA in the draw panel and updated entrant private pages so they show the entrant's own teams first followed by the full active draw results without emails, tokens or admin controls.

This pass added a compact global footer across the shared app layout, with links to the Privacy Policy and Element Seven, plus plain-English public pages at `/privacy` and `/terms`.

This pass updated the footer to show the dynamic current year and a Terms link on the left side while keeping the Element Seven credit unchanged.

This pass prepared SweepKit for a small private beta by adding `/feedback` backed by `SUPPORT_EMAIL`, polishing Privacy/Terms beta language, adding calm private beta notes to the home and dashboard pages, improving draw/cancellation email copy, adding a team-ranking source note in the admin Team selection area and documenting production/deployment requirements.

This pass added a SweepKit-specific WHM/cPanel deployment runbook at `docs/deployment.md`, covering the `/home/sweepkit/laravel` app root, `/home/sweepkit/public_html -> /home/sweepkit/laravel/public` public root pattern, production `.env` placeholders, database setup, permissions, Mailgun DNS, Cloudflare/123-reg notes, route-cache warnings and verification steps.

This pass added Symfony Mailgun transport support for production email readiness: `symfony/mailgun-mailer`, `symfony/http-client` and the transitive `symfony/http-client-contracts` package are installed, `config/services.php` now reads safe `MAILGUN_*` environment values and docs/placeholders note that real Mailgun DNS/domain/API values still belong only in production `.env`.

This pass replaced the plain text navigation wordmark with the supplied SweepKit SVG logo. The asset lives at `public/images/sweepkit-primary.svg`, and `resources/views/components/wordmark.blade.php` renders it with `alt="SweepKit"` inside the existing home link.

This pass removed the small `Fair football sweepstakes` tagline from the shared navigation/header while keeping the SweepKit SVG logo and home link behaviour unchanged.

This pass slightly increased the SweepKit navigation logo display size from `h-8 sm:h-9` to `h-9 sm:h-10`, keeping the SVG aspect ratio, alt text and home link unchanged.

This pass styled the authenticated nav `Sign out` POST button as a subtle destructive action with red text, a thin red border and a light red hover state, without changing logout route, method, CSRF handling or auth behaviour.

This pass improved the sweepstake detail tab navigation on mobile by allowing the tab list to wrap onto multiple rows below the `sm` breakpoint, while keeping the existing single-line overflow behaviour on larger screens.

This pass rebuilt the public homepage into a simple polished SweepKit landing page with hero, how-it-works cards, feature grid, fair draw, results/transparency, private beta/responsible use and final CTA sections. The home-page guest nav now labels the auth links as `Log in` and `Create a sweepstake` while keeping the existing routes.

This pass refined the homepage spacing and visual hierarchy to feel calmer and less card-heavy, with larger section rhythm, lighter hero preview treatment, softer feature cards, a blue-tinted fair draw band, an unboxed responsible-use note and a more generous deep-navy final CTA.

This pass refined the homepage design again while keeping the same early-beta landing-page structure: the hero now includes a compact benefit strip, a small "Perfect for" audience strip clarifies target groups, the feature cards have softer accents, the fair-draw section is a stronger branded navy panel and the closing CTA has a clearer final moment.

This pass tightened the homepage section polish before launch: the audience strip is now a compact deep-navy "Made for private group draws" brand moment, all feature-card accents use the same green treatment, the old internal "Private beta" section was replaced with a more useful private-group checklist and the final CTA now has balanced spacing before the footer.

This pass added a final subtle homepage background polish: the hero CTA reassurance line has more breathing room, the group-draw strip now includes Social clubs and Fundraising groups, and the homepage wrapper has a low-opacity animated radial background with a reduced-motion opt-out.

This pass corrected the homepage background treatment: the inline Blade background styles were moved into `resources/css/app.css`, the homepage now uses a pale layered gradient with a very faint inline SVG hexagon texture, the audience strip has eight groups in a controlled 4x2 desktop grid and 2-column mobile grid, and the hero CTA spacing remains relaxed.

This pass fixed the latest homepage polish issues: the homepage background is now a body-level fixed layer behind the header/main/footer, nav links remain visible and clickable above it, background movement was slowed to 120 seconds, and the audience strip is back to a compact left-copy/right-pills layout with Fundraising groups removed.

This pass corrected the homepage hexagon background implementation: the supplied JPG is now a Vite-managed project asset at `resources/images/homepage-hexagons.jpg`, CSS/SVG-recreated hexagons were removed, green was removed from the background gradient, the JPG texture is static and very low opacity in selected faded placements, and the slow moving overlay uses only blue, grey, white and faint navy tones.

## Files And Areas Touched

- Laravel app scaffold and dependency files: `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `vite.config.js`.
- App configuration and ignores: `.env.example`, `.gitignore`, `README.md`, `config/app.php`.
- Models: `User`, `Sweepstake`, `SweepstakeDraw`, `SweepstakeMember`, `Team`, `SweepstakeTeam`, `SweepstakePot`, `SweepstakePotTeam`, `TeamAssignment`, `Prize`.
- Migrations for sweepstakes, draw versions and draw strategy/cancellation metadata, entrants, entrant source, teams, sweepstake teams, custom pots, custom pot team counts, assignments and prizes.
- Seeders: `DatabaseSeeder`, `TeamSeeder`.
- Draw logic: `app/Actions/RunRankedPotDraw.php`, `app/Exceptions/DrawException.php`.
- Mail: `app/Mail/DrawResultsReady.php`, `app/Mail/DrawCancelled.php`, `resources/views/mail/draw-results-ready.blade.php`, `resources/views/mail/draw-cancelled.blade.php`.
- Controllers and routes for auth, dashboard, public Privacy/Terms/feedback pages, sweepstake settings management, joining, tokenised entrant result pages, teams, custom pots, bulk pot assignment, entrants, editable prizes, first draw, reasoned draw re-runs and active draw cancellation.
- Brand tokens and component classes: `resources/css/app.css`.
- Basic Blade views plus a small Vue dashboard stats component, text wordmark component, team-name/copy-button components, public Privacy Policy/Terms/feedback pages and lightweight JS for admin tabs, bulk counts, custom pot bulk selection, copy feedback, Manage/Cancel toggles, smooth scroll and confirmation modals.
- Tests: `tests/Feature/RunRankedPotDrawTest.php`, `tests/Feature/PublicPolicyPagesTest.php`, `tests/Feature/SweepstakeAdminTabPersistenceTest.php`, `tests/Feature/SweepstakeDrawCancellationTest.php`, `tests/Feature/SweepstakeDrawNotificationTest.php`, `tests/Feature/SweepstakeDrawPrizeRequirementTest.php`, `tests/Feature/SweepstakeMemberManagementTest.php`, `tests/Feature/SweepstakePotManagementTest.php`, `tests/Feature/SweepstakePrizeManagementTest.php`, `tests/Feature/SweepstakeSettingsTest.php`, `tests/Feature/SweepstakeTeamManagementTest.php`, `tests/Feature/SweepstakeResultsTest.php`.
- Flag helper tests: `tests/Unit/TeamFlagTest.php`.
- Project notes: `CODEX_CONTEXT.md`, `HANDOFF.md`.
- Deployment docs: `docs/deployment.md`.
- Brand assets: `public/images/sweepkit-primary.svg`.

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

Current passing test result: 99 tests, 620 assertions.

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

Footer and public policy pages pass checks:

- `php artisan test tests/Feature/PublicPolicyPagesTest.php` passed: 3 tests, 39 assertions.
- `php artisan test` passed: 98 tests, 600 assertions.
- `composer test` passed: 98 tests, 600 assertions.
- `npm run build` passed.
- `./vendor/bin/pint` passed.
- `php artisan route:list` passed and shows 35 routes, including `/privacy` and `/terms`.
- `git diff --check` passed.
- Browser smoke check in the in-app Browser verified `http://127.0.0.1:8001/`, `/privacy` and `/terms` render with the footer, Privacy Policy link and Element Seven link.

Footer year/Terms link pass checks:

- `php artisan test tests/Feature/PublicPolicyPagesTest.php` passed: 3 tests, 43 assertions.
- `php artisan test` passed: 98 tests, 604 assertions.
- `composer test` passed: 98 tests, 604 assertions.
- `npm run build` passed.
- `./vendor/bin/pint` passed.
- `php artisan route:list` passed and shows 35 routes.
- `git diff --check` passed.
- Browser smoke check in the in-app Browser verified the footer renders `© 2026 SweepKit · Privacy Policy · Terms`, with `/privacy`, `/terms` and `https://elementseven.co` links.

Beta readiness pass checks:

- `php artisan test tests/Feature/PublicPolicyPagesTest.php tests/Feature/SweepstakeDrawNotificationTest.php tests/Feature/SweepstakeDrawCancellationTest.php tests/Feature/SweepstakeResultsTest.php tests/Feature/SweepstakePotManagementTest.php` passed: 39 tests, 292 assertions.
- `php artisan test` passed: 99 tests, 620 assertions.
- `composer test` passed: 99 tests, 620 assertions.
- `npm run build` passed.
- `./vendor/bin/pint` passed.
- `php artisan route:list` passed and shows 36 routes, including `/feedback`.
- `git diff --check` passed.
- Browser smoke check in the in-app Browser verified `http://127.0.0.1:8001/`, `/privacy`, `/terms` and `/feedback` render with footer Terms links and feedback/support links.

WHM/cPanel deployment docs pass checks:

- `pwd` confirmed `/Users/kyleferguson/Documents/World Cup Sweepstake`.
- `git status --short --branch` showed `main...origin/main`, the deployment docs changes and the pre-existing untracked `extra/` directory.
- `git pull` reported `Already up to date.`
- `git log --oneline -5` and `git remote -v` confirmed the current branch history and `git@github.com:kfergele7/world-cup-sweepstake.git`.
- Read `CODEX_CONTEXT.md`, `HANDOFF.md`, `README.md`, `routes/web.php`, `composer.json` and `package.json`.
- `php artisan test` passed: 99 tests, 620 assertions.
- `composer test` passed: 99 tests, 620 assertions.
- `npm run build` passed.
- `./vendor/bin/pint` passed.
- `php artisan route:list` passed and shows 36 routes.
- `git diff --check` passed.

Mailgun transport support pass checks:

- `pwd` confirmed `/Users/kyleferguson/Documents/World Cup Sweepstake`.
- `git status --short --branch` showed `main...origin/main` with only the Mailgun dependency/config/docs changes plus the pre-existing untracked `extra/` directory.
- `git pull` reported `Already up to date.`
- `git log --oneline -5` and `git remote -v` confirmed the current branch history and `git@github.com:kfergele7/world-cup-sweepstake.git`.
- Read `CODEX_CONTEXT.md`, `HANDOFF.md`, `README.md`, `docs/deployment.md`, `config/mail.php`, `.env.example` and `config/services.php`.
- `composer require symfony/mailgun-mailer symfony/http-client` installed `symfony/mailgun-mailer` v7.4.0, `symfony/http-client` v7.4.13 and `symfony/http-client-contracts` v3.7.0. Composer selected Symfony 7.4 because Symfony 8.1 requires PHP 8.4.1 and this app targets PHP 8.3.
- `php artisan test` passed: 99 tests, 620 assertions.
- `composer test` passed: 99 tests, 620 assertions.
- `npm run build` passed.
- `./vendor/bin/pint` passed.
- `php artisan route:list` passed and shows 36 routes.
- `git diff --check` passed.

Navigation logo pass checks:

- Verified render path: `routes/web.php` routes return Blade views, views extend `resources/views/layouts/app.blade.php`, the header calls `resources/views/components/wordmark.blade.php`, and `resources/js/app.js` only mounts dashboard/helpers.
- `php artisan test` passed: 99 tests, 620 assertions.
- `npm run build` passed.
- `./vendor/bin/pint` passed.
- `git diff --check` passed.
- Browser smoke check at `http://127.0.0.1:8001/` confirmed the header logo loads from `/images/sweepkit-primary.svg`, keeps `alt="SweepKit"`, links to home and renders around 119px by 36px without distortion.

Nav tagline removal pass checks:

- Verified render path: `routes/web.php` routes return Blade views, views extend `resources/views/layouts/app.blade.php`, and the header home link renders `resources/views/components/wordmark.blade.php` plus the removed nav-only tagline span.
- `php artisan test` passed: 99 tests, 620 assertions.
- `npm run build` passed.
- `git diff --check` passed.

Nav logo size refinement pass checks:

- Confirmed `resources/views/components/wordmark.blade.php` still renders the SweepKit SVG with the same `src`, `alt="SweepKit"` and width-auto aspect ratio.
- `npm run build` passed.
- `git diff --check` passed.

Sign-out nav style pass checks:

- Verified the sign-out action remains the existing `POST` form to `route('logout')` with `@csrf` in `resources/views/layouts/app.blade.php`.
- `npm run build` passed.
- `git diff --check` passed.
- Browser visual check at `http://127.0.0.1:8001/dashboard` confirmed the authenticated desktop nav shows the red bordered Sign out button aligned with the Dashboard link.
- Browser visual check at a mobile-width viewport confirmed the logo, Dashboard link and Sign out button stay aligned without wrapping awkwardly.

Mobile sweepstake tabs pass checks:

- Verified render path: `routes/web.php` maps `/sweepstakes/{sweepstake}` to `SweepstakeController@show`, which renders `resources/views/sweepstakes/show.blade.php`; `resources/js/app.js` preserves active-tab behaviour through `data-tabs` and `data-tab-target`.
- `npm run build` passed.
- `git diff --check` passed.
- Browser visual checks at mobile, tablet and desktop widths confirmed the tab labels remain unchanged, active state remains clear and the mobile tabs wrap instead of clipping off-screen.

Homepage landing page pass checks:

- Verified render path: `routes/web.php` maps `/` to the `welcome` Blade view, `resources/views/welcome.blade.php` extends `resources/views/layouts/app.blade.php`, the shared layout renders the logo/nav/footer, and `resources/js/app.js` only provides general page helpers.
- Added homepage feature coverage in `tests/Feature/PublicPolicyPagesTest.php`.
- `php artisan test` passed: 100 tests, 640 assertions.
- `php artisan route:list` passed and shows 36 routes.
- `npm run build` passed.
- `git diff --check` passed.
- Browser visual checks at desktop and mobile widths confirmed the logged-in and logged-out homepages render the seven sections without horizontal overflow, preserve footer links, and keep CTA links pointing to existing `register`, `login` or `dashboard` routes.

Homepage spacing refinement pass checks:

- Verified render path stayed `routes/web.php` `/` closure to `resources/views/welcome.blade.php`, extending `resources/views/layouts/app.blade.php`, with `resources/js/app.js` only supplying shared helpers.
- `php artisan test tests/Feature/PublicPolicyPagesTest.php` passed.
- `npm run build` passed.
- `git diff --check` passed.
- Browser visual checks at desktop and mobile widths confirmed the refined homepage has more breathing room, no horizontal overflow, preserved CTA/footer links and sensible logged-in/logged-out nav.

Homepage design refinement pass checks:

- Verified render path stayed `routes/web.php` `/` closure to `resources/views/welcome.blade.php`, extending `resources/views/layouts/app.blade.php`, with `resources/js/app.js` only supplying shared helpers.
- Files touched: `resources/views/welcome.blade.php` and `HANDOFF.md`.
- `php artisan test` passed: 100 tests, 640 assertions.
- `php artisan route:list` passed and shows 36 routes.
- `npm run build` passed.
- `git diff --check` passed.
- Browser visual checks at desktop and mobile widths confirmed the benefit strip, "Perfect for" strip, deep-navy fair-draw panel, CTA/footer links, logged-out nav and logged-in nav all render without horizontal overflow at `http://127.0.0.1:8001/`.
- Local test path: open `http://127.0.0.1:8001/`, check the homepage at desktop and mobile widths, then sign in to confirm the CTA switches from `Create a sweepstake` to `Open dashboard`.

Homepage section polish pass checks:

- Verified render path stayed `routes/web.php` `/` closure to `resources/views/welcome.blade.php`, extending `resources/views/layouts/app.blade.php`, with `resources/js/app.js` only supplying shared helpers.
- Files touched: `resources/views/welcome.blade.php`, `tests/Feature/PublicPolicyPagesTest.php` and `HANDOFF.md`.
- `npm run build` passed.
- `git diff --check` passed.
- `php artisan test` passed: 100 tests, 642 assertions.
- `php artisan route:list` passed and shows 36 routes.
- Browser visual checks at desktop and mobile widths confirmed the new group-draw strip, consistent green feature accents, private-group checklist, footer links and final CTA/footer spacing render without horizontal overflow in the logged-out state.
- Logged-in homepage behaviour remains covered by the auth-aware Blade route logic and feature tests; an authenticated browser check was attempted, but the local browser automation wrapper could not type into the login form in this pass.
- Local test path: open `http://127.0.0.1:8001/`, check the homepage at desktop and mobile widths, then sign in to confirm the CTA switches from `Create a sweepstake` to `Open dashboard`.

Homepage background polish pass checks:

- Verified render path stayed `routes/web.php` `/` closure to `resources/views/welcome.blade.php`, extending `resources/views/layouts/app.blade.php`, with `resources/js/app.js` only supplying shared helpers.
- Files touched: `resources/views/welcome.blade.php`, `tests/Feature/PublicPolicyPagesTest.php` and `HANDOFF.md`.
- `npm run build` passed.
- `git diff --check` passed.
- `php artisan test` passed: 100 tests, 645 assertions.
- `php artisan route:list` passed and shows 36 routes.
- Browser visual checks at desktop and mobile widths confirmed the hero CTA/supporting-line spacing, Social clubs and Fundraising groups pills, audience wrapping, footer spacing, no horizontal overflow and the subtle homepage background treatment at `http://127.0.0.1:8001/`.
- Browser CSS inspection confirmed the homepage reduced-motion media rule is present and disables the background animation for `prefers-reduced-motion: reduce`.
- Local test path: open `http://127.0.0.1:8001/`, check the hero CTA spacing, the group-draw strip wrapping, the subtle background treatment and footer spacing at desktop and mobile widths.

Homepage background correction pass checks:

- Verified render path stayed `routes/web.php` `/` closure to `resources/views/welcome.blade.php`, extending `resources/views/layouts/app.blade.php`, with `resources/css/app.css` and `resources/js/app.js` loaded by Vite.
- Files touched: `resources/views/welcome.blade.php`, `resources/css/app.css`, `tests/Feature/PublicPolicyPagesTest.php` and `HANDOFF.md`.
- `npm run build` passed.
- `git diff --check` passed.
- `php artisan test` passed: 100 tests, 645 assertions.
- `php artisan route:list` passed and shows 36 routes.
- Browser visual checks at desktop and mobile widths confirmed the CSS-owned SVG hex texture and radial gradient background, no horizontal overflow, readable cards/text, balanced footer spacing and the audience strip rendering as 4 columns x 2 rows on desktop and 2 columns on mobile.
- Browser CSS inspection confirmed the reduced-motion media rule is present and disables the homepage background animation for `prefers-reduced-motion: reduce`.
- Local test path: open `http://127.0.0.1:8001/`, check the full homepage background from hero to footer, the balanced audience strip and the footer spacing at desktop and mobile widths.

Homepage background and nav layering fix pass checks:

- Verified render path stayed `routes/web.php` `/` closure to `resources/views/welcome.blade.php`, extending `resources/views/layouts/app.blade.php`, with `resources/css/app.css` and `resources/js/app.js` loaded by Vite.
- Files touched: `resources/views/welcome.blade.php`, `resources/css/app.css`, `tests/Feature/PublicPolicyPagesTest.php` and `HANDOFF.md`.
- `npm run build` passed.
- `git diff --check` passed.
- `php artisan test` passed: 100 tests, 644 assertions.
- `php artisan route:list` passed and shows 36 routes.
- Browser visual/CSS checks at desktop and mobile widths confirmed the homepage nav/logo is visible, logged-out nav links are clickable above the background layer, the background layer is fixed and viewport-wide, reduced-motion disables animation, there is no horizontal overflow, footer spacing stays balanced and the audience strip no longer includes Fundraising groups.
- Browser checks confirmed the audience strip renders as a compact 4-column pill group on desktop and a deliberate 2-column grid on mobile.
- Browser checks confirmed dashboard/auth pages do not include the `homepage-gradient-bg` class.
- Attempted authenticated browser login to verify the `Dashboard` / red `Sign out` nav state visually, but the in-app browser wrapper could not type into the login form in this pass. The authenticated nav markup/auth behaviour was not changed and remains covered by the existing tests.
- Local test path: open `http://127.0.0.1:8001/`, check the full-width slow-moving background, visible/clickable nav, audience strip and footer spacing at desktop and mobile widths; then open `http://127.0.0.1:8001/dashboard` or `/login` to confirm the homepage background is not applied.

Homepage hexagon background asset pass checks:

- Verified render path stayed `routes/web.php` `/` closure to `resources/views/welcome.blade.php`, extending `resources/views/layouts/app.blade.php`, with `resources/css/app.css` and `resources/js/app.js` loaded by Vite.
- Files touched: `resources/css/app.css`, `resources/images/homepage-hexagons.jpg` and `HANDOFF.md`.
- `npm run build` passed and fingerprinted the homepage hexagon JPG through Vite.
- `git diff --check` passed.
- `php artisan test` passed: 100 tests, 645 assertions.
- `php artisan route:list` passed and shows 36 routes.
- Browser visual/CSS checks at desktop and mobile widths confirmed the homepage uses the real JPG texture, no recreated SVG hex background remains, green is absent from the background gradient, the hex texture is fixed/static at low opacity, the blue/grey gradient overlay animates slowly at 120s and reduced-motion disables that animation.
- Browser checks confirmed nav/logo links remain visible and clickable, no horizontal overflow is introduced, hero text remains readable, Fundraising groups is absent and dashboard/auth pages do not include the homepage background class.
- Local test path: open `http://127.0.0.1:8001/`, check the faint selected hex texture placements, readable hero, slow blue/grey background movement, compact audience strip and nav clickability at desktop and mobile widths.

## Known Issues Or Blockers

- There is no separate PIN entry route yet, although entrant source values still support `pin`.
- Admin auth is intentionally minimal and does not include password reset/email verification.
- Draw result emails are sent synchronously through Laravel's configured mailer for the MVP.
- Production email links depend on `APP_URL`; set it to the real HTTPS domain before beta mail is sent.
- `SUPPORT_EMAIL` is an environment placeholder and must be set to the real beta support inbox before testers use the feedback page.
- Production Mailgun sending is dependency-ready, but real Mailgun DNS records, domain, API secret and endpoint still need configuring on the production server. No Mailgun secrets are committed.
- Do not run `php artisan route:cache` until the closure route in `routes/web.php` is refactored and route caching is tested.
- Team seed rankings are a working April 2026 dataset and should be refreshed from FIFA before production launch.
- SweepKit does not process payments. Entry fees, prize values and paid/unpaid status are organiser-managed tracking fields only.
- There is no self-service sweepstake delete/data purge flow yet. Entrants can be removed before a draw or after cancellation/reopen; full sweepstake/data removal is currently a site-owner/developer task.
- The admin settings, team selection and entrant UI are usable but still basic; dedicated edit pages/modals, search or select-all helpers may be nicer later.
- Custom pot setup now supports bulk move actions and individual dropdown fine-tuning, but does not yet include auto-fill by ranking or drag/drop.
- Admin tabs are lightweight Blade/JavaScript tabs backed by the `tab` query parameter and flashed tab state. Without JavaScript the sections remain available as normal page content.
- The final logo asset has not been chosen yet; the header uses a text wordmark placeholder component.

## Recommended Next Steps

1. Configure `APP_URL`, production mail and `SUPPORT_EMAIL`, then run a deployed email-link smoke test before inviting beta groups.
2. Add a tested self-service sweepstake deletion/data purge flow, or write an internal runbook for manual beta data removal.
3. Add a dedicated PIN entry flow if PIN joining remains part of the intended MVP.
4. Add auto-fill by ranking for Custom pots if admins want a faster ranked starting point.
5. Add richer admin management for team search, select all/none and wider tournament configuration.
6. Consider adding browser-level feature tests for copy feedback, confirmation modals and the Manage/Cancel layout once a browser runner is available.
7. Consider extracting policies or form request classes once route/controller surface grows further.
8. Add the final SweepKit logo asset and favicon once the brand mark is chosen.
9. Refresh team rankings and group metadata from an authoritative source before launch.

## Local browser check

Kyle confirmed the app is running locally at http://127.0.0.1:8001 before the next hardening task.
