<?php

namespace Tests\Feature;

use App\Models\ContactImport;
use App\Models\ImportRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_sample_downloads_are_available(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('imports.sample', 'csv'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee('email');

        $this->actingAs(User::factory()->create())
            ->get(route('imports.sample', 'xlsx'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_mapping_page_rebuilds_preview_when_missing(): void
    {
        Storage::put('imports/rebuild.csv', "email,full_name\nstudent@example.com,Student One\n");
        $import = ContactImport::factory()->create([
            'disk_path' => 'imports/rebuild.csv',
            'file_type' => 'csv',
            'mapping' => null,
            'preview_rows' => null,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('imports.mapping', $import))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Imports/Mapping')
                ->where('summary.total_rows', 1)
                ->where('mapping.email', 'email'));

        $this->assertSame(1, $import->refresh()->total_rows);
        Storage::delete('imports/rebuild.csv');
    }

    public function test_mapping_preview_rejects_columns_not_present_in_uploaded_file(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        Storage::put('imports/mapping.csv', "email,full_name\nstudent@example.com,Student One\n");
        $import = ContactImport::factory()->create([
            'disk_path' => 'imports/mapping.csv',
            'file_type' => 'csv',
            'uploaded_by' => $user->id,
            'preview_rows' => [
                'headers' => ['email', 'full_name'],
                'mapping' => ['email' => 'email', 'full_name' => 'full_name'],
                'preview_rows' => [],
                'summary' => ['total_rows' => 1, 'valid_rows' => 1, 'invalid_rows' => 0, 'duplicate_rows' => 0],
            ],
        ]);

        $this->actingAs($user)
            ->post(route('imports.preview', $import), [
                'mapping' => [
                    'email' => 'email',
                    'full_name' => 'not_in_file',
                ],
            ])
            ->assertSessionHasErrors('mapping.full_name');

        Storage::delete('imports/mapping.csv');
    }

    public function test_import_show_supports_json_status_filter_and_failed_download(): void
    {
        $import = ContactImport::factory()->create(['status' => 'completed']);
        ImportRow::factory()->create([
            'contact_import_id' => $import->id,
            'row_number' => 2,
            'status' => 'failed',
            'error_message' => 'Invalid email',
            'raw_data' => ['email' => 'bad'],
        ]);
        ImportRow::factory()->create([
            'contact_import_id' => $import->id,
            'row_number' => 3,
            'status' => 'created',
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('imports.show', [$import, 'status' => 'failed']))
            ->assertOk()
            ->assertJsonPath('rows.meta.total', 1)
            ->assertJsonPath('rows.data.0.status', 'failed');

        $this->actingAs(User::factory()->create())
            ->get(route('imports.download-failed', $import))
            ->assertOk()
            ->assertStreamed()
            ->assertHeader('Content-Disposition', 'attachment; filename=failed-import-rows.csv');
    }

    public function test_import_destroy_deletes_disk_file_and_record(): void
    {
        Storage::put('imports/delete-me.csv', "email\nstudent@example.com\n");
        $import = ContactImport::factory()->create(['disk_path' => 'imports/delete-me.csv']);

        $this->actingAs(User::factory()->create())
            ->delete(route('imports.destroy', $import))
            ->assertRedirect();

        Storage::assertMissing('imports/delete-me.csv');
        $this->assertDatabaseMissing('contact_imports', ['id' => $import->id]);
    }
}
