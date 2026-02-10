# Iteration 4: Next.js Excel UI - Input Sheet

## What Was Discussed and Planned

Build the interactive frontend for the estimation calculator using Next.js 16 with Handsontable Community Edition for an Excel-like editing experience. This iteration covers:
- Installing Handsontable CE (MIT, free)
- TypeScript types matching the API resource responses
- API service layer wrapping all estimation endpoints
- Custom React hooks for data fetching and caching
- Estimations list page with CRUD, filtering, and pagination
- Estimation editor page with header bar, tab navigation, and input sheet
- Input sheet with 7 sections, ~40 editable fields, dropdowns from API
- Debounced auto-save and server-side calculation triggering

## What Was Completed

### Dependencies Installed

| Package | Version | Purpose |
|---------|---------|---------|
| `handsontable` | 16.x | Excel-like grid component (CE, MIT license) |
| `@handsontable/react-wrapper` | 16.x | React 19 wrapper for Handsontable |

### Files Created/Modified

| File | Action | Description |
|------|--------|-------------|
| `frontend/src/types/index.ts` | Create | TypeScript interfaces: Estimation, InputData, DesignConfiguration, etc. |
| `frontend/src/lib/estimations.ts` | Create | API service layer: CRUD, calculate, sheet data, design configs |
| `frontend/src/hooks/useEstimation.ts` | Create | Hook for single estimation: fetch, save, debounced auto-save, calculate |
| `frontend/src/hooks/useEstimations.ts` | Create | Hook for paginated list with status filter |
| `frontend/src/hooks/useDesignConfigurations.ts` | Create | Hook for dropdown options with in-memory caching |
| `frontend/src/app/(protected)/estimations/page.tsx` | Rewrite | Full CRUD list with status tabs, table, pagination, create/delete |
| `frontend/src/app/(protected)/estimations/[id]/page.tsx` | Create | Estimation editor with header, tab bar, input sheet |
| `frontend/src/components/estimations/InputSheet.tsx` | Create | Handsontable-powered editable grid with 3-column layout |
| `frontend/src/components/estimations/InputSheetConfig.ts` | Create | Data-driven row definitions: 7 sections, ~47 rows |
| `frontend/src/components/estimations/EstimationHeader.tsx` | Create | Header bar with project info, stats, Save/Calculate buttons |
| `frontend/src/components/estimations/TabBar.tsx` | Create | Bottom tab bar: Input, Recap, Detail, FCPBS, SAL, BOQ, JAF |
| `frontend/src/components/estimations/SheetTab.tsx` | Create | Placeholder for non-Input tabs (shows calculated/not-calculated state) |
| `frontend/src/components/Navbar.tsx` | Modify | Added Next.js Link components, "+ New" quick-action button |
| `frontend/src/app/(protected)/page.tsx` | Modify | Dashboard shows real estimation count with draft/calculated breakdown |

### Input Sheet Sections

| Section | Fields | Type |
|---------|--------|------|
| PROJECT INFORMATION | Quote #, Revision, Building Name/No, Project, Customer, Salesperson, Date | Text/Date |
| BUILDING DIMENSIONS | Bay Spacing, Span Widths, Eave Heights, Roof Slopes | Text/Numeric |
| STRUCTURAL DESIGN | Frame Type, Base Type, CF Finish, Panel Profile, Outer Skin Material | Dropdown/Text |
| LOADS | Dead Load, Live Load, Wind Speed, Collateral Load | Numeric |
| PANEL & MATERIALS | Roof/Wall Panel Codes, Core Thickness, Paint System | Text/Dropdown |
| ROOF MONITOR | Monitor Type, Width, Height, Length | Dropdown/Numeric |
| MARKUPS | Steel, Panels, SSL, Finance | Numeric (0-5) |

### Key Architecture Decisions

1. **Debounced auto-save (800ms)**: Changes to the Handsontable grid trigger a debounced PUT request. Users don't need to manually save after every edit.
2. **Data-driven config**: `InputSheetConfig.ts` defines all rows declaratively. Adding new fields requires only adding an entry to the array.
3. **Dropdown caching**: `useDesignConfigurations` caches API responses in memory to avoid re-fetching on every render.
4. **Full-screen editor**: The estimation editor page uses `fixed inset-0` layout to maximize the grid area, matching Excel's full-screen feel.
5. **Tab bar at bottom**: Mimics Excel's sheet tab layout. Non-Input tabs are disabled until the estimation is calculated.

## What Was Skipped and Why

| Item | Reason |
|------|--------|
| Output sheet rendering (Recap, Detail, FCPBS, SAL, BOQ, JAF) | Deferred to Iteration 5. Tab bar and routing are set up; tabs show placeholder content |
| Remaining 55+ input fields (openings, endwall config, sag rods, etc.) | Will be progressively added. Core fields that feed the calculation engine are present |
| Print button | Deferred to Iteration 7 (PDF export) |
| Frontend automated tests | Deferred. Manual testing verified the flow works end-to-end |

## Status

| Category | Status |
|----------|--------|
| **Completed** | Dependencies, types, API service, hooks, list page, editor page, input sheet, tab bar, header bar, navbar update, dashboard update |
| **Backend Tests** | 120 tests, 239 assertions — all passing |
| **Frontend Build** | `npm run build` — clean, no TypeScript errors |
| **Remaining** | Iteration 5 (Output sheet rendering for all 6 tabs) |
| **Next Plan** | Iteration 5: Render Recap, Detail, FCPBS, SAL, BOQ, JAF tabs with read-only Handsontable grids |
| **How to Test** | `cd frontend && npm run dev` → Login → Create/edit estimation → Fill fields → Calculate → Verify tab switching |
