# Laravel Complaint Management System — Complete Project Specification

## 1. Role

Act as a **Senior Laravel Software Engineer and System Architect**.

You will design and develop a complete **Complaint Management System / Customer Support System** using Laravel.

The goal is to build a professional, maintainable, simple, and easy-to-understand system.

Do NOT over-engineer the project.

The code should be:

* Clean
* Simple
* Maintainable
* Easy to understand
* Easy to modify
* Consistent with Laravel conventions
* Secure
* Scalable enough for a normal business application
* Easy for a junior/mid-level Laravel developer to continue working on

Avoid unnecessary design patterns, excessive abstractions, complicated services, unnecessary repositories, unnecessary interfaces, and over-engineering.

Prefer **simple Laravel conventions** unless there is a clear reason to introduce additional architecture.

---

# 2. Technology Stack

Use:

* Laravel
* PHP
* Blade
* Livewire if needed
* Tailwind CSS
* MySQL or the project's configured relational database
* Laravel Eloquent ORM
* Laravel Migrations
* Laravel Policies / Gates
* Spatie Laravel Permission for Roles & Permissions
* Laravel Excel / Maatwebsite Excel for Excel exports

The UI should be built primarily using:

* Blade
* Livewire where interactive behavior is useful
* Tailwind CSS

Do NOT introduce React, Vue, Inertia, or another frontend framework unless explicitly requested.

The application should remain a normal Laravel application and should be easy to understand.

---

# 3. Main Goal

Build a web-based system for managing customer complaints.

The system should allow customer support employees to:

1. Search for an existing customer.
2. Create a customer if the customer does not exist.
3. Create complaints for customers.
4. Track complaints.
5. Assign complaints to branches.
6. Categorize complaints.
7. Track complaint status.
8. Track priority.
9. Track service type.
10. Track complaint source/category.
11. Record how complaints were solved.
12. Keep an activity/audit history.
13. Filter complaints.
14. Generate reports.
15. Export filtered results to Excel.
16. View dashboards and statistics.
17. Manage users, roles, and permissions.

---

# 4. Important Design Principle

The system should NOT be unnecessarily complicated.

Prefer:

```text
Laravel Models
Laravel Controllers
Laravel Form Requests
Laravel Policies
Blade / Livewire
Eloquent Relationships
Migrations
Simple Service Classes only when genuinely useful
```

Do not create:

* Repository pattern everywhere
* Generic CRUD abstractions
* Excessive interfaces
* Excessive DTOs
* Complex domain layers
* Complex event-driven architecture
* Unnecessary microservices
* Unnecessary design patterns

The project should be understandable by someone familiar with Laravel.

---

# 5. Customer Management

A customer can have multiple complaints.

Relationship:

```text
Customer 1 ──────── N Complaints
```

Customer fields should include at minimum:

```text
id
name
phone_primary
phone_2
phone_3
phone_4
address
created_at
updated_at
deleted_at
```

## Important Phone Requirement

The customer MUST have:

```text
phone_primary
```

This is required.

The customer may optionally have:

```text
phone_2
phone_3
phone_4
```

So one customer can have up to 4 phone numbers.

Example:

```text
Primary Phone:
01012345678

Phone 2:
01112345678

Phone 3:
01212345678

Phone 4:
01512345678
```

Only the primary phone is mandatory.

The other three are optional.

---

# 6. Customer Phone Search

Customer support should be able to search for a customer using ANY of the four phone numbers.

For example, if the customer has:

```text
phone_primary = 01012345678
phone_2       = 01112345678
phone_3       = NULL
phone_4       = NULL
```

Searching for:

```text
01112345678
```

must return the same customer.

The search should conceptually behave like:

```text
phone_primary = search
OR phone_2 = search
OR phone_3 = search
OR phone_4 = search
```

The phone search should be simple, fast, and easy to understand.

Consider appropriate database indexes where useful.

---

# 7. Customer Workflow

The main customer support workflow should be:

