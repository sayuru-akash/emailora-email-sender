# Emailora Agent Build Brief

Build Emailora as a production-ready email contact management and campaign platform with a compact, modern, operator-focused admin experience.

This document is for an implementation agent. Treat it as the source of truth for a fresh project.

## Core Intent

Create a production-ready email contact management and campaign platform with this app shape:

- Laravel + Inertia + Vue admin architecture.
- Compact, modern, work-focused dashboard UX.
- Sidebar/topbar/global-search/user-management/activity-log patterns.
- Same entities: contacts, lists, tags, imports, templates, campaigns, campaign recipients, messages, reports, settings, users.
- Email-specific provider layer that can use Resend or Brevo, selected by configuration and optionally per campaign.
- Email-specific deliverability, unsubscribe, bounce, complaint, open, click, and suppression handling.
- No public registration in the product unless the owner explicitly enables it. Default bootstrap is owner/super-admin only.
- Production seed keeps only owner + required system settings. Demo seeder must remain available separately for sample/demo installs.

The final product should feel like a polished email operations SaaS, not a generic scaffold or redesign experiment.

Product name: `Emailora`.

## Non-Negotiables

- Do not ship fake success states. If provider send fails, the UI must show failed.
- Do not show `queued` forever. Every campaign must have deterministic recovery, resume, retry, and finalization.
- Do not return raw JSON to Inertia form submissions unless the frontend uses `fetch`/`axios` intentionally.
- Do not assume shared Inertia props always exist. Guard auth/user/flash props in Vue.
- Do not use provider-specific SQL unless driver-specific fallbacks exist.
- Do not use client-side sorting as if it were true dataset sorting. Data tables must support server-side sort/filter/search/pagination.
- Do not cap email sends just because one API call has a recipient limit. Chunk and charge/track correctly.
- Do not import demo contacts into production data. Keep demo data only in an explicit demo seeder.
- Do not commit real contacts, imported data, provider keys, `.env`, CSV/XLSX imports, queue payloads, or provider logs with secrets.
- Do not expose signup casually. User management happens through owner/admin.
- Every feature must have focused feature tests and the full suite must pass before completion.

## Product Modules

Build these modules:

- `Dashboard` -> email metrics dashboard.
- `Contacts` -> email contacts and email deliverability state.
- `Lists` -> reusable mailing lists.
- `Tags` -> flexible contact labels.
- `Imports` -> CSV/XLSX contact imports with mapping, preview, duplicate handling, list/tag assignment.
- `Templates` -> email templates.
- `Campaigns` -> email campaigns with builder, audience selection, send flow, progress, retry, reports.
- `Reports` -> overall email analytics and campaign-specific reports.
- `Activity Logs` -> audit trail of every important action and provider event.
- `Settings` -> company, timezone, email provider, domains, senders, defaults, import defaults.
- `Users` -> owner/admin/manager/staff/viewer user management.
- `Global Search` -> search across contacts, campaigns, templates, lists, tags, activity.

Keep the same route family shape where possible:

```text
/dashboard
/contacts
/lists
/tags
/imports
/templates
/campaigns
/reports
/settings
/segments
/activity-logs
/users
/profile
```

The implementing agent should not need access to any other project. This document spells out the app behavior, UI structure, backend contracts, edge cases, and acceptance checks directly.

## Exact Product Shape

This is an authenticated operator console. The first screen after login is the dashboard, not a marketing page.

The application shell is persistent:

- Dark left sidebar.
- White topbar.
- Main work surface on light gray background.
- No public marketing chrome inside the app.
- No large hero sections.
- No decorative blobs, gradients, illustrations, or filler panels.
- Text is short and operational.
- Every screen should feel like a tool the operator uses daily.

The product is unique for email, but the user flow must remain familiar:

```text
Login
  -> Dashboard
  -> Import/create contacts
  -> Organize with lists/tags/segments
  -> Create template
  -> Build campaign
  -> Select audience
  -> Preview/test
  -> Send/schedule
  -> Watch progress
  -> Retry failures
  -> Review report/activity
```

No page should require guessing. Primary actions must be visible in the page header. Secondary actions can live in dropdowns.

## Minimal Copy Rules

Use concise wording. Avoid long instructional paragraphs. UI copy examples:

- `Add Contact`
- `Import Contacts`
- `Create Template`
- `New Campaign`
- `Send Test`
- `Schedule`
- `Send Now`
- `Pause`
- `Resume`
- `Cancel`
- `Retry Failed`
- `Export`
- `View Report`
- `Activity Log`
- `No contacts found`
- `Change the filters to see more contacts.`
- `No campaign activity yet`
- `Provider rejected the email.`
- `Unsubscribe recorded.`

Avoid:

- "This feature allows you to..."
- "Here you can manage..."
- Marketing language.
- Repeating obvious instructions under every control.

Use helper text only where it prevents mistakes:

- API keys are stored in environment variables.
- Attachments are not recommended for bulk campaigns.
- Marketing email requires an unsubscribe link.
- Sender must be verified before sending.

## Core Components To Build

The next agent must implement these reusable pieces before page work becomes repetitive:

- `AppLayout`: full app shell with sidebar, mobile sidebar, topbar, confirm dialog, toast host.
- `GuestLayout`: auth pages.
- `Sidebar`: collapsed/expanded navigation, role-aware Users item.
- `Topbar`: breadcrumbs, global search, refresh, profile menu.
- `PageHeader`: title, subtitle, actions slot.
- `Button`: variants `default`, `outline`, `ghost`, `destructive`, `secondary`, loading state, icon support.
- `Input`, `Textarea`, `Select`, `Checkbox`, `Switch`, `Label`.
- `Card`, `CardHeader`, `CardTitle`, `CardDescription`, `CardContent`, `CardFooter`.
- `Badge`, `StatusBadge`, `TagPill`, `ListBadge`.
- `BadgeOverflowPopover`: polished `+N` hover/click popover.
- `DataTable`: server-driven table controls; do not hide server sorting behind local sorting.
- `Pagination`: works with named routes and fallback current URL.
- `SearchInput`: debounced input with clear state.
- `FilterDrawer`: mobile-friendly filters when needed.
- `EmptyState`: icon, short title, short description.
- `LoadingSkeleton`: page/table skeletons.
- `ConfirmDialog`: destructive and important confirmation actions.
- `Sheet`: details panels such as activity log detail.
- `Dialog`: modal forms or previews.
- `Tabs`: settings/template/report sections.
- `StatCard`: dashboard and report metrics.
- `EmailPreview`: inbox preview + desktop/mobile rendered email preview.
- `CharacterCounter` equivalent for subject/preheader if useful.
- `CampaignProgress`: progress bar + counts.
- `ManualContactSelector`: searchable manual audience picker.
- `MultiSelectCombobox`: lists/tags/segments selection.

Component rules:

- Components must accept missing/empty data gracefully.
- Components must be keyboard usable.
- Buttons with icons need accessible labels where text is absent.
- Popovers/sheets/dialogs must close on Escape/outside click.
- Mobile layout must not overflow.
- Tables should scroll horizontally only when unavoidable.

## Tech Stack

Use this stack unless the repo already dictates newer compatible versions:

- PHP 8.3+ or PHP 8.5 if available.
- Laravel 13.
- Inertia Laravel v2.
- Vue 3 with `<script setup lang="ts">`.
- Inertia Vue v2.
- Tailwind CSS v4.
- Vite.
- Ziggy for named route generation.
- Laravel Breeze for authenticated app baseline.
- Laravel Sanctum if API/auth token surfaces are needed.
- PHPUnit 12, not Pest.
- Laravel Pint.
- Spatie Activitylog for audit logging.
- League CSV and OpenSpout for CSV/XLSX imports.
- ECharts or Vue ECharts for reports.
- lucide-vue-next for icons.
- Reka UI primitives where this app already uses custom UI components.
- `vue-sonner` for toasts.
- `zod`/`vee-validate` only if they are already used consistently; backend validation remains the source of truth.

Follow Laravel conventions:

- Controllers remain thin.
- Use Form Requests for validation.
- Use Policies for authorization.
- Use Jobs for queue-heavy send/import work.
- Use provider interfaces for Resend/Brevo.
- Use factories for tests.
- Use feature tests for user-facing flows.

## Visual Design System

Match the design language below closely.

Theme:

```css
--font-sans: "Inter", ui-sans-serif, system-ui, -apple-system, sans-serif;
--color-primary: #4f46e5;
--color-primary-hover: #4338ca;
--color-primary-light: #eef2ff;
--color-background: #fafafa;
--color-card: #ffffff;
--color-border: #e5e7eb;
--color-muted: #6b7280;
--color-muted-foreground: #9ca3af;
--color-foreground: #1a1a1a;
--color-success: #10b981;
--color-danger: #ef4444;
--color-warning: #f59e0b;
--color-info: #3b82f6;
--color-sidebar: #1a1a1a;
--color-sidebar-hover: #2a2a2a;
--color-sidebar-active: #4f46e5;
```

