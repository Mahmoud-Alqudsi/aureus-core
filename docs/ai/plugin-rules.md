---
status: verified
source_of_truth: source-code
last_verified: 2026-09-03
scope: global
confidence: high
---

# Aureus ERP — Plugin Rules

## 1. Overview & Purpose

This document defines the binding rules and checklists for creating, modifying, testing, and reviewing plugins within Aureus ERP. All domain modules reside under `plugins/webkul/<plugin>/` as local modular Laravel packages.

Every rule herein is prescriptive. Developers and AI agents MUST follow these standardized checklists and decision workflows when interacting with the plugin ecosystem.

---

## 2. New Plugin Creation Checklist

When creating a new plugin in `plugins/webkul/<new-plugin>/`, developers and AI agents MUST complete and verify each item in this checklist:

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                New Plugin Architecture Checklist                                 │
├────┬─────────────────────────────────┬───────────────────────────────────────────────────────────┤
│ 1  │ Directory & Composer            │ plugins/webkul/<plugin>/composer.json + PSR-4 mapping     │
│ 2  │ Service Provider                │ src/<Plugin>ServiceProvider.php extending PackageService │
│ 3  │ Root Provider Registration      │ Explicit entry added to bootstrap/providers.php           │
│ 4  │ Core vs Optional Decision       │ Explicit $package->isCore() decision                      │
│ 5  │ Runtime Dependencies            │ Explicit $package->hasDependencies([...]) declaration     │
│ 6  │ Filament Plugin Class           │ src/<Plugin>Plugin.php implementing Filament Plugin       │
│ 7  │ Panel Participation             │ Explicit admin vs customer branch in register(Panel)      │
│ 8  │ Persistence Strategy            │ Owns migrations OR Zero-Table Extension documentation     │
│ 9  │ Multi-Company Isolation         │ Deliberate BelongsToCompany vs BelongsToCompanies decision│
│ 10 │ Automated Test Suite            │ tests/Feature and tests/Unit with Pest test cases         │
└────┴─────────────────────────────────┴───────────────────────────────────────────────────────────┘
```

### 1. Directory Structure & `composer.json`
- MUST create `plugins/webkul/<plugin>/composer.json` declaring:
  - Package name: `"webkul/<plugin>"`
  - Autoload PSR-4 mapping: `"Webkul\\<Namespace>\\": "src/"`
  - Autoload database paths if present: `"Webkul\\<Namespace>\\Database\\Factories\\": "database/factories/"`, `"Webkul\\<Namespace>\\Database\\Seeders\\": "database/seeders/"`
  - Autoload dev mapping: `"Webkul\\<Namespace>\\Tests\\": "tests/"`
  - Extra provider declaration: `"extra": { "laravel": { "providers": ["Webkul\\<Namespace>\\<Plugin>ServiceProvider"] } }`

### 2. Service Provider (`*ServiceProvider.php`)
- MUST extend `Webkul\PluginManager\PackageServiceProvider`.
- MUST implement `configureCustomPackage(Package $package)`:
  - Set name: `$package->name(static::$name);`
  - Register translations if strings exist: `$package->hasTranslations();`
  - Register views if blade templates exist: `$package->hasViews();`
  - Register migrations if owning database tables: `$package->hasMigrations([...])->runsMigrations();`
  - Register install/uninstall hooks: `$package->hasInstallCommand(...)->hasUninstallCommand(...);`
- MUST register Filament plugin in `packageRegistered()` via `Panel::configureUsing()`.

### 3. Root Application Registration
- MUST explicitly add `<Plugin>ServiceProvider::class` to `bootstrap/providers.php`. Local plugins are NOT auto-discovered by Laravel without this registration.

### 4. Core vs Optional Decision
- MUST make an explicit decision:
  - Call `$package->isCore()` ONLY if the plugin provides foundational, cross-cutting ERP infrastructure (such as security, company tenancy, audit logging, custom fields, or plugin management) that MUST execute unconditionally without database gating.
  - OMIT `$package->isCore()` if the plugin is a business domain module (e.g., sales, purchases, manufacturing). Optional plugins are gated by runtime database installation state (`Package::isPluginInstalled()`).

### 5. Runtime Dependency Declaration
- Cross-reference: See the **Dependency Rule** in `docs/ai/architecture-rules.md`.
- A new plugin MUST declare all installation-prerequisite plugins via `$package->hasDependencies(['prerequisite-plugin'])`.
- Adding dependencies to `composer.json` does NOT establish plugin installation ordering.

### 6. Filament Plugin Class (`*Plugin.php`)
- MUST create `src/<Plugin>Plugin.php` implementing `Filament\Contracts\Plugin` with `getId()`, `make()`, and `register(Panel $panel)`.
- EXCEPTIONS: Only the two established architectural exception patterns (`analytics` with no direct UI, or `table-views` with global panel render hooks) are exempt from defining a `*Plugin.php`. Developers MUST NOT omit `*Plugin.php` merely because a plugin currently lacks UI components.

### 7. Panel Participation
- Inside `*Plugin.php::register(Panel $panel)`, the plugin MUST explicitly evaluate `$panel->getId()`:
  - Back-office administration resources MUST register only when `$panel->getId() === 'admin'`.
  - Customer portal resources MUST register only when `$panel->getId() === 'customer'`.
  - Optional plugins MUST wrap resource discovery in `if (! Package::isPluginInstalled($this->getId())) { return; }`.

### 8. Persistence Strategy
- If the plugin introduces new business state, it MUST create database migrations, register them via `hasMigrations([...])`, and execute them via `runsMigrations()`.
- If the plugin owns zero tables, it MUST document the exact upstream schema it extends following the **Zero-Table Extension Layer Pattern** defined in `docs/ai/architecture-rules.md`.

### 9. Multi-Company Isolation Strategy
- Any company-sensitive model MUST deliberately choose between `BelongsToCompany` and `BelongsToCompanies` according to Section 4 of this document.

### 10. Automated Tests
- A new plugin MUST include an automated test suite under `plugins/webkul/<plugin>/tests/` with Pest feature tests covering core workflows, policies, and multi-company boundaries.

---

## 3. Existing Plugin Modification Checklist

Before modifying, refactoring, or extending an existing plugin in `plugins/webkul/<plugin>/`, developers and AI agents MUST verify every step in this checklist:

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────┐
│                            Existing Plugin Modification Checklist                                │
├────┬─────────────────────────────┬───────────────────────────────────────────────────────────────┤
│ 1  │ Registration Verification   │ Verify provider in bootstrap/providers.php & package name     │
│ 2  │ Dependency Audit            │ Check hasDependencies() before importing external models       │
│ 3  │ Zero-Table Awareness        │ Check if plugin owns tables before adding database logic      │
│ 4  │ Migration Registration      │ Confirm new migrations are in hasMigrations() array           │
│ 5  │ Filament Panel Boundary     │ Verify if changes affect admin, customer, or both panels      │
│ 6  │ Installation Guard Check    │ Confirm changes respect Package::isPluginInstalled() gates    │
│ 7  │ Security & Authorization    │ Check Policy registration, Shield permissions, & Bouncer      │
│ 8  │ Cross-Plugin Coupling       │ Audit resolveRelationUsing(), events, and chatter listeners   │
│ 9  │ Test Suite Audit            │ Verify test presence; write tests if missing or extending     │
└────┴─────────────────────────────┴───────────────────────────────────────────────────────────────┘
```

