<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FcpbsExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @param  array<string, mixed>  $fcpbsData
     */
    public function __construct(private readonly array $fcpbsData) {}

    public function headings(): array
    {
        return [
            'Key',
            'Name',
            'Quantity',
            'Weight (kg)',
            'Weight %',
            'Material Cost',
            'Mfg Cost',
            'Overhead Cost',
            'Total Cost',
            'Markup',
            'Selling Price',
            'Selling %',
            'Price/MT',
            'Value Added',
            'VA/MT',
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        $rows = [];
        $categories = $this->fcpbsData['categories'] ?? [];

        foreach ($categories as $cat) {
            $rows[] = [
                $cat['key'] ?? '',
                $cat['name'] ?? '',
                $cat['quantity'] ?? 0,
                $cat['weight_kg'] ?? 0,
                $cat['weight_pct'] ?? 0,
                $cat['material_cost'] ?? 0,
                $cat['manufacturing_cost'] ?? 0,
                $cat['overhead_cost'] ?? 0,
                $cat['total_cost'] ?? 0,
                $cat['markup'] ?? 0,
                $cat['selling_price'] ?? 0,
                $cat['selling_price_pct'] ?? 0,
                $cat['price_per_mt'] ?? 0,
                $cat['value_added'] ?? 0,
                $cat['va_per_mt'] ?? 0,
            ];
        }

        // Steel subtotal
        $steel = $this->fcpbsData['steel_subtotal'] ?? [];
        if (! empty($steel)) {
            $rows[] = [
                '', 'Steel Subtotal', '', $steel['weight_kg'] ?? 0, '',
                $steel['material_cost'] ?? 0, $steel['manufacturing_cost'] ?? 0,
                $steel['overhead_cost'] ?? 0, $steel['total_cost'] ?? 0, '',
                $steel['selling_price'] ?? 0, '', '', $steel['value_added'] ?? 0, '',
            ];
        }

        // Panels subtotal
        $panels = $this->fcpbsData['panels_subtotal'] ?? [];
        if (! empty($panels)) {
            $rows[] = [
                '', 'Panels Subtotal', '', $panels['weight_kg'] ?? 0, '',
                $panels['material_cost'] ?? 0, $panels['manufacturing_cost'] ?? 0,
                $panels['overhead_cost'] ?? 0, $panels['total_cost'] ?? 0, '',
                $panels['selling_price'] ?? 0, '', '', $panels['value_added'] ?? 0, '',
            ];
        }

        return $rows;
    }
}
