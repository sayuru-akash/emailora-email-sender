<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsTableProps;
use App\Http\Requests\ContactRequest;
use App\Models\Contact;
use App\Models\EmailMessage;
use App\Models\ListModel;
use App\Models\Tag;
use App\Services\Activity\ActivityLogger;
use App\Support\SafeCsv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    use BuildsTableProps;

    public function index(Request $request): Response
    {
        $query = Contact::query()
            ->with(['lists:id,name,color', 'tags:id,name,color'])
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('source'), fn ($query) => $query->where('source', $request->string('source')));

        $sort = in_array($request->string('sort')->toString(), ['created_at', 'updated_at', 'email', 'last_contacted_at'], true)
            ? $request->string('sort')->toString()
            : 'created_at';

        $contacts = $query->orderBy($sort, $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc')
            ->paginate($this->perPage($request->input('per_page')))
            ->withQueryString();

        return Inertia::render('Contacts/Index', [
            'contacts' => $this->pagination($contacts),
            'filters' => $request->only(['search', 'status', 'source', 'sort', 'direction', 'per_page']),
            'filterOptions' => [
                'statuses' => ['active', 'inactive', 'unsubscribed', 'bounced', 'complained', 'blocked', 'invalid'],
                'sources' => Contact::whereNotNull('source')->distinct()->orderBy('source')->pluck('source'),
            ],
            'lists' => ListModel::active()->orderBy('name')->get(['id', 'name']),
            'tags' => Tag::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Contacts/Form', $this->formProps());
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $listIds = $data['list_ids'] ?? [];
        $tagIds = $data['tag_ids'] ?? [];
        unset($data['list_ids'], $data['tag_ids']);

        $data['created_by'] = $request->user()->id;
        $contact = Contact::create($data);
        $contact->lists()->sync($listIds);
        $contact->tags()->sync($tagIds);
        app(ActivityLogger::class)->log('contact.membership_synced', 'Contact list and tag membership synced.', $contact, [
            'list_count' => count($listIds),
            'tag_count' => count($tagIds),
        ], 'contacts');

        return redirect()->route('contacts.show', $contact)->with('success', 'Contact saved.');
    }

    public function show(Contact $contact): Response
    {
        return Inertia::render('Contacts/Show', [
            'contact' => $contact->load(['lists:id,name,color', 'tags:id,name,color']),
            'recentMessages' => EmailMessage::where('contact_id', $contact->id)->latest()->limit(10)->get(),
        ]);
    }

    public function edit(Contact $contact): Response
    {
        return Inertia::render('Contacts/Form', $this->formProps($contact->load(['lists:id', 'tags:id'])));
    }

    public function update(ContactRequest $request, Contact $contact): RedirectResponse
    {
        $data = $request->validated();
        $listIds = $data['list_ids'] ?? [];
        $tagIds = $data['tag_ids'] ?? [];
        unset($data['list_ids'], $data['tag_ids']);

        $data['updated_by'] = $request->user()->id;
        $contact->update($data);
        $contact->lists()->sync($listIds);
        $contact->tags()->sync($tagIds);
        app(ActivityLogger::class)->log('contact.membership_synced', 'Contact list and tag membership synced.', $contact, [
            'list_count' => count($listIds),
            'tag_count' => count($tagIds),
        ], 'contacts');

        return back()->with('success', 'Contact updated.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        return redirect()->route('contacts.index')->with('success', 'Contact deleted.');
    }

    public function block(Contact $contact): RedirectResponse
    {
        $contact->update(['status' => 'blocked', 'blocked_at' => now()]);
        app(ActivityLogger::class)->log('contact.blocked', 'Contact was blocked.', $contact, [], 'contacts', 'warning');

        return back()->with('success', 'Contact blocked.');
    }

    public function unsubscribe(Contact $contact): RedirectResponse
    {
        $contact->update(['status' => 'unsubscribed', 'unsubscribed_at' => now()]);
        app(ActivityLogger::class)->log('contact.unsubscribed', 'Contact was manually unsubscribed.', $contact, [], 'contacts', 'warning');

        return back()->with('success', 'Unsubscribe recorded.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:mark_inactive,block,unsubscribe,delete'],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:contacts,id'],
        ]);

        $contacts = Contact::whereIn('id', $validated['ids']);
        match ($validated['action']) {
            'mark_inactive' => $contacts->update(['status' => 'inactive']),
            'block' => $contacts->update(['status' => 'blocked', 'blocked_at' => now()]),
            'unsubscribe' => $contacts->update(['status' => 'unsubscribed', 'unsubscribed_at' => now()]),
            'delete' => $contacts->delete(),
        };
        app(ActivityLogger::class)->log('contact.bulk_action', 'Bulk contact action completed.', null, [
            'action' => $validated['action'],
            'count' => count($validated['ids']),
            'ids' => array_slice($validated['ids'], 0, 50),
        ], 'contacts', $validated['action'] === 'delete' ? 'warning' : 'info');

        return back()->with('success', 'Bulk action completed.');
    }

    public function export(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            SafeCsv::writeRow($out, ['email', 'name', 'status', 'source', 'created_at']);
            Contact::orderBy('id')->cursor()->each(fn (Contact $contact) => SafeCsv::writeRow($out, [
                $contact->email,
                $contact->display_name,
                $contact->status,
                $contact->source,
                $contact->created_at?->toIso8601String(),
            ]));
        }, 'contacts.csv');
    }

    private function formProps(?Contact $contact = null): array
    {
        return [
            'contact' => $contact,
            'lists' => ListModel::active()->orderBy('name')->get(['id', 'name']),
            'tags' => Tag::orderBy('name')->get(['id', 'name']),
        ];
    }
}