```text
Customer Support
        |
        v
Search Customer by Phone
        |
        +----------------------+
        |                      |
      Found                Not Found
        |                      |
        v                      v
Open Customer            Create Customer
        |                      |
        +----------+-----------+
                   |
                   v
             Add Complaint
                   |
                   v
          Fill Complaint Details
                   |
                   v
                 Save
```

On the customer details page there should be an obvious:

```text
Add Complaint
```

button.

---

# 8. Customer Page

The customer page should show:

```text
Customer Information

Name
Primary Phone
Phone 2
Phone 3
Phone 4
Address

Total Complaints
Pending Complaints
In Progress Complaints
Solved Complaints
Closed Complaints

[ Add Complaint ]
```

Then show the customer's complaint history.

Example:

```text
Complaint #125
Status: Solved
Priority: High
Branch: Branch A
Date: 2026-08-20
```

The user should be able to open the complaint details.

---

# 9. Complaint

Each customer can have many complaints.

Complaint fields should include approximately:

```text
id
customer_id
branch_id
service_id
source_id
category_id
type_id
priority_id
status_id

short_description
description

complaint_date

created_by

resolved_by
resolved_at
resolution

created_at
updated_at
deleted_at
```

Use foreign keys and Eloquent relationships properly.

---

# 10. Complaint Description

Every complaint should have:

### Short Description

A small summary.

Example:

```text
Wrong order received
```

### Full Description

A larger text field containing the complete complaint.

Example:

```text
Customer ordered two meals but received a different order.
Customer contacted support and requested a replacement.
```

---

# 11. Complaint Date

The complaint should have:

```text
complaint_date
```

This represents when the complaint happened / was submitted.

Do not rely only on `created_at`.

Both should exist:

```text
complaint_date
created_at
```

---

# 12. Created By

When a user creates a complaint, the system should automatically save the currently authenticated user.

Do NOT ask the user to manually select who created the complaint.

Use the authenticated user conceptually:

```php
auth()->id()
```

The complaint should contain:

```text
created_by
```

which references the users table.

---

# 13. Branches

Complaints belong to a branch.

Create a dynamic branches management module.

Suggested fields:

```text
id
name
code
is_active
created_at
updated_at
deleted_at
```

Admin should be able to:

* Create branch
* Edit branch
* Activate/deactivate branch
* Delete/soft-delete branch
* View branches

Complaints must be linked to a branch.

---

# 14. Services

Complaint service should be dynamic.

Examples:

```text
Take Away
Dine In
Delivery
```

Create:

```text
services
```

Suggested fields:

```text
id
name
color
is_active
sort_order
created_at
updated_at
deleted_at
```

Admin can:

* Create
* Edit
* Activate/deactivate
* Delete

---

# 15. Complaint Source

Create a dynamic Complaint Source module.

Examples:

```text
WhatsApp
Phone
Facebook
In Person
Website
Email
```

Suggested table:

```text
complaint_sources
```

Suggested fields:

```text
id
name
color
is_active
sort_order
created_at
updated_at
deleted_at
```

Admin can manage these values.

---

# 16. Complaint Category

Create a separate dynamic Complaint Category module.

Examples:

```text
Food Quality
Customer Service
Staff
Delivery
Payment
Order
```

Suggested table:

```text
complaint_categories
```

Fields:

```text
id
name
color
is_active
sort_order
created_at
updated_at
deleted_at
```

Admin can manage categories.

IMPORTANT:

Keep:

```text
Source
```

and:

```text
Category
```

as separate concepts.

Example:

```text
Source:
WhatsApp

Category:
Customer Service
```

---

# 17. Complaint Type

Create a dynamic Complaint Type module.

Examples:

```text
Wrong Order
Missing Item
Late Delivery
Bad Treatment
Wrong Price
Food Quality
```

Suggested table:

```text
complaint_types
```

Fields:

```text
id
name
color
is_active
sort_order
created_at
updated_at
deleted_at
```

Admin can manage these values.

---

# 18. Priority

Create dynamic priorities.

