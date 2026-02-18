<?php

use App\Services\Estimation\CsvImportService;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->service = new CsvImportService;
});

/**
 * Helper to create a CSV UploadedFile from content.
 */
function makeCsv(string $content): UploadedFile
{
    return UploadedFile::fake()->createWithContent('test.csv', $content);
}

describe('CSV import service', function () {
    it('parses valid CSV with all expected columns', function () {
        $csv = "description,code,sales_code,cost_code,size,qty,unit,weight_per_unit,rate\n";
        $csv .= "Main Frame,MFR,1,A1,28.5,5,m,35.2,3.50\n";
        $csv .= "Z-Purlin,Z20G,1,B1,9.0,40,m,4.88,3.20\n";

        $result = $this->service->parseFile(makeCsv($csv));

        expect($result['row_count'])->toBe(2);
        expect($result['errors'])->toBeEmpty();
        expect($result['items'])->toHaveCount(2);
        expect($result['items'][0]['code'])->toBe('MFR');
        expect($result['items'][0]['size'])->toBe(28.5);
        expect($result['items'][1]['qty'])->toBe(40.0);
    });

    it('returns errors for missing columns', function () {
        $csv = "description,code,size\n";
        $csv .= "Test,BU,10\n";

        $result = $this->service->parseFile(makeCsv($csv));

        expect($result['items'])->toBeEmpty();
        expect($result['errors'])->not->toBeEmpty();
        expect($result['errors'][0])->toContain('Missing required columns');
    });

    it('returns errors for non-numeric size and qty values', function () {
        $csv = "description,code,sales_code,cost_code,size,qty,unit,weight_per_unit,rate\n";
        $csv .= "Bad Row,BU,1,A1,abc,xyz,m,10,2\n";

        $result = $this->service->parseFile(makeCsv($csv));

        expect($result['items'])->toBeEmpty();
        expect($result['errors'])->toHaveCount(2);
    });

    it('handles empty CSV file (headers only)', function () {
        $csv = "description,code,sales_code,cost_code,size,qty,unit,weight_per_unit,rate\n";

        $result = $this->service->parseFile(makeCsv($csv));

        expect($result['row_count'])->toBe(0);
        expect($result['items'])->toBeEmpty();
        expect($result['errors'])->toBeEmpty();
    });

    it('maps column values to correct types', function () {
        $csv = "description,code,sales_code,cost_code,size,qty,unit,weight_per_unit,rate\n";
        $csv .= "Test Item,BU,3,X2,15.5,8,m,22.1,4.00\n";

        $result = $this->service->parseFile(makeCsv($csv));
        $item = $result['items'][0];

        expect($item['description'])->toBe('Test Item');
        expect($item['code'])->toBe('BU');
        expect($item['sales_code'])->toBe(3);
        expect($item['cost_code'])->toBe('X2');
        expect($item['size'])->toBe(15.5);
        expect($item['qty'])->toBe(8.0);
        expect($item['unit'])->toBe('m');
        expect($item['weight_per_unit'])->toBe(22.1);
        expect($item['rate'])->toBe(4.0);
        expect($item['is_header'])->toBeFalse();
    });
});
