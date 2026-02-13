# Missing Features — Gap Analysis: web-php Reference vs Laravel App

> Compared `documentations/web-php/` (reference PHP implementation) against the current Laravel 12 + Next.js application.
> **16 missing features** identified — 6 high-impact, 6 medium-impact, 4 low-impact.

---

## Summary Table

| # | Feature | Impact | Complexity | Category |
|---|---------|--------|------------|----------|
| 1 | Liner/Ceiling Calculator | High | Medium | Calculation Engine |
| 2 | Fascia Type in Canopy Calculator | High | Low | Calculation Engine |
| 3 | RAWMAT Output Sheet | High | Medium | Output Sheets |
| 4 | Multi-Building Projects | High | High | Data Architecture |
| 5 | Excel/CSV Import | High | High | Import/Export |
| 6 | CSV/Excel Export | High | Medium | Import/Export |
| 7 | User Self-Registration (API) | Medium | Low | Authentication |
| 8 | Password Change (Frontend API) | Medium | Low | Authentication |
| 9 | Password Reset Flow | Medium | Medium | Authentication |
| 10 | API Token Management | Medium | Low | Authentication |
| 11 | Product/Inventory Admin CRUD | Medium | Medium | Admin Panel |
| 12 | Product Search API | Medium | Low | Admin Panel |
| 13 | Analytics API for Frontend | Low | Low | Analytics |
| 14 | Reports/Export Log Table | Low | Low | Database |
| 15 | Session Tracking Table | Low | Low | Database |
| 16 | Analytics Aggregation Table | Low | Low | Database |

---

## Feature #1 — Liner/Ceiling Calculator

| Attribute | Detail |
|-----------|--------|
| **Impact** | High |
| **Complexity** | Medium |
| **Reference File** | `documentations/web-php/src/Services/LinerCalculator.php` |
| **Current State** | No liner calculator exists in the Laravel app |
| **Where It Fits** | New service: `app/Services/Estimation/LinerCalculator.php`. Injected into `EstimationService.calculate()` pipeline after `PaintCalculator` (step 4) and before the first `FCPBSGenerator` run (step 5). Items merge into `$detailItems`. |

### What It Does

Calculates roof and/or wall liner materials, replicating the VBA logic from the Excel estimator.

### Liner Types

- **Roof Liner** — liner on roof underside only
- **Wall Liner** — liner on wall interior only
- **Both** — roof + wall

### Key Formulas

| Calculation | Formula |
|-------------|---------|
| **Waste Factor** | `1.075` (7.5% waste added to all areas) |
| **Roof Area** | `rafterLength * buildingLength * 1.12 - roofOpenings` then `* 1.075` |
| **Rafter Length** (if not given) | `buildingWidth * sqrt(1 + avgSlope^2)` where default slope = `0.1` |
| **Sidewall Area** | `buildingLength * (backEaveHeight + frontEaveHeight) * 1.1` |
| **Endwall Area** | `avgHeight * buildingWidth + (peakHeight - avgHeight) * buildingWidth / 2` |
| **Total Wall Area** | `sidewallArea + 2 * endwallArea * 1.1 - wallOpenings` then `* 1.075` |
| **Screws per m²** | `4` (main fixing screws) |
| **Stitch Screws per m²** | `0.5` (joint screws) |

### Screw Code Selection Logic

| Material Contains | Main Screw | Stitch Screw | Description |
|-------------------|-----------|--------------|-------------|
| `PUA` | SS4 | SS1 | Stainless long screw — PU Aluminum |
| `PUS` | CS4 | CS1 | Carbon long screw — PU Steel |
| `A` | SS2 | SS1 | Stainless — Aluminum |
| Default | CS2 | CS1 | Carbon steel |

### Default Weight Lookup

| Material Thickness | Weight (kg/m²) |
|-------------------|----------------|
| 0.5mm steel | 4.2 |
| 0.7mm steel | 5.9 |
| Default | 5.0 |

### Input Fields Required

| Field | Type | Description |
|-------|------|-------------|
| `liner_type` | dropdown | `None`, `Roof Liner`, `Wall Liner`, `Both` |
| `roof_liner_code` | text | Product code (e.g., `S5OW`) |
| `wall_liner_code` | text | Product code |
| `roof_liner_area` | number | Manual override (0 = auto-calculate) |
| `wall_liner_area` | number | Manual override (0 = auto-calculate) |
| `roof_openings_area` | number | m² to deduct from roof |
| `wall_openings_area` | number | m² to deduct from walls |

### Items Generated (Detail Sheet)

| Description | Code | Sales Code | Calculation |
|-------------|------|-----------|-------------|
| Header: "LINER / CEILING PANELS" | — | — | Header row |
| Roof liner sheeting | `{roof_liner_code}` | 18 | `roofArea` m² |
| Wall liner sheeting | `{wall_liner_code}` | 18 | `wallArea` m² |
| Main fixing screws | `CS2`/`SS2`/`CS4`/`SS4` | 18 | `area * 4` |
| Stitch screws | `CS1`/`SS1` | 18 | `area * 0.5` |