Layout:

- Left sidebar, 260px expanded, 68px collapsed.
- Topbar 64px high.
- Main content max width: `max-w-7xl`, `px-4 py-6 lg:px-8`.
- White cards on light gray app background.
- Thin borders, subtle shadows, no decorative gradients/orbs.
- Dense, operational, scannable pages. This is a SaaS admin app, not a marketing site.
- Cards only for repeated items, forms, modals, and framed tools. Do not put cards inside cards.
- Keep radius mostly `8px` or less for controls; larger radii only where they serve a clear panel/card role.
- Use real icons, not text-only command pills, whenever a standard action icon exists.
- Use lucide icons for sidebar and buttons.
- No oversized hero sections.
- No in-app explanatory marketing copy.

Logo/icon:

- Create a sibling identity, not a clone.
- Use the same 40x40 rounded square motif.
- Keep indigo primary.
- Use email symbolism: envelope + outgoing arrow, or envelope flap + paper plane.
- Favicon must be generated from the same icon.
- Provide `public/favicon.ico`, `public/favicon.svg`, and Apple touch icon.

Suggested logo prompt for generated bitmap/vector reference:

```text
Minimal SaaS admin product icon, rounded square indigo background, clean white envelope with a subtle outgoing arrow, modern geometric mark, no text, high contrast, works at favicon size, distinct and professional for an email campaign product.
```

## Navigation

Sidebar main items:

- Dashboard: `LayoutDashboard`
- Contacts: `Users`
- Lists: `List`
- Tags: `Tag`
- Imports: `Upload`
- Email Templates: `FileText`
- Campaigns: `Send`
- Reports: `BarChart3`
- Activity Logs: `Activity`

Bottom items:

- Users: `UserCog`, visible only for owner/admin.
- Settings: `Settings`.
- Collapse control: `ChevronLeft`/`ChevronRight`.

Topbar:

- Breadcrumbs.
- Global search with debounce, abort controller, keyboard selection.
- Small refresh button near profile.
- Profile dropdown with profile and logout.
- Mobile menu toggle.

Important UX behavior:

- Sidebar must stay visible after client-side navigation. No first-click blank layout.
- Every top-level page must work through Inertia navigation and hard refresh.
- Refresh button uses `router.reload()` and shows a spinner.
- Global search closes cleanly on result navigation and aborts stale requests.

## Roles And Permissions

Use the same role model:

- Owner: full access, can create admin/owner-level users, can manage settings/providers/domains.
- Admin: full operational access except owner-level user changes.
- Manager: contacts, lists, tags, imports, templates, campaigns, reports.
- Staff: contacts/imports/campaign creation depending on policy, no settings/users.
- Viewer: read-only dashboards/reports/contact views.

Policy expectations:

- Enforce all important capabilities server-side.
- UI role checks are only convenience, never security.
- Owners can manage all users.
- Admins cannot create/update/delete owners or admins unless explicitly allowed.
- Users cannot delete themselves.
- Suspended/inactive users cannot access the app.
- Public registration route should be disabled or hidden by default.

## Authentication

Use Breeze/Inertia login flow with:

- Login.
- Forgot password.
- Reset password.
- Email verification only if enabled.
- Profile edit.
- Password update.
- Account deletion only if product owner wants it; otherwise hide destructive self-delete.

Default seed:

- Create exactly one owner account from environment variables:
  - `OWNER_NAME`
  - `OWNER_EMAIL`
  - `OWNER_PASSWORD`
- If env vars are absent in local, use safe documented defaults only for local/dev.
- Production seeder must not add demo users, contacts, campaigns, or templates.

Demo seeder:

- Separate `DemoDatabaseSeeder`.
- Includes fake users, fake contacts, fake lists/tags, sample email templates, sample campaigns.
- Never called by `DatabaseSeeder` in production.

## Contact Model For Email

Use email as the primary deliverability identifier. Keep phone fields only if useful for profile completeness; the email product must not require a phone.

Recommended fields:

```text
id
uuid
first_name nullable
last_name nullable
full_name nullable
email required, normalized lowercase
email_normalized required unique or unique per tenant
phone nullable
company nullable
job_title nullable
country nullable
district nullable
city nullable
gender nullable
date_of_birth nullable
source nullable
status enum: active, inactive, unsubscribed, bounced, complained, blocked, invalid
email_verified_status enum: unknown, valid, invalid, risky, disposable, role_based
consent_status enum: unknown, opted_in, opted_out
consent_source nullable
consent_at nullable timestamp
last_contacted_at nullable timestamp
last_opened_at nullable timestamp
last_clicked_at nullable timestamp
unsubscribed_at nullable timestamp
bounced_at nullable timestamp
complained_at nullable timestamp
blocked_at nullable timestamp
notes nullable text
metadata json nullable
created_by nullable
updated_by nullable
timestamps
soft_deletes
```

Indexes:

- `email_normalized` unique.
- `status`.
- `source`.
- `company`.
- `country`, `district`, `city`.
- `created_at`.
- `last_contacted_at`.
- `last_opened_at`.
- `last_clicked_at`.
- composite indexes for common filters.

Email normalization:

- Trim whitespace.
- Lowercase domain and local part for uniqueness.
- Validate using Laravel email validation.
- Do not silently rewrite plus-addressing.
- Store original email if needed only for display.

Can receive email when:

- Status is active.
- Email exists and passes validation.
- Not unsubscribed.
- Not complained.
- Not blocked.
- Not hard bounced.
- Not globally suppressed.

## Lists And Tags

Build list and tag behavior as first-class contact organization:

Lists:

- name
- slug
- colour/color
- description
- status: active, inactive, archived
- created_by
- timestamps
- many-to-many contacts

Tags:

- name
- slug
- colour/color
- description
- created_by
- timestamps
- many-to-many contacts

UI:

- Index pages with search, status filters, sort, pagination.
- Show pages list assigned contacts.
- Add/remove contacts from list.
- Export contacts.
- Badge overflow popovers must be smooth, readable, and not crushed.
- `+N` badges must have visible contrast.

## Imports

Support CSV and XLSX.

Flow:

1. Upload file.
2. Preview columns and sample rows.
3. Map columns to contact fields.
4. Choose duplicate handling.
5. Assign global tags/lists during import.
6. Confirm import.
7. Process in queue.
8. Show live progress, failed row details, and downloadable failed rows.

Supported mapped fields:

- email
- first_name
- last_name
- full_name
- phone
- company
- job_title
- country
- district
- city
- gender
- date_of_birth
- source
- notes
- consent_status
- consent_source
- consent_at
- custom metadata fields if implemented.

Duplicate handling:

- skip
- update existing
- add to list/tag only
- create if missing and update if existing

Important import rules:

- Validate all emails.
- Normalize all emails.
- Reject rows with missing/invalid email unless explicitly imported as invalid contacts.
- Keep failed rows with row number, raw data, and readable error.
- Use database transactions around state transitions.
- Use idempotency keys/indexes so double confirm does not duplicate rows.
- JSON show endpoint must return the same UI-safe shape as Inertia show.
- Never load entire import into memory; stream/chunk.
- Provide clear import activity logs.

## Email Templates

Build Email Templates.

Template fields:

```text
id
name
category nullable
subject
preheader nullable
html_body longtext
text_body longtext nullable
variables json nullable
status active/inactive
created_by nullable
timestamps
soft_deletes
```

Template categories:

- marketing
- transactional
- reminder
- announcement
- admissions
- payment
- newsletter
- onboarding

Editor:

- Keep the first version pragmatic and stable.
- Provide subject input, preheader input, HTML editor/textarea, plain text editor/textarea.
- Preview rendered email with contact variables.
- Show variable chips.
- Support inserting variables:
  - `{first_name}`
  - `{last_name}`
  - `{full_name}`
  - `{email}`
  - `{company}`
  - `{city}`
  - `{country}`
  - custom metadata variables if supported.
- Validate unresolved variables before send.
- Generate plain text fallback from HTML if text body is empty, but allow manual override.
- Add duplication.
- Add active/inactive state.

Future editor option:

- Add a block editor only if it stays reliable and testable.
- Do not ship a fragile drag-and-drop editor unless it preserves HTML output and plain text fallback.

## Campaigns

Build Email Campaigns.

Campaign fields:

```text
id
uuid
name
subject
preheader nullable
from_name
from_email
reply_to_email nullable
html_body longtext
text_body longtext nullable
template_id nullable
provider enum nullable: resend, brevo, auto
provider_account_id nullable
target_type enum: all_contacts, list, tag, saved_segment, manual_selection, advanced_filter
target_filters json nullable
status enum: draft, scheduled, queued, preparing, sending, paused, completed, failed, cancelled
scheduled_at nullable
started_at nullable
completed_at nullable
total_recipients default 0
queued_count default 0
sent_count default 0
delivered_count default 0
opened_count default 0
clicked_count default 0
failed_count default 0
bounced_count default 0
complained_count default 0
skipped_count default 0
pending_count default 0
created_by nullable
approved_by nullable
timestamps
soft_deletes
```

Recipient fields:

```text
id
campaign_id
contact_id
email_normalized
personalized_subject nullable
personalized_html nullable
personalized_text nullable
status enum: pending, queued, sent, delivered, opened, clicked, failed, bounced, complained, skipped
skip_reason nullable
queued_at nullable
sent_at nullable
delivered_at nullable
opened_at nullable
clicked_at nullable
failed_at nullable
bounced_at nullable
complained_at nullable
provider nullable
provider_message_id nullable
provider_response json nullable
error_message nullable
attempt_count default 0
last_attempt_at nullable
timestamps
unique campaign_id + email_normalized
```

Message fields:

```text
id
campaign_id nullable
campaign_recipient_id nullable
contact_id nullable
email_normalized
from_email
from_name nullable
reply_to_email nullable
subject
html_body longtext nullable
text_body longtext nullable
provider nullable
provider_message_id nullable
status enum: pending, sent, delivered, opened, clicked, failed, bounced, complained, suppressed
provider_response json nullable
error_message nullable
sent_at nullable
delivered_at nullable
opened_at nullable
clicked_at nullable
failed_at nullable
bounced_at nullable
complained_at nullable
timestamps
indexes: campaign_id, contact_id, email_normalized, status, provider_message_id, sent_at
```

Campaign statuses:

- draft: editable.
- scheduled: waiting for scheduled send.
- queued: accepted for preparation.
- preparing: resolving recipients/personalizing.
- sending: dispatching provider calls.
- paused: stopped safely; can resume.
- completed: no pending/queued/sending recipients remain.
- failed: campaign-level failure or all recipients failed before meaningful send.
- cancelled: user intentionally stopped.

Important campaign behavior:

- Creating campaign can save draft or send now.
- Send now must validate audience count > 0.
- Manual selection must support search and selected retention.
- Sending uses queue jobs and chunks.
- Use recipient-level idempotency.
- Use provider message ID mapping.
- Progress page must refresh/poll or reload smoothly.
- Closing the tab must not stop processing.
- Returning later must show actual progress from DB.
- Retrying failed recipients should create clear logs and update counts.
- Retrying one recipient should be supported.
- Retrying all failed recipients should be supported.
- Duplicating campaign should create a draft copy.
- Pause/resume/cancel must be safe under concurrent jobs.

## Audience Selection

Support the same target types:

- all active emailable contacts
- selected list(s)
- selected tag(s)
- saved segment
- manual selection
- advanced filter

Audience selection UX:

- Segmented control for target type.
- Multi-select combobox for lists/tags.
- Searchable manual contact picker with selected chips.
- Live estimate endpoint.
- Estimate must use the same backend query as send preparation.
- Empty audience must block send.
- Invalid/suppressed/unsubscribed contacts must be excluded or shown as skipped.
- Show skipped count and reasons where relevant.

Saved segments:

- Store JSON filters.
- Support status, source, country, district, city, gender, created date range, consent status, email verification state, tags, lists, last opened/clicked ranges.
- Segment preview endpoint.
- Segment show page with matching contacts.

## Email Composer UX

Campaign builder should include:

- Campaign name.
- Template selector.
- From name.
- From email.
- Reply-to.
- Subject.
- Preheader.
- HTML body.
- Plain text body.
- Variable insert.
- Preview using selected sample contact.
- Send test email.
- Audience selection.
- Schedule/send now.
- Draft save.

Validation:

- From email must be verified for selected provider.
- Reply-to must be valid.
- Subject required and length-limited.
- HTML or text body required.
- Detect unresolved variables.
- Detect empty links.
- Detect missing unsubscribe link for marketing campaigns.
- Block unsafe scripts in HTML.
- Sanitize HTML.
- Generate plain text fallback.

Preview:

- Desktop and mobile width toggles.
- HTML preview and text preview tabs.
- Show subject/preheader preview similar to inbox.
- Show personalized variable values.
- Show warnings for missing variables.

## Unsubscribe, Suppression, Bounces

Email-specific compliance is required.

Tables:

```text
email_suppressions
id
email_normalized unique
reason enum: unsubscribed, bounced, complained, manual, provider_suppressed
provider nullable
source nullable
campaign_id nullable
message_id nullable
metadata json nullable
created_at
updated_at
```

Unsubscribe:

- Add signed unsubscribe URL per recipient/message.
- One-click unsubscribe endpoint.
- Confirmation page with minimal UI.
- Global unsubscribe updates contact status and suppression.
- Campaign-specific unsubscribe can be added later, but global is MVP.
- Include `List-Unsubscribe` and `List-Unsubscribe-Post` headers where provider supports custom headers.

Bounces/complaints:

- Hard bounce -> mark contact bounced and suppress.
- Complaint/spam -> mark complained and suppress immediately.
- Soft bounce/delivery delayed -> record event; only suppress after configurable threshold.
- Provider suppressed event -> update suppression.

Suppression checks:

- Always check local suppression before queueing recipient.
- Also record provider suppression webhook events.
- Skipped recipients must show skip reason.

## Provider Architecture

Use an email provider interface. Do not scatter Resend/Brevo HTTP calls through jobs/controllers.

```php
interface EmailProviderInterface
{
    public function send(EmailPayload $payload): EmailResult;

    public function sendBatch(Collection $payloads): Collection;

    public function verifyWebhook(Request $request): bool;

    public function parseWebhook(Request $request): EmailWebhookEvent;
}
```

Suggested classes:

```text
app/Services/Email/EmailProviderInterface.php
app/Services/Email/EmailPayload.php
app/Services/Email/EmailResult.php
app/Services/Email/EmailWebhookEvent.php
app/Services/Email/EmailService.php
app/Services/Email/ResendProvider.php
app/Services/Email/BrevoProvider.php
app/Services/Email/EmailPersonalizer.php
app/Services/Email/HtmlToText.php
app/Services/Email/EmailSanitizer.php
app/Services/Email/UnsubscribeLinkBuilder.php
```

Config:

```php
return [
    'provider' => env('EMAIL_PROVIDER', 'resend'),
    'fallback_provider' => env('EMAIL_FALLBACK_PROVIDER'),
    'from_email' => env('EMAIL_FROM_ADDRESS'),
    'from_name' => env('EMAIL_FROM_NAME', env('APP_NAME')),
    'reply_to' => env('EMAIL_REPLY_TO'),
    'rate_limit_per_minute' => (int) env('EMAIL_RATE_LIMIT_PER_MINUTE', 300),
    'chunk_size' => (int) env('EMAIL_CHUNK_SIZE', 50),
    'timeout' => (int) env('EMAIL_TIMEOUT_SECONDS', 30),
    'tracking' => [
        'opens' => env('EMAIL_TRACK_OPENS', true),
        'clicks' => env('EMAIL_TRACK_CLICKS', true),
    ],
    'resend' => [
        'api_key' => env('RESEND_API_KEY'),
        'webhook_secret' => env('RESEND_WEBHOOK_SECRET'),
    ],
    'brevo' => [
        'api_key' => env('BREVO_API_KEY'),
        'webhook_secret' => env('BREVO_WEBHOOK_SECRET'),
    ],
];
```

Provider selection:

- `EMAIL_PROVIDER=resend` sends through Resend.
- `EMAIL_PROVIDER=brevo` sends through Brevo.
- `EMAIL_PROVIDER=auto` can choose provider by campaign/provider setting or failover policy.
- Campaign-level provider can be `resend`, `brevo`, or `auto`.
- Fallback provider must only be used when safe. Do not double-send if provider accepted the message but later webhook is delayed.
- Use idempotency keys where provider supports it; otherwise enforce local idempotency.

## Resend Integration Notes

Official docs checked on 2026-05-13:

- Send endpoint: <https://resend.com/docs/api-reference/emails>
- Webhook events: <https://resend.com/docs/dashboard/webhooks/event-types>
- Webhook management: <https://resend.com/docs/dashboard/webhooks/introduction>
- Domains: <https://resend.com/docs/dashboard/domains/introduction>
- Inbound receiving: <https://resend.com/docs/dashboard/receiving/introduction>

Implementation notes:

- Use API sending, not SMTP, for observability and structured results.
- `from`, `to`, `subject`, `html`, `text`, `reply_to`/`replyTo`, headers, tags, and attachments must map cleanly.
- Resend send API supports a max recipient count per request; do not use that as campaign limit. Chunk recipients.
- Attachments are possible, but campaign attachments should be avoided by default for deliverability. Prefer hosted links.
- Resend has webhook events including sent, delivered, delivery delayed, failed, bounced, opened, clicked, complained, suppressed, received, scheduled.
- Verify webhook signatures.
- Store Resend email ID as `provider_message_id`.
- Tags/metadata should include campaign ID, recipient ID, contact ID, environment.
- Domain setup should encourage subdomains and SPF/DKIM/DMARC.

Resend payload shape example:

```php
[
    'from' => "{$fromName} <{$fromEmail}>",
    'to' => [$recipientEmail],
    'reply_to' => $replyToEmail,
    'subject' => $subject,
    'html' => $html,
    'text' => $text,
    'headers' => [
        'List-Unsubscribe' => "<{$unsubscribeUrl}>",
        'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
    ],
    'tags' => [
        ['name' => 'campaign_id', 'value' => (string) $campaignId],
        ['name' => 'recipient_id', 'value' => (string) $recipientId],
    ],
]
```

## Brevo Integration Notes

Official docs checked on 2026-05-13:

- Send transactional email: <https://developers.brevo.com/docs/send-a-transactional-email>
- Batch transactional email: <https://developers.brevo.com/docs/batch-send-transactional-emails>
- API limits: <https://developers.brevo.com/docs/api-limits>
- Transactional email options: <https://help.brevo.com/hc/en-us/articles/7924148470546-How-can-I-send-transactional-emails-with-Brevo>
- Transactional attachments: <https://help.brevo.com/hc/en-us/articles/4402811730962-Add-an-attachment-to-a-transactional-email>

Implementation notes:

- Prefer API sending for campaigns.
- Brevo supports static and dynamic content via API and templates.
- Brevo transactional webhooks can report sent, delivered, opened, clicked, soft bounce, hard bounce, invalid email, deferred, complaint, unsubscribed, blocked, error.
- Brevo supports batch transactional send up to 1000 personalized versions in one API request. Still chunk internally and respect rate limits.
- Brevo exposes rate limits and returns `429` when exceeded; implement retry/backoff.
- Brevo attachments have strict size/recipient constraints; avoid attachments in bulk campaigns unless intentionally supported.
- Store Brevo message ID as `provider_message_id`.
- Use tags to map provider events back to campaign/recipient/message.

Brevo payload shape example:

```php
[
    'sender' => [
        'name' => $fromName,
        'email' => $fromEmail,
    ],
    'to' => [
        ['email' => $recipientEmail, 'name' => $recipientName],
    ],
    'replyTo' => ['email' => $replyToEmail],
    'subject' => $subject,
    'htmlContent' => $html,
    'textContent' => $text,
    'headers' => [
        'List-Unsubscribe' => "<{$unsubscribeUrl}>",
        'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
    ],
    'tags' => [
        "campaign:{$campaignId}",
        "recipient:{$recipientId}",
    ],
]
```

## Webhooks

Routes:

```text
POST /webhooks/email/resend
POST /webhooks/email/brevo
```

Do not put these behind web auth middleware. Use signature verification and IP/rate protections.

Webhook handling:

- Verify signature/secret first.
- Store raw event in `email_events`.
- Make processing idempotent by provider + event ID or provider message ID + event type + event timestamp.
- Resolve campaign recipient by provider message ID or tags.
- Update message status.
- Update campaign recipient status.
- Update contact engagement/suppression.
- Refresh campaign counts.
- Log activity for important events.
- Return 2xx only after safe persistence or idempotent duplicate handling.

Event table:

```text
email_events
id
provider
provider_event_id nullable
provider_message_id nullable
event_type
campaign_id nullable
campaign_recipient_id nullable
email_message_id nullable
contact_id nullable
email_normalized nullable
payload json
occurred_at nullable
processed_at nullable
created_at
unique nullable composite for idempotency
```

Status mapping:

```text
provider sent -> sent
provider delivered -> delivered
provider opened -> opened
provider clicked -> clicked
provider bounced/hard_bounce -> bounced
provider complained/spam -> complained
provider failed/error -> failed
provider suppressed/blocked/unsubscribed -> suppressed or skipped depending timing
provider deferred/delivery_delayed/soft_bounce -> delivery_delayed event, do not immediately hard fail
```

## Jobs

Use queue jobs for all heavy import and send work:

```text
PrepareEmailCampaignRecipients
SendEmailCampaignMessages
SendSingleEmail
FinalizeEmailCampaign
ProcessContactImport
ProcessEmailWebhookEvent
RefreshCampaignCounts
```

Job rules:

- Use `ShouldQueue`.
- Set explicit `$tries`, `$timeout`, and backoff.
- Jobs should be idempotent.
- Use `chunkById`/cursor for large datasets.
- Never sleep in tests.
- Use rate limiting per provider.
- Update recipient attempts.
- Store provider response/error.
- Dispatch finalization when no pending/queued/sending recipients remain.
- Respect pause/cancel before each batch and before each provider call.
- Use locks where concurrent retries/resumes can race.

Send flow:

1. User clicks send.
2. Validate campaign and audience.
3. Mark campaign `queued`.
4. Dispatch prepare job.
5. Prepare job resolves audience and inserts recipients idempotently.
6. Mark campaign counts.
7. Dispatch send job.
8. Send job marks campaign `sending`, queues single-send jobs or provider batches.
9. Single-send job sends provider request and stores message/recipient result.
10. Webhooks upgrade sent to delivered/opened/clicked/bounced/complained.
11. Finalizer marks complete when all recipients are terminal.

Terminal recipient statuses:

- sent if provider does not provide later delivery.
- delivered.
- clicked.
- opened.
- failed.
- bounced.
- complained.
- skipped.

Campaign finalization:

- Completed when all recipients terminal and at least one sent/delivered/opened/clicked unless all skipped.
- Failed when zero successful sends and failures are campaign-level/provider-wide.
- Cancelled remains cancelled.
- Paused remains paused.

## Page-By-Page UX Contract

### Dashboard

Purpose: fast operational overview.

Header:

- Title: `Dashboard`
- Subtitle: short current account/app summary if useful.
- Optional action: `New Campaign`

Top stat cards:

- Total Contacts.
- Active Contacts.
- Sent This Month.
- Failed/Bounced.
- Active Campaigns.
- Scheduled Campaigns.

Main sections:

- Email volume chart.
- Campaign performance chart.
- Recent campaigns table/card list capped at 5.
- Recent imports capped at 5.
- Top lists.
- Top tags.
- Contacts by status.
- Recent activity capped at 8 with `View all`.

Edge states:

- Empty dashboard should still look complete.
- Charts show empty state, not broken axes.
- Recent activity must not overflow; cap and link.

### Contacts Index

Header:

- Title: `Contacts`
- Subtitle: `{total} contacts`
- Actions: `Import`, `Add Contact`, `Export`

Filters:

- Search.
- Status.
- Source.
- List.
- Tag.
- Consent status.
- Verification status.
- Per page.
- Clear filters.

Table columns:

- Checkbox.
- Name.
- Email.
- Status.
- Lists.
- Tags.
- Source.
- Last Contacted.
- Last Opened.
- Last Clicked.
- Added.
- Last Updated.
- Actions.

Bulk actions:

- Add to list.
- Add tags.
- Remove tags.
- Mark inactive.
- Block.
- Unsubscribe.
- Export selected.
- Delete if policy allows.

Actions:

- View.
- Edit.
- Block.
- Unsubscribe.
- Delete.

Edge cases:

- Long names/emails must truncate cleanly.
- Missing name falls back to email.
- Tags/lists overflow popover.
- Source options must come from real current data, not generic hardcoded-only values.
- Default order is most recent first.
- Sorting is server-side.

### Contact Create/Edit

Sections:

- Identity: first name, last name, full name, email.
- Profile: phone, company, job title, location.
- Consent: status, source, consent date.
- Organization: lists, tags, source.
- Notes.

Validation:

- Email required and unique normalized.
- Invalid email shows field error.
- Consent date cannot be future unless explicitly allowed.
- Status changes to unsubscribed/bounced/complained should set timestamp.

### Contact Show

Must show:

- Name/email/status summary.
- Lists/tags.
- Consent state.
- Deliverability state.
- Last contacted/opened/clicked.
- Notes.
- Recent messages.
- Recent campaign participation.
- Activity log related to contact.
- Actions: Edit, Send test/single email if supported, Block, Unsubscribe.

### Lists Index/Show

Index:

- Search.
- Status filter.
- Sort.
- Contacts count.
- Actions.

Show:

- List metadata.
- Contacts table.
- Add contacts modal.
- Remove selected contacts.
- Export.
- Start campaign with this list.

