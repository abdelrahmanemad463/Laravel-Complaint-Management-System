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


---

# 66. Current Implementation Status

This document began as the system requirements and implementation brief. The following addendum records the verified state of the codebase so future development can distinguish completed behavior from intentionally deferred requirements.

**Last verified:** 2026-08-26  
**Application path:** `C:\xampp\htdocs\complaint`  
**Current local URL:** `http://localhost/complaint/public/`

## 66.1 Implemented Technology and Architecture

The system is implemented as a Laravel 12 application on PHP 8.2+ using Blade, Tailwind CSS 4, Vite, Eloquent, Laravel migrations, Laravel session authentication, Spatie Laravel Permission 6.24.0, and Maatwebsite Laravel Excel 3.1.67. The configured local runtime uses SQLite. The UI is server-rendered and uses small vanilla-JavaScript enhancements; **Livewire is not currently used**. React, Vue, Inertia, and a separate SPA were not introduced.

The actual application flow is:

```text
Browser
  ↓
Named route in routes/web.php
  ↓
SetLocale + auth middleware
  ↓
Controller permission checks / Spatie permission middleware
  ↓
Form Request validation where applicable
  ↓
Eloquent query or mutation
  ↓
ActivityLogService or ComplaintStatusService when required
  ↓
Blade response, redirect, JSON search response, or Excel download
```

The code intentionally avoids repositories, generic CRUD frameworks, excessive DTOs, custom domain layers, microservices, and unnecessary event-driven architecture. No custom application Policies, Livewire components, Notifications, or business Jobs currently exist; authorization is implemented through controllers, Laravel Gate, and Spatie permissions.

## 66.2 Implemented Modules and Workflows

| Requirement area | Current verified implementation |
|---|---|
| Customers | Customer CRUD, required primary phone, three optional phone fields, name/all-phone search, complaint summary, complaint history, and Add Complaint action |
| Customer picker | Ten initial records, protected JSON search, 250 ms debounce, `fetch()`, `AbortController`, and selected-record preservation |
| Complaints | Create/list/show/update, required relationships and descriptions, authenticated `created_by`, complaint ID and short/full description text filters, master-data filters, creator/date filters, pagination, and query-string preservation |
| Branch filter | Multiple branch selection through `branch_ids[]` and `whereIn` |
| Master data | Dynamic branches, services, sources, categories, complaint types, priorities, and statuses with active flags, colors/order fields, pagination, soft deletes, and permission checks |
| Branch report | Date range and multi-branch filters, five-record initial branch picker, name search, and SQL grouping by complaint date and branch |
| Resolution | `resolution`, `resolved_by`, and `resolved_at`; moving to the status named Solved records the authenticated resolver and timestamp |
| Status history | `complaint_status_histories` records previous status, new status, reason, authenticated actor, and timestamp inside a transaction |
| Activity logs | Append-oriented `activity_logs` records stable action names, actor, polymorphic subject, description, and JSON old/new snapshots |
| Complaint timeline | Complaint details combine status-history entries and activity-log entries; known stored action identifiers are localized at display time |
| Users | Authorized create/update, role assignment, name/email search filter, role filter, database pagination at 20 records |
| Roles | Role list, custom role creation, permission configuration, permission/user counts, protected baseline roles, reserved-name prevention, and server-side refusal to delete roles assigned to users |
| Dashboard | Seven-day complaint aggregates, top complained-about branches, status distribution, and priority distribution ordered by priority level |
| Excel export | Filtered query export with localized English/Arabic headings and the exact current columns listed below |
| Localization | English/Arabic interface, validation, authentication, complaint/customer/activity messages, localized audit output, and Arabic RTL layout |
| PWA | Manifest, icons, standalone display metadata, and static-only service-worker caching; private pages, authentication, sessions, and JSON responses are not cached |

## 66.3 Current Database and Seed State

The implemented migration order is:

```text
0001_01_01_000000_create_users_table
0001_01_01_000001_create_cache_table
0001_01_01_000002_create_jobs_table
2026_08_23_161041_create_permission_tables
2026_08_23_161200_create_complaint_master_data_tables
2026_08_23_161300_create_complaints_table
2026_08_23_161400_create_complaint_history_tables
```

The complaint and master-data migrations use foreign keys, restrictive deletion behavior for historical references, nullable resolver/activity actor references where appropriate, indexes for phone/date/filter access, and soft deletes on business records. `DatabaseSeeder` runs permissions/roles, master data, and demo data in that order.