### 1. Registration & Identity Verification
- Verify that the plugin's service provider is active in `bootstrap/providers.php` and locate its `*ServiceProvider.php` and `*Plugin.php`.

### 2. Dependency Audit
- Before consuming models, services, or tables from another plugin:
  - Check whether the target plugin is declared in `hasDependencies()`.
  - If consuming an optional plugin from another optional plugin, ensure runtime checks (`Package::isPluginInstalled('target')`) protect execution paths, or declare an explicit runtime dependency.

### 3. Zero-Table Awareness
- Check whether the target plugin is one of the 6 zero-table plugins (`accounting`, `barcode`, `contacts`, `full-calendar`, `invoices`, `timesheets`).
- Developers MUST NOT add ad-hoc database migrations to zero-table plugins without a formal architectural decision to convert the plugin into a persistence-owning package.

### 4. Migration Registration Verification
- **CRITICAL**: If adding a database migration file to a plugin:
  - The migration file MUST be explicitly listed inside `$package->hasMigrations([...])` in `configureCustomPackage()`.
  - The provider MUST call `$package->runsMigrations()`.
  - A migration file existing on disk MUST NOT be assumed to run automatically.

### 5. Filament Panel Boundary Verification
- Check whether the modified resource or page is intended for `admin`, `customer`, or both.
- Ensure that customer panel modifications do NOT reference `App\Models\User` or admin-specific middleware/guards.

