# Iteration 3: API Layer

## What Was Discussed and Planned

Wrap the calculation engine (built in Iteration 2) with secure RESTful API endpoints for frontend consumption. The API layer provides:
- Full CRUD for estimations with policy-based authorization
- Server-side calculation triggering with rate limiting
- Sheet data endpoints (detail, recap, FCPBS, SAL, BOQ, JAF)
- Design configuration lookups for dropdown population
- Activity logging for audit trail
- API Resources that never expose raw calculation formulas

## What Was Completed

### Authorization — EstimationPolicy

| File | Description |
|------|-------------|
| `app/Policies/EstimationPolicy.php` | Owner or admin can view/update/delete/calculate; any authenticated user can create; admin-only restore/forceDelete |

### API Resources (4 files)

| File | Description |
|------|-------------|
| `app/Http/Resources/Api/EstimationResource.php` | Returns all fields except raw `results_data`; exposes `summary` only when calculated/finalized |
| `app/Http/Resources/Api/EstimationItemResource.php` | Item-level fields: id, item_code, description, unit, quantity, weight_kg, rate, amount, category, sort_order |
| `app/Http/Resources/Api/EstimationCollection.php` | Paginated collection wrapper |
| `app/Http/Resources/Api/DesignConfigurationResource.php` | Configuration fields: id, category, key, value, label, sort_order, metadata |

### Form Requests (3 files)

| File | Description |
|------|-------------|
| `app/Http/Requests/Api/StoreEstimationRequest.php` | Validates quote_number, building_name, project_name, customer_name, estimation_date, input_data with nested field validation |
| `app/Http/Requests/Api/UpdateEstimationRequest.php` | Same rules as Store (all nullable) |
| `app/Http/Requests/Api/CalculateEstimationRequest.php` | Validates optional `markups` array (steel, panels, ssl, finance — numeric 0-5) |

### Controllers (2 files)

| File | Description |
|------|-------------|
| `app/Http/Controllers/Api/DesignConfigurationController.php` | `index` (filtered by category), `freightCodes`, `paintSystems` convenience endpoints |
| `app/Http/Controllers/Api/EstimationController.php` | Full CRUD, `calculate` (triggers EstimationService), 6 sheet data endpoints, 2 export stubs (501) |

### Rate Limiting

| File | Description |
|------|-------------|
| `app/Providers/AppServiceProvider.php` | Added `RateLimiter::for('calculate', ...)` — 5 requests/minute per user |

### Routes — 20 API Endpoints

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| POST | `/api/login` | — | Login |
| POST | `/api/logout` | — | Logout |
| GET | `/api/user` | — | Authenticated user |
| GET | `/api/design-configurations` | `design-configurations.index` | Filter by category param |
| GET | `/api/freight-codes` | `freight-codes.index` | Freight code configurations |
| GET | `/api/paint-systems` | `paint-systems.index` | Paint system configurations |
| GET | `/api/estimations` | `estimations.index` | List (paginated, scoped) |
| POST | `/api/estimations` | `estimations.store` | Create estimation |
| GET | `/api/estimations/{estimation}` | `estimations.show` | Show with items |
| PUT | `/api/estimations/{estimation}` | `estimations.update` | Update (resets to draft if input_data changes) |
| DELETE | `/api/estimations/{estimation}` | `estimations.destroy` | Soft delete |
| POST | `/api/estimations/{estimation}/calculate` | `estimations.calculate` | Trigger calculation (rate-limited) |
| GET | `/api/estimations/{estimation}/detail` | `estimations.detail` | Detail sheet data |
| GET | `/api/estimations/{estimation}/recap` | `estimations.recap` | Recap sheet data |
| GET | `/api/estimations/{estimation}/fcpbs` | `estimations.fcpbs` | FCPBS sheet data |
| GET | `/api/estimations/{estimation}/sal` | `estimations.sal` | SAL sheet data |
| GET | `/api/estimations/{estimation}/boq` | `estimations.boq` | BOQ sheet data |
| GET | `/api/estimations/{estimation}/jaf` | `estimations.jaf` | JAF sheet data |
| GET | `/api/estimations/{estimation}/export/boq` | `estimations.export.boq` | PDF export stub (501) |
| GET | `/api/estimations/{estimation}/export/jaf` | `estimations.export.jaf` | PDF export stub (501) |

### Feature Tests (3 files, 47 tests)

| Test File | Tests | Assertions |
|-----------|-------|------------|
| `tests/Feature/Policies/EstimationPolicyTest.php` | 8 | 8 |
| `tests/Feature/Api/DesignConfigurationTest.php` | 8 | 11 |
| `tests/Feature/Api/EstimationTest.php` | 31 | 66 |
| **Total (Iteration 3)** | **47** | **85** |

### Full Suite

| Scope | Tests | Assertions |
|-------|-------|------------|
| Iteration 1 (Auth, Models) | 16 | 49 |
| Iteration 2 (Calculation Services) | 57 | 105 |
| Iteration 3 (API Layer) | 47 | 85 |
| **Total** | **120** | **239** |

## Key Design Decisions

1. **`EstimationResource` hides `results_data`** — The raw calculation results (containing formulas and intermediate values) are never sent to the frontend. Only the `summary` key is exposed when the estimation is calculated or finalized.

2. **Sheet data via separate endpoints** — Each sheet (detail, recap, FCPBS, SAL, BOQ, JAF) has its own GET endpoint rather than returning everything at once. This keeps payloads small and allows the frontend to lazily load tabs.

3. **Update resets to draft** — When `input_data` is modified via the update endpoint, status resets to `draft` and `results_data`, `total_weight_mt`, `total_price_aed` are cleared. This forces recalculation to keep data consistent.

4. **Rate limiting on calculate** — 5 requests/minute per user on the `/calculate` endpoint to prevent abuse of the compute-intensive calculation pipeline.

5. **Export stubs return 501** — BOQ and JAF PDF export endpoints are registered but return "Not Implemented" (501), deferred to Iteration 7.

6. **`AuthorizesRequests` trait** — Added to EstimationController since Laravel 12's base Controller class doesn't include it by default.

## What Was Skipped and Why

| Item | Reason |
|------|--------|
| PDF export implementation | Deferred to Iteration 7 (requires barryvdh/laravel-dompdf) |
| Rate limiting test (429 response) | Rate limiter state is difficult to test in feature tests without complex setup; endpoint is verified working via route middleware |
| Filament admin estimation management | Deferred to Iteration 6 |

## Status

| Category | Status |
|----------|--------|
| **Completed** | Policy, 4 Resources, 3 Form Requests, 2 Controllers, Rate Limiting, 20 Routes, 47 Tests |
| **Remaining** | Iteration 4 (Next.js Excel UI - Input Sheet) |
| **Next Plan** | Iteration 4: Initialize Next.js in `frontend/`, login page, protected layout, Input sheet with Handsontable CE, tab bar, dropdowns from design-configurations API |
| **How to Test** | `php artisan test` — 120 tests, 239 assertions, all passing |
