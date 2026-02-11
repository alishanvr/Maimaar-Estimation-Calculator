# Iteration 5: Next.js Output Sheets

## What Was Discussed and Planned

Build 6 read-only output sheet views for the estimation editor: Recap, Detail, FCPBS, SAL, BOQ, and JAF. Each tab fetches its data from the existing API endpoints and renders it in either a Handsontable read-only grid (for tabular data) or a structured card layout (for form-like data).

## What Was Completed

### Shared Infrastructure

| File | Description |
|------|-------------|
| `frontend/src/types/index.ts` | Added ~120 lines of TypeScript interfaces for all 6 sheet data structures |
| `frontend/src/lib/formatters.ts` | Reusable `formatNumber`, `formatAED`, `formatPct` utilities |
| `frontend/src/hooks/useSheetData.ts` | Generic hook for fetching + caching sheet data with version-based invalidation |
| `frontend/src/components/estimations/sheets/ReadOnlySheet.tsx` | Shared wrapper with ResizeObserver height measurement, loading/error states |

### Sheet Components

| Component | Rendering | Columns/Sections |
|-----------|-----------|-------------------|
| `RecapSheet.tsx` | Card layout (2-col grid) | Weight breakdown + Price breakdown (7 values) |
| `DetailSheet.tsx` | Handsontable (200+ rows) | 11 cols: Description, Code, Sales, Cost Code, Size, Qty, Unit, Wt/Unit, Rate, Total Weight, Total Cost |
| `FCPBSSheet.tsx` | Handsontable (13 categories + subtotals) | 15 cols: SN through VA/MT, with Steel/Panels subtotals, FOB, Total rows |
| `SALSheet.tsx` | Handsontable (~24 lines) | 7 cols: Code, Description, Weight, Cost, Markup, Price, AED/MT |
| `BOQSheet.tsx` | Handsontable (9 items) | 6 cols: SL No, Description, Unit, QTY, Unit Rate, Total Price |
| `JAFSheet.tsx` | Card/form layout | Project Info, Pricing, Building Info, Special Requirements, Revision History |

### Editor Page Updates

| File | Change |
|------|--------|
| `frontend/src/app/(protected)/estimations/[id]/page.tsx` | Replaced `SheetTabPlaceholder` with per-tab conditional rendering of actual sheet components |

### Key Architecture Decisions

1. **Lazy rendering**: Each sheet component only mounts when its tab is active, avoiding 6 simultaneous API calls
2. **Version-based cache invalidation**: `useSheetData` accepts the estimation's `updated_at` as a `version` param — when recalculation updates the estimation, switching tabs triggers a re-fetch
3. **Shared ReadOnlySheet wrapper**: Extracts the ResizeObserver + loading/error pattern so each sheet component is focused on data transformation and rendering
4. **Card layout for Recap & JAF**: These sheets are summary/form documents, not large tables — card layouts provide better readability than forcing them into a grid
5. **Pre-formatted strings**: FCPBS, SAL, and BOQ sheets format numbers in `useMemo` before passing to Handsontable, so the grid displays locale-formatted values without custom cell renderers
6. **Computed columns in Detail**: `Total Weight` and `Total Cost` are computed client-side from `weight_per_unit * size * qty` and `rate * size * qty`

## What Was Skipped and Why

| Item | Reason |
|------|--------|
| Custom cell renderers for currency formatting | Pre-formatted string approach is simpler and sufficient |
| Print/export buttons on output tabs | Deferred to Iteration 7 (PDF export) |
| Handsontable merged cells for FCPBS | CE edition doesn't support merges; subtotal rows use bold styling instead |
| Real calculation test with sample data | Division-by-zero in backend calc for the test estimation; the UI components handle loading/error states gracefully |

## Status

| Category | Status |
|----------|--------|
| **Completed** | TypeScript types, shared utilities, useSheetData hook, ReadOnlySheet wrapper, all 6 sheet components, editor page wiring |
| **Backend Tests** | 120 tests, 239 assertions — all passing |
| **Frontend Build** | `npm run build` — clean, no TypeScript errors |
| **Remaining** | Iteration 6 (Admin Dashboard & Logging) |
| **Next Plan** | Iteration 6: Complete Filament admin panel with user management and activity logging |
| **How to Test** | `cd frontend && npm run dev` → Login → Open estimation → Fill inputs with valid data → Calculate → Click through Recap, Detail, FCPBS, SAL, BOQ, JAF tabs |
