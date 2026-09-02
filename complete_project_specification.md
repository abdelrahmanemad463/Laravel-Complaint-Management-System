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

**Last verified:** 2026-08-29
**Application path:** `C:\xampp\htdocs\complaint`  
**Current local URL:** `http://localhost/complaint/public/`

## 66.0 Recent Bug Fix — Customer show SQL Server compatibility

On 2026-08-29, `CustomerController::show()` was corrected for SQL Server compatibility. It had issued a raw aggregate query whose correlated subquery used `limit 1` (`select id from complaint_statuses where name = 'Pending' limit 1`), which SQL Server rejects (`Incorrect syntax near 'limit'`). This surfaced when opening `/customers/{customer}` against the local SQL Server runtime. The query was dead code: `customers/show.blade.php` already computes the complaint summary (total / pending / in progress / solved / closed) in PHP from the loaded complaint collection, and the localization tests assert those summary labels from the Blade template. The controller now loads only the complaint relationships and passes `$customer` to the view without a `$summary` variable. No schema, route, permission, or frontend change was required. Full verification on 2026-08-29 passed with **46 tests / 228 assertions**. `memory.md` and `project_structure.md` were synchronized with this fix.

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
| Customer picker | Complaint index uses a closed searchable single-select dropdown with ten initial records, protected JSON search, 250 ms debounce, `fetch()`, `AbortController`, selected-record preservation, compact summary, Clear action, and outside-click/Escape closing; create/edit retains its compatible searchable picker |
| Complaints | Create/list/show/update, required relationships and descriptions, authenticated `created_by`, complaint ID and short/full description text filters, master-data filters, creator/date filters, pagination, and query-string preservation |
| Branch filter | Complaint index uses a closed searchable multi-select dropdown with checkbox-style selected states, compact count summary, Clear action, bounded scrolling results, outside-click/Escape closing, and existing `branch_ids[]`/`whereIn` filtering |
| Master data | Dynamic branches, services, sources, categories, complaint types, priorities, and statuses with active flags, colors/order fields, pagination, soft deletes, and permission checks |
| Branch report | Date range and multi-branch filters, five-record initial branch picker, name search, and SQL grouping by complaint date and branch |
| Resolution | `resolution`, `resolved_by`, and `resolved_at`; moving to the status named Solved records the authenticated resolver and timestamp |
| Status history | `complaint_status_histories` records previous status, new status, reason, authenticated actor, and timestamp inside a transaction |
| Activity logs | Append-oriented `activity_logs` records stable action names, actor, polymorphic subject, description, and JSON old/new snapshots |
| Complaint timeline | Complaint details combine status-history entries and activity-log entries; known stored action identifiers are localized at display time |
| Users | Authorized create/update, role assignment, name/email search filter, role filter, database pagination at 20 records |
| Roles | Role list, custom role creation, permission configuration, permission/user counts, protected baseline roles, reserved-name prevention, and server-side refusal to delete roles assigned to users |
| Dashboard | Authenticated, filterable real-data analytics dashboard with KPIs, trends, branch/category/type/service/source/status/priority charts, resolution metrics, branch performance, recent/attention/solved lists, insights, and actionable complaint links |
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

Customer list results are paginated at 30 records. On the complaint index, the Customer control is a closed single-select dropdown that initially displays at most 10 records and searches all four phone fields plus name through a protected JSON route. On the complaint index, the Branch control is a closed multi-select dropdown with at most five initial results and name search; it preserves the existing `branch_ids[]` submission. The create/edit customer picker and branch-report picker retain compatible legacy markup. User results are paginated at 20 records and can be filtered by name/email and exact role.

## 66.8 Localization and Error Handling Status

The current translation directories contain `common.php`, `auth.php`, `complaints.php`, `customers.php`, `activity.php`, and `validation.php` for both `en` and `ar`. The application uses `SetLocale` to accept only `en` and `ar`, switches the main layout between LTR and RTL, and localizes flash messages, validation messages, login errors, audit actions, timeline entries, login copy, customer status-summary labels, and Excel headings.

Both locale dictionaries now contain the localized short/full description search label and Clear action used by the complaint filter dropdowns, and the existing localization regression coverage remains green. Normal Laravel validation and session flash handling are used; sensitive internal errors are not intentionally exposed through the application UI.

## 66.9 Verification Status

The latest full verification completed successfully:

```text
Blade views: cleared and cached successfully
Vite production build: completed successfully
Laravel test suite: 39 tests passed, 179 assertions
Migration status: all current migrations reported Ran
Registered routes: 42 routes reported by php artisan route:list
```

Localization regression tests cover English/Arabic messages, login copy, customer status summaries, audit-log action rendering, theme labels, the description-search label, and the dropdown Clear action. Complaint-filter regression tests verify that a single validated description term matches either the short or full description while excluding unrelated complaints, and that the complaint index renders closed Customer and Branch dropdown controls. Export regression tests verify the same filter is reused by `ComplaintsExport`. Pagination regression tests verify 30 complaint rows and 30 customer rows on the first two pages against the seeded larger dataset. PWA tests cover manifest metadata, icons, service-worker files, and the rule that private pages/API responses are not cached. Dark-mode layout tests verify the theme switch markup and early localStorage bootstrap. `php artisan db:seed --force` completed successfully with the expanded idempotent demo dataset; the latest full suite passed with 39 tests and 179 assertions.

## 66.10 Intentional Limitations and Deferred Requirements

The following requirements remain future work rather than current implementation claims:

- Full offline PWA complaint creation, synchronization, and offline authentication.
- Complaint attachments and secure file-upload workflows.
- Internal comments, notifications, email/WhatsApp integration, and external API clients.
- Customer portal, registration, password reset, mobile application, and branch-manager dashboard.
- Satisfaction ratings, SLA tracking, and escalation workflows.
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
| `questions_and_answers.md` | Rephrased implementation-focused Q&A for recurring "how does it work" questions |

No behavior should be described as implemented until it has been verified in the codebase. Schema changes also require `database_design.md` to be updated. Relevant tests, build checks, and other verification commands must be run before completion, and their results must be recorded in `memory.md`.


## 66.12 Pagination Verification Coverage

