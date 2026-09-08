# Complaint Management System — Project Structure

**Status:** Current-state implementation guide; last verified 2026-09-04
**Application:** Complaint Desk  
**Framework:** Laravel 12 on PHP 8.2+  
**Rendering:** Server-rendered Blade with Tailwind CSS and progressive JavaScript enhancement  
**Supported locales:** English (`en`) and Arabic (`ar`) with RTL layout support  
**Runtime database:** SQL Server in the current local installation at `127.0.0.1:1433`, database `complaints`; PHPUnit continues to use in-memory SQLite through `phpunit.xml`.

> This document describes the code that currently exists in the repository. It intentionally does not describe Livewire, Filament, React, Vue, an API platform, queues, or other technologies that are not used by the application’s business features.

## 1. Project Overview

Complaint Desk is an authenticated customer-support application for registering, managing, filtering, and reporting customer complaints. Support staff can search customers by name or any of four phone fields, create and update customer records, create complaints, follow status changes, record resolutions, and review a complaint timeline. Administrators manage master data, users, roles, and permissions.

The main user types are **Super Admin**, **Admin**, **Customer Support**, and **Viewer**. Access is permission-based through Spatie Laravel Permission, with an application-level Super Admin bypass. The application uses a conventional Laravel MVC flow:

```text
Browser
  ↓
Named web route + auth middleware
  ↓
Controller / Form Request
  ↓
Eloquent model relationships and query scopes
  ↓
Application service for activity or status transitions when needed
  ↓
Relational database
  ↓
Blade response, redirect, flash message, JSON picker response, or Excel download
```

The interface is a normal Laravel web application rather than a single-page application. Small JavaScript enhancements provide searchable customer and branch pickers without replacing the server-rendered pages. A Progressive Web App manifest and service worker provide installation and static-asset caching, but the business application remains network-dependent.

## 2. Technology Stack

| Concern | Actual technology | Current use |
|---|---|---|
| Backend | PHP 8.2+ and Laravel 12 | Routing, controllers, validation, authentication, sessions, migrations, Eloquent, localization, and responses |
| UI rendering | Blade templates | All application screens under `resources/views/` |
| Styling | Tailwind CSS 4 | Utility classes and reusable classes in `resources/css/app.css` |
| Browser behavior | Plain JavaScript with `fetch()` | Closed complaint-index Customer/Branch dropdowns, debounced customer and branch searches, multi-branch picker behavior, outside-click/Escape closing, and PWA registration |
| Asset build | Vite 7 with Laravel Vite plugin | Compiles `resources/css/app.css` and `resources/js/app.js` into `public/build/` |
| Database | SQLite in the current local environment | Runtime development database; migrations use Laravel schema APIs and can be configured for another relational database |
| Authentication | Laravel session authentication | Custom `AuthController` login/logout flow and `auth` middleware |
| Authorization | Laravel Gate plus Spatie Laravel Permission 6.24.0 | Permissions such as `complaint.view`, `user.update`, and `role.delete` |
| Reporting/export | Laravel Excel 3.1.67 | Query-based filtered complaint export to `.xlsx` |
| Localization | Laravel translation files and `SetLocale` middleware | English/Arabic UI, validation, activity, authentication, and export text |
| PWA | Native web manifest and service worker | Installability, icons, and static Vite asset caching only |
| Testing | PHPUnit 11 through Laravel’s test runner | Feature tests for workflows, authorization, filters, exports, localization, and PWA behavior |
| Web serving | XAMPP Apache in the current setup | Local URL is under `http://localhost/complaint/public/` |

There is no React, Vue, Inertia, Livewire, Filament, repository layer, custom API authentication, or custom queue worker in the application code.

## 3. Packages and Libraries

### Composer packages

| Package | Purpose | Important locations |
|---|---|---|
| `laravel/framework` `^12.0` | Core application framework, HTTP kernel, routing, sessions, validation, Blade, Eloquent, migrations, and testing integration | `app/`, `bootstrap/`, `config/`, `routes/`, `resources/views/` |
| `laravel/tinker` `^2.10.1` | Interactive Laravel shell for development and inspection | Artisan integration |
| `spatie/laravel-permission` `6.24.0` | Roles, permissions, model-role pivots, permission checks, and permission cache | `app/Models/User.php`, `app/Http/Controllers/RoleController.php`, `config/permission.php`, permission migration |
| `maatwebsite/excel` `3.1.67` | Excel export of the current complaint filter query | `app/Exports/ComplaintsExport.php`, `ComplaintController::export()` |
| `barryvdh/laravel-dompdf` `^3.1` | Server-side HTML→PDF rendering for quality visit reports | `app/Services/Visitors/VisitorPdfService.php`, `resources/views/visitors/reports/print.blade.php` |
| `phpunit/phpunit` `^11.5.50` | Unit and feature test runner | `phpunit.xml`, `tests/` |
| `fakerphp/faker` | Factory data generation in tests and seeders | `database/factories/` |
| `laravel/pint` | Optional PHP code formatting during development | Composer development dependency |
| `laravel/sail`, `laravel/pail`, `nunomaduro/collision`, and `mockery/mockery` | Laravel development, logging, CLI diagnostics, and test support | Composer development dependencies |

### NPM packages

| Package | Purpose | Important locations |
|---|---|---|
| `vite` `^7.0.7` | Frontend asset bundling and development server | `vite.config.js`, `package.json` |
| `laravel-vite-plugin` `^2.0.0` | Connects Vite to Laravel asset loading and build output | `vite.config.js`, Blade layout |
| `tailwindcss` `^4.0.0` | Utility-first CSS framework | `resources/css/app.css` |
| `@tailwindcss/vite` `^4.0.0` | Tailwind integration with Vite | `vite.config.js` |
| `axios` `^1.11.0` | Installed frontend HTTP client dependency | Available to frontend code; the current picker implementation uses native `fetch()` |
| `concurrently` `^9.0.1` | Runs Laravel/Vite development processes together through the Composer `dev` script | `composer.json` |
| `chart.js` | Responsive dashboard trend and distribution charts | `resources/js/app.js`, `dashboard/index.blade.php`, `visitors/reports/dashboard.blade.php` |

## 4. Technologies and Concepts Used

| Concept | What it is | Why and where this project uses it |
|---|---|---|
| Laravel MVC | Routes dispatch to controllers that validate data, use models, and render views | The entire web application follows this pattern |
| Blade | Laravel’s server-side templating engine | All screens in `resources/views/` use Blade directives and translation helpers |
| Eloquent ORM | Model and relationship layer for relational data | Complaint, customer, master-data, user, history, and activity queries |
| Form Requests | Request objects that centralize authorization and validation | `app/Http/Requests/` for complaint/customer data and complaint filters |
| Middleware | Request pipeline behavior applied before controllers | `auth`, Spatie permission middleware aliases, and `SetLocale` |
| Gates and permissions | Server-side authorization decisions | `Gate::before` protects Super Admin; controllers and route middleware enforce abilities |
| Spatie `HasRoles` | Trait and package model relations for user roles | `User` role assignment and permission checks |
| Service layer | Small classes for business operations that span multiple writes | `ComplaintStatusService` and `ActivityLogService` |
| Database transactions | Atomic grouping of related writes | Complaint creation/status transitions and status-history/activity consistency |
| Query scopes | Reusable Eloquent query composition | `Complaint::scopeFilter()` applies complaint list/export filters |
| Soft deletes | Retains records while hiding them from normal queries | Customers, branches, services, sources, categories, types, priorities, statuses, and complaints |
| Polymorphic relation | One table can reference different model types | `activity_logs.subject_type/subject_id` and `Complaint::activityLogs()` |
| Progressive enhancement | Server-rendered page remains functional while JavaScript adds convenience | Customer and branch pickers use debounced background JSON searches |
| PWA static caching | Browser installation plus cache of static assets | `public/manifest.json`, `public/service-worker.js`, and registration in `resources/js/app.js` |

## 5. Project Directory Structure