### 6. Installation State Guard Check
- Ensure that new Livewire components, routes, or console commands respect the plugin's installation state. Gating in Filament `register()` does not automatically protect raw HTTP routes or CLI commands.

### 7. Security & Authorization Audit
- Check whether the entity requires:
  - A dedicated Policy (e.g., in `src/Policies/`).
  - Filament Shield permission generation (`config/filament-shield.php`).
  - Record ownership resolution via `HasOwner` and `Webkul\Security\Bouncer`.
- Remember: **Declaration ≠ Enforcement**. Declaring a policy or permission in UI schemas does NOT guarantee backend or API isolation.

### 8. Cross-Plugin Coupling Audit
- Search the repository with `rg` for the changed symbol to check for:
  - Dynamic relationships injected via `resolveRelationUsing()`.
  - Cross-plugin event listeners (consult `docs/architecture/events-catalog.md`).
  - Chatter logging hooks (`HasLogActivity`, `HasChatter`).

### 9. Test Suite Audit & Baseline
- Audit existing test coverage in `plugins/webkul/<plugin>/tests/`.
- If modifying an existing plugin with ZERO automated test coverage (see Section 5 below), developers MUST NOT use the historical absence of tests as justification to omit testing for new or modified features.

---

## 4. Multi-Company Isolation Decision Rule: `BelongsToCompany` vs `BelongsToCompanies`

A plugin introducing a company-sensitive model or database table MUST deliberately select the correct tenancy mechanism based on semantic lifecycle requirements:

```
                                  Does the model belong to
                                  one company or multiple?
                                             │
                       ┌─────────────────────┴─────────────────────┐
                       ▼                                           ▼
                 Single Company                             Multiple Companies
                       │                                           │
         Is foreign key company_id                    Does the model use a many-to-many
          directly on the table?                        pivot table with companies?
                       │                                           │
                       ▼                                           ▼
            BelongsToCompany Trait                      BelongsToCompanies Trait
                       │                                           │
     • Adds CompanyScope global scope            • Adds CompaniesScope global scope
     • Auto-assigns active company on create     • Filters via $model->companies() relation
     • Supports optional null company rows       • Requires pivot (e.g. accounts_account_companies)
```

### Semantic Comparison

| Dimension | `BelongsToCompany` | `BelongsToCompanies` |
| :--- | :--- | :--- |
| **Tenancy Cardinality** | **Strict 1:1 or 1:0** (Single tenant owner or global null). | **M:N** (Record simultaneously accessible across multiple tenant companies). |
| **Database Schema** | Physical `company_id` column directly on the model's table. | Intermediate pivot table linking `model_id` and `company_id`. |
| **Global Eloquent Scope** | `Webkul\Support\Models\Scopes\CompanyScope` | `Webkul\Support\Models\Scopes\CompaniesScope` |
| **Creation Behavior** | Automatically populates `company_id` from `CompanyContext::getActiveCompany()` when `autoAssignsCompany()` is true. | Does NOT auto-populate pivot; relationships must be explicitly synced via `$model->companies()->sync(...)`. |
| **Query Filter** | `WHERE (company_id IN (activeCompanyIds) OR company_id IS NULL)` | `WHERE (EXISTS in pivot WHERE company_id IN (activeCompanyIds) OR DOES NOT HAVE companies)` |
| **Established Examples** | `Order` (Sales/Purchases), `Move` (Accounts), `Operation` (Inventories), `Partner`, `Product`. | `Account` (`accounts_account_companies`), `User` (`user_allowed_companies`). |