The feature regression suite verifies that both the complaints and customers index pages render 30 records on each of the first two pages when the dataset exceeds one page. The demo seed dataset is included in the same test setup, so pagination coverage is exercised against more than 100 existing records rather than only a small isolated fixture.


## 66.13 Dark Mode and Theme Preference

The shared Blade layout now includes a localized light/dark theme switch. The selected theme is stored in browser `localStorage` under `complaint-theme`, initialized before the Vite bundle runs to reduce theme flash, and applied through `html[data-theme='dark']` CSS overrides. Shared page surfaces, typography, form controls, tables, navigation, alerts, buttons, badges, picker result panels, and other light-palette utility classes have explicit dark-mode overrides so report filters and all other existing server-rendered forms do not remain white or low-contrast in dark mode. Component-specific dark selectors provide brighter labels, headings, navigation, secondary buttons, borders, hover states, and focus states while leaving light mode unchanged. The switch updates the PWA `theme-color` metadata and preserves English/Arabic labels and RTL behavior. Bilingual localization and layout regression tests cover the new theme controls and explicit dark form/card/picker surfaces. Contrast regression assertions cover component selectors for labels, secondary buttons, and navigation; the latest full suite passed after the contrast correction with 34 tests and 144 assertions.


## 66.14 Branch Report Pagination

The branch reports page at `/reports/branches` now uses Laravel `paginate(30)->withQueryString()` for its grouped complaint-date/branch rows. The filtered total is displayed above the table, and date-range plus multi-branch query parameters are preserved in paginator links. Regression coverage verifies 30 grouped rows on the first page and the remaining row on the second page using the existing report filters. Final verification is recorded in `memory.md`: the focused branch-report suite passed with 17 tests and 59 assertions, and the full suite passed with 35 tests and 152 assertions; Blade caching, the Vite build, migration status, and route verification also passed.


## 66.15 Complaint Export Short Description and Timeline

The filtered complaint Excel export now includes a localized short-description column and a localized combined timeline column. The export query eager-loads `statusHistories.fromStatus`, `statusHistories.toStatus`, `statusHistories.changer`, and `activityLogs.user` so mapping does not create an N+1 relationship pattern. The timeline cell contains newline-separated events sorted chronologically across both sources: status-history events include timestamp, actor, previous status, new status, and optional reason; activity events include timestamp, actor, and the localized action label. English headings are `Short Description` and `Timeline`; Arabic headings are `الوصف المختصر` and `الخط الزمني`. Focused export regression coverage passed with 4 tests and 15 assertions, including description-filter reuse. Full verification completed with 38 tests and 171 assertions; Blade views compiled and cached, the Vite production build completed, all migrations reported Ran, and 42 routes were registered.


## 66.11 Advanced dashboard redesign status (superseded by Section 66.19)

The advanced dashboard redesign was implemented in stages around `DashboardFilterRequest`, `DashboardStatsService`, and `DashboardController`; its final verified state is recorded in Section 66.19. No schema migration was introduced; the current data model supports the implemented metrics, with average resolution time limited to records having reliable `created_at` and `resolved_at` values.


## 66.12 Dashboard implementation progress update

The dashboard implementation includes validated filters, SQL/Eloquent metrics, localized English/Arabic labels, responsive Blade layout, and Chart.js rendering in `resources/js/app.js`. The page remains authenticated through the existing root route and `complaint.view` authorization; no new permission or route was introduced. The service uses the existing complaint schema, treats Solved and Closed as resolved, and calculates average resolution time only from non-negative `created_at` to `resolved_at` intervals. Intermediate Blade/chart/test corrections were completed before the final verification recorded in Section 66.19.


## 66.15 Dashboard verification correction

The dashboard trend aggregation now aliases `DATE(complaint_date)` as `bucket_date` before grouping, providing a consistent daily source for day/week/month bucket construction across supported database drivers. The initial dashboard regression also exposed a stale test expectation for a branch query parameter: Laravel correctly renders the indexed array form `branch_ids[0]`, so the test must assert that encoded form. Focused and full verification remain pending.


## 66.16 Dashboard test assertion alignment

The dashboard feature test now asserts Laravel’s actual indexed encoding for branch-array links (`branch_ids[0]`), matching the existing `branch_ids[]` filter contract. This removes the stale test expectation without changing application behavior. Focused dashboard verification is ready to rerun; final completion remains subject to focused and full checks.


## 66.17 Dashboard regression assertion correction

The focused dashboard test now verifies that the filtered complaint appears by its rendered complaint ID in the recent-complaints section. The dashboard intentionally presents a compact summary rather than rendering the short description in that row, so the assertion now matches the actual UI. Focused and full verification remain pending.


## 66.18 Dashboard actionable navigation correction

The total-complaints KPI now links to the complaint list with the complete active dashboard filter query. It no longer appends the Pending status; status-specific KPI links remain limited to their corresponding status. This preserves the dashboard’s filter semantics without changing backend routes or query rules. The final verified navigation behavior is recorded in Section 66.19; both locale dictionaries contain the `multi_select_hint` label used by the branch filter control.


The English locale includes the dashboard branch multi-select hint (`multi_select_hint`), and the Arabic equivalent is recorded in the completed bilingual localization coverage.


The Arabic locale includes the dashboard branch multi-select hint key `multi_select_hint`, completing the bilingual filter-label pair. Final dashboard localization verification is recorded in Section 66.19.


Dashboard localization regression coverage now asserts the filter label, branch multi-select hint, and Solved/Closed resolution-definition text in both English and Arabic. The new assertions must be included in the final full verification before this dashboard redesign is marked complete.


## 66.19 Advanced dashboard — final verified implementation

The advanced dashboard specification is now implemented and verified. The authenticated root dashboard uses `DashboardFilterRequest`, `DashboardStatsService`, and `DashboardController` with the existing `complaint.view` authorization; no new route or permission was introduced. The dashboard applies validated date, branch, service, source, category, type, priority, and status filters to real complaint data. It presents KPI cards for total, Pending, In Progress, Solved, High + Critical, and resolution rate; day/week/month complaint trends; branch, category, type, service, source, status, and priority distributions; branch-performance metrics; recent complaints; unresolved High/Critical attention items; recently solved complaints; actionable complaint list/detail links; and rule-based insights.

