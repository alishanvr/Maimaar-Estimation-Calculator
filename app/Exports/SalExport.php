<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @param  array<string, mixed>  $salData
     */
    public function __construct(private readonly array $salData) {}

    public function headings(): array
    {
        return [
            'Code',
            'Description',
            'Weight (kg)',
            'Cost',
            'Markup',
            'Price',
            'Price/MT',
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        $rows = [];
        $lines = $this->salData['lines'] ?? [];

        foreach ($lines as $line) {
            $rows[] = [
                $line['code'] ?? '',
                $line['description'] ?? '',
                $line['weight_kg'] ?? 0,
                $line['cost'] ?? 0,
                $line['markup'] ?? 0,
                $line['price'] ?? 0,
                $line['price_per_mt'] ?? 0,
            ];
        }

        // Totals row
        $rows[] = [
            '',
            'TOTAL',
            $this->salData['total_weight_kg'] ?? 0,
            $this->salData['total_cost'] ?? 0,
            $this->salData['markup_ratio'] ?? '',
            $this->salData['total_price'] ?? 0,
            $this->salData['price_per_mt'] ?? 0,
        ];

        return $rows;
    }
}
