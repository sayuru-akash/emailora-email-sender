<?php

namespace Tests\Feature;

use App\Jobs\ProcessImport;
use App\Models\ActivityLog;
use App\Models\ContactImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContactImportFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_download_sample_csv_and_xlsx(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('imports.sample', 'csv'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($user)
            ->get(route('imports.sample', 'xlsx'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_upload_creates_import_preview_and_activity_log(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('contacts.csv', "email,full_name\nstudent@example.com,Student One\nbad-email,Bad\n");

        $this->actingAs($user)
            ->post(route('imports.upload'), [
                'file' => $file,
                'duplicate_handling' => 'skip',
                'list_ids' => [],
                'tag_ids' => [],
            ])
            ->assertRedirect();

        $import = ContactImport::query()->firstOrFail();
        $this->assertSame(2, $import->total_rows);
        $this->assertSame('email', $import->mapping['email']);
        $this->assertSame(1, $import->preview_rows['summary']['invalid_rows']);
        $this->assertDatabaseHas(ActivityLog::class, [
            'event' => 'import.uploaded',
            'subject_id' => $import->id,
        ]);
    }

    public function test_mapping_preview_requires_email_column(): void
    {
        Storage::fake('local');
        Storage::put('imports/contacts.csv', "email,full_name\nstudent@example.com,Student One\n");
        $import = ContactImport::factory()->create([
            'disk_path' => 'imports/contacts.csv',
            'file_type' => 'csv',
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('imports.preview', $import), [
                'mapping' => ['full_name' => 'full_name'],
            ])
            ->assertSessionHasErrors('mapping.email');
    }

    public function test_confirm_queues_import_once_and_blocks_reconfirm(): void
    {
        Queue::fake();
        Storage::fake('local');
        Storage::put('imports/contacts.csv', "email\nstudent@example.com\n");
        $import = ContactImport::factory()->create([
            'disk_path' => 'imports/contacts.csv',
            'file_type' => 'csv',
            'status' => 'mapped',
            'total_rows' => 1,
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('imports.confirm', $import))
            ->assertRedirect(route('imports.show', $import));

        $this->actingAs(User::factory()->create())
            ->post(route('imports.confirm', $import))
            ->assertSessionHasErrors('import');

        Queue::assertPushed(ProcessImport::class, 1);
        $this->assertSame('queued', $import->refresh()->status);
    }
}
