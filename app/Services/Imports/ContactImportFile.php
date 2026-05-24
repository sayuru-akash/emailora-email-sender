<?php

namespace App\Services\Imports;

use App\Models\Contact;
use App\Models\ContactImport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class ContactImportFile
{
    private const MAX_IMPORT_ROWS = 20000;

    private const MAX_IMPORT_COLUMNS = 100;

    private const MAX_XLSX_XML_BYTES = 25000000;

    public const SAMPLE_HEADERS = [
        'email',
        'full_name',
        'first_name',
        'last_name',
        'phone',
        'company',
        'job_title',
        'country',
        'district',
        'city',
        'source',
        'consent_status',
        'notes',
        'student_id',
        'programme',
    ];

    public const CONTACT_FIELDS = [
        'email' => 'Email',
        'full_name' => 'Full name',
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'phone' => 'Phone',
        'company' => 'Company',
        'job_title' => 'Job title',
        'country' => 'Country',
        'district' => 'District',
        'city' => 'City',
        'source' => 'Source',
        'consent_status' => 'Consent status',
        'notes' => 'Notes',
    ];

    private const SAMPLE_ROWS = [
        [
            'student.one@example.com',
            'Student One',
            'Student',
            'One',
            '+94710000001',
            'Codezela',
            'Student',
            'Sri Lanka',
            'Colombo',
            'Colombo',
            'sample',
            'opted_in',
            'Ready to import',
            'CCA-001',
            'CCA',
        ],
        [
            'student.two@example.com',
            'Student Two',
            'Student',
            'Two',
            '+94710000002',
            'Codezela',
            'Student',
            'Sri Lanka',
            'Gampaha',
            'Gampaha',
            'sample',
            'unknown',
            'Optional fields can be blank',
            'CCB-002',
            'CCB',
        ],
    ];

    public function analyze(ContactImport $import, ?array $mapping = null): array
    {
        $parsed = $this->parse(Storage::path($import->disk_path), (string) $import->file_type);
        $mapping ??= $this->inferMapping($parsed['headers']);
        $previewRows = [];
        $summary = [
            'total_rows' => count($parsed['rows']),
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'duplicate_rows' => 0,
            'warnings' => [],
        ];

        foreach ($parsed['rows'] as $index => $raw) {
            $validation = $this->validateRow($raw, $mapping, $index + 2);
            $summary[$validation['status'] === 'valid' ? 'valid_rows' : 'invalid_rows']++;

            if ($validation['is_duplicate']) {
                $summary['duplicate_rows']++;
            }

            if (count($previewRows) < 25) {
                $previewRows[] = $validation;
            }
        }

        if (! isset($mapping['email']) || $mapping['email'] === '') {
            $summary['warnings'][] = 'Choose an email column before confirming the import.';
        }

        return [
            'headers' => $parsed['headers'],
            'mapping' => $mapping,
            'preview_rows' => $previewRows,
            'summary' => $summary,
        ];
    }

    public function parse(string $path, string $type): array
    {
        return match (Str::lower($type)) {
            'csv', 'txt' => $this->parseCsv($path),
            'xlsx' => $this->parseXlsx($path),
            default => throw new RuntimeException('Unsupported import file type.'),
        };
    }

    public function validateRow(array $raw, array $mapping, int $rowNumber): array
    {
        $mapped = $this->mappedData($raw, $mapping);
        $email = Contact::normalizeEmail($mapped['email'] ?? null);
        $errors = [];
        $warnings = [];

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid or missing email.';
        }

        if (($mapped['consent_status'] ?? null) && ! in_array($mapped['consent_status'], ['unknown', 'opted_in', 'opted_out'], true)) {
            $warnings[] = 'Consent status will be set to unknown unless it is unknown, opted_in, or opted_out.';
        }

        $duplicate = $email !== '' && Contact::query()->where('email_normalized', $email)->exists();

        return [
            'row_number' => $rowNumber,
            'status' => $errors === [] ? 'valid' : 'invalid',
            'is_duplicate' => $duplicate,
            'errors' => $errors,
            'warnings' => $warnings,
            'raw_data' => $raw,
            'mapped_data' => $mapped,
            'email_normalized' => $email ?: null,
        ];
    }

    public function mappedData(array $raw, array $mapping): array
    {
        $mapped = [];
        $usedHeaders = [];

        foreach (self::CONTACT_FIELDS as $field => $label) {
            $header = $mapping[$field] ?? null;
            if ($header !== null && $header !== '' && array_key_exists($header, $raw)) {
                $mapped[$field] = trim((string) $raw[$header]);
                $usedHeaders[] = $header;
            }
        }

        $metadata = [];
        foreach ($raw as $header => $value) {
            if (! in_array($header, $usedHeaders, true) && filled($value)) {
                $metadata[Str::snake((string) $header)] = $value;
            }
        }

        if ($metadata !== []) {
            $mapped['metadata'] = $metadata;
        }

        return $mapped;
    }

    public function sampleCsv(): string
    {
        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, self::SAMPLE_HEADERS);
        foreach (self::SAMPLE_ROWS as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);

        return stream_get_contents($handle) ?: '';
    }

    public function sampleXlsx(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'emailora-import-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->relsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml([self::SAMPLE_HEADERS, ...self::SAMPLE_ROWS]));
        $zip->close();

        $contents = file_get_contents($path) ?: '';
        @unlink($path);

        return $contents;
    }

    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new RuntimeException('Could not read import file.');
        }

        $headers = array_map(fn ($value) => trim((string) $value), fgetcsv($handle) ?: []);
        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count($rows) >= self::MAX_IMPORT_ROWS) {
                fclose($handle);

                throw new RuntimeException('Import files may not contain more than '.self::MAX_IMPORT_ROWS.' rows.');
            }

            if (count($row) > self::MAX_IMPORT_COLUMNS) {
                fclose($handle);

                throw new RuntimeException('Import files may not contain more than '.self::MAX_IMPORT_COLUMNS.' columns.');
            }

            $rows[] = $this->combineRow($headers, $row);
        }

        fclose($handle);

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function parseXlsx(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Could not open XLSX file.');
        }

        $sharedStrings = $this->sharedStrings($zip);
        $sheet = $this->safeZipXml($zip, 'xl/worksheets/sheet1.xml');
        $zip->close();

        if (! $sheet) {
            throw new RuntimeException('XLSX file does not contain a first worksheet.');
        }

        $xml = $this->safeXml($sheet, 'XLSX worksheet');
        if (! $xml instanceof SimpleXMLElement) {
            throw new RuntimeException('Could not read XLSX worksheet.');
        }

        $table = [];
        foreach ($xml->sheetData->row as $row) {
            if (count($table) > self::MAX_IMPORT_ROWS) {
                throw new RuntimeException('Import files may not contain more than '.self::MAX_IMPORT_ROWS.' rows.');
            }

            $cells = [];
            foreach ($row->c as $cell) {
                if (count($cells) >= self::MAX_IMPORT_COLUMNS) {
                    throw new RuntimeException('Import files may not contain more than '.self::MAX_IMPORT_COLUMNS.' columns.');
                }

                $reference = (string) $cell['r'];
                $index = $this->columnIndex($reference);
                $type = (string) $cell['t'];
                $value = (string) $cell->v;

                if ($type === 's') {
                    $value = $sharedStrings[(int) $value] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) $cell->is->t;
                }

                $cells[$index] = trim($value);
            }

            if ($cells !== []) {
                ksort($cells);
                $table[] = $cells;
            }
        }

        $headers = array_map(fn ($value) => trim((string) $value), $this->expandIndexedRow($table[0] ?? []));
        $rows = [];
        foreach (array_slice($table, 1) as $row) {
            $rows[] = $this->combineRow($headers, $this->expandIndexedRow($row));
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function expandIndexedRow(array $row): array
    {
        if ($row === []) {
            return [];
        }

        $expanded = [];
        $max = max(array_keys($row));
        if ($max >= self::MAX_IMPORT_COLUMNS) {
            throw new RuntimeException('Import files may not contain more than '.self::MAX_IMPORT_COLUMNS.' columns.');
        }

        for ($index = 0; $index <= $max; $index++) {
            $expanded[] = $row[$index] ?? null;
        }

        return $expanded;
    }

    private function inferMapping(array $headers): array
    {
        $aliases = [
            'email' => ['email', 'email_address', 'mail'],
            'full_name' => ['full_name', 'name', 'student_name'],
            'first_name' => ['first_name', 'firstname', 'given_name'],
            'last_name' => ['last_name', 'lastname', 'surname'],
            'phone' => ['phone', 'mobile', 'telephone', 'whatsapp'],
            'company' => ['company', 'organization', 'school'],
            'job_title' => ['job_title', 'title', 'role'],
            'country' => ['country'],
            'district' => ['district', 'state', 'province'],
            'city' => ['city', 'town'],
            'source' => ['source'],
            'consent_status' => ['consent_status', 'consent'],
            'notes' => ['notes', 'note'],
        ];

        $normalizedHeaders = collect($headers)->mapWithKeys(fn ($header) => [Str::snake(Str::lower((string) $header)) => $header]);

        return collect($aliases)
            ->map(fn ($options) => collect($options)->map(fn ($option) => $normalizedHeaders[$option] ?? null)->filter()->first())
            ->filter()
            ->all();
    }

    private function combineRow(array $headers, array $row): array
    {
        $result = [];
        foreach ($headers as $index => $header) {
            if ($header !== '') {
                $result[$header] = $row[$index] ?? null;
            }
        }

        return $result;
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $this->safeZipXml($zip, 'xl/sharedStrings.xml', false);
        if (! $xml) {
            return [];
        }

        $strings = $this->safeXml($xml, 'XLSX shared strings');
        if (! $strings instanceof SimpleXMLElement) {
            return [];
        }

        return collect($strings->si)->map(fn ($item) => (string) $item->t)->all();
    }

    private function columnIndex(string $reference): int
    {
        $letters = preg_replace('/[^A-Z]/', '', Str::upper($reference)) ?: 'A';
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function safeZipXml(ZipArchive $zip, string $name, bool $required = true): string
    {
        $stat = $zip->statName($name);
        if ($stat === false) {
            if ($required) {
                throw new RuntimeException('XLSX file is missing '.$name.'.');
            }

            return '';
        }

        if (($stat['size'] ?? 0) > self::MAX_XLSX_XML_BYTES) {
            throw new RuntimeException('XLSX XML parts are too large to import safely.');
        }

        $xml = $zip->getFromName($name);
        if ($xml === false) {
            if ($required) {
                throw new RuntimeException('Could not read '.$name.'.');
            }

            return '';
        }

        if (strlen($xml) > self::MAX_XLSX_XML_BYTES) {
            throw new RuntimeException('XLSX XML parts are too large to import safely.');
        }

        return $xml;
    }

    private function safeXml(string $xml, string $label): SimpleXMLElement|false
    {
        if (preg_match('/<!\s*(DOCTYPE|ENTITY)\b/i', $xml) === 1) {
            throw new RuntimeException($label.' contains unsupported XML declarations.');
        }

        return simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    }

    private function sheetXml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach ($rows as $rowIndex => $row) {
            $xml .= '<row r="'.($rowIndex + 1).'">';
            foreach ($row as $columnIndex => $value) {
                $cell = $this->columnLetters($columnIndex).($rowIndex + 1);
                $xml .= '<c r="'.$cell.'" t="inlineStr"><is><t>'.htmlspecialchars((string) $value, ENT_XML1).'</t></is></c>';
            }
            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    private function columnLetters(int $index): string
    {
        $letters = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letters = chr(65 + $mod).$letters;
            $index = intdiv($index - $mod, 26);
        }

        return $letters;
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>';
    }

    private function relsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Contacts" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>';
    }
}
