<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsTableProps;
use App\Http\Requests\CampaignRequest;
use App\Http\Requests\CampaignSendRequest;
use App\Jobs\PrepareEmailCampaignRecipients;
use App\Jobs\SendSingleEmail;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\ListModel;
use App\Models\Tag;
use App\Services\Email\AudienceResolver;
use App\Services\Email\CampaignCountRefresher;
use App\Services\Email\EmailPersonalizer;
use App\Services\Email\EmailPreviewDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    use BuildsTableProps;

    public function index(Request $request): Response
    {
        $campaigns = EmailCampaign::query()
            ->search($request->string('search')->toString())
            ->byStatus($request->string('status')->toString() ?: null)
            ->when($request->filled('provider'), fn ($query) => $query->where('provider', $request->string('provider')))
            ->latest()
            ->paginate($this->perPage($request->input('per_page')))
            ->withQueryString();

        return Inertia::render('Campaigns/Index', ['campaigns' => $this->pagination($campaigns), 'filters' => $request->only(['search', 'status', 'provider', 'per_page'])]);
    }

    public function builder(EmailPersonalizer $personalizer): Response
    {
        return Inertia::render('Campaigns/Builder', $this->builderProps(null, $personalizer));
    }

    public function create(EmailPersonalizer $personalizer): Response
    {
        return Inertia::render('Campaigns/Builder', $this->builderProps(null, $personalizer));
    }

    public function store(CampaignRequest $request): RedirectResponse
    {
        $data = $this->withUnsubscribeToken($request->validated());
        $data['created_by'] = $request->user()->id;
        $data['status'] = $data['status'] ?? 'draft';
        $campaign = EmailCampaign::create($data);

        return redirect()->route('campaigns.show', $campaign)->with('success', 'Campaign saved.');
    }

    public function show(EmailCampaign $campaign): Response
    {
        $preparedCount = $campaign->recipients()->count();
        $sendableCount = app(AudienceResolver::class)->queryForCampaign($campaign)->count();

        return Inertia::render('Campaigns/Show', [
            'campaign' => $campaign->loadCount('recipients'),
            'recipients' => $campaign->recipients()->latest()->limit(20)->get(),
            'audience' => [
                'prepared_count' => $preparedCount,
                'sendable_count' => $sendableCount,
                'display_count' => $preparedCount > 0 ? $preparedCount : $sendableCount,
            ],
            'actions' => $this->campaignActions($campaign),
        ]);
    }

    public function preview(EmailCampaign $campaign, EmailPreviewDocument $preview, EmailPersonalizer $personalizer): HttpResponse
    {
        $metadataKeys = $personalizer->metadataKeysFromContacts();

        return $preview->response(
            $personalizer->renderSample((string) $campaign->html_body, $metadataKeys),
            $personalizer->renderSample((string) $campaign->preheader, $metadataKeys),
        );
    }

    public function edit(EmailCampaign $campaign, EmailPersonalizer $personalizer): Response
    {
        abort_unless(in_array($campaign->status, ['draft', 'scheduled'], true), 422, 'Only draft or scheduled campaigns can be edited.');

        return Inertia::render('Campaigns/Builder', $this->builderProps($campaign, $personalizer));
    }

    public function update(CampaignRequest $request, EmailCampaign $campaign): RedirectResponse
    {
        abort_unless(in_array($campaign->status, ['draft', 'scheduled'], true), 422, 'Only draft or scheduled campaigns can be edited.');
        $campaign->update($this->withUnsubscribeToken($request->validated()));

        return back()->with('success', 'Campaign updated.');
    }

    public function destroy(EmailCampaign $campaign): RedirectResponse
    {
        abort_if(in_array($campaign->status, ['queued', 'preparing', 'sending', 'paused'], true), 422, 'Cancel the campaign before deleting.');
        $campaign->delete();

        return redirect()->route('campaigns.index')->with('success', 'Campaign deleted.');
    }

    public function audienceContacts(Request $request): JsonResponse
    {
        return response()->json([
            'contacts' => Contact::emailable()->search($request->string('search')->toString())->limit(20)->get(['id', 'full_name', 'email', 'status', 'company'])->map(fn (Contact $contact) => [
                'id' => $contact->id,
                'name' => $contact->display_name,
                'email' => $contact->email,
                'status' => $contact->status,
                'company' => $contact->company,
            ]),
        ]);
    }

    public function audienceEstimate(Request $request, AudienceResolver $resolver): JsonResponse
    {
        $campaign = new EmailCampaign($request->validate([
            'target_type' => ['required', 'in:all_contacts,list,tag,saved_segment,manual_selection,advanced_filter'],
            'target_filters' => ['nullable', 'array'],
        ]));

        $sendable = $resolver->queryForCampaign($campaign)->count();
        $active = Contact::active()->count();

        return response()->json(['count' => $active, 'suppressed_count' => max(0, $active - $sendable), 'sendable_count' => $sendable]);
    }

    public function send(CampaignSendRequest $request, EmailCampaign $campaign, AudienceResolver $resolver, EmailPersonalizer $personalizer): RedirectResponse
    {
        abort_unless(in_array($campaign->status, ['draft', 'scheduled'], true), 422, 'Only draft or scheduled campaigns can be sent.');

        $campaign->update($this->withUnsubscribeToken([
            'html_body' => $campaign->html_body,
            'text_body' => $campaign->text_body,
        ]));
        $campaign->refresh();

        $content = $this->personalizableContent($campaign);
        $warnings = $personalizer->unresolvedVariables($content, $personalizer->metadataKeysFromContacts());
        if ($warnings) {
            return back()->withErrors(['campaign' => 'Unresolved variables remain: '.implode(', ', $warnings)]);
        }

        if ($resolver->queryForCampaign($campaign)->count() < 1) {
            return back()->withErrors(['campaign' => 'Audience is empty.']);
        }

        $recipientMode = $request->string('recipient_mode')->toString() ?: 'current_audience';
        $campaign->update([
            'status' => $request->filled('scheduled_at') ? 'scheduled' : 'queued',
            'scheduled_at' => $request->date('scheduled_at'),
            'recipient_mode' => $recipientMode,
        ]);

        if (! $request->filled('scheduled_at')) {
            PrepareEmailCampaignRecipients::dispatch($campaign->id, $recipientMode)->onQueue('email');
        }

        return back()->with('success', $request->filled('scheduled_at') ? 'Campaign scheduled.' : 'Campaign queued.');
    }

    public function pause(EmailCampaign $campaign): RedirectResponse
    {
        abort_unless(in_array($campaign->status, ['queued', 'preparing', 'sending'], true), 422, 'Only active campaigns can be paused.');

        $campaign->update(['status' => 'paused']);

        return back()->with('success', 'Campaign paused.');
    }

    public function resume(EmailCampaign $campaign): RedirectResponse
    {
        abort_unless($campaign->status === 'paused', 422, 'Only paused campaigns can be resumed.');

        $campaign->update(['status' => 'queued']);
        PrepareEmailCampaignRecipients::dispatch($campaign->id, $campaign->recipient_mode ?: 'current_audience')->onQueue('email');

        return back()->with('success', 'Campaign resumed.');
    }

    public function cancel(EmailCampaign $campaign): RedirectResponse
    {
        abort_unless(in_array($campaign->status, ['scheduled', 'queued', 'preparing', 'sending', 'paused'], true), 422, 'Only scheduled or active campaigns can be cancelled.');

        $campaign->update(['status' => 'cancelled', 'completed_at' => now()]);

        return back()->with('success', 'Campaign cancelled.');
    }

    public function resendFailed(EmailCampaign $campaign): RedirectResponse
    {
        abort_unless(in_array($campaign->status, ['completed', 'failed'], true), 422, 'Only completed or failed campaigns can resend failed recipients.');

        $failedIds = $campaign->recipients()->where('status', 'failed')->pluck('id');
        abort_unless($failedIds->isNotEmpty(), 422, 'There are no failed recipients to resend.');

        $campaign->recipients()->whereIn('id', $failedIds)->update(['status' => 'queued', 'error_message' => null]);
        $failedIds->each(fn ($id) => SendSingleEmail::dispatch($id)->onQueue('email'));

        return back()->with('success', 'Failed recipients queued.');
    }

    public function resendRecipient(EmailCampaign $campaign, CampaignRecipient $recipient): RedirectResponse
    {
        abort_unless($recipient->email_campaign_id === $campaign->id, 404);
        abort_unless(in_array($campaign->status, ['completed', 'failed'], true), 422, 'Only completed or failed campaigns can retry failed recipients.');
        abort_unless($recipient->status === 'failed', 422, 'Only failed recipients can be retried.');
        $recipient->update(['status' => 'queued', 'error_message' => null]);
        SendSingleEmail::dispatch($recipient->id)->onQueue('email');

        return back()->with('success', 'Recipient retry queued.');
    }

    public function duplicate(EmailCampaign $campaign): RedirectResponse
    {
        $copy = $campaign->replicate([
            'uuid',
            'status',
            'scheduled_at',
            'started_at',
            'completed_at',
            'total_recipients',
            'queued_count',
            'sent_count',
            'delivered_count',
            'opened_count',
            'clicked_count',
            'failed_count',
            'bounced_count',
            'complained_count',
            'skipped_count',
            'pending_count',
            'approved_by',
        ]);
        $copy->name = $campaign->name.' Copy';
        $copy->status = 'draft';
        $copy->created_by = request()->user()?->id;
        $copy->save();

        return redirect()->route('campaigns.edit', $copy)->with('success', 'Campaign duplicated.');
    }

    public function recipients(Request $request, EmailCampaign $campaign, AudienceResolver $resolver): Response
    {
        if (! $campaign->recipients()->exists() && in_array($campaign->status, ['draft', 'scheduled'], true)) {
            $contacts = $resolver->queryForCampaign($campaign)
                ->orderBy('full_name')
                ->paginate($this->perPage($request->input('per_page')))
                ->withQueryString()
                ->through(fn (Contact $contact): array => [
                    'id' => 'target-'.$contact->id,
                    'contact_id' => $contact->id,
                    'email_normalized' => $contact->email_normalized,
                    'status' => 'targeted',
                    'provider_message_id' => null,
                    'error_message' => null,
                    'contact' => [
                        'id' => $contact->id,
                        'full_name' => $contact->display_name,
                        'email' => $contact->email,
                        'company' => $contact->company,
                    ],
                ]);

            return Inertia::render('Campaigns/Recipients', [
                'campaign' => $campaign,
                'recipients' => $this->pagination($contacts),
                'filters' => $request->only(['status', 'per_page']),
                'mode' => 'target_audience',
            ]);
        }

        $recipients = $campaign->recipients()->with('contact')->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))->latest()->paginate($this->perPage($request->input('per_page')))->withQueryString();

        return Inertia::render('Campaigns/Recipients', ['campaign' => $campaign, 'recipients' => $this->pagination($recipients), 'filters' => $request->only(['status', 'per_page']), 'mode' => 'prepared_recipients']);
    }

    public function report(EmailCampaign $campaign, CampaignCountRefresher $refresher): Response
    {
        return Inertia::render('Campaigns/Report', ['campaign' => $refresher->refresh($campaign), 'breakdown' => $campaign->recipients()->selectRaw('status, count(*) as total')->groupBy('status')->get()]);
    }

    public function sendTest(): RedirectResponse
    {
        return back()->withErrors(['provider' => 'Test email requires provider configuration.']);
    }

    private function builderProps(?EmailCampaign $campaign, EmailPersonalizer $personalizer): array
    {
        return [
            'campaign' => $campaign,
            'templates' => EmailTemplate::active()->orderBy('name')->get(['id', 'name', 'subject', 'preheader', 'html_body', 'text_body']),
            'lists' => ListModel::active()->withCount('contacts')->orderBy('name')->get(['id', 'name']),
            'tags' => Tag::withCount('contacts')->orderBy('name')->get(['id', 'name']),
            'selectedContacts' => $this->selectedContacts($campaign),
            'variableDefinitions' => $personalizer->variableDefinitions($personalizer->metadataKeysFromContacts()),
            'defaults' => [
                'from_name' => config('emailora.from_name'),
                'from_email' => config('emailora.from_email'),
                'reply_to_email' => config('emailora.reply_to'),
                'provider' => config('emailora.provider'),
            ],
        ];
    }

    private function campaignActions(EmailCampaign $campaign): array
    {
        return [
            'canEdit' => in_array($campaign->status, ['draft', 'scheduled'], true),
            'canSend' => in_array($campaign->status, ['draft', 'scheduled'], true),
            'canPause' => in_array($campaign->status, ['queued', 'preparing', 'sending'], true),
            'canResume' => $campaign->status === 'paused',
            'canCancel' => in_array($campaign->status, ['scheduled', 'queued', 'preparing', 'sending', 'paused'], true),
            'canDelete' => ! in_array($campaign->status, ['queued', 'preparing', 'sending', 'paused'], true),
            'canResendFailed' => in_array($campaign->status, ['completed', 'failed'], true) && $campaign->failed_count > 0,
        ];
    }

    private function personalizableContent(EmailCampaign $campaign): string
    {
        return implode("\n", [
            (string) $campaign->subject,
            (string) $campaign->preheader,
            (string) $campaign->html_body,
            (string) $campaign->text_body,
        ]);
    }

    private function withUnsubscribeToken(array $data): array
    {
        $html = (string) ($data['html_body'] ?? '');
        $text = (string) ($data['text_body'] ?? '');

        if (str_contains($html.$text, 'unsubscribe_url')) {
            return $data;
        }

        if ($html !== '') {
            $data['html_body'] = rtrim($html)."\n".'<p style="margin-top:24px;font-size:12px;color:#64748b;">No longer want these emails? <a href="{{ unsubscribe_url }}">Unsubscribe</a>.</p>';
        }

        if ($text !== '') {
            $data['text_body'] = rtrim($text)."\n\nUnsubscribe: {{ unsubscribe_url }}";
        }

        return $data;
    }

    private function selectedContacts(?EmailCampaign $campaign): array
    {
        $filters = $campaign?->target_filters ?? [];
        $contactIds = $filters['contact_ids'] ?? [];

        if ($campaign === null || $campaign->target_type !== 'manual_selection' || $contactIds === []) {
            return [];
        }

        return Contact::query()
            ->whereIn('id', $contactIds)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'email', 'status', 'company'])
            ->map(fn (Contact $contact) => [
                'id' => $contact->id,
                'name' => $contact->display_name,
                'email' => $contact->email,
                'status' => $contact->status,
                'company' => $contact->company,
            ])
            ->all();
    }
}