### Laravel Files to Create/Modify

| File | Action |
|------|--------|
| `app/Services/Estimation/LinerCalculator.php` | **Create** — new calculator service |
| `app/Services/Estimation/EstimationService.php` | **Modify** — inject LinerCalculator, add step 4.5 in pipeline |
| `frontend/src/components/estimations/InputSheetConfig.ts` | **Modify** — add LINER section with fields |
| `frontend/src/components/estimations/InputSheet.tsx` | **Modify** — handle new section |
| `tests/Feature/Services/Estimation/LinerCalculatorTest.php` | **Create** — unit tests |

---

## Feature #2 — Fascia Type in Canopy Calculator

| Attribute | Detail |
|-----------|--------|
| **Impact** | High |
| **Complexity** | Low |
| **Reference File** | `documentations/web-php/src/Services/CanopyCalculator.php` |
| **Current State** | `DetailGenerator.generateCanopyItems()` supports Canopy and Roof Extension. **Fascia is missing.** |
| **Where It Fits** | Add a third branch inside `generateCanopyItems()` in `app/Services/Estimation/DetailGenerator.php` |

### Current vs Expected Types

| Type | Current | Expected | Detection |
|------|---------|----------|-----------|
| Canopy | ✅ | ✅ | `frame_type` starts with `C` |
| Roof Extension | ✅ | ✅ | `frame_type` starts with `R` AND width ≤ 1.5m |
| **Fascia** | ❌ | ✅ | `frame_type` starts with `F` |

### How Fascia Differs from Canopy

| Aspect | Canopy | Fascia |
|--------|--------|--------|
| **Structural sizing** | Post index = `totalLoad * width² * bayWidth` | Post index = `windSpeed * (height + width) * bayWidth` |
| **Secondary members** | Purlins (horizontal roof) | **Girts** (vertical wall cladding) |
| **Girt lines** | N/A | `int((height + width) / 1.7) + 1` (min 3 if height ≤ 1.2m) |
| **Wind load** | `liveLoad + 0.15` | `windSpeed² / 20000` |
| **Design index** | `purlinDesignIndex` | `girtDesignIndex = 2 * windLoad * bayWidth²` |
| **Code lookup** | `getPurlinCode()` | `getGirtCode()` |
| **Sheeting** | Roof sheeting (horizontal) | **Wall sheeting** (vertical) |
| **Trims** | Eave trim or gutter | `TTS1` trim: `2 * totalLength + 4 * (height + width)` |

### Post Sizing Thresholds (Fascia)

| Post Index | Code |
|-----------|------|
| ≤ 2500 | IPEa |
| ≤ 3500 | UB2 |
| ≤ 6000 | UB2 |
| > 6000 | UB3 |

### Items Generated (Fascia only)

| Description | Code | Calculation |
|-------------|------|-------------|
| Fascia posts | `IPEa`/`UB2`/`UB3` | `height + width + 0.2` size, qty = total posts |
| Girts | `Z15G`–`Z35G` | Design index based |
| Girt clips | `CFClip` | `2 * girtQty` |
| Girt bolts | `HSB12` | `girtQty * 8` |
| Connections | `MFC1` | `totalPosts` |
| Connection bolts | `HSB16` | `8 * totalPosts` |
| Wall sheeting | `{wall_top_skin}` | `totalLength * (height + width)` m² |
| Trims | `TTS1` | `2 * totalLength + 4 * (height + width)` |
| Fasteners | screw code | `4 * wallArea` |

### Frontend Changes

Add `Fascia` to the `frame_type` dropdown options in `ComponentTableConfig.ts` for the canopy component.

### Laravel Files to Modify

| File | Action |
|------|--------|
| `app/Services/Estimation/DetailGenerator.php` | **Modify** — add Fascia branch in `generateCanopyItems()` |
| `frontend/src/components/estimations/ComponentTableConfig.ts` | **Modify** — add `Fascia` to canopy `frame_type` dropdown |
| `tests/Feature/Services/Estimation/DetailGeneratorComponentsTest.php` | **Modify** — add Fascia tests |

---

## Feature #3 — RAWMAT Output Sheet

| Attribute | Detail |
|-----------|--------|
| **Impact** | High |
| **Complexity** | Medium |
| **Reference File** | `documentations/web-php/templates/rawmat_view.php` |
| **Current State** | No RAWMAT sheet exists. Detail sheet shows individual line items without material aggregation. |
| **Where It Fits** | New generator `app/Services/Estimation/RawMatGenerator.php`, new API endpoint, new PDF template, new frontend tab |

### What It Does

Aggregates Detail sheet items by product code (`dbCode`), consolidating duplicate items into a procurement-friendly summary. For example, if `Z15G` purlins appear 5 times in Detail (for different building sections), RAWMAT shows one row with the total quantity.

### Aggregation Logic

```
For each Detail item (skip headers/separators):
  key = item.dbCode
  If key not in aggregated → create entry
  aggregated[key].totalUnits += item.quantity
  aggregated[key].totalWeight += item.totalWeight
  aggregated[key].sources.add(item.source)  // track which section generated it
```

