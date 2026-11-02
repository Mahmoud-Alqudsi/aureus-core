---
status: verified
source_of_truth: source-code
last_verified: 2026-10-26
scope: plugins
confidence: high
---

# Aureus ERP — Plugin Documentation Index

## Overview

Aureus ERP is structured around 28 domain-specific local plugins residing under `plugins/webkul/`. Each plugin operates as a local package extending the core plugin architecture provided by `plugin-manager`.

This file is the **Single Source of Truth (SSoT)** for the 28-plugin catalog, core/optional classification, runtime plugin dependencies, migration counts, and automated test coverage status. Individual plugin specifications in this directory detail each plugin's service provider lifecycle, models, database schemas, Filament UI components, security policies, commands, and domain boundaries.

### Counting Methodology (`plugins/webkul/<plugin>/`)
- **Automated Test Coverage (`11` Tested / `17` Untested Plugins)**:
  - **Executable Test Classes (`*Test.php`)**: **189 files** across `plugins/webkul/*/tests/Feature/`.
  - **Total PHP Files in `tests/` (`tests/**/*.php`)**: **201 files** (`189` `*Test.php` classes + `12` shared helper files under `tests/Helpers/*Helper.php`).
- **Database Migrations (`22` Schema-Owning / `6` Zero-Table Plugins)**:
  - Reported as `disk` (`database/migrations/*.php` files on disk) and `registered` (`$package->hasMigrations([...])` entries in `*ServiceProvider.php`), with Spatie settings migrations in `database/settings/*.php` (`$package->hasSettings([...])`) noted separately as `(+N settings)`.

## Plugin Registry, Dependencies, Migrations & Test Coverage

| Plugin Slug | Name | State | Documented | Responsibility | Install Plugins Dependency | Dependents | Migrations (Disk / `hasMigrations`) | Test Coverage (`*Test.php` / Total `tests/*.php`) | File Link |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `plugin-manager` | Plugin Manager | Core | Yes | Package discovery, lifecycle, installer, and CLI orchestration | None (`—`) | `—` | `1` / `0` (loaded via `support`) | Untested (`0` — no `tests/`) | [`plugin-manager.md`](plugin-manager.md) |
| `analytics` | Analytics | Core | Yes | Foundational `analytic_records` ledger schema and model | None (`—`) | `—` | `1` / `1` | Untested (`0` — no `tests/`) | [`analytics.md`](analytics.md) |
| `chatter` | Chatter | Core | Yes | Universal audit trail, social feed, activities, followers, and mentions | None (`—`) | `—` | `4` / `4` | Untested (`0` — no `tests/`) | [`chatter.md`](chatter.md) |
| `fields` | Custom Fields | Core | Yes | Dynamic schema mutation, DDL management, and runtime UI field injection | None (`—`) | `—` | `1` / `1` | Untested (`0` — no `tests/`) | [`fields.md`](fields.md) |
| `full-calendar` | Calendar | Core | Yes | FullCalendar JavaScript engine, base widget, and action integration | None (`—`) | `—` | `0` / `0` (Zero-Table) | Untested (`0` — no `tests/`) | [`full-calendar.md`](full-calendar.md) |
| `partners` | Partners | Core | Yes | Central party, address, company, contact, and bank master data hub | None (`—`) | `—` | `8` / `8` | **Tested** (`10` `*Test.php` / `10` total) | [`partners.md`](partners.md) |
| `security` | Security | Core | Yes | Authentication, Bouncer authorized user ID resolution, and OwnershipScope | None (`—`) | `—` | `10` / `10` (`+2` settings) | Untested (`0` — no `tests/`) | [`security.md`](security.md) |
| `support` | Support | Core | Yes | CompanyContext, SequenceService, UOM, currencies, and shared infrastructure | None (`—`) | `—` | `34` / `35` (`+1` settings) | **Tested** (`15` `*Test.php` + `5` helpers = `20` total) | [`support.md`](support.md) |
| `table-views` | Table Views | Core | Yes | User-customized filter presets, favorite views, and table tab hooks | None (`—`) | `—` | `2` / `2` | Untested (`0` — no `tests/`) | [`table-views.md`](table-views.md) |
| `accounting` | Accounting | Optional | Yes | Financial reporting, statement generation, analytics dashboard, and navigation clusters | `accounts` | `—` | `0` / `0` (Zero-Table) | **Tested** (`8` `*Test.php` + `1` helper = `9` total) | [`accounting.md`](accounting.md) |
| `accounts` | Accounts | Optional | Yes | Invoicing, payments, taxes, and fiscal journals | `products` | `accounting`, `invoices`, `payments` | `71` / `70` (`+3` settings) | **Tested** (`43` `*Test.php` + `1` helper = `44` total) | [`accounts.md`](accounts.md) |
| `barcode` | Barcode | Optional | Yes | Barcode scanning and hardware integration | `inventories` | `—` | `0` / `0` (Zero-Table) | Untested (`0` — no `tests/`) | [`barcode.md`](barcode.md) |
| `blogs` | Blogs | Optional | Yes | Content management, blog posts, and articles | `website` | `—` | `7` / `7` | Untested (`0` — no `tests/`) | [`blogs.md`](blogs.md) |
| `contacts` | Contacts | Optional | Yes | Lightweight contact book alias layer | `—` | `—` | `0` / `0` (Zero-Table) | Untested (`0` — no `tests/`) | [`contacts.md`](contacts.md) |
| `employees` | Employees | Optional | Yes | Employee directories, departments, and work history | `—` | `recruitments`, `time-off` | `22` / `19` | **Tested** (`5` `*Test.php` + `1` helper = `6` total) | [`employees.md`](employees.md) |
| `inventories` | Inventory | Optional | Yes | Warehouse stock, transfers, deliveries, receipts, and scrap | `products` | `barcode`, `manufacturing` | `57` / `57` (`+4` settings) | **Tested** (`40` `*Test.php` + `1` helper = `41` total) | [`inventories.md`](inventories.md) |
| `invoices` | Invoices | Optional | Yes | Standalone customer invoices and vendor bill tracking | `accounts` | `purchases`, `sales` | `0` / `0` (Zero-Table) | Untested (`0` — no `tests/`) | [`invoices.md`](invoices.md) |
| `maintenance` | Maintenance | Optional | Yes | Equipment maintenance requests, stages, and teams | `—` | `—` | `6` / `6` | Untested (`0` — no `tests/`) | [`maintenance.md`](maintenance.md) |
| `manufacturing` | Manufacturing | Optional | Yes | Bill of materials (BOM), work orders, and manufacturing orders | `products`, `inventories` | `—` | `37` / `37` (`+2` settings) | **Tested** (`7` `*Test.php` + `1` helper = `8` total) | [`manufacturing.md`](manufacturing.md) |
| `payments` | Payments | Optional | Yes | Payment gateway integration and transaction processing | `accounts` | `sales` | `6` / `6` | Untested (`0` — no `tests/`) | [`payments.md`](payments.md) |
| `products` | Products | Optional | Yes | Product catalog, variants, categories, and attributes | `—` | `accounts`, `inventories`, `manufacturing` | `18` / `18` (`+2` settings) | **Tested** (`19` `*Test.php` / `19` total) | [`products.md`](products.md) |
| `projects` | Projects | Optional | Yes | Project management, task tracking, and milestone planning | `—` | `timesheets` | `12` / `12` (`+2` settings) | **Tested** (`8` `*Test.php` / `8` total) | [`projects.md`](projects.md) |
| `purchases` | Purchases | Optional | Yes | Purchase orders, RFQs, vendor management, and requisition | `invoices` | `—` | `19` / `19` (`+2` settings) | **Tested** (`15` `*Test.php` + `1` helper = `16` total) | [`purchases.md`](purchases.md) |
| `recruitments` | Recruitment | Optional | Yes | Job positions, applicant pipelines, and interviews | `employees` | `—` | `14` / `14` | Untested (`0` — no `tests/`) | [`recruitments.md`](recruitments.md) |
| `sales` | Sales | Optional | Yes | Quotations, sales orders, teams, and pricelists | `invoices`, `payments` | `—` | `26` / `26` (`+4` settings) | **Tested** (`19` `*Test.php` + `1` helper = `20` total) | [`sales.md`](sales.md) |
| `time-off` | Time Off | Optional | Yes | Leave allocations, requests, and approval workflows | `employees` | `—` | `10` / `9` | Untested (`0` — no `tests/`) | [`time-off.md`](time-off.md) |
| `timesheets` | Timesheets | Optional | Yes | Timesheet entry and project time allocation | `projects` | `—` | `0` / `0` (Zero-Table) | Untested (`0` — no `tests/`) | [`timesheets.md`](timesheets.md) |
| `website` | Website | Optional | Yes | Public portal, web pages, and customer authentication | `—` | `blogs` | `4` / `4` (`+1` settings) | Untested (`0` — no `tests/`) | [`website.md`](website.md) |

