---
status: verified
source_of_truth: source-code
last_verified: 2026-09-03
scope: global
confidence: high
---

# Aureus ERP — Coding Rules

## 1. Overview & Scope

This document defines the binding implementation rules for PHP, Laravel, and Filament across all modules in Aureus ERP. It establishes conventions for naming standards, background and deferred execution, Filament resource/cluster/widget architecture, and code-level consumption boundaries.

Every rule herein is prescriptive. Developers and AI agents writing or refactoring code in this repository MUST comply with these conventions.

---

## 2. Repository Naming Rules

Naming standards in Aureus ERP are derived from verified database schema conventions (`docs/database/schema-conventions.md`) and established code patterns across existing plugins:

### 1. Database Table & Column Naming
- **Plugin Domain Tables**: MUST follow the format `<plugin>_<entity>` in plural snake_case:
  - Examples: `products_products`, `sales_orders`, `sales_order_lines`, `accounts_account_moves`, `inventories_operations`, `purchases_order_lines`.
- **Foundational / Shared Exceptions**: Shared tables defined in `support`, `fields`, `security`, `table-views`, and `analytics` omit plugin prefixes to act as cross-cutting multi-tenant primitives:
  - `companies`, `currencies`, `countries`, `states`, `banks`, `sequences`, `unit_of_measures`, `custom_fields`, `teams`, `user_team`, `table_views`, `analytic_records`.
- **Primary Keys**: MUST use `$table->id()` (`BIGINT` auto-increment) for domain entity tables. UUIDs MUST NOT be introduced for domain models.
- **Foreign Keys**: MUST follow the format `<entity>_id` matching the referenced table:
  - Tenant foreign key: `company_id`
  - User / Creator foreign key: `creator_id` or `user_id` or `assigned_to`
  - Partner foreign key: `partner_id`, `partner_invoice_id`, `partner_shipping_id`
  - Self-referencing tree hierarchy: `parent_id`
- **State Columns**: MUST be defined as `$table->string('state')` or `$table->string('status')`. Native database ENUMs (`$table->enum(...)`) MUST NOT be introduced.

### 2. PHP Model & Class Naming
- **Eloquent Models**: MUST use singular PascalCase matching the domain concept (`Product`, `Order`, `OrderLine`, `Move`, `MoveLine`, `Operation`, `Partner`).
- **State Enums**: MUST be backed string enums implementing TitleCase or UPPER_CASE values and cast via Eloquent `casts(): array` or `$casts`:
  - Examples: `OrderStatus`, `MoveState`, `OperationState`, `ProductType`, `PermissionType`.
- **Service Providers**: MUST be named `<Plugin>ServiceProvider.php` and extend `Webkul\PluginManager\PackageServiceProvider`.
- **Filament Plugin Classes**: MUST be named `<Plugin>Plugin.php` and implement `Filament\Contracts\Plugin` (with the exception of `analytics` and `table-views`).

### Prescriptive Rule: Anti-Invention
- Developers and AI agents MUST NOT invent naming schemes based solely on generic external Laravel conventions. If an entity does not fit existing patterns, use `[UNKNOWN]` and request architectural review.

---

## 3. Background & Deferred Processing Architecture

### The Queue Job Reality
The repository contains **ZERO classes extending `Illuminate\Queue\Jobs\Job` or residing in `Jobs/` directories**.

However:
> **MANDATORY FACT & PRINCIPLE:**
> **The absence of queue Job classes does NOT mean the repository lacks asynchronous or deferred mechanisms.**
> **Future background processing MUST NOT silently introduce new architectural conventions without deliberate review.**

### Concrete Established Mechanisms in the Repository

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────┐
│                          Established Deferred & Reactive Mechanisms                              │
├───────────────────────────────┬──────────────────────────────────────────────────────────────────┤
│ 1. ShouldQueue Notification   │ Webkul\Chatter\Notifications\ChatterDatabaseNotification         │
│                               │ implements ShouldQueue and uses Queueable trait                  │
│ 2. Reactive Event Listeners   │ 28 Domain Events + 6 Listeners executing synchronously           │
│ 3. Model Observers            │ 7 Eloquent Observers synchronizing state across plugins          │
│ 4. Client-Side Polling        │ AdminPanelProvider polling databaseNotifications every 30s       │
│ 5. Console Scheduling         │ routes/console.php configuring Artisan scheduled commands        │
└───────────────────────────────┴──────────────────────────────────────────────────────────────────┘
```

1. **Queued Notification Delivery**:
   - `Webkul\Chatter\Notifications\ChatterDatabaseNotification` (`plugins/webkul/chatter/src/Notifications/ChatterDatabaseNotification.php:11`) implements `Illuminate\Contracts\Queue\ShouldQueue` and uses `Illuminate\Bus\Queueable`. When notifications are dispatched via `Notification::send()`, Laravel routes them through the configured queue driver.
2. **Synchronous Reactive Decoupling (Events & Observers)**:
   - The repository decouples heavy cross-plugin workflows (e.g. recalculating sales order quantities when warehouse transfers validate, or updating invoice status when payments post) using **28 domain events**, **6 listeners**, and **7 model observers** (documented in `docs/architecture/events-catalog.md`). These execute synchronously within the request transaction.
3. **Browser-Driven Notification Polling**:
   - Instead of WebSockets or push daemons, `app/Providers/Filament/AdminPanelProvider.php:55-56` configures `->databaseNotifications()->databaseNotificationsPolling('30s')`. Real-time alerts are pulled periodically by the client browser.
4. **Artisan Console Scheduling**:
   - `routes/console.php` exposes the schedule definition hook (`Artisan::command(...)->hourly()`).

### Prescriptive Rules: Background Processing
- Future work requiring event-driven decoupling SHOULD follow the established **Domain Event → Listener / Observer** pattern where appropriate.
- Notifications dispatched to users SHOULD implement `ShouldQueue` following `ChatterDatabaseNotification`.
- When introducing long-running batch operations (such as bulk data imports or massive financial re-evaluations), developers MUST NOT silently invent custom queue worker queues without verifying worker infrastructure and documenting the architectural decision.

---

## 4. Filament Architecture Conventions

Filament architecture across Aureus ERP is governed by the comprehensive analysis in `docs/architecture/filament-architecture.md` (covering 2 panels, 204 resources, 46 clusters, 474 pages, and 24 widgets):

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                 Filament Structural Hierarchy                                    │
├─────────────────────────────┬────────────────────────────────────────────────────────────────────┤
│ Panels                      │ Explicit admin (/admin) vs customer (/) registration               │
│ Navigation Group Enum       │ Webkul\Support\Enums\NavigationGroup coordinates top-bar menus     │
│ Navigation Composition      │ Core resources hide ($shouldRegisterNavigation = false);           │
│                             │ Presentation plugins surface ($shouldRegisterNavigation = true)    │
│ Clusters                    │ Group related sub-domain entities (Configurations, Reporting)      │
│ Widgets                     │ Dashboard analytics, chart widgets, and calendar widgets           │
└─────────────────────────────┴────────────────────────────────────────────────────────────────────┘
```

