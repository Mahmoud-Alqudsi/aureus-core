---
status: verified
source_of_truth: source-code
last_verified: 2026-08-28
scope: plugins
confidence: high
---

# Aureus ERP — Plugin Documentation Index

## Overview

Aureus ERP is structured around 28 domain-specific local plugins residing under `plugins/webkul/`. Each plugin operates as a local package extending the core plugin architecture provided by `plugin-manager`.

This directory contains individual documentation for each plugin, detailing its purpose, service provider lifecycle, models, database schemas, Filament UI components, security policies, commands, data flows, business rules, and dangerous areas.

## Plugin Registry & Documentation Status

| Plugin Slug | Name | State | Documented | Responsibility | Runtime Plugin Dependencies | File Link |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `plugin-manager` | Plugin Manager | Core | Yes (Phase 5) | Package discovery, lifecycle, installer, and CLI orchestration | None (`—`) | [`plugin-manager.md`](plugin-manager.md) |
| `analytics` | Analytics | Core | Yes (Phase 5) | Foundational `analytic_records` ledger schema and model | None (`—`) | [`analytics.md`](analytics.md) |
| `chatter` | Chatter | Core | Yes (Phase 5) | Universal audit trail, social feed, activities, followers, and mentions | None (`—`) | [`chatter.md`](chatter.md) |
| `fields` | Custom Fields | Core | Yes (Phase 5) | Dynamic schema mutation, DDL management, and runtime UI field injection | None (`—`) | [`fields.md`](fields.md) |
| `full-calendar` | Calendar | Core | Yes (Phase 5) | FullCalendar JavaScript engine, base widget, and action integration | None (`—`) | [`full-calendar.md`](full-calendar.md) |
| `partners` | Partners | Core | Yes (Phase 5) | Central party, address, company, contact, and bank master data hub | None (`—`) | [`partners.md`](partners.md) |
| `security` | Security | Core | Yes (Phase 5) | Authentication, Bouncer authorized user ID resolution, and OwnershipScope | None (`—`) | [`security.md`](security.md) |
| `support` | Support | Core | Yes (Phase 5) | CompanyContext, SequenceService, UOM, currencies, and shared infrastructure | None (`—`) | [`support.md`](support.md) |
| `table-views` | Table Views | Core | Yes (Phase 5) | User-customized filter presets, favorite views, and table tab hooks | None (`—`) | [`table-views.md`](table-views.md) |
| `accounting` | Accounting | Optional | Yes (Phase 6) | Financial reporting, statement generation, analytics dashboard, and navigation clusters | `accounts` | [`accounting.md`](accounting.md) |
| `accounts` | Accounts | Optional | Yes (Phase 6) | Invoicing, payments, taxes, and fiscal journals | `products` | [`accounts.md`](accounts.md) |
| `barcode` | Barcode | Optional | Yes (Phase 6) | Barcode scanning and hardware integration | `inventories` | [`barcode.md`](barcode.md) |
| `blogs` | Blogs | Optional | Yes (Phase 6) | Content management, blog posts, and articles | `website` | [`blogs.md`](blogs.md) |
| `contacts` | Contacts | Optional | Yes (Phase 6) | Lightweight contact book alias layer | `—` | [`contacts.md`](contacts.md) |
| `employees` | Employees | Optional | Yes (Phase 6) | Employee directories, departments, and work history | `—` | [`employees.md`](employees.md) |
| `inventories` | Inventory | Optional | Yes (Phase 6) | Warehouse stock, transfers, deliveries, receipts, and scrap | `products` | [`inventories.md`](inventories.md) |
| `invoices` | Invoices | Optional | Yes (Phase 6) | Standalone customer invoices and vendor bill tracking | `accounts` | [`invoices.md`](invoices.md) |
| `maintenance` | Maintenance | Optional | Yes (Phase 6) | Equipment maintenance requests, stages, and teams | `—` | [`maintenance.md`](maintenance.md) |
| `manufacturing` | Manufacturing | Optional | Yes (Phase 6) | Bill of materials (BOM), work orders, and manufacturing orders | `products`, `inventories` | [`manufacturing.md`](manufacturing.md) |
| `payments` | Payments | Optional | Yes (Phase 6) | Payment gateway integration and transaction processing | `accounts` | [`payments.md`](payments.md) |
| `products` | Products | Optional | Yes (Phase 6) | Product catalog, variants, categories, and attributes | `—` | [`products.md`](products.md) |
| `projects` | Projects | Optional | Yes (Phase 6) | Project management, task tracking, and milestone planning | `—` | [`projects.md`](projects.md) |
| `purchases` | Purchases | Optional | Yes (Phase 6) | Purchase orders, RFQs, vendor management, and requisition | `invoices` | [`purchases.md`](purchases.md) |
| `recruitments` | Recruitment | Optional | Yes (Phase 6) | Job positions, applicant pipelines, and interviews | `employees` | [`recruitments.md`](recruitments.md) |
| `sales` | Sales | Optional | Yes (Phase 6) | Quotations, sales orders, teams, and pricelists | `invoices`, `payments` | [`sales.md`](sales.md) |
| `time-off` | Time Off | Optional | Yes (Phase 6) | Leave allocations, requests, and approval workflows | `employees` | [`time-off.md`](time-off.md) |
| `timesheets` | Timesheets | Optional | Yes (Phase 6) | Timesheet entry and project time allocation | `projects` | [`timesheets.md`](timesheets.md) |
| `website` | Website | Optional | Yes (Phase 6) | Public portal, web pages, and customer authentication | `—` | [`website.md`](website.md) |

