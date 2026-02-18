<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RecapExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /** @var array<string, string> */
    private const LABELS = [
        'total_weight_kg' => 'Total Weight (kg)',
        'total_weight_mt' => 'Total Weight (MT)',
        'total_price_aed' => 'Total Price (AED)',
        'price_per_mt' => 'Price per MT (AED)',
        'fob_price_aed' => 'FOB Price (AED)',
        'steel_weight_kg' => 'Steel Weight (kg)',
        'panels_weight_kg' => 'Panels Weight (kg)',
    ];

    /**
     * @param  array<string, mixed>  $summaryData
     */
    public function __construct(private readonly array $summaryData) {}

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        $rows = [];
        foreach (self::LABELS as $key => $label) {
            $rows[] = [$label, $this->summaryData[$key] ?? ''];
        }

        return $rows;
    }
}