### 1. Panel Targeting Rule
- Local `*Plugin.php` classes MUST inspect `$panel->getId()` inside `register(Panel $panel)`:
  - Back-office resources MUST register ONLY when `$panel->getId() === 'admin'`.
  - Customer portal resources MUST register ONLY when `$panel->getId() === 'customer'`.
  - Plugins with components for both panels (e.g., `purchases`, `website`, `blogs`) MUST separate them into distinct namespaces (`src/Filament/Admin/` and `src/Filament/Customer/`).

### 2. Navigation Registration & Composition Rule
- **Top-Level Domain Resources**:
  - Operational entry points (e.g., `QuotationResource`, `OrderResource`, `EmployeeResource`) MUST register navigation by defining `getNavigationGroup()` using an enum case from `Webkul\Support\Enums\NavigationGroup`.
- **Deliberate Navigation Hiding (`shouldRegisterNavigation = false`)**:
  - Foundational core resources (e.g., `Webkul\Partner\Filament\Resources\PartnerResource`, `Webkul\Account\Filament\Resources\InvoiceResource`, `CountryResource`, `CurrencyResource`) MUST define:
    ```php
    protected static bool $shouldRegisterNavigation = false;
    ```
  - Downstream presentation plugins (e.g., `contacts`, `invoices`, `accounting`) extend or wrap these resources, set `$shouldRegisterNavigation = true`, and assign them to specific clusters or groups.
  - Developers MUST NOT set `shouldRegisterNavigation = true` on core resources without verifying presentation ownership.

### 3. Clusters Convention
- Sub-domain configuration resources, reporting screens, and plugin settings MUST be organized under `Filament\Clusters\Cluster`:
  - `Configurations`: Houses reference tables, tags, types, categories, and stages (e.g., `Webkul\Sale\Filament\Clusters\Configuration`, `Webkul\Contact\Filament\Clusters\Configurations`).
  - `Reporting`: Houses financial statements and analytical reports (e.g., `Webkul\Accounting\Filament\Clusters\Reporting`).
  - `PluginSettings`: Houses domain-specific toggle settings pages.
- Resources belonging to a cluster MUST declare:
  ```php
  protected static ?string $cluster = Configurations::class;
  ```

### 4. Custom & Resource Pages Convention
- CRUD operations MUST use standard resource pages (`ListRecords`, `CreateRecord`, `EditRecord`, `ViewRecord`) placed under `src/Filament/Resources/<Resource>/Pages/`.
- Non-CRUD pages (standalone dashboards, custom wizards, settings screens) MUST extend `Filament\Pages\Page` or `Filament\Pages\SettingsPage` and reside under `src/Filament/Pages/` or inside a Cluster.

### 5. Widgets Convention
- Overview dashboard widgets, chart widgets, and calendar widgets MUST extend Filament widget base classes (`ChartWidget`, `StatsOverviewWidget`, `TableWidget`) or `Webkul\FullCalendar\Widgets\FullCalendarWidget`.
- Top-level dashboard widgets MUST be placed in `src/Filament/Widgets/` and discovered via `->discoverWidgets()`.

---

## 5. Code-Level Consumption vs. Runtime Dependencies

Cross-reference: See Section 3 of `docs/ai/architecture-rules.md`.

### Mandatory Principle
- Merely adding a PHP `use Webkul\<Plugin>\Models\<Model>;` statement in code is **code-level consumption**.
- Code-level consumption does NOT guarantee that the target plugin is installed in the database, that its migrations have run, or that its settings exist.
- When an optional plugin consumes another optional plugin:
  - The calling plugin MUST declare the runtime installation dependency via `$package->hasDependencies(['target-plugin'])`, OR
  - The calling code MUST verify runtime availability via `Package::isPluginInstalled('target-plugin')` before executing operations.
