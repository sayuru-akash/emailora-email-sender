<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsTableProps;
use App\Http\Requests\TagRequest;
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

    public function create(): Response
    {
        return Inertia::render('Tags/Form', ['tag' => null]);
    }

    public function store(TagRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        $data['created_by'] = $request->user()->id;
        $tag = Tag::create($data);

        return redirect()->route('tags.show', $tag)->with('success', 'Tag saved.');
    }

    public function show(Request $request, Tag $tag): Response
    {
        $contacts = $tag->contacts()->search($request->string('search')->toString())->latest('contacts.created_at')->paginate($this->perPage($request->input('per_page')))->withQueryString();

        return Inertia::render('Tags/Show', ['tag' => $tag, 'contacts' => $this->pagination($contacts), 'filters' => $request->only(['search', 'per_page'])]);
    }

    public function edit(Tag $tag): Response
    {
        return Inertia::render('Tags/Form', ['tag' => $tag]);
    }

    public function update(TagRequest $request, Tag $tag): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        $tag->update($data);

        return redirect()->route('tags.show', $tag)->with('success', 'Tag updated.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();

        return back()->with('success', 'Tag deleted.');
    }
}
