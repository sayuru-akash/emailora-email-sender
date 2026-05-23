<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsTableProps;
use App\Models\SavedSegment;
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

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:180'], 'description' => ['nullable', 'string'], 'filters' => ['required', 'array'], 'status' => ['required', 'in:active,inactive']]);
        $data['slug'] = Str::slug($data['name']);
        $data['created_by'] = $request->user()->id;
        SavedSegment::create($data);

        return back()->with('success', 'Segment saved.');
    }

    public function show(SavedSegment $segment): Response
    {
        return Inertia::render('Segments/Show', ['segment' => $segment]);
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

    public function preview(SavedSegment $segment): JsonResponse
    {
        return response()->json(['count' => 0, 'contacts' => []]);
    }
}
