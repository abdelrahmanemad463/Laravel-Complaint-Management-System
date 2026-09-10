# Complaint Desk — Persistent Project Memory

**Last synchronized:** 2026-09-10
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
        ↓
2026_09_04_190000_replace_deadline_with_period_hours
        ↓
2026_09_06_000001_add_serial_number_and_price_to_complaints_table
        ↓
2026_09_06_000002_add_category_priority_to_complaint_types_table
        ↓
2026_09_06_000003_extend_visitors_violation_follow_up
        ↓
2026_09_07_000100_create_visitors_violation_follow_ups
        ↓
2026_09_07_000200_add_resolution_review_workflow
        ↓
2026_09_08_000100_make_master_data_bilingual
```

### Core tables

| Table | Purpose | Important details |
|---|---|---|
| `users` | Authenticated staff | Standard Laravel user fields; complaint actors reference `users.id` |
| `customers` | Customer identity/contact | Name, four phone fields, optional address, timestamps, soft deletes; phone columns indexed |
| `branches` | Branch master data | `name_en`, `name_ar`, optional indexed code, active flag, sort order, soft deletes; `localized_name` accessor returns locale-appropriate name |
| `services` | Service master data | `name_en`, `name_ar`, optional color, active flag, sort order, soft deletes |
| `complaint_sources` | Complaint source master data | `name_en`, `name_ar`, optional color, active flag, sort order, soft deletes |
| `complaint_categories` | Complaint category master data | `name_en`, `name_ar`, optional color, active flag, sort order, soft deletes |
| `complaint_types` | Complaint type master data | `name_en`, `name_ar`, optional color, **required `category_id` → complaint_categories and `priority_id` → priorities**, active flag, sort order, soft deletes |
| `priorities` | Priority master data | `name_en`, `name_ar`, required color, optional level, active flag, sort order, soft deletes |
| `complaint_statuses` | Status master data | `name_en`, `name_ar`, required color, active flag, sort order, soft deletes |
| `complaints` | Main complaint record | Required master-data/customer references, descriptions/date, creator; nullable resolver/resolution fields; `category_id`/`priority_id` are derived from the selected complaint type (not client-editable); soft deletes |
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
- Category and priority are never user-editable on complaint create/edit: they are derived from the selected complaint type on create, and re-derived only when the type changes on edit. Both store and update validate that the type has `category_id` and `priority_id` configured (error key `common.type_missing_category_priority`), so a client cannot submit arbitrary category/priority values.
- Status changes create both a history row and an activity row inside a transaction.
- The configured status named Solved sets resolution actor/time metadata.
- Complaint and report lists use database pagination and query-string preservation.
- Customer and branch pickers use bounded initial results to avoid loading large tables into the page.
- Activity action identifiers are stable storage values; views translate known identifiers at render time.
- Server-side authorization is mandatory. Blade `@can` checks are only the interface layer.
- Super Admin access is protected through a Gate bypass and role-management safeguards.
- Baseline roles are `Super Admin`, `Admin`, `Customer Support`, and `Viewer`.
- Role deletion is blocked when the role is protected or assigned to a user.
- Master-data names are stored as `name_en`/`name_ar` columns on the 7 complaint master tables; the centralized `localized_name` accessor returns the locale-appropriate value with a fallback chain (`name_ar ?: name_en` under ar locale, `name_en ?: name_ar` under en locale). Branch names are also shared with the visitors module. The legacy single `name` column has been dropped; name-keyed business logic (dashboard solved/pending/high-critical detection, `ComplaintStatusService` solved check, type→category+priority linkage) matches `name_en` because the original seeded production data is English.

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
| `app/helpers.php` | `pdf_ar()` Arabic PDF text shaping helper (ar-php `utf8Glyphs`) |
| `config/dompdf.php` | Published dompdf config (`font_dir` = `storage/fonts`) |
| `storage/fonts/` | Amiri-Regular/Bold TTFs for Arabic PDF rendering |
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

The test suite contains feature coverage for customer search and CRUD, complaint creation/update/status history, filters, export, authorization, role lifecycle, users filters, localization, PWA metadata/cache boundaries, dashboard analytics/filter payloads, quality-visits inspection/CAPA workflows, visitor reports (including `capa_due` aggregates and the `due_status` filter), and known Blade regression cases. Unit coverage includes `tests/Unit/Visitors/DueDateServiceTest.php` (14 tests for `periodToHours`/`dueDate`/`hoursLabel`/`dueStatus`); application services are otherwise primarily covered through feature tests.

The latest complete validation after the due-date / `period_hours` feature was successful:

- Blade views cleared and cached successfully.
- Vite production build completed successfully.
- Full Laravel suite: **163 tests passed, 991 assertions**.
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

There are no unverified feature changes currently pending from the previous implementation work. The most recent completed work was the **all-branches multi-select dropdown enhancement** (2026-09-10, see the entry at the end of this file): branch pickers on the complaints index, dashboard, and branch report now render **every** branch (no server `limit(5)` / `.slice(0, 5)`), scroll after ~7 rows (`max-h-64`), offer an **All** select-all / clear-all option (`data-all-branches-label`, localized `common.all`), and keep search — now **client-side** over the full loaded list (the `complaints.branches.search` JSON endpoint remains but is no longer consumed by the UI). `/complaints/create` + edit each got a new **single-select** branch picker (`data-single-select` on the shared picker JS: one `branch_id` hidden input, name summary, click-to-select closes, no All row). Full suite **165 passed / 999 assertions**. Future development should first read this file and `project_structure.md`, then update both when a meaningful architectural, schema, feature, configuration, or workflow change is made.


## Quality Visits (Inspection) module — 2026-08-31

A new **Quality Visits** inspection module was added alongside the existing complaint management without breaking it. It uses `visitors_`-prefixed tables, `Visitor*` models, dedicated services, a policy, seeders, routes under a `visitors.` prefix, and Blade views (`visitors/home`, `create`, `open`, `show`) with vanilla-JS autosave.

- **Schema (all SQL Server-compatible):** `visitors_visit_types`, `visitors_sections`, `visitors_root_causes`, `visitors_checklist_items`, `visitors_visits`, `visitors_visit_items` (with immutable snapshot columns), `visitors_visit_photos`, `visitors_capa_actions`, `visitors_capa_updates`. The visitors transaction migration initially failed on SQL Server due to its "multiple cascade paths" rule; the `visit_item_id` FKs on `visitors_visit_photos` and `visitors_capa_actions` and the two `users` FKs on `visitors_capa_actions` now use `noActionOnDelete()` instead of `cascadeOnDelete()`/`nullOnDelete()`. The `visitors_`-prefixed table names deviate from Eloquent's snake-case convention, so every `Visitor*` model declares `protected $table` and every `belongsTo`/`hasMany` relation declares its foreign key explicitly.
- **Models:** `VisitorVisitType`, `VisitorSection`, `VisitorRootCause`, `VisitorChecklistItem`, `VisitorVisit`, `VisitorVisitItem`, `VisitorVisitPhoto`, `VisitorCapaAction`, `VisitorCapaUpdate`.
- **Services:** `VisitScoreService` (single source of truth for score), `VisitService` (start/saveItem/submit), `CapaService` (create CAPA for non-compliant items on submit), `DueDateService` (single source of truth for due-date logic: period-to-hours conversion, due-date computation, locale-aware labels, and virtual due-status resolution), `VisitorPhotoService` (GD compression, 20 MB cap), `ChecklistImportService` (Excel import helper).
- **Authorization:** `VisitorVisitPolicy` (view/update/submit: owner + not-completed) registered in `AppServiceProvider` via `Gate::policy`; Super Admin bypasses via the existing `Gate::before`. Controllers manually import `AuthorizesRequests` because the base `Controller` is empty.
- **Permissions/routes:** added `visit.submit`/`visit.manage` plus module `visit.*`; Customer Support and Viewer roles grant `visit.view`/`visit.create`/`visit.update`. Routes are registered under `visitors.` prefix with static `open`/`create` routes defined before the `{visit}` wildcard.
- **Score rules:** available = sum deduction of non-NA items; deduction = sum for NC; final = available − deduction; percentage = (final/available)×100 with colors ≥85 blue, 75–84 green, 68–74 yellow, <68 red, all in `VisitScoreService`.
- **Workflow rules:** items start `status=pending` with `visited_at` null at creation (nothing chosen by default — the inspector must actively pick Compliant / Non-compliant / Not Applicable per item, and only then does `visited_at` get stamped and the item count as reviewed); progress based on `visited_at`; the **live score is hidden on the inspection page** (score only appears on completed-visit reports / PDF / dashboard) so a visit creator never sees the branch's score while filling it out; snapshots copied to `visitors_visit_items` at creation; score computed only on the backend; photos stored privately on the `local` disk and served through an authenticated route; photos required when `nc` + critical OR `photo_required` (severity-based, independent of deduction — `VisitorVisitItem::isCritical()` lowercases severity and `requiresPhoto()` = `($status==='nc' && isCritical()) || photo_required`); hidden/suggested values are never auto-filling; `submit()` validates all items reviewed + required photos (throws `__('visitors.evidence_photo_required')` when a Critical NC item has no photo), creates CAPA from NC items, sets `completed`+`completed_at` inside a transaction; users only access their own `in_progress` visits (Open Visits scoped to `inspector_id = auth user AND status = in_progress`). *(2026-09-02: default briefly changed to Compliant then reverted back to no-default per user; live score removed from inspection page same day.)*
- **Localization:** added `lang/en/visitors.php` and `lang/ar/visitors.php`; added `customer_complaints`/`quality_visits` nav keys to `common.php` in both locales; added `@stack('scripts')` to the layout.
- **Verification:** added `tests/Feature/VisitorsTest.php` (14 tests, two of which assert the `evidence_photo_required` message on Critical-NC-without-photo submit). Frontend: the inspection view (`show.blade.php`) shows a `📷 Evidence *` + `photo_required_critical` indicator (toggled dynamically when a Critical item is marked NC) and blocks submit via `alert(evidence_photo_required)` until every Critical NC item has at least one photo. Full suite: **60 tests passed / 296 assertions**. Migrations + seeders run against SQL Server and the SQLite test DB.

### Quality Visits Reports module (2026-08-31)

- **Reuse/no duplication:** `VisitScoreService::fromAggregates()` is the single source of score truth, used by `calculate()`, the report list, and charts. Reports read only the immutable `visitors_visit_items` snapshot columns (never master checklist values) and existing CAPA rows; `VisitorCapaAction::effectiveStatus()` (delegating to `DueDateService::dueStatus()`) provides the virtual due statuses — open/overdue/due_soon/upcoming/immediate/completed/closed_late/rejected — reused by views and the dashboard (overdue derives from the stored `due_at`, not the legacy `due_date` field).
- **Services:** `VisitorReportService` (`build()` single QHSE report; `listReports()` paginated DB-aggregated list with branch/type/inspector/date/score-color filters), `VisitorReportDashboardService` (SQL joins/groupBy analytics: cards, severity/section/root-cause distributions, per-branch weighted score + best/worst, recurring/critical violations, CAPA status + average time-to-close, due-date analytics — `dueCards`, `dueStatusChart`, `branchDueAnalysis`, with overdue split via `due_at` — inspector performance, day/week/month trend), `VisitorPdfService` (dompdf `render()/stream()/download()` of the self-contained-CSS `visitors/reports/print` view, embedding private photos via `Storage::disk('local')->path()`). Dependencies: `barryvdh/laravel-dompdf ^3.1` (composer).
- **Authorization:** reports are NOT owner-restricted. `VisitorVisitPolicy::viewReport()` = visit completed AND (`visit.manage` OR (`report.view` AND owner)); new `Gate::define('visitors.reports', ...)` = `report.view` OR `visit.manage`; Super Admin bypasses via existing `Gate::before`. Customer Support (inspectors) → 403.
- **Controller/routes:** `VisitorReportController` (index/show/dashboard/pdf); routes `visitors/reports`, `visitors/reports/dashboard`, `visitors/reports/{visit}`, `visitors/reports/{visit}/pdf` registered BEFORE the `{visit}` wildcard. Views `visitors/reports/{index,show,dashboard,print}` plus a home Reports card link.
- **SQL Server gotchas:** color filter uses `HAVING` with repeated aggregate expressions (aliases can't be referenced in `HAVING`, and subquery select lists for `IN` must have one column); average time-to-close CAPA computed in PHP (`DATEDIFF(DAY,...)` unsupported on SQLite).
- **Verification:** added `tests/Feature/VisitorReportsTest.php` (15 tests; manager uses `Admin` role so in-progress 403 is enforceable, since Super Admin bypasses the gate). Full suite: **76 tests passed / 433 assertions**. List filter, individual report, PDF (~881 KB), and dashboard analytics smoke-tested against live SQL Server.

### Quality Visits Master Data (Excel) module (2026-08-31)

An **Excel Master Data management UI** was added on top of the existing Quality Visits module to manage the inspection checklist via `.xlsx`/`.xls` import, preview, confirm, and current-data export — without changing complaint or visit functionality.

- **Reuse (no breakage):** reuses `visitors_visit_types` (inspection types), `visitors_sections`, `visitors_root_causes`, `visitors_checklist_items` (items) as the canonical runtime master. New tables only: `visitors_severities`, `visitors_imports`, `visitors_import_rows`; plus nullable `root_cause_id` FK added to `visitors_checklist_items` (suggested default root cause; inspector still chooses at NC time). Historic `visitors_visit_items` snapshots are never overwritten.
- **New models:** `VisitorSeverity`, `VisitorImport` (statuses pending/validating/ready/imported/failed/cancelled; `validRows()`/`invalidRows()`/`isReady()`/`isImported()`), `VisitorImportRow` (`errorsList()`, `dataArray()`). `VisitorChecklistItem` gained `root_cause_id` fillable + `rootCause()`.
- **Upload rules:** `.xlsx`/`.xls` only, 50 MB cap (`Maximum allowed file size is 50 MB.`), real MIME verified (not just extension). Chunk-read via `MasterDataChunkReadFilter` (200-row slices). Exact 11-column order: code, inspection_type, section, note, severity, immediate_action, corrective_action, responsible, period_hours, preventive_action, deduction_score. (`item` is named `note`/ملاحظة, `deduction` is `deduction_score`, and `root_cause` was removed from the file — the inspector chooses it during the visit.)
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
- **Custom "Install App" button: (REMOVED 2026-09-06 — see the entry at the end of this file).** `resources/views/layouts/app.blade.php` now has a desktop header button (`data-install-button`, `hidden sm:inline-flex`) and a full-width mobile-drawer button (`data-install-button-mobile`) plus a bilingual `data-install-modal` with Android/iOS add-to-home-screen instructions, rendered before the scripts stack. `resources/js/app.js` captures `beforeinstallprompt`, detects standalone mode (`display-mode: standalone`) and iOS, reveals the buttons, and falls back to the instructions modal when there is no deferred prompt. The mobile button is **always revealed** on non-iOS because `beforeinstallprompt` never fires over plain HTTP from an Android phone (`http://<LAN-IP>`); the button hides again after `appinstalled`.