### Prescriptive Rules: Company Isolation
- A neighboring model's company trait MUST NOT be copied blindly.
- When a record is owned exclusively by one company at a time (e.g., sales orders, invoices, stock operations), developers MUST use `Webkul\Support\Traits\BelongsToCompany`.
- When a master record or configuration is shared across a defined subset of companies (e.g., Chart of Accounts), developers MUST use `Webkul\Support\Traits\BelongsToCompanies` and define a dedicated pivot table.
- When attaching `BelongsToCompany`, the database migration MUST include a `company_id` foreign key with an explicit delete action (`restrictOnDelete()`, `nullOnDelete()`, or `cascadeOnDelete()`).
- Master models that permit global un-scoped fallback rows (such as `Product` and `Partner`) MUST explicitly override `autoAssignsCompany(): bool { return false; }` to prevent unintentional tenant locking on create.

---

## 5. Automated Test Coverage Baseline (Fresh Repository Audit)

A fresh repository audit confirms that automated test coverage is **highly uneven** across the 28 plugins:

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────┐
│                           Fresh Plugin Test Coverage Audit Summary                               │
├────────────────────────────┬─────────────────────────────┬───────────────────────────────────────┤
│ Category                   │ Total In Category           │ Plugins with ZERO Test Coverage       │
├────────────────────────────┼─────────────────────────────┼───────────────────────────────────────┤
│ Core Plugins               │ 9 plugins                   │ 7 plugins (77.8% zero coverage)       │
│ Optional Plugins           │ 19 plugins                  │ 10 plugins (52.6% zero coverage)      │
├────────────────────────────┼─────────────────────────────┼───────────────────────────────────────┤
│ Total Entire Repository    │ 28 plugins                  │ 17 plugins (60.7% zero coverage)      │
└────────────────────────────┴─────────────────────────────┴───────────────────────────────────────┘
```

### Complete Breakdown by Category

#### 1. Core Plugins (9 Total)
- **Core Plugins with Automated Tests (2)**:
  - `partners` (9 test files)
  - `support` (17 test files)
- **Core Plugins with ZERO Automated Test Coverage (7)**:
  - `analytics` (`tests/` does not exist)
  - `chatter` (`tests/` does not exist)
  - `fields` (`tests/` does not exist)
  - `full-calendar` (`tests/` does not exist)
  - `plugin-manager` (`tests/` does not exist)
  - `security` (`tests/` does not exist)
  - `table-views` (`tests/` does not exist)

#### 2. Optional Plugins (19 Total)
- **Optional Plugins with Automated Tests (9)**:
  - `accounting` (9 test files)
  - `accounts` (42 test files)
  - `employees` (5 test files)
  - `inventories` (41 test files)
  - `manufacturing` (8 test files)
  - `products` (16 test files)
  - `projects` (8 test files)
  - `purchases` (15 test files)
  - `sales` (17 test files)
- **Optional Plugins with ZERO Automated Test Coverage (10)**:
  - `barcode` (`tests/` does not exist)
  - `blogs` (`tests/` does not exist)
  - `contacts` (`tests/` does not exist)
  - `invoices` (`tests/` does not exist)
  - `maintenance` (`tests/` does not exist)
  - `payments` (`tests/` does not exist)
  - `recruitments` (`tests/` does not exist)
  - `time-off` (`tests/` does not exist)
  - `timesheets` (`tests/` does not exist)
  - `website` (`tests/` does not exist)

### Prescriptive Rules: Testing Baseline
- **Historical Practice ≠ Future Standard**: The fact that 17 existing plugins lack automated tests MUST NOT become the future standard.
- New plugins MUST NOT be created without automated tests.
- High-risk modifications to zero-coverage plugins (especially `security`, `payments`, and `invoices`) MUST include new Pest test cases verifying the changed functionality.
- Developers and AI agents MUST NOT delete existing test cases without explicit user approval.
