# Complaint Desk — Persistent Project Memory

**Last synchronized:** 2026-09-02
**Project root:** `C:\xampp\htdocs\complaint`  
**Application:** Complaint Desk / Complaint Management System  
**Source of truth:** The current codebase, migrations, configuration, tests, and generated build output. This file is a concise continuation guide; `project_structure.md` and `database_design.md` contain the fuller architecture and database narratives.

## Project Overview

Complaint Desk is an authenticated Laravel web application for customer-support teams. Staff search for customers, register complaints, update complaint details and statuses, record resolutions, and review complaint history and audit activity. Administrators manage master data, users, roles, and permissions. The application supports English and Arabic, including RTL layout behavior.

The main roles are **Super Admin**, **Admin**, **Customer Support**, and **Viewer**. The application is a server-rendered Laravel MVC system using Blade and Eloquent. It is not a React/Vue SPA and does not use Livewire or Filament. Small vanilla-JavaScript enhancements provide background searching for large customer and branch lists.

## Technology Stack

| Area | Current implementation |
|---|---|
| Backend | Laravel 12, PHP 8.2+ |
| UI | Blade templates with Tailwind CSS 4 |
| Frontend build | Vite 7 with Laravel Vite plugin |
| Browser JavaScript | Native `fetch()`, debounce timers, `AbortController`, and service-worker registration |
| Database | SQL Server at `127.0.0.1:1433`, database `complaints`; test suite uses in-memory SQLite |
| Authentication | Laravel session authentication through custom `AuthController` |
| Authorization | Spatie Laravel Permission 6.24.0, Laravel Gate, controller checks, and permission middleware |
| Excel | Maatwebsite Laravel Excel 3.1.67 |
| Localization | Laravel translation dictionaries for English and Arabic plus `SetLocale` middleware |
| PWA | Native `manifest.json`, icons, and static-only service worker |
| Testing | PHPUnit 11 through `php artisan test` |
| Local web server | XAMPP Apache serving the Laravel `public` directory |

Important Composer dependencies are `laravel/framework`, `laravel/tinker`, `spatie/laravel-permission`, and `maatwebsite/excel`. Important NPM dependencies are Vite, `laravel-vite-plugin`, Tailwind CSS, `@tailwindcss/vite`, Axios, Concurrently, and Chart.js. The current picker implementation uses native `fetch()` rather than Axios; dashboard charts use Chart.js.

## Architecture

The request flow is:

```text
Browser
  ↓
Named route in routes/web.php
  ↓
SetLocale and auth middleware
  ↓
Controller permission check / Spatie middleware
  ↓
Form Request validation where applicable
  ↓
Eloquent query or mutation
  ↓
ActivityLogService / ComplaintStatusService when cross-record logic is needed
  ↓
Blade response, redirect, JSON picker response, or Excel download
```

Business logic is intentionally simple and Laravel-native. There is no repository layer, DTO layer, custom event/listener pipeline, or custom background job workflow. `ComplaintStatusService` handles atomic status transitions and `ActivityLogService` centralizes audit-record creation. `Complaint::scopeFilter()` composes complaint-list and export filters.

`bootstrap/app.php` registers `routes/web.php`, aliases Spatie `permission` and `role` middleware, and appends `SetLocale` to the web middleware group. `AppServiceProvider` registers a `Gate::before` callback that gives users with the `Super Admin` role unrestricted permission access.

## Implemented Features

### Authentication and localization

- Login and logout using Laravel sessions.
- Localized authentication failure messages.
- Locale route at `/locale/{locale}` for `en` and `ar`.
- Arabic RTL layout and English LTR layout.
- Localized navigation, labels, flash messages, validation messages, activity labels, and Excel headings.

### Customers

- Customer CRUD screens protected by permissions.
- Required name and primary phone.
- Optional second, third, and fourth phone fields.
- Search by name or any phone field.
- Initial customer picker limited to 10 records.
- Debounced background customer search with `fetch()` and `AbortController`.
- Customer summary and complaint history.
- Customer index uses Laravel `paginate(30)->withQueryString()` so 30 customers are rendered per page.
- Soft deletes at the model/database level.

### Complaints

- Complaint creation, listing, detail, and update screens.
- Complaint index uses Laravel `paginate(30)->withQueryString()` so 30 complaints are rendered per page.
- Required customer, branch, service, source, category, type, priority, status, short description, description, and complaint date.
- Authenticated user is always assigned as `created_by` server-side.
- Complaint ID and validated short/full description text filtering, plus customer, branch, master-data, creator, and date filtering.
- Multi-branch filtering with `branch_ids[]` and `whereIn`.
- Pagination with query-string preservation.
- Filtered Excel export using the same validated filters as the HTML list.
- Status history with previous status, new status, reason, actor, and timestamp.
- Resolution metadata (`resolved_by`, `resolved_at`, and `resolution`) when a complaint is moved to the configured Solved status.
- Complaint timeline combining status history and activity logs.
- Localized display of stored activity keys such as `complaint.updated`.

### Master data and reports

- CRUD management for branches, services, sources, categories, complaint types, priorities, and statuses.
- Active/inactive flag controls selection of records for new complaints.
- Soft deletion and restrictive foreign keys protect historical complaint references.
- Branch report grouped by complaint date and branch.
- Branch picker limited to 5 initial records with debounced name search.

### Users, roles, and permissions

- Authorized user creation and update with role assignment.
- Users page filters by name/email and role, with database pagination at 20 records.
- Role listing with permission and assigned-user counts.
- Custom role creation using the `web` guard.
- Permission configuration for existing roles.
- Reserved baseline role names cannot be created.
- Baseline roles cannot be deleted.
- Any role assigned to one or more users cannot be deleted; this is enforced server-side, not only by hiding the button.

### Audit logs and PWA

- Permission-protected, paginated audit-log screen with action filtering.
- Audit entries store action, actor, polymorphic subject, description, and old/new JSON snapshots.
- PWA manifest, branded icons, standalone display metadata, and service-worker registration.
- Service worker caches only hashed Vite assets and static icons. It does not cache HTML routes, authentication/session data, JSON/API responses, or private pages.
- Full offline complaint creation, synchronization, and offline authentication are intentionally not implemented.

## Database and Schema

The migration order is:

```text
0001_01_01_000000_create_users_table
0001_01_01_000001_create_cache_table
0001_01_01_000002_create_jobs_table
        ↓
2026_08_23_161041_create_permission_tables
        ↓
2026_08_23_161200_create_complaint_master_data_tables
        ↓
2026_08_23_161300_create_complaints_table
        ↓
2026_08_23_161400_create_complaint_history_tables
        ↓
2026_08_31_170000_create_visitors_master_data_tables
        ↓
2026_08_31_170100_create_visitors_transaction_tables
```

### Core tables

| Table | Purpose | Important details |
|---|---|---|
| `users` | Authenticated staff | Standard Laravel user fields; complaint actors reference `users.id` |
| `customers` | Customer identity/contact | Name, four phone fields, optional address, timestamps, soft deletes; phone columns indexed |
| `branches` | Branch master data | Name, optional indexed code, active flag, sort order, soft deletes |
| `services` | Service master data | Name, optional color, active flag, sort order, soft deletes |
| `complaint_sources` | Complaint source master data | Name, optional color, active flag, sort order, soft deletes |
| `complaint_categories` | Complaint category master data | Name, optional color, active flag, sort order, soft deletes |
| `complaint_types` | Complaint type master data | Name, optional color, active flag, sort order, soft deletes |
| `priorities` | Priority master data | Name, required color, optional level, active flag, sort order, soft deletes |
| `complaint_statuses` | Status master data | Name, required color, active flag, sort order, soft deletes |
| `complaints` | Main complaint record | Required master-data/customer references, descriptions/date, creator; nullable resolver/resolution fields; soft deletes |
| `complaint_status_histories` | Append-only status transitions | Complaint, optional from status, to status, reason, actor, changed timestamp |
| `activity_logs` | Audit trail | Actor, action, nullable polymorphic subject, description, old/new JSON values, timestamp |