Chart.js is bundled through Vite and renders responsive charts with theme-aware text/grid colors. English and Arabic dashboard labels, filter guidance, and metric definitions are covered by localization tests, and Arabic continues to use the application’s RTL layout. Solved and Closed are resolved statuses. Resolution rate is resolved-count divided by filtered total; average resolution time uses only non-negative `created_at` to `resolved_at` intervals and is omitted when no reliable interval exists. Trend buckets use `complaint_date`, with daily grouping through 31 days, week grouping through 180 days, and monthly grouping beyond that range. Previous-period comparison uses the same non-date filters over an immediately preceding equal-length period.

Final verification completed on 2026-08-27: focused dashboard coverage passed with 2 tests and 10 assertions; the full Laravel suite passed with 42 tests and 195 assertions; Blade views cleared and cached; the Vite production build completed; all migrations reported `Ran`; `php artisan route:list` reported 42 routes; and `git diff --check` passed. The sandbox browser could not connect to the user’s XAMPP-only localhost service, so live desktop/mobile visual inspection was not independently performed here. No schema change was required; dashboard metric semantics are documented in `database_design.md`. Earlier progress sections in this addendum describe intermediate implementation corrections and are superseded by this final section.


## 66.20 Picker standardization progress

The dashboard statistics service now supplies the first five branches ordered by name and appends selected branch IDs outside that initial set. This preserves the existing bounded-search scalability pattern while allowing all active multi-branch selections to remain visible after filtering. Dashboard, branch-report, and complaint-create picker markup standardization remains in progress.


## 66.21 Dashboard branch picker standardization progress

The dashboard Branch filter now uses the shared closed searchable multi-select control with the existing `branch_ids[]` contract, bounded initial results, remote branch-name search, selected states, Clear action, and outside-click/Escape closing. The branch-report and complaint-create controls remain pending standardization in this task.


## 66.22 Branch-report picker standardization progress

The branch-report Branch filter now uses the shared closed searchable multi-select control with the existing `branch_ids[]` contract, bounded initial results, remote branch-name search, selected states, Clear action, and outside-click/Escape closing. The complaint-create Customer picker remains the final selector to standardize in this task.


## 66.23 Complaint-create customer picker standardization progress

The complaint create/edit Customer control now uses the shared closed searchable single-select control while preserving the required `customer_id` input, bounded initial ten-customer list, name/any-phone background search, selected state, Clear action, and outside-click/Escape closing. Dashboard and branch-report branch controls are also standardized; regression coverage and final verification remain pending.


## 66.24 Cross-page picker regression coverage

Feature coverage now verifies closed picker markup on all requested pages: the dashboard Branch control and branch-report Branch control use closed searchable multi-select inputs with `branch_ids[]`, while complaint creation uses a closed searchable Customer single-select with `customer_id`. Existing bounded result limits and backend filtering contracts remain unchanged.


## 66.25 Cross-page picker standardization — final verified implementation

The requested selectors are now standardized across all three pages. On the dashboard and `/reports/branches`, Branch is a closed searchable multi-select dropdown that preserves the existing `branch_ids[]` contract. On `/complaints/create` and complaint edit, Customer is a closed searchable single-select dropdown that preserves the existing `customer_id` contract. All three reuse the established native-JavaScript picker behavior: bounded initial results, debounced background search, `AbortController` cancellation, selected states, Clear actions, outside-click/Escape closing, responsive dropdown panels, dark-mode styling, localization, and RTL-compatible markup. Dashboard branch seeding uses five initial branches and appends selected branches outside that seed; reports already use the same five-branch seed; complaint forms use the ten-customer seed.

Focused cross-page picker coverage passed with 22 tests and 95 assertions. Final verification on 2026-08-27 passed with 43 tests and 205 assertions; Blade views cleared/cached, the Vite production build completed, all migrations reported `Ran`, `php artisan route:list` reported 42 routes, and `git diff --check` passed. No schema migration, route, permission, or backend filter-contract change was required. The sandbox browser could not connect to the user’s XAMPP-only localhost service, so live desktop/mobile visual inspection was not independently performed here; automated Blade/frontend/feature checks passed. Earlier picker-standardization progress sections are superseded by this final section.


## 66.26 Dashboard branch-hint correction

The dashboard Branch dropdown no longer renders the incorrect `Hold Ctrl/Cmd to select multiple branches` helper text. The dropdown remains a closed searchable multi-select with the existing selection, Clear, closing, localization, and `branch_ids[]` behavior. The shared translation key remains available for other controls but is intentionally unused in the dashboard view.


## 66.27 Verified local SQL Server configuration

The attached local `.env` is configured for SQL Server with database `complaints`, local default host/port `127.0.0.1:1433`, and SQL authentication. Credentials are intentionally excluded from this specification. The attached Windows PHP runtime has both `pdo_sqlsrv` and `sqlsrv` extensions available. Connection, migration, and seeding verification completed successfully.


## 66.28 SQL Server connection options

The Laravel `sqlsrv` connection reads `DB_ENCRYPT` and `DB_TRUST_SERVER_CERTIFICATE` from the local environment. The supplied development setup uses encryption disabled and trusts the local server certificate. Credentials remain intentionally excluded from project documentation. SQL Server connection, migration, and seeding verification completed successfully.


## 66.29 SQL Server Encrypt compatibility correction

The first connection attempt reached ODBC Driver 17 but rejected the boolean `DB_ENCRYPT=false` value for the `Encrypt` connection-string attribute. The local environment now uses the ODBC-compatible string `DB_ENCRYPT=no`; `DB_TRUST_SERVER_CERTIFICATE=true` remains enabled for the local development connection. The corrected connection, migrations, and seeding were verified successfully.


## 66.30 SQL Server foreign-key migration compatibility

The first SQL Server migration run created the migration repository and completed the users, cache, jobs, permission, and complaint master-data migrations, then failed in `2026_08_23_161300_create_complaints_table.php` because SQL Server rejects `ON DELETE RESTRICT`. The failed migration did not leave a `complaints` table. Required complaint foreign keys now use Laravel’s explicit `noActionOnDelete()`, preserving the intended restrictive/no-action delete policy while generating SQL Server-compatible DDL. The complaint-history migration subsequently received the same compatibility update before the migration sequence was rerun.


