<?php

namespace Tests\Feature;

use App\Jobs\ProcessImport;
use App\Models\Contact;
use App\Models\ContactImport;
use App\Models\ListModel;
use App\Models\Tag;
use App\Services\Activity\ActivityLogger;
use App\Services\Imports\ContactImportFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_import_creates_contacts_from_email_only_csv(): void
    {
        $import = $this->importFromCsv("email\nnew@example.com\n");

        $this->process($import);

        $this->assertDatabaseHas(Contact::class, [
            'email_normalized' => 'new@example.com',
            'source' => 'import',
        ]);
        $this->assertSame('completed', $import->refresh()->status);
        $this->assertSame(1, $import->successful_rows);
    }

    public function test_skip_mode_records_duplicate_without_overwriting_contact(): void
    {
        Contact::factory()->create([
            'email' => 'existing@example.com',
            'email_normalized' => 'existing@example.com',
            'full_name' => 'Original Name',
        ]);
        $import = $this->importFromCsv("email,full_name\nexisting@example.com,New Name\n", ['duplicate_handling' => 'skip']);

        $this->process($import);

        $this->assertSame('Original Name', Contact::where('email_normalized', 'existing@example.com')->firstOrFail()->full_name);
        $this->assertSame(1, $import->refresh()->duplicate_rows);
        $this->assertDatabaseHas('import_rows', ['contact_import_id' => $import->id, 'status' => 'duplicate']);
    }

    public function test_update_mode_updates_existing_and_reports_missing_as_failed(): void
    {
        Contact::factory()->create([
            'email' => 'existing@example.com',
            'email_normalized' => 'existing@example.com',
            'full_name' => 'Original Name',
        ]);
        $import = $this->importFromCsv("email,full_name\nexisting@example.com,Updated Name\nmissing@example.com,Missing\n", [
            'duplicate_handling' => 'update',
        ]);

        $this->process($import);

        $this->assertSame('Updated Name', Contact::where('email_normalized', 'existing@example.com')->firstOrFail()->full_name);
        $this->assertDatabaseMissing(Contact::class, ['email_normalized' => 'missing@example.com']);
        $this->assertSame(1, $import->refresh()->failed_rows);
    }

    public function test_attach_only_mode_adds_lists_and_tags_without_overwriting_fields(): void
    {
        $contact = Contact::factory()->create([
            'email' => 'existing@example.com',
            'email_normalized' => 'existing@example.com',
            'full_name' => 'Original Name',
        ]);
        $list = ListModel::factory()->create();
        $tag = Tag::factory()->create();
        $import = $this->importFromCsv("email,full_name\nexisting@example.com,New Name\n", [
            'duplicate_handling' => 'add_to_list_tag',
            'assigned_list_ids' => [$list->id],
            'assigned_tag_ids' => [$tag->id],
        ]);

        $this->process($import);

        $this->assertSame('Original Name', $contact->refresh()->full_name);
        $this->assertTrue($contact->lists()->whereKey($list->id)->exists());
        $this->assertTrue($contact->tags()->whereKey($tag->id)->exists());
    }

    public function test_processing_failure_marks_import_failed(): void
    {
        Storage::fake('local');
        $import = ContactImport::factory()->create([
            'disk_path' => 'imports/missing.csv',
            'file_type' => 'csv',
        ]);

        try {
            $this->process($import);
        } catch (\Throwable) {
            // The job rethrows so the queue can record the failed attempt.
        }

        $this->assertSame('failed', $import->refresh()->status);
        $this->assertNotNull($import->failure_message);
    }

    private function importFromCsv(string $csv, array $overrides = []): ContactImport
    {
        Storage::fake('local');
        Storage::put('imports/contacts.csv', $csv);

        return ContactImport::factory()->create([
            'disk_path' => 'imports/contacts.csv',
            'file_type' => 'csv',
            'mapping' => [
                'email' => 'email',
                'full_name' => 'full_name',
            ],
            ...$overrides,
        ]);
    }

    private function process(ContactImport $import): void
    {
        (new ProcessImport($import->id))->handle(app(ContactImportFile::class), app(ActivityLogger::class));
    }
}