```text
complaint/
├── app/
│   ├── Exports/
│   │   ├── ComplaintsExport.php
│   │   └── Visitors/ (HasMasterDataColumns, MasterDataTemplateSheet, VisitorMasterTemplateExport, VisitorCurrentMasterDataExport, MasterDataChunkReadFilter)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── AuditLogController.php
│   │   │   ├── ComplaintController.php
│   │   │   ├── CustomerController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── LocaleController.php
│   │   │   ├── MasterDataController.php
│   │   │   ├── ReportController.php
│   │   │   ├── RoleController.php
│   │   │   ├── UserController.php
│   │   │   └── Visitors/ (VisitController, VisitItemController, VisitPhotoController, VisitorReportController, VisitorMasterDataController, VisitorViolationController)
│   │   ├── Middleware/
│   │   │   └── SetLocale.php
│   │   ├── Policies/
│   │   │   └── VisitorVisitPolicy.php
│   │   └── Requests/
│   │       ├── ComplaintFilterRequest.php
│   │       ├── StoreComplaintRequest.php
│   │       ├── StoreCustomerRequest.php
│   │       ├── UpdateComplaintRequest.php
│   │       ├── UpdateCustomerRequest.php
│   │       └── Visitors/ (StartVisitRequest, UpdateVisitItemRequest, StoreVisitPhotoRequest)
│   ├── Models/
│   │   ├── ActivityLog.php
│   │   ├── Branch.php
│   │   ├── Complaint.php
│   │   ├── ComplaintCategory.php
│   │   ├── ComplaintSource.php
│   │   ├── ComplaintStatus.php
│   │   ├── ComplaintStatusHistory.php
│   │   ├── ComplaintType.php
│   │   ├── Customer.php
│   │   ├── Priority.php
│   │   ├── Service.php
│   │   ├── User.php
│   │   └── Visitor*.php (VisitType, Section, RootCause, ChecklistItem, Visit, VisitItem, VisitPhoto, CapaAction, CapaUpdate, Severity, Import, ImportRow)
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   └── Services/
│       ├── ActivityLogService.php
│       ├── ComplaintStatusService.php
│       └── Visitors/ (VisitScoreService, VisitService, DueDateService, CapaService, VisitorPhotoService, ChecklistImportService, VisitorReportService, VisitorReportDashboardService, VisitorPdfService, VisitorMasterDataService)
├── bootstrap/
│   └── app.php
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── database.php
│   ├── filesystems.php
│   ├── permission.php
│   ├── visitors.php
│   └── ...
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── lang/
│   ├── ar/
│   │   ├── activity.php
│   │   ├── auth.php
│   │   ├── common.php
│   │   ├── complaints.php
│   │   ├── customers.php
│   │   ├── validation.php
│   │   └── visitors.php
│   └── en/
│       ├── activity.php
│       ├── auth.php
│       ├── common.php
│       ├── complaints.php
│       ├── customers.php
│       ├── validation.php
│       └── visitors.php
├── public/
│   ├── build/
│   ├── icons/
│   ├── manifest.json
│   ├── service-worker.js
│   └── .htaccess
├── resources/
│   ├── css/app.css
│   ├── js/app.js
│   └── views/
│       ├── auth/
│       ├── audit-logs/
│       ├── complaints/
│       ├── customers/
│       ├── dashboard/
│       ├── layouts/
│       ├── master-data/
│       ├── reports/
│       ├── roles/
│       ├── users/
│       └── visitors/ (home, create, open, show; reports/ → index, show, dashboard, print; master-data/ → index, preview; violations/ → index, show)
├── routes/
│   ├── console.php
│   └── web.php
├── tests/
│   ├── Feature/
│   ├── Unit/
│   └── TestCase.php
├── .env.example
├── artisan
├── composer.json
├── package.json
├── phpunit.xml
└── vite.config.js
```

`app/Http/Controllers/` contains the request-facing application actions. `app/Models/` contains Eloquent entities and relationships. `app/Services/` contains cross-cutting business services (complaint status/activity) and the Quality Visits domain services under `Visitors/` (including report building, analytics, and PDF generation). `app/Policies/` contains the `VisitorVisitPolicy`. `database/` contains schema, factories, seed data, and the visitors migrations/seeders. `resources/views/` contains all Blade UI including `visitors/` and `visitors/reports/`. `lang/` contains the bilingual dictionaries. `public/` contains the PWA and compiled public assets.

## 6. Application Architecture and Component Communication

The normal request flow is:

```text
Authenticated browser request
  ↓
Route in routes/web.php
  ↓
SetLocale middleware + auth middleware
  ↓
Controller permission check or permission middleware
  ↓
Form Request validation/authorization where defined
  ↓
Eloquent query, mutation, or database transaction
  ↓
ActivityLogService / ComplaintStatusService when the operation needs audit/history
  ↓
Blade view, redirect with translated flash message, JSON picker response, or Excel download
```

`ComplaintController` uses `ComplaintFilterRequest` for both the HTML index and Excel export, so exports use the same validated filters as the listing. `ComplaintController::masterData()` supplies bounded initial customer/branch picker results and active master data. `CustomerController::search()` and `ComplaintController::searchBranches()` provide protected JSON endpoints consumed by the JavaScript picker code.

`ActivityLogService` writes a stable action identifier, optional description, polymorphic subject, and old/new JSON snapshots. `ComplaintStatusService` changes the complaint status, stores a status-history row, sets resolution metadata when the new status is Solved, and records an activity entry in a transaction.

There is no repository pattern, DTO layer, custom event/listener workflow, or application-specific job pipeline. Laravel’s existing cache/jobs tables and configuration remain available for framework features, but the complaint workflows are synchronous.

## 7. Database Architecture

The database separates authentication, authorization, customers, complaints, master data, status history, and audit activity. The current runtime uses SQLite. The migrations use Laravel’s schema builder and foreign-key APIs; `.env.example` also shows the standard variables for switching to a server database.

```mermaid
erDiagram
    USERS ||--o{ COMPLAINTS : creates
    USERS ||--o{ COMPLAINTS : resolves
    USERS ||--o{ COMPLAINT_STATUS_HISTORIES : changes
    USERS ||--o{ ACTIVITY_LOGS : performs
    CUSTOMERS ||--o{ COMPLAINTS : has
    BRANCHES ||--o{ COMPLAINTS : receives
    SERVICES ||--o{ COMPLAINTS : uses
    COMPLAINT_SOURCES ||--o{ COMPLAINTS : originates
    COMPLAINT_CATEGORIES ||--o{ COMPLAINTS : categorizes
    COMPLAINT_TYPES ||--o{ COMPLAINTS : classifies
    PRIORITIES ||--o{ COMPLAINTS : prioritizes
    COMPLAINT_STATUSES ||--o{ COMPLAINTS : tracks
    COMPLAINTS ||--o{ COMPLAINT_STATUS_HISTORIES : records
    COMPLAINT_STATUSES ||--o{ COMPLAINT_STATUS_HISTORIES : from_or_to
    COMPLAINTS ||--o{ ACTIVITY_LOGS : has
    ROLES ||--o{ MODEL_HAS_ROLES : assigned
    USERS ||--o{ MODEL_HAS_ROLES : receives
    ROLES ||--o{ ROLE_HAS_PERMISSIONS : grants
    PERMISSIONS ||--o{ ROLE_HAS_PERMISSIONS : contains

    USERS { bigint id PK; string name; string email; string password }
    CUSTOMERS { bigint id PK; string name; string phone_primary; string phone_2; string phone_3; string phone_4; text address; timestamp deleted_at }
    COMPLAINTS { bigint id PK; bigint customer_id FK; bigint branch_id FK; bigint service_id FK; bigint source_id FK; bigint category_id FK; bigint type_id FK; bigint priority_id FK; bigint status_id FK; bigint created_by FK; bigint resolved_by FK; date complaint_date }
    COMPLAINT_STATUS_HISTORIES { bigint id PK; bigint complaint_id FK; bigint from_status_id FK; bigint to_status_id FK; bigint changed_by FK; text reason; timestamp changed_at }
    ACTIVITY_LOGS { bigint id PK; bigint user_id FK; string action; string subject_type; bigint subject_id; json old_values; json new_values; timestamp created_at }
```

