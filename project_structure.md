# Complaint Management System — Project Structure

**Status:** Current-state implementation guide; last verified 2026-08-26  
**Application:** Complaint Desk  
**Framework:** Laravel 12 on PHP 8.2+  
**Rendering:** Server-rendered Blade with Tailwind CSS and progressive JavaScript enhancement  
**Supported locales:** English (`en`) and Arabic (`ar`) with RTL layout support  
**Runtime database:** SQLite in the current local installation; Laravel configuration can be changed through environment variables for another supported relational database.

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
| Browser behavior | Plain JavaScript with `fetch()` | Debounced customer and branch searches, multi-branch picker behavior, and PWA registration |
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
│   │   └── ComplaintsExport.php
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
│   │   │   └── UserController.php
│   │   ├── Middleware/
│   │   │   └── SetLocale.php
│   │   └── Requests/
│   │       ├── ComplaintFilterRequest.php
│   │       ├── StoreComplaintRequest.php
│   │       ├── StoreCustomerRequest.php
│   │       ├── UpdateComplaintRequest.php
│   │       └── UpdateCustomerRequest.php
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
│   │   └── User.php
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   └── Services/
│       ├── ActivityLogService.php
│       └── ComplaintStatusService.php
├── bootstrap/
│   └── app.php
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── database.php
│   ├── filesystems.php
│   ├── permission.php
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
│   │   └── validation.php
│   └── en/
│       ├── activity.php
│       ├── auth.php
│       ├── common.php
│       ├── complaints.php
│       ├── customers.php
│       └── validation.php
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
│       └── users/
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