### Material Categories

| Category | Code Prefixes |
|----------|--------------|
| Primary Steel | `BU`, `HR`, `CON`, `PL` |
| Secondary Steel | `Z`, `C`, `PURLIN`, `GIRT`, `EAV`, `BASE` |
| Roof/Wall Sheeting | `S5`, `S7`, `A5`, `A7`, `CORE`, `SHEET` |
| Fasteners & Bolts | `HSB`, `AB`, `BOLT`, `SS2`, `SS4`, `CS2`, `CS4` |
| Trim & Flashing | `TRIM`, `FLASH`, `RC`, `WC` |
| Doors & Windows | `DOOR`, `WINDOW`, `LOUVER` |
| Gutters & Downspouts | `GUTTER`, `DS`, `DOWNSPOUT` |
| Crane Components | `CRANE`, `RUNWAY`, `CR` |
| Mezzanine | `MEZZ`, `MZ` |
| Liner Panels | `LINER`, `PU` |
| Other | Everything else |

### Output Table Columns

| Column | Description |
|--------|-------------|
| No. | Row number |
| DB Code | Product/database code |
| Cost Code | FCPBS cost code |
| Description | Product description from MBSDB |
| Unit | Unit of measure |
| Quantity | Aggregated total |
| Unit Weight | Weight per unit (kg) |
| Total Weight | Aggregated weight (kg) |
| Category | Material category |
| Sources | Comma-separated list of originating sections |

### Summary Metrics (Top of View)

- Total Items (before aggregation)
- Unique Materials (after dedup)
- Total Weight (kg)
- Number of Categories

### CSV Export

Headers: `No., DB Code, Cost Code, Description, Unit, Quantity, Unit Weight, Total Weight, Category, Sources`

### Laravel Files to Create/Modify

| File | Action |
|------|--------|
| `app/Services/Estimation/RawMatGenerator.php` | **Create** — aggregation service |
| `app/Http/Controllers/Api/EstimationController.php` | **Modify** — add `rawmat()` and `exportRawmat()` methods |
| `routes/api.php` | **Modify** — add `/estimations/{id}/rawmat` and `/estimations/{id}/export/rawmat` |
| `resources/views/pdf/rawmat.blade.php` | **Create** — PDF template |
| Frontend: new tab in output sheets viewer | **Modify** — add RAWMAT tab |

---

## Feature #4 — Multi-Building Projects

| Attribute | Detail |
|-----------|--------|
| **Impact** | High |
| **Complexity** | High |
| **Reference Files** | `documentations/web-php/src/Models/Project.php`, `ProjectBuilding.php`, `config/schema.sql` |
| **Current State** | Each estimation is standalone. `project_name` is a text field on Estimation, no grouping. |
| **Where It Fits** | New `Project` model, migration, controller, policy, Filament resource. `Estimation` gains optional `project_id` foreign key. |

### Database Schema (web-php reference)

**`projects` table:**

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Auto-increment |
| user_id | FK → users | Owner |
| project_number | varchar(50) | Unique identifier |
| project_name | varchar(200) | Display name |
| customer_name | varchar(200) | Customer |
| location | varchar(200) | Project location |
| description | text | Notes |
| status | varchar(20) | `draft`, `in_progress`, `completed`, `archived` |
| created_at | timestamp | |
| updated_at | timestamp | |

**`estimations` table change:**

| Column | Type | Description |
|--------|------|-------------|
| project_id | FK → projects, nullable | Optional project grouping |

### API Endpoints (web-php reference)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/projects` | List user's projects (filters: status, search, limit, offset) |
| POST | `/api/projects` | Create project |
| GET | `/api/projects/{id}` | Show project with buildings summary |
| PUT | `/api/projects/{id}` | Update project |
| DELETE | `/api/projects/{id}` | Delete project |
| GET | `/api/projects/{id}/history` | Project audit trail (last 50 entries) |
| GET | `/api/projects/{id}/buildings` | List buildings in project |
| POST | `/api/projects/{id}/buildings/{bid}/duplicate` | Duplicate building |

### Project Summary Aggregation

```
summary = {
  building_count: estimations.count(),
  total_weight: sum(estimations.total_weight_mt),
  total_price: sum(estimations.total_price_aed),
  total_area: sum(floor_area from input_data)
}
```

### Search/Filter Logic

Search performs LIKE queries on `project_name`, `customer_name`, `project_number`. Filter by `status`.

### History Actions Logged

`created`, `updated`, `calculated`, `exported`, `deleted`, `building_added`, `building_updated`, `building_deleted`, `building_duplicated`

### Laravel Implementation Approach

Instead of duplicating `project_history` table, reuse Spatie Activity Log (already installed) with `performedOn($project)`.

### Laravel Files to Create/Modify

