<?php

use App\Services\Estimation\CachingService;
use App\Services\Estimation\QuickEstCalculator;

beforeEach(function () {
    $this->calculator = new QuickEstCalculator(new CachingService);
});

describe('lookupPurlinCode', function () {
    it('returns Z15G for low index', function () {
        expect($this->calculator->lookupPurlinCode(10))->toBe('Z15G');
    });

    it('returns Z18G for medium index', function () {
        expect($this->calculator->lookupPurlinCode(20))->toBe('Z18G');
    });

    it('returns Z20G for higher index', function () {
        expect($this->calculator->lookupPurlinCode(40))->toBe('Z20G');
    });

    it('returns Z25G for high index', function () {
        expect($this->calculator->lookupPurlinCode(80))->toBe('Z25G');
    });
});

describe('lookupEndwallColumnCode', function () {
    it('returns Z15G for low index painted', function () {
        expect($this->calculator->lookupEndwallColumnCode(20, 3))->toBe('Z15G');
    });

    it('returns HRB1 for medium-high index painted', function () {
        expect($this->calculator->lookupEndwallColumnCode(300, 3))->toBe('HRB1');
    });

    it('returns Z25G for medium-high index galvanized', function () {
        expect($this->calculator->lookupEndwallColumnCode(300, 4))->toBe('Z25G');
    });
});

describe('calculateFrameWeightPerMeter', function () {
    it('calculates frame weight for Quote 53305 parameters', function () {
        // Quote 53305: span=28.5, dead+live load = 0.1+0.57 = 0.67, bay=9.144
        $wplm = $this->calculator->calculateFrameWeightPerMeter(0.67, 9.144, 28.5);
        // wplm = (0.1 * 0.67 * 9.144 + 0.3) * (2 * 28.5 - 9)
        // = (0.612648 + 0.3) * 48
        // = 0.912648 * 48
        // ≈ 43.807
        expect($wplm)->toBeGreaterThan(40);
        expect($wplm)->toBeLessThan(50);
    });
});

describe('calculateFixedBaseIndex', function () {
    it('returns 1.0 for pinned base', function () {
        expect($this->calculator->calculateFixedBaseIndex('Pinned Base', 6.0))->toBe(1.0);
    });

    it('calculates index for fixed base', function () {
        $index = $this->calculator->calculateFixedBaseIndex('Fixed Base', 6.0);
        // (12/6)^0.15 = 2^0.15 ≈ 1.1096
        expect($index)->toBeGreaterThan(1.0);
        expect($index)->toBeLessThan(1.2);
    });
});

describe('getConnectionPlatePercentage', function () {
    it('returns 17% for single-span pinned base', function () {
        expect($this->calculator->getConnectionPlatePercentage(1, 'Pinned Base'))->toBe(17.0);
    });

    it('returns 14% for multi-span pinned base', function () {
        expect($this->calculator->getConnectionPlatePercentage(2, 'Pinned Base'))->toBe(14.0);
    });

    it('returns 20% for single-span fixed base', function () {
        expect($this->calculator->getConnectionPlatePercentage(1, 'Fixed Base'))->toBe(20.0);
    });
});

describe('calculateBracingBays', function () {
    it('calculates bracing bays for 4-bay building', function () {
        // CInt(4/5 + 1) = CInt(1.8) = 2
        expect($this->calculator->calculateBracingBays(4))->toBe(2);
    });

    it('calculates bracing bays for 10-bay building', function () {
        // CInt(10/5 + 1) = CInt(3) = 3
        expect($this->calculator->calculateBracingBays(10))->toBe(3);
    });
});

describe('calculatePurlinLines', function () {
    it('calculates purlin lines for 28.5m wide building', function () {
        // CInt(28.5/1.5 + 1 + 0) = CInt(20) = 20
        $lines = $this->calculator->calculatePurlinLines(28.5, 1, 0);
        expect($lines)->toBe(20);
    });
});

describe('calculatePurlinSize', function () {
    it('adds 0.107 overlap for standard bay', function () {
        $size = $this->calculator->calculatePurlinSize(6.0);
        expect((float) $size)->toEqualWithDelta(6.107, 0.001);
    });

    it('adds extra extension for bay > 6.5', function () {
        $size = $this->calculator->calculatePurlinSize(7.0);
        expect((float) $size)->toEqualWithDelta(7.706, 0.001); // 7.0 + 0.107 + 0.599
    });

    it('adds double extension for bay > 9', function () {
        $size = $this->calculator->calculatePurlinSize(9.144);
        // 9.144 + 0.107 + 0.599 + 0.706 = 10.556
        expect((float) $size)->toEqualWithDelta(10.556, 0.001);
    });
});

describe('calculateFlangeBracingQty', function () {
    it('calculates flange bracing for standard building', function () {
        $qty = $this->calculator->calculateFlangeBracingQty(5, 6.0, 6.0, 28.5, 1);
        expect($qty)->toBeGreaterThan(0);
    });
});

describe('getPurlinContinuityFactor', function () {
    it('returns 1.0 for single bay', function () {
        expect($this->calculator->getPurlinContinuityFactor(1))->toBe(1.0);
    });

    it('returns 1.25 for two bays', function () {
        expect($this->calculator->getPurlinContinuityFactor(2))->toBe(1.25);
    });

    it('returns 1.0 for five or more bays', function () {
        expect($this->calculator->getPurlinContinuityFactor(5))->toBe(1.0);
    });
});

describe('calculateWindStruts', function () {
    it('returns strut sizes for standard wind loading', function () {
        $result = $this->calculator->calculateWindStruts(0.7, 28.5, 6.0, 2);
        expect($result)->toHaveKeys(['t200', 't150', 't125', 'st_purlin', 'st_clip']);
    });
});

describe('calculateRoofSheetingArea', function () {
    it('calculates roof area with standard profile', function () {
        $area = $this->calculator->calculateRoofSheetingArea(14.32, 34.257, 0, 'M45-250');
        // 1.02 * 14.32 * 34.257 = 1.02 * 490.5 ≈ 500.3
        expect($area)->toBeGreaterThan(490);
        expect($area)->toBeLessThan(510);
    });

    it('adjusts area for M45-150 profile', function () {
        $area250 = $this->calculator->calculateRoofSheetingArea(14.32, 34.257, 0, 'M45-250');
        $area150 = $this->calculator->calculateRoofSheetingArea(14.32, 34.257, 0, 'M45-150');
        // M45-150 divides by 0.9, so area should be larger
        expect($area150)->toBeGreaterThan($area250);
    });
});

describe('calculateDownspoutSpacing', function () {
    it('returns a positive spacing value', function () {
        $spacing = $this->calculator->calculateDownspoutSpacing(
            roofSlope: 1,
            leftRoofWidth: 14.25,
            rightRoofWidth: 14.25,
            baySpacing: 9.144,
            gutterType: 1,
            downspoutLocation: 'End',
            rainfallType: 'Normal',
            rainfallIntensity: 100
        );
        expect($spacing)->toBeGreaterThan(0);
        expect($spacing)->toBeLessThanOrEqual(9.144);
    });
});