- **Install translations: (REMOVED 2026-09-06 with the button feature.)** `lang/en|ar/common.php` gained `install_app`, `add_to_home_screen`, `install_instructions_title`, `install_instructions_android`, `install_instructions_ios`, `install_now`, `install_close` (221 keys each locale). Arabic was patched via a PHP script after a PowerShell `Set-Content -Encoding UTF8` double-encoded the file (restored from git); later lang edits use .NET `File::ReadAllText/WriteAllText` with UTF-8 no-BOM.
- **Arabic branding:** hardcoded "Complaint Desk" replaced by `__('common.application_name')` (`en` = Complaint Desk, `ar` = نظام الشكاوى) for the head meta/apple-web-app title, `<title>`, desktop header, and mobile drawer header.
- **Navbar RTL overlap fix:** the desktop/hamburger breakpoint moved from `lg` (1024px) to `xl` (1280px) — nav is `hidden min-w-0 flex-1 items-center justify-center xl:flex`, toggle + drawer are `xl:hidden`, JS resize check uses `matchMedia('(min-width: 1280px)')`; logo and controls are `shrink-0` so the Arabic brand (`نظام الشكاوى`) no longer collides with menu items. User name shows from `xl:inline`; avatar is `xl:hidden`.
- **Nav/drawer animations (`resources/css/app.css`):** drawer slide+fade (transform 360ms cubic-bezier + opacity 260ms, `will-change`), overlay fade, staggered `navItemIn` keyframes at 40ms increments for the drawer links, hamburger-icon rotate/crossfade (200ms), desktop `[data-nav-dropdown]` fade/translate/scale on `[open]`, `prefers-reduced-motion` disables all; viewer JS opens with a `requestAnimationFrame` double-tick and hides the drawer 360ms after close. Mobile drawer also gained dark-mode contrast overrides (panel `#1e293b`, active `bg-#3730a3`, borders `#334155`).
- **Verification:** assets rebuilt against the hashed Vite manifest (`npm run build` + `php artisan view:clear`). Full suite after this work: **91 tests passed / 479 assertions**.

### Master-data Excel format change + PhpSpreadsheet 4.x compat fix — 2026-09-02

The master-data Excel format was slimmed to **11 columns** across the shared headings trait, the download template/current-data exports, upload parsing/validation, the import confirm step, and the master-data table/preview UI labels.