| File | Action |
|------|--------|
| `database/migrations/xxxx_create_projects_table.php` | **Create** |
| `database/migrations/xxxx_add_project_id_to_estimations.php` | **Create** |
| `app/Models/Project.php` | **Create** — with `HasFactory`, `LogsActivity`, `SoftDeletes` |
| `app/Models/Estimation.php` | **Modify** — add `project()` relationship, `project_id` fillable |
| `app/Http/Controllers/Api/ProjectController.php` | **Create** |
| `app/Http/Requests/Api/StoreProjectRequest.php` | **Create** |
| `app/Http/Requests/Api/UpdateProjectRequest.php` | **Create** |
| `app/Http/Resources/Api/ProjectResource.php` | **Create** |
| `app/Policies/ProjectPolicy.php` | **Create** |
| `routes/api.php` | **Modify** — add project routes |
| `app/Filament/Resources/ProjectResource.php` | **Create** — admin panel resource |
| Frontend: project list + detail pages | **Create** |
| `tests/Feature/Api/ProjectTest.php` | **Create** |

---

## Feature #5 — Excel/CSV Import

| Attribute | Detail |
|-----------|--------|
| **Impact** | High |
| **Complexity** | High |
| **Reference File** | `documentations/web-php/src/Services/ExcelImporter.php` |
| **Current State** | No estimation data import. Only reference data seeding from Excel. |
| **Where It Fits** | New service `app/Services/Import/EstimationImportService.php`, new API endpoint, frontend upload UI |

### Supported Formats

| Format | Extension | Detection |
|--------|-----------|-----------|
| Excel 2007+ | `.xlsx` | ZIP magic bytes (`PK`) |
| CSV | `.csv` | Default fallback |
| QuickEst Project | `.qep`, `.json` | Starts with `{` or `[` |

### Field Mapping (Excel → Input Data)

| Excel Label | Maps To | Notes |
|-------------|---------|-------|
| Project Name | `project_name` | |
| Building Name | `building_name` | |
| Customer | `customer_name` | |
| Project No | `quote_number` | |
| Building No | `building_no` | |
| Revision | `revision_no` | |
| Date | `estimation_date` | |
| Estimated By | `salesperson_code` | |
| Spans | `span_widths` | Format: `1@24`, `2@12` |
| Bays | `bay_spacing` | Format: `6@6`, `3@8+2@10` |
| Slopes | `left_roof_slope` / `right_roof_slope` | |
| Back Eave Height / BEH | `back_eave_height` | |
| Front Eave Height / FEH | `front_eave_height` | |
| Frame Type | `frame_type` | `Clear Span` or `Multi-Span` |
| Base Type | `base_type` | `Pinned Base` or `Fixed Base` |
| Min. Thickness | `min_thickness` | |
| Double Welded | `double_weld` | `Yes` or `No` |
| Left EW Type | `left_endwall_type` | |
| Right EW Type | `right_endwall_type` | |
| Bracing Type | `bracing_type` | `Cables`, `Rods`, `Angles` |
| BU Finish | `bu_finish` | |
| CF Finish | `cf_finish` | |
| Dead Load | `dead_load` | Numeric |
| Live Load (Purlin) | `live_load` | Numeric |
| Live Load (Frame) | `live_load_frame` | Numeric |
| Additional Load | `additional_load` | Numeric |
| Wind Speed | `wind_speed` | Numeric |
| Back Eave Condition | `back_eave_condition` | |
| Front Eave Condition | `front_eave_condition` | |
| Roof Panel Profile | `roof_panel_profile` | |
| Roof Top Skin | `roof_top_skin` | |
| Roof Core | `roof_core` | |
| Roof Bot Skin | `roof_bottom_skin` | |
| Wall Top Skin | `wall_top_skin` | |
| Wall Core | `wall_core` | |
| Wall Bot Skin | `wall_bottom_skin` | |
| Trim Sizes | `trim_size` | |
| Freight / Destination | `freight_destination` | |

### Default Values (when field is missing)

| Field | Default |
|-------|---------|
| `span_widths` | `1@24` |
| `bay_spacing` | `6@6` |
| `back_eave_height` | `8` |
| `front_eave_height` | `8` |
| `frame_type` | `Clear Span` |
| `base_type` | `Pinned Base` |
| `min_thickness` | `6` |
| `double_weld` | `No` |
| `bracing_type` | `Cables` |
| `dead_load` | `0.1` |
| `live_load` | `0.57` |
| `wind_speed` | `130` |
| `back_eave_condition` | `Gutter+Dwnspts` |
| `front_eave_condition` | `Gutter+Dwnspts` |
| `roof_panel_profile` | `M45-250` |
| `roof_top_skin` | `S5OW` |
| `roof_core` / `wall_core` | `-` |
| `trim_size` | `0.5 AZ` |

### Numeric Fields (auto-convert to float)

`back_eave_height`, `front_eave_height`, `min_thickness`, `dead_load`, `live_load`, `live_load_frame`, `additional_load`, `wind_speed`

### JSON/QEP Import Structure

```json
{
  "version": "...",
  "input": { "project_name": "...", "bay_spacing": "6@6", ... },
  "calculated": { ... },
  "preferences": { ... }
}
```

### Validation & Error Handling

- Returns `{ success: bool, data: array, errors: string[], warnings: string[] }`
- Errors: missing required fields, invalid format
- Warnings: fields using defaults, unrecognized fields

