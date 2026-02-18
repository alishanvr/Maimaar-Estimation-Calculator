<?php

namespace App\Services\Estimation;

use Illuminate\Http\UploadedFile;

class CsvImportService
{
    /** @var array<int, string> Expected CSV column headers */
    private const EXPECTED_COLUMNS = [
        'description',
        'code',
        'sales_code',
        'cost_code',
        'size',
        'qty',
        'unit',
        'weight_per_unit',
        'rate',
    ];

    /**
     * Parse a CSV file into detail item format.
     *
     * @return array{items: array<int, array<string, mixed>>, errors: array<int, string>, row_count: int}
     */
    public function parseFile(UploadedFile $file): array
    {
        $items = [];
        $errors = [];
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return [
                'items' => [],
                'errors' => ['Unable to open the uploaded file.'],
                'row_count' => 0,
            ];
        }

        $headers = fgetcsv($handle, 0, ',', '"', '');
        if ($headers === false || empty($headers)) {
            fclose($handle);

            return [
                'items' => [],
                'errors' => ['CSV file is empty or has no headers.'],
                'row_count' => 0,
            ];
        }

        // Normalize headers (trim whitespace, lowercase)
        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headers);

        // Validate required columns
        $missing = array_diff(self::EXPECTED_COLUMNS, $headers);
        if (! empty($missing)) {
            fclose($handle);

            return [
                'items' => [],
                'errors' => ['Missing required columns: '.implode(', ', $missing)],
                'row_count' => 0,
            ];
        }

        $rowNum = 1;
        $totalDataRows = 0;
        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $rowNum++;
            $totalDataRows++;

            // Skip empty rows
            if (count($row) < count($headers)) {
                $errors[] = "Row {$rowNum}: insufficient columns, skipped.";

                continue;
            }

            $mapped = array_combine($headers, array_slice($row, 0, count($headers)));

            // Validate numeric fields
            $rowErrors = [];
            if (($mapped['size'] ?? '') !== '' && ! is_numeric($mapped['size'])) {
                $rowErrors[] = "Row {$rowNum}: 'size' must be numeric";
            }
            if (($mapped['qty'] ?? '') !== '' && ! is_numeric($mapped['qty'])) {
                $rowErrors[] = "Row {$rowNum}: 'qty' must be numeric";
            }

            if (! empty($rowErrors)) {
                $errors = array_merge($errors, $rowErrors);

                continue;
            }

            $items[] = [
                'description' => $mapped['description'] ?? '',
                'code' => $mapped['code'] ?? '',
                'sales_code' => (int) ($mapped['sales_code'] ?? 1),
                'cost_code' => $mapped['cost_code'] ?? '',
                'size' => (float) ($mapped['size'] ?? 0),
                'qty' => (float) ($mapped['qty'] ?? 0),
                'unit' => $mapped['unit'] ?? '',
                'weight_per_unit' => (float) ($mapped['weight_per_unit'] ?? 0),
                'rate' => (float) ($mapped['rate'] ?? 0),
                'is_header' => false,
                'sort_order' => 0,
            ];
        }

        fclose($handle);

        return [
            'items' => $items,
            'errors' => $errors,
            'row_count' => $totalDataRows,
            'valid_count' => count($items),
        ];
    }
}