- **New columns:** `code, inspection_type, section, note, severity, immediate_action, corrective_action, responsible, period_hours, preventive_action, deduction_score`. `root_cause` was **removed** from the file (the visitor picks the root cause at NC time; `root_cause_id` on `visitors_checklist_items` is unchanged as the suggested value), `item` was renamed `note` (Arabic ملاحظة) and `deduction` renamed `deduction_score`. Confirmation still writes `title`, `deduction_score`, `photo_required`; it no longer sets `root_cause_id`. UI labels changed to Note / ملاحظة and Deduction Score / درجة الخصم in both locales; legacy files using the old headers are rejected (they no longer match the known header list).
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

### Submit Visit button moved to bottom of inspection page — 2026-09-02

User asked for the Submit button to be at the bottom of the inspection page so it's reached after marking all checkboxes. `resources/views/visitors/show.blade.php`: removed the `#submit-visit-form` (with its `btn-primary` Submit button) from the page header (the header now keeps just the progress card + the `Completed` badge for completed visits) and re-added the same `#submit-visit-form` at the **bottom**, just after the `#visit-checklist` form closes. It is `hidden ... sm:flex` so only the **desktop** bottom button renders there (right-aligned); on mobile the existing sticky bottom bar still provides the Submit button, whose `type="submit" form="submit-visit-form"` attribute keeps pointing at the (now hidden on small screens) form and still submits correctly. No test asserted the header placement; full suite **92 passed / 489 assertions**.

### Arabic PDF report fixed (dompdf + ar-php shaping + Amiri font) — 2026-09-02

User reported that exporting a report to PDF (`visitors/reports`) produced garbled/unjoined/reversed Arabic (e.g. `ةنايصلاو عقومل`). Root cause: dompdf's CPDF backend does **not** shape or re-order RTL text, and the bundled DejaVu Sans has **no Arabic presentation-form glyphs** (U+FB50–FEFF), so letters rendered as isolated glyphs in logical order. Fix (user approved installing a package + font):

- **Package:** `khaled.alshamaa/ar-php` `^7.0` (the canonical ar-php; the abandoned `ar-php/ar-php` tawfekov fork had no `shape()`/`utf8Glyphs()` and was swapped out). Composer noted it as the recommended replacement for the abandoned one.
- **Font:** downloaded OFL-licensed **Amiri** into `storage/fonts/` (`Amiri-Regular.ttf`, `Amiri-Bold.ttf`). Verified via a TTF cmap parse that Amiri contains every presentation-form codepoint (e.g. U+FE94, FEE7, FE8E, FEF4, FEBB, FECA, FED7, FEEE, FEE3) needed by shaped Arabic.
- **Helper:** new `app/helpers.php` (registered in `composer.json` `autoload.files`) defining `pdf_ar(?string): string`. It shapes **any string that actually contains Arabic** (U+0600–06FF) via `(new ArPHP\I18N\Arabic('Glyphs'))->utf8Glyphs($text)` (joins letters into presentation forms and reverses order for RTL PDF rendering), regardless of the current app locale; strings with no Arabic characters pass through unchanged (English labels/numbers/dates unaffected). This matters because **Arabic can live in the database** (item/section/root-cause/branch names, notes, CAPA actions) and must render correctly even when the app UI is English. A single `ArPHP\I18N\Arabic('Glyphs')` is memoized in a static.
- **View:** `resources/views/visitors/reports/print.blade.php` — added `@font-face` for `Amiri` (regular+bold) pointing at the `storage/fonts` TTFs (forward-slash-normalised path via `str_replace('\\','/',...)` so dompdf resolves it within chroot); body `font-family: Amiri, 'DejaVu Sans'`; bumped font-size to 12px. Every text output is now wrapped in the shorthand `$sh = fn($s) => pdf_ar($s)` defined in the top `@php` block (labels from `__('...')`, branch/inspector/type names, section/item titles, root causes, notes, CAPA actions, footer company + labels, status badges, and severity labels). Numbers/dates/percentages are left as-is.
- **Config:** published `config/dompdf.php` (`php artisan vendor:publish --provider=Barryvdh\DomPDF\ServiceProvider`); `font_dir` = `storage_path('fonts')`.
- **Test:** `VisitorReportsTest` — kept `test_pdf_generation_works` and added `test_arabic_pdf_embeds_amiri_font` (loops over **both** `ar` and `en` locales, sets an Arabic branch name into the DB so the English-locale PDF must still shape Arabic data, and asserts the raw response contains `Amiri`). Manual end-to-end dump confirmed DejaVu count 0 / Amiri embedded, and the shaped glyphs all exist in the font. Full suite now **93 passed / 502 assertions**.

Relevant files: `app/helpers.php`, `config/dompdf.php`, `composer.json`/`composer.lock`, `storage/fonts/Amiri-{Regular,Bold}.ttf`, `resources/views/visitors/reports/print.blade.php`, `tests/Feature/VisitorReportsTest.php`.

### No default choice + live score hidden on inspection page — 2026-09-02

Final behavior on user request (this supersedes the short-lived "default Compliant" change recorded below as history): **(1) No default choice.** `VisitService::start()` again creates every `visitors_visit_items` row with `status = 'pending'`, `visited_at = null` — nothing is chosen or highlighted until the inspector actively picks Compliant / Non-compliant / Not Applicable per item (all three status buttons render inactive on load since none match `data-status="pending"`). **(2) Live score hidden on inspection page only.** `resources/views/visitors/show.blade.php` no longer renders the live-score header card (`visitors.live_score`, `data-live-score-value` / `data-live-final`) or the completed-visit score-class card; the stale `$scoreColor`/`$colorClasses`/`$scoreHex` PHP block and the `applyScore()` JS function (and its `applyScore(data.score)` call in `saveItem`) were removed. The **progress** card (reviewed `N/N` + bar) remains. The score is still fully displayed on the completed-visit **reports** (`visitors/reports/show.blade.php`, `reports/index.blade.php`, `reports/dashboard.blade.php`, `reports/print.blade.php`) — nothing there changed. `VisitController::show` still computes/passes `$score` and `VisitItemController::update` still returns it in JSON, but the inspection page ignores it (backend-only, no exposure to the visit creator). **Tests:** `VisitorsTest::test_create_stores_authenticated_inspector_and_items_default_to_compliant` reverted to `test_create_stores_authenticated_inspector_and_items_start_unreviewed` (asserts all `pending`, 0 `visited_at`); `test_progress_reflects_default_compliant_items` reverted to `test_progress_based_on_visited_at` (asserts `1 / N`). No test asserted the live score. Verification: `view:clear` + full suite **92 passed / 489 assertions**.

> **History (earlier same day, now reverted):** "New-visit items default to Compliant (status ok) — 2026-09-02." A brief change set `status='ok'`/`visited_at=now()` in `VisitService::start()` so a fresh visit was fully reviewed (progress `N/N`, score 100%) with the Compliant button highlighted; `VisitorsTest` + progress tests were updated accordingly (full suite was 92/490). Reverted the same day per user's clarification that they want no default choice and don't want the visit creator to see a branch score.

### Main kitchen removed from visitors inspection page — 2026-09-02

Removed the **Main Kitchen** checkbox from the visitors inspection page (`resources/views/visitors/show.blade.php`), keeping the **Support Department** select. The two were previously in a `sm:grid-cols-2` grid (line 126-145); now only the Support Department field renders full-width under the same `@if` guard. The JS `collectNcPayload` (line ~327) no longer reads `.mk-input` or sends `main_kitchen` in the autosave payload — it now sends only `root_cause_id`, `note`, `support_department`. **Scope:** UI only. The DB column `visitors_visit_items.main_kitchen` (default false), the `VisitorVisitItem` model fillable/cast, `UpdateVisitItemRequest` (`main_kitchen` still `sometimes`/optional), and `VisitService::saveItem` (defaults false when absent) are intentionally left intact so no risky migration is needed and old snapshot data still loads. Verification: `view:clear` + `VisitorsTest` **16 passed / 82 assertions** (+ full suite 92 passed / 489 assertions previously).

### Admin can edit Customer Support / Viewer roles — 2026-09-02

Reported: an `Admin` (non-Super Admin) user editing a role that is not Super Admin got **403 Forbidden** while Super Admin worked. Cause: `RoleController::update()` line 56 blocked **any** of `Super Admin | Customer Support | Viewer` from being updated unless the actor has the Super Admin role — so an Admin could not change Customer Support or Viewer permissions (role 3 scenario). Fix: the guard now only restricts **Super Admin** (`if ($role->name === 'Super Admin') abort_unless(auth()->user()->hasRole('Super Admin'), 403);`). `edit()` was already only Super-Admin-restricted (line 35), so Admin can now view AND update Customer Support / Viewer roles (Admin role has `role.update` since it is `$all` minus only `role.delete`,`user.delete`,`audit.view`). Super Admin itself remains Super-Admin-only to edit. Verification: `view:clear` + full suite **92 passed / 489 assertions**.

### Visitors reports dashboard access hardening + arrow fix — 2026-09-02

