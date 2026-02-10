<?php

namespace Database\Seeders;

use App\Models\DesignConfiguration;
use App\Models\MbsdbProduct;
use App\Models\RawMaterial;
use App\Models\SsdbProduct;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ReferenceDataSeeder extends Seeder
{
    private string $filePath;

    public function run(): void
    {
        $this->filePath = base_path('documentations/HQ-O-53305-R00 (3).xls');

        if (! file_exists($this->filePath)) {
            $this->command->error('Excel file not found at: '.$this->filePath);

            return;
        }

        $this->command->info('Loading Excel file...');

        $previousErrorReporting = error_reporting();
        error_reporting($previousErrorReporting & ~E_NOTICE & ~E_WARNING);
        $spreadsheet = IOFactory::load($this->filePath);
        error_reporting($previousErrorReporting);

        $this->seedMbsdbProducts($spreadsheet);
        $this->seedSsdbProducts($spreadsheet);
        $this->seedRawMaterials($spreadsheet);
        $this->seedDesignConfigurations($spreadsheet);

        $this->command->info('Reference data seeding complete!');
    }

    /**
     * MBSDB sheet layout (header at row 3):
     * A=DB Code, B=FP Code, C=Design Index, D=Description, E=Unit,
     * F=Weight kg/Unit, G=Price AED/Unit, H=Surface Area, I=Material cost
     * Data starts at row 5.
     */
    private function seedMbsdbProducts(mixed $spreadsheet): void
    {
        $sheet = $spreadsheet->getSheetByName('MBSDB');
        if (! $sheet) {
            $this->command->warn('MBSDB sheet not found, skipping...');

            return;
        }

        $this->command->info('Seeding MBSDB products...');
        $highestRow = $sheet->getHighestRow();
        $count = 0;

        for ($row = 5; $row <= $highestRow; $row++) {
            $code = trim((string) $sheet->getCell("A{$row}")->getValue());
            if (empty($code)) {
                continue;
            }

            $fpCode = trim((string) $sheet->getCell("B{$row}")->getValue());
            $designIndex = trim((string) $sheet->getCell("C{$row}")->getValue());
            $description = trim((string) $sheet->getCell("D{$row}")->getValue());
            $unit = trim((string) $sheet->getCell("E{$row}")->getValue());
            $weightPerUnit = (float) $sheet->getCell("F{$row}")->getCalculatedValue();
            $pricePerUnit = (float) $sheet->getCell("G{$row}")->getCalculatedValue();
            $surfaceArea = (float) $sheet->getCell("H{$row}")->getValue();
            $materialCost = (float) $sheet->getCell("I{$row}")->getValue();

            if (empty($description) && empty($fpCode)) {
                continue;
            }

            MbsdbProduct::query()->updateOrCreate(
                ['code' => $code],
                [
                    'description' => $description ?: $code,
                    'unit' => $unit ?: null,
                    'category' => $designIndex ?: null,
                    'rate' => $pricePerUnit,
                    'rate_type' => $this->determineRateType($unit),
                    'metadata' => [
                        'fp_code' => $fpCode,
                        'design_index' => $designIndex,
                        'weight_per_unit' => $weightPerUnit,
                        'surface_area' => $surfaceArea,
                        'material_cost_per_ton' => $materialCost,
                    ],
                ]
            );
            $count++;
        }

        $this->command->info("  Seeded {$count} MBSDB products.");
    }

    /**
     * SSDB sheet layout (header at row 3/5):
     * A=DB Code, B=FP Code, C=Design Index, D=Description, E=Unit,
     * F=Weight kg/Unit, G=Price AED/Unit, H=Surface Area, I=Material, J=Manufg
     * Data starts at row 9.
     */
    private function seedSsdbProducts(mixed $spreadsheet): void
    {
        $sheet = $spreadsheet->getSheetByName('SSDB');
        if (! $sheet) {
            $this->command->warn('SSDB sheet not found, skipping...');

            return;
        }

        $this->command->info('Seeding SSDB products...');
        $highestRow = $sheet->getHighestRow();
        $count = 0;

        for ($row = 9; $row <= $highestRow; $row++) {
            $code = trim((string) $sheet->getCell("A{$row}")->getValue());
            if (empty($code)) {
                continue;
            }

            $fpCode = trim((string) $sheet->getCell("B{$row}")->getValue());
            $designIndex = trim((string) $sheet->getCell("C{$row}")->getValue());
            $description = trim((string) $sheet->getCell("D{$row}")->getValue());
            $unit = trim((string) $sheet->getCell("E{$row}")->getValue());
            $weightPerUnit = (float) $sheet->getCell("F{$row}")->getCalculatedValue();
            $pricePerUnit = (float) $sheet->getCell("G{$row}")->getCalculatedValue();
            $materialCost = (float) $sheet->getCell("I{$row}")->getValue();
            $mfgCost = (float) $sheet->getCell("J{$row}")->getValue();

            if (empty($description) && empty($fpCode)) {
                continue;
            }

            SsdbProduct::query()->updateOrCreate(
                ['code' => $code],
                [
                    'description' => $description ?: $code,
                    'unit' => $unit ?: null,
                    'category' => $designIndex ?: null,
                    'rate' => $pricePerUnit,
                    'grade' => null,
                    'metadata' => [
                        'fp_code' => $fpCode,
                        'design_index' => $designIndex,
                        'weight_per_unit' => $weightPerUnit,
                        'material_cost' => $materialCost,
                        'manufacturing_cost' => $mfgCost,
                    ],
                ]
            );
            $count++;
        }

        $this->command->info("  Seeded {$count} SSDB products.");
    }

    /**
     * RawMat sheet layout:
     * Data is in columns J (code), K (kg/m² - formula), L (weight - formula)
     * Row 12 is header "Polyurethane Core Breakdown"
     * Row 13 has column headers: J=Item, K=Kg/M2, L=Weight(Kg)
     * Data starts at row 14.
     */
    private function seedRawMaterials(mixed $spreadsheet): void
    {
        $sheet = $spreadsheet->getSheetByName('RawMat');
        if (! $sheet) {
            $this->command->warn('RawMat sheet not found, skipping...');

            return;
        }

        $this->command->info('Seeding raw materials...');
        $highestRow = $sheet->getHighestRow();
        $count = 0;

        for ($row = 14; $row <= $highestRow; $row++) {
            $code = trim((string) $sheet->getCell("J{$row}")->getValue());
            if (empty($code)) {
                continue;
            }

            $weightPerSqm = (float) $sheet->getCell("K{$row}")->getCalculatedValue();

            RawMaterial::query()->updateOrCreate(
                ['code' => $code],
                [
                    'description' => $this->describeRawMaterial($code),
                    'weight_per_sqm' => $weightPerSqm,
                    'unit' => 'kg/m²',
                ]
            );
            $count++;
        }

        $this->command->info("  Seeded {$count} raw materials.");
    }

    /**
     * DB sheet layout:
     * A=DB Code, B=Cost Code, C=Design Index, D=Description (VLOOKUP formula)
     * Structure: Category headers in col A (when B is empty), data rows below.
     */
    private function seedDesignConfigurations(mixed $spreadsheet): void
    {
        $sheet = $spreadsheet->getSheetByName('DB');
        if (! $sheet) {
            $this->command->warn('DB sheet not found, skipping...');

            return;
        }

        $this->command->info('Seeding design configurations...');
        $highestRow = $sheet->getHighestRow();
        $count = 0;
        $currentCategory = '';

        for ($row = 1; $row <= $highestRow; $row++) {
            $colA = trim((string) $sheet->getCell("A{$row}")->getValue());
            $colB = trim((string) $sheet->getCell("B{$row}")->getValue());
            $colC = trim((string) $sheet->getCell("C{$row}")->getValue());
            $colD = trim((string) $sheet->getCell("D{$row}")->getCalculatedValue());

            if (empty($colA) && empty($colB) && empty($colC) && empty($colD)) {
                continue;
            }

            if (! empty($colA) && empty($colB) && empty($colC)) {
                $currentCategory = $this->slugifyCategory($colA);

                continue;
            }

            if (empty($currentCategory)) {
                continue;
            }

            $value = $colA ?: $colB;
            $label = $colD ?: $colA;

            if (empty($value)) {
                continue;
            }

            DesignConfiguration::query()->updateOrCreate(
                ['category' => $currentCategory, 'key' => $value],
                [
                    'value' => $value,
                    'label' => $label,
                    'sort_order' => $count,
                    'metadata' => array_filter([
                        'cost_code' => $colB ?: null,
                        'design_index' => $colC ?: null,
                    ]),
                ]
            );
            $count++;
        }

        $this->command->info("  Seeded {$count} design configurations.");
    }

    private function slugifyCategory(string $category): string
    {
        $category = strtolower(trim($category));
        $category = preg_replace('/[^a-z0-9\s]/', '', $category);
        $category = preg_replace('/\s+/', '_', $category);

        return $category;
    }

    private function determineRateType(string $unit): string
    {
        $unit = strtolower(trim($unit));

        return match (true) {
            str_contains($unit, 'm²'), str_contains($unit, 'sqm'), str_contains($unit, 'm2') => 'm²',
            str_contains($unit, 'pc'), str_contains($unit, 'set'), str_contains($unit, 'no'), str_contains($unit, 'unit') => 'unit',
            default => 'kg',
        };
    }

    private function describeRawMaterial(string $code): string
    {
        $parts = [];

        if (str_contains($code, 'Core')) {
            $parts[] = 'PU Core';
        }

        if (preg_match('/(\d+)/', $code, $matches)) {
            $parts[] = $matches[1].'mm';
        }

        if (str_contains($code, 'Pro')) {
            $parts[] = 'Professional';
        } elseif (str_contains($code, 'Eco')) {
            $parts[] = 'Economy';
        }

        if (str_contains($code, 'B')) {
            $parts[] = 'Both Sides';
        } elseif (str_contains($code, 'C')) {
            $parts[] = 'Single Side';
        }

        return implode(' ', $parts) ?: $code;
    }
}