## 66.31 SQL Server-compatible complaint history migration

The complaint-history migration `2026_08_23_161400_create_complaint_history_tables.php` now uses `noActionOnDelete()` for required status-history foreign keys. Both complaint-domain migrations preserve restrictive/no-action deletion semantics while avoiding SQL Server’s unsupported `ON DELETE RESTRICT` syntax. The migration sequence was rerun successfully from the failed complaints migration; no `complaints` table was present after the failed attempt.


## 66.32 Temporary SQL Server count verification

A temporary root-level `.sqlsrv_verify.php` script was created solely to bootstrap Laravel and report aggregate SQL Server seed counts without exposing credentials. It was removed after verification and is not part of the application architecture.


## 66.33 Verified SQL Server migration and demo seed

SQL Server verification completed successfully. `php artisan migrate --database=sqlsrv --force` completed all pending migrations, including complaints and complaint history. `php artisan db:seed --database=sqlsrv --force` completed `PermissionSeeder`, `MasterDataSeeder`, and `DemoDataSeeder`. A temporary bootstrap count check reported 100 customers, 100 complaints, 4 roles, 3 branches, 3 services, 6 sources, 6 categories, 6 types, 4 priorities, 5 statuses, and 1 `admin@example.com` user. The temporary verifier was removed. Final `php artisan migrate:status --database=sqlsrv` showed every migration as `Ran`. PHPUnit passed with 43 tests and 205 assertions using in-memory SQLite; Blade cache, the Vite production build via `npm.cmd`, `git diff --check`, the 42-route check, and the HTTP 302 smoke check also passed.


## 66.34 Local environment-file protection

The repository’s `.gitignore` now ignores `.env` so local SQL Server credentials are not newly added to version control. The tracked `.env` entry was removed from the Git index while preserving the local file; no credential value is recorded in this specification.


## 66.35 Local environment file removed from Git tracking

The tracked `.env` entry was removed from the Git index with `git rm --cached`; the local file remains in place for the attached runtime and is now protected by `.gitignore`. Its credential value was never copied into this specification or command output.


## 66.36 Final credential-file protection check

Final repository protection check passed: `git check-ignore -v .env` matched the `.env` rule, and `git ls-files --error-unmatch .env` reported it is not tracked. The local `.env` remains available to the attached XAMPP runtime without exposing its credential contents.


## 66.37 SQL Server dashboard trend compatibility

The dashboard login failure was traced to `DashboardStatsService::trend()`, which used MySQL/SQLite-style `DATE(complaint_date)` and grouped by its alias. SQL Server does not provide `DATE()` as a built-in function. The service now selects `CAST(complaint_date AS date)` for the `sqlsrv` driver and retains `DATE(complaint_date)` for the SQLite/MySQL test and supported paths; the same driver-selected expression is used in `GROUP BY` and `ORDER BY`. This preserves the existing day/week/month bucket behavior without changing dashboard metrics or filters. The SQL Server dashboard smoke check now passes.


## 66.38 Temporary SQL Server dashboard smoke check

A temporary root-level `.sqlsrv_dashboard_verify.php` script was created solely to bootstrap Laravel and execute `DashboardStatsService::build([])` against the configured SQL Server connection. It reported aggregate trend metadata (`total=100`, daily grouping, 30 trend points), passed, and was removed; it is not part of the application architecture.


## 66.39 Verified SQL Server dashboard fix

Final verification for the SQL Server dashboard fix completed on 2026-08-27. The live SQL Server dashboard smoke check executed `DashboardStatsService::build([])` successfully and reported `total=100`, daily grouping, and 30 trend points. The temporary smoke script was removed. The full PHPUnit suite passed with 43 tests and 205 assertions using in-memory SQLite; Blade view caching passed; `npm.cmd run build` passed; Laravel reported 42 routes; and `git diff --check` passed. No schema, route, permission, or database-design change was required.


## 66.40 English results-count localization

The branch-report view uses `__('common.results_count', ['count' => $rows->total()])`, but the English common dictionary did not define that key. The English dictionary now defines `results_count` as `:count results`; the Arabic counterpart remains to be added and verified.


## 66.41 Arabic results-count localization

The Arabic common dictionary now defines `results_count` as `عدد النتائج: :count`, completing the bilingual translation for the branch-report total shown above the paginated table. Focused localization and branch-report regression verification remain to be run.


## 66.42 Branch-report results-count regression coverage

Regression coverage was added to `ComplaintFiltersAndAuthorizationTest` for the exact `/reports/branches` rendering path. It requests a future empty date range in English and Arabic and asserts `0 results` and `عدد النتائج: 0`, respectively. The focused and full test suites remain to be run after this addition.


## 66.43 Verified branch-report results-count localization

Final verification for the branch-report results-count localization completed on 2026-08-27. The focused localization and complaint/filter suites passed with 26 tests and 125 assertions, including the exact English `0 results` and Arabic `عدد النتائج: 0` branch-report rendering. The full suite passed with 44 tests and 209 assertions. Blade view caching, `npm.cmd run build`, and `git diff --check` also passed. No schema, route, permission, or database-design change was required.


## 66.44 Dashboard undefined legend fix

The dashboard distribution bar charts showed an `undefined` legend item because the shared Chart.js `addChart()` helper enabled legends globally while its single dataset had no `label`. The helper now keeps legends enabled for doughnut charts, whose legends correctly use their category labels, and explicitly disables legends for the single-dataset bar charts (branches, categories, types, and services). This removes the undefined legend without changing chart data or analytics. Regression and build verification are pending for this fix.


## 66.45 Dashboard legend regression coverage

Regression coverage was added to `DashboardTest` to ensure the shared Chart.js helper contains the explicit single-dataset bar-chart legend suppression. This protects the fix against future changes that could reintroduce an `undefined` legend item. Dashboard, full-suite, build, and diff verification are pending for this fix.


## 66.46 Verified dashboard undefined-legend fix

Final verification for the dashboard undefined-legend fix completed on 2026-08-27. The dashboard-focused suite passed with 3 tests and 12 assertions, including the bar-chart legend regression. The full PHPUnit suite passed with 45 tests and 211 assertions. Blade view caching, `npm.cmd run build`, and `git diff --check` also passed. No schema, route, permission, or database-design change was required.


