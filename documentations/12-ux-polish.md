# Iteration 12: User Experience Polish

## What Was Discussed & Planned

Four features to improve day-to-day workflow:
1. Estimation Clone — duplicate an estimation (copy input_data, reset to draft, clear results)
2. Revision Tracking — link estimations into revision chains via `parent_id`
3. Estimation Comparison — select 2 estimations, compare side-by-side
4. Bulk Export — select multiple estimations, download all PDFs as ZIP

## What Was Completed

### Feature 1: Estimation Clone
- **Backend**: `POST /api/estimations/{id}/clone` endpoint, `clone()` policy method, activity logging
- **Frontend**: Clone button on list page (per-row action) + detail page header
- **Filament**: Clone action in EstimationsTable with confirmation modal
- **Tests**: 10 tests in `CloneTest.php` (all passing)

### Feature 2: Revision Tracking
- **Database**: Migration adding `parent_id` foreign key to estimations table
- **Backend**: `POST /api/estimations/{id}/revision`, `GET /api/estimations/{id}/revisions`
- **Model**: `parent()`, `children()` relationships, `getRevisionChain()` method
- **Frontend**: RevisionHistory dropdown component (shows chain, navigate between revisions), "New Rev" button in header
- **Tests**: 10 tests in `RevisionTest.php` (all passing)

### Feature 3: Estimation Comparison
- **Backend**: `POST /api/estimations/compare` with exactly 2 IDs, returns both estimations with summary + input_data
- **Frontend**: Checkbox selection on list page, "Compare" button in selection toolbar, dedicated `/estimations/compare` page with:
  - Side-by-side estimation headers with status badges
  - Key metrics table (6 metrics) with delta column (absolute + percentage, color-coded)
  - Input differences table showing fields that differ between the two estimations
- **Tests**: 8 tests in `CompareTest.php` (all passing)

### Feature 4: Bulk Export
- **Backend**: `POST /api/estimations/bulk-export` with IDs (max 20) + sheet names, generates ZIP using `ZipArchive`
- **FormRequest**: `BulkExportRequest` with validation for ids and sheets
- **Frontend**: "Export ZIP" button in selection toolbar, downloads all 6 sheet PDFs per estimation
- **Tests**: 10 tests in `BulkExportTest.php` (all passing)

### Cross-Cutting
- Route registration: Collection-level routes (compare, bulk-export) registered BEFORE `apiResource` to avoid `{estimation}` catching slugs
- `revision_no` column added to Filament EstimationsTable
- `parent_id` added to EstimationResource API response
- Fixed Filament import: `Filament\Actions\Action` (not `Filament\Tables\Actions\Action`)
- Fixed `bulkExport()` return type to include `BinaryFileResponse`

## Files Created
| File | Description |
|------|-------------|
| `database/migrations/2026_02_11_190044_add_parent_id_to_estimations_table.php` | Migration |
| `app/Http/Requests/Api/BulkExportRequest.php` | Bulk export validation |
| `tests/Feature/Api/CloneTest.php` | 10 clone tests |
| `tests/Feature/Api/RevisionTest.php` | 10 revision tests |
| `tests/Feature/Api/CompareTest.php` | 8 comparison tests |
| `tests/Feature/Api/BulkExportTest.php` | 10 bulk export tests |
| `frontend/src/components/estimations/RevisionHistory.tsx` | Revision dropdown component |
| `frontend/src/app/(protected)/estimations/compare/page.tsx` | Comparison page |

## Files Modified
| File | Change |
|------|--------|
| `app/Http/Controllers/Api/EstimationController.php` | Added 5 methods: clone, createRevision, revisions, compare, bulkExport |
| `app/Policies/EstimationPolicy.php` | Added clone(), createRevision() |
| `app/Models/Estimation.php` | Added parent_id, parent/children relationships, getRevisionChain() |
| `app/Http/Resources/Api/EstimationResource.php` | Added parent_id |
| `database/factories/EstimationFactory.php` | Added parent_id, revision() state |
| `routes/api.php` | Added 5 new routes with correct ordering |
| `app/Filament/Resources/Estimations/Tables/EstimationsTable.php` | Added revision_no column, clone action, fixed imports |
| `frontend/src/types/index.ts` | Added parent_id, RevisionEntry, ComparisonEstimation |
| `frontend/src/lib/estimations.ts` | Added 5 API functions |
| `frontend/src/components/estimations/EstimationHeader.tsx` | Added Clone, New Rev buttons, RevisionHistory badge |
| `frontend/src/app/(protected)/estimations/[id]/page.tsx` | Added clone/revision handlers |
| `frontend/src/app/(protected)/estimations/page.tsx` | Added checkbox selection, toolbar, clone/compare/export handlers |

## Test Results
- **248 tests passing** (210 existing + 38 new)
- Pint: clean
- Frontend build: clean (no TypeScript errors)

## How to Test
1. **Clone**: Open any estimation > click "Clone" > verify new draft created with same inputs
2. **Clone from list**: Click "Clone" in the actions column > navigates to new estimation
3. **Revision**: Open estimation > click "New Rev" > verify R00 > R01 > R02 chain
4. **Revision history**: Click revision badge (e.g. "R00") > dropdown shows full chain > click to navigate
5. **Compare**: Select exactly 2 estimations via checkboxes > click "Compare" > side-by-side page
6. **Bulk Export**: Select 1+ estimations > click "Export ZIP" > downloads ZIP with PDFs organized by quote number
7. **Admin**: Visit `/admin/estimations` > verify revision_no column and clone action
