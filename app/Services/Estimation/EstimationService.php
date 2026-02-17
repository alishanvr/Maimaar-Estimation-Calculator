<?php

namespace App\Services\Estimation;

use App\Models\Estimation;

class EstimationService
{
    public function __construct(
        private readonly InputParserService $parser,
        private readonly QuickEstCalculator $calculator,
        private readonly DetailGenerator $detailGenerator,
        private readonly FCPBSGenerator $fcpbsGenerator,
        private readonly SALGenerator $salGenerator,
        private readonly BOQGenerator $boqGenerator,
        private readonly JAFGenerator $jafGenerator,
        private readonly RawMatGenerator $rawmatGenerator,
        private readonly FreightCalculator $freightCalculator,
        private readonly PaintCalculator $paintCalculator,
        private readonly RoofMonitorCalculator $monitorCalculator,
    ) {}

    /**
     * Run the full estimation calculation pipeline.
     *
     * This is the PHP equivalent of the Excel's recalculation chain:
     *   1. Parse inputs (InputParserService)
     *   2. Generate Detail items (DetailGenerator — equivalent to AddArea_Click)
     *   3. Calculate freight loads and costs (FreightCalculator)
     *   4. Calculate paint costs (PaintCalculator)
     *   5. Calculate roof monitor (RoofMonitorCalculator)
     *   6. Generate FCPBS financial breakdown (FCPBSGenerator)
     *   7. Generate SAL sales summary (SALGenerator)
     *   8. Generate BOQ bill of quantities (BOQGenerator)
     *   9. Generate JAF job acceptance form (JAFGenerator)
     *
     * @param  array<string, mixed>  $input  All input fields (156 fields from Input sheet)
     * @param  array<string, float>  $markups  Optional markup overrides
     * @return array<string, mixed> Complete estimation results
     */
    public function calculate(array $input, array $markups = []): array
    {
        // Step 1: Parse input dimensions
        $parsedInput = $this->parseInput($input);

        // Step 2: Generate Detail (bill of materials)
        $detailItems = $this->detailGenerator->generate($parsedInput);

        // Step 3: Calculate roof monitor items (if applicable)
        $monitorResult = $this->monitorCalculator->calculate($parsedInput);
        if ($monitorResult['is_applicable']) {
            $detailItems = array_merge($detailItems, $monitorResult['items']);
        }

        // Step 4: Calculate paint costs and inject items
        $totalSurfaceArea = $this->calculateTotalSurfaceArea($detailItems);
        $paintResult = $this->paintCalculator->calculate($parsedInput, $totalSurfaceArea);
        $detailItems = array_merge($detailItems, $paintResult['items']);

        // Step 5: Generate FCPBS from detail items
        $fcpbsResult = $this->fcpbsGenerator->generate($detailItems, $markups);

        // Step 6: Calculate freight and inject items
        $freightResult = $this->freightCalculator->calculate(
            $fcpbsResult['categories'],
            $parsedInput,
            $detailItems
        );
        $detailItems = array_merge($detailItems, $freightResult['items']);

        // Step 7: Regenerate FCPBS with freight/container items included
        $fcpbsResult = $this->fcpbsGenerator->generate($detailItems, $markups);

        // Step 8: Generate SAL sales summary
        $salResult = $this->salGenerator->generate($detailItems, $fcpbsResult);

        // Step 9: Generate BOQ — attach FCPBS categories to freight so BOQ
        // can use M+O selling prices and align its total with the FCPBS total.
        $freightResult['_fcpbs_categories'] = $fcpbsResult['categories'] ?? [];
        $boqResult = $this->boqGenerator->generate($fcpbsResult, $freightResult, $parsedInput);

        // Step 10: Generate JAF
        $jafResult = $this->jafGenerator->generate($fcpbsResult, $parsedInput);

        // Step 11: Generate RAWMAT (raw material aggregation)
        $rawmatResult = $this->rawmatGenerator->generate($detailItems);

        // Build summary
        $totalWeightKg = (float) ($fcpbsResult['total_weight_kg'] ?? 0);
        $totalPrice = (float) ($fcpbsResult['total_price'] ?? 0);

        return [
            'summary' => [
                'total_weight_kg' => round($totalWeightKg, 3),
                'total_weight_mt' => round($totalWeightKg / 1000, 4),
                'total_price_aed' => round($totalPrice, 2),
                'price_per_mt' => ($totalWeightKg > 0) ? round(1000 * $totalPrice / $totalWeightKg, 2) : 0,
                'fob_price_aed' => (float) ($fcpbsResult['fob_price'] ?? 0),
                'steel_weight_kg' => (float) ($fcpbsResult['steel_subtotal']['weight_kg'] ?? 0),
                'panels_weight_kg' => (float) ($fcpbsResult['panels_subtotal']['weight_kg'] ?? 0),
            ],
            'detail' => $detailItems,
            'fcpbs' => $fcpbsResult,
            'sal' => $salResult,
            'boq' => $boqResult,
            'jaf' => $jafResult,
            'rawmat' => $rawmatResult,
            'freight' => $freightResult,
            'paint' => $paintResult,
            'monitor' => $monitorResult,
            'parsed_input' => $parsedInput,
        ];
    }