Two fixes on the visitors reporting area. **(1) Strict visitors-dashboard permission.** The `dashboard.visitors` gate (`app/Providers/AppServiceProvider.php:34`) previously returned `dashboard.visitors.view || report.view || visit.manage`, so a user with only `report.view` (e.g. `Viewer`) or `visit.manage` could open `GET /visitors/reports/dashboard` even without the dedicated permission. It is now **strict**: `return $user->can('dashboard.visitors.view')`, matching the strict `dashboard.view` gate on the complaint dashboard. Defense-in-depth: `routes/web.php:22` adds `->middleware('permission:dashboard.visitors.view')` on `reports.dashboard` so the gate AND the Spatie middleware both guard the route (a fully-perm-less user gets 403). `VisitorReportController@abortUnlessVisitorsDashboardAccess()` (unchanged) still calls the gate. `Customer Support` is NOT granted `dashboard.visitors.view` (it only has `dashboard.view`), so inspectors cannot reach the analytics dashboard — only `Admin`/`Super Admin` (via `$all`) and `Viewer` (explicit) can. 403 for a user with no permission is now enforced end-to-end. **(2) Unicode arrow fix.** `resources/views/visitors/reports/dashboard.blade.php:18` contained a UTF-8-corrupted `â†` byte sequence where the back-link showed garbage; replaced with the proper UTF-8 arrow `←` (bytes `E2 86 90`) matching all other `back-link` views. `Viewer` still passes the separate `visitors.reports` (reports list) gate via `report.view` — that list feature is intentionally read-only-visible; only the analytics dashboard became strict. **Follow-up:** the "Report Dashboard" button on the reports list page (`resources/views/visitors/reports/index.blade.php:20`) is now wrapped in `@can('dashboard.visitors.view')` so users without that permission no longer see the button linking to the analytics dashboard. Verification: `permission:cache-reset` + `view:clear` + full suite **92 passed / 489 assertions**.

### Dashboard permissions + default home page — 2026-09-02

Added permissions for the two dashboards and a per-user default landing page so visitors-only users are no longer sent to an unauthorized complaint dashboard on login. **Permissions:** added `dashboard.view` (Complaint Dashboard at `/`) and `dashboard.visitors.view` (Visitors Dashboard at `/visitors/reports/dashboard`) to `PermissionSeeder` and `lang/en|ar/permissions.php` (`group_dashboard` / لوحات التحكم) — sorted `dashboard` first in the roles form. Existing roles updated: `Admin` gets both via `$all`, `Viewer` gets both for read-only access, `Customer Support` (inspectors) gets `dashboard.view`. **User default home:** new nullable `users.default_home` column (`2026_09_02_000001_add_default_home_to_users_table.php`), `User` fillable, `UserController@store/update` validate `in:dashboard,visitors.dashboard,complaints,visitors` and persist it, `users/form.blade.php` shows a Default Home Page selector (Complaint Dashboard `/` / Visitors Dashboard `/visitors/reports/dashboard` / Complaints List `/complaints` / Quality Visits `/visitors`, help `default_home_help`). **Login routing:** `AuthController@login`/`showLogin` now honors `default_home` if the user has that permission (`complaints`→`complaint.view`→`/complaints`, `visitors`→`visit.view`→`/visitors`, plus the two dashboards), otherwise falls back to the first permitted dashboard (`dashboard.view` → `/`, `dashboard.visitors.view` → `/visitors/reports/dashboard`, then `complaint.view` → `/complaints`, `visit.view` → `/visitors`), so a visitors-only account (e.g. a custom role with only `visit.*` + `dashboard.visitors.view`) lands on the visitors dashboard instead of 403; an already-logged-in user hitting `GET /login` is now redirected via `redirectToHome()` (was previously able to view the login page while authenticated). **Access control:** `DashboardController` now `abort_unless(can('dashboard.view'))` and `DashboardFilterRequest:authorize()` now checks `dashboard.view` (was `complaint.view`, which made a role with only `dashboard.view` still 403 — this is the fix for the reported "has dashboard permission but unauthorized" bug); `VisitorReportController@dashboard` now checks a new `dashboard.visitors` gate (`dashboard.visitors.view || report.view || visit.manage`) via `abortUnlessVisitorsDashboardAccess()`, while `visitors.reports` stays `report.view || visit.manage`. **Navbar:** `layouts/app.blade.php:39`/`85` now wraps the Dashboard nav links (desktop + mobile drawer) in `@can('dashboard.view')` so users without that permission no longer see the Complaint Dashboard link — visitors-only users see only the destinations they can access. Lang `common.default_home`/`default_home_help`/`complaint_dashboard`/`visitors_dashboard` added. Verification: `migrate` + `db:seed --class=PermissionSeeder` + `permission:cache-reset` + `view:clear` + full suite **92 passed / 489 assertions** (follow-up 2026-09-02: expanded default_home to include `/complaints`+`/visitors` and blocked logged-in `/login`; navbar hide for no-permission; fixed DashboardFilterRequest authorize).



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

### Due-date / period_hours feature — 2026-09-04

The free-text `deadline`/date-only `due_date` model was replaced by a numeric `period_hours` model with a backend-computed, stored-only `due_at`. `app/Services/Visitors/DueDateService.php` is the **single source of truth** for all due logic.

- **Schema (`database/migrations/2026_09_04_190000_replace_deadline_with_period_hours.php`):** `visitors_checklist_items.deadline` (string) and `visitors_visit_items.deadline` (string, snapshotted at visit start) were replaced by `period_hours` (decimal 8,2, nullable; `0` = "Immediate", no due date). `visitors_capa_actions` gained `period_hours` (decimal 8,2) and `due_at` (timestamp, nullable, indexed). `due_at` = action `created_at` + `period_hours`, computed backend-side **once at creation** and stored forever (never recomputed); it is NULL when `period_hours` is 0/null. The legacy `due_date` (date) column still exists but is unused (not in fillable). The migration backfills `period_hours` from the legacy text `deadline` (via `DueDateService::periodToHours`) and seeds `due_at` from legacy `due_date` (startOfDay) or `created_at + period_hours`, then DROPs the `deadline` columns.
- **Status model:** stored `visitors_capa_actions.status` holds only `open | in_progress | closed | rejected`; `overdue`, `completed`, `closed_late`, `immediate`, `upcoming`, `due_soon` are all **virtual/derived** via `DueDateService::dueStatus()`. Overdue now derives from `due_at`, not `due_date < today()`.
- **New files:** `config/visitors.php` (`due_soon_hours`, default 24, env `VISITORS_DUE_SOON_HOURS`); `app/Services/Visitors/DueDateService.php` — `periodToHours()` (numeric pass-through; legacy text `فوري`, '2 days', '1 week', '1 month', … maps to hours; unknown text throws `InvalidArgumentException`), `dueDate()`, `hoursLabel()` (locale-aware Immediate/30 minutes/1 hour/1 day/2 days/1 week/1 month/`:hours h`), `dueStatus()`. `VisitorCapaAction::effectiveStatus()`, `periodLabel()`, and `dueStatus()` delegate to it.
- **Services/views:** `CapaService::createFromNonCompliant()` now creates inside a `DB::transaction`, copies `period_hours` from the visit-item snapshot, and computes `due_at` from the fresh `created_at`. `VisitService::start()` snapshots `period_hours` instead of `deadline`. `VisitorReportService::listReports()` attaches per-visit `critical_violations` (int) and `capa_due` (open/due_soon/overdue/immediate/closed/closed_late/completed) and accepts a `due_status` filter (open|overdue|due_soon|immediate|closed|closed_late). `VisitorReportDashboardService::build()` returns `dueCards`, `dueStatusChart`, and `branchDueAnalysis`; CAPA counts/analytics split overdue via `due_at`. `visitors/reports/show.blade.php` gained Score Summary cards (Total/Critical Violations, Open/Due Soon/Overdue/Immediate/Closed/Closed Late) plus Period and Due Status columns in the Violation Details and CAPA tables; `index.blade.php` gained Critical/Open/Due Soon/Overdue/Immediate/Closed columns and a Due Status filter; `dashboard.blade.php` gained a "Corrective Actions / Due Dates" section with dueCards, a due-status doughnut chart (`#report-dueStatus-chart`, wired via `addChart('dueStatus','doughnut',...)` in `app.js`), and a branch due analysis table; `print.blade.php` mirrors Period/Due Date/Due Status columns.
- **Master data:** Excel import/export now uses a `period_hours` header (numeric hours, decimals allowed, `0` = Immediate; free text like '24 hours' is REJECTED on new imports; the legacy `period` key is still accepted as a fallback during normalize). Template example values are numeric (24, 0, 0). `HasMasterDataColumns`, `VisitorCurrentMasterDataExport`, `VisitorMasterTemplateExport`, `ChecklistImportService`, and `VisitorMasterDataService` were updated; `VisitorChecklistSeeder` now seeds `period_hours => 48` (was `deadline` '2 days').
- **Localization:** added `period`, period labels (`period_none`/`immediate`/`30min`/`1hour`/…/`hours_fallback`), `due_on`, `due_status`, action/due status labels (`st_open`/`st_in_progress`/`overdue`/`due_soon`/`upcoming`/`immediate`/`completed`/`closed_late`/`closed`/`rejected`), `remaining`, `days_late`, `filter_due_status`, `filter_action_status`, and `critical_overdue`/`completion_rate` keys to `lang/en|ar/visitors.php`. The stale `visitors_visit_items.status` migration doc comment was fixed to `// ok | nc | na (VisitService::start explicitly sets 'pending' = Unreviewed by default)`.
- **Verification:** added `tests/Unit/Visitors/DueDateServiceTest.php` (14 tests) plus `VisitorReportsTest` additions (CAPA action snapshots `period_hours` and computes `due_at`; reports list carries `capa_due` aggregates; reports list filters by `due_status`). Full suite now **110 tests passed / 562 assertions** (previously 93).

