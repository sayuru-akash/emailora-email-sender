<?php

namespace App\Services\Email;

use Illuminate\Http\Response;

final class EmailPreviewDocument
{
    public function response(string $html, ?string $preheader = null): Response
    {
        return response($this->render($html, $preheader), 200, [
            'Content-Security-Policy' => "default-src 'none'; img-src * data: blob:; style-src 'unsafe-inline' https: http:; font-src https: http: data:; media-src * data: blob:; object-src 'none'; script-src 'none'; connect-src 'none'; form-action 'none'; frame-ancestors 'self'; base-uri 'self';",
            'Content-Type' => 'text/html; charset=UTF-8',
            'Referrer-Policy' => 'no-referrer',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function render(string $html, ?string $preheader = null): string
    {
        $html = $this->withPreheader($html, $preheader);

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

    public function withPreheader(string $html, ?string $preheader): string
    {
        $preheader = trim((string) $preheader);

        if ($preheader === '') {
            return $html;
        }

        $node = '<div style="display:none!important;max-height:0;max-width:0;opacity:0;overflow:hidden;color:transparent;line-height:1px;mso-hide:all;">'.e($preheader).'</div>';

        if (preg_match('/<body\b[^>]*>/i', $html) === 1) {
            return preg_replace('/(<body\b[^>]*>)/i', '$1'.$node, $html, 1) ?: $node.$html;
        }

        return $node.$html;
    }
}
