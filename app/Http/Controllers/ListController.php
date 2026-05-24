<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsTableProps;
use App\Http\Requests\ListRequest;
use App\Models\Contact;
use App\Models\ListModel;
use App\Services\Activity\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListController extends Controller
{
    use BuildsTableProps;

    public function index(Request $request): Response
    {
        $lists = ListModel::query()
            ->withCount('contacts')
            ->search($request->string('search')->toString())
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate($this->perPage($request->input('per_page')))
            ->withQueryString();

        return Inertia::render('Lists/Index', [
            'lists' => $this->pagination($lists),
            'filters' => $request->only(['search', 'status', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Lists/Form', ['list' => null]);
    }

    public function store(ListRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        $data['created_by'] = $request->user()->id;
        $list = ListModel::create($data);

        return redirect()->route('lists.show', $list)->with('success', 'List saved.');
    }

    public function show(Request $request, ListModel $list): Response
    {
        $contacts = $list->contacts()->search($request->string('search')->toString())->latest('contacts.created_at')->paginate($this->perPage($request->input('per_page')))->withQueryString();
        $availableContacts = Contact::active()
            ->whereNotIn('id', $list->contacts()->select('contacts.id'))
            ->orderBy('email')
            ->limit(100)
            ->get(['id', 'full_name', 'email']);

        return Inertia::render('Lists/Show', [
            'list' => $list,
            'contacts' => $this->pagination($contacts),
            'availableContacts' => $availableContacts,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function edit(ListModel $list): Response
    {
        return Inertia::render('Lists/Form', ['list' => $list]);
    }

    public function update(ListRequest $request, ListModel $list): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        $list->update($data);

        return redirect()->route('lists.show', $list)->with('success', 'List updated.');
    }

    public function destroy(ListModel $list): RedirectResponse
    {
        $list->delete();

        return back()->with('success', 'List deleted.');
    }

    public function addContacts(Request $request, ListModel $list): RedirectResponse
    {
        $data = $request->validate(['contact_ids' => ['required', 'array'], 'contact_ids.*' => ['integer', 'exists:contacts,id']]);
        $list->contacts()->syncWithoutDetaching($data['contact_ids']);
        app(ActivityLogger::class)->log('list.contacts_added', 'Contacts were added to list.', $list, [
            'count' => count($data['contact_ids']),
            'contact_ids' => array_slice($data['contact_ids'], 0, 50),
        ], 'contacts');

        return back()->with('success', 'Contacts added.');
    }

    public function removeContacts(Request $request, ListModel $list): RedirectResponse
    {
        $data = $request->validate(['contact_ids' => ['required', 'array'], 'contact_ids.*' => ['integer', 'exists:contacts,id']]);
        $list->contacts()->detach($data['contact_ids']);
        app(ActivityLogger::class)->log('list.contacts_removed', 'Contacts were removed from list.', $list, [
            'count' => count($data['contact_ids']),
            'contact_ids' => array_slice($data['contact_ids'], 0, 50),
        ], 'contacts', 'warning');

        return back()->with('success', 'Contacts removed.');
    }

    public function export(ListModel $list): StreamedResponse
    {
        return response()->streamDownload(function () use ($list): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'name', 'status']);
            $list->contacts()->cursor()->each(fn (Contact $contact) => fputcsv($out, [$contact->email, $contact->display_name, $contact->status]));
        }, Str::slug($list->name).'-contacts.csv');
    }
}
