# SweepKit WHM/cPanel Deployment Guide

This guide covers a small production or private beta deployment of SweepKit on WHM/cPanel. It uses placeholders only. Do not paste real `.env` secrets, database passwords, API keys or local credentials into Codex, ChatGPT, tickets or committed files.

## Assumptions

- Repository: `git@github.com:kfergele7/world-cup-sweepstake.git`
- Branch: `main`
- PHP: `^8.3`
- App root on the server: `/home/sweepkit/laravel`
- Public web root: `/home/sweepkit/public_html`
- Required public web root target: `/home/sweepkit/laravel/public`
- Queue mode for the current beta: `QUEUE_CONNECTION=sync`

The domain must point at Laravel's `public` directory, not the Laravel project root. Never point a public domain directly at `/home/sweepkit/laravel`, because that can expose application source, config files and storage paths.

## Server Layout

Target layout:

```bash
/home/sweepkit/laravel
/home/sweepkit/public_html -> /home/sweepkit/laravel/public
```

Before replacing `public_html`, inspect what is already there:

```bash
cd /home/sweepkit
pwd
ls -la
ls -la public_html
readlink -f public_html
```

If `public_html` already contains a site or uploaded files, back it up before replacing it:

```bash
mv public_html public_html.backup-YYYYMMDD-HHMMSS
ln -s /home/sweepkit/laravel/public public_html
ls -la public_html
readlink -f public_html
```

After the symlink is created, `readlink -f public_html` should resolve to `/home/sweepkit/laravel/public`.

Some cPanel hosts do not allow replacing `public_html` with a symlink. If that is the case, set the domain document root to `/home/sweepkit/laravel/public` in cPanel/WHM instead. Do not copy the whole Laravel app into `public_html`.

## First Deploy

Clone the app outside `public_html`:

```bash
cd /home/sweepkit
git clone git@github.com:kfergele7/world-cup-sweepstake.git laravel
cd /home/sweepkit/laravel
git checkout main
```

Install dependencies and build assets:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan storage:link
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
```

If `npm ci` fails because the host has an older npm or the lock file cannot be used, use this fallback and then investigate the npm version:

```bash
npm install
npm run build
```

Check Node versions before relying on server-side builds:

```bash
node -v
npm -v
```

If the server's Node version is too old for Vite, use cPanel's Node selector if available, ask the host to enable a newer Node runtime or build assets in CI/local release packaging and deploy the built files.

## Repeat Deploy

For normal updates:

```bash
cd /home/sweepkit/laravel
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan storage:link
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
```

Run `php artisan migrate:status` before risky releases if you want to review pending migrations first.

## Production Environment Checklist

Create `/home/sweepkit/laravel/.env` on the server. Do not commit it.

```dotenv
APP_NAME="SweepKit"
APP_ENV=production
APP_KEY=base64:GENERATED_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://sweepkit.example.com
APP_TIMEZONE=Europe/London

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=CPANEL_DATABASE_NAME
DB_USERNAME=CPANEL_DATABASE_USER
DB_PASSWORD=CPANEL_DATABASE_PASSWORD

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
QUEUE_CONNECTION=sync

MAIL_MAILER=mailgun
MAIL_FROM_ADDRESS=hello@mg.sweepkit.example.com
MAIL_FROM_NAME="${APP_NAME}"
MAILGUN_DOMAIN=mg.sweepkit.example.com
MAILGUN_SECRET=MAILGUN_SECRET_PLACEHOLDER
MAILGUN_ENDPOINT=api.eu.mailgun.net