The Spatie permission tables are standard package tables: `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, and `role_has_permissions`. Teams are disabled and wildcard permissions are disabled. Permission checks are registered with Laravel’s Gate and permissions are cached under the package cache key.

## 8. Database Tables

### Laravel infrastructure tables

| Table | Purpose | Important notes |
|---|---|---|
| `users` | Authenticated staff accounts | Standard Laravel user fields; complaint actor foreign keys reference this table |
| `cache` | Database cache backend when configured | Laravel skeleton table |
| `jobs` | Database queue backend when configured | Laravel skeleton table; no custom complaint job currently depends on it |
| `sessions` | Not present in the current migration set | `.env.example` names the database session driver, but this repository does not include a sessions migration; tests use array sessions |

### Business tables

| Table | Purpose | Important columns and rules |
|---|---|---|
| `customers` | Customer identity and contact data | Required `name` and `phone_primary`; nullable `phone_2`, `phone_3`, `phone_4`, `address`; phone columns indexed; soft deletes |
| `branches` | Branch master data | `name`, nullable indexed `code`, indexed `is_active`, `sort_order`; soft deletes |
| `services` | Complaint service master data | `name`, nullable `color`, indexed `is_active`, `sort_order`; soft deletes |
| `complaint_sources` | Complaint-origin master data | Same common master-data shape; soft deletes |
| `complaint_categories` | Complaint-category master data | Same common master-data shape; soft deletes |
| `complaint_types` | Complaint-type master data | Same common master-data shape plus required `category_id` → complaint_categories and `priority_id` → priorities (nullable in DB, enforced by master-data and complaint validation); soft deletes |
| `priorities` | Complaint priority master data | Required `color`, nullable `level`, indexed `is_active`, `sort_order`; soft deletes |
| `complaint_statuses` | Complaint status master data | Required `color`, indexed `is_active`, `sort_order`; soft deletes |
| `complaints` | Main complaint record | Required foreign keys to customer, branch, service, source, **category, type, priority (category/priority derived from type — not client submits)**, status; required descriptions/date/creator; nullable resolver, resolved time, resolution; soft deletes |
| `complaint_status_histories` | Append-only status transitions | Complaint, optional previous status, new status, reason, actor, and `changed_at`; restrictive complaint/status/user foreign keys |
| `activity_logs` | Audit trail | Nullable actor, stable action string, nullable polymorphic subject, description, JSON old/new snapshots, indexed action/date |
| `visitors_visit_types` | Quality inspection types | `name`, `code`, `is_active` |
| `visitors_sections` | Inspection checklist sections | `name`, `code`, `sort_order`, `is_active` |
| `visitors_root_causes` | Non-compliance root causes | `name`, `code`, `is_active` |
| `visitors_checklist_items` | Checklist questions per type | `visit_type_id`/`section_id` FKs, severity, deduction_score, photo_required, predefined actions, responsible, period_hours; no soft delete |
| `visitors_visits` | Inspection header | `visit_type_id`/`branch_id`/`inspector_id` FKs, `visit_date`, `status` (in_progress/completed), started/completed timestamps; indexed inspector/status and branch/date |
| `visitors_visit_items` | Snapshot per visit item | Unique `(visit_id, checklist_item_id)`; snapshot columns (including `period_hours` snapshotted at visit start); `root_cause_id`/`main_kitchen`/`support_department` nullable; no soft delete |
| `visitors_visit_photos` | Private evidence photos | `visit_id` cascade, `visit_item_id` noAction FKs, path/names/sizes; stored on private `local` disk |
| `visitors_capa_actions` | CAPA from non-compliances | `visit_id` cascade, `visit_item_id` noAction FKs, actions, responsible/reviewer FKs (noAction), `period_hours` (decimal 8,2; 0 = Immediate) and backend-computed `due_at` (created_at + period_hours; NULL when 0); legacy unused `due_date` |
| `visitors_capa_updates` | CAPA history timeline | `capa_action_id` cascade FK, user, status, comment, optional photo, created_at |
| `visitors_severities` | Quality severity master | `name`, `code`, `sort_order`, `is_active`; seeded Critical/Major/Minor by `VisitorsSeeder` |
| `visitors_imports` | Auditable Excel import header | User, file name/size/extension, status (pending/validating/ready/imported/failed/cancelled), row/created/updated/failed counts, error message, completed_at |
| `visitors_import_rows` | Per-import-row raw data + validation | `import_id` cascade FK, row number, code, inspection_type, valid flag, JSON errors, JSON data, created/updated flags |

`visitors_checklist_items` additionally gained a nullable `root_cause_id` FK (via migration `2026_08_31_180000_create_visitors_master_import_tables.php`) as the suggested/default root cause; the inspector still chooses freely at NC time.

The complaint foreign keys use restrictive deletion behavior so referenced master data, customers, and creator users cannot be physically deleted while historical complaints depend on them. `resolved_by` and `activity_logs.user_id` use nullable foreign keys with `nullOnDelete` because the application can preserve the record if the actor reference is removed. Business records prefer deactivation or soft deletion over physical deletion.

## 9. Migrations

Migrations are stored in `database/migrations/` and use Laravel timestamped filenames. The current order is:

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
2026_08_31_180000_create_visitors_master_import_tables
        ↓
2026_09_04_190000_replace_deadline_with_period_hours
        ↓
2026_09_06_000001_add_serial_number_and_price_to_complaints_table
        ↓
2026_09_06_000002_add_category_priority_to_complaint_types_table
```

The permission migration creates package roles, permissions, model-role/model-permission pivots, and role-permission pivots. The master-data migration creates customers, branches, services, sources, categories, types, priorities, and statuses. The complaints migration depends on those tables and creates complaint foreign keys plus branch/date and status/date composite indexes. The final complaint migration creates status history and polymorphic activity logs. The visitors master-data migration creates visit types, sections, root causes, and checklist items; the visitors transaction migration creates visits, visit items (snapshot), photos, CAPA actions, and CAPA updates. The visitors transaction migration uses `noActionOnDelete()` on derived foreign keys to satisfy SQL Server's single-cascade-path rule. The visitors master-import migration adds `visitors_severities`, `visitors_imports`, `visitors_import_rows`, and the nullable `root_cause_id` FK on `visitors_checklist_items` for the Excel master-data import feature. The two `2026_09_06_*` migrations add nullable `serial_number`/`price` to `complaints` and nullable `category_id`/`priority_id` (→ `complaint_categories`/`priorities`) to `complaint_types` for the complaint type → category/priority derivation.

`DatabaseSeeder` runs `PermissionSeeder`, `MasterDataSeeder`, `DemoDataSeeder`, `VisitorsSeeder`, and `VisitorChecklistSeeder` in that order. Future schema changes should add a new migration rather than editing an already-applied migration and should update this document when they affect project structure or data design.

## 10. Main Features and Modules

| Module | Main entry points | Main data and rules |
|---|---|---|
| Authentication | `AuthController`, `auth/login.blade.php` | Session login/logout; failed credentials use localized messages |
| Dashboard | `DashboardController`, `DashboardFilterRequest`, `DashboardStatsService`, `dashboard/index.blade.php` | Authenticated real-data analytics dashboard with validated global filters, SQL/Eloquent KPIs, driver-compatible day/week/month trends, dimension charts, resolution metrics, branch performance, recent/attention/solved lists, insights, and actionable complaint links; bar-chart legends suppressed to avoid empty dataset labels |
| Customers | `CustomerController`, `Customer` model, `customers/` views | CRUD, four-phone/name search, complaint count/history, 30-record pagination, soft deletion model support; primary phone required |
| Complaints | `ComplaintController`, `Complaint` model, `complaints/` views | CRUD, customer and master-data associations, filters, detail page, status transitions, resolution metadata, activity timeline, and 30-record pagination |
| Complaint filtering | `ComplaintFilterRequest`, `Complaint::scopeFilter()` | Complaint ID, validated short/full description text search, customer, multi-branch, master-data, creator, and date filters; query strings retained in pagination |
| Master data | `MasterDataController`, `master-data/` views | Branches, services, sources, categories, types, priorities, and statuses; active flag controls new selections; records are ordered and paginated |
| Branch reports | `ReportController`, `reports/branches.blade.php` | Date range and multi-branch filtering; SQL grouping by complaint date and branch; localized paginated results count |
| Excel export | `ComplaintsExport`, `ComplaintController::export()` | Exports the validated current complaint query and localized headings; permission protected |
| Users | `UserController`, `users/` views | Authorized user creation/update, role assignment, name/email and role filters, paginated results |
| Roles and permissions | `RoleController`, `roles/` views | Create custom roles, configure permissions, guarded deletion, protected baseline roles, assigned-user deletion prevention |
| Audit logs | `AuditLogController`, `audit-logs/index.blade.php` | Permission-restricted, action-filtered, paginated audit list; known actions are localized at display time |
| Localization | `SetLocale`, `LocaleController`, `lang/en/`, `lang/ar/` | Session locale selection, RTL Arabic layout, localized UI, validation, flash messages, activity labels, exports, and shared footer content |
| Quality Visits | `Visitors\VisitController`, `VisitItemController`, `VisitPhotoController`, `VisitorReportController`, `VisitorMasterDataController`, `VisitorViolationController`, `VisitorVisitPolicy`, `VisitService`, `VisitScoreService`, `DueDateService`, `CapaService`, `VisitorPhotoService`, `VisitorReportService`, `VisitorReportDashboardService`, `VisitorPdfService`, `VisitorMasterDataService`, `visitors/` views | single-page New Visit form (visit type + branch + date, inspector = auth user) → checklist with vanilla-JS autosave and review progress (no default status; live score hidden on inspection — score appears only on reports/PDF/dashboard) → private photo upload → submission that creates CAPA and blocks unreviewed/missing-photo items; **Critical NC items require mandatory evidence** (severity-based `VisitorVisitItem::isCritical()`/`requiresPhoto()`, `📷 Evidence *` indicator toggled on NC, submit blocked with `evidence_photo_required` alert/message); owner-only in-progress access; backend-only score computation; completed-visit QHSE reports + analytics dashboard + dompdf PDF export (not owner-restricted); Excel master-data management (template/example/current-data download, 50 MB `.xlsx`/`.xls` upload → chunk-read → preview → confirm create/update keyed on inspection_type code + item code, no destructive deletes) with `visit.master.*` permissions; **violation lifecycle + follow-up** (inline Existing Open Violation panel with Still Open / Resolved / New Violation, resolution evidence photos, `visit.review` approve/reject of `pending_review` with reason, no duplicate open findings, immutable historical reports) |
| PWA | `public/manifest.json`, `public/service-worker.js`, `resources/js/app.js` | Installability, icons, standalone display, static hashed asset caching, no private-page/API caching |

## 11. Important Business Flows