### Reports index table layout + Q&A additions — 2026-09-04

- **Reports index table (`resources/views/visitors/reports/index.blade.php:90-149`) re-laid out** per user request (was cramped; the "View Report" / "Print / PDF" buttons wrapped to separate lines in Arabic and the 14-column table looked poor). Now: all header/body cells are **centered** (symmetric in both LTR and RTL), padding tightened to `px-2 py-2`, headers/dates/IDs/counts are `whitespace-nowrap` (labels no longer stack), numeric columns use `tabular-nums`, the Actions cell is `flex flex-nowrap` with compact `whitespace-nowrap` buttons so View Report + Print / PDF always stay on one line, and the table has `min-w-[1180px]` (full table on desktop; clean horizontal scroll on small screens via the existing `overflow-x-auto` wrapper). Rebuilt assets (`npm.cmd run build`) → new CSS bundle `public/build/assets/app-D7be5gPN.css`; `php artisan view:clear`. Verified: `VisitorReportsTest` **19 passed / 172 assertions** (full suite still 110/562).
- **`questions_and_answers.md` gained two entries.** Q6 documents the evidence-photo compression pipeline (`VisitorPhotoService::store()`: 20 MB cap → GD decode (`imagecreatefromstring`/`imagecreatefromjpeg`) → downscale to ≤1600 px longest edge via `imagecopyresampled` → re-encode JPEG quality 80 into an output buffer (`ob_start`/`imagejpeg($image,null,80)`) → stored on the private `local` disk as `visitor-photos/Y/m/d/{uuid}.jpeg`, mime forced `image/jpeg`, with `compressed_size` returned; example code included). Q7 explains why a Super Admin can open any inspector's in-progress visit from a copied `visitors/{visit}` link while everyone else gets **403 "This action is unauthorized"**: `VisitorVisitPolicy::view` is owner-only, but `Gate::before` in `AppServiceProvider.php:25` short-circuits every ability check to `true` for the `Super Admin` role (returning `null` for everyone else so normal policy evaluation proceeds); the bypass also covers `update`/`submit`. Both entries reference exact files/lines. Docs-only change plus the layout fix above; no schema, route, or test change.

### Session cookie isolation from the sibling POS app — 2026-09-04

`C:\xampp\htdocs\complaint\.env` now sets `SESSION_COOKIE=complaint_session` (added under the SESSION_* block; `SESSION_PATH=/` and `SESSION_DOMAIN=null` unchanged). Reason: `http://localhost/complaint/public/` and the sibling `http://localhost/POS/public/` are separate Laravel installs on the **same host**, and both used the default cookie name `laravel_session` at path `/` — a browser keeps only one cookie per (name, domain, path), so logging into either app overwrote the other's session cookie; since each app has its own `APP_KEY` and session store, the foreign cookie couldn't be decrypted/recognized and each app looked logged out. The fix is per-app unique cookie names (`complaint_session` here; the POS app should set `pos_session` in its own `.env`, not part of this repo). `config/session.php:130` already reads `env('SESSION_COOKIE', 'laravel_session')` so no code change was needed; `php artisan config:clear` was run. Documented as **Q&A #8** in `questions_and_answers.md`. Note: the shared `XSRF-TOKEN` cookie also collides across the two apps but is harmless here because this app reads CSRF from the `<meta>` tag/`@csrf` inputs, never from that cookie.

### Optional Serial Number + Price on complaints — 2026-09-06