### Laravel Recommended Package

Use `maatwebsite/excel` (Laravel Excel) for XLSX/CSV parsing. Already widely used in Laravel ecosystem.

### Laravel Files to Create/Modify

| File | Action |
|------|--------|
| `app/Services/Import/EstimationImportService.php` | **Create** — field mapping, validation, defaults |
| `app/Http/Controllers/Api/EstimationController.php` | **Modify** — add `import()` method |
| `app/Http/Requests/Api/ImportEstimationRequest.php` | **Create** — file validation |
| `routes/api.php` | **Modify** — add `POST /estimations/import` |
| Frontend: upload dialog component | **Create** |
| `tests/Feature/Api/ImportTest.php` | **Create** |

---

## Feature #6 — CSV/Excel Export

| Attribute | Detail |
|-----------|--------|
| **Impact** | High |
| **Complexity** | Medium |
| **Reference Files** | `documentations/web-php/src/Models/BillOfMaterials.php` (toCsv), `ExcelImporter.php` (exportToCsv) |
| **Current State** | Only PDF export (6 sheets). No CSV or Excel export. |
| **Where It Fits** | New service `app/Services/Export/CsvExportService.php`, new routes alongside existing PDF exports |

### BOM CSV Export Format (BillOfMaterials::toCsv)

**Headers:**

```
Line,DB Code,Sales Code,Cost Code,Description,Size,Unit,Qty,Unit Wt,Total Wt,Unit Price,Total Price,Phase No
```

**Footer:**

```
,,,Total Weight,{total} kg
,,,Total Price,{total} AED
```

### Full Estimation CSV Export Format (ExcelImporter::exportToCsv)

```
QuickEst Export
Generated,{YYYY-MM-DD HH:mm:ss}

INPUT DATA
"Project Name","{value}"
"Building Name","{value}"
...

BILL OF MATERIALS
Line,DB Code,Description,Unit,Qty,Unit Weight,Total Weight,Total Price
1,S5OW,"Roof Sheeting",m2,240,5.2,1248,4960
...

SUMMARY
Total Weight,{value} kg
Total Price,{value} AED
Item Count,{value}
```

### Exportable Sheets

| Sheet | Source Data | Columns |
|-------|-----------|---------|
| Detail (BOM) | `results_data.detail` | Line, Code, Description, Size, Unit, Qty, Unit Wt, Total Wt, Rate, Total Price |
| FCPBS | `results_data.fcpbs.categories` | Category, Name, Weight, Material Cost, Labor Cost, Total Cost, Markup, Selling Price |
| SAL | `results_data.sal.items` | Code, Description, Weight, Cost, Markup, Price, Price/MT |
| BOQ | `results_data.boq.items` | No, Description, Weight, Supply Price, Transport, Charges, Total |
| RAWMAT | Generated from Detail | Code, Description, Unit, Qty, Unit Wt, Total Wt, Category |

