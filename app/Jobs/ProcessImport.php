<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\ContactImport;
use App\Services\Activity\ActivityLogger;
use App\Services\Imports\ContactImportFile;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessImport implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public int $uniqueFor = 900;

    public function __construct(public int $importId) {}

    public function uniqueId(): string
    {
        return (string) $this->importId;
    }

    public function handle(ContactImportFile $files, ActivityLogger $activity): void
    {
        $import = ContactImport::findOrFail($this->importId);
        if ($import->status === 'completed') {
            return;
        }

        $import->rows()->delete();
        $import->update([
            'status' => 'processing',
            'started_at' => now(),
            'completed_at' => null,
            'total_rows' => 0,
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'duplicate_rows' => 0,
            'failure_message' => null,
        ]);

        $activity->log('import.started', 'Contact import processing started.', $import, ['file_name' => $import->file_name], 'imports');

        try {
            $parsed = $files->parse(Storage::path($import->disk_path), (string) $import->file_type);
            $mapping = $import->mapping ?: $files->analyze($import)['mapping'];
            $stats = ['total_rows' => 0, 'processed_rows' => 0, 'successful_rows' => 0, 'failed_rows' => 0, 'duplicate_rows' => 0];

            foreach ($parsed['rows'] as $index => $raw) {
                $rowNumber = $index + 2;
                $stats['total_rows']++;
                $stats['processed_rows']++;
                $validation = $files->validateRow($raw, $mapping, $rowNumber);

                if ($validation['status'] !== 'valid') {
                    $stats['failed_rows']++;
                    $this->recordRow($import, $validation, 'failed', implode(' ', $validation['errors']));

                    continue;
                }

                $existing = Contact::query()->where('email_normalized', $validation['email_normalized'])->first();
                if ($existing && $import->duplicate_handling === 'skip') {
                    $stats['duplicate_rows']++;
                    $this->recordRow($import, $validation, 'duplicate', 'Duplicate contact skipped.');

                    continue;
                }

                if (! $existing && $import->duplicate_handling === 'update') {
                    $stats['failed_rows']++;
                    $this->recordRow($import, $validation, 'failed', 'No existing contact found for update-only import.');

                    continue;
                }

                $contact = $this->persistContact($validation['mapped_data'], $existing, $import);
                $contact->lists()->syncWithoutDetaching($import->assigned_list_ids ?? []);
                $contact->tags()->syncWithoutDetaching($import->assigned_tag_ids ?? []);
                $stats['successful_rows']++;
                $this->recordRow($import, $validation, $existing ? 'updated' : 'created', null, $contact->id);

                if ($stats['processed_rows'] % 50 === 0) {
                    $import->update($stats);
                }
            }

            $import->update([
                ...$stats,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $activity->log('import.completed', 'Contact import processing completed.', $import, $stats, 'imports');
        } catch (Throwable $exception) {
            $import->update([
                'status' => 'failed',
                'completed_at' => now(),
                'failure_message' => 'Import processing failed. Check the file format and try again.',
            ]);

            $activity->log('import.failed', 'Contact import processing failed.', $import, [
                'file_name' => $import->file_name,
                'exception' => $exception->getMessage(),
            ], 'imports', 'error');

            report($exception);

            throw $exception;
        }
    }

    private function persistContact(array $data, ?Contact $existing, ContactImport $import): Contact
    {
        $payload = [
            'email' => Contact::normalizeEmail($data['email'] ?? null),
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'full_name' => $data['full_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'country' => $data['country'] ?? null,
            'district' => $data['district'] ?? null,
            'city' => $data['city'] ?? null,
            'source' => ($data['source'] ?? null) ?: 'import',
            'consent_status' => in_array($data['consent_status'] ?? null, ['unknown', 'opted_in', 'opted_out'], true) ? $data['consent_status'] : 'unknown',
            'notes' => $data['notes'] ?? null,
            'status' => 'active',
            'metadata' => $data['metadata'] ?? null,
        ];

        if ($existing && $import->duplicate_handling === 'add_to_list_tag') {
            return $existing;
        }

        if ($existing) {
            $existing->update(array_filter($payload, fn ($value) => $value !== null));

            return $existing;
        }

        return Contact::create($payload);
    }

    private function recordRow(ContactImport $import, array $validation, string $status, ?string $error, ?int $contactId = null): void
    {
        $import->rows()->updateOrCreate(
            ['row_number' => $validation['row_number']],
            [
                'status' => $status,
                'raw_data' => $validation['raw_data'],
                'mapped_data' => $validation['mapped_data'],
                'email_normalized' => $validation['email_normalized'],
                'error_message' => $error,
                'contact_id' => $contactId,
            ]
        );
    }
}
