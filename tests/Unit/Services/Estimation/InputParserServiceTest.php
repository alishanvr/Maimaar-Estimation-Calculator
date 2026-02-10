<?php

use App\Services\Estimation\InputParserService;

beforeEach(function () {
    $this->parser = new InputParserService;
});

describe('fixSeparators', function () {
    it('normalizes colon to @', function () {
        expect($this->parser->fixSeparators('1:6.865'))->toBe('1@6.865');
    });

    it('normalizes plus to comma', function () {
        expect($this->parser->fixSeparators('1@6.865+1@9.104'))->toBe('1@6.865,1@9.104');
    });

    it('normalizes semicolons to comma', function () {
        expect($this->parser->fixSeparators('1@6;2@9'))->toBe('1@6,2@9');
    });

    it('normalizes X to @', function () {
        expect($this->parser->fixSeparators('2X9.144'))->toBe('2@9.144');
    });

    it('handles complex mixed separators', function () {
        expect($this->parser->fixSeparators('1:6.865+1:9.104+2x9.144'))->toBe('1@6.865,1@9.104,2@9.144');
    });
});

describe('getList', function () {
    it('parses single bay notation', function () {
        $result = $this->parser->getList('1@6.865');
        // Index 0 holds metadata: [num_groups, total_count]
        expect((int) $result[0][0])->toBe(1);
        expect((float) $result[0][1])->toEqualWithDelta(1.0, 0.01);
        // Index 1 holds first group: [count, value]
        expect((float) $result[1][0])->toEqualWithDelta(1.0, 0.01);
        expect((float) $result[1][1])->toEqualWithDelta(6.865, 0.001);
    });

    it('parses Quote 53305 bay spacing: 1@6.865+1@9.104+2@9.144', function () {
        $result = $this->parser->getList('1@6.865+1@9.104+2@9.144');
        expect((int) $result[0][0])->toBe(3);   // 3 distinct groups
        expect((float) $result[0][1])->toEqualWithDelta(4.0, 0.01);   // 4 total bays (1+1+2)
        expect((float) $result[1][1])->toEqualWithDelta(6.865, 0.001);
        expect((float) $result[2][1])->toEqualWithDelta(9.104, 0.001);
        expect((float) $result[3][0])->toEqualWithDelta(2.0, 0.01);
        expect((float) $result[3][1])->toEqualWithDelta(9.144, 0.001);
    });

    it('parses single value without multiplier', function () {
        $result = $this->parser->getList('28.5');
        expect($result[0][1])->toBe(1.0);
        expect((float) $result[1][1])->toEqualWithDelta(28.5, 0.1);
    });
});

describe('getBuildingDimension', function () {
    it('calculates total length for Quote 53305 bay spacing', function () {
        $dim = $this->parser->getBuildingDimension('1@6.865+1@9.104+2@9.144');
        // 6.865 + 9.104 + 2*9.144 = 6.865 + 9.104 + 18.288 = 34.257
        expect((float) $dim['total'])->toEqualWithDelta(34.257, 0.01);
        expect($dim['bay_count'])->toBe(4);
        expect((float) $dim['max_span'])->toEqualWithDelta(9.144, 0.001);
    });

    it('calculates width for single span', function () {
        // Single value path: no separators, no @, sets maxSpan only, total stays 0
        $dim = $this->parser->getBuildingDimension('28.5');
        expect($dim['bay_count'])->toBe(0);
        expect((float) $dim['max_span'])->toEqualWithDelta(28.5, 0.1);
    });
});

describe('expandList', function () {
    it('expands compressed list to individual values', function () {
        $list = $this->parser->getList('1@6.865+1@9.104+2@9.144');
        $expanded = $this->parser->expandList($list);
        // Index 0 = total count, then 1-indexed values
        expect($expanded[0])->toBe(4);
        expect((float) $expanded[1])->toEqualWithDelta(6.865, 0.001);
        expect((float) $expanded[2])->toEqualWithDelta(9.104, 0.001);
        expect((float) $expanded[3])->toEqualWithDelta(9.144, 0.001);
        expect((float) $expanded[4])->toEqualWithDelta(9.144, 0.001);
    });
});

describe('calculateSlopeProfile', function () {
    it('calculates rafter length for single span roof', function () {
        // For a single-slope roof: getList('0.1') gives 1 group with count=1, value=0.1
        $slopeList = [
            0 => [0 => 1.0, 1 => 1.0],
            1 => [0 => 1.0, 1 => 0.1],
        ];
        $profile = $this->parser->calculateSlopeProfile($slopeList, 28.5, 6.0, 6.0);

        // Width=1 becomes half of 28.5 = 14.25, rise=0.1
        // rafter = sqrt(1 + 0.01) * 14.25 ≈ 14.321
        expect((float) $profile['rafter_length'])->toBeGreaterThan(14.0);
        expect((float) $profile['rafter_length'])->toBeLessThan(30.0);
        expect($profile['num_peaks'])->toBeGreaterThanOrEqual(0);
    });
});

describe('getConnectionType', function () {
    it('returns correct connection type for light frames', function () {
        expect($this->parser->getConnectionType(5.0))->toBe(1);
    });

    it('returns correct connection type for medium frames', function () {
        // wplm=25 → > 20 and < 40 → type 2
        expect($this->parser->getConnectionType(25.0))->toBe(2);
    });

    it('returns correct connection type for heavy frames', function () {
        // wplm=50 → >= 40 and < 80 → type 3
        expect($this->parser->getConnectionType(50.0))->toBe(3);
    });
});

describe('getScrewCodes', function () {
    it('returns screw codes for AZ Steel material', function () {
        $codes = $this->parser->getScrewCodes('S045RAL9002', 'None', 'None');
        expect($codes)->toHaveKeys(['screw_code', 'stitch_code', 'plug_screw']);
        expect($codes['screw_code'])->toBe('CS2');
    });
});

describe('getTrimSuffix', function () {
    it('returns S for AZ Steel', function () {
        $result = $this->parser->getTrimSuffix('S045RAL9002', '0.5 AZ');
        expect($result['trim_suffix'])->toBe('S');
    });

    it('returns A for Aluminum', function () {
        $result = $this->parser->getTrimSuffix('A070RAL9002', '0.5 AZ');
        expect($result['trim_suffix'])->toBe('A');
    });
});