Default values:

```text
Low
Medium
High
Critical
```

Suggested fields:

```text
id
name
color
level
is_active
sort_order
created_at
updated_at
deleted_at
```

Every priority should have a color.

The UI should visually distinguish priorities.

Example:

```text
Low      → green
Medium   → yellow
High     → orange
Critical → red
```

Do not hard-code the colors everywhere.

Store the color in the database and render it consistently.

---

# 19. Status

Create dynamic complaint statuses.

Default examples:

```text
Pending
In Progress
Solved
Closed
Reopened
```

Suggested table:

```text
complaint_statuses
```

Fields:

```text
id
name
color
is_active
sort_order
created_at
updated_at
deleted_at
```

Admin should be able to manage statuses.

Statuses should have colors.

---

# 20. Complaint Resolution

When a complaint becomes solved, the user should be able to enter how it was solved.

Fields:

```text
resolution
resolved_by
resolved_at
```

Example:

```text
Status:
Solved

Resolution:
Customer was contacted and a replacement order was provided.
```

The user who resolves the complaint should be automatically recorded.

Do not make `resolved_by` manually selectable unless there is a clear administrative reason.

---

# 21. Status History

Create a complaint status history table.

Suggested:

```text
complaint_status_histories
```

Fields:

```text
id
complaint_id
from_status_id
to_status_id
reason
changed_by
changed_at
created_at
updated_at
```

Whenever the complaint status changes, record:

* Previous status
* New status
* Reason
* User who changed it
* Date/time

Example:

```text
Pending
    ↓
In Progress

Reason:
Complaint assigned to branch manager.

Changed by:
Ahmed

    ↓

Solved

Reason:
Customer was contacted and replacement was provided.
```

This history must not be lost.

---

# 22. Audit / Activity Logs

The system should have an activity/audit log.

Administrators should be able to see important actions such as:

* Complaint created
* Complaint updated
* Complaint deleted
* Status changed
* Priority changed
* Branch changed
* Service changed
* Category changed
* Type changed
* Resolution added/updated
* Customer updated
* Important system actions

Suggested structure:

```text
activity_logs
```

Fields approximately:

```text
id
user_id
action
subject_type
subject_id
description
old_values
new_values
created_at
```

For old/new values, use JSON where appropriate.

Example:

```text
User:
Ahmed

Action:
Updated Complaint #125

Changes:

Priority:
Medium → High

Status:
Pending → In Progress
```

The audit logs should be accessible only to users with the appropriate permission.

---

# 23. Complaint Timeline

The complaint details page should provide a simple timeline.

Example:

```text
Complaint #125

23 Aug 10:15
Ahmed created the complaint.

23 Aug 10:30
Priority changed:
Medium → High

23 Aug 11:00
Status changed:
Pending → In Progress

23 Aug 14:20
Status changed:
In Progress → Solved

Resolution:
Customer was contacted and replacement provided.
```

Keep the implementation simple and readable.

---

# 24. Roles

The system should support roles.

At minimum:

```text
Super Admin
Admin
Customer Support
Viewer
```

Use:

```text
Spatie Laravel Permission
```

Do not build a custom permission system if Spatie Laravel Permission is available.

---

# 25. Super Admin

Super Admin has unrestricted access.

Super Admin should always have all permissions.

IMPORTANT:

No normal Admin or another user should be able to remove Super Admin's full access.

Super Admin should be protected from accidental permission removal.

The application should enforce this at the authorization/business-logic level, not only by hiding UI buttons.

---

# 26. Admin

Admin should have broad access.

However, Admin permissions should be configurable by Super Admin.

Example permissions:

```text
customer.view
customer.create
customer.update
customer.delete

complaint.view
complaint.create
complaint.update
complaint.delete
complaint.view_logs
complaint.export

branch.view
branch.create
branch.update
branch.delete

service.view
service.create
service.update
service.delete

category.view
category.create
category.update
category.delete

source.view
source.create
source.update
source.delete

type.view
type.create
type.update
type.delete

priority.view
priority.create
priority.update
priority.delete

status.view
status.create
status.update
status.delete

report.view
report.export

user.view
user.create
user.update
user.delete

role.view
role.create
role.update
role.delete
```

