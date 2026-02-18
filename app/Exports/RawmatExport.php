<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RawmatExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @param  array<string, mixed>  $rawmatData
     */
    public function __construct(private readonly array $rawmatData) {}

    public function headings(): array
    {
        return [
            'No',
            'Code',
            'Cost Code',
            'Description',
            'Unit',
            'Quantity',
            'Unit Weight',
            'Total Weight',
            'Category',
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        $rows = [];
        $items = $this->rawmatData['items'] ?? [];

        foreach ($items as $item) {
            $rows[] = [
                $item['no'] ?? '',
                $item['code'] ?? '',
                $item['cost_code'] ?? '',
                $item['description'] ?? '',
                $item['unit'] ?? '',
                $item['quantity'] ?? 0,
                $item['unit_weight'] ?? 0,
                $item['total_weight'] ?? 0,
                $item['category'] ?? '',
            ];
        }

        return $rows;
    }
}
