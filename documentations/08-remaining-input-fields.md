# Iteration 8: Remaining Input Fields — Feature-Complete Input Sheet

## What Was Discussed & Planned

The calculator was functional end-to-end but only exposed ~35 of ~94+ input fields. Many engineering parameters (endwall configuration, secondary members, sheeting layers, freight, etc.) were hardcoded to defaults. This iteration adds all remaining fields to make the Input sheet feature-complete vs the original Excel VBA file.

**Key decision:** Openings and Accessories are array-type data that don't fit the single-row grid model. After discussion, inline Handsontable sub-tables were chosen (below the main grid, each with their own columns).

## What Was Completed

### 1. InputSheetConfig.ts — Expanded from 7 to 18 Sections

**Before:** 7 sections, ~35 fields, 302 lines
**After:** 18 sections, ~77 fields, ~650 lines

New sections added:
- **FRAME CONFIGURATION** — `min_thickness`, `double_weld`
- **ENDWALL CONFIGURATION** — `left_endwall_columns`, `left_endwall_type`, `left_endwall_portal`, `right_endwall_columns`, `right_endwall_type`, `right_endwall_portal`
- **SECONDARY MEMBERS** — `purlin_depth`, `roof_sag_rods`, `wall_sag_rods`, `roof_sag_rod_dia`, `wall_sag_rod_dia`, `bracing_type`
- **LOADS** extended — added `live_load_permanent`, `live_load_floor`, `additional_load`
- **ROOF SHEETING** — `roof_top_skin`, `roof_core`, `roof_bottom_skin`, `roof_insulation`
- **WALL SHEETING** — `wall_top_skin`, `wall_core`, `wall_bottom_skin`, `wall_insulation`
- **TRIMS & FLASHINGS** — `trim_size`, `back_eave_condition`, `front_eave_condition`
- **INSULATION** — `wwm_option`
- **FINISHES** — `bu_finish`
- **FREIGHT** — `freight_type`, `freight_rate`, `container_count`, `container_rate`
- **SALES CODES** — `area_sales_code`, `area_description`, `acc_sales_code`, `acc_description`
- **PROJECT / PRICING** — `sales_office`, `num_buildings`, `erection_price`

All new dropdowns use static `dropdownOptions` — no new API categories needed.

### 2. TypeScript Types Updated

- `InputData` interface expanded from ~24 to ~80+ optional fields
- Added `Accessory` interface: `{ description: string; code: string; qty: number }`
- Updated `Opening` interface: added `purlin_support?: number` and `bracing?: number`
- Added `accessories?: Accessory[]` and `openings?: Opening[]` to `InputData`

### 3. Openings Sub-Table Component

