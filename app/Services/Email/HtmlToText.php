<?php

namespace App\Services\Email;

final class HtmlToText
{
    public function convert(?string $html): string
    {
        $text = preg_replace('/<(br|\/p|\/div|\/li)\b[^>]*>/i', "\n", (string) $html) ?? '';
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5);

        return trim(preg_replace("/[ \t]+/", ' ', preg_replace("/\n{3,}/", "\n\n", $text) ?? '') ?? '');
    }
}
