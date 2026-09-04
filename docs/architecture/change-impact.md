---
status: verified
source_of_truth: source-code
last_verified: 2026-09-04
scope: architecture
confidence: high
---

# Change Impact Analysis & Architecture Master Control Guide

## 1. Purpose & Core Objective

Change Impact Analysis in Aureus ERP establishes an authoritative, evidence-driven engineering control layer for evaluating the blast radius, ripple effects, security implications, data integrity hazards, and verification obligations of any proposed modification to the repository.

In an enterprise resource planning (ERP) platform comprising **28 modular plugins**, **2 distinct Filament panels**, **262 database tables**, over **1,000 foreign keys**, **28 domain events**, **6 event listeners**, **7 model observers**, and **53 domain services**, changes are almost never strictly local. A one-line adjustment in a model trait, database migration, enum definition, or service method can propagate cascading failures across multi-company isolation boundaries, financial ledgers, transactional workflows, and customer-facing interfaces.

This document transforms the empirical knowledge verified across Phases 0–10 into an operational decision-making framework. It equips developers and AI coding agents to answer six foundational questions before modifying any file:
1. **What direct, indirect, and transitive components are affected by this change?**
2. **What architectural, relational, or tenant boundaries are crossed?**
3. **What business workflows, state transitions, or financial calculations are disturbed?**
4. **What level of risk does this change introduce, and is human review mandatory?**
5. **What automated, runtime, and manual verification evidence is required before merging?**
6. **What specialized architectural documentation may become stale as a result?**

---

## 2. Scope & Application Surface

