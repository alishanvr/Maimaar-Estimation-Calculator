# Iteration 9: Crane, Mezzanine, Partition, Canopy + Button Bar UI

## What Was Discussed & Planned

The calculator was feature-complete for the main building structure but missing 4 optional building components from the VBA Excel file: Crane, Mezzanine, Partition, and Canopy. These are multi-instance array data (e.g., a building can have 2 cranes, 3 canopies). The Excel VBA had `AddCrane_Click()`, `AddMezz_Click()`, `AddPartition_Click()`, `AddCanopy_Click()` buttons.

**UI concern:** The Input sheet was already long (77 fields + Openings + Accessories). Adding 4 more multi-field component sections needed a clean UX pattern.

**Key decision:** After discussing options (sidebar navigation, tabbed sections, collapsible panels), a simple **button bar above the grid** was chosen. Four toggle buttons (`+ Crane`, `+ Mezzanine`, `+ Partition`, `+ Canopy`) appear above the main grid. Clicking adds the component's sub-table below and scrolls to it. Active buttons show a checkmark with an X to remove.

## What Was Completed

### 1. TypeScript Interfaces — 4 New Component Types

Added to `frontend/src/types/index.ts`:
- **Crane** (6 fields): description, sales_code, capacity, duty, rail_centers, crane_run
- **Mezzanine** (15 fields): description, sales_code, col_spacing, beam_spacing, joist_spacing, clear_height, double_welded, deck_type, n_stairs, dead_load, live_load, additional_load, bu_finish, cf_finish, min_thickness
- **Partition** (12 fields): description, sales_code, direction, bu_finish, cf_finish, wind_speed, col_spacing, height, opening_height, front_sheeting, back_sheeting, insulation
- **Canopy** (16 fields): description, sales_code, frame_type, location, height, width, col_spacing, roof_sheeting, drainage, soffit, wall_sheeting, internal_sheeting, bu_finish, cf_finish, live_load, wind_speed

Extended `InputData` with: `cranes?: Crane[]`, `mezzanines?: Mezzanine[]`, `partitions?: Partition[]`, `canopies?: Canopy[]`

### 2. ComponentTableConfig.ts — Config-Driven Column Definitions

Data-driven configuration for each component type:
- Column definitions with key, label, type (text/numeric/dropdown), width, and dropdown options
- Max rows per type: Crane=3, Mezzanine=3, Partition=5, Canopy=5
- Component registry (`COMPONENT_CONFIGS`) for iteration in UI code
- Dropdown validations: Crane duty (L/M/H), Mezzanine double_welded (Yes/No), Partition direction (Longitudinal/Transverse), Canopy frame_type (Roof Extension/Lean-To), Canopy location (Front/Back/Left/Right/All Around)

### 3. ComponentTable.tsx — Generic Reusable HotTable

A single reusable Handsontable component that renders any of the 4 component types:
- Driven by `ComponentColumnDef[]` config — no per-component code needed
- Same pattern as OpeningsTable: `toTableData()`, `afterChange` with patching, `cells` callback
- Filters empty rows before propagating changes to parent
- Supports text, numeric, and dropdown column types

### 4. ComponentButtonBar.tsx — Toggle Buttons

Row of buttons above the main grid:
- **Inactive state:** Gray outline, "+" icon prefix — clicking activates the component
- **Active state:** Indigo/blue background, checkmark icon — clicking scrolls to section
- **Remove:** Small "x" button on the right of active buttons, with `window.confirm()` prompt
- `no-print` class applied so buttons don't appear in printed output

### 5. InputSheet.tsx — Integrated Button Bar + Optional Components

Major additions to the InputSheet component:
- **Component state management:** `activeComponents` state tracks which components are shown, auto-detected from existing data on load
- **Sync effect:** Updates `activeComponents` when estimation data changes externally (e.g., Fill Test Data)
- **Toggle handler:** Activating adds component section; deactivating clears data with `delete updated[type]`
- **Scroll-to-section:** Uses `ref` on each component section div + `scrollIntoView({ behavior: 'smooth' })`
- **Generic change handler:** `handleComponentChange(type, items)` merges component data into `input_data`
- **Rendering:** `COMPONENT_CONFIGS.map()` conditionally renders ComponentTable for active components below Accessories
- **Visual distinction:** Component section headers use indigo-100 background with indigo-500 left border to differentiate from Openings/Accessories

### 6. Fill Test Data Updated

`handleFillTestData()` now includes sample data for all 4 components:
- Crane: EOT Crane, 10 MT capacity, Medium duty
- Mezzanine: Office Mezzanine, 4.5m clear height, concrete deck, 1 stair
- Partition: Internal Partition, Transverse direction, 6m height
- Canopy: Front Canopy, Lean-To type, 4m height, 3m width

