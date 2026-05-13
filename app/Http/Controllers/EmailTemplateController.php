<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsTableProps;
use App\Http\Requests\EmailTemplateRequest;
use App\Models\EmailTemplate;
use App\Services\Email\EmailSanitizer;
use App\Services\Email\HtmlToText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailTemplateController extends Controller
{
    use BuildsTableProps;

    public function index(Request $request): Response
    {
        $templates = EmailTemplate::query()
            ->search($request->string('search')->toString())
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate($this->perPage($request->input('per_page')))
            ->withQueryString();

        return Inertia::render('Templates/Index', ['templates' => $this->pagination($templates), 'filters' => $request->only(['search', 'category', 'status', 'per_page'])]);
    }

    public function create(): Response
    {
        return Inertia::render('Templates/Form', ['template' => null]);
    }

    public function store(EmailTemplateRequest $request, EmailSanitizer $sanitizer, HtmlToText $htmlToText): RedirectResponse
    {
        $data = $request->validated();
        $data['html_body'] = $sanitizer->sanitize($data['html_body'] ?? '');
        $data['text_body'] = $data['text_body'] ?: $htmlToText->convert($data['html_body']);
        $data['created_by'] = $request->user()->id;
        $template = EmailTemplate::create($data);

        return redirect()->route('templates.show', $template)->with('success', 'Template saved.');
    }

    public function show(EmailTemplate $template): Response
    {
        return Inertia::render('Templates/Show', ['template' => $template]);
    }

    public function edit(EmailTemplate $template): Response
    {
        return Inertia::render('Templates/Form', ['template' => $template]);
    }

    public function update(EmailTemplateRequest $request, EmailTemplate $template, EmailSanitizer $sanitizer, HtmlToText $htmlToText): RedirectResponse
    {
        $data = $request->validated();
        $data['html_body'] = $sanitizer->sanitize($data['html_body'] ?? '');
        $data['text_body'] = $data['text_body'] ?: $htmlToText->convert($data['html_body']);
        $template->update($data);

        return back()->with('success', 'Template updated.');
    }

    public function duplicate(EmailTemplate $template): RedirectResponse
    {
        $copy = $template->replicate();
        $copy->name = $template->name.' Copy';
        $copy->save();

        return redirect()->route('templates.edit', $copy)->with('success', 'Template duplicated.');
    }

    public function destroy(EmailTemplate $template): RedirectResponse
    {
        $template->delete();

        return redirect()->route('templates.index')->with('success', 'Template deleted.');
    }
}
