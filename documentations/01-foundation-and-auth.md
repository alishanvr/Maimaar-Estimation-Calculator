# Iteration 1: Foundation & Auth

## What Was Discussed and Planned

The client (Nasir from maimaargroup.com) provided a VBA/Excel estimation calculator file (`HQ-O-53305-R00 (3).xls`) — a QuickEst PEB (Pre-Engineered Building) estimation calculator. The goal is to replicate this 100% as a Laravel 12 + Next.js web application.

Key decisions made during planning:
- **Frontend**: Next.js in `frontend/` within the same repo
- **Spreadsheet UI**: Handsontable Community Edition (MIT, free)
- **Admin Panel**: Filament (free)
- **Calculation Security**: 100% server-side (formulas never in JavaScript)
- **Auth**: Laravel Sanctum token-based API authentication
- **Activity Logging**: spatie/laravel-activitylog

A 7-iteration development plan was created and approved (see `plans/lucky-mapping-zephyr.md`).

---

## What Was Completed

### 1. Backend Packages Installed
- `laravel/sanctum` — API token authentication
- `filament/filament` — Admin panel
- `spatie/laravel-activitylog` — Activity logging
- `maatwebsite/excel` (PhpSpreadsheet) — Excel file parsing for seeding
- `barryvdh/laravel-dompdf` — PDF export (for later iterations)

### 2. Database Migrations (7 new migrations)
- `add_role_and_status_to_users_table` — Added `role` (admin/user), `status` (active/revoked), `company_name`, `phone`
- `create_mbsdb_products_table` — 673 products from MBSDB sheet
- `create_ssdb_products_table` — 227 products from SSDB sheet
- `create_raw_materials_table` — 32 PU core materials from RawMat sheet
- `create_design_configurations_table` — 246 design configs from DB sheet
- `create_estimations_table` — Main estimation records with JSON input/results
- `create_estimation_items_table` — Line items for estimations
- Activity log tables (via spatie package migration)

### 3. Eloquent Models (7 models)
- `User` — Extended with `HasApiTokens`, `LogsActivity`, `FilamentUser` interface
- `Estimation` — With `SoftDeletes`, `LogsActivity`, JSON casts for input/results
- `EstimationItem` — Line items with metadata JSON
- `MbsdbProduct` — With scopes `byCode()`, `byCategory()`
- `SsdbProduct` — Similar structure with manufacturing cost data
- `RawMaterial` — PU core materials with weight per sqm
- `DesignConfiguration` — Dropdown options with `getOptionsForCategory()`

### 4. Factories & Seeders
- `UserFactory` — States: `admin()`, `revoked()`, `unverified()`
- `EstimationFactory` — States: `calculated()`, `finalized()`
- `EstimationItemFactory` — Standard factory
- `DatabaseSeeder` — Creates admin user (`admin@maimaargroup.com`) and test user
- `ReferenceDataSeeder` — Parses Excel file with correct column mappings:
  - MBSDB: Columns A-I, data starts row 5 → 673 products
  - SSDB: Columns A-J, data starts row 9 → 227 products
  - RawMat: Columns J-K, data starts row 14 → 32 materials
  - DB: Columns A-D with category headers → 246 configurations

### 5. API Authentication
- `AuthController` with `login()`, `logout()`, `me()` endpoints
- `LoginRequest` form request validation
- Routes in `routes/api.php`:
  - `POST /api/login` (public)
  - `POST /api/logout` (auth:sanctum)
  - `GET /api/user` (auth:sanctum)
- Sanctum stateful API middleware configured in `bootstrap/app.php`
- Activity logging on login/logout events

### 6. Filament Admin Panel
- `AdminPanelProvider` at `/admin` route
- **UserResource**: Full CRUD with Revoke/Activate/Reset Password actions, status badges, role filters
- **EstimationResource**: Read-only list and view with status badges
- **ActivityLogResource**: Read-only log viewer
- Access restricted to admin users via `canAccessPanel()` on User model