### Tags Index/Show

Same as lists but tag-focused.

Show:

- Contacts table.
- Start campaign with this tag.
- Export.

### Imports Index

Header:

- Title: `Imports`
- Action: `Import Contacts`

Table:

- File name.
- Type.
- Status.
- Progress.
- Rows.
- Successful.
- Failed.
- Duplicate.
- Uploaded by.
- Added.
- Updated.
- Actions.

Actions:

- View.
- Continue mapping if uploaded.
- Download failed rows.
- Delete where allowed.

### Imports Upload

UX:

- File dropzone.
- CSV/XLSX only.
- Duplicate handling.
- Default list/tag assignment.
- Submit button.

Validation:

- Reject wrong type.
- Reject oversize file.
- Show readable errors.

### Imports Mapping

UX:

- Column mapping table.
- Required email mapping.
- Preview rows.
- Duplicate handling.
- Lists/tags.
- Confirm import.

Edge cases:

- Empty file.
- Headerless file if supported.
- Missing email column.
- Duplicate columns.
- Large file preview must not load entire file.

### Imports Show

Must show:

- File metadata.
- Progress bar.
- Status.
- Counts.
- Selected duplicate mode.
- Lists/tags applied.
- Failed rows table.
- Download failed rows.
- Activity.

Progress:

- Refreshable/pollable.
- Safe after tab close.
- JSON endpoint must match Inertia shape.

### Templates Index

Filters:

- Search.
- Category.
- Status.

Columns:

- Name.
- Category.
- Subject.
- Variables.
- Status.
- Added.
- Updated.
- Actions.

Actions:

- View.
- Edit.
- Duplicate.
- Delete.

### Template Create/Edit

Fields:

- Name.
- Category.
- Subject.
- Preheader.
- HTML body.
- Text body.
- Variables.
- Status.

Actions:

- Save.
- Preview.
- Send test.
- Cancel.

Warnings:

- Missing text fallback.
- Unresolved variables.
- Missing unsubscribe for marketing category.
- Unverified sender if test needs sender.

### Template Show

Must show:

- Metadata.
- Subject/preheader preview.
- HTML preview.
- Text preview.
- Variables.
- Recent campaigns using it.
- Actions: Edit, Duplicate, New Campaign from Template.

### Campaigns Index

Header:

- Title: `Campaigns`
- Actions: `New Campaign`

Summary cards:

- Campaigns.
- Recipients.
- Sent.
- Failed.
- Pending/Queued.

Filters:

- Search.
- Status.
- Provider.
- Date range.
- Per page.

Columns:

- Name.
- Status.
- Provider.
- Recipients.
- Sent.
- Delivered.
- Opened.
- Clicked.
- Failed/Bounced.
- Created by.
- Added.
- Updated.
- Actions.

Important:

- List status must match detail status.
- A campaign that is still sending must show that after page refresh.
- Draft campaigns must not appear as sent.

### Campaign Builder/Create/Edit

Use either a single builder page or create/edit pages, but the flow must be smooth.

Sections:

1. Setup:
   - Campaign name.
   - Provider.
   - From name/email.
   - Reply-to.
2. Content:
   - Template selector.
   - Subject.
   - Preheader.
   - HTML body.
   - Text body.
   - Variables.
   - Preview.
3. Audience:
   - All contacts/list/tag/segment/manual/advanced.
   - Live estimate.
   - Manual contact selector.
4. Review:
   - Sender verified.
   - Audience count.
   - Suppressed/skipped estimate.
   - Unsubscribe present.
   - Test email.
   - Schedule or send now.

Save states:

- Save draft.
- Update draft.
- Send now.
- Schedule.

Edge cases:

- Template deletion after selection.
- Selected contacts later unsubscribed.
- No recipients.
- Provider not configured.
- From email unverified.
- Unresolved variables.
- User closes tab during send.

### Campaign Show

Must show:

- Header with name/status/actions.
- Progress cards.
- Progress bar.
- Counts.
- Provider.
- Audience summary.
- Content preview.
- Recent activity.
- Recipient status table preview.
- Links to full recipients and report.

Actions by status:

- Draft: Edit, Send, Duplicate, Delete.
- Scheduled: Cancel, Edit schedule, Duplicate.
- Queued/preparing/sending: Pause, Cancel, Refresh.
- Paused: Resume, Cancel.
- Completed/failed: Retry Failed, Duplicate, View Report.
- Cancelled: Duplicate.

### Campaign Recipients

Filters:

- Search.
- Status.
- Provider event.

Columns:

- Contact.
- Email.
- Status.
- Provider.
- Attempts.
- Error.
- Sent.
- Delivered.
- Opened.
- Clicked.
- Actions.

Actions:

- View contact.
- Retry one failed/bounced if allowed.
- View provider response/details.

### Campaign Report

Must show:

- Summary metrics.
- Timeline chart.
- Status breakdown.
- Failed/bounced/complained recipients.
- Click/open stats.
- Export.
- Retry failed.
- Activity link.

### Reports Index

Must show:

- Global metrics.
- Email over time.
- Contact growth.
- Contacts by source.
- Contacts by status.
- Provider comparison.
- Top lists/tags.
- Best/worst campaigns.

### Activity Logs

Must show:

- Search/filter controls.
- Logs list.
- Actor.
- Event badge.
- Subject type/name.
- Relative and absolute time.
- View details button that opens a useful details sheet.
- Open record button when subject URL exists.

Details sheet:

- Description.
- Event.
- Actor.
- Subject.
- Timestamp.
- Properties.
- Changes old/new if available.

### Settings

Must have tabs:

- General.
- Email.
- Providers.
- Domains & Senders.
- Imports.
- Users & Access.

All settings actions must use the right response type:

- Inertia form submission -> redirect back with flash/errors.
- AJAX/provider health check -> JSON.

### Users

Must show:

- Search.
- Role/status filters.
- Per-page.
- Name/email.
- Role.
- Status.
- Last login.
- Added.
- Updated.
- Actions.

Create/edit:

- Name.
- Email.
- Role.
- Status.
- Password.
- Confirm password.

Guard all auth props in Vue.

### Profile

Must include:

- Update name/email.
- Update password.
- Optional delete account only if product owner wants it.


## Reports

Dashboard stats:

- Total contacts.
- Active emailable contacts.
- Emails sent this month.
- Delivered this month.
- Failed/bounced this month.
- Open rate this month.
- Click rate this month.
- Active campaigns.
- Scheduled campaigns.

Dashboard sections:

- Recent campaigns, capped with View All.
- Recent imports, capped with View All.
- Contact growth chart.
- Campaign performance chart.
- Contacts by status.
- Top lists.
- Top tags.
- Recent activity, capped with View All.

Reports index:

- Overall stats.
- Email over time for last 12 months.
- Contacts by source.
- Contacts by status.
- Campaign success/delivery/open/click rates.
- Top lists/tags.
- Provider comparison if both providers used.

Campaign report:

- Campaign metadata.
- Send progress.
- Recipient status counts.
- Timeline by hour/day.
- Delivery rate.
- Open rate.
- Click rate.
- Bounce rate.
- Complaint rate.
- Failed/bounced/complained recipient table.
- Retry one.
- Retry all failed.
- Export CSV.
- Activity log link/context.

SQL portability:

- Do not use `TO_CHAR()` without SQLite fallback.
- Use driver-specific date expressions behind helper methods.
- Tests must cover report index under SQLite.

## Activity Logs

Log everything important:

- User login/logout if desired.
- User created/updated/deleted.
- Contact created/updated/deleted/bulk action.
- Import uploaded/confirmed/started/completed/failed.
- Template created/updated/duplicated/deleted.
- Campaign created/updated/duplicated/sent/paused/resumed/cancelled/completed/failed.
- Recipient retry queued.
- Provider send accepted/failed.
- Webhook bounce/complaint/unsubscribe events.
- Settings changed.
- Test email sent/failed.

Activity log UI:

- Search.
- Event filter.
- Log name filter.
- Actor filter.
- Subject type/id filters.
- Per-page filter.
- View details sheet/modal.
- Subject action link.
- Clear filter chips.
- Safe fallback event when event is null.
- Clean timestamps in app timezone.

Timestamps:

- Store UTC.
- Display in configured timezone, default `Asia/Colombo`.
- Show relative and absolute timestamps where useful.

## Settings

Settings tabs:

- General.
- Email.
- Providers.
- Domains & Senders.
- Imports.
- Users & Access.

General:

- Company name.
- Timezone.
- Date format.
- Default country.

Email:

- Default from name.
- Default from email.
- Default reply-to.
- Default provider.
- Fallback provider.
- Default tracking opens/clicks.
- Default unsubscribe footer.
- Rate limit per minute.
- Chunk size.
- Test email form.

Providers:

