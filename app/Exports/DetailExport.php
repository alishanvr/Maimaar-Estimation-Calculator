<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DetailExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @param  array<int, array<string, mixed>>  $detailData
     */
    public function __construct(private readonly array $detailData) {}

    public function headings(): array
    {
        return [
            'Description',
            'Code',
            'Sales Code',
            'Cost Code',
            'Size',
            'Qty',
            'Unit',
            'Weight/Unit',
            'Rate',
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        $rows = [];
        foreach ($this->detailData as $item) {
            $isHeader = (bool) ($item['is_header'] ?? false);

            $rows[] = [
                $item['description'] ?? '',
                $isHeader ? '' : ($item['code'] ?? ''),
                $isHeader ? '' : ($item['sales_code'] ?? ''),
                $isHeader ? '' : ($item['cost_code'] ?? ''),
                $isHeader ? '' : ($item['size'] ?? ''),
                $isHeader ? '' : ($item['qty'] ?? ''),
                $isHeader ? '' : ($item['unit'] ?? ''),
                $isHeader ? '' : ($item['weight_per_unit'] ?? ''),
                $isHeader ? '' : ($item['rate'] ?? ''),
            ];
        }

        return $rows;
    }
}