*Note: Core plugins are defined by `Package::isCore()` in their service provider. Optional plugins are registered in `bootstrap/providers.php` and gated by runtime installation checks (`Package::isInstalled()`).*

## Cross-Plugin Architectural Invariants

- [VERIFIED] All 28 plugins (9 Core + 19 Optional) are represented and verified against source code in `plugins/webkul/`, with core status checked against `->isCore()` in each plugin's `PackageServiceProvider::configureCustomPackage()`.
- [VERIFIED] The Install Plugins Dependency (`Package::hasDependencies([...])`) is strictly distinguished from Composer requirements (`composer.json`) and from code-level consumption. None of the 9 Core Plugins declare an Install Plugins Dependency. The Dependents column is the reverse of the Install Plugins Dependency column across all 28 plugins.
- [VERIFIED] Panel participation is verified per plugin rather than assumed to be admin-only: `blogs`, `purchases`, and `website` participate in the Customer panel; all others operate under the Admin panel or backend service layers.
- [VERIFIED] Single-database multi-company isolation (`CompanyContext`, `CompanyScope`, `BelongsToCompany`, `BelongsToCompanies`), ownership scoping (`OwnershipScope`, `HasOwnershipScope`), and the local `Webkul\Security\Bouncer` service are consistently verified across all models.
- [VERIFIED] No queue Job classes exist in the repository; asynchronous notifications and scheduled console commands are documented where implemented.
- [VERIFIED] Database schema ownership and migrations align with the domain ERDs (`core.md`, `finance.md`, `operations.md`). Plugins with zero dedicated tables (`accounting`, `barcode`, `contacts`, `full-calendar`, `invoices`, `timesheets`) map to their underlying storage engines.
- [VERIFIED] Plugin specifications provide plugin-local architectural maps while deferring system-wide invariants to the canonical architecture, security, and database documents.

