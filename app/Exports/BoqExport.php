<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BoqExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @param  array<string, mixed>  $boqData
     */
    public function __construct(private readonly array $boqData) {}

    public function headings(): array
    {
        return [
            'SL No',
            'Description',
            'Unit',
            'Quantity',
            'Unit Rate',
            'Total Price',
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        $rows = [];
        $items = $this->boqData['items'] ?? [];

        foreach ($items as $item) {
            $rows[] = [
                $item['sl_no'] ?? '',
                $item['description'] ?? '',
                $item['unit'] ?? '',
                $item['quantity'] ?? 0,
                $item['unit_rate'] ?? 0,
                $item['total_price'] ?? 0,
            ];
        }

        // Totals row
        $rows[] = [
            '',
            'TOTAL',
            '',
            '',
            '',
            $this->boqData['total_price'] ?? 0,
        ];

        return $rows;
    }
}