*Note: Core plugins are defined by `Package::isCore()` in their service provider. Optional plugins are registered in `bootstrap/providers.php` and gated by runtime installation checks (`Package::isInstalled()`).*

## Phase 5 Cross-Plugin Verification

- [VERIFIED] All 9 Core Plugins are documented in individual Markdown specifications under `docs/plugins/`.
- [VERIFIED] Core status was checked against source code (`->isCore()` in each plugin's `PackageServiceProvider::configureCustomPackage()`).
- [VERIFIED] Runtime plugin dependencies (`Package::hasDependencies()`) are strictly distinguished from Composer requirements (`composer.json`). None of the 9 Core Plugins declare runtime plugin dependencies.
- [VERIFIED] Customer/Admin panel participation is explicitly verified per plugin rather than assumed to be admin-only.
- [VERIFIED] Previously corrected terminology (`CompanyContext`, `CompanyScope`, `BelongsToCompany`, `OwnershipScope`, local `Webkul\Security\Bouncer`) remains consistent throughout.
- [VERIFIED] No Queue Job architecture or phantom asynchronous classes were invented.
- [VERIFIED] Major cross-plugin relationships and shared infrastructure consumption patterns were verified from source code.
- [VERIFIED] Phase 5 documents provide plugin-local architectural maps without replacing canonical Phase 1–4 architecture, security, and database documents.

## Phase 6 Cross-Plugin Verification & Final Audit

- [VERIFIED] All 19 Optional Plugins are documented in dedicated specifications under `docs/plugins/`.
- [VERIFIED] All 28 plugins (9 Core + 19 Optional) are fully represented and verified against source code in `plugins/webkul/`.
- [VERIFIED] Runtime plugin dependencies (`Package::hasDependencies([...])`) are verified against source code across all 19 optional plugins.
- [VERIFIED] Panel participation is verified: `blogs`, `purchases`, and `website` participate in the Customer panel; all others operate under the Admin panel or backend service layers.
- [VERIFIED] Single-database multi-company isolation (`CompanyContext`, `CompanyScope`, `BelongsToCompany`, `BelongsToCompanies`) and Ownership scoping (`OwnershipScope`, `HasOwnershipScope`) are consistently verified across all models.
- [VERIFIED] Database schema ownership and migrations are verified against Phase 4 ERDs (`core.md`, `finance.md`, `operations.md`). Plugins with zero dedicated tables (`accounting`, `barcode`, `contacts`, `full-calendar`, `invoices`, `timesheets`) are accurately mapped to their underlying storage engines.
- [VERIFIED] Test status accurately documents presence/absence of automated test suites without unsubstantiated coverage metrics.