### 7. Next.js Frontend Shell
- Initialized in `frontend/` with Next.js 16, React 19, TypeScript, Tailwind CSS v4
- `api.ts` — Axios instance with Bearer token interceptor and 401 auto-redirect
- `AuthContext.tsx` — Auth context provider with login/logout/user state
- `login/page.tsx` — Login page with form validation and error display
- `(protected)/layout.tsx` — Protected layout that redirects to login if unauthenticated
- `(protected)/page.tsx` — Dashboard placeholder with user info cards
- `(protected)/estimations/page.tsx` — Estimations list placeholder
- `Navbar.tsx` — Navigation bar with user name and logout

### 8. Tests (16 tests, 35 assertions — all passing)
**Auth Tests (10):**
- Login with valid credentials
- Login with invalid credentials (401)
- Login with revoked account (403)
- Login validation (email/password required)
- Logout
- Get authenticated user
- Unauthenticated access (401)
- Admin role in response
- Login activity logging
- Logout activity logging

**Model Tests (4):**
- Admin role check
- Active status check
- Estimations relationship
- Hidden password/remember_token

---

## What Was Skipped and Why

- **Filament Dashboard widgets** (total users, estimations today, etc.) — Deferred to Iteration 6 when admin dashboard is fully built out with real estimation data.
- **PDF export implementation** — Package installed (`dompdf`) but implementation deferred to Iteration 7.
- **Handsontable integration** — Deferred to Iteration 4 (requires API endpoints from Iteration 3 first).

---

## Issues Encountered and Resolved

1. **Excel encoding error** (`iconv(): Detected an incomplete multibyte character`) — Fixed by suppressing `E_NOTICE`/`E_WARNING` during `IOFactory::load()`.
2. **RawMat seeder returning 0 rows** — Data was in columns J/K (not A/B/C as initially assumed). Fixed column mapping.
3. **Unit tests failing** (`A facade root has not been set`) — Pest unit tests don't load Laravel. Moved model tests from `tests/Unit/` to `tests/Feature/`.

---

## Status Table

| Area | Status |
|------|--------|
| Backend packages | Installed |
| Database schema (7 migrations) | Complete |
| Models & factories (7 models) | Complete |
| Reference data seeding | Complete (673 MBSDB, 227 SSDB, 32 RawMat, 246 DB configs) |
| Sanctum API auth | Complete |
| Auth API endpoints (login/logout/me) | Complete |
| Filament admin panel | Complete (User/Estimation/ActivityLog resources) |
| Next.js frontend shell | Complete (login, dashboard, protected routes) |
| Tests | 16/16 passing (35 assertions) |
| Pint formatting | Clean |

---

## How to Test

### Backend Tests
```bash
php artisan test
```

### Seed the Database
```bash
php artisan migrate:fresh --seed
```
Default admin: `admin@maimaargroup.com` / `password`
Default user: `user@maimaargroup.com` / `password`

### API Auth (manual testing)
```bash
# Login
curl -X POST http://maimaar-estimation-calculator.test/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@maimaargroup.com","password":"password"}'

# Get user (use token from login response)
curl http://maimaar-estimation-calculator.test/api/user \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Filament Admin
Visit `http://maimaar-estimation-calculator.test/admin` and login with admin credentials.

### Next.js Frontend
```bash
cd frontend
npm run dev
# Visit http://localhost:3000
```

---

## Next Plan: Iteration 2 — Core Calculation Engine

Port ALL VBA formulas to PHP service classes in `app/Services/Estimation/`:
1. `InputParserService` — Parse bay spacing, sheeting codes, column spacing
2. `QuickEstCalculator` — Core engine: purlin spacing, building area, panel weights
3. `DetailGenerator` — Bill of materials with MBSDB lookups
4. `RoofMonitorCalculator` — Curved eave special calculations
5. `FreightCalculator` — Weight x Rate + Volume surcharge
6. `PaintCalculator` — Building area minus openings, paint system costs
7. `FCPBSGenerator` — Financial category breakdown (A-H+)
8. `SALGenerator` — Sales pricing with markup
9. `BOQGenerator` — Bill of quantities summary
10. `JAFGenerator` — Job acceptance form data
11. `EstimationService` — Orchestrator

Validation target: Compare PHP outputs against Excel Quote 53305 (expected: 49.54 MT, 424,933 AED).