### API Routes

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/estimations/{id}/export/csv` | Export as CSV (query: `sheet=detail\|fcpbs\|sal\|boq\|rawmat\|all`) |

### Laravel Implementation Pattern

```php
return response()->streamDownload(function () use ($csvData) {
    $handle = fopen('php://output', 'w');
    // UTF-8 BOM for Excel compatibility
    fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
    foreach ($csvData as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);
}, $filename, ['Content-Type' => 'text/csv']);
```

### Laravel Files to Create/Modify

| File | Action |
|------|--------|
| `app/Services/Export/CsvExportService.php` | **Create** |
| `app/Http/Controllers/Api/EstimationController.php` | **Modify** — add `exportCsv()` method |
| `routes/api.php` | **Modify** — add CSV export route |
| Frontend: add CSV option in export dialog | **Modify** |
| `tests/Feature/Api/CsvExportTest.php` | **Create** |

---

## Feature #7 — User Self-Registration (API)

| Attribute | Detail |
|-----------|--------|
| **Impact** | Medium |
| **Complexity** | Low |
| **Reference File** | `documentations/web-php/src/Services/AuthService.php` → `register()` |
| **Current State** | No self-registration. Admin creates users via Filament panel only. |
| **Where It Fits** | New method in `AuthController`, new route, new form request |

### Endpoint

`POST /api/register` (public, no auth required)

### Request Body

| Field | Type | Validation |
|-------|------|-----------|
| `name` | string | Required, max 100 |
| `email` | string | Required, valid email, unique |
| `password` | string | Required, min 8, confirmed |
| `password_confirmation` | string | Required |
| `company_name` | string | Optional, max 100 |
| `phone` | string | Optional, max 20 |

### Response

```json
{
  "message": "Registration successful.",
  "user": { "id": 1, "name": "...", "email": "...", "role": "user" },
  "token": "..."
}
```

### Business Rules

- New users always get `role = 'user'` and `status = 'active'`
- Auto-login after registration (return Sanctum token)
- Log activity: `"registered"`

### Laravel Files to Create/Modify

| File | Action |
|------|--------|
| `app/Http/Controllers/Api/AuthController.php` | **Modify** — add `register()` method |
| `app/Http/Requests/Api/RegisterRequest.php` | **Create** |
| `routes/api.php` | **Modify** — add `POST /register` (public) |
| Frontend: registration page | **Create** |
| `tests/Feature/Api/AuthTest.php` | **Modify** — add registration tests |

---

## Feature #8 — Password Change (Frontend API)

| Attribute | Detail |
|-----------|--------|
| **Impact** | Medium |
| **Complexity** | Low |
| **Reference File** | `documentations/web-php/src/Services/AuthService.php` → `changePassword()` |
| **Current State** | No password change endpoint. Users cannot change their own password from the frontend. |
| **Where It Fits** | New method in `AuthController` |

### Endpoint

`POST /api/password` (auth required)

### Request Body

| Field | Type | Validation |
|-------|------|-----------|
| `current_password` | string | Required, must match current |
| `new_password` | string | Required, min 8, confirmed |
| `new_password_confirmation` | string | Required |

### Response

```json
{ "message": "Password changed successfully." }
```

### Business Rules

- Verify current password with `Hash::check()`
- Update password with `Hash::make()`
- Log activity: `"changed password"`
- Optionally: revoke all other tokens

### Laravel Files to Create/Modify

| File | Action |
|------|--------|
| `app/Http/Controllers/Api/AuthController.php` | **Modify** — add `changePassword()` |
| `app/Http/Requests/Api/ChangePasswordRequest.php` | **Create** |
| `routes/api.php` | **Modify** — add `POST /password` |
| Frontend: password change form (e.g., in user profile/settings) | **Create** |
| `tests/Feature/Api/AuthTest.php` | **Modify** — add password change tests |

---

## Feature #9 — Password Reset Flow

| Attribute | Detail |
|-----------|--------|
| **Impact** | Medium |
| **Complexity** | Medium |
| **Reference File** | Standard Laravel feature (not in web-php but listed in pending.md) |
| **Current State** | No forgot/reset password flow from frontend. |
| **Where It Fits** | Laravel has built-in password reset via `password_reset_tokens` table (already in default migration) |

### Flow

1. User clicks "Forgot Password?" on login page
2. `POST /api/forgot-password` — sends reset email with token
3. User clicks link in email → frontend form
4. `POST /api/reset-password` — validates token, sets new password

### Endpoints

| Method | Endpoint | Body | Description |
|--------|----------|------|-------------|
| POST | `/api/forgot-password` | `{ email }` | Send reset link email |
| POST | `/api/reset-password` | `{ token, email, password, password_confirmation }` | Reset with token |

### Laravel Built-in Support

- `Password::sendResetLink(['email' => $email])`
- `Password::reset($credentials, fn($user, $pwd) => ...)`
- Uses `password_reset_tokens` table (already in default migration `0001_01_01_000000_create_users_table.php`)

### Laravel Files to Create/Modify

| File | Action |
|------|--------|
| `app/Http/Controllers/Api/PasswordResetController.php` | **Create** |
| `app/Http/Requests/Api/ForgotPasswordRequest.php` | **Create** |
| `app/Http/Requests/Api/ResetPasswordRequest.php` | **Create** |
| `routes/api.php` | **Modify** — add forgot/reset routes (public) |
| `.env` / `config/mail.php` | **Modify** — configure mail driver (Mailgun, SES, SMTP, etc.) |
| Frontend: forgot password + reset password pages | **Create** |
| `tests/Feature/Api/PasswordResetTest.php` | **Create** |

---

## Feature #10 — API Token Management

| Attribute | Detail |
|-----------|--------|
| **Impact** | Medium |
| **Complexity** | Low |
| **Reference File** | `documentations/web-php/src/Services/AuthService.php` → token methods, `config/schema.sql` → `api_tokens` |
| **Current State** | Tokens created at login only. No management endpoints. Users cannot list, create named tokens, or revoke specific tokens. |
| **Where It Fits** | New controller or extend AuthController. Laravel Sanctum already manages `personal_access_tokens` table. |

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/tokens` | List user's active tokens (name, created_at, last_used_at) |
| POST | `/api/tokens` | Create named token with optional abilities |
| DELETE | `/api/tokens/{id}` | Revoke specific token |

### Request Body (Create)

| Field | Type | Validation |
|-------|------|-----------|
| `name` | string | Required, max 100 |
| `abilities` | array | Optional, default `['*']` |
| `expires_at` | datetime | Optional |

### Response (Create)

```json
{
  "message": "Token created.",
  "token": "1|abc123...",
  "name": "My Integration",
  "abilities": ["*"]
}
```

### Sanctum Already Provides

- `$user->createToken($name, $abilities)` — create
- `$user->tokens()->get()` — list
- `$user->tokens()->where('id', $id)->delete()` — revoke
- `$request->user()->currentAccessToken()->delete()` — revoke current

### Laravel Files to Create/Modify

| File | Action |
|------|--------|
| `app/Http/Controllers/Api/TokenController.php` | **Create** |
| `routes/api.php` | **Modify** — add token routes |
| Frontend: token management UI (optional, can be admin-only) | **Create** |
| `tests/Feature/Api/TokenTest.php` | **Create** |

