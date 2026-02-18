<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RecapExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @param  array<string, mixed>  $summaryData
     */
    public function __construct(
        private readonly array $summaryData,
        private readonly string $currencyCode = 'AED'
    ) {}

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        $currency = $this->currencyCode;
        $labels = [
            'total_weight_kg' => 'Total Weight (kg)',
            'total_weight_mt' => 'Total Weight (MT)',
            'total_price_aed' => "Total Price ({$currency})",
            'price_per_mt' => "Price per MT ({$currency})",
            'fob_price_aed' => "FOB Price ({$currency})",
            'steel_weight_kg' => 'Steel Weight (kg)',
            'panels_weight_kg' => 'Panels Weight (kg)',
        ];

        $rows = [];
        foreach ($labels as $key => $label) {
            $rows[] = [$label, $this->summaryData[$key] ?? ''];
        }

        return $rows;
    }
}