`app/Http/Controllers/` contains the request-facing application actions. `app/Models/` contains Eloquent entities and relationships. `app/Services/` contains the two focused cross-cutting business services. `database/` contains schema, factories, and seed data. `resources/views/` contains all Blade UI. `lang/` contains the bilingual dictionaries. `public/` contains the PWA and compiled public assets.

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
| `complaint_types` | Complaint-type master data | Same common master-data shape; soft deletes |
| `priorities` | Complaint priority master data | Required `color`, nullable `level`, indexed `is_active`, `sort_order`; soft deletes |
| `complaint_statuses` | Complaint status master data | Required `color`, indexed `is_active`, `sort_order`; soft deletes |
| `complaints` | Main complaint record | Required foreign keys to customer, branch, service, source, category, type, priority, status; required descriptions/date/creator; nullable resolver, resolved time, resolution; soft deletes |
| `complaint_status_histories` | Append-only status transitions | Complaint, optional previous status, new status, reason, actor, and `changed_at`; restrictive complaint/status/user foreign keys |
| `activity_logs` | Audit trail | Nullable actor, stable action string, nullable polymorphic subject, description, JSON old/new snapshots, indexed action/date |

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
```

The permission migration creates package roles, permissions, model-role/model-permission pivots, and role-permission pivots. The master-data migration creates customers, branches, services, sources, categories, types, priorities, and statuses. The complaints migration depends on those tables and creates complaint foreign keys plus branch/date and status/date composite indexes. The final migration creates status history and polymorphic activity logs.

`DatabaseSeeder` runs `PermissionSeeder`, `MasterDataSeeder`, and `DemoDataSeeder` in that order. Future schema changes should add a new migration rather than editing an already-applied migration and should update this document when they affect project structure or data design.

## 10. Main Features and Modules

| Module | Main entry points | Main data and rules |
|---|---|---|
| Authentication | `AuthController`, `auth/login.blade.php` | Session login/logout; failed credentials use localized messages |
| Dashboard | `DashboardController`, `dashboard/index.blade.php` | Aggregate seven-day counts, status/priority distributions, and branch ranking |
| Customers | `CustomerController`, `Customer` model, `customers/` views | CRUD, four-phone/name search, complaint count/history, 30-record pagination, soft deletion model support; primary phone required |
| Complaints | `ComplaintController`, `Complaint` model, `complaints/` views | CRUD, customer and master-data associations, filters, detail page, status transitions, resolution metadata, activity timeline, and 30-record pagination |
| Complaint filtering | `ComplaintFilterRequest`, `Complaint::scopeFilter()` | Complaint ID, validated short/full description text search, customer, multi-branch, master-data, creator, and date filters; query strings retained in pagination |
| Master data | `MasterDataController`, `master-data/` views | Branches, services, sources, categories, types, priorities, and statuses; active flag controls new selections; records are ordered and paginated |
| Branch reports | `ReportController`, `reports/branches.blade.php` | Date range and multi-branch filtering; SQL grouping by complaint date and branch |
| Excel export | `ComplaintsExport`, `ComplaintController::export()` | Exports the validated current complaint query and localized headings; permission protected |
| Users | `UserController`, `users/` views | Authorized user creation/update, role assignment, name/email and role filters, paginated results |
| Roles and permissions | `RoleController`, `roles/` views | Create custom roles, configure permissions, guarded deletion, protected baseline roles, assigned-user deletion prevention |
| Audit logs | `AuditLogController`, `audit-logs/index.blade.php` | Permission-restricted, action-filtered, paginated audit list; known actions are localized at display time |
| Localization | `SetLocale`, `LocaleController`, `lang/en/`, `lang/ar/` | Session locale selection, RTL Arabic layout, localized UI, validation, flash messages, activity labels, and exports |
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

Permission names use dot notation. The seeder creates systematic CRUD permissions for customers, complaints, branches, services, sources, categories, types, priorities, statuses, reports, users, roles, and audit logs, plus complaint log/export and report export permissions.

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
| Framework | `/up`, `/storage/{path}` | Laravel health/storage routes |

`bootstrap/app.php` registers the web routes, the `permission` and `role` middleware aliases, and appends `SetLocale` to the web middleware group.

## 14. Frontend Architecture

The frontend is Blade plus Tailwind CSS. `resources/views/layouts/app.blade.php` provides the authenticated shell, navigation, flash alerts, validation-error display, language direction, PWA metadata, and Vite asset loading. Individual modules use page views and a small number of form/list templates; they do not use a client-side router.

`resources/css/app.css` defines the application’s reusable visual classes, including cards, buttons, form controls, tables, badges, navigation states, and focus/hover/press behavior. `resources/js/app.js` contains:

1. Customer-picker initialization, 10-result rendering, debounced `fetch()` search, abort handling, and selected-record preservation.
2. Branch-picker initialization, five-result rendering, multi-selection, hidden `branch_ids[]` fields, debounced search, abort handling, and selected-record preservation.
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

The current local `.env` uses SQLite, database sessions/cache/queues according to the runtime configuration, and a local application URL. Tests override the database with in-memory SQLite, cache with the array store, mail with the array mailer, queue with synchronous execution, and session with the array store in `phpunit.xml`.

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

Current feature coverage includes customer phone/name search, customer and complaint workflows, authenticated complaint creator assignment, complaint filters including complaint ID, short/full description search, and multiple branches, complaint status history and resolution metadata, authorization denial, role lifecycle safeguards, users filters, branch reports, localized Excel headings, description-filtered export reuse, English/Arabic localization, PWA manifest/service-worker boundaries, and Blade/runtime regression cases. Tests are feature-focused; the two application services do not currently have separate unit-test classes.

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
- The current local runtime uses SQLite and XAMPP Apache; production database, mail, storage, cache, and queue infrastructure must be configured separately.
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

Feature tests verify that the complaints and customers index pages each render 30 records on both the first and second pages when more than 100 records exist. The test setup uses the seeded demo dataset, ensuring the paginator is exercised with a realistic larger result set. The latest full suite passed with 38 tests and 171 assertions; Blade caching, the Vite production build, migration status, and `php artisan db:seed --force` also completed successfully.


### Dark-mode theme system

The shared layout provides a localized light/dark switch with accessible button state. `resources/js/app.js` initializes the selected theme, persists it in `localStorage` under `complaint-theme`, updates the document color scheme, and changes the PWA `theme-color` metadata. `resources/css/app.css` defines dark-mode overrides under `html[data-theme='dark']` for shared surfaces, typography, form controls, navigation, alerts, tables, buttons, badges, picker result panels, and direct light-palette utility classes used by module views. Explicit `.card`, `.form-input`, `.form-label`, `.page-title`, `.section-title`, `.page-subtitle`, `.nav-link`, and `.btn-secondary` overrides provide a consistent brighter dark-only palette with clearer borders, hover states, and focus states while leaving light mode unchanged. Report filters and all other server-rendered forms therefore use readable dark surfaces rather than remaining white or low-contrast. The focused dark-mode layout/form/contrast regression tests passed; the final full suite passed after this correction with 34 tests and 144 assertions, alongside successful Blade caching, Vite production build, and route verification.


### Branch report pagination

`ReportController::branches()` now paginates grouped complaint-date/branch report rows at 30 per page and calls `withQueryString()` so date and multi-branch filters persist across pages. `reports/branches.blade.php` displays the localized filtered total and renders Laravel paginator links below the report table. The existing five-result branch picker and protected background search remain unchanged. The focused branch-report suite passed with 17 tests and 59 assertions. Full validation then passed with 35 tests and 152 assertions; Blade views compiled and cached, the Vite production build completed, all migrations reported Ran, and 42 routes were registered.


### Complaint export columns and timeline mapping

`app/Exports/ComplaintsExport.php` now maps 17 columns. In addition to the existing complaint fields, it exports `short_description` and a localized `Timeline` column. The export eager-loads the complaint status histories, related statuses and actors, and activity-log users. It combines both event collections, sorts them by event timestamp, and writes newline-separated readable lines into one Excel cell. Status transitions include the previous/new status and optional reason; general activity events use the bilingual activity dictionary. Focused export tests verify the two new headings, mapped timeline content, and description-filter reuse. Full validation passed with 38 tests and 171 assertions; Blade views compiled and cached, the Vite production build completed, all migrations reported Ran, and 42 routes were registered.


### Complaint description search

The complaints filter form has a normal GET search field named `description` at the end of the filter grid, localized in English and Arabic as “Search short or full description” / `البحث في الوصف المختصر أو الكامل`. `ComplaintFilterRequest` validates it as optional text with a 255-character maximum. `Complaint::scopeFilter()` trims the term and applies one grouped `LIKE` predicate against `short_description` or `description`; the same validated filters flow into pagination and `ComplaintsExport` without a separate export query.

Feature regression coverage verifies short-only and full-only matches exclude unrelated complaints, and export coverage verifies the shared query scope honors the same description term. No schema or route change was required.

### Final verification for complaint description search

The focused complaint-filter suite passed with 18 tests and 67 assertions, and the focused export suite passed with 4 tests and 15 assertions. Full verification passed with 38 tests and 171 assertions; Blade views compiled and cached, the Vite production build completed, all migrations reported `Ran`, and `php artisan route:list` reported 42 routes.

### Maintenance note

This document is synchronized with the current codebase as of 2026-08-26. The three living project documents must be read before every future code edit and synchronized after each meaningful change; schema changes additionally require `database_design.md` updates.

