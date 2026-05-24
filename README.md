# Emailora

Emailora is a Laravel + Inertia + Vue email contact management and campaign operations console.

The public surface includes a lightweight SEO-ready homepage at `/`, plus `/privacy`, `/terms`, `/robots.txt`, and `/sitemap.xml`. The authenticated workspace remains at `/dashboard`.

## Screenshots

Screenshots below are captured from a sanitized demo database, not local contact or campaign data.

![Emailora public homepage](docs/assets/readme/emailora-home-desktop.webp)

| Dashboard | Campaign Builder |
| --- | --- |
| ![Emailora dashboard](docs/assets/readme/emailora-dashboard-desktop.webp) | ![Emailora campaign builder](docs/assets/readme/emailora-campaign-builder.webp) |

| Import Validation Preview | Reports |
| --- | --- |
| ![Emailora import validation preview](docs/assets/readme/emailora-import-preview.webp) | ![Emailora reports dashboard](docs/assets/readme/emailora-reports.webp) |

## Stack

- Laravel 13, PHP 8.3+
- Inertia + Vue 3 + TypeScript
- Tailwind CSS 4
- PHPUnit 12
- Database queues
- Resend/Brevo provider adapters

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
composer dev
```

`composer dev` starts the Laravel server at `http://localhost:8000` / `http://127.0.0.1:8000`, Vite, logs, the scheduler loop, and a queue listener for `email`, `imports`, and `default`. Running only `php artisan serve` will show the UI, but queued campaign/import jobs will stay in the database until a matching worker is started.

Default local owner:

```text
OWNER_EMAIL=owner@example.com
OWNER_PASSWORD=password
```

Change these in `.env` before seeding any non-local environment.

## Required Environment

```dotenv
APP_NAME=Emailora
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Colombo

OWNER_NAME=Owner
OWNER_EMAIL=owner@example.com
OWNER_PASSWORD=password

EMAIL_PROVIDER=resend
EMAIL_FALLBACK_PROVIDER=
EMAIL_FROM_NAME="Emailora"
EMAIL_FROM_ADDRESS=no-reply@example.com
EMAIL_REPLY_TO=support@example.com
EMAIL_RATE_LIMIT_PER_MINUTE=300
EMAIL_CHUNK_SIZE=50
EMAIL_TIMEOUT_SECONDS=30
EMAIL_TRACK_OPENS=true
EMAIL_TRACK_CLICKS=true

RESEND_API_KEY=
RESEND_WEBHOOK_SECRET=
BREVO_API_KEY=
# Legacy local alias accepted for compatibility; prefer BREVO_API_KEY.
BREVO_SMTP_API_KEY=
BREVO_WEBHOOK_SECRET=
```

Provider keys are read from environment variables only. `BREVO_API_KEY` is canonical; `BREVO_SMTP_API_KEY` is accepted as a backwards-compatible alias for older local `.env` files. The UI reports missing provider configuration as a failure; it does not fake successful sends.

For production set `APP_ENV=production`, `APP_DEBUG=false`, a real public `APP_URL`, secure session/cookie settings for your domain, and a production database. Emailora will fail production boot if `APP_URL` points to localhost because canonical URLs, Open Graph images, robots, and sitemap output depend on it. Keep `.env` out of git; `.env.example` is the only env file that should be committed.

## Public SEO Surface

Public pages render server-visible titles, descriptions, absolute canonical URLs, `index,follow` robots metadata, Open Graph/Twitter card metadata, and homepage JSON-LD for the Emailora web application and Codezela Technologies publisher. The social preview image is stored at `/images/og/emailora.png` with the editable SVG source beside it.

`/robots.txt` and `/sitemap.xml` are dynamic, stateless routes. They do not start a session or set cookies, and they use `APP_URL` for absolute sitemap and canonical URLs. Authenticated workspace routes are disallowed in robots and excluded from the sitemap.

## Queues And Recovery

Run a queue worker in every environment that sends campaigns or processes imports:

```bash
php artisan queue:work --queue=email,imports,default --tries=3 --timeout=300 --sleep=1 --max-time=3600
```

Set `DB_QUEUE_RETRY_AFTER=420` or another value greater than the worker timeout so long import jobs are not retried while still running. Production should run the worker under Supervisor, systemd, Forge, or another process monitor, and should monitor `failed_jobs`.

Campaign scheduler and recovery commands:

```bash
php artisan emailora:campaigns:queue-scheduled
php artisan emailora:campaigns:recover
php artisan emailora:campaigns:finalize-stuck
```

The scheduler runs both commands on intervals. Production should run `php artisan schedule:run` every minute.

For local development, prefer `composer dev`; it starts `php artisan serve --host=127.0.0.1 --port=8000`, `php artisan queue:listen --queue=email,imports,default ...`, and `php artisan schedule:work`. If you start processes manually, run all three pieces in separate terminals: the local server on `localhost:8000`, the queue worker above, and `php artisan schedule:work`.

## Contact Imports

Imports support `.csv`, `.txt`, and `.xlsx` files up to 20 MB. The import page provides fillable sample downloads at `/imports/sample/csv` and `/imports/sample/xlsx`. The only required mapped field is email; optional contact fields include name, phone, company, job title, location, source, consent status, and notes. Unmapped columns are preserved in contact metadata.

The import workflow is upload, validation preview, mapping adjustment, then confirm. The preview shows detected headers, valid rows, invalid rows, duplicate rows, warnings, and sample row data before contacts are created or updated. Failed processed rows are stored and can be downloaded from the import detail page.

![Import validation preview](docs/assets/readme/emailora-import-preview.webp)

Duplicate modes:

- `skip`: create only new contacts and leave existing contacts unchanged.
- `update`: update existing contacts only; missing contacts are reported as failed rows.
- `add_to_list_tag`: attach selected lists/tags to existing contacts without overwriting their fields.
- `upsert`: create missing contacts and update existing contacts.

Import files are stored on the private local disk and processed on the `imports` queue. `ProcessImport` is unique per import id while pending/running and marks unreadable files as failed with an audit event.

## Activity Logs

`/activity-logs` is restricted to owner/admin users. It reads the `activity_logs` audit table, not provider delivery events, and includes filters for search, category, event, severity, and actor. Export uses the same filters.

The audit trail records model creates/updates/deletes, auth sign-in events, import lifecycle events, campaign send/resume/cancel/retry actions, contact bulk actions, list membership changes, and webhook accepted/rejected events. Sensitive keys and large provider/import payload fields are recursively redacted before they are stored. Query strings are not stored in audit URLs.

Activity logs include user email, IP address, user agent, and operational metadata, so production deployments should define retention/pruning expectations based on compliance needs.

## Campaign Variables And Sending

Templates and campaigns support both modern `{{ variable }}` tokens and legacy `{variable}` tokens. Common variables include contact fields such as `{{ name }}`, `{{ email }}`, `{{ first_name }}`, `{{ last_name }}`, `{{ company }}`, `{{ phone }}`, location fields, `{{ metadata.key }}`, and `{{ unsubscribe_url }}`.

![Campaign builder with dynamic variables](docs/assets/readme/emailora-campaign-builder.webp)

`{{ name }}` resolves to the best available display name: full name, first/last name, company, then email. Before sending, unresolved variables are blocked so a campaign does not ship raw placeholders.

Campaign sends support two recipient modes:

- `current_audience`: rebuild unsent recipients from the campaign's current targeting.
- `new_contacts`: add only contacts that are newly eligible and have not already been prepared for that campaign.

Every campaign is forced to include an unsubscribe link. If the body does not contain `unsubscribe_url`, the app appends one to HTML/text before saving or sending.

## Verification

```bash
composer ci:check
php artisan test --compact
npm run format:check
npm run lint:check
npm run types:check
npm run build
```

Production cache checks:

```bash
php artisan route:cache
php artisan config:cache
php artisan view:cache
php artisan optimize:clear
```

The feature suite covers public SEO metadata, stateless robots/sitemap responses, authenticated workspace route smokes, imports, contacts, lists/tags, templates, campaigns, reports, settings, users, one-click unsubscribe, webhook acceptance/rejection, webhook event idempotency, and inactive-user workspace boundaries.

Before deploying production assets, ensure `public/hot` is not present, `npm run build` has been run, and `APP_URL` is the final public origin.

## Production Notes

- Public registration is disabled. Users are managed by owner/admin roles.
- `DatabaseSeeder` creates only the owner and system settings.
- `DemoDatabaseSeeder` is separate and must not run in production.
- Webhook routes are outside auth and reject invalid signatures.
- Imports are stored on the local/private disk, not public web storage. AWS env keys are placeholders until S3-backed storage is wired.
- Use a verified sending domain and sender before real campaigns.