---

## Feature #11 — Product/Inventory Admin CRUD

| Attribute | Detail |
|-----------|--------|
| **Impact** | Medium |
| **Complexity** | Medium |
| **Reference File** | `documentations/web-php/src/Database/ProductLookup.php`, current models `MbsdbProduct.php`, `SsdbProduct.php`, `RawMaterial.php` |
| **Current State** | Products are seeded from Excel and read-only. No admin UI to view, edit, add, or remove products. |
| **Where It Fits** | New Filament resources for MBSDB Products, SSDB Products, Raw Materials, and Design Configurations |

### Models to Expose

| Model | Table | Fields to Manage |
|-------|-------|------------------|
| `MbsdbProduct` | `mbsdb_products` | code, description, unit, category, rate, rate_type, metadata (weight, surface_area, etc.) |
| `SsdbProduct` | `ssdb_products` | code, description, unit, category, rate, grade, metadata |
| `RawMaterial` | `raw_materials` | code, description, weight_per_sqm, unit, metadata |
| `DesignConfiguration` | `design_configurations` | category, key, value, label, sort_order, metadata |

### Filament Resources Needed

| Resource | Features |
|----------|----------|
| `MbsdbProductResource` | List (searchable, filterable by category), Create, Edit, bulk delete |
| `SsdbProductResource` | List (searchable, filterable by category/grade), Create, Edit |
| `RawMaterialResource` | List, Create, Edit |
| `DesignConfigurationResource` | List (grouped by category), Create, Edit |

### Important: Cache Invalidation

When products are modified, `CachingService` cache (24hr TTL) must be flushed:
- `Cache::tags(['reference-data'])->flush()` or `CachingService::clearReferenceCache()`

### Laravel Files to Create

| File | Action |
|------|--------|
| `app/Filament/Resources/MbsdbProductResource.php` | **Create** — with List, Create, Edit pages |
| `app/Filament/Resources/SsdbProductResource.php` | **Create** |
| `app/Filament/Resources/RawMaterialResource.php` | **Create** |
| `app/Filament/Resources/DesignConfigurationResource.php` | **Create** |

---

## Feature #12 — Product Search API

| Attribute | Detail |
|-----------|--------|
| **Impact** | Medium |
| **Complexity** | Low |
| **Reference File** | `documentations/web-php/src/Database/ProductLookup.php` → `searchProducts()`, `codeOf()` |
| **Current State** | `CachingService::getProductByCode()` only — exact code lookup. No search or reverse lookup. |
| **Where It Fits** | New controller or extend `DesignConfigurationController` |

### Endpoints

| Method | Endpoint | Query Params | Description |
|--------|----------|-------------|-------------|
| GET | `/api/products/search` | `q=search_term&category=optional` | Search MBSDB products by code or description |
| GET | `/api/products/{code}` | — | Get single product by code |
| GET | `/api/structural-steel/search` | `q=search_term` | Search SSDB products |

### Search Logic (from web-php)

```php
// Case-insensitive partial match on code OR description
$results = MbsdbProduct::where('code', 'LIKE', "%{$q}%")
    ->orWhere('description', 'LIKE', "%{$q}%")
    ->limit(50)
    ->get();
```

### Reverse Lookup: `codeOf(description)` → code

- Exact match first
- Fallback to partial match
- Returns `'None'` for "none" input
- Returns input string if not found

### Laravel Files to Create/Modify

| File | Action |
|------|--------|
| `app/Http/Controllers/Api/ProductController.php` | **Create** |
| `routes/api.php` | **Modify** — add product search routes |
| `tests/Feature/Api/ProductSearchTest.php` | **Create** |

---

## Feature #13 — Analytics API for Frontend

