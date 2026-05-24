<?php

namespace Tests\Unit;

use App\Models\Contact;
use App\Models\ContactImport;
use App\Services\Imports\ContactImportFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class ContactImportFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_sample_csv_and_xlsx_round_trip_through_parser(): void
    {
        $files = app(ContactImportFile::class);
        Storage::fake('local');
        Storage::put('imports/sample.csv', $files->sampleCsv());
        Storage::put('imports/sample.xlsx', $files->sampleXlsx());

        $csv = $files->parse(Storage::path('imports/sample.csv'), 'csv');
        $xlsx = $files->parse(Storage::path('imports/sample.xlsx'), 'xlsx');

        $this->assertSame(ContactImportFile::SAMPLE_HEADERS, $csv['headers']);
        $this->assertSame(ContactImportFile::SAMPLE_HEADERS, $xlsx['headers']);
        $this->assertCount(2, $csv['rows']);
        $this->assertCount(2, $xlsx['rows']);
    }

    public function test_xlsx_parser_preserves_blank_cells_by_column_reference(): void
    {
        $files = app(ContactImportFile::class);
        Storage::fake('local');
        Storage::put('imports/blank-cell.xlsx', $this->xlsxWithSheet(
            '<row r="1"><c r="A1" t="inlineStr"><is><t>email</t></is></c><c r="B1" t="inlineStr"><is><t>first_name</t></is></c><c r="C1" t="inlineStr"><is><t>company</t></is></c></row>'.
            '<row r="2"><c r="A2" t="inlineStr"><is><t>blank@example.com</t></is></c><c r="C2" t="inlineStr"><is><t>Codezela</t></is></c></row>'
        ));

        $parsed = $files->parse(Storage::path('imports/blank-cell.xlsx'), 'xlsx');

        $this->assertSame('blank@example.com', $parsed['rows'][0]['email']);
        $this->assertNull($parsed['rows'][0]['first_name']);
        $this->assertSame('Codezela', $parsed['rows'][0]['company']);
    }

    public function test_xlsx_parser_rejects_xml_doctype_declarations(): void
    {
        $files = app(ContactImportFile::class);
        Storage::fake('local');
        Storage::put('imports/entity.xlsx', $this->xlsxWithSheet(
            '<!DOCTYPE worksheet [<!ENTITY xxe "blocked">]>'.
            '<row r="1"><c r="A1" t="inlineStr"><is><t>email</t></is></c></row>'
        ));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('unsupported XML declarations');

        $files->parse(Storage::path('imports/entity.xlsx'), 'xlsx');
    }

    public function test_analysis_reports_valid_invalid_duplicate_rows_and_metadata(): void
    {
        Storage::fake('local');
        Contact::factory()->create(['email' => 'duplicate@example.com', 'email_normalized' => 'duplicate@example.com']);
        Storage::put('imports/contacts.csv', "email,Name,programme\nnew@example.com,New Student,CCA\nbad-email,Bad Student,CCB\nduplicate@example.com,Existing Student,CCA\n");
        $import = ContactImport::factory()->create([
            'disk_path' => 'imports/contacts.csv',
            'file_type' => 'csv',
        ]);

        $analysis = app(ContactImportFile::class)->analyze($import, [
            'email' => 'email',
            'full_name' => 'Name',
        ]);

        $this->assertSame(3, $analysis['summary']['total_rows']);
        $this->assertSame(2, $analysis['summary']['valid_rows']);
        $this->assertSame(1, $analysis['summary']['invalid_rows']);
        $this->assertSame(1, $analysis['summary']['duplicate_rows']);
        $this->assertSame(['programme' => 'CCA'], $analysis['preview_rows'][0]['mapped_data']['metadata']);
    }

    private function xlsxWithSheet(string $sheetRows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'emailora-test-xlsx-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Contacts" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$sheetRows.'</sheetData></worksheet>');
        $zip->close();

        $content = file_get_contents($path) ?: '';
        @unlink($path);

        return $content;
    }
}
