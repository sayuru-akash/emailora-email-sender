<?php

namespace App\Services\Email;

final class EmailSanitizer
{
    public function sanitize(?string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', (string) $html) ?? '';
        $html = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html) ?? '';

        return trim($html);
    }
}