Use Laravel authorization / Spatie permissions correctly.

---

# 27. Permission Naming

Use consistent permission names.

Prefer:

```text
customer.view
customer.create
customer.update
customer.delete
```

instead of inconsistent names.

Create permissions systematically for all modules.

---

# 28. Customer Support Permissions

Customer Support should typically be able to:

```text
View customers
Create customers
Update customers
Create complaints
View complaints
Update complaints
```

But should not automatically have access to:

```text
Users
Roles
Permissions
System configuration
Audit logs
```

unless explicitly granted.

---

# 29. Complaint Filters

Complaint listing should support filters.

At minimum:

```text
Customer
Branch
Service
Source
Category
Type
Priority
Status
Created By

Date From
Date To
```

Branch filter MUST support selecting multiple branches.

Example:

```text
[x] Branch A
[x] Branch B
[ ] Branch C
[x] Branch D
```

The result should include complaints belonging to any selected branch.

---

# 30. Result Count

Every filtered listing should clearly show the number of results.

Example:

```text
Results: 247 complaints
```

If filtering by branch:

```text
Branch A
Results: 84 complaints
```

If selecting multiple branches:

```text
Branch A + Branch B
Results: 132 complaints
```

The result count must respect the current filters.

---

# 31. Customer Filtering

There should be a customer filter on the complaints page.

Example:

```text
Customer:
Ahmed Mohamed
```

This should show all complaints belonging to that customer.

---

# 32. Customer Summary

The Customers page should support customer filtering/search.

When a customer is selected, show summary information such as:

```text
Customer:
Ahmed Mohamed

Total Complaints:
18
```

Provide a button:

```text
View Complaints
```

which takes the user to the complaint listing filtered by that customer.

Conceptually:

```text
/complaints?customer=123
```

Use Laravel route/query conventions appropriately.

---

# 33. Dashboard

Create a dashboard containing useful complaint statistics.

At minimum:

## Complaints in Last 7 Days

Show:

```text
Monday
Tuesday
Wednesday
Thursday
Friday
Saturday
Sunday
```

with complaint counts.

## Most Complained Branches

Example:

```text
Branch A     142
Branch B     119
Branch C      87
```

## Complaint Status Distribution

Example:

```text
Pending       45
In Progress   23
Solved        210
Closed         34
```

## Priority Distribution

Example:

```text
Low       120
Medium     80
High       35
Critical    5
```

Keep dashboard implementation simple.

Use lightweight charts only where useful.

---

# 34. Branch Reports

Create a report page for branch complaint statistics.

Filters:

```text
Date From
Date To
Multiple Branches
```

Example result:

```text
Date        Branch       Complaints
-----------------------------------
2026-08-20  Branch A     15
2026-08-20  Branch B      9
2026-08-21  Branch A     22
2026-08-21  Branch B     13
```

The report should allow the user to understand how many complaints each branch received per day.

---

# 35. General Filtering Architecture

Filtering should be reusable and clean.

Do not duplicate huge amounts of filtering logic across controllers.

Create simple reusable query/filter logic where appropriate.

However, do not create an over-engineered generic filtering framework.

Laravel Eloquent query scopes or a small dedicated filter class are acceptable if they keep the code readable.

---

# 36. Excel Export

Use Laravel Excel / Maatwebsite Excel.

The export MUST respect the currently applied filters.

Example:

```text
Branch = Branch A
Status = Solved
Date From = 2026-08-01
Date To = 2026-08-23
```

When clicking:

```text
Export Excel
```

only the filtered complaints should be exported.

Never export all complaints when filters are active.

---

# 37. Excel Columns

The Excel export should include useful complaint information.

Suggested columns:

```text
Complaint ID
Customer Name
Primary Phone
Phone 2
Phone 3
Phone 4
Customer Address

Branch

Service
Source
Category
Complaint Type

Short Description
Description

Priority
Status

Complaint Date

Created By

Resolved By
Resolved At
Resolution

Created At
Updated At
```

You may adjust the columns if there is a better practical structure.

---

# 38. Excel Localization

The application supports:

```text
Arabic
English
```

The Excel export must respect the current application language.

If the application language is Arabic:

```text
رقم الشكوى
اسم العميل
رقم الهاتف
الفرع
الخدمة
المصدر
التصنيف
نوع الشكوى
الأولوية
الحالة
تاريخ الشكوى
الحل
...
```

If the application language is English:

```text
Complaint ID
Customer Name
Phone
Branch
Service
Source
Category
Complaint Type
Priority
Status
Complaint Date
Resolution
...
```

Do not duplicate export classes unnecessarily.

Prefer a clean localization approach.

---

# 39. Multi-language

The application should support:

```text
Arabic
English
```

Use Laravel localization properly.

Do not hard-code user-facing text throughout Blade files.

Use translation files such as:

```text
lang/en/
lang/ar/
```

or the appropriate Laravel localization structure for the project's Laravel version.

The UI should support RTL when Arabic is selected.

The layout should switch appropriately between:

```text
LTR
RTL
```

---

# 40. Master Data

The following should be dynamic and manageable by authorized administrators:

```text
Branches
Services
Sources
Categories
Complaint Types
Priorities
Statuses
```

Do not hard-code these values in Blade templates.

---

# 41. Soft Deletes

Use soft deletes where appropriate.

Especially for:

```text
Customers
Branches
Services
Sources
Categories
Complaint Types
Priorities
Statuses
Complaints
```

Do not allow deleting important historical data in a way that breaks old complaints.

For master data, consider deactivation when appropriate.

Example:

```text
is_active = false
```

means the item cannot be selected for new complaints but remains available for historical complaints.

---

# 42. Historical Data

Old complaints must remain understandable even if master data changes.

Do not break old complaint relationships.

For example, if a branch is deactivated:

```text
Branch A
```

old complaints linked to Branch A must remain accessible.

Do not physically remove referenced master data in a way that destroys historical reports.

Use foreign keys, soft deletes, and active/inactive status appropriately.

---

# 43. Complaint Editing

Complaints should be editable by authorized users.

Editable information may include:

```text
Customer
Branch
Service
Source
Category
Type
Priority
Status
Short Description
Description
Resolution
```

Important changes should be recorded in activity logs.

---

# 44. Validation

Use Laravel Form Requests or appropriate validation.

Examples:

Customer:

```text
name        → required
phone_primary → required
phone_2     → nullable
phone_3     → nullable
phone_4     → nullable
address     → nullable
```

Complaint:

```text
customer_id
branch_id
service_id
source_id
category_id
type_id
priority_id
status_id
short_description
description
complaint_date
```

Validate all foreign keys using appropriate Laravel validation rules.

Do not trust IDs coming from the frontend.

---

# 45. Security

Follow Laravel security best practices.

Implement:

* Authentication
* Authorization
* Policies / Gates
* Spatie Permissions
* CSRF protection
* Validation
* Mass assignment protection
* Proper database constraints
* Secure file handling if attachments are implemented
* Authorization checks on every sensitive operation

Never rely only on hiding buttons in the UI.

---

# 46. Database Constraints

Use proper foreign keys.

Example:

```text
complaints.customer_id
complaints.branch_id
complaints.service_id
complaints.source_id
complaints.category_id
complaints.type_id
complaints.priority_id
complaints.status_id
complaints.created_by
```

Choose appropriate `onDelete` behavior.

Do NOT blindly use cascade deletes for historical/business data.

Think carefully about preserving complaints and audit history.

---

# 47. Indexing

Add useful database indexes.

Especially consider indexes for:

```text
customers.phone_primary
customers.phone_2
customers.phone_3
customers.phone_4

complaints.customer_id
complaints.branch_id
complaints.service_id
complaints.source_id
complaints.category_id
complaints.type_id
complaints.priority_id
complaints.status_id
complaints.complaint_date
complaints.created_by
```

Do not add indexes blindly.

Only add indexes that have practical query/reporting value.

---

# 48. UI / UX

The interface should be:

* Clean
* Simple
* Professional
* Responsive
* Easy to navigate
* Easy to understand
* Easy to modify

Use Tailwind CSS.

Avoid excessive animations.

Avoid unnecessarily complicated UI components.

Use consistent:

* Buttons
* Forms
* Tables
* Badges
* Modals
* Alerts
* Pagination
* Filters

---

# 49. Complaint Listing

The complaints page should contain:

```text
Filters

Results: 247

[ Apply Filters ]
[ Reset ]
[ Export Excel ]

Complaint Table
```

The table should show useful information such as:

```text
ID
Customer
Branch
Service
Category
Type
Priority
Status
Complaint Date
Created By
Actions
```

Use badges for:

```text
Priority
Status
Service
```

when useful.

---

# 50. Pagination

Complaint lists can become large.

Use Laravel pagination.

Do not load thousands of complaints into memory unnecessarily.

Use efficient Eloquent queries.

---

# 51. Search

Customer search should be optimized for the main support workflow.

The support employee should be able to quickly enter a phone number and find the customer.

Support:

```text
Primary Phone
Phone 2
Phone 3
Phone 4
```

Optionally allow customer name search as well.

---

# 52. Code Style

Follow Laravel conventions.

Use:

```text
Models
Controllers
Form Requests
Policies
Livewire Components
Blade Components
Migrations
Seeders
Factories
Exports
Notifications
```

only when useful.

Keep classes small and understandable.

Do not create abstractions simply to make the architecture look "enterprise".

---

# 53. Project Structure

Before implementing the system, design a clear project structure.

Prefer something conceptually similar to:

```text
app/
├── Actions/
├── Exports/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Middleware/
├── Livewire/
├── Models/
├── Policies/
├── Services/
└── ...

database/
├── factories/
├── migrations/
└── seeders/

resources/
├── css/
├── js/
└── views/
    ├── layouts/
    ├── components/
    ├── dashboard/
    ├── customers/
    ├── complaints/
    ├── branches/
    ├── services/
    ├── sources/
    ├── categories/
    ├── types/
    ├── priorities/
    ├── statuses/
    ├── users/
    ├── roles/
    └── reports/

lang/
├── ar/
└── en/

routes/
├── web.php
└── ...
```

You may improve this structure if you have a clear reason, but keep it simple.

---

# 54. Documentation Before Coding

IMPORTANT:

Do NOT immediately start generating the entire project code.

First create a high-level project design.

Create:

```text
project_structure.md
```

It must document:

1. Project overview
2. Technology stack
3. Architecture
4. Modules
5. Folder structure
6. Database tables
7. Database relationships
8. Complaint workflow
9. Customer workflow
10. Roles
11. Permissions
12. Filtering system
13. Reporting system
14. Excel export system
15. Localization
16. Audit logs
17. Security considerations
18. Future improvements

Also create:

```text
database_design.md
```

containing:

* Tables
* Columns
* Data types
* Foreign keys
* Relationships
* Indexes
* Soft deletes
* Important constraints
* Explanation of why each table exists

If useful, include a Mermaid ER diagram.

---

# 55. Documentation Must Stay Updated

VERY IMPORTANT:

During development, if you change:

* Database structure
* Table
* Column
* Relationship
* Module
* Folder structure
* Architecture
* Permission
* Workflow
* Business rule

you MUST update the relevant documentation.

At minimum:

```text
project_structure.md
database_design.md
```

must always reflect the actual implementation.

Do not let the documentation become outdated.

At the end of every significant architectural/database change, update the documentation.

---

# 56. Development Process

Use this order:

## Phase 1

Analyze requirements and identify ambiguities.

Do not invent business rules silently.

