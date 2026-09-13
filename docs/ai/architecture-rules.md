---
status: verified
source_of_truth: source-code
last_verified: 2026-09-03
scope: global
confidence: high
---

# Aureus ERP — Architecture Rules

## 1. Overview & Architectural Principles

This document defines the binding structural and architectural rules for Aureus ERP. It establishes mandatory standards for plugin registration lifecycles, dependency management, zero-table extensions, multi-panel participation, navigation composition, and dynamic schema extensions.

Every rule herein is prescriptive. Developers and AI agents modifying, extending, or creating packages in this repository MUST comply with these rules.

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                   Aureus ERP Architecture Layering                                     │
├────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│  Presentation Layer:    Filament Panels (admin & customer), Clusters, Pages, Widgets, Table Views     │
│  Domain Plugin Layer:   Local Packages (plugins/webkul/*) extending PackageServiceProvider            │
│  Lifecycle & Runtime:   Webkul\PluginManager (Package, isCore, isInstalled, hasDependencies)          │
│  Data & Schema Layer:   MySQL Schemas, Dynamic Relationships, Company Scopes, Custom Fields            │
└────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Plugin Registration Lifecycle

### The Standard Lifecycle Chain

Aureus ERP plugins operate as modular Laravel packages residing under `plugins/webkul/<plugin>/`. Every plugin executes through an explicit multi-stage lifecycle:

```
composer-merge-plugin
        ↓
PackageServiceProvider
        ↓
Panel::configureUsing()
        ↓
local *Plugin.php
        ↓
register(Panel $panel)
```

1. **Autoload & Merge Phase (`wikimedia/composer-merge-plugin`)**:
   The root `composer.json` merges each plugin's `composer.json` metadata, registering PSR-4 namespaces and development autoload paths.
2. **Service Provider Registration Phase (`bootstrap/providers.php`)**:
   Every plugin's service provider extending `Webkul\PluginManager\PackageServiceProvider` is explicitly listed in `bootstrap/providers.php`. The provider's `configureCustomPackage(Package $package)` method defines package metadata, core status (`isCore()`), migrations, views, translations, and runtime dependencies (`hasDependencies()`).
3. **Panel Configuration Phase (`Panel::configureUsing()`)**:
   During `packageRegistered()`, the service provider hooks into Filament's panel builder and binds a local Filament plugin instance via `$panel->plugin(SomePlugin::make())`.
4. **Local Plugin Registration & Discovery (`*Plugin.php::register()`)**:
   Filament invokes `register(Panel $panel)` on the local plugin instance. The plugin inspects `$panel->getId()` to target specific panels and checks `Package::isPluginInstalled($this->getId())` before discovering resources, pages, clusters, and widgets.

### Architectural Exceptions to the Lifecycle

The repository contains two verified architectural exceptions to the local `*Plugin.php` convention:

1. **`analytics` (`Webkul\Analytic`)**:
   Defines ZERO Filament plugin classes (`AnalyticPlugin.php` does not exist) and registers no direct UI components. It provides foundational models (`Record`) and schemas (`analytic_records`) consumed by downstream modules.
2. **`table-views` (`Webkul\TableViews`)**:
   Defines ZERO `*Plugin.php` classes (`TableViewsPlugin.php` does not exist). Instead of registering via `$panel->plugin()`, it injects table personalization tabs globally across all panels via Filament panel render hooks (`PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE` and `PanelsRenderHook::RESOURCE_PAGES_MANAGE_RELATED_RECORDS_TABLE_BEFORE`) inside `TableViewsServiceProvider::packageRegistered()`.

### Prescriptive Rules: Lifecycle
- A new plugin MUST extend `Webkul\PluginManager\PackageServiceProvider`.
- Every plugin service provider MUST be explicitly registered in `bootstrap/providers.php`.
- Optional plugins MUST gate their Filament UI registration inside `*Plugin.php::register()` using `Package::isPluginInstalled($this->getId())`.
- Developers MUST NOT create an `AnalyticPlugin` or `TableViewsPlugin` class.
- Cross-cutting global UI hooks MAY be registered via `PanelsRenderHook` in `packageRegistered()` ONLY when the feature applies universally to all tables/panels without requiring panel-specific discovery.

---

## 3. The Dependency Rule

The Aureus ERP architecture strictly separates three dependency concepts:

```
┌───────────────────────────────┐     ┌────────────────────────────────┐     ┌───────────────────────────────┐
│     Composer Dependency       │  ≠  │  Plugin Installation Dependency│  ≠  │    Code-Level Consumption     │
├───────────────────────────────┤     ├────────────────────────────────┤     ├───────────────────────────────┤
│ • Declared in composer.json   │     │ • Declared in Package provider │     │ • PHP use statements          │
│ • Governs class autoloading   │     │   via hasDependencies([...])   │     │ • Cross-plugin model calls    │
│ • Resolved by Composer CLI    │     │ • Consumed by InstallCommand   │     │ • No ordering guarantee       │
│ • No ERP install order impact │     │ • Controls DB migration order  │     │ • No install guard            │
└───────────────────────────────┘     └────────────────────────────────┘     └───────────────────────────────┘
```

### Mandatory Formulations
- **A plugin MUST declare Aureus plugin installation-order dependencies through `hasDependencies()`.**
- **A Composer dependency MAY exist when technically required, but `composer.json` MUST NOT be treated as the source of truth for Aureus plugin installation-order dependencies.**
- **Mere code-level consumption MUST NOT be treated as equivalent to a declared plugin installation dependency.**

### Prescriptive Rules: Dependencies
- When a plugin relies on database tables, migrations, or seeders created by another plugin, it MUST declare that dependency via `$package->hasDependencies(['prerequisite-plugin'])` in `configureCustomPackage()`.
- Developers MUST NOT assume that adding a package to `composer.json` guarantees that its database tables or settings will be installed prior to the dependent plugin.
- Importing classes (`use Webkul\...`) from an optional plugin without declaring a runtime dependency or verifying `Package::isPluginInstalled()` at runtime is STRICTLY FORBIDDEN.

---

## 4. Zero-Table Extension Layer Pattern

### Definition & Rationale

A **Zero-Table Extension Plugin** is a modular package in `plugins/webkul/` that owns **0 physical database tables and 0 migrations**, yet provides first-class business domain functionality by extending, orchestrating, or presenting schemas owned by other plugins.

Zero tables does NOT mean a plugin is incomplete or a stub. It is a deliberate design pattern to avoid schema fragmentation and redundant database tables.

### Existing Zero-Table Plugins in Aureus ERP

The repository contains exactly **six verified zero-table plugins**:

| Plugin | Extended Schema / Domain | Observed Architecture & Purpose | Evidence |
| :--- | :--- | :--- | :--- |
| **`accounting`** | `accounts` (`accounts_*` tables) | Mounts the financial reporting engine (Balance Sheet, P&L, Trial Balance, Aging), analytics widgets, and navigation clusters on top of `accounts`. | `plugins/webkul/accounting/database/migrations` does not exist. |
| **`barcode`** | `inventories` & `products` | Mobile-first handheld scanner interface and NativePHP shell executing warehouse transfers and adjustments directly against inventory and product tables. | `plugins/webkul/barcode/database/migrations` does not exist. |
| **`contacts`** | `partners` (`partners_*` tables) | Surfaces the administrative Contact Book UI in top-level navigation (`NavigationGroup::Contact`) over the core `partners` hub. | `plugins/webkul/contacts/database/migrations` does not exist. |
| **`full-calendar`** | Pure UI / Frontend Component | Provides the FullCalendar (v6) JavaScript/Alpine widget foundation and modal bindings across panels; defines no models or tables. | `plugins/webkul/full-calendar/database/migrations` does not exist. |
| **`invoices`** | `accounts` (`accounts_account_moves`) | Operational billing presentation layer managing customer invoices, vendor bills, payment registration, and commercial master data. | `plugins/webkul/invoices/database/migrations` does not exist. |
| **`timesheets`** | `projects` & `analytics` (`analytic_records`) | Extends `Webkul\Project\Models\Timesheet` on the core `analytic_records` table (`type = 'projects'`); owns zero tables. | `plugins/webkul/timesheets/database/migrations` does not exist. |

### When the Pattern is Appropriate
- When building presentation, reporting, or analytical dashboards over existing transactional data (e.g., `accounting`).
- When creating dedicated specialized workflows or mobile/scanner interfaces over existing inventory or master records (e.g., `barcode`).
- When exposing a customer-facing directory or specialized view over core master data (e.g., `contacts`).
- When providing abstract UI component primitives (e.g., `full-calendar`).
- When specializing generic record tables through discriminator columns (e.g., `timesheets` on `analytic_records`).

### Prescriptive Rules: Zero-Table Extensions
- Zero tables MUST NOT be assumed to automatically indicate an extension layer.
- A future zero-table plugin MUST explicitly identify the existing schema/behavior it extends and document why it does not own persistence.
- A future zero-table plugin extending another domain MUST declare the underlying plugin as a runtime dependency via `hasDependencies(['underlying-plugin'])` (unless it is an abstract UI primitive or core component).
- Developers MUST NOT create empty migration directories or dummy tables for plugins that qualify for the zero-table extension pattern.

---

## 5. Panel Architecture (`admin` vs `customer`)

Aureus ERP configures two separate, isolated Filament panels:

```
┌────────────────────────────────────────────────────────┐ ┌────────────────────────────────────────────────────────┐
│                   Admin Panel (/admin)                 │ │                 Customer Panel (/)                     │
├────────────────────────────────────────────────────────┤ ├────────────────────────────────────────────────────────┤
│ • Provider: app/Providers/Filament/AdminPanelProvider  │ │ • Provider: app/Providers/Filament/CustomerPanelProvider│
│ • Authentication Guard: web                            │ │ • Authentication Guard: customer                       │
│ • User Model: App\Models\User                          │ │ • User Model: Webkul\Partner\Models\Partner            │
│   (extends Webkul\Security\Models\User)                │ │ • Scope: Customer self-service portal & eCommerce      │
│ • Scope: Internal ERP back-office administration       │ │ • Password Broker: customers                           │
│ • RBAC: Filament Shield & Webkul\Security\Bouncer      │ │ • Access: Restricted customer routes                   │
└────────────────────────────────────────────────────────┘ └────────────────────────────────────────────────────────┘
```

### Prescriptive Rules: Panels
- Future plugins MUST explicitly determine their panel participation inside `*Plugin.php::register(Panel $panel)` by inspecting `$panel->getId()`.
- Back-office administration resources, settings, and widgets MUST register ONLY when `$panel->getId() === 'admin'`.
- Customer portal pages and resources MUST register ONLY when `$panel->getId() === 'customer'`.
- Code operating in the `customer` panel MUST treat the authenticated user as `Webkul\Partner\Models\Partner` and MUST NOT type-hint or cast to `User`.
- Code operating in the `admin` panel MUST treat the authenticated user as `App\Models\User`.
- Plugins supporting both panels (such as `purchases` or `website`) MUST implement explicit conditional branches for each panel ID.

---

## 6. Navigation Composition Pattern (`shouldRegisterNavigation = false`)

### Mechanism & Rationale

In Aureus ERP, core foundational plugins (such as `partners` and `accounts`) define robust Filament resources providing forms, tables, infolists, and actions. However, to prevent cluttered navigation sidebars and allow modular UI composition:

Foundational core resources deliberately set:
```php
protected static bool $shouldRegisterNavigation = false;
```

A downstream presentation or optional plugin (such as `contacts`, `invoices`, or `accounting`) then extends or mounts that resource and sets:
```php
protected static bool $shouldRegisterNavigation = true;
```
placing it into dedicated clusters, navigation groups, and sorted orders.

### Verified Implementations
- `Webkul\Partner\Filament\Resources\PartnerResource`: Sets `$shouldRegisterNavigation = false`.
  - Consumed and surfaced by `Webkul\Contact\Filament\Resources\PartnerResource`: Sets `$shouldRegisterNavigation = true` under `NavigationGroup::Contact`.
- `Webkul\Account\Filament\Resources\InvoiceResource`: Kept headless/hidden in base navigation.
  - Consumed and surfaced by `Webkul\Invoice\Filament\Resources\InvoiceResource` under the `Customers` cluster.

### Prescriptive Rules: Navigation Composition
- `shouldRegisterNavigation = false` is a deliberate composition mechanism; developers MUST NOT interpret it as broken, dead, or deprecated code.
- Developers MUST NOT change `shouldRegisterNavigation` to `true` on foundational core resources without verifying whether a presentation plugin owns user-facing navigation.
- When creating an optional presentation plugin over core resources, the presentation plugin MUST own and declare the navigation registration.
- Navigation composition MUST NOT be prescribed universally; it applies specifically when navigation ownership is decoupled from schema ownership.

---

## 7. Dynamic Schema Architecture

Aureus ERP enforces strict package boundaries. Base models in core plugins MUST NOT declare hardcoded relationships to optional downstream plugins.

Cross-module schema integration is achieved through **three established dynamic mechanisms**:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                              Three Established Dynamic Schema Mechanisms                               │
├────────────────────────────────┬───────────────────────────────────────┬───────────────────────────────┤
│ 1. resolveRelationUsing()      │ 2. CompanyProperty Attribute Cast     │ 3. Custom Fields Engine       │
├────────────────────────────────┼───────────────────────────────────────┼───────────────────────────────┤
│ • Injects Eloquent relations   │ • Scopes properties to active tenant  │ • Metadata-driven user fields │
│   at application boot time.    │   company via EAV cast pattern.       │ • Modifies physical tables    │
│ • Gated by isPluginInstalled.  │ • Casts attributes dynamically on     │   via Blueprint at runtime.   │
│ • No physical schema changes.  │   target models (Partner, Product).   │ • Injects Filament form/table │
│ • 22 verified usage sites.     │ • Eliminates tenant FK columns.       │   inputs dynamically.         │
└────────────────────────────────┴───────────────────────────────────────┴───────────────────────────────┘
```

### Mechanism 1: `Model::resolveRelationUsing()`
- Executed inside `packageBooted()` of consuming plugin service providers (`accounts`, `inventories`, `manufacturing`, `purchases`).
- Gated by `Package::isPluginInstalled(static::$name)` to prevent registering relationships to uninstalled plugin tables.
- Cross-reference: `docs/architecture/dynamic-schema.md` for the complete 22-site catalog.

### Mechanism 2: `Webkul\Account\Casts\CompanyProperty`
- Custom Eloquent attribute cast (`plugins/webkul/accounts/src/Casts/CompanyProperty.php`).
- Resolves tenant-specific property values (e.g., payable/receivable accounts, fiscal positions) based on the active `CompanyContext` without adding company-specific foreign key columns to base master tables.

### Mechanism 3: Runtime Custom Field Rendering (`fields` Plugin)
- Powered by `Webkul\Field\Traits\HasCustomFields` (model attribute hydration and casting) and `Webkul\Field\Filament\Traits\HasCustomFields` (UI schema component generation).
- Executes physical column mutations via `FieldsColumnManager` when custom field definitions are created or modified.

### Prescriptive Rules: Dynamic Schema
- Developers and AI agents MUST NOT invent a fourth dynamic schema mechanism without deliberate, approved architectural justification.
- When an optional plugin must attach an Eloquent relationship to a model owned by an upstream or core plugin, it MUST use `Model::resolveRelationUsing()` inside `packageBooted()`.
- All `resolveRelationUsing()` calls MUST be conditioned on `Package::isPluginInstalled(static::$name)`.
- When attaching company-specific dynamic configuration to models, developers MUST use the `CompanyProperty` cast pattern.
- When supporting user-defined custom attributes, models and Filament resources MUST attach the established `HasCustomFields` traits.
