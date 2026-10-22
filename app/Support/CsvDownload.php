<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/** A CSV file download that opens cleanly in Excel (UTF-8 with a byte order mark). */
class CsvDownload
{
    /**
     * @param  list<string>  $headings
     * @param  iterable<array<int, mixed>>  $rows
     */
    public static function make(string $filename, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headings, escape: '');
            foreach ($rows as $row) {
                // A leading = + - @ would make Excel treat the cell as a formula.
                fputcsv($out, array_map(fn ($value) => is_string($value) && preg_match('/^[=+\-@]/', $value) ? "'".$value : $value, $row), escape: '');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
