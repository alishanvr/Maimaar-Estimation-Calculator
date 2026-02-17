# Currency Conversion Analysis: Changing Base Currency to PKR

> **Date:** February 2026
> **Status:** Analysis Complete
> **Recommendation:** Use display-time conversion (Approach 2) - already implemented

---

## Table of Contents

1. [Current System Architecture](#1-current-system-architecture)
2. [Approach 1: Change Storage Currency to PKR](#2-approach-1-change-storage-currency-to-pkr)
3. [Approach 2: Display-Time Conversion (Recommended)](#3-approach-2-display-time-conversion-recommended)
4. [Comparison Summary](#4-comparison-summary)
5. [Hardcoded Currency Locations](#5-hardcoded-currency-locations)
6. [How to Use the Existing Feature for PKR](#6-how-to-use-the-existing-feature-for-pkr)

---

## 1. Current System Architecture

### Base Currency: AED (UAE Dirham)

The entire system was built with AED as the single, universal currency for all stored values and calculations. This is deeply embedded across every layer.

### Database Schema

All monetary columns store values in AED:

| Table | Column | Type | Purpose |
|-------|--------|------|---------|
| `estimations` | `total_price_aed` | `decimal(14,2)` | Total estimation price in AED |
| `estimation_items` | `rate` | `decimal(12,4)` | Unit rate in AED |
| `estimation_items` | `amount` | `decimal(14,2)` | Line item total in AED |
| `mbsdb_products` | `rate` | `decimal:4` | Product unit price in AED |
| `ssdb_products` | `rate` | `decimal:4` | Product unit price in AED |
| `raw_materials` | `rate` | `decimal:4` | Raw material unit price in AED |

### Calculation Pipeline

The estimation engine runs a 9-step pipeline, all operating in AED:

```
Step 1: InputParserService      - Parse 156 input fields
Step 2: DetailGenerator         - Generate bill of materials (rates in AED)
Step 3: RoofMonitorCalculator   - Roof monitor items (AED)
Step 4: PaintCalculator         - Paint cost items (AED)
Step 5: FCPBSGenerator          - Financial breakdown (all AED)
Step 6: FreightCalculator       - Freight/container costs (AED)
Step 7: FCPBSGenerator          - Regenerate with freight (AED)
Step 8: SALGenerator            - Sales summary (AED)
Step 9: BOQGenerator            - Bill of quantities (AED)
Step 10: JAFGenerator           - Job acceptance form (AED + USD via /3.67)
```

Every intermediate value, rate lookup, subtotal, and markup calculation uses AED. The generators read product rates (in AED) from the database, multiply by quantities, apply markups, and produce AED totals.

### Results Data (JSON)

The `results_data` column on the `estimations` table stores the entire calculation output as a large nested JSON structure. This JSON contains hundreds of monetary values, all in AED:

```json
{
  "summary": {
    "total_price_aed": 424933.00,
    "fob_price_aed": 380000.00,
    "price_per_mt": 12500.00
  },
  "jaf": {
    "pricing": {
      "supply_price_aed": 424933.00,
      "erection_price_aed": 0,
      "total_contract_aed": 424933.00,
      "contract_value_usd": 115786.37
    }
  },
  "fcpbs": { "categories": [ { "selling_price": 50000.00 }, ... ] },
  "sal": { "items": [ { "cost": 1200.00, "price": 1500.00, "price_per_mt": 4500.00 }, ... ] },
  "boq": { "items": [ { "unit_rate": 750.00, "amount": 15000.00 }, ... ] },
  "detail": { "items": [ { "rate": 12.50, "amount": 500.00 }, ... ] },
  "rawmat": { "items": [ { "unit_rate": 8.50, "total_cost": 340.00 }, ... ] }
}
```

### Reference Data (Seeded from Excel)

Product data is seeded from an Excel file via `ReferenceDataSeeder.php`. The Excel columns are explicitly labeled in AED:

- Column G: **"Price AED/Unit"** (for MBSDB products, line 44)
- Column G: **"Price AED/Unit"** (for SSDB products, line 105)

### USD Conversion (JAFGenerator)

There is one intentional non-AED value in the system. The JAF (Job Acceptance Form) includes a USD contract value calculated via a hardcoded AED-to-USD peg rate:

```php
// JAFGenerator.php, line 100
$contractValueUSD = ($totalPrice + $erectionPrice) / 3.67;
```

This is the AED/USD fixed exchange rate (UAE Dirham is pegged to the US Dollar). This value is stored separately as `contract_value_usd` and displayed as "Contract Value (USD)" in both the frontend and PDF.

---

## 2. Approach 1: Change Storage Currency to PKR

> **Verdict: NOT RECOMMENDED - High risk, massive effort, zero additional benefit over Approach 2**

This approach would mean changing the database to store all values in PKR instead of AED. Every calculation, every rate, every stored value would be in PKR.

### What Would Need to Change

#### 2.1 Database Migrations

- Rename column `total_price_aed` to `total_price_pkr` (or a generic name)
- Update all existing data: multiply every stored monetary value by the AED-to-PKR rate
- All 3 product tables (`mbsdb_products`, `ssdb_products`, `raw_materials`) rates need conversion

#### 2.2 Product Data (700+ rows)

All product rates in the database are in AED. Every single product rate would need to be multiplied by the AED-to-PKR exchange rate. This is a one-time migration but introduces floating-point drift.

For example, a product rate of `12.5000 AED` at a rate of ~76.5 would become `956.2500 PKR`. But what happens when the exchange rate changes? The stored PKR values become stale instantly.

#### 2.3 Calculation Pipeline (13 files)

Every generator and calculator in `app/Services/Estimation/` operates with AED values:

| File | Impact |
|------|--------|
| `EstimationService.php` | Orchestrator - pipeline flow unchanged, but all values are now PKR |
| `InputParserService.php` | Parses input, may contain hardcoded AED assumptions |
| `DetailGenerator.php` | Reads product rates (now PKR), generates line items |
| `FCPBSGenerator.php` | Financial categories, markups, selling prices |
| `SALGenerator.php` | Sales summary with cost/price columns |
| `BOQGenerator.php` | Bill of quantities with unit rates |
| `JAFGenerator.php` | **CRITICAL**: Hardcoded `/ 3.67` assumes AED input |
| `RawMatGenerator.php` | Raw material costing |
| `FreightCalculator.php` | Freight and container costs |
| `PaintCalculator.php` | Paint cost calculations |
| `RoofMonitorCalculator.php` | Roof monitor items |
| `QuickEstCalculator.php` | Quick estimation mode |
| `CachingService.php` | Caches product lookups |

#### 2.4 JAFGenerator - The Critical Blocker

```php
// Line 100 - This breaks immediately
$contractValueUSD = ($totalPrice + $erectionPrice) / 3.67;
```

This line assumes `$totalPrice` is in AED. The value `3.67` is the AED/USD peg rate. If `$totalPrice` is now in PKR, this calculation produces garbage. You would need to:

1. First convert PKR back to AED (divide by PKR rate)
2. Then divide by 3.67 to get USD
3. Or maintain a separate PKR-to-USD rate

This introduces unnecessary complexity and a new source of errors.

#### 2.5 Results Data Migration

The `results_data` JSON column contains hundreds of nested monetary values across 6+ output sheets. A migration would need to:

1. Read every estimation's `results_data` JSON
2. Walk through every nested key containing a monetary value
3. Multiply each by the AED-to-PKR rate
4. Write back the modified JSON

This is extremely fragile because:
- The JSON structure varies between estimation versions
- There's no schema defining which keys are monetary vs. non-monetary
- Keys like `weight_kg`, `quantity`, `markup_percent` must NOT be converted
- Missing or null values need special handling
- The conversion must be atomic (all or nothing)

#### 2.6 Factory & Seeder Updates

- `EstimationFactory.php`: Contains hardcoded AED test values (e.g., `424933.00`)
- `ReferenceDataSeeder.php`: Reads from Excel labeled "Price AED/Unit"
- All factories generating monetary data need updating

#### 2.7 Frontend (10+ components)

Every frontend component that displays prices would need updating since the context changes from AED to PKR.

#### 2.8 PDF Templates (7 files)

All Blade templates with AED labels and formatting.

#### 2.9 Filament Admin (2+ files)

Table and infolist files with AED column references.

### Risk Assessment for Approach 1

| Risk | Severity | Description |
|------|----------|-------------|
| Data corruption | **CRITICAL** | One wrong conversion in results_data JSON destroys estimation history |
| Calculation errors | **HIGH** | Any generator assuming AED input produces wrong results |
| JAF USD conversion | **HIGH** | Hardcoded `/3.67` produces garbage with PKR input |
| Exchange rate drift | **HIGH** | Stored PKR values become stale as PKR/AED rate changes |
| Irreversibility | **HIGH** | Rolling back requires another full data migration |
| Test breakage | **MEDIUM** | All test assertions with hardcoded AED values fail |
| Floating-point drift | **MEDIUM** | Repeated currency conversions accumulate rounding errors |
| Incomplete migration | **MEDIUM** | Missing even one monetary field corrupts that sheet |

### Effort Estimate for Approach 1

- **Backend changes**: 15-20 files, including all 13 estimation generators
- **Data migration**: Custom migration script for `results_data` JSON + all product tables
- **Frontend changes**: 10+ components
- **PDF templates**: 7 Blade files
- **Tests**: Rewrite ~50+ test assertions
- **Estimated time**: 2-3 weeks of careful work + extensive testing
- **Risk of bugs**: Very high - any missed monetary field silently produces wrong values

---

## 3. Approach 2: Display-Time Conversion (Recommended)

> **Verdict: RECOMMENDED - Already implemented, zero risk, fully reversible**

This approach keeps AED as the storage and calculation currency. All conversions happen at the moment of display (rendering). The admin selects PKR as the display currency, sets an exchange rate, and the system multiplies all displayed values by that rate.

### How It Works

```
[Database: AED] --> [CurrencyService.convert()] --> [Display: PKR]
```

1. All calculations continue in AED (unchanged, battle-tested)
2. When rendering prices (frontend, PDF, admin panel), values are multiplied by the exchange rate
3. Labels dynamically show the selected currency code (e.g., "PKR" instead of "AED")

### Already Implemented Components

The full display-time currency system is already built and tested:

#### Backend
- `app/Services/CurrencyService.php` - Core service with `convert()`, `format()`, exchange rate management
- `app/Console/Commands/FetchExchangeRates.php` - Daily API fetch command
- `app/Filament/Pages/CurrencySettings.php` - Admin settings page
- `routes/console.php` - Daily schedule for rate fetching

#### API
- `app/Http/Controllers/Api/AppSettingsController.php` - Returns `display_currency`, `currency_symbol`, `exchange_rate`

#### Frontend
- `frontend/src/hooks/useCurrency.ts` - `useCurrency()` hook with `convert()`, `format()`, `label()` helpers
- `frontend/src/contexts/BrandingContext.tsx` - Extended with currency fields
- `frontend/src/lib/formatters.ts` - `formatCurrency()`, `formatCurrencyPerMT()`
- All 10 frontend components updated to use dynamic currency

#### PDF Templates
- All 7 Blade templates use `$currencyCode` and `$exchangeRate` for dynamic labels and conversion

#### Admin Panel
- EstimationsTable and EstimationInfolist use dynamic labels and conversion

#### Tests
- 21 dedicated currency tests covering all scenarios
- All existing tests continue to pass (no regressions)

### What the Admin Does to Switch to PKR

1. Go to `/admin/currency-settings`
2. Select "PKR - Pakistani Rupee" from the Display Currency dropdown
3. Either:
   - Click "Fetch Latest Rates" to get live PKR rate from API
   - Or manually enter a PKR exchange rate in the Manual Overrides section
4. Click Save

That's it. Every price across the entire application (frontend, PDF exports, admin panel) now displays in PKR.

### Risk Assessment for Approach 2

| Risk | Severity | Description |
|------|----------|-------------|
| Data corruption | **NONE** | Database is never modified |
| Calculation errors | **NONE** | Calculation pipeline is untouched |
| Irreversibility | **NONE** | Switch back to AED anytime (rate = 1.0) |
| Exchange rate drift | **LOW** | Admin can update rate anytime; daily auto-fetch available |
| Display rounding | **NEGLIGIBLE** | Standard `number_format()` handles display rounding |

---

## 4. Comparison Summary

| Aspect | Approach 1 (Storage to PKR) | Approach 2 (Display-Time) |
|--------|---------------------------|--------------------------|
| **Database changes** | Column renames + data migration | None |
| **Calculation engine** | All 13 generators modified | Untouched |
| **Data migration** | Complex JSON walking for results_data | None |
| **Risk of data loss** | High | Zero |
| **Reversibility** | Requires another migration | One admin click |
| **Implementation effort** | 2-3 weeks | Already done |
| **Test impact** | 50+ assertions rewritten | All pass as-is |
| **JAF USD handling** | Complex workaround needed | Works naturally |
| **Exchange rate updates** | Stored values become stale | Real-time at display |
| **Multi-currency support** | Would need another migration per currency | Change dropdown selection |
| **Accuracy** | Rounding errors compound over conversions | Single conversion at display |

---

## 5. Hardcoded Currency Locations

For future reference, these are all locations in the codebase where AED is explicitly referenced:

### Backend Code

| File | Location | Reference | Notes |
|------|----------|-----------|-------|
| `JAFGenerator.php` | Line 100 | `/ 3.67` | AED-to-USD peg rate. Must NOT be changed. |
| `CurrencyService.php` | Line 18 | `open.er-api.com/v6/latest/AED` | API fetches rates relative to AED |
| `CurrencyService.php` | Lines 76-78 | `if ($currency === 'AED') return 1.0` | AED is identity rate |

### Database

| File | Location | Reference |
|------|----------|-----------|
| `create_estimations_table.php` | Line 29 | Column named `total_price_aed` |
| `EstimationFactory.php` | Lines 92-147 | Hardcoded AED test values (424933.00) |
| `ReferenceDataSeeder.php` | Lines 44, 105 | Excel column labeled "Price AED/Unit" |

### Models

| File | Reference |
|------|-----------|
| `Estimation.php` | `total_price_aed` in fillable, casts, select queries |
| `Project.php` | `$estimations->sum('total_price_aed')` |
| `EstimationItem.php` | `rate` and `amount` columns (implicitly AED) |
| `MbsdbProduct.php` | `rate` cast as `decimal:4` (implicitly AED) |
| `SsdbProduct.php` | `rate` cast as `decimal:4` (implicitly AED) |

### Frontend & PDF

All hardcoded "AED" strings in frontend components and PDF templates have already been replaced with dynamic currency display via the currency feature. The only remaining hardcoded USD reference is in JAFSheet.tsx for "Contract Value (USD)" which is intentionally kept as-is.

---

## 6. How to Use the Existing Feature for PKR

### Setup Steps

1. **Navigate to Currency Settings**: `/admin/currency-settings`
2. **Select Display Currency**: Choose "PKR - Pakistani Rupee"
3. **Set Exchange Rate** (choose one method):
   - **Automatic**: Click "Fetch Latest Rates" to pull live rates from the exchange rate API
   - **Manual Override**: Enter a specific PKR rate (e.g., `76.50`) in the Manual Overrides section. Manual overrides always take precedence over API rates.
4. **Save**: Click the Save button

### Automatic Rate Updates

The system fetches exchange rates daily via `currency:fetch-rates` artisan command (scheduled in `routes/console.php`). Manual overrides are never overwritten by API fetches.

### Switching Back

To revert to AED display:
1. Go to `/admin/currency-settings`
2. Select "AED - UAE Dirham"
3. Save

Exchange rate automatically becomes 1.0 for AED. No data is lost. No migration needed.

### Important Notes

- **Database values remain in AED** - this is by design and ensures calculation accuracy
- **Exchange rate changes apply instantly** - no recalculation needed, cache is flushed on save
- **PDF exports use the current rate** - PDFs generated reflect the exchange rate at time of export
- **The JAF "Contract Value (USD)" is unaffected** - it always shows USD via the hardcoded AED/USD peg

---

## Conclusion

**Approach 2 (display-time conversion) is the correct and safe approach.** It is already fully implemented and tested. Changing the storage currency to PKR (Approach 1) would require weeks of dangerous migration work with high risk of data corruption, for zero functional benefit over what is already available.

The admin simply needs to select PKR in the currency settings page and configure the exchange rate. All prices throughout the application will immediately display in PKR.