Added two **optional** fields to the complaint create/edit form (`resources/views/complaints/form.blade.php`, placed between Description and Resolution in a `sm:grid-cols-2` grid) — Serial Number (رقم السيريال) and Price (السعر), with no `required` and no asterisk. Schema: migration `2026_09_06_000001_add_serial_number_and_price_to_complaints_table.php` adds nullable `serial_number` (string 255) and nullable `price` (decimal 12,2) to `complaints`; **applied to the live SQL Server DB via `php artisan migrate --force`**. Backend: `Complaint` model — fillable + `price` cast `decimal:2`; `StoreComplaintRequest` and `UpdateComplaintRequest` — `serial_number` nullable string max 255, `price` nullable numeric min 0 max 9999999999.99 (both optional; the field-only rule set — the create form's other dropdowns remain not-required server-side). Display: `complaints/show.blade.php` gains Serial Number + Price rows in the Complaint Information card (`—` when empty, price formatted via `number_format(...,2)`). Lang keys `serial_number`/`price` added to `lang/en|ar/common.php` after `complaint_date`. **Excel export** `app/Exports/ComplaintsExport.php` now ships both columns right after Complaint Date (`Serial Number` / `رقم السيريال`, `Price` / `السعر`; price mapped as a numeric float, serial as string), with `tests/Feature/ComplaintExportTest.php` updated for the new column positions/headings/count (19 headings; serial + price row values asserted). Still NOT exposed (not requested): complaint list/index columns and filters. Verification: `php artisan migrate --force` (SQL Server) + focused `ComplaintExportTest` **4 passed / 21 assertions** + full suite ran at **110 passed / 562 assertions**. Docs: `database_design.md` §3.4 complaints table updated; `questions_and_answers.md` untouched.

### Navbar label "Complaints" renamed to "Services" — 2026-09-06

The desktop navbar dropdown top-level label (`resources/views/layouts/app.blade.php:42`, the `<summary>` that opens Customer Complaints / Quality Visits) now uses `__('common.services')` (Services / الخدمات) instead of `__('common.complaints')` — the existing master-data key already had the exact English/Arabic wording, so no new lang key was added. The mobile drawer is unchanged (it shows the direct sub-links "Customer Complaints" / "Quality Visits", never the top-level label). No routes, permissions, or other `common.complaints` usages (back-links/page titles) were touched. Verified via `php artisan view:clear`.

### Installed-desktop PWA draggable title strip — 2026-09-06

In the desktop installed app (`display: standalone` with `display_override: window-controls-overlay` already in `public/manifest.json`), Chrome hands the title-bar area to the page — so the top of the window was a blank strip with nothing branded or grabbable. Fix: `resources/views/layouts/app.blade.php` now renders a `#wco-titlebar` strip (app icon + localized `common.application_name`) that is `display:none` everywhere except inside `@media (display-mode: window-controls-overlay)`. The strip is positioned with the `env(titlebar-area-x/width/height)` variables (stays clear of the OS min/max/close buttons in both LTR and RTL), the whole strip is the window drag handle (`-webkit-app-region: drag` + `app-region: drag`, `pointer-events:none` children, no interactive elements inside), `body` gets `padding-top: env(titlebar-area-height)` in overlay mode so the sticky header sits below it, and dark mode is honored (`html[data-theme='dark'] #wco-titlebar` → `#0f172a`/`#e2e8f0`, matching the theme-color switch in `app.js`). `PwaTest` asserts the markup and the new CSS rules. Verified: `npm.cmd run build` (rules confirmed present in the new bundle `public/build/assets/app-yLk-OzYN.css`) + `php artisan view:clear` + full suite **110 passed / 572 assertions**.

### Install App button hidden once the app is installed — 2026-09-06

`resources/js/app.js` install-flash UI: the Install button previously only hid inside the installed window (`isStandalone` early return) or after the `appinstalled` event — so in a **browser tab while the PWA was already installed** it could still show (on Android the mobile button is revealed unconditionally because `beforeinstallprompt` never fires over plain HTTP). Restructured the install IIFE into `initInstallUi()` (beforeinstallprompt reveal, click handlers, modal, `appinstalled` → `hideInstallButtons`) that is only invoked when the app is **not** already installed: `navigator.getInstalledRelatedApps()` (Chrome/Edge, secure contexts like `localhost`/HTTPS) resolves `apps.length === 0` → init; any browser without the API or an insecure context (LAN HTTP) falls back to `initInstallUi()` so behavior is unchanged there. Now the navbar Install button stays hidden in the installed app window and whenever the app is already installed. Verified: `npm.cmd run build` (bundle `public/build/assets/app-Cyo8jwnf.js` contains the API check) + `PwaTest` **5 passed / 52 assertions**.

**Follow-up (2026-09-06, user report):** the desktop button *still* did not disappear after installing and opening the app at `http://localhost/complaint/public/`. **Root cause:** `resources/views/layouts/app.blade.php` desktop `data-install-button` had `class="hidden … sm:inline-flex sm:text-sm"` — at ≥640px Tailwind's `sm:inline-flex` **overrides** `hidden` in the cascade, so the button was unconditionally visible on desktop in the browser tab AND inside the installed app window (JS class toggling / `isStandalone` could never hide it; JS never set inline `display`). Fix (all verified, full suite **110 passed / 578 assertions**, `PwaTest` 5/58):
- Removed `sm:inline-flex` from the desktop button so it starts `hidden` at every width; the JS `reveal()` adds `flex` when an install is actually offered.
- `resources/css/app.css` now hard-hides the buttons via `@media (display-mode: standalone), (display-mode: window-controls-overlay), (display-mode: minimal-ui) { [data-install-button], [data-install-button-mobile] { display:none !important; } }` — a CSS guarantee that even a stale cached bundle cannot show the buttons inside the installed window.
- `resources/js/app.js` install IIFE hardened: `isStandalone` now also matches `minimal-ui`; a shared `localStorage` marker `complaint-installed` (set inside the installed window and on `appinstalled`, cleared when `beforeinstallprompt` fires) hides the buttons immediately in the browser tab too — including contexts where `getInstalledRelatedApps` isn't available (LAN plain HTTP). `getInstalledRelatedApps` remains the authoritative secure-context check; `triggerInstall`/`hideInstallButton` now use inline `display:none` so responsive utility classes can't override them.
- `PwaTest` updated accordingly. Rebuilt with `npm.cmd run build` (new bundle `public/build/assets/app-BM46HXx3.js` + `public/build/assets/app-BCoyBUv3.css`) + `php artisan view:clear`.

**User note:** because the service worker caches the hashed Vite assets, test on a fresh hard refresh (Ctrl+F5) and, if a previously installed app window still shows the button, uninstall + reinstall the PWA so the new bundle is loaded.

### Install App button removed entirely — 2026-09-06

The user chose to drop the custom Install App button from the navbar everywhere instead of perfecting its hiding. All of it was deleted: the desktop header `data-install-button` and the mobile-drawer `data-install-button-mobile` in `resources/views/layouts/app.blade.php` (plus the whole `data-install-modal` block), the entire install IIFE in `resources/js/app.js` (`isStandalone`, `beforeinstallprompt`, `appinstalled`, `getInstalledRelatedApps`, the `complaint-installed` localStorage marker), the install dark-mode CSS in `resources/css/app.css`, and the install lang keys (`install_app`, `add_to_home_screen`, `install_instructions_title`, `install_instructions_android`, `install_instructions_ios`, `install_now`, `install_close`) from `lang/en|ar/common.php` (**221 → 214 keys** per locale; stripped safely with a temp PHP script — UTF-8, no BOM — and both files `php -l` clean). PWA installability itself is untouched: the manifest, service worker, and the `#wco-titlebar` draggable strip remain, and users install via the browser's own install menu. `PwaTest` now asserts the layout and CSS contain **no** `data-install-button`/`data-install-modal`. Verified: `npm.cmd run build` (bundle `public/build/assets/app-BIVoMiA1.js` + `public/build/assets/app-Bip2pWax.css`) + `php artisan view:clear` + full suite **110 passed / 577 assertions** (PwaTest 5/58). `project_structure.md` and `complete_project_specification.md` bullets updated.

### Complaint Type → Category/Priority derivation — 2026-09-06

Category and Priority are no longer manually chosen on the complaint create/edit form; every Complaint Type now defines its `category_id` + `priority_id`, and the system derives the complaint's category/priority from the selected type (backend-authoritative, client cannot override).

- **Schema:** migration `2026_09_06_000002_add_category_priority_to_complaint_types_table.php` adds nullable `category_id` → `complaint_categories` and `priority_id` → `priorities` to `complaint_types` (both `nullOnDelete`). Nullable in DB; required is enforced by the master-data type form and complaint validation. Applied to the live SQL Server DB via `php artisan migrate --force`. `complaint_categories` / `priorities` tables use table names `complaint_categories` (no `s`) and `priorities`.
- **Model:** `App\Models\ComplaintType` — `$fillable` now includes `category_id`, `priority_id`; added `category()` and `priority()` belongsTo.
- **Master data:** `MasterDataController::$types['types']['fields']` now `['name','color','category_id','priority_id','is_active','sort_order']`; `rules()` gates `category_id`/`priority_id` as required+exists per type; `create()`/`edit()` pass `$categories`/`$priorities` via static `relationOptions('types')`; `index()` eager-loads `with(['category','priority'])` for types only. `master-data/form.blade.php` renders two required selects for types; `master-data/index.blade.php` shows optional Category/Priority columns for types (colspan `5 + ($type==='types' ? 2 : 0)`).
- **Complaint flow:** `complaints/form.blade.php` grid loop is now `branch_id, service_id, source_id, status_id` + type select + date (category/priority selects removed). Type options carry `data-category-id/name/color` + `data-priority-id/name/color` (from `$data['types']` eager-loaded in `ComplaintController::masterData()`); a `#type-derived` panel shows read-only Category/Priority badges (`.badge` with `--badge-color`), an inline script updates them on type change; panel hidden when the selected type lacks config.
- **Backend:** `StoreComplaintRequest`/`UpdateComplaintRequest` no longer accept `category_id`/`priority_id`; `type_id` keeps `Rule::exists(...,'is_active',true)`; both add a lazy `after()` validator error on `type_id` (`common.type_missing_category_priority`) when the type lacks category/priority; both expose `type()` (memoized, eager-loads `category`/`priority`). `ComplaintController::store()` sets `category_id`/`priority_id` from `$request->type()`; `update()` re-derives them **only when `type_id` changed**, computing `$old` after the mapping so the activity log stays clean and unrelated data is never overwritten.
- **Seed:** `MasterDataSeeder` association block after priorities maps the six seeded types → category/priority (Wrong Order→Order/High, Missing Item→Order/Medium, Late Delivery→Delivery/Medium, Bad Treatment→Customer Service/Medium, Wrong Price→Payment/High, Food Quality→Food Quality/Medium). Re-run on live DB via `php artisan db:seed --class=MasterDataSeeder --force`; verified all 6 live types configured (tinker check).
- **Lang:** `common.auto_determined` + `common.type_missing_category_priority` added to `lang/en|ar/common.php` (per-locale now 223/219 «=>'» entries; `created` key duplicated once in both locales — pre-existing, untouched; temp PHP script, UTF-8 no-BOM, both `php -l` clean).
- **Tests:** `ComplaintManagementTest` — `complaintData()` no longer posts category/priority; status-change and update fixtures derive category/priority from the type for direct `Complaint::create` (columns are NOT NULL) and post `array_merge` payloads (the `+` union helper does NOT override existing keys); NEW: store derives, store ignores manipulated category/priority (picker chooses ids ≠ the type's config), store rejects unconfigured active type, update re-derives on type change, update preserves on unchanged type, create+edit forms hide category/priority selects and render badges, master-data type store requires+persists both (Admin user).
- **Verification:** `php artisan migrate --force` (SQL Server Ran), `php artisan view:clear`, `php -l` on all changed PHP files, full suite **117 passed / 608 assertions** (13 complaint-management tests / 56 assertions). Docs: `database_design.md` (new §3.3.1 Derived category and priority + complaint_types columns + §3.4 notes), `project_structure.md`, `complete_project_specification.md` synced.
- **Dark-mode badge fix (same day, user report):** the derived Category/Priority badges were unreadable in dark mode because `.badge` renders text in the raw master-data color (e.g. dark slate `#475569`) on the dark surface. Added `html[data-theme='dark'] .badge` override in `resources/css/app.css` using `color-mix()` — text = badge color 62% + `#f8fafc`, border 55%, background 24% — so badges keep their hue but stay legible on dark; also fixes the same badges on the complaint show page. Rebuilt (`public/build/assets/app-RLfl2y8e.css`, rule confirmed in bundle) + `view:clear` + full suite **117 passed / 608 assertions**. Note: the service worker caches hashed assets, so hard-refresh/reinstall to see the new styling.

### Reports dashboard SQL Server `GROUP BY bucket` fix — 2026-09-06

`GET /visitors/reports/dashboard` 500'd on live SQL Server with `Invalid column name 'bucket'` (42S22) at `VisitorReportDashboardService.php:191`. Root cause: `dueStatusDistribution()` grouped by the SELECT alias `bucket` — allowed by SQLite (which is why the test suite never caught it) but rejected by SQL Server. First attempt (repeat the CASE in `groupByRaw`) still failed because SQL Server treats the SELECT's `@p1,@p2` and GROUP BY's `@p3,@p4` bound parameters as different expressions (`Column ... is invalid in the select list`). Final fix: compute `bucket` in a subquery (`DB::query()->fromSub($inner, 'due_buckets')`) and `groupBy('bucket')` on the derived column outside — portable across SQLite/MySQL/SQL Server. Verified live via tinker `VisitorReportDashboardService::build([])` → real buckets (`immediate:4, overdue:3, due_soon:0, upcoming:0`). Added regression test `test_reports_dashboard_buckets_open_capa_by_due_status` (upcoming bucket → backdate to overdue → dashboard page 200 as Admin). Full suite **118 passed / 617 assertions**. No schema/asset change; `memory.md` only doc touched.

### Quality Visits Violation lifecycle + Follow-up (inspection vs reports) — 2026-09-06

Reworked the Corrective Actions feature so the **Inspection Report (per visit) stays immutable** while corrective actions live a real lifecycle with review, and the same open finding is **not duplicated** on the next inspection.

- **Problem fixed:** on the next visit, re-marking the same item NC created a brand-new CAPA action that deviated from the closed one's frozen snapshot (report rooms changed...). And there was no approve/reject step — only an immediate close.
- **Schema (`2026_09_06_000003_extend_visitors_violation_follow_up.php`, applied live via `php artisan migrate --force`):** `visitors_visit_items` += `follow_up_action` (nullable string) + `linked_capa_action_id` (nullable FK → `visitors_capa_actions` on action `visit_item_id`, `nullOnDelete`, SQL Server OK). `visitors_visit_photos` += `capa_action_id` (nullable FK, `nullOnDelete`) + `evidence_role` (nullable string: `initial | resolution`). `visitors_capa_actions` += `submitted_review_at` (nullable timestamp) + `reviewed_by` (nullable FK → users, `nullOnDelete`) + `reviewed_at` (nullable timestamp) + `review_comment` (nullable string) + `reject_reason` (nullable string). All nullable → git-applied to the live DB immediately; **no UI destruction** anywhere.
- **Lifecycle:** stored status now `open | in_progress | pending_review | closed | rejected`; a **rejected** action becomes `in_progress` again (back to the inspector). Virtual due statuses unchanged. Reports stay identical because they read only snapshots + `visitors_capa_actions`; a follow-up resolution photo is tagged `evidence_role='resolution'` and links to the action, so originating-visit photos remain untouched.
- **Follow-up on the inspection page (`visitors/show.blade.php`):** when a completed, same-branch visit has an open action for the same `checklist_item_id`, the item renders an amber **Existing Open Violation** panel (`data-existing-violation-id` + `data-existing-violation-status` on the `<article>`, hidden on the completed report view) with three buttons: **Still Open**, **Resolved**, **New Violation**. They auto-save into `follow_up_action` (+ `linked_capa_action_id`) via the existing autosave. `VisitService::submit()` (in `processViolationsOnSubmit`) routes each NC item by `follow_up_action`: `still_open` → grows a `follow_up`-typed `visitors_capa_updates` row on the linked action; `resolved` → `CapaService::submitResolution` (evidence-gated); **anything else → create a NEW action** (new violations and unchanged-open re-submits open a fresh one). `saveItem()` persists `follow_up_action`/`linked_capa_action_id` and nulls the link on `new_violation`/non-nc. The panel is computed in `VisitController::show()` via `CapaService::existingOpenMap()` (same `branch_id` + same `checklist_item_id`, action status `open|in_progress|pending_review` on a completed visit).
- **Approval/review UI:** `VisitorViolationController` (replaces the deleted dead `VisitorCapaController`) with `index` (`GET /visitors/violations`, action-status filter, lists all actions across branches) and `show` (timeline card = initial evidence + resolution evidence `capa_action_id` photos + `visitors_capa_updates` log). `approve` (`POST visitors/violations/{capaAction}/approve`, `status → closed`, `completed_at = reviewed_at`), `reject` (`status → in_progress`, requires `reject_reason`, `from_collector=true`), `resolve` (`submitted_review_at = now()`, `status → pending_review`, photos attached with `evidence_role='resolution'`). **Rules:** approve/reject are gated by `@can('visit.review')`; `resolve` by the owner/inspector (or `visit.manage`). `CapaService::approve()`/`reject()` throw `capa_not_pending_review` (`RuntimeException`, error-flash) unless the action is already `pending_review`; `submitResolution()` throws `violation_cannot_resolve` unless status is `open|in_progress`, and `resolution_evidence_required` when the violation is Critical and it has no `evidence_role='resolution'` photo. The old per-item Close/Update buttons on `reports/show.blade.php` are removed — the report is immutable now; the CAPA tab shows each action's live status instead. `users` FK on `reviewed_by` is `nullOnDelete`, so user deletion never falls over.
- **Permissions:** added `visit.review` via `PermissionSeeder`; new **Quality Manager** role (syncPermissions of the 15 `visit.*` permissions) granted to no user by default (assign manually). Approval/rejection routes are protected by `permission:visit.review`.
- **Localization:** added ~40 keys to `lang/en|ar/visitors.php` (`st_pending_review`, `existing_open_violation(_help)`, `follow_up_still_open/resolved/new_violation`, `resolution*`, `submit_resolution`, `violation_submitted_for_review`, `violation_approved`, `violation_rejected`, `approve_violation`, `reject_violation`, `approve_comment_placeholder` (duplicate removed), `reject_reason(_placeholder)`, `rejected_from_report`, `review_actions`, `follow_up_timeline`, `no_follow_ups`, `no_evidence`, `pending_review_capa`, …) + `common.view`/`common.actions` (were missing → rendering `common.actions`). All `php -l` clean; Arabic via temp PHP script (no BOM, no PowerShell text munging — `Set-Content -Encoding UTF8` corrupts array files).
- **Dashboard (`reports/dashboard.blade.php`):** cards row now 7 columns (`xl:grid-cols-7`) with a new amber `pending_review_capa` card (`VisitorReportDashboardService::cards()` + status aggregate map extended to include `pending_review`); `reports/show.blade.php` + `print.blade.php` due-status maps include `pending_review` too.
- **Verification:** focused `tests/Feature/VisitorViolationTest.php` (18 tests, 148 assertions — lifecycle, no-duplicate still_open, new violation after close, resolution evidence gates incl. critical-without-photo, reviewer-only approve/reject, timestamps, vote buttons, dashboard card). Full suite now **136 passed / 765 assertions**. `php artisan migrate --force` + `PermissionSeeder` re-run live; live checks: new columns + `visit.review` + Quality Manager role all present. Routes verified via `php artisan route:list --name=violations`. `git diff --check` clean. Docs: `project_structure.md` + `database_design.md` synced; this entry appended after the SQL Server `GROUP BY bucket` fix.

### Corrective Action resolution review workflow — 2026-09-07

Reworked the violation submit/approve/reject flow into an explicit **submit → review (approve/reject)** workflow with **separated permissions**, backend-enforced (not just UI), keeping the full audit trail and preventing self-approval.

- **Permissions (naming follows the existing `{module}.{entity}.{action}` convention):** `visit.resolution.submit`, `visit.resolution.review`, `visit.resolution.approve`, `visit.resolution.reject`. `PermissionSeeder`: Quality Manager now gets submit+review+approve+reject (legacy `visit.review` dropped from the role; the permission row itself still exists for compat, no code references it anymore). Customer Support gets `visit.resolution.submit` (submit only). Admin/Super Admin have all via `$all`. NEW idempotent live seeder **`ResolutionWorkflowPermissionSeeder`** (adds only the 4 perms + assigns roles, then `PermissionRegistrar::forgetCachedPermissions()`; it deliberately does NOT reset passwords like `PermissionSeeder` does) — run on the live DB via `php artisan db:seed --class=ResolutionWorkflowPermissionSeeder --force`.
- **Schema (`2026_09_07_000200_add_resolution_review_workflow.php`, applied live via migrate):** `visitors_capa_actions` += `submitted_by` (nullable FK → users, noAction), `resolution_note` (nullable text), `closed_by` (nullable FK → users, noAction). Closure/review timestamps: approve stores `completed_at` (doubles as closed time), `reviewed_at`, `reviewed_by`, `closed_by`.
- **Service (`CapaService`):** `submitResolution()`/`markResolvedFromFollowUp()` now also set `submitted_by = auth()->id()` + `resolution_note`, and record the resolution evidence `original_name` in the update row; `approve()` throws `visitors.cannot_self_approve` (RuntimeException → error flash) when `submitted_by === auth()->id()` (legacy rows with NULL `submitted_by` skip the check) and stores `closed_by` + an `Approved by reviewer.` update comment (closure note appended); `reject()` records `Rejected: …`. Route gates in `VisitorViolationController`: `resolve`→`visit.resolution.submit`, `approve`→`visit.resolution.approve`, `reject`→`visit.resolution.reject`.
- **Policy (`VisitorVisitPolicy::viewReport`):** additionally grants `visit.resolution.review` holders access to **any completed report** (so the reviewer can open the Corrective Actions report to approve/reject other inspectors' submissions); `report.view`-only and `visit.manage` rules unchanged.
- **UI:** `violations/show.blade.php` single "Review Actions" card: `pending_review` shows the reviewer an amber submitted-resolution panel (submitter name, `submitted_at`, resolution note, `evidence_role='resolution'` photo thumbnails) with Approve & Close (closure-note textarea) gated by `@can('visit.resolution.approve') && !selfApproval`, a `cannot_self_approve` warning when the current user submitted it, and Reject (required reason) gated by `@can('visit.resolution.reject')`; `open|in_progress` shows the resolve form (photo still required only for critical) gated by `@can('visit.resolution.submit')`. Non-reviewers on `pending_review` see `resolution_submitted_no_access`. `violations/index.blade.php` + `reports/show.blade.php` show "Submitted by: <name>" + date/time under `pending_review` status, and the report approve/reject buttons are per-permission + self-approval guarded (reports also gate on `visit.resolution.review` to enter the control group). `VisitorReportService` + `VisitorViolationController::index/show` eager-load `submitter`/`closer`.
- **Critical-photo alongside:** resolution photo remains required only when the violation severity is critical (both resolve and blade), fixed earlier this day.
- **Verification:** focused visitor test files **40 passed / 358 assertions**; full suite **158 passed / 975 assertions**. Live: `php artisan migrate --force` (column check via INFORMATION_SCHEMA), `db:seed --class=ResolutionWorkflowPermissionSeeder`, `view:clear`, `permission:cache-reset`; verified 4 perms exist and role grants are exact (QM all 4, Customer Support submit-only, Admin all 4). `view:cache` compiles, `git diff --check` clean. Docs: `database_design.md` + this entry; `project_structure.md` appended.

### Complaint / Compliance master data bilingual (name_en + name_ar) — 2026-09-08

Made the 7 complaint master-data entities bilingual (`name_en` + `name_ar`) behind a centralized `localized_name` accessor, safe migration of the legacy single `name` column, and locale-aware display everywhere (including the visitors module's branch names). Relationships stay ID-based; user-generated content is not translated. **Decision:** all name-keyed business logic (dashboard solved/pending/high-critical detection, `ComplaintStatusService` 'solved' check, `customers/show` + `dashboard/index` keyBy with 'Pending'/'In Progress'/'Solved'/'Closed', type→category+priority linkage) matches **`name_en`** because legacy production data is English; no new code/key column.

- **Schema (`2026_09_08_000100_make_master_data_bilingual.php`, single reversible migration):** on `branches`, `services`, `complaint_sources`, `complaint_categories`, `complaint_types`, `priorities`, `complaint_statuses` — add nullable `name_en`+`name_ar` → backfill `name_en = name`, `name_ar = name` (old value preserved in both; admins correct Arabic later via the bilingual edit form) → **drop `name`** in the same migration (all code/tests deploy together). `down()` recreates `name` from `COALESCE(name_en, name_ar)`. Eloquent `$this->name` on a model without that attribute returns `null` (safe). Verified on live SQL Server + test SQLite.
- **`app/Models/Concerns/HasLocalizedName.php`:** `localized_name` → ar locale = `name_ar ?: name_en ?: name`, else `name_en ?: name_ar ?: name` (string cast). Used by all 7 models, now fillable `['name_en','name_ar',…]`.
- **Controllers/services/seeders/export:** `MasterDataController` (store/update validation requires both names; `orderBy('name_en')`); `ComplaintController::searchBranches` (searches `name_en` OR `name_ar`, JSON `name` = `localized_name`); `DashboardStatsService` (selects/order/key on `name_en`, localized) + `ComplaintStatusService` (solved check on `name_en`, message `localized_name`); `MasterDataSeeder` bilingual rewrite (updateOrCreate keyed on `name_en`, branches on `code`); `ComplaintsExport` values + timeline use `localized_name`.
- **Visitors module (branch shared):** blades render `$branch->localized_name`; `VisitController`/`VisitorViolationController`/`VisitorReportController` order by `name_en`. **`VisitorReportDashboardService`** — raw `branches.name` was still being selected in `branchDueAnalysis`/`branchComparison`/`criticalViolations`, which would 500 on the live SQL Server after the drop; now selects `name_en`+`name_ar` (`branch_name_en`/`branch_name_ar`) with a `pickLocalized()` helper. Visitor-only master data (VisitType/Section/RootCause/Category) stays single-name (out of scope).
- **Tests:** new **`MasterDataLocalizationTest`** (store/update require both names, `localized_name` locale + fallback, Arabic master-data page, export localization — 5 tests / 18 assertions). Full suite **163 passed / 991 assertions**.
- **Live deploy:** `migrate --force` (only the bilingual migration was pending) → a translation-only script (update `name_ar` for the 30 matched live rows — statuses, services, sources, categories, types, `Low/Minor/Major/Critical` priorities — **without creating/deleting**; the live production data deliberately differs from the demo seeder, which would have injected Branch A/B/C + High/Medium) → `view:clear`. Verified live: `name` gone, `name_en`/`name_ar` present on all 7 tables; Arabic displays correctly under the ar locale; the 16 real branches fall back to their English `name_ar` under ar locale until an admin supplies Arabic via the bilingual edit form; counts unchanged (16/3/6/6/6/4/5).

### All-branches multi-select dropdown pickers — 2026-09-10

Branch multi-select pickers now show **every** branch in a scrollable panel (~7 rows visible), add an **All** select-all / clear-all option, and filter search **client-side** over the full loaded list.

- **Server:** removed `->limit(5)` and the missing-branch concat from `ComplaintController::masterData()` + `searchBranches()`, `DashboardStatsService::filterData()`, `ReportController::branches()`. Because `masterData(false)` also feeds complaint create/edit, those forms now list **all** branches too (native `<select>`, previously capped at 5). `VisitorReportController` branch lists were already unlimited.
- **JS (`resources/js/app.js`, `data-filter-dropdown` path):** `renderBranches(initialBranches)` now runs on init and prepends an **All** row (`renderAllOption()`, class `branch-all`, `data-branch-all`) whose check state is driven by `allSelected()` (every loaded branch id present in `selectedIds`); clicking it either adds every branch id (`selectedIds.add(String(...))`) or clears. Search replaced the debounced server fetch with a synchronous `filterBranches` over the full list. Legacy fallback block: removed `.slice(0, 5)` + `:nth-child(n+6)` hiding (kept its server search).
- **Views:** `data-all-branches-label="{{ __('common.all') }}"` on the three `data-branch-picker` containers and branch options panel raised `max-h-56` → `max-h-64` in `complaints/filters.blade.php`, `dashboard/index.blade.php`, `reports/branches.blade.php`.
- **Tests:** `ComplaintFiltersAndAuthorizationTest` — the two picker tests now assert **all** rendered branch rows (3 seeded + 7 created = 10) and search JSON count 7 (no limit). `ComplaintManagementTest` — new `test_complaint_form_renders_single_select_branch_picker_with_all_branches` (create page has `data-single-select`, hidden `name="branch_id"`, every branch listed, no All row) and `test_complaint_edit_preselects_single_branch_in_picker` (edit pre-fills `#branch_id` + summary). Full suite **165 passed / 999 assertions**; Vite rebuilt (`public/build/assets/app-H4iHnjxH.js`). Live: `view:clear`; the 16 live branches feed all pickers.
- **Single-select branch on create/edit (`complaints/form.blade.php`):** the old native `<select name="branch_id">` (inside the 4-col grid, capped at 5) is replaced by a **1/4-column grid cell** (same width as Service/Source/Status, `div.relative` wrapper) reusing the shared `data-branch-picker data-filter-dropdown` JS with a new **`data-single-select`** mode: hidden `#branch_id` + `required` (server value from `old('branch_id', $complaint->branch_id)`), `#branch-summary` shows the chosen branch name (or `common.select`), the panel (`start-0 end-0`, max-h-64 scroll, search box) opens over the row like the filter pickers, click-to-select **closes** (no toggle), no **All** row, search stays client-side over all loaded branches, edit pre-fills via `data-selected-id` + init `selectedIds.add(...)`. Grid loop now covers `service_id/source_id/status_id` only. **Gotcha fixed:** the grid-cell panel introduced the new `end-0` utility — Blade-only edits don't regenerate Tailwind CSS, so the panel shrink-wrapped wider than the trigger until a second `vite build` (CSS `app-BMezMmjn.css` now contains `.end-0`); rule of thumb: any new Tailwind utility used in Blade needs a rebuild.