### Customer search and complaint creation

```text
Open complaint creation
  ↓
Load at most 10 initial customers
  ↓
Search by customer name or any of four phone fields using the JSON endpoint
  ↓
Select or create the customer
  ↓
Select active branch, service, source, category, type, priority, and status
  ↓
Submit validated complaint
  ↓
Create complaint with authenticated created_by
  ↓
Record localized activity entry
  ↓
Redirect to complaint detail page
```

The picker uses a 250 ms debounce and `AbortController`; server-side search returns at most 10 customers. The selected customer is preserved even if it is outside the initial ten results.

### Complaint status transition

```text
Open complaint edit
  ↓
Submit a new active status and optional status reason
  ↓
Begin database transaction
  ↓
Update complaint status
  ↓
If status is Solved, set resolved_by and resolved_at
  ↓
Create complaint_status_histories row
  ↓
Create complaint.status_changed activity row
  ↓
Commit and redirect with localized success message
```

Status names are dynamic master data rather than a hard-coded enum. The configured Solved status is detected by its normalized name. Existing complaints may continue to reference inactive statuses for historical display.

### Filtering and export

```text
GET complaint list with query parameters
  ↓
ComplaintFilterRequest validates filters
  ↓
Complaint::scopeFilter() composes the Eloquent query
  ↓
Paginated HTML result with query string preserved
  ↓
Optional Excel export reuses the same validated filters
```

Branch filters use an array and `whereIn`, so multiple selected branches are handled in one query. The branch report applies equivalent date and branch constraints before grouping in SQL.

### Administration

```text
Authorized administrator
  ↓
Create/update master data, users, or roles
  ↓
Controller permission check and request validation
  ↓
Persist through Eloquent
  ↓
Apply protected-role and assigned-user rules where relevant
  ↓
Redirect with localized flash message
```

Role deletion is refused when the role is one of the four protected baseline roles or has any assigned users. New roles are created with the `web` guard and can be configured through the permission editor.

## 12. Authentication and Authorization

Authentication uses the standard Laravel `users` table and session guard. `AuthController` validates login credentials, regenerates the session after a successful attempt, redirects to the dashboard, and invalidates the session/token on logout. The authenticated application routes are grouped under `auth` middleware.

Spatie Laravel Permission is configured with the default `Role` and `Permission` models, default table names, no teams, no wildcard permissions, and Laravel Gate permission checks. Permission cache expiration is configured for 24 hours and is flushed by package role/permission updates.

The baseline roles are:

| Role | Actual seeded behavior |
|---|---|
| `Super Admin` | Receives all permissions and bypasses Gate checks through `AppServiceProvider::boot()` |
| `Admin` | Receives the operational permissions except `role.delete`, `user.delete`, and `audit.view` in the seed configuration |
| `Customer Support` | Can work with customers and complaints using create/view/update permissions, but does not administer users, roles, or master data |
| `Viewer` | Read-only access to selected customer, complaint, master-data, and report areas |

Permission names use dot notation. The seeder creates systematic CRUD permissions for customers, complaints, branches, services, sources, categories, types, priorities, statuses, reports, users, roles, audit, and visits, plus complaint log/export, report export, `visit.submit`, and `visit.manage` permissions. Customer Support and Viewer roles grant the visit view/create/update permissions.

Authorization is enforced server-side in controllers and route middleware; navigation/button visibility is only a usability layer. Super Admin role and permission protections are also checked in role/user administration. The application does not expose public registration or password-reset routes.

## 13. Routes and Entry Points

All routes are defined in `routes/web.php`. There is no separate `routes/api.php` business API. JSON search endpoints are protected web routes.

| Route group | Important entry points | Protection/output |
|---|---|---|
| Authentication | `/login`, `/logout` | Login is public; logout requires `auth` |
| Locale | `/locale/{locale}` | Switches the session locale between supported languages |
| Dashboard | `/` | Authenticated dashboard controller |
| Customers | `/customers`, `/customers/create`, `/customers/{customer}` and search | Authenticated and permission-checked; search returns JSON |
| Complaints | `/complaints`, create/show/edit/update, `/complaints/branches/search`, `/complaints/export` | Authenticated; permissions control view/create/update/export |
| Reports | `/reports/branches` | Authenticated and `report.view` middleware |
| Master data | `/master-data/{type}` and CRUD subroutes | Authenticated and permission-protected; type selects the supported master-data table |
| Users | `/users`, create, edit, update | Authenticated and controller permission checks |
| Roles | `/roles`, `/roles/create`, `/roles/{role}/edit`, POST/PUT/DELETE role actions | Authenticated and controller permission checks |
| Audit | `/audit-logs` | Authenticated and `audit.view` permission |
| Quality Visits | `/visitors`, `/visitors/create`, `/visitors/open`, GET/POST `/visitors/{visit}`, POST `/visitors/{visit}/submit`, PUT `/visitors/items/{visitItem}`, POST `/visitors/items/{visitItem}/photo`, GET `/visitors/photos/{photo}`, `visitors/reports`, `visitors/reports/dashboard`, `visitors/reports/{visit}`, `visitors/reports/{visit}/pdf`, `visitors/master-data`, `visitors/master-data/template`, `visitors/master-data/template-example`, `visitors/master-data/download`, POST `visitors/master-data/import`, `visitors/master-data/import/{import}/preview`, POST `.../confirm`, POST `.../cancel` | Authenticated `visitors.` prefix; permission checks in controllers/requests and `VisitorVisitPolicy` (owner + not-completed; completed-report access via `visit.manage` or `report.view`-and-owner); JSON autosave/photo endpoints and streamed photo serving; report and master-data routes registered before the `{visit}` wildcard; master-data routes require `visit.master.view`/`import`/`export` |
| Framework | `/up`, `/storage/{path}` | Laravel health/storage routes |

`bootstrap/app.php` registers the web routes, the `permission` and `role` middleware aliases, and appends `SetLocale` to the web middleware group.

## 14. Frontend Architecture

The frontend is Blade plus Tailwind CSS. `resources/views/layouts/app.blade.php` provides the authenticated shell, navigation, flash alerts, validation-error display, language direction, localized contact/copyright footer, PWA metadata, and Vite asset loading. Footer contact values are supplied in the shared English and Arabic dictionaries, including the configured contact name and phone number. The footer is a minimal responsive horizontal bar containing contact and social links; it wraps only on narrow screens, and the location section was intentionally removed. English uses `Abdelrahman Emad`, while Arabic uses `عبدالرحمن عماد`; both locales retain the same phone number. The footer exposes safe external links to LinkedIn and Facebook and uses the current year in the copyright line. `PwaTest` verifies the minimal horizontal footer content in both locales, including the intentional absence of location content and the compact flex/padding structure. Final minimal-footer verification passed with 5 tests and 48 assertions; the full suite passed with 46 tests and 228 assertions, and Blade caching, the Vite build, and `git diff --check` passed. Individual modules use page views and a small number of form/list templates; they do not use a client-side router.

`resources/css/app.css` defines the application’s reusable visual classes, including cards, buttons, form controls, tables, badges, navigation states, and focus/hover/press behavior. `resources/js/app.js` contains:

1. Complaint-index Customer initialization as a closed single-select dropdown with 10-result rendering, debounced `fetch()` search, abort handling, compact summary, Clear action, selected-record preservation, and outside-click/Escape closing; legacy create/edit customer picker compatibility is retained.
2. Complaint-index Branch initialization as a closed multi-select dropdown with five-result rendering, checkbox-style selected states, compact count summary, Clear action, hidden `branch_ids[]` fields, debounced search, abort handling, selected-record preservation, and outside-click/Escape closing; legacy branch-report picker compatibility is retained.
3. PWA service-worker registration derived from the manifest scope, with cache update settings.

The application remains usable without JavaScript because the main forms and list pages are server-rendered. JavaScript improves large-data selection performance; it does not expose private data without the same protected JSON routes.

### PWA policy

The manifest uses the Complaint Desk name, short name, relative application scope/start URL, standalone display, theme/background colors, and 180/192/512 pixel icons, including a maskable icon. The service worker caches only Vite hashed files under `build/` and static icons under `icons/`. It does not cache HTML navigations, authentication, sessions, API/JSON responses, or private pages. Full offline complaint entry, synchronization, and offline authentication are not implemented.

## 15. Configuration and Environment

The project reads configuration through Laravel’s `.env` file. `.env.example` documents variable names and safe defaults; secrets must never be committed to `project_structure.md` or source control.

