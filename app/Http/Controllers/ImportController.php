<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsTableProps;
use App\Http\Requests\ContactImportRequest;
use App\Http\Requests\ImportMappingRequest;
use App\Jobs\ProcessImport;
use App\Models\ContactImport;
use App\Models\ListModel;
use App\Models\Tag;
use App\Services\Activity\ActivityLogger;
use App\Services\Imports\ContactImportFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

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
        return Inertia::render('Imports/Create', [
            'lists' => ListModel::active()->orderBy('name')->get(['id', 'name']),
            'tags' => Tag::orderBy('name')->get(['id', 'name']),
            'duplicateOptions' => [
                ['value' => 'skip', 'label' => 'Skip existing contacts', 'description' => 'New contacts are created and matching emails are left unchanged.'],
                ['value' => 'update', 'label' => 'Update existing contacts', 'description' => 'Matching emails are updated from the file and new contacts are created.'],
                ['value' => 'add_to_list_tag', 'label' => 'Only attach lists and tags', 'description' => 'Existing contacts keep their details and only receive the selected audience labels.'],
                ['value' => 'upsert', 'label' => 'Create or update all', 'description' => 'Every valid row is synchronized into the contact database.'],
            ],
        ]);
    }

    public function sample(string $format, ContactImportFile $files): HttpResponse
    {
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);

        if ($format === 'xlsx') {
            return response($files->sampleXlsx(), 200, [
                'Content-Disposition' => 'attachment; filename="emailora-contact-import-sample.xlsx"',
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        return response($files->sampleCsv(), 200, [
            'Content-Disposition' => 'attachment; filename="emailora-contact-import-sample.csv"',
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function upload(ContactImportRequest $request, ContactImportFile $files, ActivityLogger $activity): RedirectResponse
    {
        $data = $request->validated();
        $uploaded = $data['file'];
        $path = $uploaded->store('imports');
        $import = ContactImport::create([
            'file_name' => $uploaded->getClientOriginalName(),
            'disk_path' => $path,
            'file_type' => strtolower($uploaded->getClientOriginalExtension()),
            'duplicate_handling' => $data['duplicate_handling'],
            'assigned_list_ids' => $data['list_ids'] ?? [],
            'assigned_tag_ids' => $data['tag_ids'] ?? [],
            'uploaded_by' => $request->user()->id,
        ]);

        try {
            $analysis = $files->analyze($import);
            $import->update([
                'mapping' => $analysis['mapping'],
                'preview_rows' => $analysis,
                'total_rows' => $analysis['summary']['total_rows'],
            ]);
        } catch (Throwable $exception) {
            Storage::delete($path);
            $import->delete();

            report($exception);

            return back()->withErrors(['file' => 'The file could not be read. Check that it is a valid CSV or XLSX file and try again.'])->withInput();
        }

        $activity->log('import.uploaded', 'Contact import file uploaded and previewed.', $import, [
            'file_name' => $import->file_name,
            'file_type' => $import->file_type,
            'total_rows' => $analysis['summary']['total_rows'],
            'valid_rows' => $analysis['summary']['valid_rows'],
            'invalid_rows' => $analysis['summary']['invalid_rows'],
            'duplicate_rows' => $analysis['summary']['duplicate_rows'],
        ], 'imports');

        return redirect()->route('imports.mapping', $import)->with('success', 'Import uploaded. Review the validation preview before confirming.');
    }

    public function mapping(ContactImport $import, ContactImportFile $files): Response
    {
        $analysis = $import->preview_rows ?: $files->analyze($import, $import->mapping ?: null);
        $analysis = array_replace_recursive([
            'headers' => [],
            'mapping' => [],
            'preview_rows' => [],
            'summary' => [
                'total_rows' => 0,
                'valid_rows' => 0,
                'invalid_rows' => 0,
                'duplicate_rows' => 0,
            ],
        ], $analysis);

        if (! $import->preview_rows) {
            $import->update([
                'mapping' => $analysis['mapping'],
                'preview_rows' => $analysis,
                'total_rows' => $analysis['summary']['total_rows'],
            ]);
        }

        return Inertia::render('Imports/Mapping', [
            'import' => $import->fresh(),
            'headers' => $analysis['headers'],
            'mapping' => $analysis['mapping'],
            'previewRows' => $analysis['preview_rows'],
            'summary' => $analysis['summary'],
            'fieldOptions' => ContactImportFile::CONTACT_FIELDS,
        ]);
    }

    public function preview(ImportMappingRequest $request, ContactImport $import, ContactImportFile $files, ActivityLogger $activity): RedirectResponse
    {
        $mapping = $request->validated('mapping');
        $headers = $import->preview_rows['headers'] ?? $files->analyze($import)['headers'];
        $unknownColumns = collect($mapping)
            ->filter(fn ($column) => filled($column) && ! in_array($column, $headers, true))
            ->keys()
            ->map(fn ($field) => "mapping.{$field}")
            ->all();

        if ($unknownColumns !== []) {
            return back()
                ->withErrors(collect($unknownColumns)->mapWithKeys(fn ($field) => [$field => 'Choose a column from the uploaded file.'])->all())
                ->withInput();
        }

        $analysis = $files->analyze($import, $mapping);
        $import->update([
            'mapping' => $mapping,
            'preview_rows' => $analysis,
            'status' => 'mapped',
            'total_rows' => $analysis['summary']['total_rows'],
        ]);

        $activity->log('import.mapping_updated', 'Contact import mapping and validation preview updated.', $import, [
            'mapping' => $mapping,
            'summary' => $analysis['summary'],
        ], 'imports');

        return back()->with('success', 'Validation preview refreshed.');
    }

    public function confirm(ContactImport $import, ActivityLogger $activity): RedirectResponse
    {
        $updated = ContactImport::query()
            ->whereKey($import->id)
            ->whereIn('status', ['uploaded', 'mapped'])
            ->update(['status' => 'queued']);

        if ($updated !== 1) {
            return back()->withErrors(['import' => 'This import was already confirmed.']);
        }

        $import->refresh();
        $activity->log('import.confirmed', 'Contact import confirmed and queued.', $import, [
            'file_name' => $import->file_name,
            'total_rows' => $import->total_rows,
            'duplicate_handling' => $import->duplicate_handling,
        ], 'imports');
        ProcessImport::dispatch($import->id)->onQueue('imports');

        return redirect()->route('imports.show', $import)->with('success', 'Import queued.');
    }

    public function show(Request $request, ContactImport $import): Response|JsonResponse
    {
        $rows = $import->rows()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('row_number')
            ->paginate($this->perPage($request->input('per_page')))
            ->withQueryString();

        $payload = [
            'import' => $import->loadCount('rows'),
            'rows' => $this->pagination($rows),
            'filters' => $request->only(['status', 'per_page']),
            'statusOptions' => ['created', 'updated', 'duplicate', 'failed'],
        ];

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
        if ($import->disk_path) {
            Storage::delete($import->disk_path);
        }

        $import->delete();

        return back()->with('success', 'Import deleted.');
    }
}
