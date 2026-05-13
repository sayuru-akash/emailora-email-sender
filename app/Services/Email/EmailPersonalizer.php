<?php

namespace App\Services\Email;

use App\Models\Contact;

final class EmailPersonalizer
{
    public function render(string $content, Contact $contact): string
    {
        $values = [
            'first_name' => $contact->first_name ?? '',
            'last_name' => $contact->last_name ?? '',
            'full_name' => $contact->display_name,
            'email' => $contact->email,
            'company' => $contact->company ?? '',
            'city' => $contact->city ?? '',
            'country' => $contact->country ?? '',
        ];

        return strtr($content, collect($values)->mapWithKeys(fn ($value, $key) => ['{'.$key.'}' => $value])->all());
    }

    public function unresolvedVariables(string $content): array
    {
        preg_match_all('/\{[a-zA-Z0-9_.-]+}/', $content, $matches);

        return array_values(array_unique($matches[0] ?? []));
    }
}