The seeded master data includes Branch A/B/C; Take Away, Dine In, and Delivery services; WhatsApp, Phone, Facebook, In Person, Website, and Email sources; Food Quality, Customer Service, Staff, Delivery, Payment, and Order categories; Wrong Order, Missing Item, Late Delivery, Bad Treatment, Wrong Price, and Food Quality types; Low, Medium, High, and Critical priorities; and Pending, In Progress, Solved, Closed, and Reopened statuses. `DemoDataSeeder` then creates an idempotent dataset of exactly 100 customers and one complaint per demo customer, distributing records across the seeded master data values.

## 66.4 Current Permission Design

Permissions are generated systematically for `customer`, `complaint`, `branch`, `service`, `source`, `category`, `type`, `priority`, `status`, `report`, `user`, `role`, and `audit`, using `view`, `create`, `update`, and `delete` actions. Additional permissions are `complaint.view_logs`, `complaint.export`, `report.export`, and `audit.view`.

| Role | Current seeded permissions |
|---|---|
| Super Admin | All seeded permissions, plus Gate-level unrestricted access |
| Admin | All seeded permissions except `role.delete`, `user.delete`, and `audit.view` |
| Customer Support | `customer.view/create/update` and `complaint.view/create/update` |
| Viewer | `customer.view`, `complaint.view`, all master-data `*.view`, and `report.view` |

The `role.delete` permission is therefore normally available only to Super Admin under the baseline seed configuration. Role create/update and user administration remain permission-protected. Super Admin protection is enforced server-side and is not dependent only on hidden UI controls.

## 66.5 Exact Current Excel Columns

The implementation uses a practical 17-column export rather than every suggested field in Section 37:

```text
Complaint ID
Customer Name
Primary Phone
Branch
Service
Source
Category
Complaint Type
Priority
Status
Complaint Date
Short Description
Description
Resolution
Created By
Resolved By
Timeline
```

The export class reuses `Complaint::filter($filters)` and eager-loads related records. It includes the short description and combined timeline while intentionally not exporting the optional customer phone fields, customer address, resolved timestamp, or Laravel created/updated timestamps.

## 66.6 Exact Current Audit Actions

The current code records these action identifiers:

```text
complaint.created
complaint.updated
complaint.status_changed
customer.created
customer.updated
master_data.created
master_data.updated
master_data.deleted
```

Complaint updates preserve old/new values, so priority, branch, service, category, type, status, description, and resolution changes can be represented in snapshots. There is currently no complaint-delete route or separate action for each individual field change. The audit and timeline views translate known action identifiers into the selected locale and use a localized generic fallback for unknown actions.

## 66.7 Filtering and Scalability Status

The complaint index and export support `complaint_id`, a validated `description` term searched against both `short_description` and `description`, `customer_id`, `branch_ids[]`, `service_id`, `source_id`, `category_id`, `type_id`, `priority_id`, `status_id`, `created_by`, `date_from`, and `date_to`. The description-search control is placed at the end of the complaint filter grid. The description term is optional text limited to 255 characters, and the `date_to` validator requires it to be on or after `date_from`. Complaint results are paginated at 30 records, with query strings preserved.

Customer list results are paginated at 30 records. The customer picker initially displays at most 10 records and searches all four phone fields plus name through a protected JSON route. Branch picker results are limited to 5 records and support name search. User results are paginated at 20 records and can be filtered by name/email and exact role.

## 66.8 Localization and Error Handling Status

The current translation directories contain `common.php`, `auth.php`, `complaints.php`, `customers.php`, `activity.php`, and `validation.php` for both `en` and `ar`. The application uses `SetLocale` to accept only `en` and `ar`, switches the main layout between LTR and RTL, and localizes flash messages, validation messages, login errors, audit actions, timeline entries, login copy, customer status-summary labels, and Excel headings.

Both locale dictionaries now contain the localized short/full description search label, and the existing localization regression coverage remains green. Normal Laravel validation and session flash handling are used; sensitive internal errors are not intentionally exposed through the application UI.

## 66.9 Verification Status

The latest full verification completed successfully:

```text
Blade views: cleared and cached successfully
Vite production build: completed successfully
Laravel test suite: 38 tests passed, 171 assertions
Migration status: all current migrations reported Ran
Registered routes: 42 routes reported by php artisan route:list
```

Localization regression tests cover English/Arabic messages, login copy, customer status summaries, audit-log action rendering, theme labels, and the new description-search label. Complaint-filter regression tests verify that a single validated description term matches either the short or full description while excluding unrelated complaints; export regression tests verify the same filter is reused by `ComplaintsExport`. Pagination regression tests verify 30 complaint rows and 30 customer rows on the first two pages against the seeded larger dataset. PWA tests cover manifest metadata, icons, service-worker files, and the rule that private pages/API responses are not cached. Dark-mode layout tests verify the theme switch markup and early localStorage bootstrap. `php artisan db:seed --force` completed successfully with the expanded idempotent demo dataset; the latest full suite passed with 38 tests and 171 assertions.

