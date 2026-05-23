<?php

namespace App\Services\Email;

use Illuminate\Http\Response;

final class EmailPreviewDocument
{
    public function response(string $html): Response
    {
        return response($this->render($html), 200, [
            'Content-Security-Policy' => "default-src 'none'; img-src * data: blob:; style-src 'unsafe-inline' https: http:; font-src https: http: data:; media-src * data: blob:; object-src 'none'; script-src 'none'; connect-src 'none'; form-action 'none'; frame-ancestors 'self'; base-uri 'self';",
            'Content-Type' => 'text/html; charset=UTF-8',
            'Referrer-Policy' => 'no-referrer',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function render(string $html): string
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
