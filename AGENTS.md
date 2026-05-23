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
- Use existing table pagination via `BuildsTableProps` and `Pagination.vue`.
- Use `TableShell` for horizontally scrollable tables.
- Render validation errors beside fields with `InputError`.
- Browser-facing invalid workflow states should redirect with a visible flash message instead of raw 422 exception pages.

## Campaign Operations

- Sending uses the `email` queue.
- Imports use the `imports` queue.
- Scheduled campaigns are queued by `emailora:campaigns:queue-scheduled`.
- Active/stuck campaigns are recovered by `emailora:campaigns:recover`.
- Stale campaigns are finalized by `emailora:campaigns:finalize-stuck`.
- Production must run `php artisan schedule:run` every minute and a monitored queue worker.

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