## 66.47 Footer localization in progress

The shared layout footer implementation has started with English localization keys for contact, phone, location, social links, LinkedIn, Facebook, and the copyright message. The Arabic equivalents and shared layout markup remain to be added before verification is complete.


## 66.48 Arabic footer localization

Arabic footer localization is now defined for the same contact, phone, location, social-link, LinkedIn, Facebook, and copyright concepts. The shared layout footer markup remains to be added before verification is complete.


## 66.49 English footer contact values

The English footer contact values are now localized as `Abdelrahman Emad`, `01110174868`, and `Alexandria`, alongside the footer labels and social/copyright keys. The Arabic value equivalents and shared layout markup remain to be completed and verified.


## 66.50 Arabic footer contact values

Arabic footer contact values are now localized as `عبدالرحمن عماد`, `01110174868`, and `الإسكندرية`, matching the user-provided contact details. The shared layout footer markup remains to be completed and verified.


## 66.51 Shared contact and copyright footer

The shared `resources/views/layouts/app.blade.php` now includes a responsive footer on all layout pages. It displays the localized contact name, clickable phone number, Alexandria location, LinkedIn and Facebook links opening safely in a new tab, and a localized copyright line using the current year. The page shell uses a flex column with a flexible main area so the footer sits at the bottom on short pages. Footer regression and build verification are pending.


## 66.52 Shared footer regression coverage

`PwaTest` now covers the shared footer on the authenticated dashboard in both locales, asserting the provided contact name, city, phone link, LinkedIn/Facebook URLs, and copyright text. Footer and full verification are pending.


## 66.53 Verified shared contact and copyright footer

Final verification for the shared contact and copyright footer completed on 2026-08-27. `PwaTest` passed with 5 tests and 42 assertions, including English/Arabic footer content and contact links. The full PHPUnit suite passed with 46 tests and 222 assertions. Blade view caching, `npm.cmd run build`, and `git diff --check` passed. The footer displays the provided contact name, phone, Alexandria location, LinkedIn/Facebook links, and current-year copyright text. No schema, route, permission, or database-design change was required.


## 66.54 Compact footer revision

The footer was revised per the latest UI request: the location column was removed, the layout now uses two compact columns for contact and social links, vertical spacing was reduced, and the copyright row was shortened. The footer remains localized and responsive. Verification is pending for this revision.


## 66.55 Compact footer regression coverage

`PwaTest` was updated for the compact footer revision: it now verifies the contact and social links remain present in English and Arabic while Alexandria/الإسكندرية and the location labels are absent. Focused and full verification are pending.


## 66.56 Verified compact footer revision

Final verification for the compact footer revision completed on 2026-08-27. `PwaTest` passed with 5 tests and 44 assertions, verifying contact/social content and the absence of the location section in English and Arabic. The full PHPUnit suite passed with 46 tests and 224 assertions. Blade view caching, `npm.cmd run build`, and `git diff --check` passed. The footer is now a compact two-column contact/social layout with a shortened copyright row; no location content is rendered. No schema, route, permission, or database-design change was required.


## 66.57 Minimal horizontal footer revision

The footer was compacted again per the latest UI request. The contact name, phone, social links, and copyright now share one responsive horizontal bar with minimal padding and wrapping only when the viewport is narrow. The location section remains removed, and localization, dark mode, and RTL behavior are preserved. Verification is pending for this revision.


## 66.58 Minimal footer structure regression coverage

`PwaTest` now also checks the minimal footer structure: the shared layout uses a flex-wrapping horizontal bar with compact padding and no location translation usage. Focused and full verification are pending for this revision.


## 66.59 Verified minimal horizontal footer

Final verification for the minimal horizontal footer completed on 2026-08-27. `PwaTest` passed with 5 tests and 48 assertions, including contact/social content, the absence of location content, and the compact layout structure. The full PHPUnit suite passed with 46 tests and 228 assertions. Blade view caching, `npm.cmd run build`, and `git diff --check` passed. The footer now uses one compact responsive bar with minimal padding and no rendered location section. No schema, route, permission, or database-design change was required.


## 66.60 Quality Visits module - implemented status

A new **Quality Visits** (quality inspection) module was added as a sibling module to the existing complaint system. This is outside the original complaint-focused requirements but was requested as a new feature; this addendum records the implemented status.

Implemented:
- `visitors_`-prefixed schema (visit types, sections, root causes, checklist items, visits, visit items with snapshot, visit photos, CAPA actions, CAPA updates).
- `Visitor*` models, `app/Services/Visitors/*` services (visit scoring, submission, CAPA, photo compression, checklist import), controllers under `app/Http/Controllers/Visitors`, `VisitorVisitPolicy`, `visitors.` routes, visitors seeders, bilingual `visitors.php` dictionaries, and `visitors/` Blade views with vanilla-JS autosave and live score.
- Rules: items start `pending` with `visited_at` null (nothing chosen by default — the inspector must actively pick Compliant/Non-compliant/Not Applicable per item); progress from `visited_at`; backend-only score (available minus NC deduction over non-NA items) with blue/green/yellow/red classes; owner-only in-progress access; photos required for critical/photo-required NC items and served through an authenticated route; submission is transactional, rejects unreviewed items or missing required photos, creates CAPA from NC items, and sets `completed`/`completed_at`.

Deferred/out of scope for this task: CAPA management screens/VCAP workflow. Verification on 2026-08-31 passed with **60 tests / 296 assertions** (46 existing + 14 new `tests/Feature/VisitorsTest.php`); migrations and seeders run against SQL Server and the SQLite test DB. `memory.md`, `project_structure.md`, and `database_design.md` were synchronized with this module.

## 66.61 Quality Visits Reports module - implemented status

A **Quality Visits Reports** module was built on top of the inspection module, reusing its data, scoring, and authorization without duplicating business logic:

- **Report list** (`visitors/reports`): paginated completed visits with DB-aggregated score/violation counts and filters (branch, visit type, inspector, date range, score color). Score percentiles are derived in SQL via a `HAVING` filter using repeated aggregate expressions (SQL Server cannot reference select aliases in `HAVING`).
- **Individual report** (`visitors/reports/{visit}`): QHSE-style report with header, visit info, score summary, non-compliances by severity, performance by section, root-cause analysis, violation details with evidence thumbnails/lightbox, and an action plan of CAPA records. All historical values come from the immutable `visitors_visit_items` snapshot columns (`item_code`, `item_title`, `section_name`, `severity`, `deduction_score`, immediate/corrective/preventive actions, responsible, deadline), never the master checklist.
- **Analytics dashboard** (`visitors/reports/dashboard`): summary cards, severity/section/root-cause distributions, per-branch weighted score comparison plus best/worst branches, recurring and critical violations, CAPA status analytics with overdue splitting and average time-to-close (computed in PHP for cross-DB compatibility), inspector performance, and a day/week/month score-trend chart. Aggregations use SQL joins/groupBy/HAVING rather than loading rows into PHP.
- **PDF export** (`visitors/reports/{visit}/pdf`): `barryvdh/laravel-dompdf` renders a self-contained-CSS print view that embeds private evidence photos (resolved to absolute paths) — ready for email/WhatsApp.
- **Authorization**: reports are **not** owner-restricted. `VisitorVisitPolicy::viewReport()` requires a completed visit and (`visit.manage` OR (`report.view` AND owner)); a new `visitors.reports` Gate grants list/dashboard access to anyone with `report.view` OR `visit.manage`; Super Admin bypasses via the existing `Gate::before`. Customer Support (inspectors) is denied.
- **Reuse**: `VisitScoreService` stays the single source of score truth (`fromAggregates()` now used by `calculate()`, the list, and charts); `VisitorCapaAction::effectiveStatus()` provides overdue status reused by views and analytics.
- **Frontend**: Chart.js renders the `#report-*`-prefixed canvases with theme-refresh support wired in `resources/js/app.js`; a home Reports card link was added; bilingual keys added to `lang/en|ar/visitors.php`.

Verification on 2026-08-31 passed with **76 tests / 433 assertions** (61 existing + 15 new `tests/Feature/VisitorReportsTest.php`); report list filtering, individual report, PDF (881 KB), and dashboard analytics were also smoke-tested against live SQL Server. `memory.md` and `project_structure.md` were synchronized with this module; no schema changes were introduced (reports read existing tables).

## 66.62 Quality Visits Master Data (Excel) module - implemented status

A **Master Data management UI** was added to the Quality Visits module so administrators can manage the inspection checklist (items/notes, sections, severity, actions, deduction) through Excel: download a template/example, download the current master data in the same importable format, upload `.xlsx`/`.xls`, preview with per-row validation, then confirm or cancel. It reuses the existing inspection master tables and does not touch complaints or visits.

- **Reused masters (no breakage):** `visitors_visit_types` = inspection types, `visitors_sections` = sections, `visitors_root_causes` = root causes, `visitors_checklist_items` = items. Only genuinely-new tables were added for this feature. Historic `visitors_visit_items` snapshots are never overwritten (severity on master is a stable string; visits snapshot it at creation).
- **New schema** (migration `2026_08_31_180000_create_visitors_master_import_tables.php`): `visitors_severities` (Critical/Major/Minor master, seeded by `VisitorsSeeder`), `visitors_imports` (auditable import header + status), `visitors_import_rows` (per-row raw data + validation errors), and a nullable `root_cause_id` FK on `visitors_checklist_items` (suggested/default root cause; the inspector still chooses at NC time).
- **Severity independent of deduction:** deduction is configured per inspection item and never derived from the severity name; `photo_required` = `severity === 'critical'`.
- **Import rules:** `.xlsx`/`.xls` only, 50 MB max, real MIME verified (not just extension). Exact 11-column order (as of 2026-09-02): `code, inspection_type, section, note, severity, immediate_action, corrective_action, responsible, period, preventive_action, deduction_score`. `root_cause` is no longer a file column (the inspector chooses it at NC time); `note` is the checklist item text (Arabic ملاحظة) and `deduction_score` the configured deduction. Logical unique key = `(inspection_type code, item code)`; create/update only — no destructive deletes. Any invalid row rejects the entire import on `confirm()`. Duplicate `(type, code)` within one file is rejected.
- **Implementation:** `VisitorMasterDataService` (validate → chunk-read via `MasterDataChunkReadFilter` in 200-row slices → validateRows by code-or-name lookups with forced lowercase → storeImport persists preview (no DB mutation) at status `ready` → confirm runs a transactional create/update up-sert and refuses when `invalid_rows > 0` → cancel). Exports: `VisitorMasterTemplateExport` (Template sheet + "Example (Sample Data)" sheet), `VisitorCurrentMasterDataExport` (FromQuery+Mapping honoring type/section filters).
- **Routes**: `visitors/master-data*` (index, template, template-example, download, import, import/{import}/preview/confirm/cancel) registered inside the `visitors.` prefix group BEFORE the `{visit}` catch-all so models don't bind to the wildcard.
- **Authorization**: new permissions `visit.master.view`, `visit.master.import`, `visit.master.export`. Admin receives all three (`syncPermissions` minus role.delete/user.delete/audit.view); Super Admin bypasses via the existing `Gate::before`; Customer Support (inspectors) is denied. `visit.manage` (or ownership) also allows confirming/cancelling another user's staged import.
- **UI/localization**: `visitors/master-data/{index,preview}.blade.php` with stats cards (total/daily/monthly/safety/sections/last-import), the four action buttons, filters, current-data table, upload form, import history, and an import-preview confirmation screen; a home Master Data card gated by `@can('visit.master.view')`; `master_*` bilingual (en/ar, Arabic RTL) keys added to `visitors.php`.
- **Verification**: added `tests/Feature/VisitorMasterDataTest.php` (15 tests: role gating, template/current-data xlsx downloads, oversized/wrong-extension upload rejection, duplicate+invalid-type+negative-deduction detection, preview persists without mutation, confirm create/update keyed by type+code, invalid rows block confirm, cancel, history/statuses). Full suite: **91 tests passed / 479 assertions**. Migration + `VisitorsSeeder` verified against live SQL Server (3 severities seeded); routes confirmed via `php artisan route:list`. `memory.md`, `project_structure.md`, and `database_design.md` were synchronized.