If something is unclear and materially affects the database or architecture, ask before implementing.

Otherwise choose the simplest reasonable Laravel approach and document the assumption.

## Phase 2

Create:

```text
project_structure.md
database_design.md
```

## Phase 3

Create database migrations.

## Phase 4

Create models and relationships.

## Phase 5

Create seeders/factories.

Create realistic demo data for:

* Users
* Roles
* Permissions
* Branches
* Services
* Sources
* Categories
* Complaint Types
* Priorities
* Statuses
* Customers
* Complaints

## Phase 6

Authentication and authorization.

## Phase 7

Customer module.

## Phase 8

Complaint module.

## Phase 9

Status history and activity logs.

## Phase 10

Dashboard and reports.

## Phase 11

Excel export.

## Phase 12

Arabic/English localization and RTL.

## Phase 13

Testing and cleanup.

---

# 57. Testing

Write appropriate tests for important business logic.

At minimum test:

### Customer

* Create customer
* Required primary phone
* Optional additional phones
* Search using primary phone
* Search using phone 2
* Search using phone 3
* Search using phone 4

### Complaint

* Create complaint
* Complaint belongs to customer
* Complaint belongs to branch
* Complaint automatically stores created_by
* Update complaint
* Change status
* Store status history
* Store resolution when solved

### Permissions

* Super Admin access
* Admin permissions
* Customer Support permissions
* Unauthorized users cannot perform protected actions

### Filters

* Customer filter
* Branch filter
* Multiple branch filter
* Date range
* Status
* Priority
* Combined filters

### Export

* Export respects filters
* Arabic headers
* English headers

---

# 58. Error Handling

Use normal Laravel validation and error handling.

Do not expose sensitive internal errors to normal users.

Show useful user-friendly messages.

Example:

```text
Complaint created successfully.
Complaint updated successfully.
Status changed successfully.
Customer created successfully.
```

---

# 59. Performance

The system should remain efficient for a normal business database.

Use:

* Pagination
* Eager loading where appropriate
* Proper indexes
* Efficient queries
* Avoid N+1 queries
* Avoid loading unnecessary columns
* Avoid loading huge datasets into memory during exports

For Excel exports, use appropriate Laravel Excel techniques if the dataset becomes large.

---

# 60. Avoid Overengineering

This is VERY important.

Do NOT turn this into an enterprise architecture unnecessarily.

The desired architecture is:

```text
Simple
Clean
Readable
Maintainable
Laravel-native
```

If a problem can be solved with a simple Eloquent query, do not create five classes for it.

If a controller becomes too large, extract only the logic that genuinely deserves extraction.

If a simple Blade component is enough, do not create a complex frontend abstraction.

---

# 61. Future Features

Keep the architecture open enough for future additions such as:

```text
Complaint Attachments
Internal Comments
Notifications
Email Notifications
WhatsApp Integration
Customer Satisfaction Rating
SLA Tracking
Escalation
Advanced Analytics
Branch Manager Dashboard
Customer Portal
API
Mobile Application
```

Do NOT implement these unless explicitly requested.

Only make sure the current design does not make them unnecessarily difficult later.

---

# 62. Important Business Rules Summary

The final system must follow these rules:

```text
1. One customer can have many complaints.

2. Customer has one required primary phone.

3. Customer can have up to three additional optional phones.

4. Searching using ANY of the four phones must find the customer.

5. Complaint creator is automatically the authenticated user.

6. Complaint belongs to a customer.

7. Complaint belongs to a branch.

8. Complaint has a service.

9. Complaint has a source.

10. Complaint has a category.

11. Complaint has a type.

12. Complaint has a priority.

13. Complaint has a status.

14. Status changes must be logged.

15. Solved complaints can store resolution information.

16. Important complaint changes must be logged.

17. Admin can manage dynamic master data.

18. Super Admin always has full access.

19. Super Admin cannot have permissions removed by normal admins.

20. Permissions are granular.

21. Complaint listing supports filters.

22. Branch filter supports multiple branches.

23. Filtered result count must be displayed.

24. Excel export must respect filters.

25. Excel headers must follow current language.

26. System supports Arabic and English.

27. Arabic UI must support RTL.

28. Historical complaints must remain safe when master data is deactivated.

29. Use soft deletes where appropriate.

30. Keep the architecture simple and Laravel-native.
```

