# AGENTS.md

## Scope

This repo is the Emailora Laravel/Inertia/Vue email campaign system. Keep work inside `/Users/sayuru/Documents/GitHub/emailora-email-sender` unless the user explicitly points elsewhere.

## Safety

- Never commit `.env` or provider secrets.
- Use `.env.example` for reusable env documentation only.
- Do not run real send jobs unless the user asks for delivery testing and the target audience is confirmed.
- Before changing local campaign/contact data, inspect counts and avoid destructive deletes unless explicitly requested.

## App Conventions

- Backend: Laravel 13 controllers, Form Requests, Eloquent models, database queues.
- Frontend: Inertia Vue pages in `resources/js/pages`, shared Emailora UI in `resources/js/components/emailora`.
- Public marketing/legal pages use `resources/js/layouts/public/PublicLayout.vue` and are intentionally outside the authenticated app shell.
- Public SEO metadata must be absolute and derived from `APP_URL`; production must never run with a localhost `APP_URL`.
- Keep `/robots.txt` and `/sitemap.xml` stateless, cookie-free, publicly cacheable, and limited to public routes.
- Use existing table pagination via `BuildsTableProps` and `Pagination.vue`.
- Use `TableShell` for horizontally scrollable tables.
- Render validation errors beside fields with `InputError`.
- Browser-facing invalid workflow states should redirect with a visible flash message instead of raw 422 exception pages.
- Inertia component names are case-sensitive in production. Match controller names to actual paths exactly, for example `settings/Index` for `resources/js/pages/settings/Index.vue`.
- Contact, list, and tag writes use Form Requests. Normalize contact email before validation and validate list/tag duplicate names by generated slug so DB unique constraints do not become 500s.

## Data Model Notes

- `contacts.email_normalized` is the canonical duplicate key.
- Contact membership is stored in `contact_list` and `contact_tag`; never pass `list_ids` or `tag_ids` into `Contact::create()` / `update()`. Pull them out, then `sync()` relationships.
- Lists use the `lists` table through `App\Models\ListModel`.
- Imports parse CSV/TXT through `fgetcsv()` and XLSX through `App\Services\Imports\ContactImportFile`. Keep sample downloads, validation preview, mapping, duplicate handling, row results, and list/tag assignment covered when changing imports.
- Import files are private local storage. Delete stored files when deleting imports and mark processing failures as failed instead of leaving jobs stuck in `queued` or `processing`.
- Activity logs live in `activity_logs` and `/activity-logs` is owner/admin-only. Use semantic aggregate logs for bulk operations and queue/webhook actions because query updates and pivot syncs do not fire model observers.
- Do not store provider payloads, raw import rows, personalized bodies, tokens, headers, signatures, cookies, or API keys in activity properties. Use the recursive redaction path in `ActivityLogger` and model allowlists in `ActivityLogObserver`.

## Personalization

- Campaign/template variables support both `{{ variable }}` and legacy `{variable}`.
- `{{ name }}` resolves from full name, first/last name, company, then email.
- `metadata.<key>` values are supported when contact metadata contains that key.
- `{{ unsubscribe_url }}` is required for real sends; save/send paths append a footer when missing.
- Sending blocks unresolved variables so raw placeholders do not ship.

## Campaign Operations

- Sending uses the `email` queue.
- Imports use the `imports` queue.
- Scheduled campaigns are queued by `emailora:campaigns:queue-scheduled`.
- Active/stuck campaigns are recovered by `emailora:campaigns:recover`.
- Stale campaigns are finalized by `emailora:campaigns:finalize-stuck`.
- Local `composer dev` must serve `http://localhost:8000` / `http://127.0.0.1:8000`, keep listening to `--queue=email,imports,default`, and run `php artisan schedule:work`; `php artisan serve` alone is not enough for queued campaigns/imports.
- Production must run `php artisan schedule:run` every minute and a monitored queue worker with `--queue=email,imports,default --timeout=300`; `DB_QUEUE_RETRY_AFTER` must be greater than the timeout, for example `420`.
- A queued/preparing campaign with no `campaign_recipients` yet should show its target audience count in the UI. The recover command should prepare recipients, not finalize it as empty.
- `PrepareEmailCampaignRecipients` and `SendEmailCampaignMessages` are unique per campaign while pending/processing to avoid repeated recover runs piling up duplicate work.
- After manual local queue fixes, verify `jobs=0`, `failed_jobs=0`, campaign counts, and recipient statuses before reporting success.

## Providers

- Effective provider env keys are `EMAIL_PROVIDER`, `EMAIL_FROM_ADDRESS`, `EMAIL_FROM_NAME`, `EMAIL_REPLY_TO`, `RESEND_API_KEY`, and `BREVO_API_KEY`; `BREVO_SMTP_API_KEY` is a backwards-compatible local alias only.
- Do not echo provider secrets in chat or logs.
- Brevo rejects empty optional fields such as an empty `headers` object; provider payloads should omit optional keys when empty.
- `EmailPayload::$idempotencyKey` exists but is not yet wired to provider-specific idempotency headers. Treat retry/double-send risk seriously around provider sends.

## Verification

Use focused tests while working, then run full gates before handoff when practical:

```bash
php artisan test --filter=<FocusedTest>
vendor/bin/pint --test
npm run format:check
npm run lint:check
npm run types:check
npm run build
php artisan test
```

For rendered UI changes, validate the actual local route in the browser and check for console errors, blank pages, clipped tables, broken actions, and mobile/table overflow.