The scope of Change Impact Analysis encompasses the complete Aureus ERP software lifecycle across all functional tiers:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                   Aureus ERP Complete Impact Surface                                   │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ Presentation Layer:   Filament Admin (/admin) & Customer (/) Panels, Clusters, Resources, Pages, UI   │
│ Security & Isolation: Bouncer, OwnershipScope, Multi-Company Isolation Suite, Auth Guards, Policies    │
│ Domain Plugins:       28 Plugins (9 Core, 19 Optional) under plugins/webkul/*, Zero-Table Extensions  │
│ Reactive Runtime:     28 Domain Events, 6 Listeners, 7 Observers, 53 Services, Sequences, Polling     │
│ Persistence Layer:    MySQL Schemas, Migrations, Foreign Key Cascades, Dynamic Relations, Custom Fields│
│ Packaging & Config:   wikimedia/composer-merge-plugin, bootstrap/providers.php, config/*, Cache       │
│ Quality & Governance: Automated Test Suites (Pest v4), Manual Testing, Canonical Architecture Docs     │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

An impact surface exists whenever a proposed modification alters:
- **Executable Logic**: Domain services, event listeners, observers, controllers, Livewire components, or scheduled commands.
- **Persistence & Referential Integrity**: Physical table columns, foreign key delete behaviors, unique indexes, nullability, default values, or attribute casts.
- **Tenant Isolation**: Multi-company query scopes, active company resolution, or cross-company relational consistency.
- **Authorization & Ownership**: Role assignments, permission checks, model policies, or ownership filtering.
- **Presentation & Navigation**: Form schemas, table columns, action buttons, cluster navigation, or panel configurations.
- **Workflow State Machines**: Document status transitions, validation locks, approval thresholds, or sequential document numbering.
- **Cross-Plugin Interoperability**: Injected relationships (`resolveRelationUsing`), shared interfaces, or dynamic custom fields.
- **Deployment & Lifecycle Operations**: Migration execution order, seed dependencies, Composer package resolution, or rollback feasibility.
- **Verification Infrastructure**: Test runners, shared test helpers, factories, or baseline assertions.

---

## 3. Core Philosophy & Critical Logical Distinctions

### 3.1 The Foundational Axiom: Local Correctness ≠ System Safety

The governing axiom of Phase 11 is:

> **«A change is not safe merely because the modified file is correct. It is safe only when its relevant impact surface has been identified and the appropriate verification has been performed.»**
>
> **Local Correctness ≠ System Safety**

A modification to a model, service, or Blade view may compile cleanly, pass static analysis, and satisfy local unit tests, while simultaneously causing catastrophic failure across the broader system:
- It may silently bypass multi-company query filtering in an un-scoped raw SQL query.
- It may trigger an unhandled `cascadeOnDelete()` that purges immutable financial transactions.
- It may cause an event listener in a downstream optional plugin to crash because a payload property was renamed.
- It may break the sequential numbering format relied upon by tax authorities.
- It may expose back-office administrative actions to the customer portal through an un-gated resource page.

### 3.2 The Seven-Stage Conceptual Impact Chain

Every engineering action in Aureus ERP must be evaluated through the formal seven-stage progression:

$$\text{Change} \longrightarrow \text{Affected Area} \longrightarrow \text{Potential Risk} \longrightarrow \text{Required Review} \longrightarrow \text{Required Verification} \longrightarrow \text{Required Tests} \longrightarrow \text{Documentation Updating}$$

1. **Change**: Identification of the exact diff, modified files, affected symbols, and schema alterations.
2. **Affected Area**: Comprehensive mapping of direct callers, indirect listeners, database dependencies, tenant boundaries, and downstream plugins.
3. **Potential Risk**: Categorization of hazard level based on domain sensitivity, blast radius, and test coverage (not file count).
4. **Required Review**: Determining whether architectural, security, database, or domain specialist approval is mandatory.
5. **Required Verification**: Execution of required static analysis, runtime inspection, and manual verification protocols.
6. **Required Tests**: Execution and authoring of targeted automated Pest tests covering the full blast radius.
7. **Documentation Updating**: Identification of specialized living documentation files that require synchronization.

### 3.3 The Ten Critical Logical Distinctions

Developers and AI agents MUST strictly respect the following ten architectural distinctions codified throughout the Aureus ERP knowledge base:

```
┌────┬──────────────────────────────────────┬──────────────────────────────────────────────────────────────────┐
│ #  │ Logical Distinction                  │ Operational Reality in Aureus ERP                                │
├────┼──────────────────────────────────────┼──────────────────────────────────────────────────────────────────┤
│ 1  │ Declaration ≠ Enforcement            │ Policies, Shield permissions, and Form Requests run ONLY when    │
│    │                                      │ explicitly invoked; they do NOT automatically protect DB or APIs.│
├────┼──────────────────────────────────────┼──────────────────────────────────────────────────────────────────┤
│ 2  │ Enum / Schema ≠ Workflow             │ An enum case (e.g. 'unbuild') or table column does not prove that│
│    │                                      │ the underlying business engine or background process exists.     │
├────┼──────────────────────────────────────┼──────────────────────────────────────────────────────────────────┤
│ 3  │ UI Capability ≠ Backend Enforcement  │ A Filament action or form validation rule does NOT secure the    │
│    │                                      │ model layer, raw queries, or REST API endpoints.                 │
├────┼──────────────────────────────────────┼──────────────────────────────────────────────────────────────────┤
│ 4  │ Surface Presence ≠ Operational Power │ The existence of an API route, table, or relation does not prove │
│    │                                      │ that the operational business logic is fully functional.         │
├────┼──────────────────────────────────────┼──────────────────────────────────────────────────────────────────┤
│ 5  │ Naming ≠ Behavior                    │ A class named "Bouncer" or "EmailTemplateService" does not imply │
│    │                                      │ external package capabilities or working implementation.         │
├────┼──────────────────────────────────────┼──────────────────────────────────────────────────────────────────┤
│ 6  │ Architectural Rationale ≠ Behavior   │ Inferred author intent or historical frequency cannot substitute │
│    │                                      │ for verified runtime source execution paths.                     │
├────┼──────────────────────────────────────┼──────────────────────────────────────────────────────────────────┤
│ 7  │ Composer Dep ≠ Runtime Plugin Dep    │ composer.json manages class autoloading; Package::hasDependencies│
│    │ ≠ Code Consumption                   │ governs ERP migration order; code consumption is mere import.    │
├────┼──────────────────────────────────────┼──────────────────────────────────────────────────────────────────┤
│ 8  │ Migration File ≠ Executed State      │ A file in database/migrations/ does NOT execute unless registered│
│    │                                      │ in PackageServiceProvider::hasMigrations([...]).                 │
├────┼──────────────────────────────────────┼──────────────────────────────────────────────────────────────────┤
│ 9  │ Model Relation ≠ Business Behavior   │ Defining hasMany() on an Eloquent model does not prove that any  │
│    │                                      │ business workflow actively evaluates or maintains that relation. │
├────┼──────────────────────────────────────┼──────────────────────────────────────────────────────────────────┤
│ 10 │ "company_id" Presence ≠ Isolation    │ Adding company_id to a table or query does not guarantee tenant  │
│    │                                      │ safety if joined tables, pivots, or subqueries lack filtering.   │
└────┴──────────────────────────────────────┴──────────────────────────────────────────────────────────────────┘
```

---

## 4. Source of Truth Hierarchy

When resolving conflicting claims, analyzing change impact, or designing verification procedures, the following strict hierarchy of authority MUST be enforced:

```
    1. Source Code               (Authoritative implementation reality)
         │
         ▼
    2. Automated Tests           (Verified behavioral specifications)
         │
         ▼
    3. Migrations & DB Schema    (Physical persistence structure)
         │
         ▼
    4. Configuration Files       (Framework and package settings)
         │
         ▼
    5. Composer Configuration    (Package dependencies & lockfile metadata)
         │
         ▼
    6. Canonical Documentation   (docs/* verified knowledge base)
         │
         ▼
    7. Previous AI Statements    (Heuristic statements; zero evidentiary weight)
```

### Operational Rules for Discrepancies
- Source code evidence **always overrides** documentation, comments, or external expectations.
- If existing documentation in `docs/` contradicts verified source code behavior, developers and AI agents MUST NOT silently edit the historical documentation. The discrepancy MUST be formally recorded as a `[PROPOSED CORRECTION]` while current source code dictates the change impact analysis.
- Previous AI-generated explanations possess zero evidentiary weight unless corroborated by direct repository file and line citations.

---

## 5. Plugin Architecture & Dependency Impact

### 5.1 Core vs. Optional Plugins

Aureus ERP partitions its 28 plugins into two architectural tiers:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                 Aureus ERP Plugin Tier Architecture                                    │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ CORE PLUGINS (9) — Foundational, Unconditional, Never Gated by Database State                          │
│ • analytics       • chatter         • fields          • full-calendar   • partners                     │
│ • plugin-manager  • security        • support         • table-views                                    │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ OPTIONAL DOMAIN PLUGINS (19) — Business Modules, Gated by Package::isPluginInstalled()                 │
│ • accounting      • accounts        • barcode         • blogs           • contacts                     │
│ • employees       • inventories     • invoices        • maintenance     • manufacturing                │
│ • payments        • products        • projects        • purchases       • recruitments                 │
│ • sales           • time-off        • timesheets      • website                                        │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

[VERIFIED] Evidence: `plugins/webkul/*/src/*ServiceProvider.php`; `Package::isCore()` calls.

### 5.2 The Three Distinct Dependency Concepts

Developers and AI agents MUST NOT conflate dependency concepts:

```
┌───────────────────────────────┐     ┌────────────────────────────────┐     ┌───────────────────────────────┐
│     Composer Dependency       │  ≠  │  Plugin Installation Dependency│  ≠  │    Code-Level Consumption     │
├───────────────────────────────┤     ├────────────────────────────────┤     ├───────────────────────────────┤
│ • Declared in composer.json   │     │ • Declared in Package provider │     │ • PHP use statements          │
│ • Governs class autoloading   │     │   via hasDependencies([...])   │     │ • Cross-plugin model calls    │
│ • Resolved by Composer CLI    │     │ • Consumed by InstallCommand   │     │ • No ordering guarantee       │
│ • No ERP install order impact │     │ • Controls DB migration order  │     │ • Crashes if plugin inactive  │
└───────────────────────────────┘     └────────────────────────────────┘     └───────────────────────────────┘
```

1. **Composer Dependency**: Governs PSR-4 class discovery and package merge via `wikimedia/composer-merge-plugin`. Adding a package to `composer.json` does NOT install its database tables or run its seeders.
2. **Runtime Installation Dependency (`Package::hasDependencies([...])`)**: Declared in `*ServiceProvider::configureCustomPackage()`. Consumed exclusively by `InstallPluginCommand` to enforce database migration order and prerequisite checks.
3. **Code-Level Consumption**: Mere PHP `use Webkul\...` imports. If Plugin A imports a model from optional Plugin B without declaring `hasDependencies(['B'])` or checking `Package::isPluginInstalled('B')`, Plugin A will crash at runtime if Plugin B is deactivated.

### 5.3 Verified Runtime Dependency Map

The following baseline represents the verified installation-order dependency map:

```
┌────────────────────────┬──────────────────────────────────────────┬────────────────────────────────────┐
│ Dependent Plugin       │ Declared Prerequisites (hasDependencies) │ Primary Shared Integration         │
├────────────────────────┼──────────────────────────────────────────┼────────────────────────────────────┤
│ accounting             │ accounts                                 │ Balance Sheet, P&L, Ledgers        │
│ accounts               │ products                                 │ Product account mappings, taxes    │
│ barcode                │ inventories                              │ Mobile scanning of stock moves     │
│ blogs                  │ website                                  │ Public content rendering           │
│ inventories            │ products                                 │ Stockable products, quantities     │
│ manufacturing          │ products, inventories                    │ BoM components, stock consumption  │
│ invoices               │ accounts                                 │ Customer invoices, vendor bills    │
│ payments               │ accounts                                 │ Payment registration, journals     │
│ purchases              │ invoices                                 │ Vendor bills, 3-way matching       │
│ recruitments           │ employees                                │ Candidate-to-employee onboarding   │
│ sales                  │ invoices, payments                       │ Commercial orders, billing         │
│ time-off               │ employees                                │ Leave balances, employee calendars │
│ timesheets             │ projects                                 │ Project task duration records      │
└────────────────────────┴──────────────────────────────────────────┴────────────────────────────────────┘
```

*(Note: None of the 9 Core Plugins declare `hasDependencies()`, as core infrastructure is presumed available).*

### 5.4 Zero-Table Extension Layer Plugins

The repository contains exactly **six verified zero-table plugins** that own **0 migrations and 0 physical database tables**:

```
┌────────────────┬───────────────────────────┬───────────────────────────────────────────────────────────┐
│ Plugin         │ Extended Schema Domain    │ Operational Purpose                                       │
├────────────────┼───────────────────────────┼───────────────────────────────────────────────────────────┤
│ accounting     │ accounts (accounts_*)     │ Financial reports (Balance Sheet, P&L), analytics widgets │
│ barcode        │ inventories & products    │ Mobile scanner interface executing stock pickings/moves   │
│ contacts       │ partners (partners_*)     │ Top-level administrative contact directory navigation     │
│ full-calendar  │ Pure UI Component         │ Alpine/FullCalendar v6 widget wrapper; no persistence     │
│ invoices       │ accounts (accounts_*)     │ Operational billing presentation layer (invoices/bills)   │
│ timesheets     │ projects & analytics      │ Specialized timesheet views over analytic_records         │
└────────────────┴───────────────────────────┴───────────────────────────────────────────────────────────┘
```

[VERIFIED] Evidence: Verification of absence of `plugins/webkul/<plugin>/database/migrations/`.

**Impact Rule**: Modifying an underlying persistence schema (e.g. `accounts_account_moves`) directly impacts zero-table presentation plugins (e.g. `invoices` and `accounting`). Developers MUST NOT add ad-hoc database migrations to zero-table plugins without formal architectural reclassification.

### 5.5 Plugin Registration & Lifecycle Pipeline

A class or migration file existing in the repository does not prove that the framework or plugin system registers or executes it. The full registration chain requires:
1. **Composer Discovery**: `wikimedia/composer-merge-plugin` merges package `composer.json` into root PSR-4 autoloading.
2. **Provider Registration**: The plugin service provider MUST be explicitly registered in `bootstrap/providers.php`.
3. **Package Configuration**: `configureCustomPackage()` configures package name, core status (`isCore()`), dependencies (`hasDependencies()`), and migration paths (`hasMigrations()`).
4. **Bootstrapping**: `packageRegistered()` and `packageBooted()` bind services, register event listeners, attach observers, and invoke dynamic relationships.
5. **Filament Registration**: Admin and Customer panel providers discover local `*Plugin.php` classes via `$panel->plugin(...)` or auto-discovery paths.

---

## 6. Dynamic Schema & Model Extension Impact

### 6.1 Dynamic Relationship Extensions (`resolveRelationUsing`)

Base models in core plugins are extended by optional plugins at boot time using `resolveRelationUsing()`. The repository contains exactly **22 verified usage sites** across **4 service providers**:

```
┌──────────────────────┬─────────────┬───────────────────────────────┬───────────────────────────────────┐
│ Service Provider     │ Total Sites │ Target Base Models            │ Injected Dynamic Relations        │
├──────────────────────┼─────────────┼───────────────────────────────┼───────────────────────────────────┤
│ AccountServiceProv.  │ 14 sites    │ Partner, Product, Category    │ companyProperties, accounts, terms│
│ InventoryServiceProv.│ 5 sites     │ Product, Category             │ quantities, routes, warehouses    │
│ ManufactServiceProv. │ 2 sites     │ Product                       │ billOfMaterials, workOrders       │
│ PurchaseServiceProv. │ 1 site      │ Product                       │ supplierPricelists                │
└──────────────────────┴─────────────┴───────────────────────────────┴───────────────────────────────────┘
```

[VERIFIED] Evidence: `docs/architecture/dynamic-schema.md`; `plugins/webkul/accounts/src/AccountServiceProvider.php:251-369`.

**Impact Rule**: Changing a model that participates in `resolveRelationUsing()` requires auditing both the base model and all dynamic relations attached by downstream service providers. Dynamic relationships are invisible to standard model file inspection.

### 6.2 CompanyProperty (EAV Cast vs Model/Table)

- `Webkul\Account\Casts\CompanyProperty`: An Eloquent custom attribute cast (`plugins/webkul/accounts/src/Casts/CompanyProperty.php`) implementing `CastsAttributes`. Serializes and deserializes company-specific dynamic configuration properties stored in JSON/text attributes.
- `Webkul\Partner\Models\PartnerCompanyProperty`: A concrete Eloquent model (`plugins/webkul/partners/src/Models/PartnerCompanyProperty.php`) backed by the physical database table `partners_partner_company_properties`, managing partner accounting properties per company.
- **Impact Rule**: Modifying `CompanyProperty` casting alters dynamic attribute deserialization across tenant contexts. Developers MUST NOT confuse the custom cast with the partner property model.

### 6.3 Custom Fields Engine (`HasCustomFields`)

Custom fields dynamically alter model schemas at runtime:
- `Webkul\Field\Traits\HasCustomFields`: Attached to Eloquent Models. Merges custom field columns into `$fillable` and attaches dynamic casts.
- `Webkul\Field\Filament\Traits\HasCustomFields`: Attached to Filament Resources and Pages. Dynamically injects form components, table columns, table filters, and infolist entries at request time.
- **Impact Rule**: Altering `FieldsColumnManager` or the custom fields schema impacts both physical database persistence and UI form generation across all decorated resources.

---

## 7. Database & Persistence Layer Impact

### 7.1 The Migration Registration Rule (`Migration File ≠ Executed State`)

In Aureus ERP, migrations are **NOT auto-discovered** by scanning directory trees. The plugin manager executes migrations exclusively from the array explicitly passed to `$package->hasMigrations([...])`:

```php
$package
    ->hasMigrations([
        '2024_11_25_091807_create_products_products_table',
        '2024_12_11_070420_create_products_categories_table',
    ])
    ->runsMigrations();
```

> **CRITICAL ARCHITECTURAL HAZARD:**
> A migration file placed on disk in `database/migrations/` that is omitted from `hasMigrations([...])` **NEVER EXECUTES**.
> Conversely, declaring a migration in `hasMigrations([...])` that does not exist on disk crashes plugin installation.

#### Verified Repository Anomalies:
- **`EmailTemplate` Anomaly**: Declared in `SupportServiceProvider::hasMigrations()`, but missing from disk.
- **`support` Unregistered Unique Index**: `2026_03_09_000001_add_unique_index_to_companies_name.php` exists on disk but is omitted from `SupportServiceProvider::hasMigrations()`; therefore, this index is never created.
- **`time-off` Unregistered Data Migration**: `2026_08_04_100000_share_default_time_off_leave_types.php` exists on disk but is omitted from `TimeOffServiceProvider::hasMigrations()`.
- **`employees` Orphaned Calendar Migrations**: Three migration files exist on disk in `employees` but were omitted from `EmployeeServiceProvider::hasMigrations()` when calendar logic was centralized.

[VERIFIED] Evidence: `docs/ai/database-rules.md:53-78`.

### 7.2 Foreign-Key Delete Behavior Decision Framework

Across 1016 physical foreign keys, Aureus ERP exhibits the following observed distribution:
- `nullOnDelete()`: 607 keys (59.7%)
- `cascadeOnDelete()`: 226 keys (22.2%)
- `restrictOnDelete()`: 181 keys (17.8%)
- No Action / Default: 2 keys (0.2%)

> **MANDATORY SEMANTIC RULE:**
> **New foreign-key delete behavior MUST be selected based on the semantic lifecycle of the relationship, NOT by frequency statistics.**
> The fact that `nullOnDelete()` represents ~60% of existing keys does NOT make it the default or preferred choice.

```
                           What should happen when the
                               referenced record is deleted?
                                            │
          ┌─────────────────────────────────┼─────────────────────────────────┐
          ▼                                 ▼                                 ▼
Parent Owns Child Life            Child Has Independent Life        Child Represents Critical Audit
    (Composition)                     (Optional Reference)           or Financial Ledger Record
          │                                 │                                 │
          ▼                                 ▼                                 ▼
   cascadeOnDelete()                 nullOnDelete()                   restrictOnDelete()
          │                                 │                                 │
• Line items (order_lines)        • Creator / assigned user         • Currency on orders / moves
• Pivot junction tables           • Category on products            • Company on ledger moves
• Child chatter attachments       • Parent category on category     • Warehouse on stock pickings
• Detail distributions            • Partner on draft transaction    • Unit of Measure on items
```

### 7.3 Raw SQL and Query Builder Governance

Approximately **143 files** in Aureus ERP execute raw database operations (`DB::table`, `DB::raw`, `DB::select`, `DB::statement`).

> **MANDATORY RULE FOR RAW QUERIES:**
> Eloquent models with `BelongsToCompany` or `BelongsToCompanies` MUST be preferred.
> If raw SQL or query builder is necessary, verification MUST confirm tenant isolation across the **complete query surface**:
> 1. Primary table `FROM` clause includes active company filter.
> 2. Every joined table (`JOIN`, `LEFT JOIN`) includes tenant filtering.
> 3. Subqueries, CTEs, and derived tables filter by active company.
> 4. Pivot tables and junction tables enforce tenant boundaries.
> 5. Aggregate calculations (`SUM`, `COUNT`) filter out foreign company rows.
> 6. Results are verified against `CompanyContext` before output.

[VERIFIED] Evidence: `docs/ai/security-rules.md:31-66`.

---

## 8. Company Isolation & Multi-Tenancy Impact

### 8.1 The Multi-Company Isolation Suite

Multi-company tenancy in Aureus ERP is implemented through an opt-in, modular suite of traits, scopes, and services located in `plugins/webkul/support/` and `plugins/webkul/security/`:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                 Multi-Company Isolation Suite                                         │
├──────────────────────────────┬─────────────────────────────────────────────────────────────────────────┤
│ BelongsToCompany             │ Single-company trait. Adds CompanyScope; auto-assigns active company_id.│
│ BelongsToCompanies           │ Multi-company trait. Adds CompaniesScope; filters via pivot relation.   │
│ CompanyContext               │ Request-scoped service managing active company and allowed user tenants.│
│ CompanyScope                 │ Global Eloquent scope: WHERE (company_id IN (...) OR company_id IS NULL)│
│ CompaniesScope               │ Global Eloquent scope: WHERE HAS relation IN (...) or NO relation.      │
│ RestrictToAllowedCompanies   │ HTTP middleware rejecting requests to unauthorized tenant companies.   │
│ ChecksCompanyConsistency     │ Model trait validating parent/child company alignment on save.          │
└──────────────────────────────┴─────────────────────────────────────────────────────────────────────────┘
```

> **FORBIDDEN TERM:**
> The trait or scope name `HasCompanyScope` **DOES NOT EXIST** in Aureus ERP.
> Developers and AI agents MUST NOT reference, import, or assume the existence of `HasCompanyScope`.

[VERIFIED] Evidence: `plugins/webkul/support/src/Traits/BelongsToCompany.php`; `plugins/webkul/support/src/Models/Scopes/CompanyScope.php`.

### 8.2 The Fallacy of `company_id` Presence

A query or table is NOT proven safe merely because `company_id` is present in code:
- A raw query joining `sales_orders` to `inventories_moves` without tenant constraints on `inventories_moves` leaks inventory operations across companies.
- A nullable `company_id` where `NULL` is interpreted as "global visibility" can inadvertently expose private tenant transactions to all companies if improperly queried.
- Multi-company relational consistency MUST be enforced: `ChecksCompanyConsistency` ensures a user cannot link a Warehouse belonging to Company A to a Sales Order belonging to Company B.
- `ChecksCrossCompanyTransfer` in `inventories` independently validates that transfers between warehouse locations do not violate company ownership.

---

## 9. Security & Authorization Impact

### 9.1 Proprietary Bouncer Architecture

`Webkul\Security\Bouncer` (`plugins/webkul/security/src/Bouncer.php`) is a **custom, proprietary Aureus ERP authorization service**.

> **CRITICAL ARCHITECTURAL DISTINCTION:**
> Aureus ERP does **NOT** use the external open-source package `silber/bouncer`.
> Developers and AI agents MUST NOT attempt to call third-party Bouncer methods absent from `Webkul\Security\Bouncer`.

[VERIFIED] Evidence: `plugins/webkul/security/src/Bouncer.php`; absence of `silber/bouncer` in `composer.lock`.

### 9.2 PermissionType Scoping

Resource authorization scoping is governed by `Webkul\Security\Enums\PermissionType`:
- `PermissionType::GLOBAL` (`'global'`): Full access across all records in authorized companies.
- `PermissionType::GROUP` (`'group'`): Access restricted to records owned by members of the user's teams (`user_team`).
- `PermissionType::INDIVIDUAL` (`'individual'`): Access restricted strictly to records owned by the authenticated user.

> **CANONICAL ENUM RULE:**
> The enum case `'self'` or `PermissionType::SELF` **DOES NOT EXIST**.
> Code and documentation MUST use `PermissionType::INDIVIDUAL`.

[VERIFIED] Evidence: `plugins/webkul/security/src/Enums/PermissionType.php:9-13`.

### 9.3 Record Ownership Resolution (`OwnerSource`)

Record-level access control resolves ownership dynamically via `Webkul\Security\Traits\HasOwner` and `OwnerSource`:
1. `column`: Direct foreign key on the model (`user_id`, `creator_id`, `assigned_to`).
2. `relation`: An Eloquent relation pointing to the owner (`creator()`, `user()`).
3. `pivot`: Many-to-many relationship resolving through an intermediate join table.
4. `followers`: Dynamic followers list resolving ownership through `chatter_followers`.

### 9.4 Dual User Model & Dual-Panel Boundaries

```
┌────────────────────────────────────────────────────────┐ ┌────────────────────────────────────────────────────────┐
│                   Admin Panel (/admin)                 │ │                 Customer Panel (/)                     │
├────────────────────────────────────────────────────────┤ ├────────────────────────────────────────────────────────┤
│ • Provider: app/Providers/Filament/AdminPanelProvider  │ │ • Provider: app/Providers/Filament/CustomerPanelProvider│
│ • Authentication Guard: web                            │ │ • Authentication Guard: customer                       │
│ • Authenticatable Model: App\Models\User               │ │ • Authenticatable Model: Webkul\Partner\Models\Partner │
│ • Base Class: Webkul\Security\Models\User              │ │ • Context: Self-service customer portal                │
│ • Authorization: Filament Shield RBAC & Bouncer        │ │ • Authorization: Scoped portal queries                 │
└────────────────────────────────────────────────────────┘ └────────────────────────────────────────────────────────┘
```

> **AUTHORIZATION BOUNDARY DISTINCTION:**
> **UI Visibility ≠ Authorization Policy ≠ Database Isolation**
> Hiding an action button in Filament does NOT protect backend routes. An API endpoint or service method must independently enforce policies and tenant scopes.

### 9.5 Security Testing Reality

`plugins/webkul/security/` currently has **ZERO automated test coverage** (`plugins/webkul/security/tests/` does not exist). Any modification to security, policies, or tenancy carries **extreme regression risk** and requires mandatory manual verification and peer review.

---

## 10. Filament Presentation & UI Impact

### 10.1 Presentation Architecture

Filament v5 (`v5.7.6`) and Livewire v4 (`v4.3.3`) power the presentation tier across both panels:
- **204 Filament Resource Classes**
- **474 Total Pages** (398 Resource Pages, 76 Custom/Cluster/Settings Pages)
- **46 Filament Clusters**
- **24 Filament Widget Classes**
- **26 Local Filament Plugin Classes** (`*Plugin.php`)

### 10.2 Navigation Composition Pattern (`shouldRegisterNavigation = false`)

Aureus ERP coordinates navigation using `Webkul\Support\Enums\NavigationGroup`:
- **Core Resources Deliberately Hide Navigation**:
  Foundational resources (`PartnerResource`, `InvoiceResource`, `CountryResource`) declare:
  ```php
  protected static bool $shouldRegisterNavigation = false;
  ```
- **Presentation Plugins Surface Navigation**:
  Downstream plugins (`contacts`, `invoices`, `accounting`) extend these resources, set `$shouldRegisterNavigation = true`, and attach them to specific navigation clusters.
- **Impact Rule**: Never set `shouldRegisterNavigation = true` on a core resource without verifying presentation layer ownership.

---

## 11. Workflows, Business Rules & State Machines Impact

### 11.1 The Eight-Layer Workflow Tracing Model

Whenever a workflow is altered, impact MUST be traced through all eight architectural layers:

```
[1] UI Entry Point       (Filament Page, Table Action, Bulk Action, or API Route)
       │
       ▼
[2] Action / Handler     (Filament Action closure or Controller method)
       │
       ▼
[3] Domain Service       (Business calculation engine, validation, or SequenceService)
       │
       ▼
[4] Model State Machine  (State transition on model, e.g. draft -> confirmed -> done)
       │
       ▼
[5] Database Mutation    (SQL execution, foreign key verification, ledger creation)
       │
       ▼
[6] Domain Events        (Synchronous event dispatch, e.g. OrderConfirmed)
       │
       ▼
[7] Listeners/Observers  (Reactive recalculations, cross-plugin side effects)
       │
       ▼
[8] Security & Rules     (Bouncer check, company consistency, double-entry balance)
```

### 11.2 Document Locking Mechanisms

- **Sales Orders (`Webkul\Sale\Models\Order`)**: Uses a dedicated boolean column `is_locked` (`sales_orders.is_locked`). Locking is independent of the order `state` enum (Draft, Sent, Sale, Cancelled).
- **Purchase Orders (`Webkul\Purchase\Models\Order`)**: Uses state-based locking. Transitioning the order `state` to `OrderState::DONE` represents administrative locking (`purchases_orders.state`).
- **Impact Rule**: Developers MUST NOT evaluate purchase order locking via `is_locked` or assume sales orders use `OrderState::DONE`.

### 11.3 Financial Ledger Invariants

- All financial transactions route to `accounts_account_moves` via `Webkul\Account\Models\Move`.
- Double-entry balance invariant: The sum of debits across all move lines MUST equal the sum of credits before posting (`MoveState::POSTED`).
- Classes named `Invoice` in `invoices`, `accounting`, or `sales` are proxy subclasses extending `Move`.

---

## 12. Events, Observers, Services & Asynchronous Impact

### 12.1 Reactive Infrastructure Summary

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                 Reactive Infrastructure Summary                                        │
├──────────────────────────────┬───────────────┬─────────────────────────────────────────────────────────┤
│ Component Type               │ Total Classes │ Participating Plugins                                   │
├──────────────────────────────┼───────────────┼─────────────────────────────────────────────────────────┤
│ Domain Events                │ 28 classes    │ accounts (7), inventories (6), manufacturing (5),       │
│                              │               │ purchases (5), sales (5)                                │
│ Event Listeners              │ 6 classes     │ sales (3), purchases (2), plugin-manager (1)            │
│ Model Observers              │ 7 classes     │ inventories (3), manufacturing (2), products (2)        │
│ Queued Notifications         │ 1 class       │ chatter (ChatterDatabaseNotification)                   │
│ Domain Services              │ 53 classes    │ support, sales, accounts, inventories, etc.             │
└──────────────────────────────┴───────────────┴─────────────────────────────────────────────────────────┘
```

[VERIFIED] Evidence: `docs/architecture/events-catalog.md:17-25`.

### 12.2 Synchronous In-Transaction Hazard

All 28 Domain Events and 6 Listeners execute **synchronously within the active HTTP request and database transaction**:
- If an event listener fails (e.g. `ComputeSaleOrderListener` throws an exception during `OperationDone`), **the entire primary transaction rolls back**.
- A change to an event payload signature directly breaks all registered listeners across other plugins.

### 12.3 Eloquent Model Observers

Seven observers intercept model lifecycle hooks (`created`, `updated`, `deleted`):
- `CompanyObserver` (`inventories`): Auto-creates default warehouse and stock locations on company creation.
- `UOMObserver` (`inventories` & `products`): Validates conversions and prevents deletion of in-use units of measure.
- `ProductObserver` (`inventories`): Initializes stock tracking on product creation.
- `WarehouseObserver` (`manufacturing`): Synchronizes operation types and routing steps.
- `MoveObserver` (`manufacturing`): Tracks raw material consumption.
- `ProductAttributeObserver` (`products`): Regenerates variant matrices when attributes change.

### 12.4 The SequenceService Blast Radius

`Webkul\Support\Services\SequenceService` coordinates centralized sequential document numbering across the enterprise:
- Generates document codes for `sales.order`, `purchases.order`, `account.move` (invoices/bills), `inventories.operation`, and `mrp.production`.
- Relies on the `support_sequences` table, using tenant-specific scopes and row locks.
- **Critical Risk**: Any modification to `SequenceService` or sequence formatting can cause duplicate document errors, disrupt sequential tax compliance, or lock concurrent order processing.

### 12.5 The Asynchronous Reality: Zero Queue Jobs ≠ Zero Async Behavior

> **VERIFIED REPOSITORY FACT:**
> The repository contains **ZERO classes extending `Illuminate\Queue\Jobs\Job`** or residing in `Jobs/` directories.
> **However, Zero Job classes ≠ Zero asynchronous behavior.**

Established deferred and reactive mechanisms in Aureus ERP:
1. **Queued Notifications**: `Webkul\Chatter\Notifications\ChatterDatabaseNotification` implements `ShouldQueue` and uses `Queueable`.
2. **Client-Side Notification Polling**: `AdminPanelProvider` configures `->databaseNotifications()->databaseNotificationsPolling('30s')`.
3. **Artisan Console Scheduling**: `routes/console.php` exposes scheduled commands executed via cron.
4. **Synchronous Reactive Decoupling**: 28 Events, 6 Listeners, and 7 Observers coordinate work without worker daemons.

---

## 13. Testing & Verification Impact

### 13.1 The Coverage Baseline: 11 Tested vs. 17 Untested Plugins

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                 Automated Test Coverage Distribution                                  │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ TESTED PLUGINS (11) — Automated Pest Tests Exist in plugins/webkul/<plugin>/tests/                     │
│ Core (2):     partners (9 tests), support (17 tests)                                                   │
│ Optional (9): accounting (9), accounts (42), employees (5), inventories (41), manufacturing (8),       │
│               products (16), projects (8), purchases (15), sales (17)                                  │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ UNTESTED PLUGINS (17) — Zero Automated Tests (tests/ directory does not exist)                         │
│ Core (7):     analytics, chatter, fields, full-calendar, plugin-manager, security, table-views         │
│ Optional (10):barcode, blogs, contacts, invoices, maintenance, payments, recruitments, time-off,       │
│               timesheets, website                                                                      │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

[VERIFIED] Evidence: `docs/ai/testing-rules.md:31-86`.

### 13.2 The Testing Governance Principle

> **«Existing lack of tests describes the historical baseline; it does NOT define the future minimum standard.»**
> **Passing Test Suite ≠ Complete Impact Verification**

- Modifications to the 17 untested plugins (especially `security`, `payments`, `invoices`, and `plugin-manager`) CANNOT rely on existing CI tests to catch regressions.
- Future changes in critical zones require authoring new Pest tests or undergoing comprehensive manual verification.

---

## 14. AI Governance & Knowledge Base Impact

Modifications to Phase 10 AI rule files in `docs/ai/` alter developer and AI coding agent behavior globally:
- `docs/ai/terminology.md`: Updates naming conventions and misconception boundaries.
- `docs/ai/forbidden-patterns.md`: Codifies anti-patterns and operational guardrails.
- `docs/ai/architecture-rules.md`: Governs plugin boundaries, panel separation, and zero-table layers.
- `docs/ai/plugin-rules.md`: Dictates creation checklists and provider wiring.
- `docs/ai/database-rules.md`: Governs migration registration and delete behaviors.
- `docs/ai/security-rules.md`: Dictates tenancy scoping and Bouncer governance.
- `docs/ai/coding-rules.md`: Governs code structure, Livewire v4 patterns, and Filament conventions.
- `docs/ai/testing-rules.md`: Establishes Pest testing standards and priority thresholds.

**Impact Rule**: Modifying an AI rule requires auditing dependent architecture documents and ensuring AI agents do not introduce regression patterns.

---

## 15. The Master Change-Impact Matrix

The following comprehensive matrix details the impact surface, hazard level, mandatory review requirements, verification protocols, required tests, and documentation implications across all discovered repository change categories.

| Change Category / Trigger | Specific Change | Blast Radius / Impact Area | Risk Level | Why It Matters (Hazard) | Required Review | Verification Protocol | Required Tests | Stale Documentation |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Model - Registration** | Adding a new Eloquent model class | Owning plugin, ORM layer, Filament resources, database schema | **MEDIUM** | Missing tenancy traits (`BelongsToCompany`), missing `$fillable`, or unindexed foreign keys lead to data leaks and performance bottlenecks. | Architecture & Domain Lead | Verify traits, casts, relationships, `$fillable`, and table name conventions. | Model unit test; factory creation test; tenancy scope assertion. | `docs/database/models-index.md`, owning plugin docs. |
| **Model - Removal** | Deprecating or removing an Eloquent model | Downstream consuming plugins, dynamic relations, foreign key cascades | **CRITICAL** | Silent runtime crashes in consuming plugins importing the class; orphaned foreign keys in relational tables. | Lead Architect & Core Team | Grep codebase for model imports, `resolveRelationUsing()`, and polymorphic type strings. | Regression suite across all 28 plugins; migration rollback test. | `docs/database/models-index.md`, `docs/database/relationships.md`. |
| **Model - Attributes** | Adding, removing, or renaming model attributes | ORM queries, `$fillable`, serialization, Filament forms and tables | **MEDIUM** | Mass-assignment exceptions (`MassAssignmentException`); broken Filament form field bindings; broken API payloads. | Plugin Lead | Verify `$fillable`, `$guarded`, attribute accessors/mutators, and Livewire bindings. | Feature test asserting form submission and attribute persistence. | Owning plugin documentation, API schemas. |
| **Model - Casts** | Modifying `casts(): array` on an Eloquent model | Data serialization, date formatting, JSON decoding, boolean evaluation | **HIGH** | Type coercion mismatches; broken JSON payload manipulation; silent data corruption on save. | Domain Lead | Verify cast return types against database column physical types. | Unit tests asserting cast serialization and deserialization. | Owning plugin docs, `docs/ai/coding-rules.md`. |
| **Model - CompanyProperty** | Altering `CompanyProperty` custom EAV cast | Tenant-specific configuration on Partner, Product, or Account | **CRITICAL** | Deserialization failure across company contexts; leaking tenant configuration across companies. | Core Architect & Security | Verify `CastsAttributes` implementation across multiple company contexts. | Multi-company integration test asserting per-company property isolation. | `docs/architecture/dynamic-schema.md`, `docs/ai/terminology.md`. |
| **Model - Relationships** | Adding, modifying, or removing Eloquent relations | Relational navigation, eager loading, delete cascades, query joins | **HIGH** | N+1 query regressions; broken eager loading in Filament tables; integrity violations on delete. | Domain Lead | Verify relation method return type, foreign key names, and reciprocal inverse relations. | Feature test testing eager loading and relation cascading. | `docs/database/relationships.md`, `docs/database/models-index.md`. |
| **Model - Dynamic Relations** | Altering `resolveRelationUsing()` in ServiceProviders | Base models (`Partner`, `Product`), cross-plugin query navigation | **CRITICAL** | Dynamic relation becomes unavailable if consuming plugin is uninstalled; eager loading fails silently. | Lead Architect | Inspect all 22 usage sites across `AccountServiceProvider`, `InventoryServiceProvider`, etc. | Cross-plugin integration tests asserting dynamic relation availability. | `docs/architecture/dynamic-schema.md`, `docs/ai/architecture-rules.md`. |
| **Model - Query Scopes** | Modifying global or local Eloquent query scopes | All model queries, Filament resource listings, API endpoints | **CRITICAL** | Global data leaks; records disappearing from UI; query performance degradation. | Core Architect & Security | Inspect SQL query output generated by the modified scope in Tinker. | Pest tests asserting query SQL constraints and record filtering. | `docs/security/ownership-scopes.md`, `docs/database/company-isolation.md`. |
| **Model - Tenancy Trait** | Attaching or removing `BelongsToCompany` / `BelongsToCompanies` | Multi-company isolation, `CompanyScope`, automatic company assignment | **CRITICAL** | Cross-tenant data leakage or tenant lockout; failing to auto-populate `company_id` on save. | Security Lead & Core Team | Trace query execution under `CompanyContext`; verify tenant constraint injection. | Pest feature test with multiple company fixtures asserting isolation. | `docs/database/company-isolation.md`, `docs/ai/security-rules.md`. |
| **Model - Consistency Guard** | Attaching or removing `ChecksCompanyConsistency` | Referential integrity between parent and child models | **HIGH** | Cross-company foreign key links (e.g., Company A order linked to Company B warehouse). | Core Architect | Trace `CompanyConsistencyGuard::validate()` across all foreign relations on the model. | Integration test attempting cross-company relation linkage. | `docs/security/multi-company.md`, `docs/ai/terminology.md`. |
| **Model - Ownership Trait** | Modifying `HasOwner` trait or `OwnerSource` configuration | Record-level authorization, `OwnershipScope`, Bouncer filtering | **CRITICAL** | Unauthorized user access; records visible to wrong team members; Bouncer query failure. | Security Lead | Verify `OwnerSource` resolution kind (`column`, `relation`, `pivot`, `followers`). | Pest authorization test exercising `INDIVIDUAL`, `GROUP`, and `GLOBAL` roles. | `docs/security/ownership-scopes.md`, `docs/security/authorization.md`. |
| **Model - Chatter Activity** | Modifying `HasChatter` or `HasLogActivity` traits | Audit logging, chatter message timeline, activity tracking | **LOW** | Missing audit trail records on critical changes, or extraneous notification spam. | Plugin Lead | Verify whether audit logging (`HasLogActivity`) or interactive feed (`HasChatter`) is needed. | Model lifecycle test verifying `chatter_messages` record creation. | `docs/ai/terminology.md`, chatter plugin docs. |
| **Model - Custom Fields** | Attaching or modifying `HasCustomFields` model trait | Runtime `$fillable` array, dynamic attribute casting, table columns | **MEDIUM** | Inability to save dynamic custom attributes; broken custom field validation. | Plugin Lead | Trace `FieldsColumnManager` interaction on model boot and save. | Feature test saving and querying custom fields on the model. | `docs/architecture/dynamic-schema.md`, fields plugin docs. |
| **Model - Lifecycle Hooks** | Modifying Eloquent model events (`saving`, `creating`, `deleted`) | Transactional mutations, side effects, audit logs, sequence generation | **HIGH** | Unhandled exceptions aborting save operations; infinite loops in recursive save calls. | Domain Lead | Verify return values in hook closures; check for recursive `$model->save()` calls. | Unit test asserting state changes before and after model persistence. | Owning plugin docs, `docs/ai/coding-rules.md`. |
| **Database - New Table** | Adding a new database table migration | Physical storage, migration execution order, database indexing | **MEDIUM** | Migration failure during installation; missing tenant `company_id`; unindexed foreign keys. | Database Specialist | Verify migration naming, primary keys, foreign keys, and `hasMigrations()` inclusion. | Fresh migration test (`migrate:fresh`); schema inspection. | `docs/database/overview.md`, `docs/database/schema-conventions.md`. |
| **Database - Drop Table** | Dropping an existing database table | Historical business data, dependent tables, foreign key constraints | **CRITICAL** | Irreversible business data loss; broken foreign key references in downstream tables. | Architecture & Data Board | Audit all code references across all 28 plugins before approving drop. | Rollback and forward migration testing on production-like dataset. | `docs/database/overview.md`, `docs/database/models-index.md`. |
| **Database - Column Addition** | Adding a column to an existing table | Physical schema, model `$fillable`, casts, existing production rows | **MEDIUM** | Non-nullable column without default breaks existing records; missing index on foreign keys. | Database Specialist | Verify nullability, default values, backfill needs, and model `$fillable`. | Migration test asserting schema state and nullability behavior. | `docs/database/schema-conventions.md`, owning plugin ERD. |
| **Database - Column Drop** | Dropping a column from an existing table | Stored data, Eloquent attributes, Filament form/table schemas | **CRITICAL** | Irreversible data loss; SQL syntax errors in un-updated queries referencing the dropped column. | Database Specialist | Grep repository for column name in models, queries, Filament schemas, and views. | Migration test and full test suite run across owning and consuming plugins. | `docs/database/schema-conventions.md`, owning plugin ERD. |
| **Database - Column Type** | Altering column type or width in migration | Data truncation, casting compatibility, database query performance | **HIGH** | Data truncation errors; silent type conversion bugs; table locking during migration. | Database Specialist | Assess table row volume and potential lock contention during migration. | Migration test verifying data preservation across type alteration. | `docs/database/schema-conventions.md`. |
| **Database - Nullability** | Changing a column from nullable to non-nullable | Database persistence, Form Requests, legacy records with NULL values | **HIGH** | Migration fails if existing rows have NULL; insert queries fail if attribute omitted. | Database Specialist | Verify that a data backfill migration precedes the schema constraint change. | Test inserting records with and without the attribute; verify backfill script. | `docs/database/schema-conventions.md`. |
| **Database - Default Value** | Changing or removing a column default value | Record insertion, ORM defaults, Form Request defaults | **LOW** | Implicit assumptions in code about default values break; unintended NULL insertions. | Domain Lead | Check whether model `$attributes` or migration default defines the fallback. | Test creating records without explicitly specifying the attribute. | `docs/database/schema-conventions.md`. |
| **Database - Indexes** | Adding, modifying, or dropping database indexes | Query performance, unique constraints, table locking on large tables | **HIGH** | Table lock contention on large transactional tables (`move_lines`, `moves`); query slowdowns. | Database Specialist | Review `EXPLAIN` query plans for critical queries utilizing the index. | Benchmark query performance before and after index modification. | `docs/database/schema-conventions.md`. |
| **Database - Unique Constraint** | Adding or modifying a unique composite constraint | Data integrity, concurrent insertions, tenant-scoped uniqueness | **HIGH** | Constraint violation errors on existing production duplicates; missing `company_id` in composite key. | Database Specialist | Verify composite unique keys include `company_id` for multi-tenant uniqueness. | Integration test attempting duplicate insertion within same and across companies. | `docs/database/schema-conventions.md`, `docs/ai/database-rules.md`. |
| **Database - Foreign Keys** | Adding or modifying foreign key constraints | Referential integrity, cascade paths, child record lifecycles | **HIGH** | Constraint violation on orphan records; circular dependency preventing record deletion. | Database Specialist | Verify referenced table existence and column type matching (unsignedBigInteger). | Migration test asserting referential constraint enforcement. | `docs/database/relationships.md`, `docs/database/schema-conventions.md`. |
| **Database - FK Delete Rule** | Changing foreign key action (`cascade`, `null`, `restrict`) | Child record lifecycles, ledger audit trails, relational integrity | **CRITICAL** | Catastrophic data loss via accidental cascade delete, or orphaned child records via improper nullOnDelete. | Lead Architect & Database Spec. | Evaluate relationship lifecycle semantics: Composition vs Independent vs Ledger. | Automated test deleting parent record and verifying child persistence state. | `docs/database/schema-conventions.md`, `docs/ai/database-rules.md`. |
| **Database - Enum Columns** | Adding, renaming, or removing enum values in schema | Database schema, PHP backed enums, model casts, state machines | **HIGH** | SQL errors when persisting un-migrated enum strings; broken state machine transitions. | Domain Lead | Verify synchronization between MySQL enum definition and PHP BackedEnum. | Test persisting every enum case to the database column. | Owning plugin docs, `docs/ai/coding-rules.md`. |
| **Database - JSON Structure** | Modifying JSON / JSONB attribute schema or keys | EAV storage, custom fields, company properties, query JSON extracts | **MEDIUM** | Silent null returns when reading renamed JSON keys; broken client presentation. | Domain Lead | Verify backward compatibility or author a migration script to rename keys. | Unit tests asserting serialization and deserialization of JSON payload. | Owning plugin docs, `docs/architecture/dynamic-schema.md`. |
| **Database - Pivot Tables** | Altering many-to-many intermediate junction tables | Eloquent `belongsToMany` relations, role assignments, tags | **MEDIUM** | Broken junction queries; orphaned pivot rows on parent deletion; missing composite unique keys. | Domain Lead | Verify foreign key cascade rules on both parent keys of the pivot table. | Feature test attaching, detaching, and syncing records via the pivot. | `docs/database/relationships.md`. |
| **Database - Polymorphic** | Changing polymorphic relation columns (`*_type`, `*_id`) | Dynamic relations, chatter messages, tags, addresses | **HIGH** | Query failures if morph map aliases do not match class strings; broken relation resolution. | Domain Lead | Verify `Relation::morphMap()` registration in service providers. | Integration test querying polymorphic relations across multiple target types. | `docs/database/relationships.md`. |
| **Database - Migration Order** | Changing migration execution order or dependency | Plugin installation, fresh database setup, CI deployment | **CRITICAL** | Migration crashes with "Table not found" or "Foreign key constraint incorrectly formed". | Core Architect | Verify `$package->hasDependencies([...])` ordering matches foreign key dependencies. | Run `php artisan migrate:fresh` on clean database. | `docs/ai/plugin-rules.md`, `docs/ai/database-rules.md`. |
| **Database - hasMigrations** | Modifying `$package->hasMigrations([...])` array | Plugin installation, migration discovery, runtime execution | **CRITICAL** | Migration file on disk NEVER runs if omitted from array; installer crashes if declared file missing. | Core Architect | Verify exact 1-to-1 match between files on disk and items declared in array. | Run `php artisan plugin:install <plugin>` and verify tables in schema. | `docs/ai/database-rules.md`, `docs/ai/plugin-rules.md`. |
| **Database - Data Backfill** | Executing data migration or backfill scripts | Existing production records, data consistency, long-running locks | **HIGH** | Transaction timeout; locking production tables during high traffic; corrupting historical data. | Database Specialist | Test backfill in chunks using `lazy()` or chunking; execute in transaction. | Run script on staging clone with production data volume; verify record counts. | Migration documentation, release notes. |
| **Database - Rollbacks** | Defining or executing migration `down()` method | Development rollbacks, failed deployment recovery, data safety | **HIGH** | `down()` method dropping columns containing irreplaceable audit or financial data. | Database Specialist | Verify whether `down()` method safely restores schema without silent data loss. | Test `migrate:rollback` and subsequent `migrate` in local environment. | `docs/database/schema-conventions.md`. |
| **Tenancy - CompanyContext** | Modifying `CompanyContext` service logic | Active company resolution, allowed company set, user session | **CRITICAL** | Global multi-tenant breach; users seeing records from unauthorized sister companies. | Security Specialist & Lead Arch. | Trace `CompanyContext::getActiveCompany()` and `getAvailableCompanies()`. | Security test suite asserting company boundaries across multi-company users. | `docs/database/company-isolation.md`, `docs/security/multi-company.md`. |
| **Tenancy - CompanyScope** | Modifying `CompanyScope` or `CompaniesScope` classes | All single-tenant and multi-tenant Eloquent queries globally | **CRITICAL** | System-wide cross-tenant data exposure or global query failure on 262 tables. | Security Specialist & Lead Arch. | Review generated SQL `WHERE (company_id IN (...) OR company_id IS NULL)`. | Unit tests asserting scope query manipulation across all query types. | `docs/database/company-isolation.md`, `docs/ai/security-rules.md`. |
| **Tenancy - AllowedCompanies** | Modifying `RestrictToAllowedCompanies` middleware | HTTP request gating, cross-company URL tampering protection | **CRITICAL** | Bypassing HTTP tenant gate via manipulated header or session parameter. | Security Specialist | Trace middleware interception on API and web requests with invalid company IDs. | Feature test asserting HTTP 403 Forbidden on unauthorized company access. | `docs/security/multi-company.md`, `docs/ai/security-rules.md`. |
| **Tenancy - Cross-Company** | Modifying `ChecksCrossCompanyTransfer` concern | Warehouse transfers between locations across company borders | **HIGH** | Unauthorized stock transfers moving inventory between distinct legal entities. | Inventory Domain Lead | Trace `CrossCompanyTransferGuard::validate()` on stock operations and transfers. | Test moving stock between same-company locations vs cross-company locations. | `docs/workflows/inventory.md`, `docs/business-rules/inventory.md`. |
| **Tenancy - Raw SQL Query** | Altering raw SQL queries (`DB::table`, `DB::select`) | Performance queries, financial reports, dashboard aggregations | **CRITICAL** | Cross-tenant data leaks in un-scoped joins, subqueries, or aggregate calculations. | Security Lead & Senior Dev | Audit the complete 6-point query surface (primary, joins, subqueries, pivots, aggs). | Pest test asserting zero foreign-company rows returned by raw query. | `docs/ai/security-rules.md`, `docs/database/company-isolation.md`. |
| **Security - Bouncer** | Modifying `Webkul\Security\Bouncer` internal service | Global authorization, `getAuthorizedUserIds()`, role cache | **CRITICAL** | Total authorization bypass; users granted super-admin privileges; cache poisoning. | Security Lead & Core Team | Trace `isSuperAdmin()`, `hasRole()`, and `getAuthorizedUserIds()` implementations. | Test authorization matrix across super-admin, standard user, and unassigned user. | `docs/security/authorization.md`, `docs/ai/terminology.md`. |
| **Security - PermissionType** | Altering `PermissionType` enum cases or handling | Scoping resource authorization (`GLOBAL`, `GROUP`, `INDIVIDUAL`) | **CRITICAL** | Inability to restrict access to user records; regression to global visibility. | Security Specialist | Verify enum cases: `GLOBAL = 'global'`, `GROUP = 'group'`, `INDIVIDUAL = 'individual'`. | Authorization test verifying record filtering under each permission type. | `docs/security/authorization.md`, `docs/ai/terminology.md`. |
| **Security - OwnershipScope** | Modifying `OwnershipScope` query scope | Record-level ownership query filtering across all domain models | **CRITICAL** | Records exposed to unauthorized peers; broken team-based record sharing. | Security Specialist | Trace SQL generated by `OwnershipScope` under different `PermissionType` values. | Feature test asserting user can only query their own or team records. | `docs/security/ownership-scopes.md`, `docs/ai/security-rules.md`. |
| **Security - Policy Methods** | Adding, altering, or removing methods in model Policies | Model-level authorization gates, Filament action visibility | **HIGH** | Users executing unauthorized actions; policies returning false negatives blocking work. | Security Specialist | Verify policy method signatures match Laravel conventions (`User $user, Model $model`). | Policy unit tests asserting boolean access determinations per role. | `docs/security/authorization.md`, owning plugin docs. |
| **Security - Shield Permissions** | Modifying Filament Shield permission definitions or roles | Admin panel access control, resource permissions, navigation | **HIGH** | Administrative users locked out of resources, or unprivileged staff granted edits. | Security Specialist | Run Shield permission regeneration; audit role-to-permission bindings. | Filament test asserting resource view/create/edit permissions per role. | `docs/security/authorization.md`, `docs/architecture/filament-architecture.md`. |
| **Security - Dual User Model** | Modifying `Webkul\Security\Models\User` or `App\Models\User` | Core security traits, admin panel authenticatable identity | **CRITICAL** | Admin login crashes; authentication guard failure; broken role relationships. | Security Lead & Core Team | Verify inheritance: `App\Models\User extends Webkul\Security\Models\User`. | Authentication tests for admin panel login, MFA, and API token generation. | `docs/ai/terminology.md`, `docs/architecture/overview.md`. |
| **Security - Dual Panel Auth** | Modifying auth guards in `AdminPanelProvider` or `CustomerPanelProvider` | Admin panel (`web`) vs Customer portal (`customer`) authentication | **CRITICAL** | Customer portal accessing administrative models; admin routes exposed without MFA. | Security Specialist & Lead Arch. | Verify guard bindings: `web` -> `App\Models\User`; `customer` -> `Partner`. | Separate feature tests authenticating through admin and customer panels. | `docs/architecture/filament-architecture.md`, `docs/ai/terminology.md`. |
| **Security - API Routes** | Modifying `routes/api.php` routes or middleware | Headless REST API endpoints, mobile integrations, external sync | **HIGH** | Unauthenticated endpoints exposing sensitive customer or financial data. | Security Specialist | Verify `auth:sanctum` and policy gate middleware on every declared API endpoint. | Pest API tests verifying HTTP 401 on unauthenticated access. | Owning plugin API docs, `docs/ai/security-rules.md`. |
| **Plugin - Composer Dep** | Adding or altering dependencies in `composer.json` | Vendor package resolution, autoloading, merge-plugin discovery | **HIGH** | Package version conflicts; failed deployment; autoloader bloat. | Tech Lead | Verify `wikimedia/composer-merge-plugin` handles sub-package requirements. | Run `composer validate` and `composer update --dry-run`. | Root and package `composer.json`, `docs/ai/architecture-rules.md`. |
| **Plugin - Runtime Dep** | Modifying `$package->hasDependencies([...])` array | Plugin installation ordering, prerequisite validation in CLI | **HIGH** | Plugin fails to install due to missing dependencies; migrations run out of order. | Core Architect | Trace `InstallPluginCommand` dependency resolution DAG. | Test installing plugin on clean system via `php artisan plugin:install`. | `docs/architecture/plugin-registry.md`, `docs/ai/plugin-rules.md`. |
| **Plugin - Code Consumption** | Importing classes from another plugin (`use Webkul\...`) | Cross-plugin coupling, optional module runtime dependencies | **HIGH** | Unhandled `ClassNotFoundException` if consuming plugin is active but provider inactive. | Architecture Lead | Verify consuming plugin guards execution via `Package::isPluginInstalled()`. | Integration test running consuming plugin with target plugin deactivated. | `docs/ai/architecture-rules.md`, `docs/ai/plugin-rules.md`. |
| **Plugin - ServiceProvider** | Modifying `*ServiceProvider.php` methods (`packageBooted`, etc.) | Service bindings, event registrations, render hooks, routes | **CRITICAL** | Core plugin services fail to bind; listeners not registered; application crash. | Core Architect | Verify `configureCustomPackage()`, `packageRegistered()`, and `packageBooted()`. | Smoke test booting application and resolving registered services. | Owning plugin docs, `docs/architecture/overview.md`. |
| **Plugin - bootstrap/providers** | Adding, removing, or reordering providers in `bootstrap/providers.php` | Laravel framework package auto-discovery and boot order | **CRITICAL** | Entire plugin ceases to load; classes unresolvable; application boot failure. | Core Architect | Verify all 28 plugin service providers are present in the array. | Run `php artisan package:discover` and verify clean framework boot. | `docs/architecture/plugin-registry.md`, `docs/ai/architecture-rules.md`. |
| **Plugin - Filament Plugin** | Modifying `*Plugin.php` class implementing `Filament\Contracts\Plugin` | Filament panel resource discovery, panel configuration injection | **HIGH** | Resources, pages, and clusters fail to mount in the target Filament panel. | Presentation Lead | Verify `getId()`, `register()`, and `boot()` implementations. | Test panel loading in browser and verify navigation items render correctly. | `docs/architecture/filament-architecture.md`, owning plugin docs. |
| **Plugin - Zero-Table Layer** | Modifying one of the 6 zero-table presentation plugins | Presentation layers (`accounting`, `barcode`, `contacts`, `invoices`, etc.) | **HIGH** | Violating architectural boundary by adding migrations; schema duplication. | Lead Architect | Verify that zero-table plugins do NOT define `database/migrations/`. | Test presentation features against underlying shared domain tables. | `docs/architecture/overview.md`, `docs/ai/terminology.md`. |
| **Plugin - New Plugin** | Creating a completely new domain plugin in `plugins/webkul/` | Global ERP architecture, composer merge, installation commands | **CRITICAL** | Non-standard directory structure; missing registration; un-scoped tenancy. | Architecture & Core Board | Execute Phase 10 Plugin Creation Checklist (all 13 structural requirements). | Author full Pest test suite for the new plugin; verify installation CLI. | `docs/architecture/plugin-registry.md`, `docs/ai/plugin-rules.md`. |
| **UI - Resource Form** | Modifying Form schema in Filament Resource | Admin/Customer data entry, validation, Livewire reactivity | **MEDIUM** | Validation errors blocking save; missing required tenant fields; broken reactivity. | Frontend / UI Lead | Verify Form component schema, validation rules, and Livewire hooks. | Livewire component test asserting form filling and record creation. | Owning plugin Filament docs. |
| **UI - Resource Table** | Modifying Table columns, filters, or actions in Resource | Record browsing, sorting, searching, bulk actions, data export | **MEDIUM** | Slow query performance on unindexed search columns; broken table actions. | Frontend / UI Lead | Verify eager loading on relational columns to prevent N+1 query loops. | Filament table test verifying search, column display, and action triggers. | Owning plugin Filament docs. |
| **UI - Resource Pages** | Modifying `List*`, `Create*`, `Edit*`, or `View*` page classes | Resource lifecycle hooks, mutateFormDataBeforeSave, redirect paths | **MEDIUM** | Broken post-save redirects; un-mutated form attributes; broken modal actions. | Frontend / UI Lead | Trace page lifecycle hooks (`mutateFormDataBeforeCreate`, `beforeSave`). | Livewire page test verifying record creation and lifecycle hooks. | Owning plugin Filament docs. |
| **UI - Clusters** | Modifying Filament Cluster registration or grouping | Navigation grouping, URL prefixes, sub-page hierarchy | **LOW** | 404 errors on altered URL paths; broken breadcrumbs; confusing UI navigation. | Frontend / UI Lead | Verify `$cluster` property on member resources and pages. | Browser test navigating through cluster hierarchy and sub-resources. | `docs/architecture/filament-architecture.md`. |
| **UI - Navigation Hiding** | Toggling `shouldRegisterNavigation` on Filament Resource | Top-bar navigation sidebar, presentation layer ownership | **HIGH** | Duplicated menu items in sidebar, or core resources exposed directly without wrapper. | Presentation Lead | Check whether presentation plugin owns user-facing navigation for this resource. | Verify navigation rendering in admin and customer panels. | `docs/ai/terminology.md`, `docs/architecture/filament-architecture.md`. |
| **UI - Filament Actions** | Modifying Header, Table Row, or Bulk Actions in Filament | User-triggered operational workflows (Confirm, Cancel, Post) | **HIGH** | Broken business workflow execution; un-handled exceptions in action closures. | Domain Lead | Trace action closure: does it delegate to a Domain Service or raw SQL? | Test clicking action and verifying model state transition and side effects. | Owning plugin Filament docs, workflow docs. |
| **UI - Table Filters** | Adding or modifying Table Filters in Filament Resources | Data exploration, tenant filtering, date range and state filters | **LOW** | SQL errors in custom filter query closures; unexpected empty table results. | Frontend / UI Lead | Verify filter query callback applies valid Eloquent query constraints. | Test applying filter combinations and verifying returned row counts. | Owning plugin Filament docs. |
| **UI - Widgets** | Modifying Filament Dashboard or Resource Widgets | Dashboard analytics, metric cards, chart data aggregations | **MEDIUM** | Dashboard slowdown caused by heavy uncached aggregations; divide-by-zero errors. | Domain Lead | Verify caching on heavy queries; ensure multi-company filtering applies. | Test widget rendering under active tenant context with realistic data volume. | `docs/architecture/filament-architecture.md`. |
| **UI - AdminPanelProvider** | Modifying `AdminPanelProvider` configuration | Admin panel theme, plugins, middleware, MFA, auth routes | **CRITICAL** | Global admin panel failure; broken login; disabled security middleware. | Core Architect | Verify middleware pipeline, theme configuration, and plugin discovery. | Smoke test full admin portal login, MFA challenge, and dashboard render. | `docs/architecture/filament-architecture.md`, `docs/ai/architecture-rules.md`. |
| **UI - CustomerPanelProvider** | Modifying `CustomerPanelProvider` configuration | Customer portal UI, auth guard, customer registration, themes | **CRITICAL** | Customer portal broken; leaking admin resources to customer users; guard errors. | Core Architect | Verify portal middleware, `customer` guard, and model binding to `Partner`. | Smoke test customer portal login, profile view, and order history. | `docs/architecture/filament-architecture.md`, `docs/ai/architecture-rules.md`. |
| **Workflow - State Transitions** | Modifying document state transitions (Draft -> Confirmed, etc.) | Document lifecycle, posting locks, inventory allocations | **CRITICAL** | Inconsistent document states; operations executing on cancelled documents. | Domain Specialist | Map out state machine transition graph; verify valid transition guards. | State machine test exercising valid transitions and asserting invalid ones throw. | `docs/workflows/*`, `docs/business-rules/*`. |
| **Workflow - Sales Locking** | Modifying sales order locking mechanics (`is_locked` boolean) | Sales order modifications, recalculations, invoice generation | **HIGH** | Editing locked orders altering historical billing; failing to unlock for revisions. | Sales Domain Lead | Verify `is_locked` boolean attribute check on order line edits. | Feature test attempting to modify order lines when `is_locked === true`. | `docs/workflows/sales.md`, `docs/ai/terminology.md`. |
| **Workflow - Purchase Locking** | Modifying purchase order locking (`OrderState::DONE`) | Vendor order modification, warehouse receipt generation, bills | **HIGH** | Modifying completed purchase orders; confusing state lock with boolean flag. | Purchase Domain Lead | Verify locking check evaluates `state === OrderState::DONE`. | Feature test attempting to edit purchase lines in `DONE` state. | `docs/workflows/purchasing.md`, `docs/ai/terminology.md`. |
| **Workflow - Approvals** | Altering approval thresholds or validation gates | Purchasing approval limits, discount authorizations, credit limits | **HIGH** | Purchase orders confirmed without required manager sign-off; rogue discounts. | Business Domain Lead | Verify threshold evaluation logic and authorization policy gates. | Feature test with order values below, at, and above approval threshold. | `docs/business-rules/purchasing.md`, `docs/business-rules/sales.md`. |
| **Workflow - Preconditions** | Modifying workflow prerequisites or alternative paths | Stock transfers, invoice posting, manufacturing work orders | **HIGH** | Posting un-balanced journal entries; transferring unavailable inventory stock. | Domain Specialist | Trace 8-layer workflow execution model from UI trigger to DB persistence. | Edge-case tests asserting workflow aborts cleanly if preconditions fail. | `docs/workflows/*`, `docs/business-rules/*`. |
| **Business Rule - Double-Entry** | Modifying account move line creation or posting logic | General ledger, trial balance, tax accounting, balance sheet | **CRITICAL** | Unbalanced journal entries (`debit != credit`); catastrophic accounting corruption. | Accounting Specialist & Lead | Verify strict invariant: `sum(debit) === sum(credit)` before `MoveState::POSTED`. | Financial test asserting error when attempting to post unbalanced move. | `docs/business-rules/accounting.md`, `docs/workflows/accounting.md`. |
| **Business Rule - Currency** | Altering currency conversion or exchange gain/loss logic | Multi-currency invoices, payments, foreign currency bank accounts | **CRITICAL** | Financial ledger discrepancies; incorrect foreign exchange gain/loss postings. | Accounting Specialist | Trace exchange rate evaluation and rate date resolution in `Currency`. | Multi-currency transaction test asserting exact decimal ledger balances. | `docs/business-rules/accounting.md`, `docs/ai/terminology.md`. |
| **Business Rule - Stock Valuation** | Altering stock valuation (FIFO/AVCO/Standard) or quants | Inventory balance sheet value, cost of goods sold (COGS), quants | **CRITICAL** | Corrupted inventory valuation; discrepancy between physical stock and ledger. | Inventory Specialist | Audit valuation engine and accounting automated move creation. | Inventory movement test asserting quant updates and valuation postings. | `docs/business-rules/inventory.md`, `docs/workflows/inventory.md`. |
| **Business Rule - Pricing** | Modifying pricing engine, pricelists, or discount rules | Sales order line pricing, purchase order vendor costs | **HIGH** | Incorrect customer billing; price rules not applied to order lines at checkout. | Sales / Pricing Lead | Verify whether pricing rule logic is actively evaluated in checkout workflow. | Test pricing calculations with various partner pricelists and quantity breaks. | `docs/business-rules/sales.md`, `docs/business-rules/purchasing.md`. |
| **Business Rule - Sequences** | Modifying `SequenceService` or sequence formatting | Sequential document codes (`INV/2026/0001`, `SO0001`, `PO0001`) | **CRITICAL** | Duplicate document numbers; gaps in tax invoice sequences violating legal norms. | Core Architect | Review row locking in `SequenceService::generate()` to prevent concurrency race. | Concurrency integration test generating simultaneous sequence numbers. | `docs/ai/terminology.md`, `docs/architecture/overview.md`. |
| **Reactive - Event Dispatch** | Adding, altering, or removing a Domain Event class | Reactive decoupling, event payloads, listener invocation | **HIGH** | Renaming or removing an event property crashes downstream listeners in other plugins. | Lead Architect | Inspect all 28 domain events and identify subscribed listeners across plugins. | Feature test asserting event dispatch and payload integrity. | `docs/architecture/events-catalog.md`. |
| **Reactive - Event Listener** | Modifying Event Listener handling logic | Synchronous cross-plugin side effects (Sales -> Inventory -> Accts) | **CRITICAL** | Unhandled exception in listener rolls back entire primary database transaction. | Domain Specialist | Trace listener execution: wrap non-fatal secondary logic in try-catch if needed. | Integration test asserting listener executes and completes side effects cleanly. | `docs/architecture/events-catalog.md`. |
| **Reactive - Model Observer** | Adding, altering, or removing Eloquent Model Observers | Automatic warehouse creation, UOM validation, attribute matrices | **HIGH** | Silent side effects failing to trigger; unexpected database mutations on save. | Core Architect | Inspect all 7 verified observers; verify registration in service providers. | Unit test asserting observer hooks fire on model create/update/delete. | `docs/architecture/events-catalog.md`, `docs/ai/forbidden-patterns.md`. |
| **Reactive - Notifications** | Modifying `ChatterDatabaseNotification` | In-app alerts, database notifications, asynchronous queueing | **LOW** | Failing to deliver critical workflow notifications to assigned users. | Plugin Lead | Verify `ShouldQueue` contract and `via()` database channel configuration. | Notification test asserting notification record created in `notifications`. | `docs/architecture/overview.md`, chatter docs. |
| **Reactive - UI Polling** | Changing `databaseNotificationsPolling` configuration | Real-time notification updates, server request frequency | **LOW** | Excessive server load from aggressive polling, or delayed user notifications. | Core Architect | Review polling interval (default 30s) against server capacity. | Browser verification observing notification bell update interval. | `docs/architecture/filament-architecture.md`. |
| **Reactive - Console Cron** | Modifying scheduled commands in `routes/console.php` | Background maintenance, automated recurring tasks | **MEDIUM** | Missed execution of scheduled recurring jobs; cron command failures. | DevOps / Core Lead | Verify command signature, parameters, and schedule interval (`daily`, `hourly`). | Test executing command directly via `php artisan` in terminal. | `docs/architecture/overview.md`, `routes/console.php`. |
| **Services - Domain Service** | Modifying method signatures or logic in Domain Services | Core business calculations, stock moves, invoice posting | **HIGH** | Broken cross-plugin service calls; invalid calculations propagated to models. | Domain Lead | Identify all 53 domain services and audit consuming callers across plugins. | Service unit and integration tests asserting calculation accuracy. | Owning plugin docs, `docs/architecture/overview.md`. |
| **Services - Cross-Plugin Calls** | Calling an optional plugin service from another plugin | Modularity boundaries, runtime decoupling, optionality | **HIGH** | Application crashes when target optional plugin is disabled in production. | Architecture Lead | Verify existence check: `Package::isPluginInstalled('plugin-name')` before call. | Test executing workflow with target plugin enabled vs deactivated. | `docs/ai/architecture-rules.md`, `docs/ai/plugin-rules.md`. |
| **Testing - Untested Plugin** | Adding automated tests to one of the 17 untested plugins | Test suite coverage, CI verification reliability | **LOW** *(Positive)* | Test failures caused by incomplete test environment setup or missing factories. | QA / Testing Lead | Follow Phase 10 Testing Rules: create isolated test suite with proper fixtures. | Run `vendor/bin/pest plugins/webkul/<plugin>/tests/`. | `docs/ai/testing-rules.md`. |
| **Testing - Existing Tests** | Modifying existing Pest tests in the 11 tested plugins | Regression suite validity, continuous integration pipeline | **MEDIUM** | Relaxing assertions weakens quality gate; deleting tests masks regressions. | QA / Testing Lead | Verify assertions remain strict; avoid testing mock data instead of real behavior. | Run full test suite: `php artisan test --compact`. | `docs/ai/testing-rules.md`. |
| **Testing - Shared Helpers** | Modifying shared test helpers, traits, or factories | All automated tests across plugins using the helper | **HIGH** | Widespread cascading test failures across previously green test suites. | QA / Testing Lead | Run test suites across all 11 tested plugins before committing helper changes. | Run full test runner across all plugins to confirm zero regressions. | `docs/ai/testing-rules.md`. |
| **AI Rule - terminology.md** | Modifying canonical terms or misconception definitions | AI assistant vocabulary, developer conventions, code generation | **HIGH** | AI agents introducing forbidden terms (`HasCompanyScope`, `self`, `silber/bouncer`). | AI Governance Lead | Verify source code evidence before updating canonical definitions. | Lint living documentation for compliance with terminology rules. | `docs/ai/terminology.md`, all rule files. |
| **AI Rule - forbidden-patterns** | Modifying or adding forbidden patterns in AI rules | AI assistant code generation constraints, quality guardrails | **HIGH** | Permitting previously blocked anti-patterns, or blocking valid idioms. | AI Governance Lead | Verify specific repository instances and evidence citations for the pattern. | Review AI prompts and generated code against forbidden pattern catalog. | `docs/ai/forbidden-patterns.md`. |
| **AI Rule - architecture-rules** | Modifying architectural guidelines or panel boundaries | Plugin boundaries, zero-table constraints, panel providers | **HIGH** | Architectural erosion; ad-hoc migrations in zero-table modules; broken guards. | Lead Architect | Verify architectural rule aligns with verified source code reality. | Audit new PRs against modified architecture rules. | `docs/ai/architecture-rules.md`. |
| **AI Rule - plugin-rules** | Modifying plugin creation or lifecycle rules | Plugin structure, ServiceProvider conventions, dependency wiring | **HIGH** | New plugins created with non-standard layout or broken provider registration. | Lead Architect | Verify checklist reflects current `plugin-manager` package mechanics. | Verify new plugins pass all 13 plugin creation checklist items. | `docs/ai/plugin-rules.md`. |
| **AI Rule - database-rules** | Modifying database, migration, or foreign key rules | Schema design, delete behaviors, migration registration standards | **HIGH** | Unregistered migrations on disk; unjustified foreign key delete actions. | Database Specialist | Verify rules reflect semantic lifecycle determination over statistical defaults. | Audit new migrations against database rules checklist. | `docs/ai/database-rules.md`. |
| **AI Rule - security-rules** | Modifying tenancy, Bouncer, or query scoping rules | Multi-company enforcement, raw SQL safety, authorization checks | **CRITICAL** | Insecure code generation; un-scoped raw queries; multi-company vulnerabilities. | Security Specialist | Verify security rules enforce complete query surface review. | Audit security tests and raw SQL sites against updated rules. | `docs/ai/security-rules.md`. |
| **AI Rule - coding-rules** | Modifying general coding or Filament standards | Code style, Livewire v4 idioms, Filament form/table conventions | **MEDIUM** | Outdated Livewire v3 patterns generated; inconsistent component design. | Tech Lead | Verify alignment with Livewire v4.3.3 and Filament v5.7.6 specifications. | Run Laravel Pint and PHPStan across modified files. | `docs/ai/coding-rules.md`. |
| **AI Rule - testing-rules** | Modifying Pest testing conventions or priority tiers | Automated test structure, priority thresholds, coverage standards | **MEDIUM** | Ineffective tests written; priority high-risk zones left untested. | QA / Testing Lead | Verify testing standards enforce real model interactions over excessive mocking. | Run test runner to verify compliance with test formatting standards. | `docs/ai/testing-rules.md`. |

---

## 16. Change Impact Statistics & Matrix Rollup

### 16.1 Numerical Rollup

The Master Change-Impact Matrix synthesizes the complete empirical reality of the Aureus ERP repository into a structured decision instrument:

```
┌─────────────────────────────────────────────────────────────┬────────┐
│ Metric Description                                          │ Value  │
├─────────────────────────────────────────────────────────────┼────────┤
│ Total Change-Impact Matrix Rows                             │ 93     │
│ Baseline Categories (Prompt Section 8 Seed Rows)            │ 12     │
│ Expanded Rows (Refined from Section 8 Baseline)             │ 36     │
│ Entirely New Rows (Discovered across Sections 9–21 Audits)  │ 45     │
└─────────────────────────────────────────────────────────────┴────────┘
```

### 16.2 Major Newly Discovered Categories

The 45 entirely new rows introduced beyond the initial baseline cover vital operational domains that were previously unmapped or implicit:
1. **Dynamic Schema Mechanisms**: Granular impact rules for `resolveRelationUsing()` (22 sites across 4 providers), `CompanyProperty` custom EAV casting, and `HasCustomFields` runtime schema injection.
2. **Tenancy & Isolation Integrity**: Explicit rules for `CompanyContext`, `CompanyScope` / `CompaniesScope`, `ChecksCompanyConsistency`, `ChecksCrossCompanyTransfer`, and raw SQL complete query surface reviews (143 raw query sites).
3. **Security & Authorization Deep-Dives**: Proprietary `Webkul\Security\Bouncer`, canonical `PermissionType` scoping (`GLOBAL`, `GROUP`, `INDIVIDUAL`), `OwnerSource` 4-way resolution, and Dual User Model (`Webkul\Security\Models\User` vs `App\Models\User`) / Dual Panel boundaries.
4. **Zero-Table Extension Layers**: Formal governance protecting the 6 zero-table plugins (`accounting`, `barcode`, `contacts`, `full-calendar`, `invoices`, `timesheets`) from illegal ad-hoc schema additions.
5. **Document Sequencing**: Cross-cutting blast radius of `Webkul\Support\Services\SequenceService` on `support_sequences`.
6. **Asynchronous Runtime Reality**: Explicit governance for queued notifications (`ChatterDatabaseNotification`), client-side polling (`30s`), and scheduled console cron jobs in the absence of queue Job classes.
7. **Document Locking Duality**: Explicit distinction between sales order boolean locking (`is_locked`) and purchase order state locking (`OrderState::DONE`).
8. **Testing Governance**: Distinct impact rules for changes in the 11 tested plugins vs changes in the 17 untested plugins.
9. **AI Rule Governance**: Full blast radius mapping for all 8 Phase 10 AI rule files in `docs/ai/`.

---

## 17. Reusable 19-Step Change Impact Procedure

Any human engineer or AI coding agent preparing a change in Aureus ERP MUST execute this 19-step procedure:

```
STEP 1  ──► Identify the exact changed artifact (files, classes, methods, columns)
STEP 2  ──► Classify the change using the Master Change-Impact Matrix
STEP 3  ──► Identify direct consumers (callers, child classes, route bindings)
STEP 4  ──► Identify indirect / transitive consumers (events, listeners, observers)
STEP 5  ──► Check plugin dependencies (hasDependencies vs Composer require vs imports)
STEP 6  ──► Check database & data impact (hasMigrations, FK delete rules, nullability)
STEP 7  ──► Check company isolation (CompanyScope, tenancy traits, raw SQL surface)
STEP 8  ──► Check authorization & security (Bouncer, OwnershipScope, policies, guards)
STEP 9  ──► Check UI & Filament exposure (Admin vs Customer panel, clusters, pages)
STEP 10 ──► Trace workflow impact through the 8-Layer Workflow Model
STEP 11 ──► Check reactive events, listeners, and model observers
STEP 12 ──► Check business rules (Declaration != Enforcement; Enum != Workflow)
STEP 13 ──► Determine required review categories (Architecture, Security, Database, Domain)
STEP 14 ──► Determine required automated tests (Pest feature/unit tests, new tests if untested)
STEP 15 ──► Determine deployment & rollback implications (data loss, migration order)
STEP 16 ──► Check documentation impact (identify stale docs for separate task queue)
STEP 17 ──► Execute verification (static analysis, tests, manual checks)
STEP 18 ──► Record UNKNOWN / INFERRED items explicitly
STEP 19 ──► Formulate Change Decision (VERIFIED SAFE / BLOCKED / VERIFICATION REQUIRED)
```

---

## 18. Pre-Merge AI Change-Impact Checklist

Before proposing or applying any code modification, AI agents MUST verify:

- [ ] **Axiom Check**: Does this change assume local correctness equals system safety?
- [ ] **Multi-Company Check**: Does this change touch any model or query without verifying company isolation?
- [ ] **Forbidden Symbol**: Does the code reference `HasCompanyScope`? (Must be rejected).
- [ ] **Bouncer Distinction**: Does the code treat Bouncer as `silber/bouncer`? (Must be rejected).
- [ ] **Permission Case**: Does the code use `'self'` instead of `PermissionType::INDIVIDUAL`?
- [ ] **Panel Boundary**: Does code in the customer panel reference `App\Models\User` or admin guards?
- [ ] **Migration Registration**: If a new migration is created, is it registered in `hasMigrations([...])`?
- [ ] **FK Delete Action**: Is the foreign key delete action justified by relationship lifecycle semantics?
- [ ] **Raw SQL Safety**: If using raw queries, is the complete query surface (joins, pivots, subqueries) filtered by tenant?
- [ ] **Dependency Rule**: If consuming another plugin, is it in `hasDependencies()` or gated by `Package::isPluginInstalled()`?
- [ ] **Zero-Table Rule**: Is this change adding ad-hoc migrations to a zero-table plugin? (Forbidden).
- [ ] **Async Reality**: Does this change claim Aureus has no asynchronous behavior? (False: notifications & polling exist).
- [ ] **Test Reality**: Is this change touching an untested plugin, and is manual verification or a new test required?
- [ ] **Path Formatting**: Are all references in documentation formatted as repository-relative paths?

---

## 19. Known Proposed Corrections & Unresolved Items

The following historical discrepancies identified during Phases 0–10 are preserved here as authoritative context. They MUST NOT be silently altered:

```
┌────┬──────────────┬──────────────────────────────┬─────────────────────────────────────────────────────┬──────────────────┐
│ ID │ Source Phase │ Affected Document / Symbol   │ Documented Discrepancy                              │ Current Status   │
├────┼──────────────┼──────────────────────────────┼─────────────────────────────────────────────────────┼──────────────────┤
│ C1 │ Phase 10     │ AGENTS.md:19                 │ States Livewire v3; composer.lock proves Livewire v4│ Pending update   │
│ C2 │ Phase 4      │ SupportServiceProvider.php   │ Declares missing migration 'email_templates'        │ Pending fix      │
│ C3 │ Phase 4      │ SupportServiceProvider.php   │ Missing registration for companies unique index     │ Pending fix      │
│ C4 │ Phase 4      │ TimeOffServiceProvider.php   │ Missing registration for default leave types        │ Pending fix      │
│ C5 │ Phase 4      │ EmployeeServiceProvider.php  │ 3 orphaned calendar migration files on disk         │ Pending clean up │
│ C6 │ Phase 3      │ Webkul\Security\Bouncer      │ Historically conflated with silber/bouncer          │ Documented       │
│ C7 │ Phase 10     │ Historical Audits            │ Historical claim of 6 observers (actually 7)        │ Documented       │
│ C8 │ Phase 10     │ Historical Audits            │ Historical claim of 52 services (actually 53)       │ Documented       │
└────┴──────────────┴──────────────────────────────┴─────────────────────────────────────────────────────┴──────────────────┘
```

### Known [UNKNOWN] and [INFERRED] Items
- **[UNKNOWN] Complete API Route Authorization Map**: While Filament panels use Shield and Bouncer, direct API routes under `plugins/webkul/*/routes/api.php` exhibit inconsistent policy gating across all 28 plugins; full API auth coverage requires an endpoint-by-endpoint audit.
- **[UNKNOWN] Production Data Volume Constraints**: Exact production row counts for `accounts_account_move_lines` and `inventories_moves` are environment-dependent; migration locking risks must be evaluated per target deployment.
- **[UNKNOWN] External Queue Worker Infrastructure**: The repository defines zero Job classes, but whether production hosting environments configure queue workers for `ChatterDatabaseNotification` (`ShouldQueue`) depends on client deployment infrastructure.
- **[INFERRED] Zero-Table Extension Architectural Intent**: It is inferred that `accounting`, `barcode`, `contacts`, `full-calendar`, `invoices`, and `timesheets` omit migrations deliberately to prevent schema duplication, rather than due to incomplete development.
- **[INFERRED] Synchronous Cross-Plugin Design Choice**: It is inferred that domain events and observers were implemented synchronously within database transactions to guarantee transactional consistency across sales, inventory, and accounting ledgers.

---

## 20. Repository Evidence & Canonical Citations

Every rule, catalog entry, and architectural constraint in this document is backed by verified repository evidence:

- **Multi-Company Tenancy**:
  - `plugins/webkul/support/src/Traits/BelongsToCompany.php`
  - `plugins/webkul/support/src/Traits/BelongsToCompanies.php`
  - `plugins/webkul/support/src/Services/CompanyContext.php`
  - `plugins/webkul/support/src/Models/Scopes/CompanyScope.php`
  - `plugins/webkul/support/src/Models/Scopes/CompaniesScope.php`
  - `plugins/webkul/support/src/Traits/ChecksCompanyConsistency.php`
  - `plugins/webkul/security/src/Http/Middleware/RestrictToAllowedCompanies.php`
- **Security & Authorization**:
  - `plugins/webkul/security/src/Bouncer.php`
  - `plugins/webkul/security/src/Enums/PermissionType.php`
  - `plugins/webkul/security/src/Models/Scopes/OwnershipScope.php`
  - `plugins/webkul/security/src/Traits/HasOwner.php`
  - `plugins/webkul/security/src/Models/User.php`
  - `app/Models/User.php`
- **Plugin Lifecycle & Dependencies**:
  - `plugins/webkul/plugin-manager/src/Package.php`
  - `plugins/webkul/plugin-manager/src/PackageServiceProvider.php`
  - `plugins/webkul/plugin-manager/src/Console/Commands/InstallPluginCommand.php`
  - `bootstrap/providers.php`
  - `composer.json` & `composer.lock`
- **Dynamic Schema & Casting**:
  - `plugins/webkul/accounts/src/AccountServiceProvider.php` (`resolveRelationUsing`)
  - `plugins/webkul/accounts/src/Casts/CompanyProperty.php`
  - `plugins/webkul/fields/src/Traits/HasCustomFields.php`
  - `plugins/webkul/fields/src/FieldsColumnManager.php`
- **Events, Observers & Services**:
  - `plugins/webkul/sales/src/SaleServiceProvider.php`
  - `plugins/webkul/purchases/src/PurchaseServiceProvider.php`
  - `plugins/webkul/inventories/src/InventoryServiceProvider.php`
  - `plugins/webkul/manufacturing/src/ManufacturingServiceProvider.php`
  - `plugins/webkul/support/src/Services/SequenceService.php`
  - `plugins/webkul/chatter/src/Notifications/ChatterDatabaseNotification.php`
- **Filament Presentation & Panels**:
  - `app/Providers/Filament/AdminPanelProvider.php`
  - `app/Providers/Filament/CustomerPanelProvider.php`
  - `plugins/webkul/support/src/Enums/NavigationGroup.php`
- **Knowledge Base References**:
  - `docs/ai/terminology.md`
  - `docs/ai/architecture-rules.md`
  - `docs/ai/plugin-rules.md`
  - `docs/ai/database-rules.md`
  - `docs/ai/security-rules.md`
  - `docs/ai/coding-rules.md`
  - `docs/ai/testing-rules.md`
  - `docs/ai/forbidden-patterns.md`
  - `docs/architecture/filament-architecture.md`
  - `docs/architecture/dynamic-schema.md`
  - `docs/architecture/events-catalog.md`
  - `docs/database/company-isolation.md`
  - `docs/database/schema-conventions.md`
