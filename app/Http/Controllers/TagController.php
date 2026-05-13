<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsTableProps;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    use BuildsTableProps;

    public function index(Request $request): Response
    {
        $tags = Tag::query()->withCount('contacts')->search($request->string('search')->toString())->latest()->paginate($this->perPage($request->input('per_page')))->withQueryString();

        return Inertia::render('Tags/Index', ['tags' => $this->pagination($tags), 'filters' => $request->only(['search', 'per_page'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:180'], 'description' => ['nullable', 'string'], 'color' => ['nullable', 'string', 'max:24']]);
        $data['slug'] = Str::slug($data['name']);
        $data['created_by'] = $request->user()->id;
        Tag::create($data);

        return back()->with('success', 'Tag saved.');
    }

    public function show(Request $request, Tag $tag): Response
    {
        $contacts = $tag->contacts()->search($request->string('search')->toString())->latest('contacts.created_at')->paginate($this->perPage($request->input('per_page')))->withQueryString();

        return Inertia::render('Tags/Show', ['tag' => $tag, 'contacts' => $this->pagination($contacts), 'filters' => $request->only(['search', 'per_page'])]);
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:180'], 'description' => ['nullable', 'string'], 'color' => ['nullable', 'string', 'max:24']]);
        $data['slug'] = $tag->slug ?: Str::slug($data['name']);
        $tag->update($data);

        return back()->with('success', 'Tag updated.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();

        return back()->with('success', 'Tag deleted.');
    }
}