Spatie adds `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, and `role_has_permissions`. Teams and wildcard permissions are disabled. Permissions are cached using Spatie’s configured cache key and 24-hour expiration.

Foreign keys on complaints and status histories use restrictive deletion behavior for historical references. `resolved_by` and `activity_logs.user_id` are nullable and use `nullOnDelete`. Business records should normally be deactivated or soft-deleted instead of physically deleted.

The `.env.example` specifies `SESSION_DRIVER=database`, and the default Laravel users migration includes the `sessions` table. PHPUnit overrides sessions with the array driver. Database-backed sessions are therefore available in the verified local SQL Server schema.

## Business Rules and Decisions

- The customer’s primary phone is required; up to three additional phone values are nullable.
- Customer search checks name and all four phone columns.
- New complaint selections use active master-data records only; existing complaints can still display inactive historical records.
- Complaint creators are taken from `auth()->id()` and cannot be supplied by a client request.
- Status changes create both a history row and an activity row inside a transaction.
- The configured status named Solved sets resolution actor/time metadata.
- Complaint and report lists use database pagination and query-string preservation.
- Customer and branch pickers use bounded initial results to avoid loading large tables into the page.
- Activity action identifiers are stable storage values; views translate known identifiers at render time.
- Server-side authorization is mandatory. Blade `@can` checks are only the interface layer.
- Super Admin access is protected through a Gate bypass and role-management safeguards.
- Baseline roles are `Super Admin`, `Admin`, `Customer Support`, and `Viewer`.
- Role deletion is blocked when the role is protected or assigned to a user.
- Master-data names are stored as database values and are not duplicated per locale. Interface labels are translated, but a branch/service/status name itself remains the value entered in the database.

## Important Files

| File/directory | Responsibility |
|---|---|
| `routes/web.php` | Auth, locale, dashboard, customer, complaint, report, master-data, users, roles, and audit routes |
| `bootstrap/app.php` | Route registration and middleware aliases/registration |
| `app/Http/Controllers/` | Request-facing actions and permission checks |
| `app/Http/Requests/` | Complaint/customer/filter validation and authorization |
| `app/Models/Complaint.php` | Complaint relationships, casts, soft deletes, and filter scope |
| `app/Services/ComplaintStatusService.php` | Transactional status transition/history/resolution logic |
| `app/Services/ActivityLogService.php` | Activity-log persistence |
| `app/Exports/ComplaintsExport.php` | Query-based localized Excel export |
| `app/Providers/AppServiceProvider.php` | Super Admin Gate bypass + visitors policy registration |
| `app/Http/Middleware/SetLocale.php` | Session locale validation and application locale selection |
| `resources/views/layouts/app.blade.php` | Main shell, navigation, flash alerts, validation errors, PWA metadata |
| `resources/js/app.js` | Customer/branch pickers, Chart.js report dashboard charts, and service-worker registration |
| `resources/css/app.css` | Tailwind source and shared UI classes |
| `lang/en/` and `lang/ar/` | English/Arabic common, auth, complaint, customer, activity, validation, visitors, and permissions dictionaries |
| `public/manifest.json` | PWA install metadata |
| `public/service-worker.js` | Static-asset-only cache policy |
| `database/seeders/PermissionSeeder.php` | Permissions, baseline roles, and initial Super Admin |
| `database/seeders/MasterDataSeeder.php` | Example branches and complaint master data |
| `database/seeders/DemoDataSeeder.php` | Demo customers and complaints |
| `tests/Feature/` | Workflow, authorization, filter, export, localization, and PWA regression tests |
| `project_structure.md` | Full developer-oriented architecture and current-state map |
| `database_design.md` | Detailed database design narrative and relationship diagram |
| `questions_and_answers.md` | Rephrased, implementation-focused Q&A for recurring "how does it work" questions |

## Routes and Entry Points

There is no separate business API route file. JSON search endpoints are protected web routes.

| Area | Routes |
|---|---|
| Authentication | `/login`, `/logout` |
| Locale | `/locale/{locale}` |
| Dashboard | `/` |
| Customers | `/customers`, `/customers/create`, `/customers/search`, `/customers/{customer}`, edit/update |
| Complaints | `/complaints`, create/show/edit/update, `/complaints/branches/search`, `/complaints/export` |
| Reports | `/reports/branches` |
| Quality Visits | `/visitors`, `/visitors/create`, `/visitors/open`, `/visitors/{visit}`, POST `/visitors`, POST `/visitors/{visit}/submit`, PUT `/visitors/items/{visitItem}`, POST `/visitors/items/{visitItem}/photo`, GET `/visitors/photos/{photo}`; reports: `/visitors/reports`, `/visitors/reports/dashboard`, `/visitors/reports/{visit}`, `/visitors/reports/{visit}/pdf` |
| Master data | `/master-data/{type}` and type-specific create/edit/store/update/destroy routes |
| Users | `/users`, create, edit, store, update |
| Roles | `/roles`, `/roles/create`, role edit/update/store/destroy |
| Audit | `/audit-logs` |

Authenticated pages are under the `auth` middleware group. Permission checks are implemented in controllers and, for selected routes, with Spatie middleware aliases.

## Testing and Last Verification

The test suite contains feature coverage for customer search and CRUD, complaint creation/update/status history, filters, export, authorization, role lifecycle, users filters, localization, PWA metadata/cache boundaries, dashboard analytics/filter payloads, and known Blade regression cases. Unit coverage currently contains the Laravel example unit test; application services are primarily covered through feature tests.

The latest complete validation after the advanced dashboard implementation was successful:

- Blade views cleared and cached successfully.
- Vite production build completed successfully.
- Full Laravel suite: **42 tests passed, 195 assertions**.
- Bilingual localization tests passed for complaint/customer/auth messages, login copy, customer status summaries, audit-log action rendering, and dark-mode labels.
- Dark-mode layout tests passed for the theme switch markup and early localStorage bootstrap.
- Dashboard localization regression assertions passed for English and Arabic filter labels, branch-selection guidance, and the Solved/Closed resolution definition.

## Development Commands

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run dev
npm run build
php artisan view:clear
php artisan view:cache
php artisan test --no-ansi
php artisan route:list
php artisan migrate:status
```

For the current XAMPP setup, Apache serves the `public` directory and the application URL is `http://localhost/complaint/public/`. Never place secrets, passwords, API keys, or tokens in this file or in `project_structure.md`.

## Current Tasks

There are no unverified feature changes currently pending from the previous implementation work. The most recent completed work was the project documentation and persistent-memory documentation update. Future development should first read this file and `project_structure.md`, then update both when a meaningful architectural, schema, feature, configuration, or workflow change is made.


## Quality Visits (Inspection) module — 2026-08-31

A new **Quality Visits** inspection module was added alongside the existing complaint management without breaking it. It uses `visitors_`-prefixed tables, `Visitor*` models, dedicated services, a policy, seeders, routes under a `visitors.` prefix, and Blade views (`visitors/home`, `create`, `open`, `show`) with vanilla-JS autosave.