- Resend configured state.
- Brevo configured state.
- Never display API keys.
- Show masked key status only.
- Provider health/test actions.

Domains & Senders:

- Domain list.
- Verification status.
- SPF/DKIM/DMARC checklist.
- Sender list.
- From email verification status.
- Use subdomain guidance for sending reputation.

Imports:

- Max file size.
- Duplicate handling default.
- Default source.

Users & Access:

- Role descriptions.
- No verbose tutorial text.

Important Settings implementation:

- Inertia forms must get redirects with flash, not raw JSON.
- JSON endpoints should be used only by explicit AJAX/fetch flows.
- Test email must show success/failure truthfully.

## Global Search

Search across:

- Contacts.
- Campaigns.
- Templates.
- Lists.
- Tags.
- Imports.
- Activity.

Behavior:

- Debounce around 220ms.
- Abort stale fetch.
- Minimum query length 2.
- Keyboard navigation.
- Grouped results.
- Result badges.
- Error state if unavailable.
- Search route returns JSON.

## Frontend Robustness Rules

Every Inertia page must:

- Use optional fallbacks for props that may be absent during partial reload or error transitions.
- Never dereference `page.props.auth.user` without guards.
- Use computed safe props:

```ts
const safeUsers = computed(() => props.users ?? { data: [], meta: emptyMeta });
const currentUserRole = computed(() => page.props.auth?.user?.role ?? 'staff');
```

- Keep Vue components single-root.
- Use named routes through Ziggy.
- Use `Link` for navigation.
- Use `router.get/post/put/delete` for Inertia actions.
- Use `fetch`/`axios` only for intentional JSON endpoints.
- Preserve scroll/state only where appropriate.
- Clear stale selections after bulk actions.
- Keep all text within containers on mobile and desktop.
- Use stable widths for tables, badges, toolbars, and icon buttons.

## Data Tables

All large indexes must support:

- Server-side search.
- Server-side filters.
- Server-side sort.
- Server-side pagination.
- URL state.
- Empty states.
- Loading states if async.
- Bulk selection where applicable.
- Export where applicable.

Tables:

- Contacts.
- Lists show contacts.
- Tags show contacts.
- Imports.
- Campaigns.
- Campaign recipients.
- Reports failed recipients.
- Activity logs.
- Users.

Do not implement only client-side sorting over the current page and call it done.

## Contact Page Columns

Contacts index should include:

- Name.
- Email.
- Status.
- Lists.
- Tags.
- Source.
- Last contacted.
- Last opened.
- Last clicked.
- Added.
- Last updated.
- Actions.

Default sort:

- Most recent first by `created_at desc` unless user changes sort.

Badge overflow:

- Show first visible badges.
- `+N` button visible with clear contrast.
- Smooth popover on hover/click.
- Open/close on click.
- Close on outside click/escape.
- Do not crush columns.

## User Management

Users page:

- Search by name/email.
- Filter by role/status.
- Per-page selector.
- Default most recent first.
- Add user.
- Edit user.
- Show user.
- Delete where policy allows.
- Clear disabled/rejected actions or show clear error.

Fields:

- name.
- email.
- role.
- status.
- last_login_at.
- email_verified_at.
- created_at.
- updated_at.

User pages must not blank when navigating from sidebar.

## Error Pages

Implement Inertia error rendering for:

- 403 Forbidden.
- 404 Not Found.
- 419 session expired.
- 500 generic server error.

Authenticated layout should remain stable for expected app errors where possible.

## Security

- Validate every request with Form Requests.
- Authorize every controller action.
- CSRF on all state-changing routes.
- Webhooks use signature verification.
- Never log API keys.
- Never expose provider secrets in Inertia props.
- Rate limit login, password reset, test email, webhook endpoints, and provider test endpoints.
- Sanitize email HTML.
- Prevent stored XSS in template previews.
- Escape user content.
- Validate uploaded file MIME, extension, and size.
- Store imports outside public web root.
- Use signed URLs for unsubscribe and tracking where relevant.
- Use policies rather than frontend-only role checks.

## Deliverability

Required:

- SPF/DKIM/DMARC setup guidance in Settings.
- Verified sending domains.
- Verified senders.
- Subdomain recommendation for campaigns.
- Plain text alternative.
- Unsubscribe link/header for bulk/marketing mail.
- Suppression list.
- Bounce/complaint handling.
- Avoid bulk attachments by default.
- Avoid misleading sender identities.
- Track opens/clicks only if enabled and disclosed.

Recommended:

- Warm-up guidance, not automation unless supported.
- Campaign preflight checklist.
- Spam-risk checks:
  - missing text body.
  - subject too long.
  - no unsubscribe link.
  - too many images vs text.
  - empty links.
  - unverified sender.
  - high suppressed/skipped percentage.

## Compliance

The app should support compliance workflows without pretending to provide legal advice:

- Store consent status/source/date.
- Store unsubscribe date.
- Store complaint date.
- Export contact data.
- Delete/anonymize contact if required by operator.
- Global suppression honored before every send.
- Clear audit trail.

## Environment Variables

Minimum:

```dotenv
APP_NAME=Emailora
APP_ENV=local
APP_URL=http://127.0.0.1:8000
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

Use `.env.example` with placeholder values only.

## File Structure

Backend:

```text
app/Http/Controllers/
  DashboardController.php
  ContactController.php
  ListController.php
  TagController.php
  ImportController.php
  EmailTemplateController.php
  CampaignController.php
  ReportController.php
  SettingController.php
  SavedSegmentController.php
  ActivityLogController.php
  UserController.php
  GlobalSearchController.php
  Webhooks/ResendWebhookController.php
  Webhooks/BrevoWebhookController.php

app/Http/Requests/
  ContactRequest.php
  ContactImportRequest.php
  ImportMappingRequest.php
  EmailTemplateRequest.php
  CampaignRequest.php
  CampaignSendRequest.php
  SettingRequest.php
  TestEmailRequest.php
  UserRequest.php

app/Jobs/
  ProcessImport.php
  PrepareEmailCampaignRecipients.php
  SendEmailCampaignMessages.php
  SendSingleEmail.php
  FinalizeEmailCampaign.php
  ProcessEmailWebhookEvent.php

app/Models/
  Contact.php
  ListModel.php
  Tag.php
  Import.php
  ImportRow.php
  EmailTemplate.php
  EmailCampaign.php
  CampaignRecipient.php
  EmailMessage.php
  EmailEvent.php
  EmailSuppression.php
  SavedSegment.php
  SystemSetting.php
  User.php

app/Services/Email/
  EmailService.php
  EmailProviderInterface.php
  ResendProvider.php
  BrevoProvider.php
  EmailPayload.php
  EmailResult.php
  EmailWebhookEvent.php
  EmailPersonalizer.php
  HtmlToText.php
  EmailSanitizer.php
  UnsubscribeLinkBuilder.php
```

Frontend:

```text
resources/js/Pages/
  Dashboard.vue
  Contacts/
  Lists/
  Tags/
  Imports/
  Templates/
  Campaigns/
  Reports/
  Segments/
  Settings/
  ActivityLogs/
  Users/
  Profile/
  Auth/
  Errors/

resources/js/Components/
  common/
  layout/
  ui/
  campaigns/
  icons/

resources/js/types/
  index.ts
  contact.ts
  template.ts
  campaign.ts
  import.ts
  user.ts
