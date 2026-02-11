# Iteration 11: Branding & PDF Polish

## What Was Discussed

The user requested professional branding for all PDF exports: company logo in headers, letterhead-style layout, and polished typography. The goal was to replace the plain navy-bar header across all 6 PDF templates with a professional letterhead design.

## What Was Completed

### Shared Blade Partials

Created 3 reusable Blade partials in `resources/views/pdf/partials/` to eliminate duplicated CSS and HTML across all 6 templates:

1. **`styles.blade.php`** — Shared base CSS for all PDFs:
   - Reset styles, font family (DejaVu Sans), configurable body font-size and line-height via Blade variables
   - Letterhead layout styles (logo, company name, document title, quote/date meta)
   - Project info bar styles
   - Footer styles with left/right float layout
   - Common table styles (number alignment, total rows, subtotal rows, highlights, alternating row backgrounds)

2. **`header.blade.php`** — Letterhead header with:
   - Company logo (left) — renders only if `public/images/logo.png` exists (`file_exists()` check)
   - "Maimaar Group" company name + uppercase document title (center-left)
   - Quote number + formatted date (right)
   - Navy accent line under letterhead (`2px solid #1e3a5f` border-bottom)
   - Project info bar below with Building, Customer, Project, Revision

3. **`footer.blade.php`** — Two-column footer:
   - Left: Quote number + document title
   - Right: Generation timestamp
   - Separator line above (`1px solid #ccc` border-top)

### Updated All 6 PDF Templates

Each template was refactored to use the shared partials:

| Template | Document Title | Font Size | Orientation |
|----------|---------------|-----------|-------------|
| `boq.blade.php` | Bill of Quantities (BOQ) | 11px | Landscape |
| `jaf.blade.php` | Job Acceptance Form (JAF) | 10px | Portrait |
| `recap.blade.php` | Estimation Summary (Recap) | 11px | Portrait |
| `detail.blade.php` | Detail Sheet | 9px | Landscape |
| `fcpbs.blade.php` | Cost & Price Breakdown (FCPBS) | 9px | Landscape |
| `sal.blade.php` | Sales Analysis (SAL) | 11px | Landscape |

Each template now:
- Includes `@include('pdf.partials.styles', [...])` with template-specific font size
- Includes `@include('pdf.partials.header', ['documentTitle' => '...'])` replacing the old navy bar
- Includes `@include('pdf.partials.footer', ['documentTitle' => '...'])` replacing the old centered footer
- Retains only template-specific CSS (table column sizing, special classes)

### Letterhead Layout

```
┌─────────────────────────────────────────────────────────────────────┐
│ [LOGO]   Maimaar Group                    Quote: HQ-O-53305-R00    │
│          BILL OF QUANTITIES (BOQ)         Date: 15 Jan 2026        │
├─────────────────────────────────────────────────────────────────────┤
│ Building: Test Warehouse    Customer: Test Customer    Rev: R00     │
└─────────────────────────────────────────────────────────────────────┘
```

### Logo Support

- Logo location: `public/images/logo.png`
- Uses `public_path()` for DomPDF compatibility (`enable_remote => false`)
- Graceful fallback: if logo file doesn't exist, header still renders without it
- Logo height: 44px, auto width

## What Was Skipped

- **Custom fonts**: Stayed with DejaVu Sans (DomPDF built-in). Custom font registration via DomPDF's font subsetting requires additional infrastructure. DejaVu Sans is reliable and supports UTF-8.
- **Page numbers**: DomPDF has limited support for dynamic page numbers in CSS. Can be added later via DomPDF's inline PHP canvas if needed.

## Files Created/Modified

| File | Action | Description |
|------|--------|-------------|
| `resources/views/pdf/partials/styles.blade.php` | Created | Shared base CSS for all PDFs |
| `resources/views/pdf/partials/header.blade.php` | Created | Letterhead header with logo support |
| `resources/views/pdf/partials/footer.blade.php` | Created | Two-column footer |
| `resources/views/pdf/boq.blade.php` | Modified | Uses shared partials |
| `resources/views/pdf/jaf.blade.php` | Modified | Uses shared partials |
| `resources/views/pdf/recap.blade.php` | Modified | Uses shared partials |
| `resources/views/pdf/detail.blade.php` | Modified | Uses shared partials |
| `resources/views/pdf/fcpbs.blade.php` | Modified | Uses shared partials |
| `resources/views/pdf/sal.blade.php` | Modified | Uses shared partials |
| `public/images/` | Created (dir) | Logo directory |

## How to Test

1. **Run export tests**: `php artisan test tests/Feature/Api/ExportTest.php` — 20 tests pass
2. **Full test suite**: `php artisan test` — 210 tests pass
3. **Visual verification**:
   - Place a company logo at `public/images/logo.png`
   - Login → Open/create estimation → Calculate → Switch to any tab
   - Click "Download PDF" on BOQ or JAF tabs
   - Verify letterhead with logo, company name, document title, quote/date
   - Verify project info bar with building/customer info
   - Verify footer with quote number and generation timestamp
4. **Without logo**: Remove `logo.png` → PDFs still generate cleanly

## How to Swap the Logo

Place your company logo at `public/images/logo.png`. Requirements:
- PNG format (DomPDF supports PNG, JPEG, GIF)
- Recommended: 200-400px wide, transparent background
- The header renders the logo at 44px height with auto width

## Status

| Item | Status |
|------|--------|
| Shared Blade partials | Completed |
| Updated all 6 templates | Completed |
| Logo support | Completed (graceful fallback) |
| Custom fonts | Skipped (DejaVu Sans is reliable) |
| Export tests | 20/20 passing |
| Full test suite | 210/210 passing |