- **Schema (all SQL Server-compatible):** `visitors_visit_types`, `visitors_sections`, `visitors_root_causes`, `visitors_checklist_items`, `visitors_visits`, `visitors_visit_items` (with immutable snapshot columns), `visitors_visit_photos`, `visitors_capa_actions`, `visitors_capa_updates`. The visitors transaction migration initially failed on SQL Server due to its "multiple cascade paths" rule; the `visit_item_id` FKs on `visitors_visit_photos` and `visitors_capa_actions` and the two `users` FKs on `visitors_capa_actions` now use `noActionOnDelete()` instead of `cascadeOnDelete()`/`nullOnDelete()`. The `visitors_`-prefixed table names deviate from Eloquent's snake-case convention, so every `Visitor*` model declares `protected $table` and every `belongsTo`/`hasMany` relation declares its foreign key explicitly.
- **Models:** `VisitorVisitType`, `VisitorSection`, `VisitorRootCause`, `VisitorChecklistItem`, `VisitorVisit`, `VisitorVisitItem`, `VisitorVisitPhoto`, `VisitorCapaAction`, `VisitorCapaUpdate`.
- **Services:** `VisitScoreService` (single source of truth for score), `VisitService` (start/saveItem/submit), `CapaService` (create CAPA for non-compliant items on submit), `VisitorPhotoService` (GD compression, 20 MB cap), `ChecklistImportService` (Excel import helper).
- **Authorization:** `VisitorVisitPolicy` (view/update/submit: owner + not-completed) registered in `AppServiceProvider` via `Gate::policy`; Super Admin bypasses via the existing `Gate::before`. Controllers manually import `AuthorizesRequests` because the base `Controller` is empty.
- **Permissions/routes:** added `visit.submit`/`visit.manage` plus module `visit.*`; Customer Support and Viewer roles grant `visit.view`/`visit.create`/`visit.update`. Routes are registered under `visitors.` prefix with static `open`/`create` routes defined before the `{visit}` wildcard.
- **Score rules:** available = sum deduction of non-NA items; deduction = sum for NC; final = available − deduction; percentage = (final/available)×100 with colors ≥85 blue, 75–84 green, 68–74 yellow, <68 red, all in `VisitScoreService`.
- **Workflow rules:** items start `status=pending` with `visited_at` null at creation (nothing chosen by default — the inspector must actively pick Compliant / Non-compliant / Not Applicable per item, and only then does `visited_at` get stamped and the item count as reviewed); progress based on `visited_at`; snapshots copied to `visitors_visit_items` at creation; score computed only on the backend; photos stored privately on the `local` disk and served through an authenticated route; photos required when `nc` + critical OR `photo_required` (severity-based, independent of deduction — `VisitorVisitItem::isCritical()` lowercases severity and `requiresPhoto()` = `($status==='nc' && isCritical()) || photo_required`); hidden/suggested values are never auto-filling; `submit()` validates all items reviewed + required photos (throws `__('visitors.evidence_photo_required')` when a Critical NC item has no photo), creates CAPA from NC items, sets `completed`+`completed_at` inside a transaction; users only access their own `in_progress` visits (Open Visits scoped to `inspector_id = auth user AND status = in_progress`).
- **Localization:** added `lang/en/visitors.php` and `lang/ar/visitors.php`; added `customer_complaints`/`quality_visits` nav keys to `common.php` in both locales; added `@stack('scripts')` to the layout.
- **Verification:** added `tests/Feature/VisitorsTest.php` (14 tests, two of which assert the `evidence_photo_required` message on Critical-NC-without-photo submit). Frontend: the inspection view (`show.blade.php`) shows a `📷 Evidence *` + `photo_required_critical` indicator (toggled dynamically when a Critical item is marked NC) and blocks submit via `alert(evidence_photo_required)` until every Critical NC item has at least one photo. Full suite: **60 tests passed / 296 assertions**. Migrations + seeders run against SQL Server and the SQLite test DB.

### Quality Visits Reports module (2026-08-31)

