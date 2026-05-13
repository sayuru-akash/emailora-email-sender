<?php

namespace App\Services\Email;

use Illuminate\Support\Str;

final class EmailNormalizer
{
    public function normalize(?string $email): string
    {
        return Str::lower(trim((string) $email));
    }
}
