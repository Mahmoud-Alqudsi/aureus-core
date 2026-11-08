# Aureus ERP — Architectural Reference Specification

**Status:** Canonical Root Architectural Reference  
**Authority:** Level 1 Source Code Authority  
**Repository:** `aureuserp`  
**Last Verified Baseline:** PHP ^8.3 | Laravel v13.36.0 | Filament v5.10.2 | Livewire v4.4.7 | Filament Shield 4.2.0  

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
   - [2.4 Production Containerization & Multi-Process Architecture](#24-production-containerization--multi-process-architecture)
   - [2.5 Native Mobile Shell Integration (NativePHP Remote)](#25-native-mobile-shell-integration-nativephp-remote)
   - [2.6 Frontend Architecture: Tailwind CSS v4, Vue 3 & Vite Cross-Package Compilation](#26-frontend-architecture-tailwind-css-v4-vue-3--vite-cross-package-compilation)
   - [2.7 Developer CLI Concurrency Pipeline (`composer dev`)](#27-developer-cli-concurrency-pipeline-composer-dev)
3. [Core Application Architecture (`app/` & `bootstrap/`)](#3-core-application-architecture-app--bootstrap)
   - [3.1 Host Application Layout & Responsibilities](#31-host-application-layout--responsibilities)
   - [3.2 Bootstrap Pipeline (`bootstrap/app.php`)](#32-bootstrap-pipeline-bootstrapappphp)
   - [3.3 Centralized Exception Handling & UI Notifications](#33-centralized-exception-handling--ui-notifications)
   - [3.4 Static Service Provider Registration (`bootstrap/providers.php`)](#34-static-service-provider-registration-bootstrapprovidersphp)
   - [3.5 Core Foundation Services, Dynamic OKLCH Branding & Multi-Language Negotiation](#35-core-foundation-services-dynamic-oklch-branding--multi-language-negotiation)
   - [3.6 REST API Architecture & OpenAPI Documentation Engine](#36-rest-api-architecture--openapi-documentation-engine)
4. [Domain & Plugin Ecosystem Topology](#4-domain--plugin-ecosystem-topology)
   - [4.1 Comprehensive Catalog of All 30 Local Plugins](#41-comprehensive-catalog-of-all-30-local-plugins)
   - [4.2 Plugin Directory Conventions & Manifest Layout](#42-plugin-directory-conventions--manifest-layout)
   - [4.3 Service Provider Inheritance & Lifecycle Execution](#43-service-provider-inheritance--lifecycle-execution)
   - [4.4 Plugin Manager Engine (`plugins/webkul/plugin-manager`)](#44-plugin-manager-engine-pluginswebkulplugin-manager)
   - [4.5 Inter-Plugin Dependency Graph & Validation](#45-inter-plugin-dependency-graph--validation)
   - [4.6 Decoupled Cross-Plugin Integration Patterns](#46-decoupled-cross-plugin-integration-patterns)
   - [4.7 Domain Facades & Service Manager Architecture](#47-domain-facades--service-manager-architecture)
   - [4.8 Headless Engine & Composable UI Architecture](#48-headless-engine--composable-ui-architecture)
5. [Multi-Company Tenancy & Isolation Architecture](#5-multi-company-tenancy--isolation-architecture)
   - [5.1 Bespoke Eloquent Tenancy vs. Filament Native Tenancy](#51-bespoke-eloquent-tenancy-vs-filament-native-tenancy)
   - [5.2 Tenancy Primitives: Scopes, Traits, and Context](#52-tenancy-primitives-scopes-traits-and-context)
   - [5.3 Multi-Active Company Viewing Mode](#53-multi-active-company-viewing-mode)
   - [5.4 Referential Tenancy Integrity & Interactive Form Isolation Guards](#54-referential-tenancy-integrity--interactive-form-isolation-guards)
   - [5.5 Tenancy Scope Bypasses & Privileged Contexts](#55-tenancy-scope-bypasses--privileged-contexts)
   - [5.6 Tenant Switcher UI & Controller Integration](#56-tenant-switcher-ui--controller-integration)
6. [Security, Authentication & Authorization Architecture](#6-security-authentication--authorization-architecture)
   - [6.1 Dual-Guard Authentication Architecture](#61-dual-guard-authentication-architecture)
   - [6.2 Filament Shield Integration & Distributed Configuration](#62-filament-shield-integration--distributed-configuration)
   - [6.3 Role & Permission Lifecycle and Protected System Roles](#63-role--permission-lifecycle-and-protected-system-roles)
   - [6.4 Fine-Grained Record Ownership Architecture (`Bouncer`)](#64-fine-grained-record-ownership-architecture-bouncer)
   - [6.5 Orthogonal Decoupling: Authorization Policies vs. Tenant Scoping](#65-orthogonal-decoupling-authorization-policies-vs-tenant-scoping)
7. [Filament Panel Architecture](#7-filament-panel-architecture)
   - [7.1 Multi-Panel Topography & Runtime Configuration](#71-multi-panel-topography--runtime-configuration)
   - [7.2 Inversion of Control: `Panel::configureUsing()`](#72-inversion-of-control-panelconfigureusing)
   - [7.3 Dynamic Discovery Gating & Panel Archetypes](#73-dynamic-discovery-gating--panel-archetypes)
   - [7.4 Central Navigation Hierarchy & Domain SVG Iconography](#74-central-navigation-hierarchy--domain-svg-iconography)
   - [7.5 Modular Navigation Topology: App Launcher & Dynamic Module Scoping](#75-modular-navigation-topology-app-launcher--dynamic-module-scoping)
   - [7.6 Tabular Line Items Architecture: Reusable Repeater, Column Manager & Summarizers](#76-tabular-line-items-architecture-reusable-repeater-column-manager--summarizers)
   - [7.7 Universal Workflow State Pipeline: ProgressStepper](#77-universal-workflow-state-pipeline-progressstepper)
   - [7.8 Record Sub-Navigation: RecordNavigationTabs & HasRecordNavigationTabs](#78-record-sub-navigation-recordnavigationtabs--hasrecordnavigationtabs)
   - [7.9 Collaboration & Activity Stream: Chatter Slide-Over & HasChatter](#79-collaboration--activity-stream-chatter-slide-over--haschatter)
   - [7.10 Reusable Resource Concerns: Navigation Labels, Form State Refresh & PDF Generation](#710-reusable-resource-concerns-navigation-labels-form-state-refresh--pdf-generation)
8. [Persistence & Database Conventions](#8-persistence--database-conventions)
   - [8.1 Schema Architecture Across 383 Migrations](#81-schema-architecture-across-383-migrations)
   - [8.2 Table & Column Conventions](#82-table--column-conventions)
   - [8.3 Foreign Key Semantics & Referential Cascade Tiers](#83-foreign-key-semantics--referential-cascade-tiers)
   - [8.4 Multi-Company Storage Models](#84-multi-company-storage-models)
   - [8.5 Root Database Seeders & Baseline Data Provisioning](#85-root-database-seeders--baseline-data-provisioning)
9. [Architectural Invariants, Constraints & Extension Guidelines](#9-architectural-invariants-constraints--extension-guidelines)
   - [9.1 Non-Negotiable Architectural Rules](#91-non-negotiable-architectural-rules)
   - [9.2 Dual-Tier Testing Architecture: Pest Feature Testing & Playwright Browser Automation](#92-dual-tier-testing-architecture-pest-feature-testing--playwright-browser-automation)
   - [9.3 New Plugin Authoring & Extension Standards](#93-new-plugin-authoring--extension-standards)
   - [9.4 Architecture Governance & Specification Synchronization Engine](#94-architecture-governance--specification-synchronization-engine)

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
| **Laravel Framework** | `^13.0` | `v13.36.0` | `composer.json:25`, `composer.lock:3858` |
| **Filament Admin Panel** | `^5.0` | `v5.10.2` | `composer.json:18`, `composer.lock:2240` |
| **Filament Settings Plugin** | `^5.0` | `v5.10.2` | `composer.json:19` |
| **Filament Shield** | `4.2.0` (pinned) | `4.2.0` | `composer.json:17`, `composer.lock:524` |
| **Livewire** | `^4.0` | `v4.4.7` | `composer.json:29`, `composer.lock:5136` |
| **Laravel Sanctum** | `^4.0` | `v4.3.3` | `composer.json:26`, `composer.lock:4144` |
| **Pest Testing Framework** | `^4.4` | `v4.7.8` | `composer.json:48`, `composer.lock:13666` |
| **Composer Merge Plugin** | `^2.1` | `v2.1.0` | `composer.json:37`, `composer.lock:12615` |
| **Filament Language Switch** | `^4.3` | `4.3.0` | `composer.json:16` |
| **Filament DateRangePicker** | `^5.0` | `5.0.7` | `composer.json:31`, `composer.lock:5372` |
| **Spatie Query Builder** | `^7.0` | `7.3.5` | `composer.json:35`, `composer.lock:9003` |
| **Spatie Translatable** | `^6.14` | `6.14.1` | `composer.json:36`, `composer.lock:9162` |
| **Zeus Spatie Translatable** | `^2.0` | `2.0.1` | `composer.json:24`, `composer.lock:3801` |
| **Laravel Pint** | `^1.27` | `v1.32.1` | `composer.json:44`, `composer.lock:13333` |
| **Scribe API Documentation** | `^5.6` | `5.11.0` | `composer.json:23`, `composer.lock:3709` |
| **NativePHP Remote Shell** | `^0.1.0` | `v0.1.0` | `composer.json:13`, `composer.lock:75` |
| **NativePHP Mobile Runtime** | `^3.3` | `3.3.9` | `composer.json:33`, `composer.lock:5850` |
| **Barryvdh Laravel DomPDF** | `^3.1` | `v3.1.2` | `composer.json:15`, `composer.lock:281` |
| **Barryvdh Laravel Debugbar** | `^4.0` | `v4.4.4` | `composer.json:14`, `composer.lock:181` |
| **Flowframe Laravel Trend** | `^0.5` | `v0.5.0` | `composer.json:20`, `composer.lock:2794` |
| **Guava Filament Icon Picker** | `^4.0` | `4.0.0` | `composer.json:21`, `composer.lock:3001` |
| **Khaled Alshamaa Ar-PHP** | `^7.0` | `v7.0.0` | `composer.json:22`, `composer.lock:3581` |
| **League Glide Image Engine** | `^2.3` | `2.3.2` | `composer.json:28`, `composer.lock:4748` |
| **Maatwebsite Excel** | `^3.1` | `3.1.70` | `composer.json:30`, `composer.lock:5211` |
| **Milon Barcode Engine** | `^13.0` | `v13.5.1` | `composer.json:32`, `composer.lock:5614` |
| **Spatie Eloquent Sortable** | `^5.0` | `5.0.1` | `composer.json:34`, `composer.lock:8720` |
| **Laravel Pail Log Streamer** | `^1.2.5` | `v1.2.7` | `composer.json:43`, `composer.lock:13251` |

### 2.2 Runtime Configuration & Self-Contained Persistence

Aureus ERP is architected to be completely **self-contained and zero-daemon** in its default configuration. Baseline configuration in `config/` confirms that every infrastructure subsystem utilizes database persistence:
- **Default Database Driver (`config/database.php:16`)**: `env('DB_CONNECTION', 'sqlite')`. Path resolves to `database/database.sqlite` with `foreign_key_constraints = true`.
- **Database Persistence & Production Dialects (`SupportServiceProvider.php:148–159`)**: While `sqlite` serves as the zero-daemon default for self-contained execution, local development, and rapid unit testing, multi-tenant enterprise ERP operations require MySQL, MariaDB, or PostgreSQL. `SupportServiceProvider:154` registers the `Webkul\Support\Database\Dialects\DatabaseDialect` singleton, which specifically implements `MySqlDialect` (`mysql`, `mariadb`) and `PostgresDialect` (`pgsql`). Advanced dialect operations (such as `jsonArrayAgg()`, `monthBucket()`, `alterColumnType()`, `syncSequences()`, and `caseInsensitiveEquals()`) are unsupported for SQLite and throw a `RuntimeException("No DatabaseDialect implementation is registered for the [sqlite] database driver. Supported drivers: mysql, mariadb, pgsql.")`.
- **MySQL/MariaDB Strict Mode Exemption (`config/database.php:58, 78`)**: In MySQL and MariaDB drivers, SQL strict mode is disabled (`'strict' => false`). Enterprise ERP systems perform complex financial aggregations, balance calculations, and multi-period reporting that utilize SQL `GROUP BY` expressions across non-aggregated descriptive columns (e.g. grouped invoices with customer names, tax breakdown aggregations). Disabling `ONLY_FULL_GROUP_BY` allows these performant relational groupings without synthetic nested sub-queries.
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

The merge plugin automatically scans and injects the `composer.json` of all 30 plugins under `plugins/webkul/*`. This provides:
1. Unified class loading across all plugin namespaces (e.g., `Webkul\Account\` -> `plugins/webkul/accounts/src/`).
2. Centralized dependency compilation and vendor conflict resolution during `composer install` or `composer update`.
3. Seamless developer workflows without maintaining individual Git submodules or separate package repositories.

### 2.4 Production Containerization & Multi-Process Architecture

For production deployments, Aureus ERP provides a container topology defined in `docker/production/Dockerfile`:
- **Base Environment**: Ubuntu 24.04 LTS, PHP 8.4, Node.js 22, MySQL 8, Nginx, and Supervisor.
- **Image Processing Extensions**: In addition to standard extensions (`bcmath`, `curl`, `mbstring`, `zip`, `pdo_mysql`, `intl`, `gd`), the Dockerfile compiles the ImageMagick PECL extension (`imagick.so`), optimizing high-resolution document processing and PDF barcode rendering.
- **Build-Time Installation Baking (`docker/production/build-install.sh`)**:
  Rather than migrating and seeding upon container boot (which creates startup latency and race conditions in multi-replica deployments), database provisioning is baked during `docker build`:
  1. Initializes a temporary MySQL daemon instance via `mysqld --initialize-insecure`.
  2. Starts the temporary MySQL daemon in the background.
  3. Executes `php artisan erp:install --force --no-interaction --admin-name='Admin' --admin-email='admin@example.com' --admin-password='password'`, which runs all core and plugin migrations, seeders, and initializes the root administrative company.
  4. Shuts down MySQL cleanly, baking the populated database files directly into the container image layer (`/var/lib/mysql`).
- **Process Orchestration (`supervisord.conf`)**:
  Supervisor coordinates 3 concurrent processes:
  - `mysqld`: Relational database engine.
  - `php-fpm8.4`: FastCGI process manager executing PHP requests.
  - `nginx`: Web server routing static assets and reverse-proxying PHP-FPM on port 80.
- **Container Health Check**: Evaluates `curl -fsS http://127.0.0.1/health` with a 30-second interval, 5-second timeout, and 3 retries.

### 2.5 Native Mobile Shell Integration (NativePHP Remote)

Aureus ERP incorporates native mobile shell capabilities via `aureuserp/nativephp-remote` and `nativephp/mobile`:
- **Configuration (`config/nativephp-remote.php:13–46`)**:
  - Detects native mobile shells through the URL query parameter `?nativephp=1` and sets a 30-day persistent cookie (`nativephp_remote_shell = 1`).
  - Automatically identifies whether incoming traffic originates from the native mobile shell wrapper (`NativePhpRemote::isNative()`).
- **Hardware Permissions Injection**:
  - Android: Automatically configures `android.permission.CAMERA` and `android.permission.VIBRATE`.
  - iOS: Injects `NSCameraUsageDescription` ("This app uses your camera to scan barcodes and QR codes.").
- **Barcode & Hardware Scanners**: In conjunction with `plugins/webkul/barcode`, warehouse operators running the native mobile application can trigger real-time device camera scanning for serials, lots, and SKU tracking directly from the browser view.

### 2.6 Frontend Architecture: Tailwind CSS v4, Vue 3 & Vite Cross-Package Compilation

Frontend assets are bundled via **Vite v5** with the **Tailwind CSS v4** engine and **Vue 3** for interactive terminals (`package.json:9–16`):
- **Vite Configuration (`vite.config.js:7–18`)**:
  Rather than compiling plugin assets in isolation, the root Vite configuration compiles host assets alongside modular plugin assets and Vue 3 single-page applications in a single bundle:
  ```javascript
  laravel({
      input: [
          'resources/css/app.css',
          'resources/js/app.js',
          'plugins/webkul/barcode/resources/dist/barcode.css',
          'plugins/webkul/barcode/resources/dist/barcode.js',
          'plugins/webkul/point-of-sale/resources/js/till/main.js',
      ],
      refresh: true,
  }),
  vue(),
  ```
- **Vue 3 Till Engine**: Powers the high-performance Point of Sale till interface at `plugins/webkul/point-of-sale/resources/js/till/` via `@vitejs/plugin-vue`.
- **Tailwind CSS v4 & Theme Compilation**: Built on `@tailwindcss/vite` (^4.3.0) and `@tailwindcss/cli` (^4.3.0). Administrative and customer themes are compiled via `npm run build:theme`:
  ```bash
  tailwindcss -i resources/css/filament/admin/theme.css -o public/css/filament/admin/theme.css --minify && tailwindcss -i resources/css/filament/customer/theme.css -o public/css/filament/customer/theme.css --minify
  ```

### 2.7 Developer CLI Concurrency Pipeline (`composer dev`)

Local full-stack development is orchestrated through a single unified command (`composer.json:79–82`):
```bash
composer dev
```
Powered by `npx concurrently -c "#93c5fd,#c4b5fd,#fb7185,#fdba74"`, this starts four parallel development services:
1. `php artisan serve`: Host HTTP development server on `localhost:8000`.
2. `php artisan queue:listen --tries=1`: Background job worker executing database queues.
3. `php artisan pail --timeout=0`: Real-time CLI log streaming for exception tailing.
4. `npm run dev`: Vite Hot Module Replacement (HMR) server for frontend styles and scripts.

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

Aureus ERP rejects runtime directory scanning for service providers. Instead, `bootstrap/providers.php` explicitly returns an immutable array of **34 service providers**:

```php
// File: bootstrap/providers.php
return [
    // Core Application Providers (4)
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\CustomerPanelProvider::class,
    App\Providers\Filament\PosPanelProvider::class,

    // Local Plugin Providers (30)
    Webkul\Accounting\AccountingServiceProvider::class,
    Webkul\Account\AccountServiceProvider::class,
    Webkul\Analytic\AnalyticServiceProvider::class,
    Webkul\Barcode\BarcodeServiceProvider::class,
    Webkul\Blog\BlogServiceProvider::class,
    Webkul\Chatter\ChatterServiceProvider::class,
    Webkul\Contact\ContactServiceProvider::class,
    Webkul\Crm\CRMServiceProvider::class,
    Webkul\Employee\EmployeeServiceProvider::class,
    Webkul\Field\FieldServiceProvider::class,
    Webkul\Inventory\InventoryServiceProvider::class,
    Webkul\Invoice\InvoiceServiceProvider::class,
    Webkul\Maintenance\MaintenanceServiceProvider::class,
    Webkul\Manufacturing\ManufacturingServiceProvider::class,
    Webkul\Partner\PartnerServiceProvider::class,
    Webkul\Payment\PaymentServiceProvider::class,
    Webkul\PointOfSale\PointOfSaleServiceProvider::class,
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

### 3.5 Core Foundation Services, Dynamic OKLCH Branding & Multi-Language Negotiation

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

#### Multi-Language Negotiation & RTL Pipeline (`SetLocale.php:20–44`)
Aureus ERP provides a built-in internationalization infrastructure supporting multilingual and bidirectional (LTR/RTL) enterprise environments:
1. **Canonical Supported Locales (`config/app.php:113–144`)**:
   Declares the platform's supported locales with their metadata:
   - `en`: English (flag `us`, `rtl => false`)
   - `ar`: Arabic (flag `sa`, `rtl => true`)
   - `es`: Spanish (flag `es`, `rtl => false`)
   - `pt_BR`: Portuguese Brazil (flag `br`, `rtl => false`)
   - `fr`: French (flag `fr`, `rtl => false`)
2. **Deterministic 4-Tier Resolution Sequence**:
   The `App\Http\Middleware\SetLocale` middleware evaluates incoming requests in strict priority:
   - **Tier 1 (Query Parameter)**: `$request->query('lang')`. If valid and present in `supported_locales`, updates the user's active session via `Session::put('locale', $queryLang)`.
   - **Tier 2 (Session State)**: `Session::get('locale')`. Persists user preference across subsequent stateful requests.
   - **Tier 3 (User Preference)**: `$request->user()?->language`. Retrieves persisted preference from the authenticated user profile.
   - **Tier 4 (System Fallback Chain)**: `config('app.locale')` ?? `config('app.fallback_locale')` ?? first supported locale ?? `'en'`.
3. **Custom Language Switchers & Flag Assets**:
   Mounted at `PanelsRenderHook::GLOBAL_SEARCH_END` in both `AdminPanelProvider` and `CustomerPanelProvider`, custom Alpine.js dropdown switchers (`resources/views/filament/components/language-switcher.blade.php` and `auth-language-switcher.blade.php`) render country flag SVGs (`public/flags/{code}.svg`) alongside native localized titles.
4. **Arabic Glyph & Text Formatting Engine (`khaled.alshamaa/ar-php`)**:
   Integrated via `khaled.alshamaa/ar-php: ^7.0` for Arabic morphological processing, date formatting, and right-to-left character shaping across generated PDFs and print views.

### 3.6 REST API Architecture & OpenAPI Documentation Engine

Aureus ERP provides a decoupled RESTful API infrastructure designed for headless ERP integrations, mobile applications, and third-party services:

#### 1. Endpoint Architecture & Route Topology
- **Base URI Pattern**: `/admin/api/*` (e.g. `/admin/api/v1/*`).
- **Middleware Pipeline**: Configured under the `api` middleware group, enforcing stateless request handling, rate limiting, and exception normalization.

#### 2. Bearer Token Authentication (Laravel Sanctum)
- **Token Model**: `Laravel\Sanctum\PersonalAccessToken` persisted in the `personal_access_tokens` table (`2026_01_28_134402_create_personal_access_tokens_table.php`).
- **Issuance**: Authenticated users obtain tokens via `POST /admin/api/v1/login`.
- **Request Authorization**: Subsequent requests transmit tokens via the standard header:
  ```http
  Authorization: Bearer {YOUR_AUTH_TOKEN}
  ```

#### 3. Interactive Scalar Engine & Scribe Pipeline (`config/scribe.php:13–198`)
- **Interactive Documentation UI**: Hosted at `/api/docs` using the modern `scalar` theme (`theme => 'scalar'`), providing live interactive API testing directly from the browser.
- **Specification Generation (`ScalarOpenApiGenerator`)**:
  Leverages `Webkul\Support\ScalarOpenApiGenerator` to generate:
  - OpenAPI 3.0.3 Specification: Exported at `/docs.openapi`.
  - Postman Collection: Exported at `/docs.postman` (Format v2.1.0).
- **Domain API Groups**:
  Endpoints are organized into 9 ordered functional groups:
  1. `Security`: Authentication, tokens, user permissions.
  2. `Support`: Currencies, units of measure, company context.
  3. `Partner`: Contacts, addresses, customer and vendor profiles.
  4. `Product`: Master catalog, variants, categories, price lists.
  5. `Inventory`: Warehouses, locations, stock moves, delivery orders.
  6. `Purchase`: RFQs, purchase orders, vendor agreements.
  7. `Sales`: Quotations, sales orders, order templates.
  8. `Account`: Invoices, bills, journal entries, payments, taxes.
  9. `Project`: Projects, tasks, timesheet entries.

#### 4. Normalized JSON Exception Handling (`bootstrap/app.php:30–113`)
When `$request->is('api/*') || $request->expectsJson()`, exceptions are intercepted and normalized into uniform JSON schemas:
- `ValidationException` (422): Structured `errors` mapping fields to validation error arrays.
- `AuthenticationException` (401): `{"message": "Unauthenticated."}`.
- `AuthorizationException` (403): `{"message": "This action is unauthorized."}`.
- `ModelNotFoundException` (404): `{"message": "Resource not found."}`.
- `MissingJournalException` (422): Domain-specific accounting error payloads.
- `Throwable` (500): In production, returns sanitized `{"message": "Server error occurred."}` to prevent leaking sensitive database credentials or call stacks.

---

## 4. Domain & Plugin Ecosystem Topology

### 4.1 Comprehensive Catalog of All 30 Local Plugins

Every business feature in Aureus ERP is encapsulated in `plugins/webkul/*`. Exactly 30 plugins are present:

| # | Directory | Composer Package | PSR-4 Namespace | Primary Service Provider | Core / Optional | Disk Migrations | Zero-Table? | Business Domain & Architectural Role |
|:---:|:---|:---|:---|:---|:---:|:---:|:---:|:---|
| 1 | `accounting` | `webkul/accounting` | `Webkul\Accounting\` | `Webkul\Accounting\AccountingServiceProvider` | Optional | 0 | **Yes** | Financial reporting, chart of accounts navigation, journals, reconciliations, statements. Extends `accounts`. |
| 2 | `accounts` | `webkul/accounts` | `Webkul\Account\` | `Webkul\Account\AccountServiceProvider` | Optional | 71 | No | Core double-entry ledger, Chart of Accounts, tax engines, currencies, moves, move lines, bank reconciliation. |
| 3 | `analytics` | `webkul/analytics` | `Webkul\Analytic\` | `Webkul\Analytic\AnalyticServiceProvider` | **Core** | 1 | No | Analytic distribution tags, multi-plan cost tracking (`analytic_records`). Pure backend service (no UI plugin). |
| 4 | `barcode` | `webkul/barcode` | `Webkul\Barcode\` | `Webkul\Barcode\BarcodeServiceProvider` | Optional | 0 | **Yes** | Barcode scanning UI and print generation. Extends `inventories` operations without standalone tables. |
| 5 | `blogs` | `webkul/blogs` | `Webkul\Blog\` | `Webkul\Blog\BlogServiceProvider` | Optional | 7 | No | Content publishing, blog categories, blog posts, SEO metadata for backend administration and customer portal. |
| 6 | `chatter` | `webkul/chatter` | `Webkul\Chatter\` | `Webkul\Chatter\ChatterServiceProvider` | **Core** | 4 | No | Universal activity tracking, chatter message threads, record followers, activity scheduling across models (`HasChatter`). |
| 7 | `contacts` | `webkul/contacts` | `Webkul\Contact\` | `Webkul\Contact\ContactServiceProvider` | Optional | 0 | **Yes** | CRM contacts, address books, customer/vendor categorization. Zero-table UI layer over `partners`. |
| 8 | `crm` | `webkul/crm` | `Webkul\Crm\` | `Webkul\Crm\CRMServiceProvider` | Optional | 37 | No | Lead generation, CRM opportunity Kanban pipeline, sales teams, mail aliases, lead scoring, and reveal rules. |
| 9 | `employees` | `webkul/employees` | `Webkul\Employee\` | `Webkul\Employee\EmployeeServiceProvider` | Optional | 22 | No | HR employee directory, departments, job positions, employment history, work locations, reporting hierarchy. |
| 10 | `fields` | `webkul/fields` | `Webkul\Field\` | `Webkul\Field\FieldServiceProvider` | **Core** | 1 | No | Dynamic custom fields engine (`custom_fields`). Allows adding arbitrary custom fields to any model via `HasCustomFields`. |
| 11 | `full-calendar` | `webkul/full-calendar` | `Webkul\FullCalendar\` | `Webkul\FullCalendar\FullCalendarServiceProvider` | **Core** | 0 | **Yes** | FullCalendar JS wrapper and reusable Filament calendar components/widgets across modules (maintenance, time-off). |
| 12 | `inventories` | `webkul/inventories` | `Webkul\Inventory\` | `Webkul\Inventory\InventoryServiceProvider` | Optional | 57 | No | Multi-location stock tracking, warehouse transfers, deliveries, receipts, scrap, stock valuation, adjustments. |
| 13 | `invoices` | `webkul/invoices` | `Webkul\Invoice\` | `Webkul\Invoice\InvoiceServiceProvider` | Optional | 0 | **Yes** | Dedicated invoicing UI workflows, bill run management, customer billing, vendor bills. Zero-table extension over `accounts`. |
| 14 | `maintenance` | `webkul/maintenance` | `Webkul\Maintenance\` | `Webkul\Maintenance\MaintenanceServiceProvider` | Optional | 6 | No | Equipment maintenance requests, maintenance teams, preventative and corrective maintenance logs. |
| 15 | `manufacturing` | `webkul/manufacturing` | `Webkul\Manufacturing\` | `Webkul\Manufacturing\ManufacturingServiceProvider` | Optional | 37 | No | Production control, Bills of Materials (BOM), work orders, routing, work centers, scrap, unbuild orders. |
| 16 | `partners` | `webkul/partners` | `Webkul\Partner\` | `Webkul\Partner\PartnerServiceProvider` | **Core** | 8 | No | Master partner entity (customers, suppliers, companies, individuals, addresses, payment terms). |
| 17 | `payments` | `webkul/payments` | `Webkul\Payment\` | `Webkul\Payment\PaymentServiceProvider` | Optional | 6 | No | Payment vouchers, customer receipts, vendor payments, payment registers, bank payment integration. |
| 18 | `plugin-manager` | `webkul/plugin-manager` | `Webkul\PluginManager\` | `Webkul\PluginManager\PluginManagerServiceProvider` | **Core** | 1 | No | Plugin lifecycle engine, package manifest discovery, installation, uninstallation, table rollback, Shield manager. |
| 19 | `point-of-sale` | `webkul/point-of-sale` | `Webkul\PointOfSale\` | `Webkul\PointOfSale\PointOfSaleServiceProvider` | Optional | 33 | No | Point of Sale terminal, Vue 3 Till UI, cash movements, sessions, floor/table planning, receipt printers, POS orders. |
| 20 | `products` | `webkul/products` | `Webkul\Product\` | `Webkul\Product\ProductServiceProvider` | Optional | 19 | No | Master product catalog, variants, configurable products, attributes, units of measure (UOM), price lists. |
| 21 | `projects` | `webkul/projects` | `Webkul\Project\` | `Webkul\Project\ProjectServiceProvider` | Optional | 12 | No | Project management, task tracking, kanban stages, milestones, project member assignment, privacy scoping. |
| 22 | `purchases` | `webkul/purchases` | `Webkul\Purchase\` | `Webkul\Purchase\PurchaseServiceProvider` | Optional | 19 | No | Procurement pipeline, Requests for Quotation (RFQ), Purchase Orders (PO), approval workflows, vendor price lists. |
| 23 | `recruitments` | `webkul/recruitments` | `Webkul\Recruitment\` | `Webkul\Recruitment\RecruitmentServiceProvider` | Optional | 14 | No | Recruitment pipeline, job candidates, applications, recruitment stages, interview tracking. |
| 24 | `sales` | `webkul/sales` | `Webkul\Sale\` | `Webkul\Sale\SaleServiceProvider` | Optional | 26 | No | Sales orders, quotation generation, quotation templates, price lists, sales teams, delivery invoicing triggers. |
| 25 | `security` | `webkul/security` | `Webkul\Security\` | `Webkul\Security\SecurityServiceProvider` | **Core** | 10 | No | Security foundation, Filament Shield integration, RBAC roles, permissions, Bouncer, ownership scoping. |
| 26 | `support` | `webkul/support` | `Webkul\Support\` | `Webkul\Support\SupportServiceProvider` | **Core** | 34 | No | Tenancy suite (`CompanyScope`, `CompanyContext`, `BelongsToCompany`), navigation enums, shared traits. |
| 27 | `table-views` | `webkul/table-views` | `Webkul\TableViews\` | `Webkul\TableViews\TableViewsServiceProvider` | **Core** | 2 | No | Custom table view presets, favorite views, column visibility controls via Filament render hooks. Core infrastructure. |
| 28 | `time-off` | `webkul/time-off` | `Webkul\TimeOff\` | `Webkul\TimeOff\TimeOffServiceProvider` | Optional | 10 | No | Employee leave management, time off requests, allocations, leave types, multi-level approvals, public holidays. |
| 29 | `timesheets` | `webkul/timesheets` | `Webkul\Timesheet\` | `Webkul\Timesheet\TimesheetServiceProvider` | Optional | 0 | **Yes** | Employee project timesheets, billable hour logging, task time tracking. Zero-table extension over `projects`. |
| 30 | `website` | `webkul/website` | `Webkul\Website\` | `Webkul\Website\WebsiteServiceProvider` | Optional | 6 | No | CMS website pages, public portal layout, customer authentication (login, registration, reset), theme engine. |

#### Architectural Plugin Archetypes
- **9 Core Infrastructure Packages (`$package->isCore()`)**: `analytics`, `chatter`, `fields`, `full-calendar`, `partners`, `plugin-manager`, `security`, `support`, `table-views`. Loaded unconditionally. They execute migrations and register routes without database installation checks.
- **21 Optional Domain Packages**: Gated by runtime installation (`Package::isPluginInstalled()`). Their UI, routes, and console migrations remain dormant until explicitly installed.
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

#### 1. Schema Registries & Contribution Architecture (`AbstractSchemaRegistry`)
Downstream plugins contribute form fields, infolist entries, table columns, and header actions to upstream master resources without modifying upstream code:
- Base implementation: `Webkul\Support\Filament\Contributions\AbstractSchemaRegistry` (`support/src/Filament/Contributions/AbstractSchemaRegistry.php:7–110`).
- Registries: `PartnerSchemaRegistry`, `ProductSchemaRegistry`.
- **7 Distinct Contribution Slots**:
  1. `form($slot, Closure $factory)`: Injects form schemas into tabs/sections (e.g. `accounts` contributing tax fields via `ProductSchemaRegistry::form('right.pricing.fields', fn () => AccountProductSchema::taxFields())`).
  2. `infolist($slot, Closure $factory)`: Injects read-only schema entries into infolist views.
  3. `table($slot, Closure $factory)`: Injects columns and filters (e.g. `inventories` contributing stock on-hand columns).
  4. `actions($slot, Closure $factory)`: Injects header/row actions (e.g. `inventories` contributing `UpdateQuantityAction`).
  5. `presetView($key, $factory, $priority)`: Injects tab presets into list pages (`ListProducts.php:54`).
  6. `eagerLoad(array $relations)`: Dynamically appends eager-loaded relations to prevent N+1 queries when contributed columns render (`ProductResource.php:115`, `PartnerResource.php:95`).
  7. `companyDefaultFields()` & `companyDependentFields()`: Multi-company property field handling (`AccountServiceProvider.php:291–302`).
- **Cross-Plugin Contributor (`PortalContributions`)**: `Webkul\Website\PortalContributions` (`plugins/webkul/website/src/PortalContributions.php:18–89`) injects portal access badges, QueryBuilder constraints, and header action groups into `PartnerSchemaRegistry` without `partners` possessing knowledge of `website`.
- **Orphaned Class Reference**: `Webkul\Support\Services\SchemaRegistry` (`plugins/webkul/support/src/Services/SchemaRegistry.php`) is a 270-line legacy class with zero references across the repository, superseded by `Webkul\Support\Filament\Contributions\SchemaRegistry`.
- **Settings Registry Singleton**: `Webkul\Support\SettingsRegistry` (`plugins/webkul/support/src/SettingsRegistry.php:5–16`) is the container singleton registered in `SupportServiceProvider.php:146` backing the global `settings()` helper.

#### 2. Dynamic Eloquent Relationships via `resolveRelationUsing`
Downstream packages dynamically bind Eloquent relationships to upstream models during `packageBooted()`. The platform maintains exactly 22 dynamic relationship injections:

| # | Provider | Target Model | Relationship Name | Relation Type | Target Related Class | Source Citation |
|---|---|---|---|---|---|---|
| 1 | `AccountServiceProvider` | `Partner` | `CompanyProperty::RELATION` | `hasMany` | `PartnerCompanyProperty` | `AccountServiceProvider.php:264` |
| 2 | `AccountServiceProvider` | `Partner` | `propertyAccountPayable` | `belongsTo` | `Account` | `AccountServiceProvider.php:271` |
| 3 | `AccountServiceProvider` | `Partner` | `propertyAccountReceivable` | `belongsTo` | `Account` | `AccountServiceProvider.php:272` |
| 4 | `AccountServiceProvider` | `Partner` | `propertyAccountPosition` | `belongsTo` | `FiscalPosition` | `AccountServiceProvider.php:273` |
| 5 | `AccountServiceProvider` | `Partner` | `propertyPaymentTerm` | `belongsTo` | `PaymentTerm` | `AccountServiceProvider.php:274` |
| 6 | `AccountServiceProvider` | `Partner` | `propertySupplierPaymentTerm` | `belongsTo` | `PaymentTerm` | `AccountServiceProvider.php:275` |
| 7 | `AccountServiceProvider` | `Partner` | `propertyOutboundPaymentMethodLine` | `belongsTo` | `PaymentMethodLine` | `AccountServiceProvider.php:276` |
| 8 | `AccountServiceProvider` | `Partner` | `propertyInboundPaymentMethodLine` | `belongsTo` | `PaymentMethodLine` | `AccountServiceProvider.php:277` |
| 9 | `AccountServiceProvider` | `Product` | `productTaxes` | `belongsToMany` | `Tax` | `AccountServiceProvider.php:322` |
| 10 | `AccountServiceProvider` | `Product` | `supplierTaxes` | `belongsToMany` | `Tax` | `AccountServiceProvider.php:329` |
| 11 | `AccountServiceProvider` | `Product` | `CompanyProperty::RELATION` | `hasMany` | `ProductCompanyAccount` | `AccountServiceProvider.php:341` |
| 12 | `AccountServiceProvider` | `Category` | `CompanyProperty::RELATION` | `hasMany` | `CategoryCompanyAccount` | `AccountServiceProvider.php:352` |
| 13 | `AccountServiceProvider` | `Product` | `propertyAccountIncome` | `belongsTo` | `Account` | `AccountServiceProvider.php:357` |
| 14 | `AccountServiceProvider` | `Product` | `propertyAccountExpense` | `belongsTo` | `Account` | `AccountServiceProvider.php:370` |
| 15 | `InventoryServiceProvider` | `Product` | `routes` | `belongsToMany` | `Route` | `InventoryServiceProvider.php:255` |
| 16 | `InventoryServiceProvider` | `Product` | `responsible` | `belongsTo` | `User` | `InventoryServiceProvider.php:262` |
| 17 | `InventoryServiceProvider` | `Product` | `moveLines` | `hasMany` | `MoveLine` | `InventoryServiceProvider.php:267` |
| 18 | `InventoryServiceProvider` | `Product` | `moves` | `hasMany` | `Move` | `InventoryServiceProvider.php:272` |
| 19 | `InventoryServiceProvider` | `Product` | `quantities` | `hasMany` | `ProductQuantity` | `InventoryServiceProvider.php:277` |
| 20 | `ManufacturingServiceProvider` | `Product` | `billsOfMaterials` | `hasMany` | `BillOfMaterial` | `ManufacturingServiceProvider.php:272` |
| 21 | `ManufacturingServiceProvider` | `Product` | `billOfMaterialLines` | `hasMany` | `BillOfMaterialLine` | `ManufacturingServiceProvider.php:277` |
| 22 | `PurchaseServiceProvider` | `Product` | `sellers` | `hasMany` | `ProductSupplier` | `PurchaseServiceProvider.php:131` |

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

### 4.7 Domain Facades & Service Manager Architecture

Aureus ERP abstracts complex business logic away from Filament UI pages into dedicated Service Managers exposed via static Facades:

```
┌────────────────────────────────────────────────────────────────────────┐
│                        Domain Service Layer                            │
├───────────────┬────────────────────────────┬───────────────────────────┤
│ Domain Facade │ Service Manager Singleton  │ Core Injected Sub-Services│
├───────────────┼────────────────────────────┼───────────────────────────┤
│ SaleOrder     │ Webkul\Sale\SaleManager    │ OrderWorkflow, Calculator,│
│ (accessor:    │                            │ Invoicer, Mailer,         │
│  'sale')      │                            │ ProcurementRequester      │
├───────────────┼────────────────────────────┼───────────────────────────┤
│ PurchaseOrder │ Webkul\Purchase\           │ OrderWorkflow, Calculator,│
│ (accessor:    │   PurchaseOrder            │ ReceiptPlanner, Biller,   │
│'purchase_order│                            │ DocumentGenerator         │
├───────────────┼────────────────────────────┼───────────────────────────┤
│ Inventory     │ Webkul\Inventory\          │ TransferWorkflow, Reserver│
│ (accessor:    │   InventoryManager         │ MoveConfirmer, Completer, │
│  'inventory') │                            │ ProcurementRunner, Resolver│
├───────────────┼────────────────────────────┼───────────────────────────┤
│ Account       │ Webkul\Account\            │ MoveWorkflow, Calculator, │
│ (accessor:    │   AccountManager           │ DynamicLineSynchronizer,  │
│  'account')   │                            │ Reconciler, PaymentRegistr│
├───────────────┼────────────────────────────┼───────────────────────────┤
│ Tax           │ Webkul\Account\TaxManager  │ TaxLineFactory, Computer, │
│ (accessor:    │                            │ TaxDetailRounder, Mapper  │
│  'tax')       │                            │                           │
├───────────────┼────────────────────────────┼───────────────────────────┤
│ Manufacturing │ Webkul\Manufacturing\      │ OrderWorkflow, Expander,  │
│ (accessor:    │   ManufacturingManager     │ ProductionRecorder        │
│'manufacturing'│                            │                           │
└───────────────┴────────────────────────────┴───────────────────────────┘
```

#### Key Capabilities & Cross-Module Invocations:
- **`SaleOrder::createInvoice($record, $data)`**: Called by `CreateInvoiceAction:82` in `sales`, orchestrating line item conversion, account move creation via `Account` facade, and tax computations via `Tax` manager.
- **`PurchaseOrder::createPurchaseOrderBill($record)`**: Called by `CreateBillAction:57` in `purchases`, synchronizing received quantities with vendor bills.
- **`Inventory::runProcurements($requests)`**: Injected into `ProcurementRequester:50` in `sales` to evaluate warehouse replenishment rules and schedule delivery orders upon sales quotation confirmation.
- **`Account::computeAccountMove($accountMove)`**: Injected into `Invoicer:59` and `Biller:33`, recalculating debits, credits, dynamic tax lines, and currency exchanges before ledger posting.

### 4.8 Headless Engine & Composable UI Architecture

Aureus ERP enforces a strict architectural separation between **transactional engines** and **presentation layers**:

#### 1. Headless Engine Pattern (`plugins/webkul/accounts`)
The `accounts` package encapsulates all financial ledger models, journal moves, reconciliation services, and tax calculators. However, it intentionally suppresses all Filament navigation:
```php
// Declared on all 17 Resource classes in accounts:
protected static bool $shouldRegisterNavigation = false;
```
Resources like `InvoiceResource`, `BillResource`, `PaymentResource`, and `AccountResource` define complete forms, tables, and infolists, but are never directly exposed in the top navigation or sidebar by `accounts`.

#### 2. Composable UI Layers (`invoices` and `accounting`)
Presentation and user journeys are composed through specialized consumer plugins:
- **`plugins/webkul/invoices`**: Reuses `accounts` resources to provide a lightweight, streamlined invoicing workflow under `NavigationGroup::Invoice`, tailored for sales and billing teams without general ledger overhead.
- **`plugins/webkul/accounting`**: Provides a complete double-entry general ledger navigation suite (`Accounting`, `Customers`, `Vendors`, `Configuration`, `Reporting`, `PluginSettings`) under `NavigationGroup::Accounting`, adding journal entry management, bank reconciliation widgets, and financial reports.

#### 3. Zero-Table Model Subclassing
Zero-table packages extend domain entities through clean object-oriented inheritance without schema overhead:
- **`contacts`**: `Webkul\Contact\Models\Partner extends Webkul\Partner\Models\Partner`
- **`timesheets`**: `Webkul\Timesheet\Models\Timesheet extends Webkul\Project\Models\Timesheet`
This allows packages to inject domain-specific scopes, model labels, and event listeners while sharing identical database tables.

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

### 5.4 Referential Tenancy Integrity & Interactive Form Isolation Guards

Cross-company relational contamination (e.g., assigning a warehouse from Company B to a sales order in Company A) is strictly prevented across five integrated layers:

```
┌────────────────────────────────────────────────────────────────────────┐
│                      Cross-Company Guard Layers                        │
├──────────────────────────────────┬─────────────────────────────────────┤
│ 1. Interactive Form Scoping      │ owned_by_company(), hide_deleted()  │
│ 2. Pre-Emptive State Cleansing   │ clear_foreign_company_values()      │
│ 3. Settings Reapplication        │ reapply_company_defaults()          │
│ 4. Eloquent Consistency Hook     │ ChecksCompanyConsistency trait      │
│ 5. Deep Relational Assertion     │ CompanyConsistencyGuard::detect()   │
└──────────────────────────────────┴─────────────────────────────────────┘
```

#### 1. Interactive Tenancy Isolation Suite (`plugins/webkul/support/src/helpers.php`)
While backend Eloquent scopes protect database queries, interactive Filament form editing requires specialized form query scoping and reactive state cleansing:
- **`owned_by_company($companyId = null): Closure` (`helpers.php:357`)**:
  Generates a query closure filtering records to `$companyId ?? current_company_id()`. Reused across **over 50 relationship select inputs** platform-wide:
  ```php
  Select::make('partner_id')
      ->relationship('partner', 'name', modifyQueryUsing: fn (Builder $query, Get $get) => $query->where(owned_by_company($get('company_id'))))
  ```
  This guarantees that dropdown choices in relational forms never display records belonging to other tenants.
- **`clear_foreign_company_values($set, $get, array $fields, $companyId = null): void` (`helpers.php:426`)**:
  Attached to `afterStateUpdated()` on company select inputs. When a user switches company assignment on a draft record, this helper immediately purges selected foreign keys if the referenced records belong to the previous company, preventing cross-tenant foreign key persistence.
- **`reapply_company_defaults($component, array $fields): void` (`helpers.php:403`)**:
  Attached to company select inputs; dynamically inspects and restores configured tenant settings (default currency, payment terms, tax groups) for the freshly selected company.
- **`hide_deleted_unless_selected($state = null): Closure` (`helpers.php:380`)**:
  Query modifier for select inputs:
  ```php
  $query->withTrashed()->where(hide_deleted_unless_selected($state))
  ```
  Ensures that existing soft-deleted records remain visible on historical orders and invoices without leaking inactive or deleted options into dropdowns for new selections.

#### 2. `Webkul\Support\Traits\ChecksCompanyConsistency`
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

#### 3. `Webkul\Support\Support\CompanyConsistencyGuard`
Queries referenced foreign models using `withoutGlobalScopes()`, asserts that referenced foreign records belong to the target model's `company_id`, and throws `CrossCompanyException::forRecords($conflicts)` if boundary violations are detected.

#### 4. Warehouse Cross-Company Transfer Guard
In `plugins/webkul/inventories`, inter-company stock movement is guarded by `Webkul\Inventory\Models\Concerns\ChecksCrossCompanyTransfer` on `Operation` and `Scrap` models:
- Intercepts `creating` and `updating` events.
- Calls `CrossCompanyTransferGuard::assert($this->source_location_id, $this->destination_location_id)`.
- Forbids transferring stock directly between locations owned by different companies unless routed through configured transit inter-company locations.

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

### 7.1 Multi-Panel Topography & Runtime Configuration

Aureus ERP configures three distinct Filament panels:

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                                       HTTP Request                                     │
└───────────────────────────────────────────┬────────────────────────────────────────────┘
                                            │
                  ┌─────────────────────────┼─────────────────────────┐
                  ▼                         ▼                         ▼
          Path: `/admin/*`              Path: `/*`               Path: `/pos/*`
   ┌─────────────────────────────┐ ┌─────────────────────────┐ ┌─────────────────────────┐
   │      Admin Panel            │ │  Customer Portal Panel  │ │     POS Till Panel      │
   │   (AdminPanelProvider)      │ │ (CustomerPanelProvider) │ │   (PosPanelProvider)    │
   ├─────────────────────────────┤ ├─────────────────────────┤ ├─────────────────────────┤
   │ • id: 'admin' (default)     │ │ • id: 'customer'        │ │ • id: 'pos'             │
   │ • path: 'admin'             │ │ • path: '/'             │ │ • path: 'pos'           │
   │ • guard: 'web'              │ │ • guard: 'customer'     │ │ • guard: 'web'          │
   │ • Top navigation            │ │ • Top navigation        │ │ • Fullscreen (No topbar)│
   │ • maxContentWidth(Full)     │ │ • darkMode(false)       │ │ • Vue 3 Till UI runtime │
   │ • Shield & Translatable     │ │ • Self-registration     │ │ • PWA Install Support   │
   │ • Global Search Provider    │ │ • Customer portal pages │ │ • Cash movement / Orders│
   │ • 30s Database Notifications│ │ • Public brand headers  │ │ • MaxContentWidth(Full) │
   └──────────────┬──────────────┘ └────────────┬────────────┘ └────────────┬────────────┘
                  │                             │                           │
                  └─────────────────────────────┼───────────────────────────┘
                                                ▼
                   ┌────────────────────────────────────────────────────────┐
                   │   Dynamically Injected via `Panel::configureUsing()`   │
                   │   from 30 Plugin Service Providers                     │
                   └────────────────────────────────────────────────────────┘
```

#### Runtime Lifeline & Form Unsaved Changes Guard (`AdminPanelProvider.php:36, 52`)
1. **Request Timeout Extension (`set_time_limit(300)`)**:
   In `AdminPanelProvider::panel()`, the execution timeout is extended to 5 minutes (`set_time_limit(300)`). This prevents timeouts during initial plugin discovery, heavy multi-warehouse stock valuation reports, and large batch PDF generations.
2. **Form Navigation Guard (`->unsavedChangesAlerts()`)**:
   Activates browser-level navigation guards across all administrative forms. If a user modifies form inputs and attempts to navigate away without committing changes, the browser presents a confirmation alert to prevent accidental data loss.

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
- **Admin Panel Only (22 plugins)**: `accounting`, `accounts`, `barcode`, `contacts`, `crm`, `employees`, `fields`, `inventories`, `invoices`, `maintenance`, `manufacturing`, `partners`, `payments`, `plugin-manager`, `products`, `projects`, `recruitments`, `sales`, `security`, `support`, `time-off`, `timesheets`.
- **Dual / Multi-Panel (4 plugins)**:
  - `point-of-sale`: Configurations, registers, orders, and sessions in `admin` panel; dedicated fullscreen Vue 3 till terminal in `pos` panel.
  - `website`: Login, registration, password reset, account clusters, public CMS pages, topbar links, and footer render hooks.
  - `blogs`: Backend editor in admin panel; customer-facing reading UI in customer panel.
  - `purchases`: Admin procurement clusters; customer/vendor quotation and order portal in customer panel.
- **Universal (2 plugins)**: `chatter`, `full-calendar` (embeddable widgets across both panels).
- **Non-Filament Plugins (2 plugins)**: `analytics` (pure backend service), `table-views` (Filament render hooks only).

### 7.4 Central Navigation Hierarchy & Domain SVG Iconography

#### Navigation Groups Enum (`Webkul\Support\Enums\NavigationGroup`)
Centralizes all navigation groups to avoid hardcoded string literals:
- File: `plugins/webkul/support/src/Enums/NavigationGroup.php`.
- Cases: `Dashboard`, `Contact`, `Crm`, `Sale`, `Purchase`, `Maintenance`, `Manufacturing`, `Inventory`, `Invoice`, `Accounting`, `Project`, `Employee`, `TimeOff`, `Recruitment`, `Website`, `PointOfSale`, `Barcode`, `Plugin`, `Setting`, `Help`.
- Maps custom SVG icons (`icon-sales`, `icon-inventories`, etc.) and localized labels (`__('admin.navigation.' . $this->value)`).

#### Blade Icon Resolution & Domain SVG Catalog (`config/blade-icons.php`)
`config/blade-icons.php:33, 59` registers the default icon set mapped directly to `resources/svg` under the `'icon'` prefix. This enables referencing custom domain icons anywhere via `icon-<name>` without external dependencies. The catalog includes 23 custom SVG assets:
`accounting.svg`, `barcode.svg`, `contacts.svg`, `crm.svg`, `dashboard.svg`, `employees.svg`, `help.svg`, `inventories.svg`, `invoices.svg`, `maintenance.svg`, `manufacturing.svg`, `menu.svg`, `pin.svg`, `plugin.svg`, `point-of-sale.svg`, `projects.svg`, `purchases.svg`, `recruitments.svg`, `sales.svg`, `settings.svg`, `time-offs.svg`, `un-pin.svg`, and `website.svg`.

#### Cluster Breadcrumbs (`HasClusterBreadcrumbs`)
Complex domains organize resources inside Filament Clusters (e.g. `inventories` clusters `Operations`, `Configurations`, `Products`, `Reporting`, `Settings`). The trait `Webkul\Support\Traits\HasClusterBreadcrumbs` automatically generates breadcrumb hierarchies navigating from Group -> Cluster -> Resource -> Record.

### 7.5 Modular Navigation Topology: App Launcher & Dynamic Module Scoping

To prevent visual fatigue and clutter across 30 modular packages, Aureus ERP replaces Filament's default monolithic vertical navigation with a two-tier modular navigation topology:

1. **Topbar App Launcher Dropdown (`resources/views/vendor/filament-panels/livewire/topbar.blade.php:43–85`)**:
   In the admin panel header, an `icon-menu` button opens a 3-column dropdown modal displaying all installed module groups (`filament()->getNavigation()`). Each group is presented with a 64x64 domain SVG icon, localized label, and direct entry link.
2. **Active-Module-Scoped Sidebar (`resources/views/vendor/filament-panels/livewire/sidebar.blade.php:60–63`)**:
   The admin sidebar dynamically checks the active module group:
   ```blade
   if ($isAdminPanel && ! $isGroupActive) {
       continue;
   }
   ```
   Inactive navigation groups are filtered out. The sidebar displays only the resources, pages, and clusters belonging to the currently active module, providing an intuitive, focused workspace.

### 7.6 Tabular Line Items Architecture: Reusable Repeater, Column Manager & Summarizers

Transactional documents in ERP (quotations, purchase orders, invoices, bills, journal entries, BOMs, stock transfers) require tabular line-item entry with column management and financial summarizers:

#### 1. Tabular Form Repeater (`Webkul\Support\Filament\Forms\Components\Repeater`)
- **Source**: `plugins/webkul/support/src/Filament/Forms/Components/Repeater.php:13`
- Extends `Filament\Forms\Components\Repeater`, incorporates `HasColumnManager`.
- Renders `support::filament.forms.components.repeater.table`.
- **Public API**:
  - `table(array|Closure|null $columns): static`: Transforms repeater items into a responsive spreadsheet table.
  - `footerActions(array $actions): static`: Injects action buttons below the table grid.
  - `applyTableColumnManager(?array $columns): void`: Stores per-user column visibility and ordering in session (`repeater_{statePath}_column_manager`).
  - `getSummaryForColumn(string $columnName): ?string`: Evaluates summarizers in real time.

#### 2. Reusable Column Definition (`TableColumn`)
- **Source**: `plugins/webkul/support/src/Filament/Forms/Components/Repeater/TableColumn.php:18`
- Supports `resizable()`, `summarize()`, `hiddenHeaderLabel()`, `markAsRequired()`, and responsive width constraints.

#### 3. Read-Only Infolist RepeatableEntry (`RepeatableEntry`)
- **Source**: `plugins/webkul/support/src/Filament/Infolists/Components/RepeatableEntry.php:13`
- Renders read-only tabular line items in infolists (`support::filament.infolists.components.repeatable-entry.table`) with session column management (`repeatable_entry_{statePath}_column_manager`).

#### 4. Livewire Page Concerns
- `Webkul\Support\Filament\Concerns\HasRepeaterColumnManager`: Bridges Livewire actions (`applyRepeaterColumnManager`, `resetRepeaterColumnManager`) on `CreateRecord` and `EditRecord` pages.
- `Webkul\Support\Filament\Concerns\HasRepeatableEntryColumnManager`: Bridges Livewire column actions on `ViewRecord` pages.

#### 5. Custom Summarizer Subsystem
- **Base Class**: `Webkul\Support\Filament\Summarizers\Summarizer` (`plugins/webkul/support/src/Filament/Summarizers/Summarizer.php:12`)
- **Concrete Implementations**:
  - `Sum`: Calculates column totals (e.g. debit/credit totals in journal entries).
  - `Average`: Computes mathematical means across lines.
  - `Count`: Tracks line item counts.
  - `Range`: Calculates minimum and maximum spans.

### 7.7 Universal Workflow State Pipeline: ProgressStepper

Workflow status visualization is standardized platform-wide via `ProgressStepper`:
- **Form Stepper**: `Webkul\Field\Filament\Forms\Components\ProgressStepper` (extends `ToggleButtons`, view `fields::filament.forms.components.progress-stepper.index`).
- **Infolist Stepper**: `Webkul\Field\Filament\Infolists\Components\ProgressStepper` (extends `Entry`, view `fields::filament.infolists.components.progress-stepper`).
- **Visual Behavior**: Evaluates backed enums and renders an interconnected chevron/ribbon pipeline with highlighted active stages and muted inactive stages.
- **Ubiquitous Adoption**: Standardized across 8+ operational domains (`sales`, `purchases`, `accounts`, `accounting`, `inventories`, `manufacturing`, `projects`, `recruitments`, `time-off`, `maintenance`).

#### Dynamic Custom Field Infolist Component: `CustomEntries`
- **Class**: `Webkul\Field\Filament\Infolists\Components\CustomEntries` (`plugins/webkul/fields/src/Filament/Infolists/Components/CustomEntries.php:15`)
- **Hierarchy**: Extends `Filament\Schemas\Components\Component`.
- **API**: `make(string $resource): static`, `include(array $fields): static`, `exclude(array $fields): static`, `getSchema(): array`.
- **Behavior**: Dynamically generates Filament Infolist entries (`ColorEntry`, `IconEntry`, `TextEntry`) from custom fields configured on the target resource.
- **Consumption**: Consumed via `HasCustomFields::getCustomInfolistEntries()` (`plugins/webkul/fields/src/Filament/Traits/HasCustomFields.php:71`) across resource view and infolist pages.

### 7.8 Record Sub-Navigation: RecordNavigationTabs & HasRecordNavigationTabs

- **Widget**: `Webkul\Support\Filament\Widgets\RecordNavigationTabs` (`plugins/webkul/support/src/Filament/Widgets/RecordNavigationTabs.php:8`, view `support::filament.widgets.record-navigation-tabs`).
- **Trait**: `Webkul\Support\Traits\HasRecordNavigationTabs` (`plugins/webkul/support/src/Traits/HasRecordNavigationTabs.php:8`).
- **Behavior**: Converts Filament's `getRecordSubNavigation()` into a horizontal topbar navigation tab strip across Edit, View, and Manage pages, automatically integrating clustered components. Reused on over 100 pages across the monorepo.

### 7.9 Collaboration & Activity Stream: Chatter Slide-Over & HasChatter

Audit trails, follower subscriptions, and scheduled activities are powered by `plugins/webkul/chatter`:
- **Slide-Over Action**: `Webkul\Chatter\Filament\Actions\ChatterAction` mounted in `getHeaderActions()` across `EditRecord` and `ViewRecord` pages for 18 primary domain entities.
- **Reactive Panel**: `Webkul\Chatter\Livewire\ChatterPanel` embedded in page footers via `ChatterWidget`.
- **Model Traits**:
  - `HasChatter`: Automatically subscribes record creators, synchronizes assigned owners, and manages message threads.
  - `HasLogActivity`: Automatically logs create, update, and delete events into the chatter stream.
- **Table Activity Action**: `Webkul\Chatter\Filament\Actions\ActivityTableAction` (`heroicon-o-clock`) embedded across 25+ domain tables in 11 plugins to schedule activities directly from list views.

### 7.10 Reusable Resource Concerns: Navigation Labels, Form State Refresh & PDF Generation

1. **`HasNavigationLabelTitles` (`plugins/webkul/support/src/Filament/Concerns/HasNavigationLabelTitles.php:19`)**:
   Overrides `getTitleCasePluralModelLabel()` to return `getNavigationLabel()`. Reused across **over 90 Resource classes** to resolve Arabic RTL definite article prefixing ('Al-') across page titles, breadcrumbs, and tabs without corrupting singular/plural model labels.
2. **`RefreshesRecordState` (`plugins/webkul/support/src/Traits/RefreshesRecordState.php:5`)**:
   Attached to over 30 `EditRecord` pages, executing `$this->record->refresh()`, `$this->record->unsetRelations()`, `$this->fillForm()`, and `$this->rememberData()` after save to prevent Livewire hydration desynchronization.
3. **`HandlesCrossCompanyException` (`plugins/webkul/support/src/Filament/Concerns/HandlesCrossCompanyException.php:8`)**:
   Wraps page `create()` and `save()` operations, catching `CrossCompanyException` and emitting Filament danger notifications instead of crashing.
4. **`PDFHandler` (`plugins/webkul/support/src/Traits/PDFHandler.php:11`)**:
   Encapsulates DomPDF rendering, UTF-8 normalization, Arabic RTL text shaping (`prepare_rtl_html`), storage, and downloads.
5. **`HasTableViews` (`plugins/webkul/table-views/src/Filament/Concerns/HasTableViews.php:20`)**:
   Provides preset views, custom favorites, column customization, and URL parameter filtering across 45+ list pages.
6. **`HasFilamentDefaults` (`plugins/webkul/support/src/Traits/HasFilamentDefaults.php:14`)**:
   Configures global Filament schema defaults: enforces `columnSpanFull()` on `Fieldset`, `Grid`, and `Section` components; sets default currency on `Table` and `Schema` via `default_currency_code()`. Registers the `PanelsRenderHook::USER_MENU_PROFILE_BEFORE` ERP version badge in the user menu. Consumed directly in `SupportServiceProvider.php:32`.
7. **`HasRouterMacros` (`plugins/webkul/support/src/Traits/HasRouterMacros.php:7`)**:
   Injects the `softDeletableApiResource` router macro on Laravel's `Router`, registering `{id}/restore` and `{id}/force` endpoints for nested REST API routes. Consumed directly in `SupportServiceProvider.php:33`.
8. **`ProgressBarEntry` Infolist Component (`plugins/webkul/support/src/Filament/Tables/Infolists/ProgressBarEntry.php:7`)**:
   Infolist entry sister component to `Webkul\Support\Filament\Tables\Columns\ProgressBarEntry`, rendering visual progress bars within record infolists (`support::tables.infolists.progress-bar-entry`) alongside dynamic custom field entries (`CustomEntries`, documented in §7.7).

---

## 8. Persistence & Database Conventions

### 8.1 Schema Architecture Across 383 Migrations

Aureus ERP contains **456 database migrations**:
- Core application: `database/migrations/` (13 migrations).
- Local plugins: `plugins/webkul/*/database/migrations/` (443 migrations across 24 plugins).
Plugin migrations are loaded explicitly via `$package->hasMigrations([...])` and gated by installation state.

#### Core Infrastructure Migrations & System Table Inventory
The 13 root database migrations in `database/migrations/` provision the foundational identity, asynchronous queue, caching, and background processing substrate:

| Migration File | Persisted Database Tables | Subsystem & Schema Responsibility |
|---|---|---|
| `0001_01_01_000000_create_users_table.php` | `users`, `password_reset_tokens`, `sessions` | Base user identity, password resets, and database HTTP session storage. |
| `0001_01_01_000001_create_cache_table.php` | `cache`, `cache_locks` | Centralized database cache key-value store and atomic mutex locks. |
| `0001_01_01_000002_create_jobs_table.php` | `jobs`, `job_batches`, `failed_jobs` | Database queue workers, batch coordination, and dead-letter failure logging. |
| `2022_12_14_083707_create_settings_table.php` | `settings` | Spatie settings persistence store for application and module settings. |
| `2024_11_04_132945_create_permission_tables.php` | `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` | Spatie RBAC tables powering Filament Shield roles and permissions. |
| `2024_11_26_053234_add_resource_permission_column_to_users_table.php` | `users` (`resource_permission`) | Adds `resource_permission` column for fine-grained Bouncer ownership scoping. |
| `2026_01_14_150817_create_imports_table.php` | `imports` | Filament asynchronous CSV/Excel import job tracking and progress metrics. |
| `2026_01_14_150818_create_exports_table.php` | `exports` | Filament asynchronous data export job tracking and download links. |
| `2026_01_14_150819_create_failed_import_rows_table.php` | `failed_import_rows` | Stores validation failures and rejected row payloads during data imports. |
| `2026_01_14_151113_create_notifications_table.php` | `notifications` | Database notification storage for administrative user notifications. |
| `2026_01_28_134402_create_personal_access_tokens_table.php` | `personal_access_tokens` | Laravel Sanctum personal access tokens for REST API authentication. |
| `2026_07_07_185249_alter_notifications_table_data_column_to_json.php` | `notifications` (`data`) | Converts `data` column to native JSON for high-performance notification querying. |
| `2026_07_21_100000_add_company_id_to_settings_table.php` | `settings` (`company_id`, index) | Multi-company settings partitioning with composite unique index `['group', 'name', 'company_id']`. |

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

### 8.5 Root Database Seeders & Baseline Data Provisioning

Database seeding initializes system security, currency standards, and plugin records:

1. **`DatabaseSeeder` (`database/seeders/DatabaseSeeder.php:17–21`)**:
   Coordinates foundational provisioning by sequentially delegating to:
   - `Webkul\Security\Database\Seeders\DatabaseSeeder`: Provisions roles and default super-admin user.
   - `Webkul\Support\Database\Seeders\DatabaseSeeder`: Provisions currencies and measurement units.
   - `Webkul\PluginManager\Database\Seeders\DatabaseSeeder`: Provisions plugin records in the `plugins` catalog.
2. **`CurrencySeeder` (`database/seeders/CurrencySeeder.php:15–71`)**:
   Populates 41 standard ISO currencies into `Webkul\Support\Models\Currency` (`USD`, `EUR`, `GBP`, `CAD`, `AUD`, `JPY`, `SAR`, `AED`, `INR`, etc.) with ISO codes, symbols, precision rules, and active flags.
3. **`ShieldSeeder` (`database/seeders/ShieldSeeder.php:11–68`)**:
   - Flushes Spatie permission caches via `PermissionRegistrar::forgetCachedPermissions()`.
   - Provisions the baseline `panel_user` role.
   - Seeds 55 direct permissions covering identity, user teams, and dynamic custom fields.

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

### 9.2 Dual-Tier Testing Architecture: Pest Feature Testing & Playwright Browser Automation

Aureus ERP enforces verification across two complementary testing tiers:

#### Tier 1: Pest PHP Backend & Feature Testing (`TestBootstrapHelper`)
Automated tests extend `Tests\TestCase` and utilize `DatabaseTransactions` (`tests/Pest.php`).
- Before test execution, `TestCase::setUp()` invokes `\TestBootstrapHelper::ensureERPInstalled()`.
- `TestBootstrapHelper` provisions a clean environment:
  - Runs `migrate:fresh --force`.
  - Executes `erp:install --force --no-interaction --admin-name='Test Admin' --admin-email='admin@example.com' --admin-password='admin123'`.
  - Uses an isolated database connection (`test_bootstrap`) to seed plugins without colliding with transactional rollbacks.
  - Gating preserves tenancy scopes during unit testing (`runningUnitTests() === true`).

#### Tier 2: Playwright TypeScript Browser Automation (`tests/e2e-pw/`)
End-to-end browser workflows are validated using Playwright TypeScript:
- **Configuration (`tests/e2e-pw/playwright.config.ts:1–62`)**:
  - Persistent authentication state caching via `.state/admin-auth.json`, eliminating redundant logins across test specs.
  - Automatic screenshot, video, and trace capture upon test failures.
  - Configurable timeouts (30s action, 60s test navigation).
- **Reusable Abstractions**:
  - Locator repository (`tests/e2e-pw/locator/erp_locator.ts`): Centralizes Filament and ERP DOM selectors.
  - Administrative helpers (`tests/e2e-pw/utils/admin.ts`): Standardized login, company switching, and record actions.
- **7 End-to-End Operational Suites (`tests/e2e-pw/tests/`)**:
  - `01_plugins.spec.ts`: Validates dynamic plugin discovery, installation, and uninstallation rollback.
  - `02_companies.spec.ts`: Validates multi-company switching and tenant isolation.
  - `03_users.spec.ts`: Validates user creation, role assignment, and Bouncer permission boundaries.
  - `04_sales.spec.ts`: Validates end-to-end sales quotation, confirmation, and invoice creation.
  - `05_purchases.spec.ts`: Validates RFQ, purchase order approval, receipt planning, and vendor bill creation.
  - `05_inventories.spec.ts`: Validates warehouse transfers, deliveries, receipts, and stock adjustments.
  - `06_website.spec.ts`: Validates customer portal login, registration, and quotation approval.

### 9.3 New Plugin Authoring & Extension Standards

When authoring a new plugin under `plugins/webkul/<plugin>`:
1. **Manifest**: Define `composer.json` with PSR-4 autoload mapping and register provider in `extra.laravel.providers`.
2. **Provider**: Extend `Webkul\PluginManager\PackageServiceProvider`. Implement `configureCustomPackage()`, declare `$package->name('<plugin>')`, `$package->hasTranslations()`, `$package->hasViews()`. If defining database tables, declare `$package->hasMigrations([...])` and `$package->runsMigrations()`.
3. **Registration**: Explicitly register the provider in `bootstrap/providers.php` before `PluginManagerServiceProvider`.
4. **Filament Plugin**: Implement `Filament\Contracts\Plugin`. Hook into `Panel::configureUsing()` in `packageRegistered()`. Check `Package::isPluginInstalled($this->getId())` before registering resources, pages, or clusters.
5. **Cross-Plugin Decoupling**: If extending upstream resources, use `SchemaRegistry` or `resolveRelationUsing` rather than modifying upstream code.
6. **Tenancy**: If tables store tenant data, include `$table->foreignId('company_id')`, use trait `BelongsToCompany`, and implement `ChecksCompanyConsistency` on transactional models.

### 9.4 Architecture Governance & Specification Synchronization Engine

To guarantee that `ARCHITECTURE.md` never drifts from Level 1 source reality, the repository incorporates an automated synchronization subsystem:

- **CLI Tool (`scripts/sync-architecture.php`)**:
  - `--check`: Compares extracted codebase metrics against `ARCHITECTURE.md`. Exits with code 0 if fully in sync, or code 1 with detailed drift items.
  - `--sync`: Automatically updates quantitative baseline strings, dependency tables, plugin tables, and migration counts in `ARCHITECTURE.md`.
  - `--json`: Outputs structured JSON metrics for CI integration.
  - `--hook=<type>`: Invoked during agent lifecycle hooks (`stop`, `post-tool`).
- **Core Components (`scripts/architecture/`)**:
  - `Extractor.php`: Inspects `composer.json`, `composer.lock`, `database/migrations/`, `plugins/webkul/`, and `NavigationGroup.php`.
  - `Synchronizer.php`: Computes exact text diffs against canonical markdown sections.
  - `HookHandler.php`: Evaluates git working tree against an architectural watchlist (`composer.json`, `database/migrations/`, `plugins/webkul/`, `bootstrap/providers.php`, `NavigationGroup.php`).
- **Git Pre-Commit Guard (`scripts/install-git-hooks.sh`)**:
  Installs a `.git/hooks/pre-commit` script that runs `php scripts/sync-architecture.php --check`, rejecting commits if specification drift is detected.
- **Automated CI Test (`tests/Unit/ArchitectureSyncTest.php`)**:
  Executes `--check` in the Pest test suite to ensure automated pipeline verification.
- **Composer Tooling Shortcuts**:
  - `composer check-architecture`
  - `composer sync-architecture`