## 66.63 Mandatory Critical Evidence (photo) - implemented status

When an inspection item with **Critical** severity is marked **Non-Compliant (NC)**, the inspector MUST upload at least one evidence photo before the visit can be submitted/completed. The rule is severity-based only (`severity === 'critical'`), independent of the configured deduction; Major/Minor/non-Critical severities keep photos optional. Draft/resume is allowed without the photo — only submission is blocked.

- **Backend rule:** `VisitorVisitItem::isCritical()` lowercases severity and compares to `'critical'`; `requiresPhoto()` returns `($status === 'nc' && isCritical()) || photo_required`. `VisitService::submit()` throws `RuntimeException(__('visitors.evidence_photo_required'))` for any Critical NC item that has no photo; `VisitController::submit()` flashes that message as `error` via `back()->with('error', $e->getMessage())`.
- **Photo rules (unchanged):** `VisitorPhotoService` enforces 20 MB max original size, real MIME + content (magic-byte) check, JPG/JPEG/PNG/WEBP only (others rejected), compression after upload, secure UUID filenames, and private `local` Storage disk (no base64). Existing backend enforcement and tests remain in place.
- **Frontend (`show.blade.php`):** each item renders `data-critical="1|0"`; Critical items show `📷 Evidence *` (red asterisk) plus a `photo_required_critical` message (`photo-required-msg`) and `aria-required` on the file input. The message appears when the item is NC and toggles dynamically as the inspector changes status. The `#submit-visit-form` submit handler first scans every Critical+NC item and, before the confirmation dialog, blocks with `alert(__('visitors.evidence_photo_required'))` whenever one lacks a photo; only then confirms.
- **Localization:** added after `photo_help` in both `lang/en/visitors.php` and `lang/ar/visitors.php`: `photo_required_critical` ("Photo is required for Critical violations" / «الصورة مطلوبة للمخالفات الحرجة») and `evidence_photo_required` ("A photo/evidence is required for Critical non-compliant items." / «صورة/دليل مطلوبة لعناصر عدم الامتثال الحرجة.»). Arabic RTL is handled by the shared bilingual key mechanism.
- **Verification:** `test_reviewed_nc_critical_without_photo_blocks_submission` and `test_critical_nc_item_requires_photo_on_submit` now assert `assertSessionHas('error', __('visitors.evidence_photo_required'))`; the non-Critical NC submit path remains successful without a photo (`test_submission_creates_capa_and_completes_visit`). Full suite: **91 tests passed / 479 assertions** (`php artisan test`, SQLite in-memory). This was a pure view/JS/message/assertion change — no schema or service migration; no live SQL Server schema changes were required.

## 66.64 Shared shell/PWA + Master-data Excel format — 2026-09-02 update

On 2026-09-02 the shared authenticated shell and the Quality Visits master-data Excel format were updated. Full suite after this work: **91 tests passed / 479 assertions**.

- **Custom Install App button & PWA installability:** `resources/views/layouts/app.blade.php` gained a desktop header `data-install-button` (`hidden sm:inline-flex`, indigo + download icon), a full-width mobile-drawer `data-install-button-mobile` button, and a bilingual `data-install-modal` (Android + iOS add-to-home-screen instructions) rendered before the scripts stack. `resources/js/app.js` captures `beforeinstallprompt`, detects standalone mode (`display-mode: standalone`) and iOS, reveals the buttons, and falls back to the instructions modal whenever no deferred prompt exists (the mobile button is always revealed on non-iOS because Android over plain HTTP — e.g. `http://<LAN-IP>` — never fires `beforeinstallprompt`), then hides on `appinstalled`. New install keys (`install_app`, `add_to_home_screen`, `install_instructions_title`, `install_instructions_android`, `install_instructions_ios`, `install_now`, `install_close`) were added to both `lang/en|ar/common.php` (221 keys each locale). The Arabic file was repaired via a PHP patch script after a PowerShell `Set-Content` double-encoded it; lang edits now use .NET `File::ReadAllText/WriteAllText` with UTF-8 no-BOM.
- **Arabic branding:** hardcoded "Complaint Desk" replaced by `__('common.application_name')` (`en` = Complaint Desk, `ar` = نظام الشكاوى) in the head meta/apple-web-app title, the `<title>`, the desktop header, and the mobile drawer header.
- **Navbar RTL overlap fix:** the desktop/hamburger breakpoint moved from `lg` (1024px) to `xl` (1280px) — nav is `hidden min-w-0 flex-1 items-center justify-center xl:flex`, toggle + drawer are `xl:hidden`, and the JS resize check uses `matchMedia('(min-width: 1280px)')`; the logo and controls are `shrink-0`/`whitespace-nowrap` so the Arabic brand never collides with menu items. The user name appears from `xl:inline` and the avatar is `xl:hidden`.
- **Nav/drawer animations:** `resources/css/app.css` animates the mobile drawer (transform 360ms cubic-bezier + opacity 260ms, `will-change`), the overlay (300ms fade), staggered `navItemIn` keyframes at 40ms increments, the hamburger-icon rotate/crossfade (200ms), and desktop `[data-nav-dropdown]` fade/translate/scale on `[open]`, all disabled under `prefers-reduced-motion`; the drawer open uses a `requestAnimationFrame` double-tick and close is delayed 360ms. Mobile drawer dark-mode contrast overrides (panel `#1e293b`, active `bg-#3730a3`, borders `#334155`) were added.
- **Master-data Excel format change:** the file format went from 12 to **11 columns** — `root_cause` removed (chosen by the inspector at NC time; the DB `root_cause_id` on `visitors_checklist_items` remains as the suggested/default value), `item` renamed `note` (ملاحظة), `deduction` renamed `deduction_score`. Applied uniformly through the `HasMasterDataColumns` trait to the template, example, and current-data downloads, and through the `VisitorMasterDataService` header-scan (`A1:K1`), normalize/validate (`note` required, `deduction_score` numeric, no root-cause lookup), and confirm pipeline (`title`, `deduction_score`, `photo_required`; no longer sets `root_cause_id`). Master-data table and preview labels became Note / ملاحظة and Deduction Score / درجة الخصم. Files carrying the legacy 12-column headers are intentionally rejected (they no longer match the known header list) — re-download the template/current file to edit.
- **PhpSpreadsheet 4.x compatibility bug fix:** the installed `phpoffice/phpspreadsheet` vendor files are v4 while `composer.lock` pins 1.30.6; v4 moved `PhpOffice\PhpSpreadsheet\Reader\IOFactory` to `PhpOffice\PhpSpreadsheet\IOFactory` and removed `Worksheet::disconnectWorksheets()`. `VisitorMasterDataService` still used the old import and the removed call, so **every** upload (not only a downloaded file) threw into the controller catch-all "Unable to read the Excel file. Please check the column headers and format." at `VisitorMasterDataController.php:100`. Both call sites were fixed and the now-unused `lookupRootCauseMap()` removed.
- **Verification:** a round-trip probe regenerated the template + current-data exports and re-read them with the service — template parses (0 rows), current file reads all rows and validates **17 valid / 0 invalid**; headings match in the template, example, and current-data sheets. Tests updated to the new keys (15 `VisitorMasterDataTest` cases). Full suite: **91 tests passed / 479 assertions**; assets rebuilt via `npm run build` + `php artisan view:clear`. `memory.md`, `project_structure.md`, and `database_design.md` were synchronized with this update.

