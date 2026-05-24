<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsTableProps;
use App\Models\SavedSegment;
use App\Services\Email\AudienceResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SavedSegmentController extends Controller
{
    use BuildsTableProps;

    public function index(Request $request): Response
    {
        $segments = SavedSegment::latest()->paginate($this->perPage($request->input('per_page')))->withQueryString();

        return Inertia::render('Segments/Index', [
            'segments' => $this->pagination($segments),
            'filters' => $request->only(['per_page']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Segments/Form', [
            'segment' => null,
            'defaultFilters' => [
                'status' => 'active',
                'source' => null,
                'tags' => [],
                'lists' => [],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:180'], 'description' => ['nullable', 'string'], 'filters' => ['required', 'array'], 'status' => ['required', 'in:active,inactive']]);
        $data['slug'] = Str::slug($data['name']);
        $data['created_by'] = $request->user()->id;
        $segment = SavedSegment::create($data);

        return redirect()->route('segments.show', $segment)->with('success', 'Segment saved.');
    }

    public function show(SavedSegment $segment): Response
    {
        return Inertia::render('Segments/Show', ['segment' => $segment]);
    }

    public function edit(SavedSegment $segment): Response
    {
        return Inertia::render('Segments/Form', [
            'segment' => $segment,
            'defaultFilters' => $segment->filters,
        ]);
    }

    public function update(Request $request, SavedSegment $segment): RedirectResponse
    {
        $segment->update($request->validate(['name' => ['required', 'string', 'max:180'], 'description' => ['nullable', 'string'], 'filters' => ['required', 'array'], 'status' => ['required', 'in:active,inactive']]));

        return back()->with('success', 'Segment updated.');
    }

    public function destroy(SavedSegment $segment): RedirectResponse
    {
        $segment->delete();

        return back()->with('success', 'Segment deleted.');
    }

    public function preview(SavedSegment $segment, AudienceResolver $resolver): JsonResponse
    {
        $query = $resolver->queryForFilters($segment->filters ?? []);

        return response()->json([
            'count' => (clone $query)->count(),
            'contacts' => $query->latest()->limit(20)->get(['id', 'full_name', 'first_name', 'last_name', 'email', 'company', 'status'])->map(fn ($contact): array => [
                'id' => $contact->id,
                'name' => $contact->display_name,
                'email' => $contact->email,
                'company' => $contact->company,
                'status' => $contact->status,
            ]),
        ]);
    }
}
