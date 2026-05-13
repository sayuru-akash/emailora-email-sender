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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function builder(): Response
    {
        return Inertia::render('Campaigns/Builder', $this->builderProps());
    }

    public function create(): Response
    {
        return $this->builder();
    }

    public function store(CampaignRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['status'] = $data['status'] ?? 'draft';
        $campaign = EmailCampaign::create($data);

        return redirect()->route('campaigns.show', $campaign)->with('success', 'Campaign saved.');
    }

    public function show(EmailCampaign $campaign): Response
    {
        return Inertia::render('Campaigns/Show', [
            'campaign' => $campaign->loadCount('recipients'),
            'recipients' => $campaign->recipients()->latest()->limit(20)->get(),
        ]);
    }

    public function edit(EmailCampaign $campaign): Response
    {
        return Inertia::render('Campaigns/Builder', $this->builderProps($campaign));
    }

    public function update(CampaignRequest $request, EmailCampaign $campaign): RedirectResponse
    {
        abort_unless(in_array($campaign->status, ['draft', 'scheduled'], true), 422, 'Only draft or scheduled campaigns can be edited.');
        $campaign->update($request->validated());

        return back()->with('success', 'Campaign updated.');
    }

    public function destroy(EmailCampaign $campaign): RedirectResponse
    {
        abort_if(in_array($campaign->status, ['queued', 'preparing', 'sending'], true), 422, 'Pause or cancel the campaign before deleting.');
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
        $warnings = $personalizer->unresolvedVariables(($campaign->subject ?? '').($campaign->html_body ?? '').($campaign->text_body ?? ''));
        if ($warnings) {
            return back()->withErrors(['campaign' => 'Unresolved variables remain: '.implode(', ', $warnings)]);
        }

        if (! str_contains((string) $campaign->html_body.(string) $campaign->text_body, 'unsubscribe')) {
            return back()->withErrors(['campaign' => 'Marketing email requires an unsubscribe link.']);
        }

        if ($resolver->queryForCampaign($campaign)->count() < 1) {
            return back()->withErrors(['campaign' => 'Audience is empty.']);
        }

        $campaign->update(['status' => $request->filled('scheduled_at') ? 'scheduled' : 'queued', 'scheduled_at' => $request->date('scheduled_at')]);

        if (! $request->filled('scheduled_at')) {
            PrepareEmailCampaignRecipients::dispatch($campaign->id)->onQueue('email');
        }

        return back()->with('success', $request->filled('scheduled_at') ? 'Campaign scheduled.' : 'Campaign queued.');
    }

    public function pause(EmailCampaign $campaign): RedirectResponse
    {
        $campaign->update(['status' => 'paused']);

        return back()->with('success', 'Campaign paused.');
    }

    public function resume(EmailCampaign $campaign): RedirectResponse
    {
        $campaign->update(['status' => 'queued']);
        PrepareEmailCampaignRecipients::dispatch($campaign->id)->onQueue('email');

        return back()->with('success', 'Campaign resumed.');
    }

    public function cancel(EmailCampaign $campaign): RedirectResponse
    {
        $campaign->update(['status' => 'cancelled', 'completed_at' => now()]);

        return back()->with('success', 'Campaign cancelled.');
    }

    public function resendFailed(EmailCampaign $campaign): RedirectResponse
    {
        $campaign->recipients()->where('status', 'failed')->update(['status' => 'queued', 'error_message' => null]);
        $campaign->recipients()->where('status', 'queued')->pluck('id')->each(fn ($id) => SendSingleEmail::dispatch($id)->onQueue('email'));

        return back()->with('success', 'Failed recipients queued.');
    }

    public function resendRecipient(EmailCampaign $campaign, CampaignRecipient $recipient): RedirectResponse
    {
        abort_unless($recipient->email_campaign_id === $campaign->id, 404);
        abort_unless($recipient->status === 'failed', 422, 'Only failed recipients can be retried.');
        $recipient->update(['status' => 'queued', 'error_message' => null]);
        SendSingleEmail::dispatch($recipient->id)->onQueue('email');

        return back()->with('success', 'Recipient retry queued.');
    }

    public function duplicate(EmailCampaign $campaign): RedirectResponse
    {
        $copy = $campaign->replicate(['uuid', 'status', 'started_at', 'completed_at']);
        $copy->name = $campaign->name.' Copy';
        $copy->status = 'draft';
        $copy->save();

        return redirect()->route('campaigns.edit', $copy)->with('success', 'Campaign duplicated.');
    }

    public function recipients(Request $request, EmailCampaign $campaign): Response
    {
        $recipients = $campaign->recipients()->with('contact')->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))->latest()->paginate($this->perPage($request->input('per_page')))->withQueryString();

        return Inertia::render('Campaigns/Recipients', ['campaign' => $campaign, 'recipients' => $this->pagination($recipients), 'filters' => $request->only(['status', 'per_page'])]);
    }

    public function report(EmailCampaign $campaign, CampaignCountRefresher $refresher): Response
    {
        return Inertia::render('Campaigns/Report', ['campaign' => $refresher->refresh($campaign), 'breakdown' => $campaign->recipients()->selectRaw('status, count(*) as total')->groupBy('status')->get()]);
    }

    public function sendTest(): RedirectResponse
    {
        return back()->withErrors(['provider' => 'Test email requires provider configuration.']);
    }

    private function builderProps(?EmailCampaign $campaign = null): array
    {
        return [
            'campaign' => $campaign,
            'templates' => EmailTemplate::active()->orderBy('name')->get(['id', 'name', 'subject', 'preheader', 'html_body', 'text_body']),
            'lists' => ListModel::active()->orderBy('name')->get(['id', 'name']),
            'tags' => Tag::orderBy('name')->get(['id', 'name']),
            'defaults' => [
                'from_name' => config('emailora.from_name'),
                'from_email' => config('emailora.from_email'),
                'reply_to_email' => config('emailora.reply_to'),
                'provider' => config('emailora.provider'),
            ],
        ];
    }
}