| Variable/configuration | Purpose |
|---|---|
| `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` | Application environment, encryption key, debug mode, and base URL |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE` | Default/fallback language and generated-data locale |
| `DB_CONNECTION` and database-specific `DB_*` variables | Database driver and connection settings |
| `SESSION_DRIVER`, `SESSION_LIFETIME`, `SESSION_DOMAIN` | Session storage and lifetime |
| `CACHE_STORE` and `CACHE_PREFIX` | Cache backend and key prefix |
| `QUEUE_CONNECTION` | Queue backend; current default template is database |
| `FILESYSTEM_DISK` | Filesystem disk; current application has no complaint attachment workflow |
| `MAIL_*` | Mail transport and sender configuration; no complaint email workflow currently exists |
| `REDIS_*` and `MEMCACHED_HOST` | Optional cache/queue backends supported by Laravel configuration |
| `AWS_*` | Optional S3-compatible filesystem configuration |
| `VITE_APP_NAME` | Frontend build-time application name |

The current local `.env` uses SQL Server at `127.0.0.1:1433` with database sessions/cache/queues according to the runtime configuration, and a local application URL. Tests override the database with in-memory SQLite, cache with the array store, mail with the array mailer, queue with synchronous execution, and session with the array store in `phpunit.xml`.

Localization is applied per request by `SetLocale`, which reads the session `locale` value and permits only `en` or `ar`. The selected Arabic locale sets RTL direction in the main layout.

## 16. Testing

The project uses PHPUnit 11 through Laravel’s `php artisan test` command. Tests use `RefreshDatabase`, seed the application baseline where needed, and run against in-memory SQLite as configured by `phpunit.xml`.

```text
tests/
├── Feature/
│   ├── ComplaintExportTest.php
│   ├── ComplaintFiltersAndAuthorizationTest.php
│   ├── ComplaintManagementTest.php
│   ├── LocalizationTest.php
│   ├── PwaTest.php
│   └── ExampleTest.php
├── Unit/
│   └── ExampleTest.php
└── TestCase.php
```

Current feature coverage includes customer phone/name search, customer and complaint workflows, authenticated complaint creator assignment, complaint filters including complaint ID, short/full description search, closed Customer single-select and Branch multi-select dropdown markup, and multiple branches, complaint status history and resolution metadata, authorization denial, role lifecycle safeguards, users filters, branch reports, localized Excel headings, description-filtered export reuse, English/Arabic localization including dashboard labels, PWA manifest/service-worker boundaries, dashboard analytics/filter payloads, and Blade/runtime regression cases. Tests are feature-focused; application services do not currently have separate unit-test classes.

## 17. Important Development Commands

Run these commands from the project root:

```bash
# Install PHP dependencies
composer install

# Create/update the local environment and application key
copy .env.example .env
php artisan key:generate

# Run schema migrations and seed baseline/demo data
php artisan migrate
php artisan db:seed

# Clear/rebuild cached configuration and compiled Blade views
php artisan config:clear
php artisan view:clear
php artisan view:cache

# Install and build frontend assets
npm install
npm run dev
npm run build

# Run the full test suite
php artisan test --no-ansi

# Inspect routes and migration status
php artisan route:list
php artisan migrate:status

# Optional Laravel combined development command
composer run dev
```

For XAMPP deployment, Apache should serve the Laravel `public/` directory. In the current local setup, the application is accessed at `http://localhost/complaint/public/`. Build assets with `npm run build` before relying on the production Vite manifest.

## 18. Current Architecture Decisions

- The application uses Blade and Laravel controllers rather than introducing a separate SPA. This keeps the complaint workflows simple, server-authorized, and compatible with the existing XAMPP deployment.
- Searchable customer and branch selectors use bounded initial results plus protected JSON search endpoints. This avoids loading 100,000-plus customers or a full branch table into an HTML select.
- Complaint filtering is composed in an Eloquent scope and reused by Excel export so the visible report and downloaded report have the same constraints, including the grouped short/full description text search.
- Complaint creation and status changes use database transactions where multiple records must remain consistent.
- Statuses are master data rather than a PHP enum so administrators can manage them, while the configured Solved name controls resolution metadata.
- Foreign keys use restrictive deletion behavior for historical complaint references. Master data should normally be deactivated or soft-deleted rather than physically removed.
- Activity logs store stable action identifiers and JSON snapshots. Display views translate known identifiers at render time so changing the current locale does not expose raw keys such as `complaint.updated`.
- Permission checks are server-side. UI conditionals improve usability but are not considered a security boundary.
- The PWA service worker is deliberately static-only. Private data, session state, authentication, and JSON responses are not cached.

## 19. Known Limitations

- The application is network-dependent. It is installable as a PWA, but full offline complaint creation, synchronization, and offline authentication are not implemented.
- Master-data names are stored as single database values rather than per-locale translations. The interface labels are bilingual, but a branch/service/status name entered in one language remains that database value.
- There is no public customer portal, registration, password reset, attachment workflow, email notification, WhatsApp integration, or external complaint API.
- There is no branch-specific user scoping; authorization is role/permission based rather than restricted to a user’s branch.
- The application currently has no custom queued jobs, scheduled tasks, event/listener pipeline, or background synchronization process.
- The current local runtime uses SQL Server and XAMPP Apache; production database, mail, storage, cache, and queue infrastructure must be configured separately.
- `public/storage` is not linked in the current local runtime, and the present complaint workflow does not upload files.
- The default Laravel `welcome.blade.php` remains in the repository, but the application’s `/` route is the authenticated dashboard and does not use the default welcome screen.

## 20. Documentation Maintenance

`project_structure.md` is living documentation. When a change affects project structure, database schema, migrations, packages, technologies, architecture, major features, business flows, authorization, configuration, or development workflow:

1. Inspect the current code and this document.
2. Implement the code change.
3. Run the relevant tests/build/migration checks.
4. Update this document to describe the final implementation.
5. Confirm that no secrets, passwords, API keys, or tokens have been added.

The companion `database_design.md` provides a more detailed data-design narrative. This file is the higher-level navigation guide for developers joining the project.


## Documentation synchronization workflow

Persistent project instructions require every code edit or code change to begin by reading `memory.md`, `complete_project_specification.md`, and `project_structure.md`. After the change, all three files must be updated in the same task so they remain synchronized with the actual code, migrations, routes, permissions, tests, configuration, and known limitations. `complete_project_specification.md` is the requirements and verified implementation-status source, `memory.md` is the continuation context, and this file is the architecture/developer map. Schema changes additionally require `database_design.md`. Relevant tests and build checks must be run before reporting completion, with verification results recorded in `memory.md`.


### Pagination verification

Feature tests verify that the complaints and customers index pages each render 30 records on both the first and second pages when more than 100 records exist. The test setup uses the seeded demo dataset, ensuring the paginator is exercised with a realistic larger result set. The latest full suite passed with 39 tests and 179 assertions; Blade caching, the Vite production build, migration status, and `php artisan db:seed --force` also completed successfully.


### Dark-mode theme system

The shared layout provides a localized light/dark switch with accessible button state. `resources/js/app.js` initializes the selected theme, persists it in `localStorage` under `complaint-theme`, updates the document color scheme, and changes the PWA `theme-color` metadata. `resources/css/app.css` defines dark-mode overrides under `html[data-theme='dark']` for shared surfaces, typography, form controls, navigation, alerts, tables, buttons, badges, picker result panels, and direct light-palette utility classes used by module views. Explicit `.card`, `.form-input`, `.form-label`, `.page-title`, `.section-title`, `.page-subtitle`, `.nav-link`, and `.btn-secondary` overrides provide a consistent brighter dark-only palette with clearer borders, hover states, and focus states while leaving light mode unchanged. Report filters and all other server-rendered forms therefore use readable dark surfaces rather than remaining white or low-contrast. The focused dark-mode layout/form/contrast regression tests passed; the final full suite passed after this correction with 34 tests and 144 assertions, alongside successful Blade caching, Vite production build, and route verification.


### Branch report pagination

`ReportController::branches()` now paginates grouped complaint-date/branch report rows at 30 per page and calls `withQueryString()` so date and multi-branch filters persist across pages. `reports/branches.blade.php` displays the filtered total through the bilingual `common.results_count` key and renders Laravel paginator links below the report table. The English value is `:count results`, and the Arabic value is `عدد النتائج: :count`. The existing five-result branch picker and protected background search remain unchanged. Regression coverage includes the exact English and Arabic rendering. The focused localization/filter suites passed with 26 tests and 125 assertions; the full suite passed with 44 tests and 209 assertions; Blade caching, the Vite production build, and `git diff --check` also passed.


### Complaint export columns and timeline mapping

`app/Exports/ComplaintsExport.php` now maps 17 columns. In addition to the existing complaint fields, it exports `short_description` and a localized `Timeline` column. The export eager-loads the complaint status histories, related statuses and actors, and activity-log users. It combines both event collections, sorts them by event timestamp, and writes newline-separated readable lines into one Excel cell. Status transitions include the previous/new status and optional reason; general activity events use the bilingual activity dictionary. Focused export tests verify the two new headings, mapped timeline content, and description-filter reuse. Full validation passed with 38 tests and 171 assertions; Blade views compiled and cached, the Vite production build completed, all migrations reported Ran, and 42 routes were registered.


### Complaint description search

