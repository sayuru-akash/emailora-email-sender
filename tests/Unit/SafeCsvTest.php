<?php

namespace Tests\Unit;

use App\Support\SafeCsv;
use PHPUnit\Framework\TestCase;

class SafeCsvTest extends TestCase
{
    public function test_it_neutralizes_spreadsheet_formula_cells(): void
    {
        $handle = fopen('php://temp', 'w+');

        SafeCsv::writeRow($handle, [
            '=HYPERLINK("https://example.test")',
            '+SUM(1,1)',
            '-10+20',
            '@cmd',
            "\t=hidden",
            "\r=hidden",
            'normal value',
            123,
        ]);

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $this->assertStringContainsString('"\'=HYPERLINK(""https://example.test"")"', $csv);
        $this->assertStringContainsString("'+SUM(1,1)", $csv);
        $this->assertStringContainsString("'-10+20", $csv);
        $this->assertStringContainsString("'@cmd", $csv);
        $this->assertStringContainsString("'\t=hidden", $csv);
        $this->assertStringContainsString("'\r=hidden", $csv);
        $this->assertStringContainsString('normal value', $csv);
        $this->assertStringContainsString('123', $csv);
    }
}