SUPPORT_EMAIL=kyle@elementseven.co
VITE_APP_NAME="${APP_NAME}"
```

Important notes:

- `APP_DEBUG=false` is mandatory in production.
- `APP_URL` must be the real HTTPS domain before public join links, private entrant links or draw emails are used.
- `SESSION_SECURE_COOKIE=true` should be enabled once HTTPS is active.
- If cPanel provides a database host or port other than `localhost:3306`, use the cPanel value.
- If the Mailgun account is not EU-region, use the correct Mailgun endpoint for that account.
- SweepKit includes the Symfony Mailgun transport packages required for Laravel's `MAIL_MAILER=mailgun` configuration. Set the real Mailgun domain, secret and endpoint only in the production `.env` file.

Generate the app key on the server if it has not already been created:

```bash
php artisan key:generate --show
```

Paste the generated value into `APP_KEY`. Do not regenerate `APP_KEY` after real user data exists unless you understand the impact on encrypted data.

## Database Setup

In cPanel:

1. Create a MySQL/MariaDB database.
2. Create a database user.
3. Grant the user privileges on the SweepKit database.
4. Add the final database name, username and password to `.env`.

Then verify and migrate:

```bash
cd /home/sweepkit/laravel
php artisan migrate:status
php artisan migrate --force
```

Take a database backup before risky migrations or before importing production data. Depending on the host, use cPanel Backup, phpMyAdmin export, WHM backups or a command like this with real server values:

```bash
mysqldump -u CPANEL_DATABASE_USER -p CPANEL_DATABASE_NAME > sweepkit-backup-YYYYMMDD.sql
```

Do not run seeders in production unless the seed data has been reviewed for the exact launch. The current team data is a working 2026 list and rankings should be refreshed from an authoritative source before a wider launch.

## Permissions

Laravel must be able to write to `storage` and `bootstrap/cache`:

```bash
cd /home/sweepkit/laravel
chmod -R ug+rw storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod ug+rwx {} \;
find storage bootstrap/cache -type f -exec chmod ug+rw {} \;
```

If permissions still fail, check the cPanel account user/group and the PHP handler. Avoid broad `777` permissions unless the host explicitly requires it and you understand the risk.

## Storage Link

Create or refresh the public storage symlink:

```bash
php artisan storage:link
ls -la public/storage
```

The link should point to `../storage/app/public`.

## Route, Config And View Caches

Use config cache after `.env` is correct:

```bash
php artisan config:cache
```

Do not run `php artisan route:cache` yet. SweepKit currently has a closure route for `/` in `routes/web.php`, so route caching is not safe by default. Only enable route cache after refactoring closure routes to controllers and testing `php artisan route:cache` on the deployed code.

View cache is optional after testing:

```bash
php artisan view:cache
```

When debugging environment changes, clear caches first:

```bash
php artisan optimize:clear
```

## Queue

For the current beta:

```dotenv
QUEUE_CONNECTION=sync
```

No queue worker is needed while emails and jobs run synchronously.

If SweepKit later moves to queued mail or background jobs, switch to a durable queue connection and configure a supervised worker. A typical command is:

```bash
php artisan queue:work --tries=3 --timeout=90
```

On cPanel, run this through the host's process manager, cron strategy or supervisor equivalent rather than an unmanaged SSH session.

## Mailgun Setup

Preferred sending subdomain:

```text
mg.sweepkit.co.uk
```

Use a different subdomain if the final production domain differs. Add Mailgun DNS records in Cloudflare DNS, not in the old registrar panel if the domain's nameservers already point to Cloudflare. Mailgun records should be DNS-only, not proxied.

Typical Mailgun DNS records include:

- SPF TXT record for the sending subdomain.
- DKIM records, commonly CNAME or TXT records provided by Mailgun.
- Tracking CNAME if click/open tracking is enabled.
- Optional MX records for inbound mail if needed.
- DMARC TXT record for the sending domain/subdomain.

Check propagation:

```bash
dig TXT mg.sweepkit.co.uk +short
dig CNAME pdk1._domainkey.mg.sweepkit.co.uk +short
dig CNAME pdk2._domainkey.mg.sweepkit.co.uk +short
dig MX mg.sweepkit.co.uk +short
dig TXT _dmarc.mg.sweepkit.co.uk +short
```

Once Mailgun is verified, set:

```dotenv
MAIL_MAILER=mailgun
MAIL_FROM_ADDRESS=hello@mg.sweepkit.co.uk
MAIL_FROM_NAME="${APP_NAME}"
MAILGUN_DOMAIN=mg.sweepkit.co.uk
MAILGUN_SECRET=MAILGUN_SECRET_PLACEHOLDER
MAILGUN_ENDPOINT=api.eu.mailgun.net
```

Send a test draw email after deployment and confirm every link uses the production `APP_URL`.

## DNS With Cloudflare And 123-reg

If the domain was bought in 123-reg but DNS is managed in Cloudflare, the nameservers at 123-reg should point to Cloudflare. Make DNS changes in Cloudflare after that.

Common records:

```text
@      A      SERVER_IP
www    CNAME  sweepkit.co.uk
```

For an app subdomain instead:

```text
app    A      SERVER_IP
```

For Mailgun, add the records Mailgun provides and keep them DNS-only.

For the website record, Cloudflare proxied mode can work once SSL is correct. During first deployment or diagnosis, start DNS-only so browser and server errors are easier to see. Make sure Cloudflare SSL/TLS mode matches the server certificate setup.

## Post-Deploy Verification

Run:

```bash
php artisan about
php artisan migrate:status
php artisan route:list
```

In the browser, verify:

- `/` loads over HTTPS.
- `/privacy`, `/terms` and `/feedback` load.
- Register/login works for an admin.
- A sweepstake can be created.
- Public join links use the production domain.
- Entrant private result links use the production domain.
- Draw notification emails arrive and use the production domain.
- `storage/logs/laravel.log` has no new production errors.

## Troubleshooting

If the site shows a 500 error:

```bash
tail -n 100 storage/logs/laravel.log
php artisan optimize:clear
php artisan config:cache
```

If assets are missing:

```bash
npm ci
npm run build
ls -la public/build
```

If sessions or cache fail, confirm the database migrations ran because the production checklist uses database-backed sessions and cache:

```bash
php artisan migrate:status
```

If email fails, confirm the selected mailer is installed/configured, Mailgun DNS is verified, the Mailgun endpoint matches the account region and `APP_URL` is correct.

## Rollback Notes

Before a risky deploy, note the current commit:

```bash
git rev-parse --short HEAD
```

If a code rollback is needed:

```bash
git checkout PREVIOUS_GOOD_COMMIT
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan optimize:clear
php artisan config:cache
```

Database rollbacks require extra care and a verified backup. Do not run destructive rollback commands against production unless the migration impact is understood and a backup is available.