`OpeningsTable.tsx` — Inline Handsontable sub-table:
- 5 columns: Location (dropdown), Size (text), Qty (numeric), Purlin Support (numeric), Bracing (numeric)
- Location dropdown: Front Sidewall, Back Sidewall, Left Endwall, Right Endwall
- 9 fixed rows (matches backend's `parseOpenings` loop)
- `afterChange` callback rebuilds array, filters empty rows, propagates via `onChange` prop

### 4. Accessories Sub-Table Component

`AccessoriesTable.tsx` — Inline Handsontable sub-table:
- 3 columns: Description (text), Code (text), Qty (numeric)
- 5 empty rows
- Same pattern as OpeningsTable

### 5. InputSheet.tsx — Sub-Table Integration

- Changed layout from single `overflow-hidden` fixed-height container to `overflow-auto` scrollable container
- Main HotTable uses fixed height based on row count (`INPUT_ROWS.length * 23 + 30`)
- Openings and Accessories sub-tables rendered below main grid with section headers
- Added `handleOpeningsChange` and `handleAccessoriesChange` callbacks
- Removed ResizeObserver (no longer needed with fixed grid height)

### 6. Backend Validation Rules

Both `StoreEstimationRequest.php` and `UpdateEstimationRequest.php` updated with ~50 new validation rules:
- All new scalar fields with appropriate types and constraints
- Enum validation (`in:`) for dropdowns: `double_weld`, `left_endwall_type`, `right_endwall_type`, `left_endwall_portal`, `right_endwall_portal`, `purlin_depth`, `roof_sag_rod_dia`, `wall_sag_rod_dia`, `bracing_type`, `wwm_option`, `freight_type`
- Array validation for `openings` and `accessories` with nested item rules

### 7. Fill Test Data Updated

`handleFillTestData()` now includes sample values for all 42+ new fields, including:
- Frame configuration, endwall settings, secondary members
- Sheeting layers (roof + wall), trims, insulation
- Freight settings, sales codes, project pricing
- Sample openings entry (Front Sidewall, 4x4, qty 2)
- Sample accessories entry (Skylight Panel, SL-01, qty 4)

### 8. Tests

**New test file:** `tests/Feature/Api/ValidationTest.php` (9 tests):
- Validates `endwall_type` rejects invalid values
- Validates `freight_type` rejects invalid values
- Validates numeric fields reject strings (`min_thickness`, `freight_rate`, `erection_price`)
- Validates openings array structure accepted
- Validates accessories array structure accepted
- Validates `wwm_option` rejects invalid values
- Validates `double_weld` rejects invalid values
- Persistence: saves/retrieves openings data correctly
- Persistence: saves/retrieves all new input fields correctly

## What Was Skipped & Why

1. **API-driven dropdowns for new fields** — All new dropdowns use static options since their values don't change. No need to add design_configurations entries or extend the `useAllDropdowns` hook.

2. **Backend service modifications** — The backend calculation services already parse all these fields from `input_data` JSON with defaults. No service code changes were needed.

## Status Table

| Item | Status |
|------|--------|
| InputSheetConfig.ts (18 sections, ~77 fields) | ✅ Complete |
| InputData TypeScript interface | ✅ Complete |
| Accessory interface + Opening update | ✅ Complete |
| OpeningsTable.tsx (5-col sub-table) | ✅ Complete |
| AccessoriesTable.tsx (3-col sub-table) | ✅ Complete |
| InputSheet.tsx (scrollable layout + sub-tables) | ✅ Complete |
| StoreEstimationRequest validation rules | ✅ Complete |
| UpdateEstimationRequest validation rules | ✅ Complete |
| Fill Test Data (all new fields) | ✅ Complete |
| ValidationTest (9 tests) | ✅ Complete |
| Pint formatting | ✅ Complete |
| Full test suite (170 tests) | ✅ Passing |
| Frontend build (npm run build) | ✅ Clean |

## Test Results

```
Tests:    170 passed (384 assertions)
Duration: 4.25s
```

**Test breakdown:**
- Unit tests: 34 (InputParser, QuickEstCalculator, FCPBSGenerator, SALGenerator)
- Feature tests: 136 (Auth, DesignConfig, Estimation, Export, Validation, Caching, Filament, Models, Policies)

## Files Created/Modified

| File | Action |
|------|--------|
| `frontend/src/components/estimations/InputSheetConfig.ts` | Modified (7→18 sections, 35→77 fields) |
| `frontend/src/types/index.ts` | Modified (+42 InputData fields, +Accessory interface) |
| `frontend/src/components/estimations/OpeningsTable.tsx` | Created |
| `frontend/src/components/estimations/AccessoriesTable.tsx` | Created |
| `frontend/src/components/estimations/InputSheet.tsx` | Modified (scrollable + sub-tables) |
| `app/Http/Requests/Api/StoreEstimationRequest.php` | Modified (+50 validation rules) |
| `app/Http/Requests/Api/UpdateEstimationRequest.php` | Modified (+50 validation rules) |
| `frontend/src/app/(protected)/estimations/[id]/page.tsx` | Modified (Fill Test Data expanded) |
| `tests/Feature/Api/ValidationTest.php` | Created (9 tests) |
| `documentations/08-remaining-input-fields.md` | Created |

## How to Test

### Backend
```bash
# Run validation-specific tests
php artisan test tests/Feature/Api/ValidationTest.php

# Run full test suite
php artisan test
```

### Frontend
```bash
cd frontend && npm run build   # Verify clean build
cd frontend && npm run dev     # Start dev server
```

### Manual Testing
1. Login → Open an estimation → Scroll through all 18 sections in Input sheet
2. Verify new dropdowns work (Endwall Type, Freight Type, Bracing Type, etc.)
3. Scroll down → verify Openings sub-table with 9 rows, Location dropdown works
4. Verify Accessories sub-table with 5 rows below Openings
5. Click "Test Data" → verify all fields populate (including openings + accessories)
6. Click Calculate → verify output tabs show enriched data
7. Refresh page → verify all data persists (including openings + accessories)