### 7. Backend Validation Rules

Both `StoreEstimationRequest.php` and `UpdateEstimationRequest.php` updated with ~50 new validation rules:
- **Cranes:** 6 field rules + array validation, duty `in:L,M,H`, capacity/rail_centers `numeric min:0`
- **Mezzanines:** 15 field rules + array validation, double_welded `in:Yes,No`, numeric constraints
- **Partitions:** 12 field rules + array validation, direction `in:Longitudinal,Transverse`, numeric constraints
- **Canopies:** 16 field rules + array validation, frame_type `in:Roof Extension,Lean-To`, location `in:Front,Back,Left,Right,All Around`

### 8. Tests

**11 new tests** added to `tests/Feature/Api/ValidationTest.php`:

Validation tests (10):
- Crane: duty rejects invalid, capacity rejects string, array structure accepted
- Mezzanine: double_welded rejects invalid, array structure accepted
- Partition: direction rejects invalid, array structure accepted
- Canopy: frame_type rejects invalid, location rejects invalid, array structure accepted

Persistence test (1):
- Saves estimation with all 4 component arrays, fetches back, verifies all fields persist correctly

## What Was Skipped & Why

1. **Backend calculation services for components** — The VBA has calculation logic for crane beams, mezzanine joists, partition framing, and canopy structures. These are complex engineering calculations that will need careful porting with Excel comparison. Input capture was prioritized first.

2. **Dynamic row addition** — Currently fixed max rows (3 cranes, 3 mezzanines, 5 partitions, 5 canopies). An "Add Row" button could be added later if users need more instances.

3. **Component-specific output in Detail/FCPBS/SAL sheets** — The backend services already have stubs for some component categories (SALGenerator maps sales codes 2=Mezzanine, 3=Canopy, 4=Crane, 11=Partitions). Wiring up the full calculation pipeline is deferred.

## Status Table

| Item | Status |
|------|--------|
| Crane, Mezzanine, Partition, Canopy TypeScript interfaces | ✅ Complete |
| ComponentTableConfig.ts (4 component configs) | ✅ Complete |
| ComponentTable.tsx (generic reusable HotTable) | ✅ Complete |
| ComponentButtonBar.tsx (toggle buttons) | ✅ Complete |
| InputSheet.tsx (button bar + optional components) | ✅ Complete |
| Fill Test Data (all 4 components) | ✅ Complete |
| StoreEstimationRequest validation rules (+50) | ✅ Complete |
| UpdateEstimationRequest validation rules (+50) | ✅ Complete |
| ValidationTest (11 new tests) | ✅ Complete |
| Pint formatting | ✅ Complete |
| Full test suite (181 tests) | ✅ Passing |
| Frontend build (npm run build) | ✅ Clean |

## Test Results

```
Tests:    181 passed (423 assertions)
Duration: 4.83s
```

**Test breakdown:**
- Unit tests: 34 (InputParser, QuickEstCalculator, FCPBSGenerator, SALGenerator)
- Feature tests: 147 (Auth, DesignConfig, Estimation, Export, Validation, Caching, Filament, Models, Policies)

## Files Created/Modified

| File | Action |
|------|--------|
| `frontend/src/types/index.ts` | Modified (+4 interfaces, +4 InputData fields) |
| `frontend/src/components/estimations/ComponentTableConfig.ts` | Created |
| `frontend/src/components/estimations/ComponentTable.tsx` | Created |
| `frontend/src/components/estimations/ComponentButtonBar.tsx` | Created |
| `frontend/src/components/estimations/InputSheet.tsx` | Modified (button bar + component rendering) |
| `frontend/src/app/(protected)/estimations/[id]/page.tsx` | Modified (Fill Test Data expanded) |
| `app/Http/Requests/Api/StoreEstimationRequest.php` | Modified (+50 validation rules) |
| `app/Http/Requests/Api/UpdateEstimationRequest.php` | Modified (+50 validation rules) |
| `tests/Feature/Api/ValidationTest.php` | Modified (+11 tests) |
| `documentations/09-optional-components-ui.md` | Created |

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
1. Login → Open an estimation → See 4 toggle buttons above the grid ("+ Crane", "+ Mezzanine", etc.)
2. Click "+ Crane" → Crane sub-table appears below Accessories with indigo header, page scrolls to it
3. Fill in crane fields (description, capacity, duty dropdown) → verify auto-save
4. Click "✓ Crane" button → page scrolls back to crane section
5. Click "×" on crane button → confirm dialog → crane section removed, data cleared
6. Click "Test Data" → verify all 4 component buttons activate, sub-tables populate with sample data
7. Click Calculate → verify output tabs show data
8. Refresh page → verify component data persists, buttons auto-activate for non-empty components