| Attribute | Detail |
|-----------|--------|
| **Impact** | Low |
| **Complexity** | Low |
| **Reference File** | `documentations/web-php/src/Api/Endpoints.php` → analytics endpoints |
| **Current State** | Stats computed in Filament widgets (admin-only). No API for frontend. |
| **Where It Fits** | New `AnalyticsController` |

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/analytics/dashboard` | User's stats: estimation count, total weight, total price, recent estimations |
| GET | `/api/analytics/data` | Monthly trends (last 12 months), by status breakdown, by category |

### Response Structure (dashboard)

```json
{
  "stats": {
    "total_estimations": 45,
    "total_weight_mt": 1250.5,
    "total_price_aed": 4500000,
    "draft_count": 10,
    "calculated_count": 30,
    "finalized_count": 5
  },
  "recent_estimations": [ ... ]
}
```

### Response Structure (data)

```json
{
  "by_status": [
    { "status": "draft", "count": 10 },
    { "status": "calculated", "count": 30 }
  ],
  "monthly": [
    { "month": "2025-01", "count": 5, "total_weight": 120.5 }
  ]
}
```

### Laravel Files to Create

| File | Action |
|------|--------|
| `app/Http/Controllers/Api/AnalyticsController.php` | **Create** |
| `routes/api.php` | **Modify** — add analytics routes |
| `tests/Feature/Api/AnalyticsTest.php` | **Create** |

---

## Feature #14 — Reports/Export Log Table

| Attribute | Detail |
|-----------|--------|
| **Impact** | Low |
| **Complexity** | Low |
| **Reference File** | `documentations/web-php/config/schema.sql` → `reports` table |
| **Current State** | Exports happen but are not logged. Activity log captures "exported X PDF" but no dedicated report tracking. |
| **Where It Fits** | New migration + model. Log entries created automatically in export methods. |

### Schema

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| user_id | FK → users | Who exported |
| estimation_id | FK → estimations, nullable | Which estimation |
| report_type | varchar(50) | `pdf`, `csv`, `excel`, `fcpbs`, `rawmat` |
| sheet_name | varchar(50) | `detail`, `boq`, `sal`, `fcpbs`, `jaf`, `recap`, `rawmat` |
| filename | varchar(255) | Generated filename |
| created_at | timestamp | When exported |

### Usage

In every export method, after generating the file:
```php
Report::create([
    'user_id' => $request->user()->id,
    'estimation_id' => $estimation->id,
    'report_type' => 'pdf',
    'sheet_name' => 'detail',
    'filename' => $filename,
]);
```

### Laravel Files to Create/Modify

| File | Action |
|------|--------|
| `database/migrations/xxxx_create_reports_table.php` | **Create** |
| `app/Models/Report.php` | **Create** |
| `app/Http/Controllers/Api/EstimationController.php` | **Modify** — log exports |
| `app/Filament/Resources/ReportResource.php` | **Create** — admin view of export history |

---

## Feature #15 — Session Tracking Table

| Attribute | Detail |
|-----------|--------|
| **Impact** | Low |
| **Complexity** | Low |
| **Reference File** | `documentations/web-php/config/schema.sql` → `sessions` table |
| **Current State** | No session tracking. Sanctum tokens exist but no IP/user-agent/expiry tracking. |
| **Where It Fits** | Laravel has built-in session table support via `SESSION_DRIVER=database` |

### Schema (web-php reference)

| Column | Type | Description |
|--------|------|-------------|
| id | varchar(64) PK | Session ID |
| user_id | FK → users | |
| ip_address | varchar(45) | Client IP |
| user_agent | varchar(255) | Browser string |
| payload | text | Session data |
| created_at | timestamp | |
| expires_at | timestamp | Default: 7 days |

### Laravel Built-in Solution

1. Run `php artisan session:table` to generate migration
2. Set `SESSION_DRIVER=database` in `.env`
3. Laravel automatically manages `sessions` table

### Laravel Files to Modify

| File | Action |
|------|--------|
| `.env` | Set `SESSION_DRIVER=database` |
| `database/migrations/xxxx_create_sessions_table.php` | **Create** via artisan |

---

## Feature #16 — Analytics Aggregation Table

| Attribute | Detail |
|-----------|--------|
| **Impact** | Low |
| **Complexity** | Low |
| **Reference File** | `documentations/web-php/config/schema.sql` → `analytics` table |
| **Current State** | All stats computed on-the-fly in Filament widgets. Works fine at current scale. |
| **Where It Fits** | Pre-computed statistics for performance at scale |

### Schema (web-php reference)

| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | |
| user_id | FK → users, nullable | Per-user or system-wide |
| metric_name | varchar(100) | e.g., `monthly_estimations`, `total_weight` |
| metric_value | decimal(15,2) | Numeric value |
| metric_data | text (JSON) | Complex metrics |
| period_start | date | Period start |
| period_end | date | Period end |
| created_at | timestamp | |

### When to Implement

Only needed when widget queries become slow (hundreds of thousands of estimations). At current scale, on-the-fly computation is fine. Consider implementing as a scheduled job:

```php
// app/Console/Commands/AggregateAnalytics.php
// Runs daily via scheduler
$this->call('analytics:aggregate');
```

### Laravel Files to Create

| File | Action |
|------|--------|
| `database/migrations/xxxx_create_analytics_table.php` | **Create** |
| `app/Models/AnalyticsMetric.php` | **Create** |
| `app/Console/Commands/AggregateAnalytics.php` | **Create** — scheduled command |
| `routes/console.php` | **Modify** — schedule daily |

---

## Cross-Reference with pending.md

| pending.md Item | Maps to Feature # |
|-----------------|-------------------|
| Item 7: Admin company logo/favicon settings | Not in web-php gap (already partial via PdfSettings) |
| Item 8: Robust/rich reporting features | #14 Reports Log + #13 Analytics API |
| Item 12: Password change for users | #8 Password Change |
| Item 13: Admin self-revocation prevention | Not in web-php gap (admin safeguard) |
| Item 14: Password reset from frontend | #9 Password Reset Flow |
| Item 15: Currency conversion system | Not in web-php gap (custom feature) |
| Item 16: Inventory CRUD for admin | #11 Product/Inventory Admin CRUD |
| Data Import/Export item | #5 Excel/CSV Import + #6 CSV/Excel Export |
