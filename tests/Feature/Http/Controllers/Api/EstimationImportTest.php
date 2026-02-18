<?php

use App\Models\Estimation;
use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->estimation = Estimation::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'draft',
    ]);
});

/**
 * Create a valid CSV file for testing.
 */
function createCsvFile(array $rows = []): UploadedFile
{
    $headers = ['description', 'code', 'sales_code', 'cost_code', 'size', 'qty', 'unit', 'weight_per_unit', 'rate'];

    if (empty($rows)) {
        $rows = [
            ['Custom Beam', 'BU', '1', 'A1', '10.5', '3', 'm', '25.2', '3.50'],
            ['Z-Purlin', 'Z20G', '1', 'B1', '9.0', '12', 'm', '4.88', '3.20'],
        ];
    }

    $content = implode(',', $headers)."\n";
    foreach ($rows as $row) {
        $content .= implode(',', $row)."\n";
    }

    return UploadedFile::fake()->createWithContent('test-import.csv', $content);
}

describe('CSV import', function () {
    it('parses valid CSV and returns preview', function () {
        $file = createCsvFile();

        $response = $this->actingAs($this->user)
            ->postJson("/api/estimations/{$this->estimation->id}/import", [
                'file' => $file,
            ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.row_count', 2)
            ->assertJsonCount(2, 'data.items');

        // Should NOT have saved (no commit flag)
        $this->estimation->refresh();
        expect($this->estimation->input_data['imported_items'] ?? null)->toBeNull();
    });

    it('commits imported items when commit flag is true', function () {
        $file = createCsvFile();

        $response = $this->actingAs($this->user)
            ->postJson("/api/estimations/{$this->estimation->id}/import?commit=true", [
                'file' => $file,
            ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.row_count', 2);

        $this->estimation->refresh();
        expect($this->estimation->input_data['imported_items'])->toHaveCount(2);
        expect($this->estimation->input_data['imported_items'][0]['code'])->toBe('BU');
    });

    it('appends to existing imported items by default', function () {
        // Pre-seed with existing imported items
        $this->estimation->update([
            'input_data' => [
                'imported_items' => [
                    ['description' => 'Existing', 'code' => 'EX', 'sales_code' => 1, 'cost_code' => '', 'size' => 5, 'qty' => 1, 'unit' => 'm', 'weight_per_unit' => 10, 'rate' => 2],
                ],
            ],
        ]);

        $file = createCsvFile();

        $response = $this->actingAs($this->user)
            ->postJson("/api/estimations/{$this->estimation->id}/import?commit=true", [
                'file' => $file,
            ]);

        $response->assertSuccessful();

        $this->estimation->refresh();
        expect($this->estimation->input_data['imported_items'])->toHaveCount(3); // 1 existing + 2 new
    });

    it('replaces existing imported items with replace strategy', function () {
        $this->estimation->update([
            'input_data' => [
                'imported_items' => [
                    ['description' => 'Existing', 'code' => 'EX', 'sales_code' => 1, 'cost_code' => '', 'size' => 5, 'qty' => 1, 'unit' => 'm', 'weight_per_unit' => 10, 'rate' => 2],
                ],
            ],
        ]);

        $file = createCsvFile();

        $response = $this->actingAs($this->user)
            ->postJson("/api/estimations/{$this->estimation->id}/import?commit=true", [
                'file' => $file,
                'merge_strategy' => 'replace',
            ]);

        $response->assertSuccessful();

        $this->estimation->refresh();
        expect($this->estimation->input_data['imported_items'])->toHaveCount(2); // Only new items
    });

    it('rejects import on non-draft estimation', function () {
        $calculated = Estimation::factory()->calculated()->create([
            'user_id' => $this->user->id,
        ]);

        $file = createCsvFile();

        $response = $this->actingAs($this->user)
            ->postJson("/api/estimations/{$calculated->id}/import", [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Only draft estimations can be imported into.');
    });

    it('rejects invalid file type', function () {
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->user)
            ->postJson("/api/estimations/{$this->estimation->id}/import", [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    });

    it('returns 403 for unauthorized user', function () {
        $other = User::factory()->create();
        $file = createCsvFile();

        $response = $this->actingAs($other)
            ->postJson("/api/estimations/{$this->estimation->id}/import", [
                'file' => $file,
            ]);

        $response->assertForbidden();
    });

    it('logs import activity when committed', function () {
        $file = createCsvFile();

        $this->actingAs($this->user)
            ->postJson("/api/estimations/{$this->estimation->id}/import?commit=true", [
                'file' => $file,
            ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Estimation::class,
            'subject_id' => $this->estimation->id,
            'description' => 'imported CSV data',
        ]);
    });
});
