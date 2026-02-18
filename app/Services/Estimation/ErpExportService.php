<?php

namespace App\Services\Estimation;

use Carbon\Carbon;

class ErpExportService
{
    /** @var array<string, int> Category key → ERP code mapping */
    private const ERP_CODES = [
        'A' => 100100,
        'B' => 100200,
        'C' => 100300,
        'D' => 100400,
        'F' => 200100,
        'G' => 200200,
        'H' => 200300,
        'I' => 200400,
        'J' => 200500,
        'M' => 300100,
        'O' => 400100,
        'Q' => 500100,
        'T' => 600100,
    ];

    /**
     * Generate ERP-formatted CSV content following the VBA ExportToERP format.
     *
     * Format:
     * - Line type 1 (header): 1,FY,JobBldg,ContractDate,Job,ContractValue
     * - Line type 2 (items): 2,FY,ERPCode,Qty,Rate,MatCost,ProdCost,OHCost,MQty
     *
     * @param  array<string, mixed>  $fcpbsData  FCPBS results data with categories
     * @param  array<string, mixed>  $erpInput  Contains job_number, building_number, contract_date, fiscal_year
     * @param  float  $contractValue  Total contract value
     * @return string Raw CSV content
     */
    public function generate(array $fcpbsData, array $erpInput, float $contractValue): string
    {
        $lines = [];
        $fy = (int) $erpInput['fiscal_year'];
        $jobBldg = str_pad(substr($erpInput['job_number'].$erpInput['building_number'], 0, 10), 10);
        $contractDate = Carbon::parse($erpInput['contract_date'])->format('d-m-Y');
        $job = str_pad(substr((string) $erpInput['job_number'], 0, 9), 9);
        $cvFormatted = str_pad(number_format($contractValue, 2, '.', ''), 15);

        // Line type 1 (header)
        $lines[] = implode(',', ['1', $fy, $jobBldg, $contractDate, $job, $cvFormatted]);

        // Line type 2 (items) — only categories with ERP code > 0 AND selling price > 0
        $categories = $fcpbsData['categories'] ?? [];
        foreach ($categories as $catKey => $cat) {
            $erpCode = self::ERP_CODES[$catKey] ?? 0;
            $sellingPrice = (float) ($cat['selling_price'] ?? 0);

            if ($erpCode <= 0 || $sellingPrice <= 0) {
                continue;
            }

            $qty = (float) ($cat['weight_kg'] ?? 0);
            $matCost = (float) ($cat['material_cost'] ?? 0);
            $prodCost = (float) ($cat['manufacturing_cost'] ?? 0);
            $ohCost = (float) ($cat['overhead_cost'] ?? 0);
            $priceMt = (float) ($cat['price_per_mt'] ?? 0);

            if ($qty > 0) {
                // Convert kg to MT and per-unit costs
                $sQty = $qty / 1000;
                $matUnit = $matCost * 1000 / $qty;
                $prodUnit = $prodCost * 1000 / $qty;
                $ohUnit = $ohCost * 1000 / $qty;
            } else {
                // Lump-sum items: qty forced to 1, costs used directly
                $sQty = 1;
                $matUnit = $matCost;
                $prodUnit = $prodCost;
                $ohUnit = $ohCost;
            }

            $lines[] = implode(',', [
                '2',
                $fy,
                str_pad((string) $erpCode, 6, '0', STR_PAD_LEFT),
                str_pad(number_format($sQty, 4, '.', ''), 15),
                str_pad(number_format($priceMt, 2, '.', ''), 15),
                str_pad(number_format($matUnit, 2, '.', ''), 15),
                str_pad(number_format($prodUnit, 2, '.', ''), 15),
                str_pad(number_format($ohUnit, 2, '.', ''), 15),
                str_pad(number_format($sQty, 4, '.', ''), 15),
            ]);
        }

        return implode("\r\n", $lines);
    }

    /**
     * Get the ERP code for a given FCPBS category key.
     */
    public static function getErpCode(string $categoryKey): int
    {
        return self::ERP_CODES[$categoryKey] ?? 0;
    }
}
