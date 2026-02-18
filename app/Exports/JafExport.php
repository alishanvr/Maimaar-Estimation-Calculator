<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class JafExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @param  array<string, mixed>  $jafData
     */
    public function __construct(private readonly array $jafData) {}

    public function headings(): array
    {
        return ['Field', 'Value'];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        $rows = [];

        // Project info section
        $rows[] = ['--- Project Information ---', ''];
        $projectInfo = $this->jafData['project_info'] ?? [];
        foreach ($projectInfo as $key => $value) {
            $rows[] = [$this->formatLabel($key), (string) $value];
        }

        // Pricing section
        $rows[] = ['--- Pricing ---', ''];
        $pricing = $this->jafData['pricing'] ?? [];
        foreach ($pricing as $key => $value) {
            $rows[] = [$this->formatLabel($key), (string) $value];
        }

        // Building info section
        $rows[] = ['--- Building Information ---', ''];
        $buildingInfo = $this->jafData['building_info'] ?? [];
        foreach ($buildingInfo as $key => $value) {
            $rows[] = [$this->formatLabel($key), (string) $value];
        }

        // Special requirements
        $specialReqs = $this->jafData['special_requirements'] ?? [];
        if (! empty($specialReqs)) {
            $rows[] = ['--- Special Requirements ---', ''];
            $seqNum = 1;
            foreach ($specialReqs as $req) {
                $rows[] = ["Requirement {$seqNum}", (string) $req];
                $seqNum++;
            }
        }

        return $rows;
    }

    /**
     * Convert snake_case key to Title Case label.
     */
    private function formatLabel(string $key): string
    {
        return ucwords(str_replace('_', ' ', $key));
    }
}
