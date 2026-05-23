<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsTableProps;
use App\Http\Requests\EmailTemplateRequest;
use App\Models\EmailTemplate;
use App\Services\Email\EmailSanitizer;
use App\Services\Email\HtmlToText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
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

    public function preview(EmailTemplate $template): HttpResponse
    {
        return response($this->previewDocument((string) $template->html_body), 200, [
            'Content-Security-Policy' => "default-src 'none'; img-src * data: blob:; style-src 'unsafe-inline' https: http:; font-src https: http: data:; media-src * data: blob:; object-src 'none'; script-src 'none'; connect-src 'none'; form-action 'none'; frame-ancestors 'self'; base-uri 'self';",
            'Content-Type' => 'text/html; charset=UTF-8',
            'Referrer-Policy' => 'no-referrer',
            'X-Content-Type-Options' => 'nosniff',
        ]);
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

    private function previewDocument(string $html): string
    {
        if (stripos($html, '<base ') !== false) {
            return $html;
        }

        if (preg_match('/<head\b/i', $html) === 1) {
            return preg_replace('/<head([^>]*)>/i', '<head$1><base target="_blank">', $html, 1) ?: $html;
        }

        if (preg_match('/<html\b/i', $html) === 1) {
            return preg_replace('/<html([^>]*)>/i', '<html$1><head><meta charset="utf-8"><base target="_blank"></head>', $html, 1) ?: $html;
        }

        return '<!doctype html><html><head><meta charset="utf-8"><base target="_blank"></head><body>'.$html.'</body></html>';
    }
}
