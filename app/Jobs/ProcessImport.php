<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\ContactImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ProcessImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public int $importId) {}

    public function handle(): void
    {
        $import = ContactImport::findOrFail($this->importId);
        if ($import->status === 'completed') {
            return;
        }

        $import->update(['status' => 'processing', 'started_at' => now()]);
        $handle = fopen(Storage::path($import->disk_path), 'r');
        $header = fgetcsv($handle) ?: [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $raw = array_combine($header, $row) ?: [];
            $email = trim((string) ($raw['email'] ?? $raw['Email'] ?? ''));
            $import->increment('total_rows');

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $import->rows()->create(['row_number' => $rowNumber, 'status' => 'failed', 'raw_data' => $raw, 'error_message' => 'Invalid or missing email.']);
                $import->incrementEach(['processed_rows' => 1, 'failed_rows' => 1]);

                continue;
            }

            Contact::updateOrCreate(
                ['email_normalized' => Contact::normalizeEmail($email)],
                [
                    'email' => $email,
                    'first_name' => $raw['first_name'] ?? $raw['First Name'] ?? null,
                    'last_name' => $raw['last_name'] ?? $raw['Last Name'] ?? null,
                    'full_name' => $raw['full_name'] ?? $raw['Name'] ?? null,
                    'company' => $raw['company'] ?? $raw['Company'] ?? null,
                    'source' => 'import',
                    'status' => 'active',
                ]
            );

            $import->incrementEach(['processed_rows' => 1, 'successful_rows' => 1]);
        }

        fclose($handle);
        $import->update(['status' => 'completed', 'completed_at' => now()]);
    }
}
