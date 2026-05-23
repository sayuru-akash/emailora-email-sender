# Emailora

Emailora is a Laravel + Inertia + Vue email contact management and campaign operations console.

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
php artisan serve
```

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
BREVO_WEBHOOK_SECRET=
```

Provider keys are read from environment variables only. The UI reports missing provider configuration as a failure; it does not fake successful sends.

For production set `APP_ENV=production`, `APP_DEBUG=false`, a real `APP_URL`, secure session/cookie settings for your domain, and a production database. Keep `.env` out of git; `.env.example` is the only env file that should be committed.

## Queues And Recovery

Run a queue worker in every environment that sends campaigns or processes imports:

```bash
php artisan queue:work --queue=email,imports,default --tries=3 --timeout=120 --sleep=1 --max-time=3600
```

If you tune queue env values, make sure `DB_QUEUE_RETRY_AFTER` is greater than the longest worker timeout. Production should run the worker under Supervisor, systemd, Forge, or another process monitor.

Campaign scheduler and recovery commands:

```bash
php artisan emailora:campaigns:queue-scheduled
php artisan emailora:campaigns:recover
php artisan emailora:campaigns:finalize-stuck
```

The scheduler runs both commands on intervals. Production should run `php artisan schedule:run` every minute.

## Campaign Variables And Sending

Templates and campaigns support both modern `{{ variable }}` tokens and legacy `{variable}` tokens. Common variables include contact fields such as `{{ name }}`, `{{ email }}`, `{{ first_name }}`, `{{ last_name }}`, `{{ company }}`, `{{ phone }}`, location fields, `{{ metadata.key }}`, and `{{ unsubscribe_url }}`.

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

## Production Notes

- Public registration is disabled. Users are managed by owner/admin roles.
- `DatabaseSeeder` creates only the owner and system settings.
- `DemoDatabaseSeeder` is separate and must not run in production.
- Webhook routes are outside auth and reject invalid signatures.
- Imports are stored on the local/private disk, not public web storage. AWS env keys are placeholders until S3-backed storage is wired.
- Use a verified sending domain and sender before real campaigns.