The complaints filter form has a normal GET search field named `description` at the end of the filter grid, localized in English and Arabic as “Search short or full description” / `البحث في الوصف المختصر أو الكامل`. `ComplaintFilterRequest` validates it as optional text with a 255-character maximum. `Complaint::scopeFilter()` trims the term and applies one grouped `LIKE` predicate against `short_description` or `description`; the same validated filters flow into pagination and `ComplaintsExport` without a separate export query.

Feature regression coverage verifies short-only and full-only matches exclude unrelated complaints, and export coverage verifies the shared query scope honors the same description term. No schema or route change was required.

### Final verification for complaint description search

The focused complaint-filter suite for description filtering passed with 18 tests and 67 assertions, and the focused export suite passed with 4 tests and 15 assertions. The subsequent dropdown UI regression brought the focused complaint-filter suite to 19 tests and 75 assertions. Latest full verification passed with 39 tests and 179 assertions; Blade views compiled and cached, the Vite production build completed, all migrations reported `Ran`, and `php artisan route:list` reported 42 routes.

### Complaint filter dropdown controls

The complaints index uses a closed Customer dropdown that submits the existing single `customer_id` value and a closed Branch dropdown that submits the existing multi-value `branch_ids[]` inputs. Their searchable result panels are hidden until the select-style trigger is activated, are bounded with scrolling, show selected states, provide localized Clear actions, and close when the user clicks outside or presses Escape. `resources/js/app.js` scopes this behavior to the complaint-index `data-filter-dropdown` markup and keeps the create/edit and branch-report picker markup compatible.

### Maintenance note

This document is synchronized with the current codebase as of 2026-08-27. The three living project documents must be read before every future code edit and synchronized after each meaningful change; schema changes additionally require `database_design.md` updates.
### Advanced dashboard redesign

The advanced dashboard is implemented around `DashboardFilterRequest`, `DashboardStatsService`, `DashboardController`, `dashboard/index.blade.php`, and Chart.js initialization in `resources/js/app.js`. The service keeps dashboard statistics on filtered Eloquent/SQL queries rather than loading all complaints into PHP, while preserving the existing authenticated root route and `complaint.view` authorization. The responsive view, chart bootstrap, bilingual labels, actionable links, and Chart.js dependency are verified by the dashboard and localization feature coverage.


Dashboard verification originally corrected the trend query to use an explicit `DATE(complaint_date) AS bucket_date` alias before building day/week/month buckets. After SQL Server testing exposed that `DATE()` is not a SQL Server function, the service now selects `CAST(complaint_date AS date)` for the `sqlsrv` driver and retains `DATE(complaint_date)` for SQLite/MySQL, using the same expression in grouping and ordering. The dashboard regression continues to match Laravel’s indexed `branch_ids[0]` link encoding and checks the rendered complaint identity.


The dashboard regression test matches the existing multi-value branch filter contract by checking Laravel’s indexed `branch_ids[0]` query encoding in actionable links. No runtime dashboard behavior changed; focused dashboard verification passed with 2 tests and 10 assertions.


The focused dashboard test checks the complaint ID shown by the compact recent-complaints dashboard row instead of asserting a description that the row does not render. This is test-only alignment; runtime dashboard behavior is unchanged.


The dashboard total KPI links to `complaints.index` using the complete active dashboard query; only status-specific KPI cards append their own status constraint. This keeps actionable navigation aligned with visible filter semantics. Both locales contain the branch-filter hint translation, and final verification is recorded below.


The English locale contains the dashboard branch multi-select hint key `multi_select_hint`; the Arabic equivalent is also present and covered by localization tests.


The Arabic locale contains the dashboard branch multi-select hint key `multi_select_hint`, completing the bilingual dashboard-filter labels.


`LocalizationTest` covers the dashboard filter label, multi-branch selection hint, and Solved/Closed resolution-definition label in both supported locales. The expanded localization suite passed as part of the final full suite.


## Advanced dashboard — final verified implementation

The advanced dashboard is complete. `DashboardFilterRequest` validates date and master-data filters; `DashboardStatsService` applies them to real Eloquent/SQL aggregations with driver-compatible trend date expressions; `DashboardController` serves the authenticated root route; `dashboard/index.blade.php` renders the responsive analytics layout; and `resources/js/app.js` initializes bundled Chart.js trend/distribution charts with theme-aware colors. The dashboard includes KPI cards, day/week/month trends, branch/category/type/service/source/status/priority charts, resolution metrics, branch performance, recent/attention/solved lists, actionable complaint links, and rule-based insights. Single-dataset bar-chart legends are suppressed because their datasets have no standalone label; doughnut legends remain enabled for category labels. `DashboardTest` protects this chart configuration against regression. Solved and Closed count as resolved, and average resolution time is calculated only from valid `created_at` to `resolved_at` intervals.

The dashboard-focused suite now passes with 3 tests and 12 assertions, including the bar-chart legend regression; the full Laravel suite passes with 45 tests and 211 assertions. Blade caching, the Vite build, migration status, the 42-route listing, and `git diff --check` all pass. `chart.js` is listed in `package.json` and the lockfile. No route, permission, or schema change was required. The sandbox browser cannot reach the user’s XAMPP-only localhost service, so live desktop/mobile visual inspection is not independently performed here; automated Blade/frontend/feature checks pass. Single-dataset bar-chart legends are explicitly suppressed in `resources/js/app.js`, while doughnut legends remain enabled. Earlier progress notes in this section are superseded by this final record.


The dashboard service seeds the branch filter with five name-ordered branches and appends selected IDs outside that seed, matching the bounded branch-picker scalability pattern used elsewhere. Its trend aggregation now uses `CAST(complaint_date AS date)` on SQL Server and the existing `DATE(complaint_date)` expression on SQLite/MySQL. Dashboard, branch-report, and complaint-create picker markup standardization is complete and verified in the latest full suite.


The dashboard Branch filter now uses the shared closed `data-filter-dropdown` multi-select markup, preserving `branch_ids[]`, bounded initial results, remote branch-name search, selected states, Clear action, and outside-click/Escape closing. The branch-report and complaint-create controls are also standardized and covered by the latest regression suite.


The branch-report Branch filter now uses the shared closed `data-filter-dropdown` multi-select markup, preserving `branch_ids[]`, bounded initial results, remote branch-name search, selected states, Clear action, and outside-click/Escape closing. The complaint-create Customer picker is also standardized and verified.


The complaint create/edit Customer control now uses the shared closed `data-filter-dropdown` single-select markup, preserving `customer_id`, the bounded ten-customer initial list, background name/any-phone search, selected state, Clear action, and outside-click/Escape closing. Dashboard and branch-report branch controls are also standardized; regression coverage and final verification passed in the latest full verification.


Feature coverage now verifies closed picker markup on the dashboard Branch filter, branch-report Branch filter, and complaint create Customer filter. Dashboard/report branches preserve `branch_ids[]`; complaint creation preserves `customer_id`; bounded search behavior remains implemented by the shared picker JavaScript.


## Cross-page picker standardization — final verified implementation

The dashboard Branch picker and branch-report Branch picker are closed searchable multi-select controls preserving `branch_ids[]`. The complaint create/edit Customer picker is a closed searchable single-select control preserving `customer_id`. All three reuse the shared native-JavaScript picker behavior in `resources/js/app.js`: bounded initial results, 250 ms debounce, background `fetch()` search, `AbortController` cancellation, selected states, Clear actions, outside-click/Escape closing, dark-mode-compatible classes, localization, and responsive scrollable panels. The dashboard now seeds five branches and appends selected branches outside that seed, while complaint forms continue to seed ten customers.

Focused cross-page picker tests passed with 22 tests and 95 assertions. Final verification on 2026-08-27 passed with 43 tests and 205 assertions; Blade caching, the Vite build, migration status, 42 registered routes, and `git diff --check` all passed. No schema, route, permission, or backend filter-contract change was required. The sandbox browser could not reach the user’s XAMPP-only localhost service, so live desktop/mobile visual inspection was not independently performed; automated Blade/frontend/feature checks passed. Earlier picker-standardization progress notes are superseded by this final record.


The dashboard Branch dropdown intentionally renders no helper hint beneath the trigger; the incorrect Ctrl/Cmd guidance was removed. Its closed searchable multi-select behavior and `branch_ids[]` contract remain unchanged. The shared translation key remains available for other controls but is not used by the dashboard view. The temporary `.sqlsrv_dashboard_verify.php` script executed the dashboard service against SQL Server successfully (`total=100`, daily grouping, 30 trend points) and was removed. The full post-fix PHPUnit suite passed with 43 tests and 205 assertions, Blade caching and `npm.cmd run build` passed, 42 routes were registered, and `git diff --check` passed.


The attached local `.env` selects the SQL Server driver and database `complaints` at `127.0.0.1:1433` with SQL authentication. Credentials are not stored in this architecture document. The attached Windows PHP runtime exposes `pdo_sqlsrv` and `sqlsrv`; connectivity, migrations, and seeders were verified successfully. Seed counts are 100 customers, 100 complaints, 4 roles, 3 branches, 3 services, 6 sources, 6 categories, 6 types, 4 priorities, 5 statuses, and 1 seeded admin user. `.env` is ignored by `.gitignore`, is no longer tracked by Git, and remains locally available to the attached runtime.