## 66.65 Quality Visits single-page New Visit form - 2026-09-02

The new-visit flow was reduced from two pages to **one page** to make starting an inspection faster on phones.

- **Before:** GET `/visitors/create` presented inspection-type cards; clicking a card went to GET `/visitors/create/{visitType}` (`setup`) where the inspector picked branch and date and then submitted POST `/visitors`.
- **After:** GET `/visitors/create` renders a single card form: **Visit Type** (dropdown showing name + code), **Branch** (dropdown), **Inspector** (read-only = authenticated user, unchanged), and **Visit date** (defaults to today). Submission goes directly to the unchanged POST `/visitors` (`StartVisitRequest` still validates `visit_type_id` active, `branch_id` active, `visit_date`). The `setup()` method on `VisitController`, the `visitors/create/{visitType}` route, and `visitors/setup.blade.php` were removed; the `visitors/home`, `visitors/open`, and `visitors/reports/*` "New Visit" buttons already pointed at `visitors.create` so no callers changed.
- **Verification:** added `test_create_page_shows_single_form_with_type_branch_and_date` (asserts the type/branch/date controls on `GET visitors.create`). Full suite: **92 tests passed / 489 assertions**. `memory.md` and `project_structure.md` were synchronized with this change.

## 66.66 New-visit items no longer default to Compliant - 2026-09-02

Requested on the inspection page (`/visitors/{visit}`): a brand-new visit must have **nothing chosen** until the inspector picks a status — no implicit Compliant selection.

- **Before:** `VisitService::start()` pre-created every item with `status = 'ok'` and `visited_at = now()`, so all items looked Compliant/reviewed immediately and `0/N` was never the starting progress.
- **After:** items are created `status = 'pending'`, `visited_at = null`. Nothing is highlighted, reviewed, or scored until the inspector taps Compliant / Non-compliant / Not Applicable (the NC details panel stays hidden; `submit()` already rejects unreviewed items). `isReviewed()` still keys off `visited_at`; score/report logic is untouched and only runs on completed visits where every item has an explicit choice. Migration default comment updated to `// pending | ok | nc | na`.
- **Tests:** default-state, progress, and reports helpers updated to model real completed visits (non-NC items marked `ok` + `visited_at`). Full suite: **92 passed / 489 assertions**.

## 66.67 Report tables layout fix - 2026-09-02

Fixed `/visitors/reports/{visit}` — the **Corrective Action Plan** table's words overlapped due to 10 un-sized columns and no wrapping; the header `visitors.due_date` had no translation (showed as `VISITORS.DUE_DATE`). Change: added `due_date` (Due Date / تاريخ الاستحقاق) in `lang/en|ar/visitors.php`, gave CAPA `min-w-[1150px]` with per-column `min-w` and `break-words whitespace-normal` wrapping plus `text-center`/`px-2` on every report table (Performance by Section, Violation Details, CAPA, etc.) so body text is centered like the headers as requested and Arabic action text wraps instead of overlapping (`show.blade.php` + matching `print.blade.php:6,16,17` centering). Verification: `view:clear` + `VisitorReportsTest` (15 passed).

## 66.68 Per-item CAPA Close workflow on the report page - 2026-09-02

Completed the open→closed workflow directly on the report page per request — **single-state, per-row Close, not "close all"**. New `VisitorCapaController@close` (`POST visitors/capa/{capaAction}/close`) and `update` (`PATCH visitors/capa/{capaAction}`, `status in open,in_progress,closed,rejected`) authorized via `viewReport` on the visit, calling `CapaService::recordUpdate` and setting `completed_at` on close. `reports/show.blade.php` shows a per-row **Close** button under the status badge only when `effectiveStatus` is `open`/`in_progress`/`overdue` (confirm `capa_close_confirm`), with `success`/`info` flash and `print:hidden`. Routes `visitors.capa.close` / `visitors.capa.update`. Lang `close`, `capa_close_confirm`, `capa_closed_success`, `capa_already_closed`, `capa_updated_success`. `questions_and_answers.md` Q4 updated. **Follow-up fix:** removed duplicate `CAPA action closed successfully.` banner (layout `app.blade.php:117` already rendered `success` globally) and duplicate `Inspection Visit Report` heading — the top page header now keeps only `← Reports` + `Print / PDF`, the report header remains the single `Inspection Visit Report` title; `info` flash moved to the layout. Verification: `view:clear` + **92 passed / 489 assertions**.

## 66.69 Roles edit page localization - 2026-09-02

Localized `roles/{role}/edit` (`resources/views/roles/form.blade.php:1`) which showed raw permission names (`customer.view` …). Added `lang/en|ar/permissions.php` (every seeder permission + `group_*` headers) and grouped the form by module (sorted customer→visit) with localized section headers (`permissions.group_*`) and permission labels (`permissions.customer.view` = View Customers / عرض العملاء), keeping the raw name as the checkbox value so `syncPermissions()` is unchanged; `Super Admin` stays read-only and a Cancel button (`common.cancel`) was added. No schema/route change; `view:clear` + **92 passed / 489 assertions**.
