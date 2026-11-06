# Aureus ERP — Architectural Reference Specification

**Status:** Canonical Root Architectural Reference  
**Authority:** Level 1 Source Code Authority  
**Repository:** `aureuserp`  
**Last Verified Baseline:** PHP ^8.3 | Laravel v13.31.0 | Filament v5.8.1 | Livewire v4.4.5 | Filament Shield 4.2.0  

---

## Table of Contents

1. [Executive System Overview & Architectural Paradigms](#1-executive-system-overview--architectural-paradigms)
   - [1.1 Architectural Vision & Monorepo Topology](#11-architectural-vision--monorepo-topology)
   - [1.2 Micro-Kernel Host & Plugin Monolith](#12-micro-kernel-host--plugin-monolith)
   - [1.3 System Component & Data Flow Architecture](#13-system-component--data-flow-architecture)
2. [Technology Baseline & Platform Invariants](#2-technology-baseline--platform-invariants)
   - [2.1 Locked Dependency Baseline](#21-locked-dependency-baseline)
   - [2.2 Runtime Configuration & Self-Contained Persistence](#22-runtime-configuration--self-contained-persistence)
   - [2.3 Autoloading & Monorepo Manifest Merging](#23-autoloading--monorepo-manifest-merging)
3. [Core Application Architecture (`app/` & `bootstrap/`)](#3-core-application-architecture-app--bootstrap)
   - [3.1 Host Application Layout & Responsibilities](#31-host-application-layout--responsibilities)
   - [3.2 Bootstrap Pipeline (`bootstrap/app.php`)](#32-bootstrap-pipeline-bootstrapappphp)
   - [3.3 Centralized Exception Handling & UI Notifications](#33-centralized-exception-handling--ui-notifications)
   - [3.4 Static Service Provider Registration (`bootstrap/providers.php`)](#34-static-service-provider-registration-bootstrapprovidersphp)
   - [3.5 Core Foundation Services & Dynamic OKLCH Branding Middleware](#35-core-foundation-services--dynamic-oklch-branding-middleware)
4. [Domain & Plugin Ecosystem Topology](#4-domain--plugin-ecosystem-topology)
   - [4.1 Comprehensive Catalog of All 28 Local Plugins](#41-comprehensive-catalog-of-all-28-local-plugins)
   - [4.2 Plugin Directory Conventions & Manifest Layout](#42-plugin-directory-conventions--manifest-layout)
   - [4.3 Service Provider Inheritance & Lifecycle Execution](#43-service-provider-inheritance--lifecycle-execution)
   - [4.4 Plugin Manager Engine (`plugins/webkul/plugin-manager`)](#44-plugin-manager-engine-pluginswebkulplugin-manager)
   - [4.5 Inter-Plugin Dependency Graph & Validation](#45-inter-plugin-dependency-graph--validation)
   - [4.6 Decoupled Cross-Plugin Integration Patterns](#46-decoupled-cross-plugin-integration-patterns)
5. [Multi-Company Tenancy & Isolation Architecture](#5-multi-company-tenancy--isolation-architecture)
   - [5.1 Bespoke Eloquent Tenancy vs. Filament Native Tenancy](#51-bespoke-eloquent-tenancy-vs-filament-native-tenancy)
   - [5.2 Tenancy Primitives: Scopes, Traits, and Context](#52-tenancy-primitives-scopes-traits-and-context)
   - [5.3 Multi-Active Company Viewing Mode](#53-multi-active-company-viewing-mode)
   - [5.4 Referential Tenancy Integrity & Cross-Company Guards](#54-referential-tenancy-integrity--cross-company-guards)
   - [5.5 Tenancy Scope Bypasses & Privileged Contexts](#55-tenancy-scope-bypasses--privileged-contexts)
   - [5.6 Tenant Switcher UI & Controller Integration](#56-tenant-switcher-ui--controller-integration)
6. [Security, Authentication & Authorization Architecture](#6-security-authentication--authorization-architecture)
   - [6.1 Dual-Guard Authentication Architecture](#61-dual-guard-authentication-architecture)
   - [6.2 Filament Shield Integration & Distributed Configuration](#62-filament-shield-integration--distributed-configuration)
   - [6.3 Role & Permission Lifecycle and Protected System Roles](#63-role--permission-lifecycle-and-protected-system-roles)
   - [6.4 Fine-Grained Record Ownership Architecture (`Bouncer`)](#64-fine-grained-record-ownership-architecture-bouncer)
   - [6.5 Orthogonal Decoupling: Authorization Policies vs. Tenant Scoping](#65-orthogonal-decoupling-authorization-policies-vs-tenant-scoping)
7. [Filament Panel Architecture](#7-filament-panel-architecture)
   - [7.1 Dual-Panel Topography](#71-dual-panel-topography)
   - [7.2 Inversion of Control: `Panel::configureUsing()`](#72-inversion-of-control-panelconfigureusing)
   - [7.3 Dynamic Discovery Gating & Panel Archetypes](#73-dynamic-discovery-gating--panel-archetypes)
   - [7.4 Central Navigation Hierarchy & Cluster Breadcrumbs](#74-central-navigation-hierarchy--cluster-breadcrumbs)
8. [Persistence & Database Conventions](#8-persistence--database-conventions)
   - [8.1 Schema Architecture Across 383 Migrations](#81-schema-architecture-across-383-migrations)
   - [8.2 Table & Column Conventions](#82-table--column-conventions)
   - [8.3 Foreign Key Semantics & Referential Cascade Tiers](#83-foreign-key-semantics--referential-cascade-tiers)
   - [8.4 Multi-Company Storage Models](#84-multi-company-storage-models)
9. [Architectural Invariants, Constraints & Extension Guidelines](#9-architectural-invariants-constraints--extension-guidelines)
   - [9.1 Non-Negotiable Architectural Rules](#91-non-negotiable-architectural-rules)
   - [9.2 Testing Bootstrap Architecture (`TestBootstrapHelper`)](#92-testing-bootstrap-architecture-testbootstraphelper)
   - [9.3 New Plugin Authoring & Extension Standards](#93-new-plugin-authoring--extension-standards)

---

## 1. Executive System Overview & Architectural Paradigms

### 1.1 Architectural Vision & Monorepo Topology

Aureus ERP is a multi-company, modular enterprise resource planning platform engineered on **Laravel 13** and **Filament 5**. The repository is organized as a specialized **modular monolith** structured around two fundamental tiers:
1. **The Host Application Micro-Kernel (`app/`, `bootstrap/`, `config/`)**: A minimal host wrapper providing HTTP routing pipelines, global reverse proxy termination, session negotiation, exception rendering, and Filament panel definitions.
2. **The Local Plugin Ecosystem (`plugins/webkul/*`)**: An autonomous suite of 28 local Composer packages encapsulating all domain entities, business workflows, migrations, seeders, API routes, Livewire components, and Filament UI resources.

By strictly separating the micro-kernel host from domain packages, the system ensures zero business logic leaks into the root application namespace, keeping core infrastructure completely independent of domain volatility.

```
aureuserp/ (Repository Root)
├── app/                          # Micro-kernel application host (6 classes, 1 abstract controller)
│   ├── Http/Controllers/        # Base controller
│   ├── Http/Middleware/         # Brand settings (OKLCH), Locale negotiation
│   ├── Models/                  # Base authenticatable User
│   └── Providers/               # AppServiceProvider, AdminPanelProvider, CustomerPanelProvider
├── bootstrap/
│   ├── app.php                  # Fluent Laravel 13 application builder & exception pipelines
│   └── providers.php            # Static registration of all 31 service providers
├── config/                      # Framework and package configuration baselines
├── database/                    # Root migrations (13 system migrations), factories, seeders
├── plugins/webkul/              # 28 Local Composer packages (domain & infrastructure plugins)
│   ├── accounts/                # Core financial ledger and Chart of Accounts
│   ├── inventories/             # Multi-warehouse logistics and stock valuation
│   ├── partners/                # Master partner directory (customers, suppliers, companies)
│   ├── plugin-manager/          # Dynamic discovery, lifecycle, and Shield permissions
│   ├── security/                # RBAC roles, Bouncer, model ownership scoping
│   ├── support/                 # Tenancy isolation suite (CompanyScope, CompanyContext)
│   └── ... (22 other plugins)
└── routes/                      # Minimal root routes (all domain routes reside in plugins)
```

### 1.2 Micro-Kernel Host & Plugin Monolith

The platform adopts a decoupled **micro-kernel architecture**:
- **Host Application as an Inversion Container**: The host application defines two Filament panels (`admin` and `customer`), but registers zero domain resources or business navigation items directly. Instead, panels expose extension points via Filament's `Panel::configureUsing()` lifecycle hook.
- **Local Packages as Independent Units**: Each package in `plugins/webkul/` is a valid Composer package defining its own `composer.json`, PSR-4 namespaces, service provider, configuration files, migrations, translations, Blade views, and Filament UI components.
- **Dynamic Manifest Composition**: The root `composer.json` employs `wikimedia/composer-merge-plugin` (`v2.1.0`) to merge package manifests from `plugins/*/*/composer.json`. Autoloading, package discovery, and dependencies are unified without requiring private Composer registries or external artifact packaging.

### 1.3 System Component & Data Flow Architecture

The runtime architecture partitions HTTP requests across dedicated authentication guards, panel providers, multi-company context resolvers, and Eloquent global scopes:

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                               Incoming HTTP Request                                    │
└──────────────────────────────────────────┬─────────────────────────────────────────────┘
                                           │
                                           ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                        Laravel 13 Global Middleware Pipeline                           │
│     TrustProxies (*)  ──►  SetLocale  ──►  EncryptCookies  ──►  StartSession           │
└──────────────────────────────────────────┬─────────────────────────────────────────────┘
                                           │
                     ┌─────────────────────┴─────────────────────┐
                     ▼                                           ▼
          Path: `/admin/*`                                Path: `/*`
┌───────────────────────────────────────┐   ┌───────────────────────────────────────────┐
│           Admin Panel                 │   │         Customer Portal Panel             │
│    (AdminPanelProvider.php)           │   │      (CustomerPanelProvider.php)          │
├───────────────────────────────────────┤   ├───────────────────────────────────────────┤
│ • Guard: 'web'                        │   │ • Guard: 'customer'                       │
│ • Model: Webkul\Security\Models\User  │   │ • Model: Webkul\Website\Models\Partner    │
│ • Full viewport (Width::Full)         │   │ • Light mode enforced (darkMode(false))   │
│ • Top navigation bar                  │   │ • Top navigation bar                      │
│ • MFA App TOTP Authentication         │   │ • Self-registration & password reset      │
│ • Global Search Provider              │   │ • Portal dashboard & public pages         │
│ • Database Notifications (30s polling)│   │ • Customer quotation/order views          │
│ • Render Hooks:                       │   │ • Render Hooks:                           │
│   - GLOBAL_SEARCH_BEFORE: CompanySw.  │   │   - TOPBAR_END: Public links              │
│   - GLOBAL_SEARCH_END: LanguageSw.    │   │   - FOOTER: Portal footer                 │
└───────────────────┬───────────────────┘   └─────────────────────┬─────────────────────┘
                    │                                             │
                    └──────────────────────┬──────────────────────┘
                                           │
                                           ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                       Tenancy & Security Context Resolution                            │
│  1. CompanyContext: session('active_company_ids') ∩ $user->allowedCompanies()          │
│     - activeIds = [1, 2] (multi-view) | currentId = 1 (primary creation stamp)         │
│  2. Bouncer: Evaluates resource_permission (GLOBAL | GROUP | INDIVIDUAL)               │
└──────────────────────────────────────────┬─────────────────────────────────────────────┘
                                           │
                                           ▼
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                       Eloquent ORM Isolation & Storage Layer                           │
│  • CompanyScope: WHERE (company_id IN (1, 2) OR company_id IS NULL)                   │
│  • OwnershipScope: WHERE creator_id IN (authorized_user_ids)                           │
│  • ChecksCompanyConsistency: Asserts foreign relations belong to company_id            │
│  • Storage: SQLite (dev/test default) | MySQL / MariaDB / PostgreSQL (production)      │
└────────────────────────────────────────────────────────────────────────────────────────┘
```

> **Database Persistence & Production Dialect Nuance**: While `config/database.php:16` defaults to `sqlite` for zero-daemon self-contained execution and automated unit testing, production deployments target enterprise relational engines. `Webkul\Support\SupportServiceProvider:154` registers `Webkul\Support\Database\Dialects\DatabaseDialect` which natively supports `mysql`, `mariadb`, and `pgsql` (unsupported for SQLite in advanced dialect features such as JSON array aggregation, month bucketing, column type alteration, sequence synchronization, and case-insensitive matching).

---

## 2. Technology Baseline & Platform Invariants

### 2.1 Locked Dependency Baseline

Aureus ERP enforces strict platform locking across its runtime and development dependencies. Exact versions verified against `composer.lock` establish the platform baseline:

| Platform Component | Constraint in `composer.json` | Exact Locked Version | Source Citation |
|---|---|---|---|
| **PHP Runtime** | `^8.3` | `8.3.x` | `composer.json:12`, `composer.lock:15937` |
| **Laravel Framework** | `^13.0` | `v13.31.0` | `composer.json:25`, `composer.lock:3858` |
| **Filament Admin Panel** | `^5.0` | `v5.8.1` | `composer.json:18`, `composer.lock:2240` |
| **Filament Settings Plugin** | `^5.0` | `v5.8.1` | `composer.json:19` |
| **Filament Shield** | `4.2.0` (pinned) | `4.2.0` | `composer.json:17`, `composer.lock:524` |
| **Livewire** | `^4.0` | `v4.4.5` | `composer.json:29`, `composer.lock:5136` |
| **Laravel Sanctum** | `^4.0` | `v4.3.3` | `composer.json:26`, `composer.lock:4144` |
| **Pest Testing Framework** | `^4.4` | `v4.7.5` | `composer.json:48`, `composer.lock:13666` |
| **Composer Merge Plugin** | `^2.1` | `v2.1.0` | `composer.json:37`, `composer.lock:12615` |
| **Filament Language Switch** | `^4.3` | `4.3.0` | `composer.json:16` |
| **Filament DateRangePicker** | `^5.0` | `5.0.6` | `composer.json:31`, `composer.lock:5372` |
| **Spatie Query Builder** | `^7.0` | `7.3.0` | `composer.json:35`, `composer.lock:9003` |
| **Spatie Translatable** | `^6.14` | `6.14.1` | `composer.json:36`, `composer.lock:9162` |
| **Zeus Spatie Translatable** | `^2.0` | `2.0.1` | `composer.json:24`, `composer.lock:3801` |
| **Laravel Pint** | `^1.27` | `v1.29.3` | `composer.json:44`, `composer.lock:13333` |
| **Scribe API Documentation** | `^5.6` | `5.11.0` | `composer.json:23`, `composer.lock:3709` |

### 2.2 Runtime Configuration & Self-Contained Persistence

Aureus ERP is architected to be completely **self-contained and zero-daemon** in its default configuration. Baseline configuration in `config/` confirms that every infrastructure subsystem utilizes database persistence:
- **Default Database Driver (`config/database.php:16`)**: `env('DB_CONNECTION', 'sqlite')`. Path resolves to `database/database.sqlite` with `foreign_key_constraints = true`.
- **Database Persistence & Production Dialects (`SupportServiceProvider.php:148–159`)**: While `sqlite` serves as the zero-daemon default for self-contained execution, local development, and rapid unit testing, multi-tenant enterprise ERP operations require MySQL, MariaDB, or PostgreSQL. `SupportServiceProvider:154` registers the `Webkul\Support\Database\Dialects\DatabaseDialect` singleton, which specifically implements `MySqlDialect` (`mysql`, `mariadb`) and `PostgresDialect` (`pgsql`). Advanced dialect operations (such as `jsonArrayAgg()`, `monthBucket()`, `alterColumnType()`, `syncSequences()`, and `caseInsensitiveEquals()`) are unsupported for SQLite and throw a `RuntimeException("No DatabaseDialect implementation is registered for the [sqlite] database driver. Supported drivers: mysql, mariadb, pgsql.")`.
- **Session Management (`config/session.php:21–23`)**: Defaults to `'database'` driver (`SESSION_DRIVER=database`), persisting active sessions in the `sessions` table with `lifetime = 120`, `http_only = true`, and `same_site = 'lax'`.
- **Asynchronous Queues (`config/queue.php:16–18`)**: Defaults to `'database'` driver (`QUEUE_CONNECTION=database`), utilizing the `jobs` table with automatic 90-second retry timeout.
- **Cache Storage (`config/cache.php:18–20`)**: Defaults to `'database'` store (`CACHE_STORE=database`), storing application cache in the `cache` table.
- **Tenant-Scoped Settings (`config/settings.php:45–50`)**: Overrides Spatie's default database settings repository with `Webkul\Support\Settings\CompanyAwareSettingsRepository`, automatically partitioning system and brand settings per tenant company.

**Stand-Alone Portability Invariant**: Aureus ERP boots, runs migrations, executes background jobs, caches computational state, and manages authenticated multi-company sessions entirely within its database connection without requiring Redis, Memcached, or external queue workers. Production multi-tenant deployments swap the connection driver to MySQL, MariaDB, or PostgreSQL to enable full `DatabaseDialect` operations.

### 2.3 Autoloading & Monorepo Manifest Merging

The root `composer.json` declares PSR-4 autoloading for the host application and delegates plugin discovery to `wikimedia/composer-merge-plugin`:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "dont-discover": []
        },
        "merge-plugin": {
            "include": [
                "plugins/*/*/composer.json"
            ]
        }
    }
}
```

The merge plugin automatically scans and injects the `composer.json` of all 28 plugins under `plugins/webkul/*`. This provides:
1. Unified class loading across all plugin namespaces (e.g., `Webkul\Account\` -> `plugins/webkul/accounts/src/`).
2. Centralized dependency compilation and vendor conflict resolution during `composer install` or `composer update`.
3. Seamless developer workflows without maintaining individual Git submodules or separate package repositories.

---

## 3. Core Application Architecture (`app/` & `bootstrap/`)

### 3.1 Host Application Layout & Responsibilities

The root `app/` directory is deliberately lean, containing only 6 PHP classes and 1 abstract base controller:

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Controller.php                  # Abstract base controller
│   └── Middleware/
│       ├── ApplyBrandSettings.php          # Dynamic OKLCH color palettes & brand assets
│       └── SetLocale.php                   # Request & session locale negotiation
├── Models/
│   └── User.php                            # Foundation Authenticatable User model
└── Providers/
    ├── AppServiceProvider.php              # Container bindings, HTTPS, Livewire redirect hook
    └── Filament/
        ├── AdminPanelProvider.php          # Admin panel configuration
        └── CustomerPanelProvider.php       # Customer portal configuration
```

The host application does **not** declare domain models, business policies, domain migrations, or business controllers. It serves strictly as the bootstrap runtime harness.

### 3.2 Bootstrap Pipeline (`bootstrap/app.php`)

Application initialization utilizes the modern fluent builder pattern introduced in Laravel 11/13:

```php
// File: bootstrap/app.php:16-30
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->trustProxies(at: '*');
    })
```

- **Health Check Endpoint**: `/up` provides a lightweight ping route for container orchestrators and load balancers.
- **Proxy Configuration**: Global reverse proxy trust (`$middleware->trustProxies(at: '*')`) ensures correct SSL offloading, client IP resolution, and host header evaluation in containerized environments.
- **Locale Middleware**: Appends `App\Http\Middleware\SetLocale` to the `web` middleware group, resolving language preferences before hitting panels or controllers.

### 3.3 Centralized Exception Handling & UI Notifications

`bootstrap/app.php` (`lines 30–113`) bridges domain business exceptions with both JSON API responses and Filament browser sessions:

1. **`Webkul\Account\Exceptions\MissingJournalException` (`lines 31–46`)**:
   - **API / JSON Client**: Transforms into HTTP 422 Unprocessable Entity (`['message' => $e->getMessage()]`).
   - **Browser Web Request**: Intercepts the exception, constructs a persistent Filament danger notification (`title: accounts::system.move.no-journal-found-title`), flashes it to the session, and triggers `return back()`, preventing fatal error screens during accounting ledger posting.
2. **`Illuminate\Validation\ValidationException` (`lines 48–55`)**: Returns structured JSON 422 with validation errors.
3. **`Illuminate\Auth\AuthenticationException` (`lines 57–63`)**: Returns JSON 401 `Unauthenticated.`
4. **`Illuminate\Auth\Access\AuthorizationException` and `AccessDeniedHttpException` (`lines 65–79`)**: Returns JSON 403 `This action is unauthorized.`
5. **`ModelNotFoundException` and `NotFoundHttpException` (`lines 81–95`)**: Normalizes 404 responses.
6. **`Throwable` (`lines 97–112`)**: For unhandled 500 server errors on API endpoints, sanitizes error output in production (`'message' => 'Server error occurred.'`) while exposing exception class, message, file, and line during testing and local development.

### 3.4 Static Service Provider Registration (`bootstrap/providers.php`)

Aureus ERP rejects runtime directory scanning for service providers. Instead, `bootstrap/providers.php` explicitly returns an immutable array of **31 service providers**:

```php
// File: bootstrap/providers.php:35-67
return [
    // Core Application Providers (3)
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\CustomerPanelProvider::class,

    // Local Plugin Providers (28)
    Webkul\Accounting\AccountingServiceProvider::class,
    Webkul\Account\AccountServiceProvider::class,
    Webkul\Analytic\AnalyticServiceProvider::class,
    Webkul\Barcode\BarcodeServiceProvider::class,
    Webkul\Blog\BlogServiceProvider::class,
    Webkul\Chatter\ChatterServiceProvider::class,
    Webkul\Contact\ContactServiceProvider::class,
    Webkul\Employee\EmployeeServiceProvider::class,
    Webkul\Field\FieldServiceProvider::class,
    Webkul\Inventory\InventoryServiceProvider::class,
    Webkul\Invoice\InvoiceServiceProvider::class,
    Webkul\Maintenance\MaintenanceServiceProvider::class,
    Webkul\Manufacturing\ManufacturingServiceProvider::class,
    Webkul\Partner\PartnerServiceProvider::class,
    Webkul\Payment\PaymentServiceProvider::class,
    Webkul\Product\ProductServiceProvider::class,
    Webkul\Project\ProjectServiceProvider::class,
    Webkul\Purchase\PurchaseServiceProvider::class,
    Webkul\Recruitment\RecruitmentServiceProvider::class,
    Webkul\Sale\SaleServiceProvider::class,
    Webkul\Security\SecurityServiceProvider::class,
    Webkul\Support\SupportServiceProvider::class,
    Webkul\TableViews\TableViewsServiceProvider::class,
    Webkul\TimeOff\TimeOffServiceProvider::class,
    Webkul\FullCalendar\FullCalendarServiceProvider::class,
    Webkul\Timesheet\TimesheetServiceProvider::class,
    Webkul\Website\WebsiteServiceProvider::class,
    Webkul\PluginManager\PluginManagerServiceProvider::class,
];
```

**Architectural Rationale**:
- **Deterministic Boot Sequence**: Core infrastructure providers (`AppServiceProvider`, panel providers) initialize first, followed by domain packages, with `PluginManagerServiceProvider` registered last to coordinate discovery and permission management.
- **Zero Overhead**: Eliminates runtime filesystem globbing and directory scanning on every request.
- **Full `artisan optimize` Compatibility**: Allows compiling provider manifests cleanly during container builds.

### 3.5 Core Foundation Services & Dynamic OKLCH Branding Middleware

#### Container Contract Binding (`AppServiceProvider.php:19`)
In `App\Providers\AppServiceProvider::register()`, the application rebinds Laravel's default authenticatable contract:
```php
$this->app->bind(Authenticatable::class, User::class);
```
Where `User` is `Webkul\Security\Models\User`. This guarantees that dependency injection across the framework resolves the security plugin's tenant-aware user model rather than the bare framework user.

#### Livewire v4 Flash Notification Fix (`AppServiceProvider.php:28–44`)
To prevent notification loss caused by Livewire v4 dehydration during component redirects:
```php
on('dehydrate', function (Component $component): void {
    if (! Livewire::isLivewireRequest() || ! store($component)->has('redirect')) {
        return;
    }
    $notifications = session()->pull('filament.notifications');
    if (empty($notifications)) {
        return;
    }
    session()->put('filament.claimed_notifications', $notifications);
});
```

#### Dynamic OKLCH Color Generator (`ApplyBrandSettings.php:16–155`)
`App\Http\Middleware\ApplyBrandSettings` dynamically styles the Filament UI from database settings:
1. Reads `settings(BrandSettings::class)` from `Webkul\Support\Settings\BrandSettings`.
2. Inspects hex color overrides for 6 palette keys: `primary`, `success`, `danger`, `warning`, `info`, `gray`.
3. Executes `paletteFromHex($hex)`: Converts hex color values into the modern **OKLCH perceptual color space** (`lightness`, `chroma`, `hue`). It generates an 11-step color palette (`50`, `100`, `200`, `300`, `400`, `500`, `600`, `700`, `800`, `900`, `950`) maintaining uniform perceptual contrast across both light and dark modes.
4. Registers colors via `FilamentColor::register($brandColors)` and configures `$panel->brandLogo()`, `$panel->darkModeBrandLogo()`, `$panel->favicon()`, and `$panel->brandLogoHeight()`.

---

## 4. Domain & Plugin Ecosystem Topology

### 4.1 Comprehensive Catalog of All 28 Local Plugins

Every business feature in Aureus ERP is encapsulated in `plugins/webkul/*`. Exactly 28 plugins are present:

| # | Directory | Composer Package | PSR-4 Namespace | Primary Service Provider | Core / Optional | Disk Migrations | Zero-Table? | Business Domain & Architectural Role |
|:---:|:---|:---|:---|:---|:---:|:---:|:---:|:---|
| 1 | `accounting` | `webkul/accounting` | `Webkul\Accounting\` | `Webkul\Accounting\AccountingServiceProvider` | Optional | 0 | **Yes** | Financial reporting, chart of accounts navigation, journals, reconciliations, statements. Extends `accounts`. |
| 2 | `accounts` | `webkul/accounts` | `Webkul\Account\` | `Webkul\Account\AccountServiceProvider` | Optional | 71 | No | Core double-entry ledger, Chart of Accounts, tax engines, currencies, moves, move lines, bank reconciliation. |
| 3 | `analytics` | `webkul/analytics` | `Webkul\Analytic\` | `Webkul\Analytic\AnalyticServiceProvider` | **Core** | 1 | No | Analytic distribution tags, multi-plan cost tracking (`analytic_records`). Pure backend service (no UI plugin). |
| 4 | `barcode` | `webkul/barcode` | `Webkul\Barcode\` | `Webkul\Barcode\BarcodeServiceProvider` | Optional | 0 | **Yes** | Barcode scanning UI and print generation. Extends `inventories` operations without standalone tables. |
| 5 | `blogs` | `webkul/blogs` | `Webkul\Blog\` | `Webkul\Blog\BlogServiceProvider` | Optional | 7 | No | Content publishing, blog categories, blog posts, SEO metadata for backend administration and customer portal. |
| 6 | `chatter` | `webkul/chatter` | `Webkul\Chatter\` | `Webkul\Chatter\ChatterServiceProvider` | **Core** | 4 | No | Universal activity tracking, chatter message threads, record followers, activity scheduling across models (`HasChatter`). |
| 7 | `contacts` | `webkul/contacts` | `Webkul\Contact\` | `Webkul\Contact\ContactServiceProvider` | Optional | 0 | **Yes** | CRM contacts, address books, customer/vendor categorization. Zero-table UI layer over `partners`. |
| 8 | `employees` | `webkul/employees` | `Webkul\Employee\` | `Webkul\Employee\EmployeeServiceProvider` | Optional | 22 | No | HR employee directory, departments, job positions, employment history, work locations, reporting hierarchy. |
| 9 | `fields` | `webkul/fields` | `Webkul\Field\` | `Webkul\Field\FieldServiceProvider` | **Core** | 1 | No | Dynamic custom fields engine (`custom_fields`). Allows adding arbitrary custom fields to any model via `HasCustomFields`. |
| 10 | `full-calendar` | `webkul/full-calendar` | `Webkul\FullCalendar\` | `Webkul\FullCalendar\FullCalendarServiceProvider` | **Core** | 0 | **Yes** | FullCalendar JS wrapper and reusable Filament calendar components/widgets across modules (maintenance, time-off). |
| 11 | `inventories` | `webkul/inventories` | `Webkul\Inventory\` | `Webkul\Inventory\InventoryServiceProvider` | Optional | 57 | No | Multi-location stock tracking, warehouse transfers, deliveries, receipts, scrap, stock valuation, adjustments. |
| 12 | `invoices` | `webkul/invoices` | `Webkul\Invoice\` | `Webkul\Invoice\InvoiceServiceProvider` | Optional | 0 | **Yes** | Dedicated invoicing UI workflows, bill run management, customer billing, vendor bills. Zero-table extension over `accounts`. |
| 13 | `maintenance` | `webkul/maintenance` | `Webkul\Maintenance\` | `Webkul\Maintenance\MaintenanceServiceProvider` | Optional | 6 | No | Equipment maintenance requests, maintenance teams, preventative and corrective maintenance logs. |
| 14 | `manufacturing` | `webkul/manufacturing` | `Webkul\Manufacturing\` | `Webkul\Manufacturing\ManufacturingServiceProvider` | Optional | 37 | No | Production control, Bills of Materials (BOM), work orders, routing, work centers, scrap, unbuild orders. |
| 15 | `partners` | `webkul/partners` | `Webkul\Partner\` | `Webkul\Partner\PartnerServiceProvider` | **Core** | 8 | No | Master partner entity (customers, suppliers, companies, individuals, addresses, payment terms). |
| 16 | `payments` | `webkul/payments` | `Webkul\Payment\` | `Webkul\Payment\PaymentServiceProvider` | Optional | 6 | No | Payment vouchers, customer receipts, vendor payments, payment registers, bank payment integration. |
| 17 | `plugin-manager` | `webkul/plugin-manager` | `Webkul\PluginManager\` | `Webkul\PluginManager\PluginManagerServiceProvider` | **Core** | 1 | No | Plugin lifecycle engine, package manifest discovery, installation, uninstallation, table rollback, Shield manager. |
| 18 | `products` | `webkul/products` | `Webkul\Product\` | `Webkul\Product\ProductServiceProvider` | Optional | 18 | No | Master product catalog, variants, configurable products, attributes, units of measure (UOM), price lists. |
| 19 | `projects` | `webkul/projects` | `Webkul\Project\` | `Webkul\Project\ProjectServiceProvider` | Optional | 12 | No | Project management, task tracking, kanban stages, milestones, project member assignment, privacy scoping. |
| 20 | `purchases` | `webkul/purchases` | `Webkul\Purchase\` | `Webkul\Purchase\PurchaseServiceProvider` | Optional | 19 | No | Procurement pipeline, Requests for Quotation (RFQ), Purchase Orders (PO), approval workflows, vendor price lists. |
| 21 | `recruitments` | `webkul/recruitments` | `Webkul\Recruitment\` | `Webkul\Recruitment\RecruitmentServiceProvider` | Optional | 14 | No | Recruitment pipeline, job candidates, applications, recruitment stages, interview tracking. |
| 22 | `sales` | `webkul/sales` | `Webkul\Sale\` | `Webkul\Sale\SaleServiceProvider` | Optional | 26 | No | Sales orders, quotation generation, quotation templates, price lists, sales teams, delivery invoicing triggers. |
| 23 | `security` | `webkul/security` | `Webkul\Security\` | `Webkul\Security\SecurityServiceProvider` | **Core** | 10 | No | Security foundation, Filament Shield integration, RBAC roles, permissions, Bouncer, ownership scoping. |
| 24 | `support` | `webkul/support` | `Webkul\Support\` | `Webkul\Support\SupportServiceProvider` | **Core** | 34 | No | Tenancy suite (`CompanyScope`, `CompanyContext`, `BelongsToCompany`), navigation enums, shared traits. |
| 25 | `table-views` | `webkul/table-views` | `Webkul\TableViews\` | `Webkul\TableViews\TableViewsServiceProvider` | **Core** | 2 | No | Custom table view presets, favorite views, column visibility controls via Filament render hooks. Core infrastructure. |
| 26 | `time-off` | `webkul/time-off` | `Webkul\TimeOff\` | `Webkul\TimeOff\TimeOffServiceProvider` | Optional | 10 | No | Employee leave management, time off requests, allocations, leave types, multi-level approvals, public holidays. |
| 27 | `timesheets` | `webkul/timesheets` | `Webkul\Timesheet\` | `Webkul\Timesheet\TimesheetServiceProvider` | Optional | 0 | **Yes** | Employee project timesheets, billable hour logging, task time tracking. Zero-table extension over `projects`. |
| 28 | `website` | `webkul/website` | `Webkul\Website\` | `Webkul\Website\WebsiteServiceProvider` | Optional | 4 | No | CMS website pages, public portal layout, customer authentication (login, registration, reset), theme engine. |

#### Architectural Plugin Archetypes
- **9 Core Infrastructure Packages (`$package->isCore()`)**: `analytics`, `chatter`, `fields`, `full-calendar`, `partners`, `plugin-manager`, `security`, `support`, `table-views`. Loaded unconditionally. They execute migrations and register routes without database installation checks.
- **19 Optional Domain Packages**: Gated by runtime installation (`Package::isPluginInstalled()`). Their UI, routes, and console migrations remain dormant until explicitly installed.
- **6 Zero-Table Extension Packages**: `accounting`, `barcode`, `contacts`, `full-calendar`, `invoices`, `timesheets`. Own **zero migrations** on disk; they extend upstream schemas and UI workflows cleanly without creating duplicate database tables.

### 4.2 Plugin Directory Conventions & Manifest Layout

Every plugin under `plugins/webkul/<plugin>/` adheres to a strict standard modular directory structure:

```
plugins/webkul/<plugin>/
├── composer.json               # Package identity, PSR-4 autoload, dependencies
├── config/                     # Plugin configuration files (merged into Laravel config)
│   ├── <plugin>.php
│   └── filament-shield.php     # Optional Shield resource permission overrides
├── database/
│   ├── factories/              # Model factories for Pest testing
│   ├── migrations/             # Database table migrations (*.php)
│   ├── seeders/                # Database seeders (*Seeder.php)
│   └── settings/               # Spatie settings migrations (*.php)
├── resources/
│   ├── dist/                   # Compiled CSS/JS assets
│   ├── lang/                   # Translations: en/, ar/, es/, fr/, pt_BR/ (PHP & JSON)
│   └── views/                  # Blade templates, Livewire views, Filament render hooks
├── routes/
│   ├── api.php                 # Optional API route definitions
│   └── web.php                 # Optional Web route definitions
├── src/
│   ├── <Plugin>ServiceProvider.php # Primary service provider extending PackageServiceProvider
│   ├── <Plugin>Plugin.php          # Filament Plugin class (implements Filament\Contracts\Plugin)
│   ├── Enums/                  # Domain enumerations (statuses, types, states)
│   ├── Events/                 # Domain event classes
│   ├── Filament/               # Admin / Customer Filament components
│   │   ├── Clusters/           # Sub-domain clusters (Operations, Configurations, etc.)
│   │   ├── Pages/              # Standalone Filament pages
│   │   ├── Resources/          # Filament CRUD resources
│   │   └── Widgets/            # Filament dashboard/table widgets
│   ├── Listeners/              # Cross-plugin event listeners
│   ├── Livewire/               # Embedded Livewire components
│   ├── Models/                 # Eloquent domain models
│   ├── Observers/              # Eloquent model observers
│   ├── Policies/               # Authorization policies
│   ├── Services/               # Domain services and calculation managers
│   └── Traits/                 # Reusable domain model traits
└── tests/                      # Automated test suite (Feature/ and Unit/ with Pest)
```

### 4.3 Service Provider Inheritance & Lifecycle Execution

All 28 plugin service providers inherit from `Webkul\PluginManager\PackageServiceProvider`:

```
Illuminate\Support\ServiceProvider
       ▲
       │
Spatie\LaravelPackageTools\PackageServiceProvider
       ▲
       │
Webkul\PluginManager\PackageServiceProvider
       ▲
       │
Webkul\<Namespace>\<Plugin>ServiceProvider (all 28 plugins)
```

#### Provider Lifecycle Execution Stages

1. **`register()` Phase**:
   - `registeringPackage()` hook fires.
   - Instantiates `Webkul\PluginManager\Package` and sets base path.
   - Invokes abstract `configureCustomPackage(Package $package)`.
   - Merges package configuration files via `$this->mergeConfigFrom()`.
   - Merges local `config/filament-shield.php` via `$this->mergeShieldConfig()`.
   - Invokes `packageRegistered()` hook (where plugins bind to Filament via `Panel::configureUsing()`).
2. **`boot()` Phase**:
   - `bootingPackage()` hook fires.
   - Publishes assets, configurations, and views if running in console.
   - **Conditional Migration Loading**:
     ```php
     if ($this->package->runsMigrations) {
         if ($this->package->isCore || $this->package->isInstalled()) {
             $this->loadMigrationsFrom($filePath);
         }
     }
     ```
     Migrations for optional plugins are never loaded unless the plugin is marked installed in the `plugins` table.
   - **Conditional Route Loading**:
     ```php
     if ($this->package->isCore || $this->package->isInstalled()) {
         foreach ($this->package->routeFileNames as $routeFileName) {
             $this->loadRoutesFrom("{$this->package->basePath('/../routes/')}{$routeFileName}.php");
         }
     }
     ```
   - Loads translations and views.
   - Invokes `packageBooted()` hook (where observers, dynamic relations, and event listeners register).

### 4.4 Plugin Manager Engine (`plugins/webkul/plugin-manager`)

`plugins/webkul/plugin-manager` is the central operational engine for the plugin ecosystem.

#### 1. Database Schema
- `plugins` table: Stores `name`, `author`, `summary`, `description`, `latest_version`, `license`, `is_active`, `is_installed`, and `sort`.
- `plugin_dependencies` table: Pivot table mapping `plugin_id` to prerequisite `dependency_id`.

#### 2. Dynamic Discovery Without Filesystem Scanning (`Plugin::getAllPluginPackages()`)
Rather than scanning disk directories at runtime, `Plugin::getAllPluginPackages()` (`plugins/webkul/plugin-manager/src/Models/Plugin.php:58–97`) reflects upon registered Filament panels:
1. Iterates over `$panels = app('filament')->getPanels()`.
2. Inspects `$panel->getPlugins()`.
3. Derives the service provider class name from the plugin class (replacing `Plugin` with `ServiceProvider`).
4. Instantiates the provider, runs `configureCustomPackage()`, filters out core plugins, and returns the discovered package instances.

#### 3. O(1) In-Memory Installation Lookup (`Package::isPluginInstalled()`)
Checking plugin availability occurs thousands of times per request:
```php
// File: plugins/webkul/plugin-manager/src/Package.php:270-296
public static function isPluginInstalled(string $name): bool
{
    static $isLoaded = false;

    try {
        if (! $isLoaded) {
            DB::connection()->getPdo();

            if (Schema::hasTable('plugins') === false) {
                $isLoaded = true;
                return false;
            }

            static::$plugins = Plugin::all()->keyBy('name');
            $isLoaded = true;
        }

        if (isset(static::$plugins[$name]) && static::$plugins[$name]->is_installed) {
            return true;
        }

        return false;
    } catch (Exception) {
        return false;
    }
}
```
- Executes a single query on the first check: `Plugin::all()->keyBy('name')`.
- All subsequent checks across all 28 plugins execute via **O(1) static array lookup**.
- Safely handles un-migrated databases by returning `false` without throwing exceptions.

#### 4. Installation & Uninstallation Lifecycle Commands
- **`InstallCommand` (`{shortName}:install`)**: Resolves runtime dependencies recursively, publishes vendor assets, runs migrations, executes seeders, registers the record in `plugins`, syncs `plugin_dependencies`, regenerates Filament Shield permissions via `shield:generate`, and invalidates caches.
- **`UninstallCommand` (`{shortName}:uninstall {--force}`)**:
  - **Dependent Safety Check**: Checks if any currently installed plugin depends on this package. If installed dependents exist, uninstallation is **aborted immediately** (`UninstallCommand.php:46–50`).
  - **Reverse Migration Rollback**: Iterates through migration files in **reverse order**, executes `$migration->down()`, and removes entries from `migrations`.
  - Reverses settings migrations and deletes the plugin record.
  - Clears caches and triggers background `php artisan optimize` in production.

#### 5. Dynamic Permission Prefixing (`PermissionManager.php:14–66`)
Hooks into `FilamentShield::buildPermissionKeyUsing()`. It extracts the plugin namespace, converts it to snake_case, and prefixes permissions:
- Resources: `{affix}_{pluginKey}_{resourceName}` (e.g. `view_any_account_account`).
- Pages: `page_{pluginKey}_{pageName}` (e.g. `page_plugin_manager_plugin`).
- Widgets: `widget_{pluginKey}_{widgetName}`.

### 4.5 Inter-Plugin Dependency Graph & Validation

Aureus ERP enforces three distinct layers of dependency coupling:
1. **Composer Autoload**: Merges PSR-4 class mapping globally via `wikimedia/composer-merge-plugin`.
2. **Runtime Installation Dependencies**: Declared via `$package->hasDependencies([...])`, governing installation ordering and preventing accidental uninstallations.
3. **PHP Code-Level Coupling**: Inter-plugin imports, schema registries, and event listeners.

```
                           [ support ]  [ security ]  [ plugin-manager ]
                           [ fields ]   [ chatter ]   [ table-views ]
                           [ analytics ][ full-calendar ]
                                  ▲
                                  │ (Horizontal Infrastructure - Core)
                                  │
                             [ partners ]
                                  ▲
                                  │
                             [ products ] ◄──────────┐
                                  ▲                  │
                                  │                  │
                    ┌─────────────┴─────────────┐    │
                    │                           │    │
              [ accounts ]               [ inventories ]
              ▲     ▲    ▲                      ▲    ▲
              │     │    │                      │    │
     ┌────────┘     │    └────────┐             │    │
     │              │             │             │    │
[ accounting ] [ invoices ]  [ payments ]  [ barcode ]│
                    ▲             ▲                  │
                    │             │                  │
              ┌─────┴──────┐      │                  │
              │            │      │                  │
         [ purchases ]   [ sales ]                   │
              │            │                         │
              └────────────┼─────────────────────────┘
                           │
                           ▼
                   [ manufacturing ]

Other Isolated Chains:
[ employees ] ◄─── [ recruitments ]
[ employees ] ◄─── [ time-off ]
[ projects ]  ◄─── [ timesheets ]
[ website ]   ◄─── [ blogs ]
[ contacts ]  (Zero-table extension over partners)
[ maintenance ] (Independent domain relying on core + full-calendar)
```

### 4.6 Decoupled Cross-Plugin Integration Patterns

To prevent circular dependencies and monolithic schema bloat, Aureus ERP utilizes six decoupling mechanisms:

#### 1. Schema Registries (`AbstractSchemaRegistry`)
Downstream plugins contribute form fields, infolist entries, table columns, and header actions to upstream master resources without modifying upstream code:
- Base implementation: `Webkul\Support\Filament\Contributions\AbstractSchemaRegistry` (`support/src/Filament/Contributions/AbstractSchemaRegistry.php:7–110`).
- Registries: `PartnerSchemaRegistry`, `ProductSchemaRegistry`.
- Example (`accounts` contributing tax fields to product forms):
  ```php
  ProductSchemaRegistry::form('right.pricing.fields', fn () => AccountProductSchema::taxFields());
  ```
- Example (`inventories` contributing stock on-hand columns):
  ```php
  ProductSchemaRegistry::table('columns', fn () => InventoryProductSchema::onHandColumn());
  ProductSchemaRegistry::actions('header', fn () => UpdateQuantityAction::make());
  ```

#### 2. Dynamic Eloquent Relationships via `resolveRelationUsing`
Downstream packages inject dynamic Eloquent relationships into upstream models during `packageBooted()`:
- `accounts` injects into `Partner`: `propertyAccountPayable`, `propertyAccountReceivable`, `propertyPaymentTerm`.
- `accounts` injects into `Product`: `productTaxes` (belongsToMany), `supplierTaxes` (belongsToMany).
- `inventories` injects into `Product`: `routes`, `moveLines`, `moves`, `quantities` (hasMany).
- `manufacturing` injects into `Product`: `billsOfMaterials`, `billOfMaterialLines`.
- `purchases` injects into `Product`: `sellers` (hasMany `ProductSupplier`).

#### 3. Contributed Attributes & Casts (`HasContributedAttributes`)
Master models incorporate `Webkul\Support\Models\Concerns\HasContributedAttributes`. Downstream plugins call:
```php
Product::contributeFillable(['tracking', 'is_storable']);
Product::contributeCasts(['tracking' => ProductTracking::class]);
```
During model instantiation, `initializeHasContributedAttributes()` merges contributed fields and casts into Eloquent's native `$fillable` and `$casts` arrays.

#### 4. Company-Scoped Attributes (`CompanyProperty`)
To support company-specific settings on global master records (`Partner`, `Product`, `Category`) without schema changes, `accounts` provides `Webkul\Account\Casts\CompanyProperty`:
- Syntax: `'property_account_income_id' => CompanyProperty::class.':'.ProductCompanyAccount::class.',product_id'`
- Intercepts attribute getters/setters and routes reads/writes to company-scoped property tables (`partner_company_properties`, `product_company_accounts`, `category_company_accounts`) scoped by `company_id = current_company_id()`.
- Utilizes PHP `WeakMap` for unpersisted pending changes, flushing to the database upon model save.

#### 5. Product Usage Governance (`ProductUsageRegistry`)
Prevents accidental deletion of products referenced in operational modules:
- File: `plugins/webkul/products/src/Support/ProductUsageRegistry.php:9–82`.
- Downstream registration:
  - `manufacturing`: `Order`, `WorkOrder`, `UnbuildOrder`, `BillOfMaterial`, `BillOfMaterialLine`.
  - `purchases`: `OrderLine`, `RequisitionLine`.
  - `sales`: `OrderLine`, `OrderOption`, `OrderTemplateProduct`.
- Deletion verification: `ProductUsageRegistry::isProductInUse($productId)` queries all registered models without global scopes prior to deletion.

#### 6. Cross-Boundary Domain Events
- `accounts` publishes `MoveConfirmed`, `MoveCancelled`, `MoveDrafted`, `MoveReversed`, `MovePaid`.
- `inventories` publishes `OperationDone`, `OperationBackOrdered`, `OperationConfirmed`, `OperationCanceled`.
- `sales` listens:
  - `OperationDone` -> `ComputeSaleOrderListener` (updates delivered quantities).
  - `MovePaid` -> `SendSMSNotificationListener` (sends SMS alert).
  - `MoveConfirmed`, `MoveCancelled` -> `ComputeSaleOrderFromMoveListener` (updates invoice status).
- `purchases` listens:
  - `OperationDone` -> `ComputePurchaseOrderListener` (updates received quantities).
  - `MoveConfirmed`, `MoveCancelled` -> `ComputePurchaseOrderFromMoveListener` (updates bill status).

---

## 5. Multi-Company Tenancy & Isolation Architecture

### 5.1 Bespoke Eloquent Tenancy vs. Filament Native Tenancy

Aureus ERP deliberately avoids Filament's native single-tenant routing (`$panel->tenant(...)`). Instead, it implements a bespoke tenancy suite operating at the Eloquent ORM layer.

**Why Filament Tenancy Was Rejected**:
1. Filament's native tenancy forces a single active tenant per URL route (`/admin/{tenant}/...`), which breaks multi-company aggregation.
2. Enterprise ERP users frequently need to view unified ledgers, multi-warehouse stock balances, and consolidated reports across multiple operating companies simultaneously.
3. Decoupling tenancy into Eloquent global scopes ensures that multi-company isolation protects all execution paths: Filament UI, REST APIs, CLI commands, queued jobs, and Pest automated tests.

### 5.2 Tenancy Primitives: Scopes, Traits, and Context

The tenancy suite is centered in `plugins/webkul/support/src/`:

```
┌────────────────────────────────────────────────────────────────────────┐
│                        Tenancy Suite Hierarchy                         │
├──────────────────────────────────┬─────────────────────────────────────┤
│ 1. Context Resolution            │ Webkul\Support\Services\            │
│                                  │   CompanyContext (Singleton)        │
├──────────────────────────────────┼─────────────────────────────────────┤
│ 2. Single-Company Scope          │ Webkul\Support\Models\Scopes\       │
│                                  │   CompanyScope                      │
├──────────────────────────────────┼─────────────────────────────────────┤
│ 3. Multi-Company Pivot Scope     │ Webkul\Support\Models\Scopes\       │
│                                  │   CompaniesScope                    │
├──────────────────────────────────┼─────────────────────────────────────┤
│ 4. Allowed Companies Scope       │ Webkul\Support\Models\Scopes\       │
│                                  │   AllowedCompanyScope               │
├──────────────────────────────────┼─────────────────────────────────────┤
│ 5. Model Trait                   │ Webkul\Support\Traits\              │
│                                  │   BelongsToCompany                  │
└──────────────────────────────────┴─────────────────────────────────────┘
```

#### 1. `Webkul\Support\Services\CompanyContext`
Scoped singleton in the Laravel container (`SupportServiceProvider.php:165`):
- Session Storage Key: `public const SESSION_KEY = 'active_company_ids';`
- Resolves authenticated user (`$user = auth()->user()`).
- Resolves allowed companies: `$user->allowedCompanies()->get()` (or all companies if `Gate::allows('bypass_company_scope')`).
- **Tamper-Proof Active ID Resolution (`CompanyContext.php:67–85`)**:
  ```php
  public function activeIds(): array
  {
      $allowed = $this->allowedIds();
      if (empty($allowed)) {
          return [];
      }
      $stored = session(self::SESSION_KEY);
      if (! is_array($stored) || empty($stored)) {
          $default = $this->defaultId();
          return [$default && in_array($default, $allowed) ? $default : $allowed[0]];
      }
      $active = array_values(array_intersect($stored, $allowed));
      return empty($active) ? [$allowed[0]] : $active;
  }
  ```
  `array_intersect($stored, $allowed)` strips out any company ID not explicitly mapped to the user in `user_allowed_companies`, preventing session tampering.
- Primary Company Resolution: `currentId(): ?int { return $this->activeIds()[0] ?? $this->defaultId(); }`.

#### 2. `Webkul\Support\Models\Scopes\CompanyScope`
```php
// File: plugins/webkul/support/src/Models/Scopes/CompanyScope.php
public function apply(Builder $builder, Model $model): void
{
    if (app()->runningInConsole() && ! app()->runningUnitTests()) {
        return;
    }
    $context = app(CompanyContext::class);
    if (! $context->internalUser()) {
        return;
    }
    if ($context->bypassed()) {
        return;
    }
    $ids = $context->activeIds();
    $table = $model->getTable();
    $builder->where(function (Builder $query) use ($ids, $table) {
        $query->whereIn($table.'.company_id', $ids)
            ->orWhereNull($table.'.company_id');
    });
}
```
- Appends `WHERE (table.company_id IN (...) OR table.company_id IS NULL)`.
- Natively allows shared records where `company_id` is null.

#### 3. `Webkul\Support\Traits\BelongsToCompany`
```php
// File: plugins/webkul/support/src/Traits/BelongsToCompany.php
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model) {
            if (empty($model->company_id) && static::autoAssignsCompany()) {
                $model->company_id ??= app(CompanyContext::class)->currentId();
            }
        });
    }

    public static function autoAssignsCompany(): bool
    {
        return true;
    }
}
```
Automatically binds `CompanyScope` and stamps new records with `currentId()` on the `creating` event. Global shared models override `autoAssignsCompany(): bool { return false; }`.

### 5.3 Multi-Active Company Viewing Mode

Aureus ERP supports **multi-active viewing**:
- A user can select multiple companies in the header switcher (e.g., Company 1 and Company 2).
- Queries retrieve records where `company_id IN (1, 2)`.
- However, when creating a new record, the application must stamp a single `company_id`. The system designates **index 0** of the active array as the primary company (`currentId() = activeIds()[0]`).
- Clicking a specific company in the switcher sets it at index 0, designating it as the active creation target.

### 5.4 Referential Tenancy Integrity & Cross-Company Guards

Cross-company relational contamination (e.g., assigning a warehouse from Company B to a sales order in Company A) is strictly prevented across four layers:

```
┌────────────────────────────────────────────────────────────────────────┐
│                      Cross-Company Guard Layers                        │
├──────────────────────────────────┬─────────────────────────────────────┤
│ 1. Filament UI Form Cleansing    │ clear_foreign_company_values()      │
│ 2. Eloquent Model Hook           │ ChecksCompanyConsistency trait      │
│ 3. Deep Relational Assertion     │ CompanyConsistencyGuard::detect()   │
│ 4. Exception & UI Flash          │ CrossCompanyException               │
└──────────────────────────────────┴─────────────────────────────────────┘
```

#### 1. `Webkul\Support\Traits\ChecksCompanyConsistency`
Hooks into Eloquent `creating` and `updating` events (`ChecksCompanyConsistency.php:12–25`):
```php
public static function bootChecksCompanyConsistency(): void
{
    $check = function ($model) {
        if (app()->runningInConsole()) {
            return;
        }
        $model->assertCompanyConsistency();
    };
    static::creating($check);
    static::updating($check);
}
```
Models declare their foreign key boundary mappings via `companyConsistentFields()`:
```php
// In Webkul\Sale\Models\Order:
public function companyConsistentFields(): array
{
    return [
        'warehouse_id'       => Warehouse::class,
        'fiscal_position_id' => FiscalPosition::class,
        'payment_term_id'    => PaymentTerm::class,
        'journal_id'         => Journal::class,
    ];
}
```

#### 2. `Webkul\Support\Support\CompanyConsistencyGuard`
Queries referenced foreign models using `withoutGlobalScopes()`, asserts that referenced foreign records belong to the target model's `company_id`, and throws `CrossCompanyException::forRecords($conflicts)` if boundary violations are detected.

#### 3. UI Pre-Emptive Form Cleansing (`helpers.php`)
`clear_foreign_company_values($set, $get, array $fields, $companyId = null)` is attached to `->afterStateUpdated()` on company select inputs in Filament forms. When the company selection changes, all dependent foreign key form fields are instantly purged to prevent submitting invalid cross-tenant combinations.

### 5.5 Tenancy Scope Bypasses & Privileged Contexts

1. **Super-Admin Global Bypass (`SupportServiceProvider.php:94–104`)**:
   ```php
   Gate::before(function ($user, string $ability) {
       if ($ability !== 'bypass_company_scope') {
           return null;
       }
       if ($user && method_exists($user, 'hasRole') && $user->hasRole(array_filter([config('filament-shield.super_admin.name'), 'super_admin']))) {
           return true;
       }
       return null;
   });
   ```
   When granted, `CompanyContext::bypassed()` returns `true`, and scopes exit without applying query constraints.
2. **CLI / Background Worker Bypass**:
   `CompanyScope`, `CompaniesScope`, and `AllowedCompanyScope` check:
   ```php
   if (app()->runningInConsole() && ! app()->runningUnitTests()) {
       return;
   }
   ```
   Ensures queue workers, background seeders, and Artisan commands operate across all companies, while actively enforcing scopes during automated Pest tests (`runningUnitTests()`). In contrast, `ChecksCompanyConsistency` (`ChecksCompanyConsistency.php:12`) checks:
   ```php
   if (app()->runningInConsole()) {
       return;
   }
   ```
   This unconditionally bypasses consistency validation during all console execution (including CLI commands, seeders, and migrations).
3. **Explicit Query Bypass**:
   Services can explicitly bypass tenancy via `Model::query()->withoutGlobalScope(CompanyScope::class)`.

### 5.6 Tenant Switcher UI & Controller Integration

- **Render Hook Mount (`SupportServiceProvider.php:112`)**: Mounts `support::company-switcher` at `PanelsRenderHook::GLOBAL_SEARCH_BEFORE` in the `admin` panel.
- **Blade Component (`company-switcher.blade.php`)**: Displays a topbar dropdown showing the primary company name (`$active[0]`), checkboxes for all allowed companies, and a "Reset" action.
- **Controller Endpoint (`POST /company-context/set`)**: Handled by `CompanyContextController::set()`, which updates `session('active_company_ids')` and redirects back.

---

## 6. Security, Authentication & Authorization Architecture

### 6.1 Dual-Guard Authentication Architecture

Aureus ERP enforces strict identity and session boundaries across administrative and portal traffic using two separate guards (`config/auth.php`):

| Guard | Driver | Provider | Model Class | Target Panel | Session Broker |
|---|---|---|---|---|---|
| `web` | `session` | `users` | `Webkul\Security\Models\User` | Admin Panel (`/admin`) | `users` (60m expire) |
| `customer` | `session` | `customers` | `Webkul\Website\Models\Partner` | Customer Panel (`/`) | `customers` (60m expire) |

- **Administrative Users (`Webkul\Security\Models\User`)**: Extends `App\Models\User`. Implements `FilamentUser`, multi-factor TOTP authentication (`HasAppAuthentication`), Spatie permissions (`HasRoles`), and ownership scoping (`HasOwnershipScope`). Panel access is gated: `canAccessPanel(): bool { return $this->is_active; }`.
- **Portal Customers (`Webkul\Website\Models\Partner`)**: Master partner records possessing login credentials for the customer portal. Completely isolated from administrative access.

### 6.2 Filament Shield Integration & Distributed Configuration

Filament Shield (`4.2.0`) manages role-based access control. Rather than maintaining a monolithic configuration, Aureus ERP implements **distributed Shield configuration**:
- Each local plugin package provides its own `config/filament-shield.php`.
- In `Webkul\PluginManager\PackageServiceProvider::register()`:
  ```php
  $this->mergeShieldConfig();
  ```
  Dynamically merges `filament-shield.resources.manage`, `resources.exclude`, `pages.exclude`, and `widgets.exclude` from each package into Laravel's runtime configuration.

### 6.3 Role & Permission Lifecycle and Protected System Roles

1. **Immutable System Roles (`Role.php:27–42`)**:
   `Webkul\Security\Models\Role` defines protected system roles:
   ```php
   protected const SYSTEM_ROLE_FALLBACKS = ['admin', 'super_admin'];
   ```
   In `Role::booted()`:
   - Attempting to update `name` or `guard_name` on a system role throws `AuthorizationException`.
   - Attempting to delete a system role throws `AuthorizationException`.

   > **Known Lifecycle Hazard (`HasPermissions::bootHasPermissions()`)**: Spatie's underlying `Spatie\Permission\Traits\HasPermissions::bootHasPermissions()` trait registers a `deleting` model event listener that automatically detaches permissions (`$model->permissions()->detach()`) and user associations (`$model->users()->detach()`). Because trait boot listeners execute before the subclass listener in `Role::booted()`, invoking `$role->delete()` on a protected system role causes Spatie to execute its pivot detaches *before* `Role::booted()` evaluates `isSystemRole()` and throws `AuthorizationException`. Consequently, unless the deletion attempt is executed within a rolled-back database transaction (`DB::transaction`), the role record is preserved but stripped of all its permissions and assigned users.
2. **High-Performance Permission Synchronization**:
   `Role::syncPermissionsByNames(Collection|array $permissionNames)` executes bulk database insertions:
   - Batches missing permissions into chunks of 500 using `insertOrIgnore`.
   - Batches pivot insertions into chunks of 1000 directly against `role_has_permissions`.
   - Clears permission cache via `PermissionRegistrar::forgetCachedPermissions()`.
3. **Permission Naming Convention**:
   Format: `<action>_<plugin>_<entity>` (e.g. `view_any_contact_partner`, `create_sales_order`, `update_accounts_account_move`).

### 6.4 Fine-Grained Record Ownership Architecture (`Bouncer`)

Within an isolated company, Aureus ERP provides record-level access control:

```
┌────────────────────────────────────────────────────────────────────────┐
│                        Record Ownership Flow                           │
│                                                                        │
│   User (resource_permission: GLOBAL | GROUP | INDIVIDUAL)              │
│                           │                                            │
│                           ▼                                            │
│                Bouncer::getAuthorizedUserIds()                         │
│                           │                                            │
│              ┌────────────┼─────────────┐                              │
│              ▼            ▼             ▼                              │
│           GLOBAL        GROUP       INDIVIDUAL                         │
│           (null)      (Team IDs)     ([$user->id])                     │
│              │            │             │                              │
│              └────────────┼─────────────┘                              │
│                           ▼                                            │
│                 OwnershipScope::apply()                                │
│                           │                                            │
│     ┌─────────────────────┼────────────────────┐                       │
│     ▼                     ▼                    ▼                       │
│OwnerSource::column  OwnerSource::relation  OwnerSource::followers      │
└────────────────────────────────────────────────────────────────────────┘
```

#### 1. `Webkul\Security\Bouncer` (`plugins/webkul/security/src/Bouncer.php`)
Evaluates `auth()->user()->resource_permission` (`PermissionType` enum):
- `GLOBAL`: Returns `null` (unrestricted access to all records in the active company).
- `GROUP`: Queries `teams` and `user_team` pivot table to return IDs of all users sharing team membership with the current user.
- `INDIVIDUAL`: Returns `[$user->id]`.

#### 2. `Webkul\Security\Models\Scopes\OwnershipScope`
Applies ownership constraints dynamically based on the model's declared `ownershipSources()`:
- `OwnerSource::column($col)`: `$query->orWhereIn($table.'.'.$col, $userIds)`
- `OwnerSource::relation($rel, $key)`: `$query->orWhereHas($rel, fn($q) => $q->whereIn($key, $userIds))`
- `OwnerSource::pivot($table, $fk, $relatedKey)`: `$query->orWhereExists(...)`
- `OwnerSource::followers()`: Resolves chatter followers and grants access to record followers.

### 6.5 Orthogonal Decoupling: Authorization Policies vs. Tenant Scoping

A critical architectural invariant in Aureus ERP is the **strict orthogonal separation of authorization policies and tenancy scoping**:
- **Policies Evaluate Role Permissions**: Policies (e.g., `PartnerPolicy`, `OrderPolicy`, `MovePolicy`) check only user role capabilities via `$user->can('view_any_...')`. Policies **never** filter by `company_id`.
- **Global Scopes Enforce Tenancy**: Data filtering by company is handled exclusively by `CompanyScope`.
This ensures clean separation of concerns: changing tenancy rules never requires modifying security policies, and vice versa.

---

## 7. Filament Panel Architecture

### 7.1 Dual-Panel Topography

Aureus ERP configures two distinct Filament panels:

```
┌────────────────────────────────────────────────────────────────────────┐
│                              HTTP Request                              │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                  ┌─────────────────┴─────────────────┐
                  ▼                                   ▼
          Path: `/admin/*`                     Path: `/*`
   ┌─────────────────────────────┐     ┌─────────────────────────────┐
   │      Admin Panel            │     │    Customer Portal Panel    │
   │   (AdminPanelProvider)      │     │   (CustomerPanelProvider)   │
   ├─────────────────────────────┤     ├─────────────────────────────┤
   │ • id: 'admin' (default)     │     │ • id: 'customer'            │
   │ • path: 'admin'             │     │ • path: '/'                 │
   │ • guard: 'web'              │     │ • guard: 'customer'         │
   │ • Top navigation            │     │ • Top navigation            │
   │ • maxContentWidth(Full)     │     │ • darkMode(false)           │
   │ • Shield & Translatable     │     │ • Self-registration & reset │
   │ • Global Search Provider    │     │ • Customer portal pages     │
   │ • 30s Database Notifications│     │ • Public brand headers      │
   └──────────────┬──────────────┘     └──────────────┬──────────────┘
                  │                                   │
                  └─────────────────┬─────────────────┘
                                    │
                                    ▼
       ┌────────────────────────────────────────────────────────┐
       │   Dynamically Injected via `Panel::configureUsing()`   │
       │   from 28 Plugin Service Providers                     │
       └────────────────────────────────────────────────────────┘
```

### 7.2 Inversion of Control: `Panel::configureUsing()`

Neither `AdminPanelProvider` nor `CustomerPanelProvider` hardcodes business resources. The registration is inverted:
1. In `packageRegistered()`, each plugin provider hooks into panel configuration:
   ```php
   Panel::configureUsing(function (Panel $panel): void {
       $panel->plugin(AccountPlugin::make());
   });
   ```
2. When Filament boots, it triggers the callback, instantiating the plugin class.

### 7.3 Dynamic Discovery Gating & Panel Archetypes

Filament panel registration differentiates strictly between core infrastructure and optional domain packages:

1. **19 Optional Domain Plugins Gate on Installation State**:
   Within `*Plugin.php::register(Panel $panel)`, all 19 optional domain plugins evaluate `Package::isPluginInstalled($this->getId())` before discovering any UI components:
   ```php
   public function register(Panel $panel): void
   {
       if (! Package::isPluginInstalled($this->getId())) {
           return;
       }

       $panel->when($panel->getId() == 'admin', function (Panel $panel) {
           $panel
               ->discoverResources(in: __DIR__.'/Filament/Admin/Resources', for: '...')
               ->discoverPages(in: __DIR__.'/Filament/Admin/Pages', for: '...')
               ->discoverClusters(in: __DIR__.'/Filament/Admin/Clusters', for: '...')
               ->discoverWidgets(in: __DIR__.'/Filament/Admin/Widgets', for: '...');
       });
   }
   ```
   If the plugin is not installed in the `plugins` table, `register()` exits immediately. Its resources, pages, clusters, and navigation items are never registered in Filament's component registry.

2. **7 Core Infrastructure Plugins Register Unconditionally**:
   The 7 core infrastructure plugins with Filament panel participation (`chatter`, `fields`, `full-calendar`, `partners`, `plugin-manager`, `security`, and `support`) do **not** gate on `Package::isPluginInstalled()`. They register their resources, widgets, render hooks, and configuration unconditionally into the panels, ensuring baseline platform capabilities (identity, tenancy context, custom fields, chatter activity, partner masters, and plugin lifecycle administration) are permanently available. (The remaining 2 core packages, `analytics` and `table-views`, do not declare a Filament `*Plugin` class).

#### Panel Participation Breakdown
- **Admin Panel Only (21 plugins)**: `accounting`, `accounts`, `barcode`, `contacts`, `employees`, `fields`, `inventories`, `invoices`, `maintenance`, `manufacturing`, `partners`, `payments`, `plugin-manager`, `products`, `projects`, `recruitments`, `sales`, `security`, `support`, `time-off`, `timesheets`.
- **Dual Panel (3 plugins)**:
  - `website`: Login, registration, password reset, account clusters, public CMS pages, topbar links, and footer render hooks.
  - `blogs`: Backend editor in admin panel; customer-facing reading UI in customer panel.
  - `purchases`: Admin procurement clusters; customer/vendor quotation and order portal in customer panel.
- **Universal (2 plugins)**: `chatter`, `full-calendar` (embeddable widgets across both panels).
- **Non-Filament Plugins (2 plugins)**: `analytics` (pure backend service), `table-views` (Filament render hooks only).

### 7.4 Central Navigation Hierarchy & Cluster Breadcrumbs

#### Navigation Groups Enum (`Webkul\Support\Enums\NavigationGroup`)
Centralizes all navigation groups to avoid hardcoded string literals:
- File: `plugins/webkul/support/src/Enums/NavigationGroup.php`.
- Cases: `Dashboard`, `Contact`, `Sale`, `Purchase`, `Maintenance`, `Manufacturing`, `Inventory`, `Invoice`, `Accounting`, `Project`, `Employee`, `TimeOff`, `Recruitment`, `Website`, `Barcode`, `Plugin`, `Setting`, `Help`.
- Maps custom SVG icons (`icon-sales`, `icon-inventories`, etc.) and localized labels (`__('admin.navigation.' . $this->value)`).

#### Cluster Breadcrumbs (`HasClusterBreadcrumbs`)
Complex domains organize resources inside Filament Clusters (e.g. `inventories` clusters `Operations`, `Configurations`, `Products`, `Reporting`, `Settings`). The trait `Webkul\Support\Traits\HasClusterBreadcrumbs` automatically generates breadcrumb hierarchies navigating from Group -> Cluster -> Resource -> Record.

---

## 8. Persistence & Database Conventions

### 8.1 Schema Architecture Across 383 Migrations

Aureus ERP contains **383 database migrations**:
- Core application: `database/migrations/` (13 migrations).
- Local plugins: `plugins/webkul/*/database/migrations/` (370 migrations across 22 plugins).
Plugin migrations are loaded explicitly via `$package->hasMigrations([...])` and gated by installation state.

### 8.2 Table & Column Conventions

1. **Table Naming**:
   - Domain plugin tables: snake_case prefixed by package name: `<plugin_plural>_<entity_plural>` (e.g., `accounts_account_moves`, `inventories_warehouses`, `sales_orders`).
   - Shared infrastructure tables: unprefixed (e.g., `companies`, `currencies`, `users`, `teams`, `custom_fields`, `plugins`).
2. **Column Standards**:
   - Primary Key: `$table->id()` (BIGINT unsigned auto-increment). UUIDs are never used for domain tables.
   - Company Identifier: `$table->foreignId('company_id')->constrained('companies')`.
   - Creator Audit: `$table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete()`.
   - Timestamps: `$table->timestamps()`.
   - Soft Deletes: `$table->softDeletes()` (`deleted_at`).
   - States / Statuses: Stored as `$table->string('state')` or `$table->string('status')` and cast to PHP 8 backed enums. Native database `ENUM` types are prohibited.

### 8.3 Foreign Key Semantics & Referential Cascade Tiers

Audit of 907+ foreign key deletion rules establishes three distinct semantic tiers:

| Delete Behavior | Occurrences | Semantic Lifecycle Rule | Repository Examples |
|---|---|---|---|
| `nullOnDelete()` | 551 | Optional reference; child entity survives parent deletion. | `creator_id` / `user_id`, `partner_id`, self-referencing `parent_id`, nullable `company_id` on global entities. |
| `cascadeOnDelete()` | 185 | Strict composition / ownership; child is meaningless without parent. | `sales_order_lines.order_id`, `accounts_account_move_lines.move_id`, junction tables (`user_team`, `user_allowed_companies`). |
| `restrictOnDelete()` | 171 | Critical referential, financial, or audit integrity; parent cannot be deleted while dependent records exist. | `currency_id` on financial moves/orders, `company_id` on posted journal entries and manufacturing orders, `uom_id` on products. |

### 8.4 Multi-Company Storage Models

Aureus ERP employs three distinct database persistence patterns for multi-tenancy:

1. **Single-Company Scoped Entity (`BelongsToCompany`)**:
   Direct `company_id` column with `restrictOnDelete()` or `nullOnDelete()`.
2. **Multi-Company Pivot Entity (`BelongsToCompanies`)**:
   Junction table mapping entity to multiple companies (e.g., `accounts_account_companies` with unique constraint `['account_id', 'company_id']`).
3. **Shared Master with Company-Specific Properties (EAV Property Table)**:
   Master records (`Partner`, `Product`, `Category`) exist globally, while company-specific accounting ledgers and payment terms are stored in property tables (`partners_partner_company_properties`, `products_product_company_accounts`) scoped by composite key `['record_id', 'company_id']`.
4. **Compound Unique Indexes**:
   Multi-tenant uniqueness constraints combine `company_id` with business codes:
   - `['company_id', 'code']` on warehouses and sequences.
   - `['company_id', 'name']` on warehouses and sequences.
   - `['company_id', 'barcode']` on inventory locations.

---

## 9. Architectural Invariants, Constraints & Extension Guidelines

### 9.1 Non-Negotiable Architectural Rules

Every modification, extension, or new plugin in Aureus ERP must strictly adhere to these non-negotiable rules:

1. **Zero Domain Logic in `app/`**: The host application is strictly a micro-kernel. Never add domain models, migrations, or business controllers to `app/`.
2. **Company Isolation is Mandatory**: Every query touching company-scoped data must pass through `CompanyScope`, `CompaniesScope`, or `CompanyContext`. Bypassing isolation requires explicit architectural justification and `Gate::allows('bypass_company_scope')`.
3. **Source-of-Truth Hierarchy**: Source code is Level 1 absolute authority. When code and documentation diverge, source code wins.
4. **No Direct `DB::` Domain Queries**: Always use `Model::query()` and Eloquent relationships to ensure global scopes (`CompanyScope`, `OwnershipScope`) and model observers execute.
5. **No Phantom Architecture**: Never reference or introduce traits, base classes, or patterns that do not exist in the codebase.
6. **No Breaking Foreign Key References**: Always configure foreign key cascade rules according to the three semantic tiers (`nullOnDelete`, `cascadeOnDelete`, `restrictOnDelete`).
7. **Proactive Referential Consistency**: Transactional models referencing foreign relations must implement `companyConsistentFields()` to prevent cross-tenant poisoning.

### 9.2 Testing Bootstrap Architecture (`TestBootstrapHelper`)

Automated tests extend `Tests\TestCase` and utilize `DatabaseTransactions` (`tests/Pest.php`).
- Before test execution, `TestCase::setUp()` invokes `\TestBootstrapHelper::ensureERPInstalled()`.
- `TestBootstrapHelper` provisions a clean environment:
  - Runs `migrate:fresh --force`.
  - Executes `erp:install --force --no-interaction --admin-name='Test Admin' --admin-email='admin@example.com' --admin-password='admin123'`.
  - Uses an isolated database connection (`test_bootstrap`) to seed plugins without colliding with transactional rollbacks.
  - Gating preserves tenancy scopes during unit testing (`runningUnitTests() === true`).

### 9.3 New Plugin Authoring & Extension Standards

When authoring a new plugin under `plugins/webkul/<plugin>`:
1. **Manifest**: Define `composer.json` with PSR-4 autoload mapping and register provider in `extra.laravel.providers`.
2. **Provider**: Extend `Webkul\PluginManager\PackageServiceProvider`. Implement `configureCustomPackage()`, declare `$package->name('<plugin>')`, `$package->hasTranslations()`, `$package->hasViews()`. If defining database tables, declare `$package->hasMigrations([...])` and `$package->runsMigrations()`.
3. **Registration**: Explicitly register the provider in `bootstrap/providers.php` before `PluginManagerServiceProvider`.
4. **Filament Plugin**: Implement `Filament\Contracts\Plugin`. Hook into `Panel::configureUsing()` in `packageRegistered()`. Check `Package::isPluginInstalled($this->getId())` before registering resources, pages, or clusters.
5. **Cross-Plugin Decoupling**: If extending upstream resources, use `SchemaRegistry` or `resolveRelationUsing` rather than modifying upstream code.
6. **Tenancy**: If tables store tenant data, include `$table->foreignId('company_id')`, use trait `BelongsToCompany`, and implement `ChecksCompanyConsistency` on transactional models.
