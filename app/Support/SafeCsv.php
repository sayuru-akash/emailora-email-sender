<?php

namespace App\Support;

final class SafeCsv
{
    public static function writeRow($handle, array $row): void
    {
        fputcsv($handle, array_map(self::cell(...), $row), ',', '"', '', "\n");
    }

    private static function cell(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return preg_match('/^\s*[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }
}
