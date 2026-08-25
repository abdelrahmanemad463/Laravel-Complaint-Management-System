# Complaint Desk — Persistent Project Memory

**Last synchronized:** 2026-08-26  
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
| Database | SQLite in the current local installation; test suite uses in-memory SQLite |
| Authentication | Laravel session authentication through custom `AuthController` |
| Authorization | Spatie Laravel Permission 6.24.0, Laravel Gate, controller checks, and permission middleware |
| Excel | Maatwebsite Laravel Excel 3.1.67 |
| Localization | Laravel translation dictionaries for English and Arabic plus `SetLocale` middleware |
| PWA | Native `manifest.json`, icons, and static-only service worker |
| Testing | PHPUnit 11 through `php artisan test` |
| Local web server | XAMPP Apache serving the Laravel `public` directory |

Important Composer dependencies are `laravel/framework`, `laravel/tinker`, `spatie/laravel-permission`, and `maatwebsite/excel`. Important NPM dependencies are Vite, `laravel-vite-plugin`, Tailwind CSS, `@tailwindcss/vite`, Axios, and Concurrently. The current picker implementation uses native `fetch()` rather than Axios.

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
- Complaint ID, customer, branch, master-data, creator, and date filtering.
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

The `.env.example` specifies `SESSION_DRIVER=database`, but the current migration set does not include a `sessions` migration. PHPUnit overrides sessions with the array driver. If database sessions are used in a deployment, the required sessions table must be provided or the session driver should be changed.

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
| `app/Providers/AppServiceProvider.php` | Super Admin Gate bypass |
| `app/Http/Middleware/SetLocale.php` | Session locale validation and application locale selection |
| `resources/views/layouts/app.blade.php` | Main shell, navigation, flash alerts, validation errors, PWA metadata |
| `resources/js/app.js` | Customer/branch pickers and service-worker registration |
| `resources/css/app.css` | Tailwind source and shared UI classes |
| `lang/en/` and `lang/ar/` | English/Arabic common, auth, complaint, customer, activity, and validation dictionaries |
| `public/manifest.json` | PWA install metadata |
| `public/service-worker.js` | Static-asset-only cache policy |
| `database/seeders/PermissionSeeder.php` | Permissions, baseline roles, and initial Super Admin |
| `database/seeders/MasterDataSeeder.php` | Example branches and complaint master data |
| `database/seeders/DemoDataSeeder.php` | Demo customers and complaints |
| `tests/Feature/` | Workflow, authorization, filter, export, localization, and PWA regression tests |
| `project_structure.md` | Full developer-oriented architecture and current-state map |
| `database_design.md` | Detailed database design narrative and relationship diagram |

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
| Master data | `/master-data/{type}` and type-specific create/edit/store/update/destroy routes |
| Users | `/users`, create, edit, store, update |
| Roles | `/roles`, `/roles/create`, role edit/update/store/destroy |
| Audit | `/audit-logs` |

Authenticated pages are under the `auth` middleware group. Permission checks are implemented in controllers and, for selected routes, with Spatie middleware aliases.

## Testing and Last Verification

The test suite contains feature coverage for customer search and CRUD, complaint creation/update/status history, filters, export, authorization, role lifecycle, users filters, localization, PWA metadata/cache boundaries, and known Blade regression cases. Unit coverage currently contains the Laravel example unit test; application services are primarily covered through feature tests.

The last complete validation performed after the localization sweep was successful:

- Blade views cleared and cached successfully.
- Vite production build completed successfully.
- Full Laravel suite: **29 tests passed, 117 assertions**.
- Bilingual localization tests passed for complaint/customer/auth messages, login copy, customer status summaries, and audit-log action rendering.
- A static audit checked **143 translation helper keys** against both locale dictionaries and found **0 missing keys**.

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

## Pending Features and Future Improvements

The following are intentionally not implemented and may be considered later: full offline PWA behavior, complaint attachments, internal comments, notifications, email/WhatsApp integration, customer portal, password reset, branch-specific user scoping, SLA/escalation workflows, satisfaction ratings, advanced analytics, external API clients, scheduled synchronization, and background job workflows.

## Known Issues and Operational Notes

- The default Laravel `welcome.blade.php` remains in the repository but is not the application entry page; `/` is the authenticated dashboard.
- The repository has no sessions migration even though `.env.example` names the database session driver; deployment must address this configuration/schema mismatch.
- `public/storage` is not linked in the current local runtime, and no file-upload feature currently depends on it.
- PWA installation requires a supported browser and HTTPS in production. `localhost` is treated as secure by supported browsers.
- Existing historical activity descriptions may have been written in the locale active at the time of the event. Known action identifiers are now translated when displayed, preventing raw keys from appearing.

## Maintenance Rule

After every meaningful code change, verify the final implementation and update this file if the change affects features, schema, routes, permissions, architecture, configuration, tests, deployment, or known limitations. Code is authoritative if this file ever conflicts with the implementation; reconcile the discrepancy instead of preserving stale memory.


## Specification synchronization

`complete_project_specification.md` was reviewed against the current codebase and updated with a **Current Implementation Status** addendum. The original requirements remain preserved as the baseline, while the addendum records verified implementation details and intentional deviations: Blade/vanilla JavaScript rather than Livewire, exact permission assignments, seeded master data, implemented route/workflow coverage, exact 15-column Excel export, actual audit action identifiers, bounded customer/branch searches, bilingual localization, PWA static-only caching, current test/build verification, and deferred features.

The latest specification verification confirmed the document is synchronized with the implemented Laravel 12 system. The current application remains at 29 passing tests with 117 assertions; all current migrations report as ran, and the known `.env.example` database-session-driver versus missing sessions-migration issue is documented rather than hidden.


## Mandatory documentation synchronization

Persistent project instructions now require that every code edit or code change—feature work, bug fixes, refactors, configuration changes, route changes, permission changes, and frontend changes—starts by reading `memory.md`, `complete_project_specification.md`, and `project_structure.md`. The same task must update all three after the change and keep them consistent with the verified codebase. `complete_project_specification.md` remains the requirements and verified implementation-status source, `memory.md` remains continuation context, and `project_structure.md` remains the architecture/developer map. Schema changes additionally require `database_design.md`. Relevant tests and build checks must be run before reporting completion, and verification results must be recorded here.


## Complaint/customer pagination and demo data

The complaints index and customers index now use Laravel `paginate(30)->withQueryString()`. The existing Blade views already render paginator links, totals, and current filters, so no client-side pagination was introduced. The 10-record customer picker and 5-record branch picker limits remain separate and unchanged.

`database/seeders/DemoDataSeeder.php` now creates an idempotent dataset of exactly 100 customers and one complaint per demo customer. It preserves the three named sample customers, generates deterministic additional phone values, distributes complaints across seeded branches/services/sources/categories/types/priorities/statuses, and uses `firstOrCreate` keys so rerunning the seeder does not duplicate the demo records. It requires the seeded master data and initial Super Admin user.

Regression tests verify the 30-record first/second pages and the 100-customer/100-complaint seeded dataset. Final verification completed successfully on 2026-08-26: `php artisan db:seed --force`, Blade view caching, the Vite production build, migration status, and the full test suite all passed. The full suite result is **32 tests passed, 127 assertions**.


The pagination regression expectations account for the seeded 100-customer/100-complaint baseline: when 35 additional records are created, both the first and second pages contain 30 rows. The tests verify the configured page size across multiple pages against a realistic result set.