```

## Routes

Use this route structure:

```php
Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/global-search', GlobalSearchController::class)->name('global-search');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/contacts/bulk-action', [ContactController::class, 'bulkAction'])->name('contacts.bulk-action');
    Route::get('/contacts/export', [ContactController::class, 'export'])->name('contacts.export');
    Route::post('/contacts/{contact}/block', [ContactController::class, 'block'])->name('contacts.block');
    Route::post('/contacts/{contact}/unsubscribe', [ContactController::class, 'unsubscribe'])->name('contacts.unsubscribe');
    Route::resource('contacts', ContactController::class);

    Route::resource('tags', TagController::class);
    Route::post('/lists/{list}/add-contacts', [ListController::class, 'addContacts'])->name('lists.add-contacts');
    Route::post('/lists/{list}/remove-contacts', [ListController::class, 'removeContacts'])->name('lists.remove-contacts');
    Route::get('/lists/{list}/export', [ListController::class, 'export'])->name('lists.export');
    Route::resource('lists', ListController::class);

    Route::get('/imports/create', [ImportController::class, 'create'])->name('imports.create');
    Route::post('/imports/upload', [ImportController::class, 'upload'])->name('imports.upload');
    Route::get('/imports/{import}/mapping', [ImportController::class, 'mapping'])->name('imports.mapping');
    Route::post('/imports/{import}/preview', [ImportController::class, 'preview'])->name('imports.preview');
    Route::post('/imports/{import}/confirm', [ImportController::class, 'confirm'])->name('imports.confirm');
    Route::get('/imports/{import}/download-failed', [ImportController::class, 'downloadFailed'])->name('imports.download-failed');
    Route::resource('imports', ImportController::class)->only(['index', 'show', 'destroy']);

    Route::post('/templates/{template}/duplicate', [EmailTemplateController::class, 'duplicate'])->name('templates.duplicate');
    Route::resource('templates', EmailTemplateController::class);

    Route::get('/campaigns/builder', [CampaignController::class, 'builder'])->name('campaigns.builder');
    Route::get('/campaigns/audience/contacts', [CampaignController::class, 'audienceContacts'])->name('campaigns.audience.contacts');
    Route::post('/campaigns/audience/estimate', [CampaignController::class, 'audienceEstimate'])->name('campaigns.audience.estimate');
    Route::post('/campaigns/{campaign}/send', [CampaignController::class, 'send'])->name('campaigns.send');
    Route::post('/campaigns/{campaign}/send-test', [CampaignController::class, 'sendTest'])->name('campaigns.send-test');
    Route::post('/campaigns/{campaign}/resend-failed', [CampaignController::class, 'resendFailed'])->name('campaigns.resend-failed');
    Route::post('/campaigns/{campaign}/recipients/{recipient}/resend', [CampaignController::class, 'resendRecipient'])->name('campaigns.recipients.resend');
    Route::post('/campaigns/{campaign}/pause', [CampaignController::class, 'pause'])->name('campaigns.pause');
    Route::post('/campaigns/{campaign}/resume', [CampaignController::class, 'resume'])->name('campaigns.resume');
    Route::post('/campaigns/{campaign}/cancel', [CampaignController::class, 'cancel'])->name('campaigns.cancel');
    Route::post('/campaigns/{campaign}/duplicate', [CampaignController::class, 'duplicate'])->name('campaigns.duplicate');
    Route::get('/campaigns/{campaign}/report', [CampaignController::class, 'report'])->name('campaigns.report');
    Route::get('/campaigns/{campaign}/recipients', [CampaignController::class, 'recipients'])->name('campaigns.recipients');
    Route::resource('campaigns', CampaignController::class);

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/campaigns/{campaign}', [ReportController::class, 'campaignReport'])->name('reports.campaign');
    Route::get('/reports/campaigns/{campaign}/export', [ReportController::class, 'exportCampaign'])->name('reports.campaign.export');

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-email', [SettingController::class, 'testEmail'])->name('settings.test-email');

    Route::post('/segments/{segment}/preview', [SavedSegmentController::class, 'preview'])->name('segments.preview');
    Route::resource('segments', SavedSegmentController::class);

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified', 'role:owner,admin'])->group(function () {
    Route::resource('users', UserController::class);
});

Route::post('/webhooks/email/resend', ResendWebhookController::class)->name('webhooks.email.resend');
Route::post('/webhooks/email/brevo', BrevoWebhookController::class)->name('webhooks.email.brevo');
Route::get('/unsubscribe/{signedToken}', [UnsubscribeController::class, 'show'])->name('unsubscribe.show');
Route::post('/unsubscribe/{signedToken}', [UnsubscribeController::class, 'store'])->name('unsubscribe.store');
```

## Testing Requirements

Every meaningful change must be tested.

Required feature tests:

- Auth login/logout.
- Public registration disabled/hidden if not needed.
- Owner/admin user management.
- Admin cannot escalate to owner.
- Inactive user cannot access app.
- Contacts index filters, sorting, pagination, export.
- Contact create/update/delete/block/unsubscribe.
- Lists CRUD and contact add/remove/export.
- Tags CRUD.
- Imports upload/preview/confirm/process/failures/idempotency.
- Import show JSON and Inertia contracts match.
- Templates CRUD/duplicate/validation.
- Campaign draft create.
- Campaign audience estimate matches send preparation.
- Manual audience selection.
- Campaign send queues recipients.
- Single send success.
- Single send provider failure.
- Campaign pause/resume/cancel.
- Retry one failed recipient.
- Retry all failed recipients.
- Dynamic variables render correctly.
- Unresolved variables are handled.
- Test email success/failure.
- Resend provider with `Http::fake()`.
- Brevo provider with `Http::fake()`.
- Webhook verification rejects invalid signature.
- Webhooks update recipient/message/contact/campaign state.
- Suppression blocks sends.
- Reports index works on SQLite and production DB.
- Campaign report flattens data for UI.
- Activity logs include details and safe null fallbacks.
- Settings Inertia update redirects with flash; JSON endpoints return JSON only when expected.
- Global search returns grouped results.

Required unit tests:

- Email normalizer.
- Email personalizer.
- HTML to text fallback.
- Email sanitizer.
- Provider payload mapping.
- Provider webhook event mapping.
- Suppression decision logic.

Frontend/build:

- `npm run build` must pass.
- `vue-tsc` must pass.
- No known runtime blank screens on sidebar navigation.

Backend:

- `vendor/bin/pint --dirty --format agent`.
- Focused tests after changes.
- Full `php artisan test --compact` before handoff.

## Production Readiness Gate

Do not call the app production-ready until all are true:

- Full test suite passes.
- Frontend build passes.
- Queue worker tested locally.
- Campaign send works with provider fake.
- At least one real provider test can be performed safely in local/staging.
- Webhook signature verification tested.
- No demo data in production seeder.
- `.env.example` complete.
- No secrets committed.
- All top-level pages work via Inertia navigation and hard refresh.
- All reports use DB-portable SQL or tested driver-specific expressions.
- All pages have empty states.
- All destructive actions confirm.
- All background jobs are idempotent.
- All provider failures are visible.
- Retry capabilities exist.
- Activity logs record important state changes.
- Suppression/unsubscribe/bounce/complaint handling works.
- App timezone defaults to `Asia/Colombo`.
- User management works through owner/admin.

## Backend Contract Details

Pagination shape returned to Inertia:

```php
[
    'data' => [...],
    'meta' => [
        'current_page' => 1,
        'last_page' => 1,
        'per_page' => 25,
        'total' => 0,
        'from' => null,
        'to' => null,
    ],
]
```

Every index response should include:

```php
[
    'items' => $paginated,
    'filters' => $request->only([...]),
    'filterOptions' => [...],
]
```

For page-specific names use `contacts`, `campaigns`, `templates`, `activities`, etc., but keep the pagination meta shape consistent.

Controller rules:

- Normalize invalid filter values before using them.
- Return safe `null` or fallback values for optional props.
- Return arrays shaped for UI, not raw Eloquent models, when the UI depends on derived fields.
- For JSON and Inertia versions of the same detail page, keep shapes compatible unless intentionally documented.
- Use `expectsJson()` or `wantsJson()` carefully. Inertia form posts normally want redirects.
- Use `back()->with('success', ...)` for Inertia form success.
- Use `back()->withErrors([...])` for Inertia validation-like failures.
- Use `response()->json(...)` for explicit AJAX endpoints only.

Model scopes to implement:

```php
Contact::search($query)
Contact::active()
Contact::emailable()
Contact::suppressed()
Contact::bySource($source)
Contact::byStatus($status)

EmailCampaign::search($query)
EmailCampaign::byStatus($status)
EmailCampaign::active()
EmailCampaign::terminal()

EmailTemplate::search($query)
EmailTemplate::active()

ListModel::search($query)
ListModel::active()