The `sqlsrv` connection in `config/database.php` now consumes `DB_ENCRYPT` and `DB_TRUST_SERVER_CERTIFICATE`. ODBC Driver 17 required the local environment to use the ODBC-compatible string `DB_ENCRYPT=no`; `DB_TRUST_SERVER_CERTIFICATE=true` remains enabled for local development. Credentials are kept only in `.env` and are not documented. The complaints and complaint-history migrations use explicit `noActionOnDelete()` for required foreign keys because SQL Server rejects `ON DELETE RESTRICT`; this preserves the intended restrictive/no-action policy. The SQL Server migration sequence and seeders completed successfully. Verified counts are 100 customers, 100 complaints, 4 roles, 3 branches, 3 services, 6 sources, 6 categories, 6 types, 4 priorities, 5 statuses, and 1 seeded admin user. The temporary verifier was removed.


## Customer show SQL Server compatibility — 2026-08-29

`CustomerController::show()` previously issued a raw aggregate query whose correlated subquery used `limit 1` (`select id from complaint_statuses where name = 'Pending' limit 1`), which SQL Server rejects with `Incorrect syntax near 'limit'` (SQL Server requires `TOP 1`). This surfaced when viewing a customer (`/customers/{customer}`) against the local SQL Server runtime. The `$summary` SQL block was unused dead code: `customers/show.blade.php` already computes the total/pending/in-progress/solved/closed counts in PHP by filtering the loaded complaint collection (`$customer->complaints->where('status.name', ...)`). `CustomerController::show()` now only load the complaint relationships and passes `$customer` to the view; no `$summary` variable is built or passed. No schema, route, permission, or frontend change was required. Full verification on 2026-08-29 passed with **46 tests / 228 assertions**. `memory.md` and `complete_project_specification.md` were synchronized with this fix.


## Quality Visits module - 2026-08-31

A new `visitors_`-prefixed Quality Visits (inspection) module was added without breaking the existing complaint system. It introduces `Visitor*` models under `app/Models` (each with explicit `` and explicit foreign keys because the `visitors_` table names deviate from Eloquent snake-case), services under `app/Services/Visitors` (`VisitService`, `VisitScoreService`, `DueDateService`, `CapaService`, `VisitorPhotoService`, `ChecklistImportService`), controllers under `app/Http/Controllers/Visitors`, the `VisitorVisitPolicy` registered in `AppServiceProvider`, routes under a `visitors.` prefix, seeders (`VisitorsSeeder`, `VisitorChecklistSeeder`), `visitors.php` dictionaries, and `visitors/` Blade views with vanilla-JS autosave, progress, section pills, an NC panel, and private photo upload. *(2026-09-02: items default to no status and the live score was removed from the inspection page — scores now appear only on reports/PDF/dashboard.)*

The visitors transaction migration uses `noActionOnDelete()` on derived foreign keys (`visitors_visit_photos.visit_item_id`, `visitors_capa_actions.visit_item_id`/`responsible_user_id`/`reviewed_by`) because SQL Server rejects multiple cascade paths. Scores are computed only on the backend; the checklist configuration is snapshotted onto `visitors_visit_items` at visit creation. Full verification on 2026-08-31 passed with **60 tests / 296 assertions** (46 existing + 14 new visitors tests), migrations and seeders run against both SQL Server and the SQLite test DB. `memory.md`, `database_design.md`, and `complete_project_specification.md` were synchronized with this module.

A Quality Visits Reports module was added on top of the inspection module without duplicating business logic. `VisitScoreService` remains the single source of score truth (new `fromAggregates()` used by `calculate()`, the report list, and charts). Reports are **not** owner-restricted: `VisitorVisitPolicy::viewReport()` requires a completed visit and `visit.manage` OR (`report.view` AND owner); a new `visitors.reports` Gate covers list/dashboard access; Super Admin bypasses via the existing `Gate::before`. `VisitorReportService` builds a single report (`build()`: score, counts, severity, sections, root causes, only-NC violations from snapshot columns, CAPA with `VisitorCapaAction::effectiveStatus()` due/overdue splitting via `DueDateService`) and a DB-aggregated paginated list (`listReports()`, per-visit `critical_violations` + `capa_due` attributes and a `due_status` filter) whose score-color filter uses `HAVING` with repeated aggregate expressions (SQL Server cannot reference select aliases in `HAVING`). `VisitorReportDashboardService` computes cards, severity/section/root-cause distributions, per-branch weighted score comparison + best/worst, recurring and critical violations, due-status analytics (dueCards/dueStatusChart/branchDueAnalysis) plus CAPA status analytics + average time-to-close (overdue split via `due_at`, computed in PHP for cross-DB compatibility), inspector performance, and a day/week/month score trend — all with SQL joins/groupBy/HAVING rather than loading rows into PHP. `VisitorPdfService` renders a self-contained-CSS dompdf view (`visitors/reports/print`) embedding private photos resolved to absolute paths. Views `visitors/reports/{index,show,dashboard,print}` and a home Reports card link were added; Chart.js renders `#report-*`-prefixed canvases with theme-refresh support in `app.js`. Full verification on 2026-08-31 passed with **76 tests / 433 assertions** (61 existing + 15 new `tests/Feature/VisitorReportsTest.php`), and the list/PDF/dashboard were smoke-tested against live SQL Server. `memory.md` and `complete_project_specification.md` were synchronized with this module.

A **Quality Visits Master Data (Excel) management** module was added on top of the inspection module without breaking complaints or visits. `VisitorMasterDataController` (index/template/templateExample/download/import/preview/confirm/cancel) and `VisitorMasterDataService` (validateUpload → `readRows` chunked via `MasterDataChunkReadFilter` → validateRows by code-or-name lookups → `storeImport` preview (no DB mutation, status `ready`) → `confirm` transactional create/update up-sert keyed on inspection_type code + item code, refusing when `invalid_rows > 0` → `cancel`) manage the checklist through Excel. Uploads are `.xlsx`/`.xls` only, 50 MB max, with real MIME verification; the exact 11-column order is `code, inspection_type, section, note, severity, immediate_action, corrective_action, responsible, period_hours, preventive_action, deduction_score` (see the 2026-09-02 update below for what changed; the `period` legacy header is now accepted at normalize — see the 2026-09-04 update below). New tables: `visitors_severities`, `visitors_imports`, `visitors_import_rows`; `visitors_checklist_items` gained a nullable `root_cause_id`. Exports live under `app/Exports/Visitors/` (`VisitorMasterTemplateExport` — Template + "Example (Sample Data)" sheets — and `VisitorCurrentMasterDataExport`). New permissions `visit.master.view`/`import`/`export` (Admin granted; Customer Support denied); new `master_*` bilingual keys and `visitors/master-data/{index,preview}` views plus a home Master Data card. `VisitorMasterDataService` up-serts only (no destructive deletes) and never overwrites the immutable `visitors_visit_items` snapshots. Full verification on 2026-08-31 passed with **91 tests / 479 assertions** (76 existing + 15 new `tests/Feature/VisitorMasterDataTest.php`); the migration and `VisitorsSeeder` were run against live SQL Server (3 severities seeded) and routes confirmed via `php artisan route:list`. `memory.md`, `complete_project_specification.md`, and `database_design.md` were synchronized with this module.

## Shared shell/PWA + master-data format update - 2026-09-02

On 2026-09-02 the shared authenticated shell and the Quality Visits master-data Excel format were updated:

- **PWA installability (browser UI only — custom button removed):** the app stays installable (manifest + service worker), but the custom **Install App button was removed on 2026-09-06** — the desktop header `data-install-button`, the mobile-drawer `data-install-button-mobile`, the bilingual `data-install-modal`, the whole install IIFE in `resources/js/app.js` (`beforeinstallprompt`/`appinstalled`/`getInstalledRelatedApps`/standalone detection), the install dark-mode CSS in `resources/css/app.css`, and the install lang keys (`install_app`, `add_to_home_screen`, `install_instructions_title`, `install_instructions_android`, `install_instructions_ios`, `install_now`, `install_close`; per-locale keys went 221 → 214) were all deleted. Users install via the browser's own menu; the draggable `#wco-titlebar` strip remains.
- **Arabic branding & navbar:** hardcoded "Complaint Desk" became `__('common.application_name')` (`ar` = نظام الشكاوى) in head meta/title, the desktop header, and the mobile drawer. The desktop/hamburger breakpoint moved from `lg` to `xl` (1280px) with `min-w-0 flex-1` centered nav and `shrink-0` logo/controls so the Arabic brand never overlaps the menu. Nav/drawer animation was added in `resources/css/app.css` (drawer 360ms slide+fade, overlay fade, staggered `navItemIn`, hamburger rotate, desktop dropdown fade/scale, `prefers-reduced-motion`) with a `requestAnimationFrame` open and delayed hide; the mobile drawer dark-mode contrast overrides were added (panel `#1e293b`, active `bg-#3730a3`).
- **Master-data Excel format change:** the 12-column file format became **11 columns**: `root_cause` was removed (chosen by the inspector at NC time; DB `root_cause_id` stays as the suggested value), `item` → `note` (ملاحظة), `deduction` → `deduction_score`. Applied uniformly via the `HasMasterDataColumns` trait to the template, example, and current-data downloads, the `VisitorMasterDataService` header-scan/normalize/validate/confirm pipeline, and the master-data table/preview labels (Note / ملاحظة, Deduction Score / درجة الخصم). Files with the old headers are rejected by design.
- **PhpSpreadsheet 4.x compatibility bug fix:** the installed `phpoffice/phpspreadsheet` vendor is v4 even though `composer.lock` pins 1.30.6; the service still imported `PhpOffice\PhpSpreadsheet\Reader\IOFactory` (moved to `PhpOffice\PhpSpreadsheet\IOFactory` in v4) and called the removed `Worksheet::disconnectWorksheets()`, so every master-data upload failed under the catch-all "Unable to read the Excel file..." at `VisitorMasterDataController.php:100`. Both call sites in `VisitorMasterDataService` were fixed and the dead root-cause lookup removed.
- **Verification:** round-trip probe regenerated the template + current export and re-read them (17 rows validated 17 valid / 0 invalid); full suite **91 tests passed / 479 assertions**; `npm run build` + `php artisan view:clear` rebuilt assets. `memory.md`, `complete_project_specification.md`, and `database_design.md` were synchronized with this update.


## Quality Visits due-date / period-hours model - 2026-09-04

On 2026-09-04 the Quality Visits due-date model moved from a free-text deadline to machine-computed due dates. `visitors_checklist_items` and `visitors_visit_items` replaced `deadline` with `period_hours` (decimal 8,2, nullable; 0 = Immediate; snapshotted at visit start on visit items), and `visitors_capa_actions` gained `period_hours` (decimal 8,2) plus a backend-computed, indexed `due_at` (created_at + period_hours, computed once at creation, NULL when 0) while the legacy `due_date` column remains unused. The new migration `database/migrations/2026_09_04_190000_replace_deadline_with_period_hours.php` drops the `deadline` columns and backfills `period_hours` + `due_at`. The stored CAPA status is only **open | in_progress | closed | rejected**; overdue/completed/closed_late/immediate/upcoming/due_soon are virtual statuses. New `config/visitors.php` (due_soon_hours default 24) and `app/Services/Visitors/DueDateService.php` (`periodToHours` / `dueDate` / `hoursLabel` / `dueStatus`) are the single source of truth, with `VisitorCapaAction::effectiveStatus()`, `dueStatus()`, and `periodLabel()` delegating to it. `CapaService::createFromNonCompliant()` stores `period_hours` + `due_at` in a DB transaction at creation; `VisitService::start()` snapshots `period_hours`. The reports list now exposes per-visit `critical_violations` + `capa_due` attributes and a `due_status` filter (open/due_soon/overdue/immediate/closed/closed_late/completed); `VisitorReportDashboardService` returns dueCards/dueStatusChart/branchDueAnalysis, with `countCapa`/`capaAnalytics` overdue split via `due_at`. Views were updated accordingly: reports show Score Summary due cards + Period/Due Status columns, the reports list shows Critical/Open/Due Soon/Overdue/Immediate/Closed columns, the reports dashboard gained a Corrective Actions/Due Dates section with a dueStatus doughnut (`addChart('dueStatus', ...)` in `resources/js/app.js`), reports print shows Period/Due Date/Due Status, and the master-data preview shows a Period column. Master-data import/export now uses the `period_hours` header (numeric hours, decimals ok, 0 = Immediate; free text rejected on import, legacy `period` fallback accepted at normalize) across `HasMasterDataColumns`, `VisitorCurrentMasterDataExport`, `VisitorMasterTemplateExport`, `ChecklistImportService`, and `VisitorMasterDataService`; `VisitorChecklistSeeder` seeds `period_hours => 48`. Full verification passed with **110 tests / 562 assertions** (was 93 tests), including new `tests/Unit/Visitors/DueDateServiceTest.php` (14 tests) and 3 new `tests/Feature/VisitorReportsTest.php` due-date tests. The three living documents were synchronized with this update.

## Quality Visits violation lifecycle + follow-up - 2026-09-06

The Corrective Actions feature was reworked so inspection reports stay **immutable** while violations follow a real lifecycle with review, and the same open finding is not duplicated on the next inspection. New migration `2026_09_06_000003_extend_visitors_violation_follow_up.php` adds to `visitors_visit_items` the nullable `follow_up_action` (`still_open | resolved | new_violation`) and `linked_capa_action_id` (FK → `visitors_capa_actions`); to `visitors_visit_photos` nullable `capa_action_id` (FK) and `evidence_role` (`initial | resolution`); and to `visitors_capa_actions` nullable `submitted_review_at`, `reviewed_by` (FK → users, `nullOnDelete`), `reviewed_at`, `review_comment`, `reject_reason`. Stored CAPA status is now **open | in_progress | pending_review | closed | rejected** (a rejected action returns to `in_progress`); virtual due statuses are unchanged.

- **Controller/routes:** `app/Http/Controllers/Visitors/VisitorViolationController.php` (replaces the deleted dead `VisitorCapaController`) with `index` (`GET /visitors/violations`, action-status filter), `show` (timeline: initial + resolution evidence photos + `visitors_capa_updates` log), `approve` (`POST visitors/violations/{capaAction}/approve` → closed), `reject` (requires `reject_reason`, → in_progress), `resolve` (`POST visitors/violations/{capaAction}/resolve`, → `pending_review`). Implicit model binding needs the controller param named `VisitorCapaAction $capaAction`.
- **Services:** `CapaService::existingOpenMap()` (same branch + same `checklist_item_id`, action in `open|in_progress|pending_review` on a completed visit) feeds the inspection-page panel; `processViolationsOnSubmit()` (in `VisitService::submit`) routes each NC item by `follow_up_action` — `still_open` appends a `follow_up`-typed `visitors_capa_updates` row to the linked action, `resolved` calls `submitResolution` (evidence-gated), anything else creates a NEW action. `submitResolution` requires the action be `open|in_progress` and Critical violations need a resolution evidence photo; `approve`/`reject` require status `pending_review`. `VisitService::saveItem` persists `follow_up_action`/`linked_capa_action_id` (link cleared on `new_violation`/non-nc).
- **Permissions/seed:** `visit.review` permission + a seeded **Quality Manager** role (`syncPermissions` of the 15 `visit.*` permissions), granted to no user by default; approval/rejection routes are `permission:visit.review`-protected and `@can('visit.review')`-gated in views.
- **Views/lang:** `visitors/show.blade.php` renders an amber "Existing Open Violation" panel (with the three auto-saving follow-up buttons) per matching NC item; `visitors/violations/{index,show}.blade.php` are new; `reports/dashboard.blade.php` cards row is now 7 columns (`xl:grid-cols-7`) with a `pending_review_capa` amber card; `reports/show.blade.php` + `print.blade.php` due-status maps include `pending_review`. ~40 new keys in `lang/en|ar/visitors.php` plus the previously-missing `common.view`/`common.actions`; all `php -l` clean (Arabic added via a safe temp PHP script, no BOM).
- **Resolution review workflow (2026-09-07):** separated permissions `visit.resolution.{submit,review,approve,reject}` replace the coarse `visit.review` gating — Quality Manager holds all four, Customer Support only `submit`. Migration `2026_09_07_000200_add_resolution_review_workflow.php` adds `submitted_by`/`closed_by` (nullable FKs → users, noAction) + `resolution_note` to `visitors_capa_actions`; on approve the reviewer stores `completed_at`/`reviewed_at`/`reviewed_by`/`closed_by` + an `Approved by reviewer.` update. `CapaService::approve()` blocks self-approval (throws `visitors.cannot_self_approve` when `submitted_by === auth()->id()`; NULL-submitted legacy rows are skipped). `VisitorViolationController` gates resolve/approve/reject per-permission; `VisitorVisitPolicy::viewReport` now lets `visit.resolution.review` holders open any completed report. Live-friendly idempotent seeder **`ResolutionWorkflowPermissionSeeder`** (no password reset) for repeat deploys. Violation show/index + report show display `Submitted by`/date and gate Approve-Close/Reject buttons by the exact permission + self-approval guard.
- **Verification:** `tests/Feature/VisitorViolationTest.php` (18 tests, 148 assertions: lifecycle transitions, reviewer-only approve/reject, no-duplicate still_open, new violation after close, critical/mandatory evidence gates, immediate due_at NULL, snapshot immutability, dashboard pending-review count, violations pages/filters). Full suite **136 passed / 765 assertions**; `php artisan migrate --force` + `PermissionSeeder` re-run on live SQL Server (all new columns + `visit.review` + Quality Manager role verified), routes confirmed via `php artisan route:list --name=violations`, `git diff --check` clean.
