<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsTableProps;
use App\Jobs\ProcessImport;
use App\Models\ContactImport;
use App\Models\ListModel;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    use BuildsTableProps;

    public function index(Request $request): Response
    {
        $imports = ContactImport::latest()->paginate($this->perPage($request->input('per_page')))->withQueryString();

        return Inertia::render('Imports/Index', ['imports' => $this->pagination($imports), 'filters' => $request->only(['per_page'])]);
    }

    public function create(): Response
    {
        return Inertia::render('Imports/Create', ['lists' => ListModel::active()->get(['id', 'name']), 'tags' => Tag::orderBy('name')->get(['id', 'name'])]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:20480'],
            'duplicate_handling' => ['required', 'in:skip,update,add_to_list_tag,upsert'],
            'list_ids' => ['array'],
            'tag_ids' => ['array'],
        ]);

        $path = $data['file']->store('imports');
        $import = ContactImport::create([
            'file_name' => $data['file']->getClientOriginalName(),
            'disk_path' => $path,
            'file_type' => $data['file']->getClientOriginalExtension(),
            'duplicate_handling' => $data['duplicate_handling'],
            'assigned_list_ids' => $data['list_ids'] ?? [],
            'assigned_tag_ids' => $data['tag_ids'] ?? [],
            'uploaded_by' => $request->user()->id,
        ]);

        return redirect()->route('imports.mapping', $import)->with('success', 'Import uploaded.');
    }

    public function mapping(ContactImport $import): Response
    {
        return Inertia::render('Imports/Mapping', ['import' => $import]);
    }

    public function preview(Request $request, ContactImport $import): RedirectResponse
    {
        $import->update(['mapping' => $request->input('mapping', []), 'status' => 'mapped']);

        return back()->with('success', 'Mapping saved.');
    }

    public function confirm(ContactImport $import): RedirectResponse
    {
        if (! in_array($import->status, ['uploaded', 'mapped'], true)) {
            return back()->withErrors(['import' => 'This import was already confirmed.']);
        }

        $import->update(['status' => 'queued']);
        ProcessImport::dispatch($import->id)->onQueue('imports');

        return redirect()->route('imports.show', $import)->with('success', 'Import queued.');
    }

    public function show(ContactImport $import): Response|JsonResponse
    {
        $payload = ['import' => $import->loadCount('rows'), 'failedRows' => $import->rows()->where('status', 'failed')->limit(50)->get()];

        return request()->wantsJson() ? response()->json($payload) : Inertia::render('Imports/Show', $payload);
    }

    public function downloadFailed(ContactImport $import): StreamedResponse
    {
        return response()->streamDownload(function () use ($import): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['row_number', 'error', 'raw_data']);
            $import->rows()->where('status', 'failed')->cursor()->each(fn ($row) => fputcsv($out, [$row->row_number, $row->error_message, json_encode($row->raw_data)]));
        }, 'failed-import-rows.csv');
    }

    public function destroy(ContactImport $import): RedirectResponse
    {
        $import->delete();

        return back()->with('success', 'Import deleted.');
    }
}