## 66.10 Intentional Limitations and Deferred Requirements

The following requirements remain future work rather than current implementation claims:

- Full offline PWA complaint creation, synchronization, and offline authentication.
- Complaint attachments and secure file-upload workflows.
- Internal comments, notifications, email/WhatsApp integration, and external API clients.
- Customer portal, registration, password reset, mobile application, and branch-manager dashboard.
- Satisfaction ratings, SLA tracking, escalation workflows, and advanced analytics.
- Branch-specific user scoping.
- Custom queued jobs, scheduled synchronization, and event/listener workflows.
- Separate per-locale database values for branch/service/status/master-data names.

The repository also does not include a sessions migration even though `.env.example` names `SESSION_DRIVER=database`; deployments must provide the table or choose another session driver. The default Laravel `welcome.blade.php` remains as an unused skeleton file because the application’s `/` route is the authenticated dashboard.

This addendum supersedes any earlier speculative wording when it conflicts with verified code. The original sections remain useful as the requirements baseline, while this section records what is actually implemented and what is intentionally deferred.


## 66.11 Mandatory Documentation Synchronization

The project now has a persistent instruction requiring documentation synchronization for every code edit or code change, including feature work, bug fixes, refactors, configuration changes, route changes, permission changes, and frontend changes.

Before editing code, the developer or agent must read:

```text
memory.md
complete_project_specification.md
project_structure.md
```

After the code change, the same task must update all three files so they remain consistent with the final implementation:

| File | Required role |
|---|---|
| `complete_project_specification.md` | Requirements baseline plus verified implementation status |
| `memory.md` | Persistent continuation context, decisions, pending work, issues, and verification results |
| `project_structure.md` | Current architecture, project map, technologies, modules, routes, and developer guidance |

No behavior should be described as implemented until it has been verified in the codebase. Schema changes also require `database_design.md` to be updated. Relevant tests, build checks, and other verification commands must be run before completion, and their results must be recorded in `memory.md`.


## 66.12 Pagination Verification Coverage

The feature regression suite verifies that both the complaints and customers index pages render 30 records on each of the first two pages when the dataset exceeds one page. The demo seed dataset is included in the same test setup, so pagination coverage is exercised against more than 100 existing records rather than only a small isolated fixture.


## 66.13 Dark Mode and Theme Preference

The shared Blade layout now includes a localized light/dark theme switch. The selected theme is stored in browser `localStorage` under `complaint-theme`, initialized before the Vite bundle runs to reduce theme flash, and applied through `html[data-theme='dark']` CSS overrides. Shared page surfaces, typography, form controls, tables, navigation, alerts, buttons, badges, picker result panels, and other light-palette utility classes have explicit dark-mode overrides so report filters and all other existing server-rendered forms do not remain white or low-contrast in dark mode. Component-specific dark selectors provide brighter labels, headings, navigation, secondary buttons, borders, hover states, and focus states while leaving light mode unchanged. The switch updates the PWA `theme-color` metadata and preserves English/Arabic labels and RTL behavior. Bilingual localization and layout regression tests cover the new theme controls and explicit dark form/card/picker surfaces. Contrast regression assertions cover component selectors for labels, secondary buttons, and navigation; the latest full suite passed after the contrast correction with 34 tests and 144 assertions.


## 66.14 Branch Report Pagination

The branch reports page at `/reports/branches` now uses Laravel `paginate(30)->withQueryString()` for its grouped complaint-date/branch rows. The filtered total is displayed above the table, and date-range plus multi-branch query parameters are preserved in paginator links. Regression coverage verifies 30 grouped rows on the first page and the remaining row on the second page using the existing report filters. Final verification is recorded in `memory.md`: the focused branch-report suite passed with 17 tests and 59 assertions, and the full suite passed with 35 tests and 152 assertions; Blade caching, the Vite build, migration status, and route verification also passed.


## 66.15 Complaint Export Short Description and Timeline

The filtered complaint Excel export now includes a localized short-description column and a localized combined timeline column. The export query eager-loads `statusHistories.fromStatus`, `statusHistories.toStatus`, `statusHistories.changer`, and `activityLogs.user` so mapping does not create an N+1 relationship pattern. The timeline cell contains newline-separated events sorted chronologically across both sources: status-history events include timestamp, actor, previous status, new status, and optional reason; activity events include timestamp, actor, and the localized action label. English headings are `Short Description` and `Timeline`; Arabic headings are `الوصف المختصر` and `الخط الزمني`. Focused export regression coverage passed with 4 tests and 15 assertions, including description-filter reuse. Full verification completed with 38 tests and 171 assertions; Blade views compiled and cached, the Vite production build completed, all migrations reported Ran, and 42 routes were registered.