    /**
     * Run calculation and persist results to an Estimation model.
     */
    public function calculateAndSave(Estimation $estimation, array $markups = []): Estimation
    {
        $input = $estimation->input_data ?? [];

        // Merge top-level estimation fields into input so downstream generators
        // (JAF, SAL, BOQ) can access quote_number, customer_name, etc.
        $input['quote_number'] = $estimation->quote_number ?? '';
        $input['building_name'] = $estimation->building_name ?? '';
        $input['project_name'] = $estimation->project_name ?? '';
        $input['customer_name'] = $estimation->customer_name ?? '';
        $input['revision_number'] = (int) ($estimation->revision_no ?? 0);
        $input['building_number'] = $estimation->building_no ?? '';
        $input['salesperson_code'] = $estimation->salesperson_code ?? '';
        $input['date'] = $estimation->estimation_date ?? now()->format('Y-m-d');

        $results = $this->calculate($input, $markups);

        $estimation->update([
            'results_data' => $results,
            'total_weight_mt' => $results['summary']['total_weight_mt'],
            'total_price_aed' => $results['summary']['total_price_aed'],
            'status' => 'calculated',
        ]);

        return $estimation->fresh();
    }

    /**
     * Parse and enrich input data with calculated dimensions.
     *
     * Converts raw input fields into a structured array with parsed bay spacings,
     * slope profiles, column heights, screw codes, etc.
     *
     * @param  array<string, mixed>  $input  Raw input fields
     * @return array<string, mixed> Enriched input with parsed dimensions
     */
    private function parseInput(array $input): array
    {
        $parsed = $input;

        // Map frontend field names to DetailGenerator's expected keys
        $parsed['spans'] = $input['span_widths'] ?? $input['spans'] ?? '1@28.5';
        $parsed['bays'] = $input['bay_spacing'] ?? $input['bays'] ?? '1@6';

        // Build slopes string from left/right roof slope values
        $leftSlope = (float) ($input['left_roof_slope'] ?? 1.0);
        $rightSlope = (float) ($input['right_roof_slope'] ?? $leftSlope);
        if (! isset($input['slopes'])) {
            $slopeRise = $leftSlope / 10;
            $parsed['slopes'] = "1@{$slopeRise}";
        }

        // Map live_load to the permanent/floor keys the DetailGenerator expects
        $parsed['live_load_permanent'] = $parsed['live_load_permanent'] ?? $input['live_load'] ?? 0.57;
        $parsed['live_load_floor'] = $parsed['live_load_floor'] ?? $input['live_load'] ?? 0.57;

        // Parse bay spacing
        $baySpacingStr = $input['bay_spacing'] ?? '1@6';
        $bayList = $this->parser->getList($baySpacingStr);
        $bayDim = $this->parser->getBuildingDimension($baySpacingStr);
        $parsed['bay_list'] = $bayList;
        $parsed['building_length'] = $bayDim['total'];
        $parsed['num_bays'] = $bayDim['bay_count'];
        $parsed['max_bay_spacing'] = $bayDim['max_span'];

        // Parse span widths
        $spanStr = $input['span_widths'] ?? '1@28.5';
        $spanList = $this->parser->getList($spanStr);
        $spanDim = $this->parser->getBuildingDimension($spanStr);
        $parsed['span_list'] = $spanList;
        $parsed['building_width'] = $spanDim['total'];
        $parsed['num_spans'] = $spanDim['bay_count'];

        // Eave heights
        $backEaveHeight = (float) ($input['back_eave_height'] ?? 6.0);
        $frontEaveHeight = (float) ($input['front_eave_height'] ?? $backEaveHeight);
        $parsed['back_eave_height'] = $backEaveHeight;
        $parsed['front_eave_height'] = $frontEaveHeight;

        // Roof slopes
        $leftSlope = (float) ($input['left_roof_slope'] ?? 1.0);
        $rightSlope = (float) ($input['right_roof_slope'] ?? $leftSlope);
        $parsed['left_roof_slope'] = $leftSlope;
        $parsed['right_roof_slope'] = $rightSlope;

        // Slope profile calculation
        $slopeProfile = $this->parser->calculateSlopeProfile(
            $spanList,
            $backEaveHeight,
            $leftSlope / 10,
            $rightSlope / 10
        );
        $parsed['slope_profile'] = $slopeProfile;
        $parsed['rafter_length'] = $slopeProfile['rafter_length'];
        $parsed['endwall_area'] = $slopeProfile['endwall_area'];
        $parsed['num_peaks'] = $slopeProfile['num_peaks'];
        $parsed['num_valleys'] = $slopeProfile['num_valleys'];

        // Loads
        $parsed['dead_load'] = (float) ($input['dead_load'] ?? 0.1);
        $parsed['live_load'] = (float) ($input['live_load'] ?? 0.57);
        $parsed['wind_speed'] = (float) ($input['wind_speed'] ?? 0.7);

        // Main frame calculation
        $mfLoad = $parsed['dead_load'] + $parsed['live_load'];
        $tributaryBay = $parsed['max_bay_spacing'];
        $wplm = $this->calculator->calculateFrameWeightPerMeter(
            $mfLoad,
            $tributaryBay,
            $parsed['building_width']
        );
        $parsed['frame_weight_per_meter'] = $wplm;

        // Number of frames
        $parsed['num_frames'] = $parsed['num_bays'] + 1;

        // Connection type based on frame weight
        $parsed['connection_type'] = $this->parser->getConnectionType($wplm);

        // Base type
        $baseType = $input['base_type'] ?? 'Pinned Base';
        $parsed['base_type'] = $baseType;

        // Fixed base index
        $parsed['fixed_base_index'] = $this->calculator->calculateFixedBaseIndex($baseType, $backEaveHeight);

        // Connection plate percentage
        $parsed['connection_pct'] = $this->calculator->getConnectionPlatePercentage(
            $parsed['num_spans'],
            $baseType
        ) / 100;

        // Purlin design
        $purlinIndex = ($parsed['dead_load'] + $parsed['live_load']) * pow($tributaryBay, 2);
        $parsed['purlin_design_index'] = $purlinIndex;
        $parsed['purlin_code'] = $this->calculator->lookupPurlinCode($purlinIndex);

        // Purlin lines and sizes
        $parsed['purlin_lines'] = $this->calculator->calculatePurlinLines(
            $parsed['building_width'],
            $parsed['num_peaks'],
            $parsed['num_valleys']
        );

        // Girt lines
        $parsed['girt_lines'] = $this->calculator->calculateGirtLines($backEaveHeight, $frontEaveHeight);

        // Endwall girt lines
        $parsed['ew_girt_lines'] = $this->calculator->calculateEndwallGirtLines(
            $parsed['endwall_area'],
            $parsed['building_width']
        );

        // Bracing
        $parsed['num_bracing_bays'] = $this->calculator->calculateBracingBays($parsed['num_bays']);

        // Flange bracing quantity
        $parsed['flange_bracing_qty'] = $this->calculator->calculateFlangeBracingQty(
            $parsed['num_frames'],
            $backEaveHeight,
            $frontEaveHeight,
            $parsed['building_width'],
            $parsed['num_spans']
        );

        // Endwall column code
        $ewcIndex = ($parsed['dead_load'] + $parsed['live_load']) * pow($parsed['building_width'] / 4, 2);
        // CF Finish: frontend stores string ("Painted"/"Galvanized"), map to int (3=Painted, 4=Galvanized)
        $cfFinishRaw = $input['cf_finish'] ?? 'Painted';
        $cfFinish = in_array($cfFinishRaw, ['Galvanized', 'Alu/Zinc', 4, '4'], true) ? 4 : 3;
        $parsed['ewc_code'] = $this->calculator->lookupEndwallColumnCode($ewcIndex, $cfFinish);

        // Sheeting codes
        $parsed['roof_panel_code'] = $input['roof_panel_code'] ?? '';
        $parsed['wall_panel_code'] = $input['wall_panel_code'] ?? '';
        $parsed['panel_profile'] = $input['panel_profile'] ?? 'M45-250';

        // Screw codes
        if (! empty($parsed['roof_panel_code'])) {
            $parsed['screw_codes'] = $this->parser->getScrewCodes(
                $parsed['roof_panel_code'],
                $input['outer_skin_material'] ?? 'AZ Steel',
                (int) ($input['core_thickness'] ?? 50)
            );
        }

        // Frame type
        $parsed['frame_type'] = $input['frame_type'] ?? 'Clear Span';

        return $parsed;
    }

    /**
     * Sum total surface area from detail items for paint calculation.
     * Detail column S contains surface area per item.
     */
    private function calculateTotalSurfaceArea(array $detailItems): float
    {
        $total = 0;
        foreach ($detailItems as $item) {
            if (! ($item['is_header'] ?? false)) {
                $total += (float) ($item['surface_area'] ?? 0);
            }
        }

        return $total;
    }
}