---

# 63. Expected Output Before Coding

Before writing implementation code, provide:

## A. Architecture Overview

Explain:

```text
How the system works
Main modules
Main relationships
Request flow
Authorization flow
Complaint lifecycle
```

## B. Database Design

Provide:

```text
All tables
All important columns
Relationships
Indexes
Foreign keys
Soft deletes
```

## C. ER Diagram

Prefer Mermaid if possible.

## D. Project Structure

Provide the complete folder/file structure.

## E. Roles & Permissions Matrix

Example:

| Permission       | Super Admin |        Admin | Customer Support |   Viewer |
| ---------------- | ----------: | -----------: | ---------------: | -------: |
| Customer View    |           ✓ |            ✓ |                ✓ |        ✓ |
| Customer Create  |           ✓ |            ✓ |                ✓ |        - |
| Customer Update  |           ✓ |            ✓ |                ✓ |        - |
| Customer Delete  |           ✓ |            ✓ |                - |        - |
| Complaint View   |           ✓ |            ✓ |                ✓ |        ✓ |
| Complaint Create |           ✓ |            ✓ |                ✓ |        - |
| Complaint Update |           ✓ |            ✓ |                ✓ |        - |
| Complaint Delete |           ✓ |            ✓ |                - |        - |
| Reports          |           ✓ |            ✓ |         optional |        ✓ |
| Export           |           ✓ |            ✓ |         optional | optional |
| Users            |           ✓ |            ✓ |                - |        - |
| Roles            |           ✓ | configurable |                - |        - |

Adjust the matrix based on the final permission design.

## F. Complaint Workflow

Show the lifecycle clearly.

## G. Customer Workflow

Show:

```text
Search Phone
→ Existing Customer
→ Add Complaint
```

and:

```text
Search Phone
→ Customer Not Found
→ Create Customer
→ Add Complaint
```

## H. Implementation Plan

Break the implementation into small manageable phases.

---

# 64. Coding Rules

When implementation starts:

1. Do not generate the whole project in one huge response.
2. Work module by module.
3. Before each major module, explain what will be created.
4. Generate complete files when appropriate.
5. Do not leave important code as pseudo-code.
6. Follow Laravel conventions.
7. Keep code simple.
8. Explain non-obvious decisions briefly.
9. Do not modify unrelated files without reason.
10. Preserve existing functionality when making changes.
11. If a database change is required, create/update the migration appropriately.
12. If an architectural decision changes, update documentation.
13. If the database changes, update `database_design.md`.
14. If project structure changes, update `project_structure.md`.

---

# 65. Final Requirement

The most important goal is:

> Build a professional Complaint Management System without making the code unnecessarily complicated.

I prefer:

```text
Simple Laravel architecture
+
Clean database design
+
Readable Eloquent relationships
+
Simple Blade / Livewire UI
+
Tailwind CSS
+
Proper authorization
+
Good validation
+
Good audit logging
+
Good filtering/reporting
+
Good documentation
```

over:

```text
Complex enterprise architecture
+
Too many abstractions
+
Too many design patterns
+
Hard-to-understand code
```

Always choose the simplest design that correctly satisfies the requirements.

---

# START

First, analyze the requirements.

Then produce:

```text
1. Architecture Overview
2. Database Design
3. ER Diagram
4. Project Structure
5. Modules
6. Roles & Permissions
7. Complaint Workflow
8. Customer Workflow
9. Filtering & Reporting Design
10. Excel Export Design
11. Localization Design
12. Development Roadmap
13. project_structure.md
14. database_design.md
```

Do NOT start generating the complete application code until this design is reviewed and finalized.
