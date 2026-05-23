<?php

namespace App\Services\Email;

use App\Models\Contact;
use DateTimeInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class EmailPersonalizer
{
    /**
     * @var array<string, string>
     */
    private const DIRECT_FIELDS = [
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'full_name' => 'Full name',
        'email' => 'Email',
        'email_normalized' => 'Normalized email',
        'phone' => 'Phone',
        'company' => 'Company',
        'job_title' => 'Job title',
        'country' => 'Country',
        'district' => 'District',
        'city' => 'City',
        'gender' => 'Gender',
        'date_of_birth' => 'Date of birth',
        'source' => 'Source',
        'status' => 'Contact status',
        'email_verified_status' => 'Email verification status',
        'consent_status' => 'Consent status',
        'consent_source' => 'Consent source',
        'consent_at' => 'Consent date',
        'last_contacted_at' => 'Last contacted date',
        'last_opened_at' => 'Last opened date',
        'last_clicked_at' => 'Last clicked date',
        'unsubscribed_at' => 'Unsubscribed date',
        'bounced_at' => 'Bounced date',
        'complained_at' => 'Complained date',
        'blocked_at' => 'Blocked date',
        'notes' => 'Notes',
        'created_at' => 'Created date',
        'updated_at' => 'Updated date',
    ];

    /**
     * @var array<string, string>
     */
    private const ALIASES = [
        'name' => 'Display name',
        'display_name' => 'Display name',
    ];

    /**
     * @var array<string, string>
     */
    private const SYSTEM_FIELDS = [
        'unsubscribe_url' => 'One-click unsubscribe URL',
    ];

    /**
     * @param  array<string, mixed>  $extraValues
     */
    public function render(string $content, Contact $contact, array $extraValues = []): string
    {
        $values = array_merge($this->valuesFor($contact), $this->normalizeValues($extraValues));

        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_.-]+)\s*}}|\{\s*([a-zA-Z0-9_.-]+)\s*}/',
            fn (array $match): string => $values[$this->variableFromMatch($match)] ?? '',
            $content
        ) ?? $content;
    }

    public function unresolvedVariables(string $content, array $metadataKeys = []): array
    {
        return array_values(array_filter(
            $this->variablesIn($content),
            fn (string $variable): bool => ! $this->isSupportedVariable($variable, $metadataKeys)
        ));
    }

    /**
     * @return array<int, array{key: string, token: string, label: string, group: string, description: string, sample: string}>
     */
    public function variableDefinitions(array $metadataKeys = []): array
    {
        $definitions = [];

        foreach (self::ALIASES as $key => $label) {
            $definitions[] = $this->definition($key, $label, 'Common', 'Best display value for the contact.', 'Sample Contact');
        }

        foreach (self::DIRECT_FIELDS as $key => $label) {
            $definitions[] = $this->definition($key, $label, 'Contact fields', 'Direct value from the contact record.', $this->sampleValueFor($key));
        }

        foreach (self::SYSTEM_FIELDS as $key => $label) {
            $definitions[] = $this->definition($key, $label, 'System', 'Generated when campaign recipients are prepared.', 'https://example.com/unsubscribe/sample');
        }

        $metadataKeys = collect($metadataKeys)
            ->map(fn (string $key): string => trim($key))
            ->filter(fn (string $key): bool => $key !== '')
            ->unique()
            ->sort()
            ->values();

        if ($metadataKeys->isEmpty()) {
            $definitions[] = $this->definition('metadata.key', 'Metadata key', 'Metadata', 'Replace key with any metadata value stored on the contact.', 'Sample metadata value');
        } else {
            $metadataKeys->each(function (string $key) use (&$definitions): void {
                $definitions[] = $this->definition("metadata.{$key}", Str::headline($key), 'Metadata', 'Value imported in contact metadata.', 'Sample '.Str::headline($key));
            });
        }

        return $definitions;
    }

    /**
     * @return array<string>
     */
    public function variablesIn(string $content): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z0-9_.-]+)\s*}}|\{\s*([a-zA-Z0-9_.-]+)\s*}/', $content, $matches, PREG_SET_ORDER);

        return collect($matches)
            ->map(fn (array $match): string => $this->variableFromMatch($match))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function valuesFor(Contact $contact): array
    {
        $values = [];

        foreach (array_keys(self::DIRECT_FIELDS) as $field) {
            $values[$field] = $this->stringValue($contact->getAttribute($field));
        }

        $values['name'] = $contact->display_name;
        $values['display_name'] = $contact->display_name;

        foreach (Arr::dot($contact->metadata ?? []) as $key => $value) {
            $values["metadata.{$key}"] = $this->stringValue($value);
        }

        return $values;
    }

    public function renderSample(string $content, array $metadataKeys = [], array $extraValues = []): string
    {
        return $this->render($content, $this->sampleContact($metadataKeys), array_merge([
            'unsubscribe_url' => 'https://example.com/unsubscribe/sample',
        ], $extraValues));
    }

    /**
     * @return array<string>
     */
    public function metadataKeysFromContacts(int $limit = 1000): array
    {
        return Contact::query()
            ->whereNotNull('metadata')
            ->latest('id')
            ->limit($limit)
            ->get(['metadata'])
            ->flatMap(fn (Contact $contact): array => array_keys(Arr::dot($contact->metadata ?? [])))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function isSupportedVariable(string $variable, array $metadataKeys = []): bool
    {
        return array_key_exists($variable, self::ALIASES)
            || array_key_exists($variable, self::DIRECT_FIELDS)
            || array_key_exists($variable, self::SYSTEM_FIELDS)
            || $this->isSupportedMetadataVariable($variable, $metadataKeys);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, string>
     */
    private function normalizeValues(array $values): array
    {
        return collect($values)
            ->map(fn (mixed $value): string => $this->stringValue($value))
            ->all();
    }

    private function stringValue(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
            is_bool($value) => $value ? 'yes' : 'no',
            is_scalar($value) => (string) $value,
            default => '',
        };
    }

    private function variableFromMatch(array $match): string
    {
        return ($match[1] ?? '') !== '' ? $match[1] : ($match[2] ?? '');
    }

    private function isSupportedMetadataVariable(string $variable, array $metadataKeys): bool
    {
        if (! Str::startsWith($variable, 'metadata.')) {
            return false;
        }

        if ($metadataKeys === []) {
            return true;
        }

        return in_array(Str::after($variable, 'metadata.'), $metadataKeys, true);
    }

    private function sampleContact(array $metadataKeys): Contact
    {
        $metadata = collect($metadataKeys)
            ->mapWithKeys(fn (string $key): array => [$key => 'Sample '.Str::headline($key)])
            ->all();

        if ($metadata === []) {
            $metadata = ['key' => 'Sample metadata value'];
        }

        return new Contact([
            'first_name' => 'Sample',
            'last_name' => 'Contact',
            'full_name' => 'Sample Contact',
            'email' => 'sample@example.com',
            'email_normalized' => 'sample@example.com',
            'phone' => '+94 77 000 0000',
            'company' => 'Codezela',
            'job_title' => 'Student',
            'country' => 'Sri Lanka',
            'district' => 'Colombo',
            'city' => 'Colombo',
            'gender' => 'Not specified',
            'source' => 'Import',
            'status' => 'active',
            'email_verified_status' => 'valid',
            'consent_status' => 'subscribed',
            'consent_source' => 'signup',
            'notes' => 'Sample preview contact',
            'metadata' => $metadata,
        ]);
    }

    private function sampleValueFor(string $key): string
    {
        return match ($key) {
            'first_name' => 'Sample',
            'last_name' => 'Contact',
            'full_name' => 'Sample Contact',
            'email', 'email_normalized' => 'sample@example.com',
            'phone' => '+94 77 000 0000',
            'company' => 'Codezela',
            'job_title' => 'Student',
            'country' => 'Sri Lanka',
            'district', 'city' => 'Colombo',
            'gender' => 'Not specified',
            'date_of_birth' => '2000-01-01',
            'source' => 'Import',
            'status' => 'active',
            'email_verified_status' => 'valid',
            'consent_status' => 'subscribed',
            'consent_source' => 'signup',
            'notes' => 'Sample preview contact',
            default => '2026-05-24 00:00:00',
        };
    }

    /**
     * @return array{key: string, token: string, label: string, group: string, description: string, sample: string}
     */
    private function definition(string $key, string $label, string $group, string $description, string $sample): array
    {
        return [
            'key' => $key,
            'token' => '{{ '.$key.' }}',
            'label' => $label,
            'group' => $group,
            'description' => $description,
            'sample' => $sample,
        ];
    }
}