- **Reuse/no duplication:** `VisitScoreService::fromAggregates()` is the single source of score truth, used by `calculate()`, the report list, and charts. Reports read only the immutable `visitors_visit_items` snapshot columns (never master checklist values) and existing CAPA rows; `VisitorCapaAction::effectiveStatus()` provides overdue splitting reused by views and the dashboard.
- **Services:** `VisitorReportService` (`build()` single QHSE report; `listReports()` paginated DB-aggregated list with branch/type/inspector/date/score-color filters), `VisitorReportDashboardService` (SQL joins/groupBy analytics: cards, severity/section/root-cause distributions, per-branch weighted score + best/worst, recurring/critical violations, CAPA status + average time-to-close, inspector performance, day/week/month trend), `VisitorPdfService` (dompdf `render()/stream()/download()` of the self-contained-CSS `visitors/reports/print` view, embedding private photos via `Storage::disk('local')->path()`). Dependencies: `barryvdh/laravel-dompdf ^3.1` (composer).
- **Authorization:** reports are NOT owner-restricted. `VisitorVisitPolicy::viewReport()` = visit completed AND (`visit.manage` OR (`report.view` AND owner)); new `Gate::define('visitors.reports', ...)` = `report.view` OR `visit.manage`; Super Admin bypasses via existing `Gate::before`. Customer Support (inspectors) → 403.
- **Controller/routes:** `VisitorReportController` (index/show/dashboard/pdf); routes `visitors/reports`, `visitors/reports/dashboard`, `visitors/reports/{visit}`, `visitors/reports/{visit}/pdf` registered BEFORE the `{visit}` wildcard. Views `visitors/reports/{index,show,dashboard,print}` plus a home Reports card link.
- **SQL Server gotchas:** color filter uses `HAVING` with repeated aggregate expressions (aliases can't be referenced in `HAVING`, and subquery select lists for `IN` must have one column); average time-to-close CAPA computed in PHP (`DATEDIFF(DAY,...)` unsupported on SQLite).
- **Verification:** added `tests/Feature/VisitorReportsTest.php` (15 tests; manager uses `Admin` role so in-progress 403 is enforceable, since Super Admin bypasses the gate). Full suite: **76 tests passed / 433 assertions**. List filter, individual report, PDF (~881 KB), and dashboard analytics smoke-tested against live SQL Server.

### Quality Visits Master Data (Excel) module (2026-08-31)

An **Excel Master Data management UI** was added on top of the existing Quality Visits module to manage the inspection checklist via `.xlsx`/`.xls` import, preview, confirm, and current-data export — without changing complaint or visit functionality.

- **Reuse (no breakage):** reuses `visitors_visit_types` (inspection types), `visitors_sections`, `visitors_root_causes`, `visitors_checklist_items` (items) as the canonical runtime master. New tables only: `visitors_severities`, `visitors_imports`, `visitors_import_rows`; plus nullable `root_cause_id` FK added to `visitors_checklist_items` (suggested default root cause; inspector still chooses at NC time). Historic `visitors_visit_items` snapshots are never overwritten.
- **New models:** `VisitorSeverity`, `VisitorImport` (statuses pending/validating/ready/imported/failed/cancelled; `validRows()`/`invalidRows()`/`isReady()`/`isImported()`), `VisitorImportRow` (`errorsList()`, `dataArray()`). `VisitorChecklistItem` gained `root_cause_id` fillable + `rootCause()`.
- **Upload rules:** `.xlsx`/`.xls` only, 50 MB cap (`Maximum allowed file size is 50 MB.`), real MIME verified (not just extension). Chunk-read via `MasterDataChunkReadFilter` (200-row slices). Exact 11-column order: code, inspection_type, section, note, severity, immediate_action, corrective_action, responsible, period, preventive_action, deduction_score. (`item` is named `note`/ملاحظة, `deduction` is `deduction_score`, and `root_cause` was removed from the file — the inspector chooses it during the visit.)
- **Import identity:** `(inspection_type, code)` is the logical unique key; create/update up-sert only, NO destructive deletes. Whole-file rejection: any invalid row blocks `confirm()`. Duplicate `(type, code)` within one file is rejected.
- **Service:** `app/Services/Visitors/VisitorMasterDataService.php` — `validateUpload()`, `readRows()` (chunked, header-keyed), `validateRows()` (lookups by code OR name, forced lowercase; deduction_score numeric), `storeImport()` (persists header + per-row JSON, status `ready`), `confirm()` (transactional up-sert; increments created/updated; refuses when `invalid_rows > 0`; sets imported/completed_at), `cancel()`.
- **Exports:** `HasMasterDataColumns` trait, `MasterDataTemplateSheet`, `VisitorMasterTemplateExport` (TWO sheets: "Template" headings-only + "Example (Sample Data)" 3 sample rows), `VisitorCurrentMasterDataExport` (FromQuery+Mapping, respects type/section filters), `MasterDataChunkReadFilter`.
- **Controller/routes:** `VisitorMasterDataController` (index/stats+filters+history, template, templateExample, download, import, preview, confirm, cancel). Routes `visitors/master-data*` are registered inside the `visitors.` group BEFORE the `{visit}` catch-all.
- **Permissions:** added `visit.master.view`, `visit.master.import`, `visit.master.export`. Admin gets all three (via `syncPermissions` minus role.delete/user.delete/audit.view); Super Admin bypasses via the existing `Gate::before`; Customer Support (inspectors) is denied. Admin/`visit.manage` can also confirm/cancel another user's import.
- **Views/localization:** `visitors/master-data/{index,preview}.blade.php` (stats cards, 4 action buttons, filters, current-data table, upload form, import history, preview + confirm/cancel), a home Master Data card gated by `@can('visit.master.view')`, and `master_*` bilingual keys in `lang/en|ar/visitors.php`.
- **Verification:** added `tests/Feature/VisitorMasterDataTest.php` (15 tests: permissions, downloads, upload validation size/extension, duplicate+invalid detection, preview persists without mutation, confirm create/update keyed by type+code, invalid blocks confirm, cancel, history). Full suite: **91 tests passed / 479 assertions**. Migration + `VisitorsSeeder` run against live SQL Server (3 severities seeded).

### Mandatory Critical Evidence (photo) — 2026-08-31

When an inspection item with **Critical** severity is marked **Non-Compliant (NC)**, the inspector MUST upload at least one evidence photo before the visit can be submitted/completed. Mandatory is decided purely by severity (`severity === 'critical'`), independent of deduction. Major/Minor/non-Critical photo remains optional.

- **Backend rule:** `VisitorVisitItem::isCritical()` compares `strtolower(severity) === 'critical'`; `requiresPhoto()` = `($status==='nc' && isCritical()) || photo_required`. `VisitService::submit()` throws `RuntimeException(__('visitors.evidence_photo_required'))` when any Critical NC item has no photo; the controller flashes that message as `error`. Photo rules unchanged: `VisitorPhotoService` 20 MB cap, real MIME + magic-byte check, JPG/JPEG/PNG/WEBP only, compression after upload, secure UUID filenames, private `local` Storage disk (no base64). Draft/resume is allowed without the photo; only submit is blocked.
- **Frontend (show.blade.php):** each item carries `data-critical="1|0"`; Critical items render `📷 Evidence *` (asterisk) + a `photo_required_critical` message (`photo-required-msg`) that is shown/hidden dynamically when the item's status changes to/from NC; the upload also gets `aria-required`. Submit is intercepted by `#submit-visit-form` JS which, before confirming, scans every Critical+NC item and blocks with `alert(__('visitors.evidence_photo_required'))` until it has a photo (`.photo-list a`).
- **Localization:** added `photo_required_critical` ("Photo is required for Critical violations" / «الصورة مطلوبة للمخالفات الحرجة») and `evidence_photo_required` ("A photo/evidence is required for Critical non-compliant items." / «صورة/دليل مطلوبة لعناصر عدم الامتثال الحرجة.») after `photo_help` in both `lang/en/visitors.php` and `lang/ar/visitors.php`.
- **Verification:** `test_reviewed_nc_critical_without_photo_blocks_submission` and `test_critical_nc_item_requires_photo_on_submit` now assert `assertSessionHas('error', __('visitors.evidence_photo_required'))`; non-Critical NC submit still passes without photo (`test_submission_creates_capa_and_completes_visit`). Full suite: **91 tests passed / 479 assertions**. Pure view/message change — no schema/service migration, verified against SQLite tests.

### PWA Install App button + Arabic branding + navbar polish — 2026-09-02

Shared-shell and PWA installability improvements for the phone-first layout.

- **Custom "Install App" button:** `resources/views/layouts/app.blade.php` now has a desktop header button (`data-install-button`, `hidden sm:inline-flex`) and a full-width mobile-drawer button (`data-install-button-mobile`) plus a bilingual `data-install-modal` with Android/iOS add-to-home-screen instructions, rendered before the scripts stack. `resources/js/app.js` captures `beforeinstallprompt`, detects standalone mode (`display-mode: standalone`) and iOS, reveals the buttons, and falls back to the instructions modal when there is no deferred prompt. The mobile button is **always revealed** on non-iOS because `beforeinstallprompt` never fires over plain HTTP from an Android phone (`http://<LAN-IP>`); the button hides again after `appinstalled`.
- **Install translations:** `lang/en|ar/common.php` gained `install_app`, `add_to_home_screen`, `install_instructions_title`, `install_instructions_android`, `install_instructions_ios`, `install_now`, `install_close` (221 keys each locale). Arabic was patched via a PHP script after a PowerShell `Set-Content -Encoding UTF8` double-encoded the file (restored from git); later lang edits use .NET `File::ReadAllText/WriteAllText` with UTF-8 no-BOM.
- **Arabic branding:** hardcoded "Complaint Desk" replaced by `__('common.application_name')` (`en` = Complaint Desk, `ar` = نظام الشكاوى) for the head meta/apple-web-app title, `<title>`, desktop header, and mobile drawer header.
- **Navbar RTL overlap fix:** the desktop/hamburger breakpoint moved from `lg` (1024px) to `xl` (1280px) — nav is `hidden min-w-0 flex-1 items-center justify-center xl:flex`, toggle + drawer are `xl:hidden`, JS resize check uses `matchMedia('(min-width: 1280px)')`; logo and controls are `shrink-0` so the Arabic brand (`نظام الشكاوى`) no longer collides with menu items. User name shows from `xl:inline`; avatar is `xl:hidden`.
- **Nav/drawer animations (`resources/css/app.css`):** drawer slide+fade (transform 360ms cubic-bezier + opacity 260ms, `will-change`), overlay fade, staggered `navItemIn` keyframes at 40ms increments for the drawer links, hamburger-icon rotate/crossfade (200ms), desktop `[data-nav-dropdown]` fade/translate/scale on `[open]`, `prefers-reduced-motion` disables all; viewer JS opens with a `requestAnimationFrame` double-tick and hides the drawer 360ms after close. Mobile drawer also gained dark-mode contrast overrides (panel `#1e293b`, active `bg-#3730a3`, borders `#334155`).
- **Verification:** assets rebuilt against the hashed Vite manifest (`npm run build` + `php artisan view:clear`). Full suite after this work: **91 tests passed / 479 assertions**.

### Master-data Excel format change + PhpSpreadsheet 4.x compat fix — 2026-09-02

The master-data Excel format was slimmed to **11 columns** across the shared headings trait, the download template/current-data exports, upload parsing/validation, the import confirm step, and the master-data table/preview UI labels.

- **New columns:** `code, inspection_type, section, note, severity, immediate_action, corrective_action, responsible, period, preventive_action, deduction_score`. `root_cause` was **removed** from the file (the visitor picks the root cause at NC time; `root_cause_id` on `visitors_checklist_items` is unchanged as the suggested value), `item` was renamed `note` (Arabic ملاحظة) and `deduction` renamed `deduction_score`. Confirmation still writes `title`, `deduction_score`, `photo_required`; it no longer sets `root_cause_id`. UI labels changed to Note / ملاحظة and Deduction Score / درجة الخصم in both locales; legacy files using the old headers are rejected (they no longer match the known header list).
- **Root cause of the earlier "Unable to read the Excel file..." error:** the installed PhpSpreadsheet vendor files are **4.x** while `composer.lock` pins `phpoffice/phpspreadsheet: 1.30.6`; in 4.x `PhpOffice\PhpSpreadsheet\Reader\IOFactory` moved to `PhpOffice\PhpSpreadsheet\IOFactory` and `Worksheet::disconnectWorksheets()` was removed. `VisitorMasterDataService.php` still used the old import and the removed call, so **every** upload (not just a downloaded file) threw inside the controller's catch-all at `VisitorMasterDataController.php:100`. Both lines were fixed; `lookupRootCauseMap()` and the now-unused root-cause import were removed.
- **Verification:** round-trip probe regenerated the template + current export and re-read them with the service — template parses (0 rows), current file reads all rows and validates **17 valid / 0 invalid**; the shared headings now match in template, example, and current-data sheets. Full suite: **91 tests passed / 479 assertions**.

### Quality Visits single-page New Visit form — 2026-09-02

The two-step "pick a visit type, then pick branch/date" flow was merged into **one page** at `/visitors/create`.

- **Before:** GET `/visitors/create` showed type cards → GET `/visitors/create/{visitType}` (setup) collected branch + date → POST `/visitors`.
- **After:** GET `/visitors/create` renders a single card form with **visit type** (dropdown, shows name + code), **branch** (dropdown), **inspector** (readonly = authenticated user, unchanged), and **visit date** (defaults to today), submitting directly to POST `/visitors` (`StartVisitRequest` unchanged). The `setup()` controller method, the `visitors/create/{visitType}` route, and the `visitors/setup.blade.php` view were removed.
- **Verification:** added `test_create_page_shows_single_form_with_type_branch_and_date` asserting the unified form on `GET visitors.create`. Full suite: **92 tests passed / 489 assertions**.

### Visit status Arabic terminology — 2026-09-02

Arabic visit-item status labels were aligned to the requested wording in `lang/ar/visitors.php`: `option_ok` = ✓ مطابق, `option_nc` = غير مطابق, `option_na` = غير متاح (the buttons on the inspection page), plus the matching report keys `compliant` = مطابق, `non_compliant` = غير مطابق, `not_applicable` = غير متاح and `nc_by_severity` = العناصر غير المطابقة حسب الخطورة. The old ملتزم / غير قابل للتطبيق terms are gone. Content-only change (keys and English values untouched); `VisitorsTest` + `LocalizationTest` pass.

### New-visit items no longer default to Compliant — 2026-09-02

Requested on the inspection page (`/visitors/{visit}`): a brand-new visit must have **nothing chosen** until the inspector picks a status. `VisitService::start()` therefore creates `visitors_visit_items` with `status = 'pending'` and `visited_at = null` (previously `'ok'` + `now()`, which silently treated every item as Compliant/reviewed). Downstream behavior is unchanged and consistent: `isReviewed()` still keys off `visited_at`, progress starts at `0/N`, no status button is highlighted on load (all three inactive), the NC panel stays hidden, and `submit()` already rejects any unreviewed item. Score/reports only ever run on completed visits where every item has an explicit chosen status. Migration default comment updated to `// pending | ok | nc | na`. Tests updated to the new reality (`test_create_stores_authenticated_inspector_and_items_start_unreviewed`, `test_progress_based_on_visited_at` now expects `1 / N` after one review, `test_fresh_visit_submits_after_all_items_reviewed`; `VisitorReportsTest` helpers mark the non-NC items `ok` + `visited_at` to model real completed visits). Full suite: **92 tests / 489 assertions**.

### Questions & Answers doc created — 2026-09-02

Created `questions_and_answers.md` collecting rephrased, implementation-focused Q&A for future readers ("ask as you'd google it, answer from the code"). Initial entries: (1) why Critical non-compliant items require an evidence photo while Major/Minor do not — how that is decided (`VisitorVisitItem::isCritical()`/`requiresPhoto()`) and enforced (UI `Evidence *` indicator, client + server submit block); (2) how clicking a status button saves to the database instantly and updates the live score — the click → `saveItem()` fetch → `VisitItemController@update` → `VisitService::saveItem()` (stamps `visited_at` on first review) → `VisitScoreService::calculate()` → `refreshCounts()` + `applyScore()` chain; (3) how the Reports-page color filter works — it is a weighted score percentage (`(available − deduction) / available`, `na` items excluded from the denominator), thresholds blue ≥ 85 / green 75–84.9 / yellow 68–74.9 / red < 68 / slate null, implemented by `VisitorReportService::colorSubquery()` `HAVING` bands mirroring `VisitScoreService::colorFor()`; (4) when a CAPA action moves from Open to Closed and why reports still show Open — creation as `open` at submit, `effectiveStatus()` virtual `overdue`, `CapaService::recordUpdate` closes it, and the CAPA management screen is deferred so status stays Open until updated. No code changed; the file is registered in the doc table above.

### Report tables layout fix — 2026-09-02

Fixed `/visitors/reports/{visit}` (`reports/show.blade.php` and `reports/print.blade.php:6,16,17` and `lang/*/visitors.php:108`): the **Corrective Action Plan** table's words were overlapping because 10 columns were crammed without widths or wrapping and the header key `visitors.due_date` had no translation (rendered as `VISITORS.DUE_DATE`). Fix: added `due_date` = Due Date / تاريخ الاستحقاق, gave CAPA a `min-w-[1150px]` horizontally-scrollable table with `min-w-[150px]`/`min-w-[190px]` column minima and `break-words whitespace-normal` wrapping, so Arabic action text can wrap instead of overlapping. All report tables were also aligned per request: headers and body cells are now `text-center` (like the headers in the screenshots) with `px-2` padding and `min-w` on the section/violations tables, and `align-top` kept for multi-line readability. `print.blade.php` mirrors the centering via `th { text-align:center } td { text-align:center; word-wrap:break-word }`. Focused verification: `VisitorReportsTest` + `view:clear`.

### Per-item CAPA Close workflow on the report page — 2026-09-02

Completed the open→closed workflow directly on the report page per request — **single-state, per-row Close, not "close all"**. New `VisitorCapaController@close` (`POST visitors/capa/{capaAction}/close`) and `update` (`PATCH visitors/capa/{capaAction}`) authorized via `VisitorVisitPolicy::viewReport` on the visit (`viewReport` = completed + `visit.manage` or `report.view`+owner). The controller sets `status='closed'`, `completed_at=now()` and appends a `visitors_capa_updates` row via `CapaService::recordUpdate` (`visitors.capa_analytics`/`effectiveStatus()` already handles `overdue` as virtual). `reports/show.blade.php` shows a per-row **Close** button under the status badge only when `effectiveStatus` is `open`/`in_progress`/`overdue` (`onSubmit` confirms with `capa_close_confirm`), with flash `capa_closed_success` / `capa_already_closed` handling and `print:hidden`. Routes: `visitors.capa.close` / `visitors.capa.update` (prefix `visitors`). Lang added `close`, `capa_close_confirm`, `capa_closed_success`, `capa_already_closed`, `capa_updated_success` in `lang/en|ar/visitors.php`. `questions_and_answers.md` Q4 updated to document the now-shipped per-item UI. **Follow-up fix:** removed duplicate flash (layout `app.blade.php:117` already renders `success` globally, the report page had re-rendered it, producing two green banners) — deleted the report-page success/info flash and moved `info` handling to the layout; removed the duplicate `Inspection Visit Report` heading (page `h1` + report `h2` were identical and stacked with the duplicated flash) — the page now shows only the report header (`border-b-2` block) while the top bar keeps the `← Reports` nav + `Print / PDF` action. Verification: `view:clear` + full suite **92 passed / 489 assertions**.

### Roles edit page localization — 2026-09-02

Localized `http://localhost/complaint/public/roles/{role}/edit` (`resources/views/roles/form.blade.php:1`) which previously showed raw permission names (`customer.view`, `complaint.update` …). Added bilingual `lang/en|ar/permissions.php` covering every `PermissionSeeder` permission (56 module·action + `complaint.view_logs`/`complaint.export`/`report.export`/`visit.submit`/`visit.manage`/`visit.master.*`) and `group_customer`…`group_visit` group headers. The form now groups permissions by module (sorted `customer`→`visit`) in card sections with localized headers (`permissions.group_*`) and localized permission labels (`permissions.complaint.view` = View Complaints / عرض الشكاوى), keeping the raw name as the checkbox `value` so `Role::syncPermissions()` is unchanged, with `Super Admin` remaining read-only. Added cancel button (`common.cancel`). No schema/route change; `view:clear` + full suite still **92 passed / 489 assertions**; the file is registered in `memory.md:185` important-files table via the permissions dictionaries entry.



## Customer show SQL Server fix — 2026-08-29

`CustomerController::show()` previously ran a raw aggregate query whose subquery used `limit 1` (`select id from complaint_statuses where name = 'Pending' limit 1`), which SQL Server rejects with `Incorrect syntax near 'limit'` (it requires `TOP 1`). The `$summary` query was already unused: `customers/show.blade.php` computes the total/pending/in-progress/solved/closed counts by filtering the loaded `complaints` collection in PHP (`$customer->complaints->where('status.name', ...)`). The controller no longer builds or passes a `$summary` variable. No schema, route, permission, or frontend change was required. Full verification on 2026-08-29 passed: focused customer/localization suites and the full Laravel suite **46 tests passed / 228 assertions**. `project_structure.md` and `complete_project_specification.md` were synchronized with this fix.

## Pending Features and Future Improvements

The following are intentionally not implemented and may be considered later: full offline PWA behavior, complaint attachments, internal comments, notifications, email/WhatsApp integration, customer portal, password reset, branch-specific user scoping, SLA/escalation workflows, satisfaction ratings, external API clients, scheduled synchronization, and background job workflows.

## Known Issues and Operational Notes

- The default Laravel `welcome.blade.php` remains in the repository but is not the application entry page; `/` is the authenticated dashboard.
- The repository has no sessions migration even though `.env.example` names the database session driver; deployment must address this configuration/schema mismatch.
- `public/storage` is not linked in the current local runtime, and no file-upload feature currently depends on it.
- PWA installation requires a supported browser and HTTPS in production. `localhost` is treated as secure by supported browsers.
- Existing historical activity descriptions may have been written in the locale active at the time of the event. Known action identifiers are now translated when displayed, preventing raw keys from appearing.

## Maintenance Rule

After every meaningful code change — and the user expects this on **every** edit — verify the final implementation and update this file, then keep the other project docs in sync (`project_structure.md`, `database_design.md`, `complete_project_specification.md`, and `questions_and_answers.md` where behaviour is described in Q&A form) whenever the change touches features, localization, schema, routes, permissions, architecture, configuration, UI/flows, tests, deployment, or known limitations. Code is authoritative if this file ever conflicts with the implementation; reconcile the discrepancy instead of preserving stale memory.


## Specification synchronization

`complete_project_specification.md` was reviewed against the current codebase and updated with a **Current Implementation Status** addendum. The original requirements remain preserved as the baseline, while the addendum records verified implementation details and intentional deviations: Blade/vanilla JavaScript rather than Livewire, exact permission assignments, seeded master data, implemented route/workflow coverage, exact 17-column Excel export with short description and timeline, actual audit action identifiers, bounded customer/branch searches, bilingual localization, PWA static-only caching, current test/build verification, and deferred features.

The latest specification verification confirmed the document is synchronized with the implemented Laravel 12 system. The current application has 38 passing tests with 171 assertions; all current migrations report as ran, and the known `.env.example` database-session-driver versus missing sessions-migration issue is documented rather than hidden.


## Mandatory documentation synchronization

Persistent project instructions now require that every code edit or code change—feature work, bug fixes, refactors, configuration changes, route changes, permission changes, and frontend changes—starts by reading `memory.md`, `complete_project_specification.md`, and `project_structure.md`. The same task must update all three after the change and keep them consistent with the verified codebase. `complete_project_specification.md` remains the requirements and verified implementation-status source, `memory.md` remains continuation context, and `project_structure.md` remains the architecture/developer map. Schema changes additionally require `database_design.md`. Relevant tests and build checks must be run before reporting completion, and verification results must be recorded here.


## Complaint/customer pagination and demo data

The complaints index and customers index now use Laravel `paginate(30)->withQueryString()`. The existing Blade views already render paginator links, totals, and current filters, so no client-side pagination was introduced. The 10-record customer picker and 5-record branch picker limits remain separate and unchanged.

`database/seeders/DemoDataSeeder.php` now creates an idempotent dataset of exactly 100 customers and one complaint per demo customer. It preserves the three named sample customers, generates deterministic additional phone values, distributes complaints across seeded branches/services/sources/categories/types/priorities/statuses, and uses `firstOrCreate` keys so rerunning the seeder does not duplicate the demo records. It requires the seeded master data and initial Super Admin user.

Regression tests verify the 30-record first/second pages and the 100-customer/100-complaint seeded dataset. Final verification completed successfully on 2026-08-26: `php artisan db:seed --force`, Blade view caching, the Vite production build, migration status, and the full test suite all passed. The full suite result is **36 tests passed, 161 assertions**.


The pagination regression expectations account for the seeded 100-customer/100-complaint baseline: when 35 additional records are created, both the first and second pages contain 30 rows. The tests verify the configured page size across multiple pages against a realistic result set.


## Dark mode

The shared authenticated layout now includes a localized light/dark switch. An inline bootstrap script sets the saved theme before Vite loads, while `resources/js/app.js` applies the theme, persists it in `localStorage` under `complaint-theme`, updates `aria-pressed`, changes the icon/label, and updates the PWA `theme-color` meta value. The default is light mode when no valid saved preference exists.

`resources/css/app.css` now includes theme-aware base/component styles and explicit dark overrides for the shared layout, `.card`, `.form-input`, `.form-label`, `.page-title`, `.section-title`, `.page-subtitle`, navigation, secondary buttons, tables, picker result panels, alerts, badges, text, borders, and direct light-palette utilities used by module views. The dark-only palette uses brighter text, clearer borders, and stronger hover/focus colors while leaving light mode unchanged. English and Arabic theme labels are present. Regression tests cover the layout bootstrap/toggle markup, form/card/picker dark selectors, component contrast selectors, and both locale dictionaries. Focused dark-mode tests passed with 8 tests and 61 assertions; final full-suite verification then passed with 34 tests and 144 assertions, including Blade caching, the Vite production build, and route verification.


## Branch report pagination

The branch reports page at `/reports/branches` now paginates grouped complaint rows at 30 results per page with `paginate(30)->withQueryString()`. Date-range and multi-branch filters are preserved in paginator links, and the view displays the filtered total through the existing localized results-count pattern. The branch picker remains limited to five initial records with background name search. Regression coverage passed with 17 tests and 59 assertions. Full validation then passed with 35 tests and 152 assertions; Blade views compiled and cached, the Vite production build completed, all migrations reported Ran, and 42 routes were registered.


## Complaint Excel export columns

The filtered complaint Excel export now includes both `Short Description`/`الوصف المختصر` and a combined `Timeline`/`الخط الزمني` column. `ComplaintsExport` eager-loads status-history and activity-log relationships and maps their events into one chronologically sorted, newline-separated cell. Status-history lines include timestamp, actor, previous status, new status, and optional reason; activity lines include timestamp, actor, and localized action labels. Focused export regression coverage passed with 4 tests and 15 assertions, including reuse of the description filter. Full validation then passed with 38 tests and 171 assertions; Blade views compiled and cached, the Vite production build completed, all migrations reported Ran, and 42 routes were registered.


## Complaint description search

The complaints index now has a localized GET field named `description` at the end of the filter grid, displayed as “Search short or full description” in English and `البحث في الوصف المختصر أو الكامل` in Arabic. `ComplaintFilterRequest` validates the optional term as a string with a maximum length of 255 characters. `Complaint::scopeFilter()` trims non-empty input and applies one grouped, parameter-bound `LIKE` condition against `short_description` or `description`.

Because `ComplaintController::index()` and `ComplaintController::export()` both use the validated request filters, the description search is preserved through normal pagination query strings and applies to Excel exports through `ComplaintsExport` as well. No schema migration or route change was needed, and the existing customer/branch picker limits remain unchanged.

Regression coverage now includes a complaint-list test with short-only and full-only matches plus an unrelated complaint, and an export test confirming the shared filter scope excludes non-matching records. Focused suites passed with 18 filter tests/67 assertions and 4 export tests/15 assertions. Final verification on 2026-08-26 passed: Blade views cleared/cached, Vite production build completed, full Laravel suite **38 tests passed with 171 assertions**, all migrations reported `Ran`, and `php artisan route:list` reported 42 routes.

The required living documents were synchronized in this task: `memory.md`, `complete_project_specification.md`, and `project_structure.md`. `database_design.md` was not changed because the feature uses existing complaint columns and introduces no schema change.

## Complaint filter dropdown controls

On the complaints index, the Customer filter is now a closed, searchable single-select dropdown that continues to submit the existing `customer_id` value. The Branch filter is now a closed, searchable multi-select dropdown that continues to submit one hidden `branch_ids[]` input per selected branch. Both panels open from select-style trigger buttons, show bounded searchable results, display selected state, include a localized Clear action when applicable, close on outside click or Escape, and use compact summaries for the selected values. The complaint create/edit customer picker and the branch-report picker retain their legacy markup through compatibility branches in `resources/js/app.js`.

The new controls reuse the existing form-input, border, spacing, and dark-mode palette. Feature coverage verifies the closed dropdown markup while the existing endpoint/filter tests continue to verify customer search, five-result branch search, single-customer filtering, and multi-branch filtering. Focused complaint-filter verification passed with 19 tests and 75 assertions; full verification on 2026-08-27 passed with 39 tests and 179 assertions, Blade view caching, the Vite production build, all migrations reported `Ran`, and 42 registered routes.


## Dashboard redesign

The attached advanced dashboard specification is now implemented and verified as a real-data Laravel/Blade analytics dashboard. `DashboardFilterRequest`, `DashboardStatsService`, and `DashboardController` provide validated filters, SQL-backed KPI/chart/ranking data, comparison periods, recent/attention/solved complaint lists, and rule-based insights. The dashboard Blade layout, Chart.js rendering, bilingual labels, regression tests, and final build/test verification are complete. The design keeps the existing authenticated root route and `complaint.view` authorization, treats Solved and Closed as resolved, and calculates average resolution time only from non-negative `created_at` to `resolved_at` intervals.


The verified dashboard implementation includes validated filters, SQL/Eloquent metrics, trend/distribution charts, branch performance, recent/attention/solved complaint lists, rule-based insights, bilingual labels, Chart.js rendering, and actionable complaint-list/detail links. The final Vite build and Laravel suite passed after the intermediate Blade/chart and test assertion corrections.


During dashboard implementation, the Blade chart-card variable, trend-date alias, and indexed branch-link test expectation were corrected. The final focused dashboard suite passed with 2 tests and 10 assertions, and the complete suite passed afterward.


The dashboard regression assertion now matches Laravel’s indexed query-string encoding (`branch_ids[0]`) for actionable branch links. Focused dashboard verification is ready to rerun; the dashboard trend alias correction and Blade chart-card fix are both recorded, and the advanced dashboard remains pending final validation.


The focused dashboard test now checks the rendered complaint ID for the filtered recent-complaints row rather than expecting the short description, which the dashboard intentionally summarizes through complaint ID/customer/branch/type. The branch actionable-link assertion remains aligned with indexed `branch_ids[0]` encoding. Focused verification must be rerun before completion.


The dashboard total-complaints KPI now links to the complaint list with the complete active dashboard filter query, rather than incorrectly appending the Pending status. Pending, In Progress, and Solved KPI links still add their respective status filters. The dashboard feature tests passed before this navigation-only correction; final verification remains pending. A missing `multi_select_hint` translation key identified during review still needs to be added to both locales.


The English locale now includes `multi_select_hint`, used by the dashboard’s native multi-branch select. The Arabic equivalent is still pending; dashboard verification remains in progress.


The Arabic locale now includes `multi_select_hint` (`اضغط Ctrl/Cmd لاختيار عدة فروع`), completing the dashboard branch-filter hint pair. Focused dashboard tests previously passed; the updated locale and total-card navigation now require the final localization/build/test verification.


Localization regression coverage now asserts the dashboard filter label, branch multi-select hint, and Solved/Closed resolution-definition text in both English and Arabic. The dashboard feature tests previously passed with 2 tests and 10 assertions; the new localization test must be included in the final full verification.


## Dashboard final verification — 2026-08-27

The advanced dashboard is complete and verified. It uses `DashboardFilterRequest`, `DashboardStatsService`, and `DashboardController` with the existing authenticated root route and `complaint.view` access. The Blade view provides date/master-data filters, KPI cards, trends, seven distribution charts, branch performance, recent/attention/solved lists, actionable complaint links, and rule-based insights. Chart.js renders the responsive trend/distribution canvases with light/dark-aware colors. Solved and Closed count as resolved; average resolution time uses only valid `created_at` to `resolved_at` intervals. No schema migration or new route/permission was required.

Final checks passed: focused dashboard coverage 2 tests/10 assertions; full Laravel suite 42 tests/195 assertions; Blade views cleared and cached; Vite production build completed; all migrations reported `Ran`; route list reported 42 routes; and `git diff --check` passed. The sandbox browser could not connect to the user’s XAMPP-only localhost service, so independent live desktop/mobile visual inspection was not performed; automated Blade/frontend/feature checks passed. Earlier dashboard debugging notes in this file describe intermediate corrections and are superseded by this final record.


The dashboard statistics service now seeds its branch filter with the first five branches ordered by name and appends any selected branch IDs that fall outside that seed. This keeps the dashboard aligned with the existing bounded branch-picker pattern while preserving active multi-branch selections. The dashboard/report/create selector standardization is now in progress; final tests remain pending.


The dashboard Branch filter now uses the shared closed `data-filter-dropdown` multi-select markup with `branch_ids[]` hidden inputs, bounded initial branches, remote name search, selected-state checks, Clear action, and outside-click/Escape closing. Dashboard behavior remains backed by the existing branch filter contract; the report and complaint-create controls are still pending standardization.


The branch-report Branch filter now uses the shared closed searchable multi-select markup while preserving the existing `branch_ids[]` inputs, five-result bounded seed, remote name search, selected-state checks, Clear action, and outside-click/Escape behavior. The complaint-create Customer picker remains the final selector to standardize.


The complaint create/edit Customer control now uses the shared closed `data-filter-dropdown` single-select markup while preserving the required `customer_id` hidden input. It keeps the bounded initial ten-customer list, background name/any-phone search, selected state, Clear action, outside-click/Escape closing, and localized summary. Dashboard and branch-report branch controls are also now standardized; regression coverage and final verification remain pending.


Regression coverage now verifies that the dashboard Branch picker, branch-report Branch picker, and complaint create Customer picker all render as closed dropdown controls with the expected hidden-input contracts. Focused tests remain to be run after this coverage addition.


## Cross-page picker standardization — final verification

The dashboard Branch picker, branch-report Branch picker, and complaint create/edit Customer picker now all use the shared closed dropdown patterns already implemented in `resources/js/app.js`. Dashboard and reports preserve searchable Branch multi-select with `branch_ids[]`; complaint creation preserves searchable Customer single-select with `customer_id`. The existing five-branch and ten-customer bounded initial-result rules, native `fetch()` search, 250 ms debounce, request cancellation, selected states, Clear actions, outside-click/Escape closing, dark styling, localization, and backend contracts remain intact. The dashboard service supplies five initial branches plus selected out-of-seed branches.

Focused cross-page picker tests passed with 22 tests and 95 assertions. Final full verification passed with 43 tests and 205 assertions; Blade views cleared/cached, Vite production build completed, all migrations reported `Ran`, 42 routes were listed, and `git diff --check` passed. No schema migration, route, permission, or backend filter contract change was required. The sandbox browser still cannot connect to the user’s XAMPP-only localhost service, so live desktop/mobile visual inspection was not independently performed; automated Blade/frontend/feature checks passed. Earlier picker-standardization progress notes above are superseded by this final record.


The dashboard Branch filter no longer renders the `Hold Ctrl/Cmd to select multiple branches` helper text. The closed multi-select control, internal search, selection state, Clear action, and backend `branch_ids[]` behavior remain unchanged. The hint remains available for other controls/localization but is intentionally unused on the dashboard.


The attached local `.env` is configured for SQL Server using database `complaints`, the local default host/port (`127.0.0.1:1433`), and SQL authentication. Credentials are intentionally not recorded in project documentation. The SQL Server PHP extensions (`pdo_sqlsrv` and `sqlsrv`) are installed in the attached Windows PHP runtime. Connection, migrations, and seeders were subsequently verified successfully.


Laravel’s `sqlsrv` connection reads `DB_ENCRYPT` and `DB_TRUST_SERVER_CERTIFICATE` from the local environment. The local configuration uses encryption disabled and trusts the local server certificate for the supplied development SQL Server setup. Credentials remain excluded from documentation. Connection, migrations, and seeders were subsequently verified successfully.


The first SQL Server connection attempt reached the ODBC driver but rejected the boolean `DB_ENCRYPT=false` value as invalid for the ODBC Driver 17 `Encrypt` attribute. The local `.env` was corrected to use the ODBC-compatible string value `DB_ENCRYPT=no`; `DB_TRUST_SERVER_CERTIFICATE=true` remains enabled for this local development connection. The corrected connection, migrations, and seeders were verified successfully.


The first SQL Server migration run created the migration repository and completed the users, cache, jobs, permission, and complaint master-data migrations, then failed in `2026_08_23_161300_create_complaints_table.php` because SQL Server rejects `ON DELETE RESTRICT`. The failed migration did not leave a `complaints` table. The migration was updated to use Laravel’s explicit `noActionOnDelete()` for required complaint foreign keys; this preserves the intended restrictive/no-action delete policy while generating SQL Server-compatible DDL. The complaint-history migration subsequently received the same compatibility update before the migration sequence was rerun.


The complaint-history migration `2026_08_23_161400_create_complaint_history_tables.php` was updated to use `noActionOnDelete()` for its required status-history foreign keys. Both complaint-domain migrations now preserve restrictive/no-action deletion semantics while avoiding SQL Server’s unsupported `ON DELETE RESTRICT` syntax. The SQL Server migration sequence was rerun successfully from the failed complaints migration; no `complaints` table was present after the failed attempt.


A temporary root-level `.sqlsrv_verify.php` script was created solely to bootstrap Laravel and report aggregate SQL Server seed counts without exposing credentials. It was removed after verification and is not part of the application architecture.


SQL Server verification completed successfully. `php artisan migrate --database=sqlsrv --force` completed all pending migrations, including complaints and complaint history. `php artisan db:seed --database=sqlsrv --force` completed PermissionSeeder, MasterDataSeeder, and DemoDataSeeder. A temporary bootstrap count check reported 100 customers, 100 complaints, 4 roles, 3 branches, 3 services, 6 sources, 6 categories, 6 types, 4 priorities, 5 statuses, and 1 `admin@example.com` user. The temporary verifier was removed. The first non-destructive status check after configuration connected successfully and reported only that the migration table did not yet exist; the final post-migration status check showed every migration as `Ran`.


The repository’s `.gitignore` now ignores `.env` so local SQL Server credentials are not newly added to version control. The previously tracked `.env` was removed from the Git index while preserving the local file; no credential value is recorded here.


The tracked `.env` entry was removed from the Git index with `git rm --cached`; the local file remains in place for the attached runtime and is now protected by `.gitignore`. Its credential value was never copied into documentation or output.


Final verification on 2026-08-27: `php artisan migrate:status --database=sqlsrv` showed all seven migrations as `Ran` across batches 1 and 2. The PHPUnit suite passed with 43 tests and 205 assertions using its configured in-memory SQLite test database. `php artisan view:cache` passed, `npm.cmd run build` passed with Vite 7.3.6, `git diff --check` passed, and Laravel reported 42 registered routes. The XAMPP HTTP smoke request to `/complaint/public/` returned HTTP 302, consistent with the authenticated application redirect. An initial `npm run build` attempt was blocked by the Windows PowerShell execution policy for `npm.ps1`; the equivalent `npm.cmd run build` completed successfully.


Final repository protection check passed: `git check-ignore -v .env` matched the `.env` rule, and `git ls-files --error-unmatch .env` reported it is not tracked. The local `.env` remains available to the attached XAMPP runtime without exposing its credential contents.


## SQL Server dashboard trend compatibility

The dashboard login failure was traced to `DashboardStatsService::trend()`, which used MySQL/SQLite-style `DATE(complaint_date)` and grouped by its alias. SQL Server does not provide `DATE()` as a built-in function. The service now selects `CAST(complaint_date AS date)` for the `sqlsrv` driver and retains `DATE(complaint_date)` for the SQLite/MySQL test and supported paths; the same driver-selected expression is used in `GROUP BY` and `ORDER BY`. This preserves the existing day/week/month bucket behavior without changing dashboard metrics or filters. The SQL Server dashboard smoke check now passes.


A temporary root-level `.sqlsrv_dashboard_verify.php` script was created solely to bootstrap Laravel and execute `DashboardStatsService::build([])` against the configured SQL Server connection. It reported aggregate trend metadata (`total=100`, daily grouping, 30 trend points), passed, and was removed; it is not part of the application architecture.


Final verification for the SQL Server dashboard fix completed on 2026-08-27. The live SQL Server dashboard smoke check executed `DashboardStatsService::build([])` successfully and reported `total=100`, daily grouping, and 30 trend points. The temporary smoke script was removed. The full PHPUnit suite passed with 43 tests and 205 assertions using in-memory SQLite; Blade view caching passed; `npm.cmd run build` passed; Laravel reported 42 routes; and `git diff --check` passed. No schema, route, permission, or database-design change was required.


The branch-report localization issue was traced to the missing `common.results_count` key. The English dictionary now defines `results_count` as `:count results`; the Arabic dictionary still needs its matching entry before verification is complete.


The Arabic common dictionary now defines `results_count` as `عدد النتائج: :count`, completing the bilingual translation for the branch-report total shown above the paginated table. Focused localization and branch-report regression verification remain to be run.


Regression coverage was added to `ComplaintFiltersAndAuthorizationTest` for the exact `/reports/branches` rendering path. It requests a future empty date range in English and Arabic and asserts `0 results` and `عدد النتائج: 0`, respectively. The focused and full test suites remain to be run after this addition.


Final verification for the branch-report results-count localization completed on 2026-08-27. The focused localization and complaint/filter suites passed with 26 tests and 125 assertions, including the exact English `0 results` and Arabic `عدد النتائج: 0` branch-report rendering. The full suite passed with 44 tests and 209 assertions. Blade view caching, `npm.cmd run build`, and `git diff --check` also passed. No schema, route, permission, or database-design change was required.


## Dashboard undefined legend fix

The dashboard distribution bar charts showed an `undefined` legend item because the shared Chart.js `addChart()` helper enabled legends globally while its single dataset had no `label`. The helper now keeps legends enabled for doughnut charts, whose legends correctly use their category labels, and explicitly disables legends for the single-dataset bar charts (branches, categories, types, and services). This removes the undefined legend without changing chart data or analytics. Regression and build verification are pending for this fix.


Regression coverage was added to `DashboardTest` to ensure the shared Chart.js helper contains the explicit single-dataset bar-chart legend suppression. This protects the fix against future changes that could reintroduce an `undefined` legend item. Dashboard, full-suite, build, and diff verification are pending for this fix.


Final verification for the dashboard undefined-legend fix completed on 2026-08-27. The dashboard-focused suite passed with 3 tests and 12 assertions, including the bar-chart legend regression. The full PHPUnit suite passed with 45 tests and 211 assertions. Blade view caching, `npm.cmd run build`, and `git diff --check` also passed. No schema, route, permission, or database-design change was required.


The shared footer implementation has started with English localization keys for contact, phone, location, social links, LinkedIn, Facebook, and the copyright message. The Arabic equivalents and shared layout markup remain to be added before verification is complete.


Arabic footer localization is now defined for the same contact, phone, location, social-link, LinkedIn, Facebook, and copyright concepts. The shared layout footer markup remains to be added before verification is complete.


English footer contact values are now localized as `Abdelrahman Emad`, `01110174868`, and `Alexandria`, alongside the footer labels and social/copyright keys. The Arabic value equivalents and shared layout markup remain to be completed and verified.


Arabic footer contact values are now localized as `عبدالرحمن عماد`, `01110174868`, and `الإسكندرية`, matching the user-provided contact details. The shared layout footer markup remains to be completed and verified.


The shared `resources/views/layouts/app.blade.php` now includes a responsive footer on all layout pages. It displays the localized contact name, clickable phone number, Alexandria location, LinkedIn and Facebook links opening safely in a new tab, and a localized copyright line using the current year. The page shell uses a flex column with a flexible main area so the footer sits at the bottom on short pages. Footer regression and build verification are pending.


`PwaTest` now covers the shared footer on the authenticated dashboard in both locales, asserting the provided contact name, city, phone link, LinkedIn/Facebook URLs, and copyright text. Footer and full verification are pending.


Final verification for the shared contact and copyright footer completed on 2026-08-27. `PwaTest` passed with 5 tests and 42 assertions, including English/Arabic footer content and contact links. The full PHPUnit suite passed with 46 tests and 222 assertions. Blade view caching, `npm.cmd run build`, and `git diff --check` passed. The footer displays the provided contact name, phone, Alexandria location, LinkedIn/Facebook links, and current-year copyright text. No schema, route, permission, or database-design change was required.


The footer was revised per the latest UI request: the location column was removed, the layout now uses two compact columns for contact and social links, vertical spacing was reduced, and the copyright row was shortened. The footer remains localized and responsive. Verification is pending for this revision.


`PwaTest` was updated for the compact footer revision: it now verifies the contact and social links remain present in English and Arabic while Alexandria/الإسكندرية and the location labels are absent. Focused and full verification are pending.


Final verification for the compact footer revision completed on 2026-08-27. `PwaTest` passed with 5 tests and 44 assertions, verifying contact/social content and the absence of the location section in English and Arabic. The full PHPUnit suite passed with 46 tests and 224 assertions. Blade view caching, `npm.cmd run build`, and `git diff --check` passed. The footer is now a compact two-column contact/social layout with a shortened copyright row; no location content is rendered. No schema, route, permission, or database-design change was required.


The footer was compacted again per the latest UI request. The contact name, phone, social links, and copyright now share one responsive horizontal bar with minimal padding and wrapping only when the viewport is narrow. The location section remains removed, and localization, dark mode, and RTL behavior are preserved. Verification is pending for this revision.


`PwaTest` now also checks the minimal footer structure: the shared layout uses a flex-wrapping horizontal bar with compact padding and no location translation usage. Focused and full verification are pending for this revision.


Final verification for the minimal horizontal footer completed on 2026-08-27. `PwaTest` passed with 5 tests and 48 assertions, including contact/social content, the absence of location content, and the compact layout structure. The full PHPUnit suite passed with 46 tests and 228 assertions. Blade view caching, `npm.cmd run build`, and `git diff --check` passed. The footer now uses one compact responsive bar with minimal padding and no rendered location section. No schema, route, permission, or database-design change was required.
