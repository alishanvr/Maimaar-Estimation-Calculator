<?php
/**
 * QuickEst - Excel Importer
 *
 * Imports data from existing Excel QuickEst files
 * Supports both .xlsx and .csv formats
 */

namespace QuickEst\Services;

class ExcelImporter
{
    private array $errors = [];
    private array $warnings = [];

    /**
     * Import from uploaded file
     */
    public function import(string $filePath, string $fileType = 'auto'): array
    {
        if ($fileType === 'auto') {
            $fileType = $this->detectFileType($filePath);
        }

        switch ($fileType) {
            case 'xlsx':
                return $this->importXlsx($filePath);
            case 'csv':
                return $this->importCsv($filePath);
            case 'qep':
            case 'json':
                return $this->importJson($filePath);
            default:
                throw new \Exception('Unsupported file type: ' . $fileType);
        }
    }

    /**
     * Import from XLSX file (simplified - reads as XML)
     */
    private function importXlsx(string $filePath): array
    {
        $zip = new \ZipArchive();

        if ($zip->open($filePath) !== true) {
            throw new \Exception('Cannot open XLSX file');
        }

        // Read shared strings
        $sharedStrings = [];
        $stringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($stringsXml) {
            $xml = simplexml_load_string($stringsXml);
            foreach ($xml->si as $si) {
                $sharedStrings[] = (string)$si->t;
            }
        }

        // Read the first sheet (usually Input sheet)
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (!$sheetXml) {
            throw new \Exception('Cannot read worksheet');
        }

        $xml = simplexml_load_string($sheetXml);
        $data = [];

        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $value = '';
                $type = (string)$cell['t'];

                if ($type === 's' && isset($cell->v)) {
                    // Shared string
                    $index = (int)$cell->v;
                    $value = $sharedStrings[$index] ?? '';
                } elseif (isset($cell->v)) {
                    $value = (string)$cell->v;
                }

                $cellRef = (string)$cell['r'];
                $col = preg_replace('/[0-9]/', '', $cellRef);
                $rowData[$col] = $value;
            }
            $data[] = $rowData;
        }

        return $this->parseInputData($data);
    }

    /**
     * Import from CSV file
     */
    private function importCsv(string $filePath): array
    {
        $data = [];
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            throw new \Exception('Cannot open CSV file');
        }

        while (($row = fgetcsv($handle)) !== false) {
            $data[] = $row;
        }

        fclose($handle);

        return $this->parseCsvData($data);
    }

    /**
     * Import from JSON/QEP file
     */
    private function importJson(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON format');
        }

        // If it's a QuickEst project file
        if (isset($data['version']) && isset($data['input'])) {
            return [
                'success' => true,
                'inputData' => $data['input'],
                'calculatedData' => $data['calculated'] ?? null,
                'preferences' => $data['preferences'] ?? null,
                'errors' => $this->errors,
                'warnings' => $this->warnings
            ];
        }

        // Otherwise, try to use as input data directly
        return [
            'success' => true,
            'inputData' => $data,
            'errors' => $this->errors,
            'warnings' => $this->warnings
        ];
    }

    /**
     * Parse input data from Excel-style array
     */
    private function parseInputData(array $data): array
    {
        $inputData = [];
        $currentSection = '';

        // Map Excel cell references to field names
        $fieldMap = [
            // Project Info
            'Project Name' => 'projectName',
            'Building Name' => 'buildingName',
            'Customer' => 'customerName',
            'Project No' => 'projectNumber',
            'Building No' => 'buildingNumber',
            'Revision' => 'revisionNumber',
            'Date' => 'date',
            'Estimated By' => 'estimatedBy',
            'Location' => 'location',

            // Dimensions
            'Spans' => 'spans',
            'Bays' => 'bays',
            'Slopes' => 'slopes',
            'Back Eave Height' => 'backEaveHeight',
            'Front Eave Height' => 'frontEaveHeight',
            'BEH' => 'backEaveHeight',
            'FEH' => 'frontEaveHeight',

            // Frame options
            'Frame Type' => 'frameType',
            'Base Type' => 'baseType',
            'Min. Thickness' => 'minThickness',
            'Double Welded' => 'doubleWelded',
            'Left EW Type' => 'leftEndwallType',
            'Right EW Type' => 'rightEndwallType',
            'Bracing Type' => 'bracingType',

            // Finish
            'BU Finish' => 'buFinish',
            'CF Finish' => 'cfFinish',

            // Loads
            'Dead Load' => 'deadLoad',
            'Live Load (Purlin)' => 'liveLoadPurlin',
            'Live Load (Frame)' => 'liveLoadFrame',
            'Additional Load' => 'additionalLoad',
            'Wind Speed' => 'windSpeed',

            // Eave conditions
            'Back Eave Condition' => 'backEaveCondition',
            'Front Eave Condition' => 'frontEaveCondition',

            // Sheeting
            'Roof Panel Profile' => 'roofPanelProfile',
            'Roof Top Skin' => 'roofTopSkin',
            'Roof Core' => 'roofCore',
            'Roof Bot Skin' => 'roofBotSkin',
            'Wall Top Skin' => 'wallTopSkin',
            'Wall Core' => 'wallCore',
            'Wall Bot Skin' => 'wallBotSkin',

            // Trim
            'Trim Sizes' => 'trimSizes',

            // Freight
            'Freight' => 'freightDestination',
            'Destination' => 'freightDestination'
        ];

        // Process rows to find label:value pairs
        foreach ($data as $row) {
            // Look for label in column A or B, value in next column
            $label = trim($row['A'] ?? $row[0] ?? '');
            $value = trim($row['B'] ?? $row['C'] ?? $row[1] ?? '');

            // Clean label
            $label = preg_replace('/[:\.]$/', '', $label);

            if (isset($fieldMap[$label])) {
                $field = $fieldMap[$label];
                $inputData[$field] = $this->convertValue($value, $field);
            }

            // Also check column C:D, E:F patterns
            foreach (['C' => 'D', 'E' => 'F', 'G' => 'H'] as $labelCol => $valueCol) {
                $label = trim($row[$labelCol] ?? '');
                $value = trim($row[$valueCol] ?? '');
                $label = preg_replace('/[:\.]$/', '', $label);

                if (isset($fieldMap[$label])) {
                    $field = $fieldMap[$label];
                    $inputData[$field] = $this->convertValue($value, $field);
                }
            }
        }

        // Set defaults for missing required fields
        $this->setDefaults($inputData);

        return [
            'success' => true,
            'inputData' => $inputData,
            'errors' => $this->errors,
            'warnings' => $this->warnings
        ];
    }

    /**
     * Parse CSV data
     */
    private function parseCsvData(array $data): array
    {
        $inputData = [];
        $headers = array_map('strtolower', array_map('trim', $data[0] ?? []));

        // Map CSV headers to field names
        $headerMap = [
            'project_name' => 'projectName',
            'building_name' => 'buildingName',
            'customer_name' => 'customerName',
            'project_number' => 'projectNumber',
            'building_number' => 'buildingNumber',
            'spans' => 'spans',
            'bays' => 'bays',
            'back_eave_height' => 'backEaveHeight',
            'front_eave_height' => 'frontEaveHeight',
            'wind_speed' => 'windSpeed',
            // Add more mappings as needed
        ];

        // If headers match expected format, use first data row
        if (count($data) > 1) {
            $values = $data[1];
            foreach ($headers as $index => $header) {
                $header = str_replace(' ', '_', $header);
                if (isset($headerMap[$header]) && isset($values[$index])) {
                    $field = $headerMap[$header];
                    $inputData[$field] = $this->convertValue($values[$index], $field);
                }
            }
        }

        $this->setDefaults($inputData);

        return [
            'success' => true,
            'inputData' => $inputData,
            'errors' => $this->errors,
            'warnings' => $this->warnings
        ];
    }

    /**
     * Convert value based on field type
     */
    private function convertValue($value, string $field)
    {
        // Numeric fields
        $numericFields = [
            'backEaveHeight', 'frontEaveHeight', 'minThickness',
            'deadLoad', 'liveLoadPurlin', 'liveLoadFrame', 'additionalLoad', 'windSpeed'
        ];

        if (in_array($field, $numericFields)) {
            return (float)$value;
        }

        return $value;
    }

    /**
     * Set default values for missing fields
     */
    private function setDefaults(array &$inputData): void
    {
        $defaults = [
            'spans' => '1@24',
            'bays' => '6@6',
            'slopes' => '1@0.1',
            'backEaveHeight' => 8,
            'frontEaveHeight' => 8,
            'frameType' => 'Clear Span',
            'baseType' => 'Pinned Base',
            'minThickness' => 6,
            'doubleWelded' => 'No',
            'leftEndwallType' => 'Bearing Frame',
            'rightEndwallType' => 'Bearing Frame',
            'bracingType' => 'Cables',
            'buFinish' => 'Red Oxide Primer',
            'cfFinish' => 'Galvanized',
            'deadLoad' => 0.1,
            'liveLoadPurlin' => 0.57,
            'liveLoadFrame' => 0.57,
            'additionalLoad' => 0,
            'windSpeed' => 130,
            'backEaveCondition' => 'Gutter+Dwnspts',
            'frontEaveCondition' => 'Gutter+Dwnspts',
            'roofPanelProfile' => 'M45-250',
            'roofTopSkin' => 'S5OW',
            'roofCore' => '-',
            'roofBotSkin' => '-',
            'wallTopSkin' => 'S5OW',
            'wallCore' => '-',
            'wallBotSkin' => '-',
            'trimSizes' => '0.5 AZ'
        ];

        foreach ($defaults as $field => $default) {
            if (!isset($inputData[$field]) || $inputData[$field] === '') {
                $inputData[$field] = $default;
                $this->warnings[] = "Using default value for {$field}";
            }
        }
    }

    /**
     * Detect file type from extension or content
     */
    private function detectFileType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'xlsx':
            case 'xls':
                return 'xlsx';
            case 'csv':
                return 'csv';
            case 'qep':
            case 'json':
                return 'json';
            default:
                // Try to detect from content
                $content = file_get_contents($filePath, false, null, 0, 100);
                if (substr($content, 0, 2) === 'PK') {
                    return 'xlsx'; // ZIP-based format
                }
                if ($content[0] === '{' || $content[0] === '[') {
                    return 'json';
                }
                return 'csv';
        }
    }

    /**
     * Get import errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get import warnings
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Export to Excel format (as CSV for simplicity)
     */
    public static function exportToCsv(array $inputData, array $calculatedData = null): string
    {
        $csv = "QuickEst Export\n";
        $csv .= "Generated," . date('Y-m-d H:i:s') . "\n\n";

        // Input data section
        $csv .= "INPUT DATA\n";
        foreach ($inputData as $key => $value) {
            $label = ucwords(preg_replace('/([A-Z])/', ' $1', $key));
            $csv .= '"' . $label . '","' . $value . '"' . "\n";
        }

        // Calculated data section
        if ($calculatedData && isset($calculatedData['items'])) {
            $csv .= "\nBILL OF MATERIALS\n";
            $csv .= "Line,DB Code,Description,Unit,Qty,Unit Weight,Total Weight,Total Price\n";

            foreach ($calculatedData['items'] as $item) {
                if ($item['isHeader'] ?? false) continue;
                if ($item['isSeparator'] ?? false) continue;

                $csv .= implode(',', [
                    $item['lineNumber'] ?? '',
                    $item['dbCode'] ?? '',
                    '"' . str_replace('"', '""', $item['description'] ?? '') . '"',
                    $item['unit'] ?? '',
                    $item['quantity'] ?? '',
                    $item['unitWeight'] ?? '',
                    $item['totalWeight'] ?? '',
                    $item['totalPrice'] ?? ''
                ]) . "\n";
            }

            // Summary
            if (isset($calculatedData['summary'])) {
                $csv .= "\nSUMMARY\n";
                $csv .= "Total Weight," . ($calculatedData['summary']['totalWeight'] ?? 0) . " kg\n";
                $csv .= "Total Price," . ($calculatedData['summary']['totalPrice'] ?? 0) . " AED\n";
                $csv .= "Item Count," . ($calculatedData['summary']['itemCount'] ?? 0) . "\n";
            }
        }

        return $csv;
    }
}