Tag::search($query)
```

Derived attributes:

- `Contact::display_name`: full name, first/last, company, or email fallback.
- `EmailCampaign::success_rate`: sent/delivered/opened/clicked over total recipients.
- `EmailCampaign::delivery_rate`.
- `EmailCampaign::open_rate`.
- `EmailCampaign::click_rate`.
- `EmailCampaign::created_by_name`: creator name or `System`.
- `Import::progress_percent`.

Count synchronization:

- Campaign counts must be recomputed from recipients, not guessed from UI actions.
- After every send, retry, webhook, pause/resume/cancel, call a shared count refresher.
- Keep `pending_count` semantics clear. If UI shows `pending`, decide whether queued is included and use same rule everywhere.
- Campaign index, show, report, and dashboard must agree.

Status terminality:

```php
final recipient statuses = [
    'sent',
    'delivered',
    'opened',
    'clicked',
    'failed',
    'bounced',
    'complained',
    'skipped',
];
```

Provider failure taxonomy:

- validation failure: bad payload before provider call.
- provider rejected: provider returned 4xx.
- provider unavailable: timeout/5xx/network.
- rate limited: provider returned 429.
- accepted then bounced: webhook event after accepted send.
- suppressed locally: local suppression prevented call.
- suppressed by provider: provider/webhook says suppressed.

Retry rules:

- Do not retry local suppression, unsubscribe, complaint, or hard bounce by default.
- Retry transient provider errors.
- Retry failed provider calls only after incrementing attempt count and storing reason.
- Retrying one recipient must not duplicate successful recipient rows.
- Retrying all failed must only target retryable failed rows.

Idempotency:

- Recipient insert: unique `campaign_id + email_normalized`.
- Single send: lock by `campaign_recipient_id`.
- Provider message: if a recipient already has accepted `provider_message_id`, do not send again unless explicit retry creates a new attempt record or clears state intentionally.
- Webhook event: unique provider event key.
- Import confirm: cannot enqueue/process same import twice.

## JSON Endpoint Contracts

Audience contacts:

```json
{
  "contacts": [
    {
      "id": 1,
      "name": "Jane Doe",
      "email": "jane@example.com",
      "status": "active",
      "company": "Example"
    }
  ]
}
```

Audience estimate:

```json
{
  "count": 120,
  "suppressed_count": 3,
  "sendable_count": 117
}
```

Global search:

```json
{
  "query": "jane",
  "groups": [
    {
      "label": "Contacts",
      "items": [
        {
          "id": "contact-1",
          "title": "Jane Doe",
          "subtitle": "jane@example.com",
          "badge": "active",
          "url": "/contacts/1"
        }
      ]
    }
  ]
}
```

Provider health/test:

```json
{
  "ok": true,
  "provider": "resend",
  "message": "Provider accepted the test email."
}
```

Campaign progress:

```json
{
  "campaign": {
    "id": 1,
    "status": "sending",
    "total_recipients": 100,
    "pending_count": 10,
    "queued_count": 5,
    "sent_count": 80,
    "failed_count": 5,
    "delivered_count": 70,
    "opened_count": 30,
    "clicked_count": 8
  }
}
```

Import progress:

```json
{
  "import": {
    "id": 1,
    "status": "processing",
    "progress": 45.5,
    "total_rows": 1000,
    "processed_rows": 455,
    "successful_rows": 430,
    "failed_rows": 25,
    "duplicate_handling": "update",
    "lists": [],
    "tags": []
  }
}
```

## Queue Recovery And Maintenance

Add artisan commands if needed:

```text
emailora:campaigns:recover
emailora:campaigns:finalize-stuck
emailora:providers:test
emailora:suppressions:sync
emailora:imports:cleanup
```

Recovery command behavior:

- Find campaigns stuck in `queued`, `preparing`, or `sending`.
- Recompute counts from recipients.
- If no pending/queued rows remain, finalize.
- If pending rows exist and campaign is active, dispatch send job.
- If campaign has been sending too long with no activity, log warning and mark failed only if no safe resume is possible.
- Never double-send accepted recipients.

Scheduler:

- Run campaign recovery every few minutes.
- Run import cleanup daily.
- Run failed job monitoring/alerts where deployment supports it.

Queue configuration:

- `retry_after` must exceed job timeout.
- Long campaign jobs should not block short jobs forever; use queues like `default`, `imports`, `email`.
- Use supervisor/Horizon/Laravel Cloud worker config in production.

## Observability

Logs:

- Structured context for campaign ID, recipient ID, contact ID, provider, provider message ID.
- Log provider failures without secrets or full HTML.
- Log webhook verification failures with minimal metadata.

Metrics to expose or calculate:

- Send acceptance rate.
- Delivery rate.
- Bounce rate.
- Complaint rate.
- Open rate.
- Click rate.
- Provider error rate.
- Queue lag.
- Stuck campaign count.

Admin-visible diagnostics:

- Provider configured: yes/no.
- From email verified: yes/no/unknown.
- Webhook last received at.
- Last provider error.
- Queue worker appears active if detectable.

## Deployment And Operations

Production deployment checklist:

- `APP_ENV=production`.
- `APP_DEBUG=false`.
- `APP_URL` correct.
- `APP_TIMEZONE=Asia/Colombo` unless changed by owner.
- Database migrated.
- Queue worker running.
- Scheduler running.
- Storage link only if needed.
- Cache config/routes/views after env finalized.
- Provider keys configured.
- Webhook URLs registered in Resend/Brevo.
- Domains verified.
- Owner account seeded.
- Demo seeder not run.

Commands:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Local development:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
composer run dev
```

Local queue:

- The dev script should run server, queue listener, logs, and Vite together.
- For provider tests, allow fake provider mode so local work does not accidentally email real contacts.

Staging:

- Use separate provider domain/subdomain.
- Use test list only.
- Webhook endpoint must be publicly reachable.
- Send one real test email and verify webhook receipt.

Backups:

- Database backups configured.
- Import files retention policy.
- Provider event logs retention policy.
- Failed rows downloadable for a reasonable period.

Data retention:

- Keep campaign records.
- Keep provider event records long enough for audit.
- Optionally prune raw webhook payloads after safe period, but never before debugging window.

## Edge Case Matrix

Contacts:

- Duplicate email in different casing.
- Email with leading/trailing spaces.
- Invalid email.
- Missing name.
- Very long name/email/company.
- Contact unsubscribed then re-imported.
- Contact hard bounced then included in campaign.
- Contact manually blocked.
- Contact deleted but recipient history remains.

Imports:

- Empty CSV.
- CSV with only headers.
- XLSX with blank first sheet.
- Large file.
- Duplicate emails in same file.
- Duplicate emails already in DB.
- Invalid encoding.
- Unknown columns.
- Required email column missing.
- Double submit confirm.
- Queue fails mid-import.

Templates:

- Empty subject.
- Empty body.
- HTML only.
- Text only.
- Broken HTML.
- Script tags.
- Unknown variables.
- Long subject.
- Missing unsubscribe link for marketing.

Campaigns:

- No audience.
- Audience all suppressed.
- Provider not configured.
- From email unverified.
- Campaign paused mid-send.
- Campaign cancelled mid-send.
- Queue worker stopped then restarted.
- User closes tab.
- Provider returns 429.
- Provider timeout after accepting message.
- Provider returns accepted but webhook delayed.
- Webhook arrives before UI refresh.
- Duplicate webhook.
- Retry failed after some contacts unsubscribed.
- Scheduled campaign in past.
- Duplicate campaign with deleted template.

Reports:

- No data.
- Single campaign.
- Long campaign names.
- SQLite local/test.
- PostgreSQL/MySQL production.
- Timezone boundary at month/day start.

Activity:

- Null event.
- Missing subject.
- Deleted subject.
- Large properties payload.
- Actor deleted.

Users:

- Admin tries to create owner.
- Admin tries to edit owner/admin.
- User tries to delete self.
- Inactive user logs in.
- Missing shared auth prop during navigation.

Settings:

- Provider key missing.
- Provider test fails.
- Rate limit invalid.
- From email invalid.
- Timezone invalid.
- Inertia form receives JSON accidentally.

## Known Mistakes To Avoid

Avoid these classes of issues from day one:

- Do not leave required page props unguarded in Vue.
- Do not assume `page.props.auth.user.role` exists.
- Do not return JSON to Inertia forms.
- Do not let campaign list and campaign detail disagree on status.
- Do not let progress freeze after tab close.
- Do not let queued recipients remain queued without recovery/finalization.
- Do not skip dynamic variable replacement verification.
- Do not show literal `{first_name}` in sent messages.
- Do not hide activity logs deep in the UI.
- Do not make log View buttons useless.
- Do not crush list/tag columns.
- Do not make `+N` badge invisible.
- Do not make contact source filters generic when real source values exist.
- Do not only sort the current page client-side.
- Do not use DB-specific report SQL without test coverage.
- Do not let JSON and Inertia show endpoints return incompatible shapes.
- Do not seed demo records into production.
- Do not overwrite live imported data from seeders.
- Do not leave provider URL/config changes unexplained.
- Do not claim a send succeeded until provider acceptance is stored.

## Suggested Implementation Order

1. Scaffold Laravel/Inertia/Vue app and auth.
2. Build shared UI components and layout to match this brief.
3. Add owner-only production seeder and optional demo seeder.
4. Add users/roles/policies.
5. Add contacts/lists/tags.
6. Add imports.
7. Add email templates.
8. Add provider interface and fake provider tests.
9. Add Resend provider.
10. Add Brevo provider.
11. Add campaigns and audience selection.
12. Add send jobs and retry/finalization.
13. Add webhooks and suppression.
14. Add reports.
15. Add activity logs everywhere.
16. Add settings/provider health/domain checklist.
17. Add global search.
18. Add production hardening and full verification.

## Agent Acceptance Checklist

The implementing agent should finish with:

- A working local app URL.
- Owner login details from local env.
- Full test output.
- Build output.
- List of files changed.
- Provider setup instructions.
- Clear note of anything intentionally deferred.
- No broad redesign.
- No unverified claim that real email was sent unless it was actually sent.
