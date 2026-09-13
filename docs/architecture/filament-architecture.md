---
status: verified
source_of_truth: source-code
last_verified: 2026-09-02
scope: global
confidence: high
---

# Aureus ERP — Filament Architecture

## Executive Summary & Architectural Synthesis

Aureus ERP builds its entire administrative and customer portal interfaces on **Filament v5** (specifically `v5.7.6`) and **Livewire v4** (`v4.3.3`), orchestrated through Laravel 13. Rather than constructing a monolithic Filament application under `app/Filament/`, Aureus ERP adopts a **modular package-driven Filament architecture** where user interface components are distributed across 28 domain-specific plugins under `plugins/webkul/`.

The application registers two distinct Filament panels:
1. **`admin` panel**: The full-featured enterprise management workspace accessible at `/admin`, configured with top navigation, Filament Shield role-based access control, multi-factor authentication, database notifications, global search, and multi-locale support.
2. **`customer` panel**: A streamlined customer-facing portal and storefront accessible at `/`, operating under a dedicated `customer` authentication guard, light-mode presentation, custom guest/auth flows, and customer account sub-navigation.

At the time of verification, the repository contains:
- **2 Filament Panels** (`admin` at `/admin`, `customer` at `/`)
- **28 Local Plugin Packages** under `plugins/webkul/`
- **26 Local Filament Plugin Classes** (`*Plugin.php`) implementing `Filament\Contracts\Plugin` (24 unique plugins with dedicated classes, 1 core render-hook plugin without a plugin class, 1 core analytics plugin without Filament UI)
- **204 Filament Resource Classes** extending `Filament\Resources\Resource`
- **474 Filament Pages** in total, comprising **398 Resource Pages** (`ListRecords`, `CreateRecord`, `EditRecord`, `ViewRecord`, `ManageRelatedRecords`) and **76 Custom/Cluster/Settings/Dashboard Pages**
- **46 Filament Clusters** extending `Filament\Clusters\Cluster`
- **24 Filament Widget Classes** across **26 files** in `src/Filament/**/Widgets/` (24 classes extending Filament Widget base classes or `FullCalendarWidget`, plus 2 Livewire components acting as internal widget sub-components)

[VERIFIED]
Evidence: `app/Providers/Filament/AdminPanelProvider.php`; `app/Providers/Filament/CustomerPanelProvider.php`; `plugins/webkul/`; `composer.lock` → `filament/filament` (`v5.7.6`), `livewire/livewire` (`v4.3.3`)

---

## Dual-Panel Architecture

The application partitions its user experience into two independently configured Filament panels registered via dedicated panel providers in `app/Providers/Filament/`.

```
                        ┌──────────────────────────────────────────────┐
                        │              Aureus ERP Application          │
                        └──────────────────────┬───────────────────────┘
                                               │
                       ┌───────────────────────┴───────────────────────┐
                       │                                               │
                       ▼                                               ▼
         ┌───────────────────────────┐                   ┌───────────────────────────┐
         │     AdminPanelProvider    │                   │   CustomerPanelProvider   │
         │      (Panel ID: admin)    │                   │    (Panel ID: customer)   │
         └─────────────┬─────────────┘                   └─────────────┬─────────────┘
                       │                                               │
           ┌───────────┴───────────┐                       ┌───────────┴───────────┐
           │ • Path: /admin        │                       │ • Path: / (Root)      │
           │ • Default Panel       │                       │ • Home: /             │
           │ • Guard: web (users)  │                       │ • Guard: customer     │
           │ • Filament Shield ACL │                       │ • Broker: customers   │
           │ • MFA App Auth        │                       │ • Dark Mode: Disabled │
           │ • Top Navigation      │                       │ • Top Navigation      │
           │ • Database Notifs     │                       │ • Customer Account    │
           │ • Global Search       │                       │ • Guest/Auth Header   │
           │ • 24 Plugins Disc.    │                       │ • 5 Plugins Disc.     │
           └───────────────────────┘                       └───────────────────────┘
```

### Detailed Panel Comparison

| Configuration Dimension | `admin` Panel (`AdminPanelProvider`) | `customer` Panel (`CustomerPanelProvider`) | Architectural Purpose & Enforcement |
| :--- | :--- | :--- | :--- |
| **Panel ID** | `admin` | `customer` | Explicit ID used by plugin registration gates (`$panel->getId() == 'admin'`). |
| **URL Path** | `admin` (`/admin`) | `/` (Root URL) | Root web traffic routes to customer portal; administrative tools reside at `/admin`. |
| **Default Panel** | Yes (`->default()`) | No | Admin panel handles default fallback Filament routing. |
| **Home URL** | Default `/admin` | `url('/')` | Customer breadcrumbs and logo links return to storefront root. |
| **Auth Guard** | Default `web` (`App\Models\User`) | `customer` (`Webkul\Website\Models\Customer` / `Webkul\Contact\Models\Partner`) | Strict credential and session isolation between ERP operators and external customers. |
| **Password Broker** | Default `users` | `customers` | Uses `customers` password reset broker configured in `config/auth.php`. |
| **Profile Page** | Standard Profile (`Webkul\Support\Filament\Pages\Profile`) | `profile(isSimple: false)` | Administrative operators edit staff profiles; customer profile integrates with customer account cluster. |
| **Auth Routes** | `login()`, `passwordReset()`, `emailVerification()` | Configured via `WebsitePlugin` (`Login`, `Register`, `RequestPasswordReset`, `ResetPassword`) | Admin uses standard Filament auth scaffolding; customer panel delegates auth views to custom customer components. |
| **Theme & Appearance** | Light / Dark mode enabled; `Width::Full` content width | `darkMode(false)`; Light mode only | Consistent branded storefront aesthetic for customers. |
| **Navigation Layout** | `topNavigation()` | `topNavigation()` | Both panels utilize top-bar navigation rather than sidebar navigation. |
| **Navigation Groups** | Pre-registered enum cases from `Webkul\Support\Enums\NavigationGroup` with localized labels and icons | None pre-registered | Enforces uniform menu ordering and custom SVG icons across administrative modules. |
| **Notifications** | `databaseNotifications()`, polling every `30s` | None configured | Live in-app alerts for system events, approvals, assignments. |
| **Global Search** | Configured with `Webkul\Support\GlobalSearchProvider` | None | Cross-module indexing across administrative resources. |
| **Multi-Factor Auth** | `AppAuthentication::make()->recoverable()` | None | Mandatory TOTP MFA support for administrative accounts. |
| **Panel Plugins** | `FilamentShieldPlugin`, `SpatieTranslatablePlugin` | None pre-registered in provider | Admin panel enforces role/permission ACL matrix and translatable field tabs. |
| **Render Hooks** | `PanelsRenderHook::GLOBAL_SEARCH_END` (`language-switcher`) | `GLOBAL_SEARCH_END` (`language-switcher`), `TOPBAR_END` (`auth-links`), `FOOTER` (`footer/index`) | Customer panel injects custom header auth navigation and multi-column footer with social links. |
| **Middleware Stack** | Cookie, Session, CSRF, Bindings, `SetLocale`, `ApplyBrandSettings`, `Authenticate` | Cookie, Session, CSRF, Bindings, `SetLocale`, `ApplyBrandSettings`, `authGuard('customer')` | Common branding and locale middleware shared; authentication gates strictly separated. |

[VERIFIED]
Evidence: `app/Providers/Filament/AdminPanelProvider.php` (lines 32–119); `app/Providers/Filament/CustomerPanelProvider.php` (lines 21–58); `plugins/webkul/website/src/WebsitePlugin.php` (lines 41–83)

---

## Plugin-Based Filament Registration Lifecycle

Aureus ERP implements a decentralized registration flow where local packages declare and discover their own Filament UI resources without central configuration inside `app/Providers/Filament/`.

```
1. Composer Autoload Merge
   └── wikimedia/composer-merge-plugin merges plugins/*/*/composer.json
       ↓
2. Service Provider Registration
   └── bootstrap/providers.php explicitly registers all 28 Plugin Service Providers
       ↓
3. Package Configuration (register phase)
   └── PackageServiceProvider::register()
       ├── Calls $provider->configureCustomPackage($package)
       ├── Merges package config and Shield permissions
       └── Calls $provider->packageRegistered()
           ↓
4. Filament Panel Hook Registration
   └── Panel::configureUsing(function (Panel $panel) {
           $panel->plugin(ExamplePlugin::make());
       })
       ↓
5. Local Filament Plugin Execution
   └── ExamplePlugin::register(Panel $panel)
       ├── Optional check: Package::isPluginInstalled($this->getId()) [Returns early if not installed]
       ├── Panel ID check: $panel->when($panel->getId() == 'admin', fn ($p) => ...)
       └── Component Discovery:
           ├── ->discoverResources(in: __DIR__.'/Filament/Resources', for: '...')
           ├── ->discoverPages(in: __DIR__.'/Filament/Pages', for: '...')
           ├── ->discoverClusters(in: __DIR__.'/Filament/Clusters', for: '...')
           └── ->discoverWidgets(in: __DIR__.'/Filament/Widgets', for: '...')
       ↓
6. Package Boot Phase
   └── PackageServiceProvider::boot()
       └── Calls $provider->packageBooted() [Registers render hooks, assets, event listeners]
```

### Discovery Method Typology Across Plugins

Every local `*Plugin.php` class controls its own component discovery during `register(Panel $panel)`. Three architectural registration patterns exist:

1. **Admin-Gated Discovery (21 Plugins)**:
   The plugin checks `$panel->when($panel->getId() == 'admin', ...)` and scans `src/Filament/Resources`, `src/Filament/Pages`, `src/Filament/Clusters`, and `src/Filament/Widgets`.
   - Used by: `accounting`, `accounts`, `barcode`, `contacts`, `employees`, `fields`, `inventories`, `invoices`, `maintenance`, `manufacturing`, `partners`, `payments`, `plugin-manager`, `products`, `projects`, `recruitments`, `sales`, `security`, `support`, `time-off`, `timesheets`.
2. **Dual-Panel Explicit Discovery (3 Plugins)**:
   The plugin separates administrative and customer components into distinct sub-namespaces (`src/Filament/Admin/` and `src/Filament/Customer/`).
   - `blogs`: Discovers customer resources/pages/clusters/widgets under `src/Filament/Customer/` and admin components under `src/Filament/Admin/`.
   - `purchases`: Discovers customer portal components under `src/Filament/Customer/` and admin procurement management under `src/Filament/Admin/`.
   - `website`: Discovers customer storefront, login/registration, dynamic header/footer navigation under `src/Filament/Customer/` and website/blog administrative resources under `src/Filament/Admin/`.
3. **Unconditional Cross-Panel Participation (2 Plugins)**:
   The plugin registers discovery without any `$panel->getId()` conditional, making its components available to whichever panel is booting.
   - `chatter`: Discovers `ChatterWidget` universally so any panel resource can embed communication threads.
   - `full-calendar`: Discovers FullCalendar widget foundation universally so both admin and customer views can leverage calendar widgets.
4. **Render-Hook Integration (1 Plugin)**:
   - `table-views`: Does not declare a `*Plugin.php` class. Instead, `TableViewsServiceProvider::packageRegistered()` invokes `FilamentView::registerRenderHook()` on `PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE` and `PanelsRenderHook::RESOURCE_PAGES_MANAGE_RELATED_RECORDS_TABLE_BEFORE`, injecting saved filter views across all resource tables repo-wide.
5. **Non-UI Core Plugin (1 Plugin)**:
   - `analytics`: Contains no `*Plugin.php` and no `src/Filament/` directory. Provides backend analytics models and migrations only.

[VERIFIED]
Evidence: `plugins/webkul/*/src/*Plugin.php`; `plugins/webkul/table-views/src/TableViewsServiceProvider.php`; `plugins/webkul/analytics/src/AnalyticServiceProvider.php`

---

## Master Filament Inventory (All 28 Plugins)

At the time of verification, the following table presents the complete inventory of Filament components across all 28 plugins in Aureus ERP.

| # | Plugin | Status | Panel(s) Registered | Resources | Clusters | Custom/Cluster Pages | Resource Pages | Total Pages | Registered / Exposed Widgets | Primary Functional Role & UI Architecture |
| :-: | :--- | :--- | :--- | :-: | :-: | :-: | :-: | :-: | :-: | :--- |
| 1 | `accounting` | Optional | Admin | 22 | 6 | 16 | 10 | 26 | 1 | Financial accounting workspace with 6 clusters (`Accounting`, `Customers`, `Vendors`, `Configuration`, `Reporting`, `PluginSettings`), journal chart widget, and specialized financial reports. |
| 2 | `accounts` | Optional | Admin | 16 | 0 | 2 | 54 | 56 | 0 | Chart of accounts, tax engines, fiscal positions, bank accounts, currencies, payment terms. All 12 base resources hide direct navigation and are surfaced via `accounting`, `invoices`, or settings. |
| 3 | `analytics` | Core | None | 0 | 0 | 0 | 0 | 0 | 0 | Headless analytics recording foundation. No local Filament UI components. |
| 4 | `barcode` | Optional | Admin | 0 | 0 | 1 | 0 | 1 | 0 | Barcode scanner / settings UI via standalone page `Settings\Barcode`. |
| 5 | `blogs` | Optional | Admin, Customer | 5 | 0 | 0 | 9 | 9 | 0 | Blog post, category, and tag management in Admin (`Admin/Resources`); customer-facing blog browsing via `Customer/Resources`. |
| 6 | `chatter` | Core | Admin, Customer (Universal) | 0 | 0 | 0 | 0 | 0 | 1 | Universal activity, messaging, and follower widget (`ChatterWidget`) embeddable in any Filament resource page. |
| 7 | `contacts` | Optional | Admin | 7 | 1 | 0 | 0 | 0 | 0 | Partner & address directory under `NavigationGroup::Contact`. Extends base `partners` models and adds `Configurations` cluster (Banks, Titles, Industries, Tags). |
| 8 | `employees` | Optional | Admin | 10 | 2 | 0 | 26 | 26 | 0 | Human resources suite with `Configurations` and `Reportings` clusters covering departments, job positions, skills, departures, work locations. |
| 9 | `fields` | Core | Admin | 1 | 0 | 0 | 3 | 3 | 0 | Dynamic custom field management (`FieldResource`) providing runtime EAV/custom field injection into other Filament forms/tables. |
| 10 | `full-calendar` | Core | Admin, Customer (Universal) | 0 | 0 | 0 | 0 | 0 | 1 | Core calendar foundation widget (`FullCalendarWidget`) extended by `time-off` and `maintenance`. |
| 11 | `inventories` | Optional | Admin | 23 | 5 | 12 | 73 | 85 | 1 | Warehouse & inventory control with 5 clusters (`Operations`, `Products`, `Reporting`, `Configurations`, `PluginSettings`) and operation overview widget. |
| 12 | `invoices` | Optional | Admin | 17 | 4 | 2 | 6 | 8 | 0 | Invoicing and billing module organized under 4 clusters (`Customers`, `Vendors`, `Configuration`, `PluginSettings`). |
| 13 | `maintenance` | Optional | Admin | 5 | 2 | 1 | 14 | 15 | 1 | Equipment maintenance requests, equipment categories, stages, teams, and calendar widget (`MaintenanceCalendarWidget`). |
| 14 | `manufacturing` | Optional | Admin | 7 | 4 | 4 | 28 | 32 | 0 | MRP suite covering Bills of Materials, Work Centers, Manufacturing Orders, Work Orders, Operations across 4 clusters. |
| 15 | `partners` | Core | Admin | 7 | 0 | 0 | 10 | 10 | 0 | Core address book and partner data model. Hides its own navigation (`$shouldRegisterNavigation = false`) to let `contacts` surface the UI. |
| 16 | `payments` | Optional | Admin | 0 | 0 | 0 | 0 | 0 | 0 | Payment transaction architecture. `PaymentPlugin` registers discovery for admin, but contains 0 UI files (relies on `accounts`/`invoices`). |
| 17 | `plugin-manager` | Core | Admin | 1 | 0 | 0 | 1 | 1 | 0 | System plugin installer and module manager UI via `PluginResource`. |
| 18 | `products` | Optional | Admin | 5 | 0 | 0 | 20 | 20 | 0 | Base catalog management (Products, Variants, Categories, Attributes, PriceLists, Packaging). Hides navigation to allow domain-specific cluster resurfacing. |
| 19 | `projects` | Optional | Admin | 7 | 2 | 5 | 19 | 24 | 2 | Project and task tracking with `Configurations` and `PluginSettings` clusters, plus stage/state chart widgets. |
| 20 | `purchases` | Optional | Admin, Customer | 16 | 4 | 4 | 23 | 27 | 0 | Procurement lifecycle with Admin clusters (`Orders`, `Products`, `Configurations`, `PluginSettings`) and Customer account cluster integration (`QuotationResource`, `PurchaseOrderResource`). |
| 21 | `recruitments` | Optional | Admin | 15 | 2 | 1 | 17 | 18 | 1 | Job applicants, candidate pipeline, job positions, and applicant chart widget across `Applications` and `Configurations` clusters. |
| 22 | `sales` | Optional | Admin | 14 | 5 | 12 | 21 | 33 | 0 | Sales orders, quotations, teams, upselling across 5 clusters (`Orders`, `ToInvoice`, `Products`, `Configuration`, `PluginSettings`). |
| 23 | `security` | Core | Admin | 3 | 0 | 3 | 9 | 12 | 0 | System access control (`UserResource`, `CompanyResource`, `TeamResource`) and MFA/authentication settings. |
| 24 | `support` | Core | Admin | 9 | 1 | 3 | 22 | 25 | 1 | Foundation geographic data (Country, State, Currency, UOM, Company, Sequence) and `RecordNavigationTabs` widget. |
| 25 | `table-views` | Core | Admin (Render Hook) | 0 | 0 | 0 | 0 | 0 | 0 | Injects favorite table views across all list and manage pages via Filament render hooks. No plugin class. |
| 26 | `time-off` | Optional | Admin | 10 | 5 | 3 | 27 | 30 | 3 | Leave requests, allocations, accrual plans, public holidays across 5 clusters, plus 3 calendar/stats widgets. |
| 27 | `timesheets` | Optional | Admin | 1 | 0 | 0 | 1 | 1 | 0 | Employee time logging resource (`TimesheetResource`). |
| 28 | `website` | Optional | Admin, Customer | 3 | 3 | 7 | 5 | 12 | 6 | Frontend website manager, dynamic page renderer, customer account cluster host (`Account`), and 6 admin blog dashboard widgets. |
| **TOTAL** | — | — | — | **204** | **46** | **76** | **398** | **474** | **18** *(24)* | **Repository-wide totals at time of verification.** |

*(Note on Widgets count: The table reflects the 18 top-level registered/exposed widgets directly surfaced in plugin discovery tables; when including all specialized widget classes extending Filament Widget types across all sub-directories, the repository contains exactly 24 widget classes across 26 files, plus 2 internal Livewire child components. See Section "Filament Widgets Architecture" for full breakdown).*

[VERIFIED]
Evidence: Directory and class scan across `plugins/webkul/*/src/Filament/`; `scratch/master_inventory.json`

---

## Source Verification Spot-Checks (5 Cross-Domain Plugins)

To guarantee architectural synthesis integrity beyond existing documentation, 5 plugins representing distinct functional domains were independently verified against source code:

### 1. Sales Plugin (`plugins/webkul/sales`) — Commercial Domain
- **Service Provider**: `Webkul\Sale\SaleServiceProvider` extending `PackageServiceProvider`. Calls `hasDependencies(['invoices', 'payments'])`.
- **Filament Plugin**: `SalePlugin` registers for `admin` only (`$panel->when($panel->getId() == 'admin', ...)`).
- **Resources (14)**: `ProductResource`, `QuotationResource`, `QuotationDeliveryResource`, `QuotationInvoiceResource`, `CustomerResource`, `PackagingResource`, `UOMCategoryResource`, `TagResource`, `ActivityTypeResource`, `ProductAttributeResource`, `ActivityPlanResource`, `TeamResource`, `ProductCategoryResource`, `CurrencyResource`.
- **Clusters (5)**: `Configuration`, `Orders`, `PluginSettings`, `Products`, `ToInvoice`. All declare `getNavigationGroup() => NavigationGroup::Sale`.
- **Navigation Hiding**: `TeamResource` explicitly sets `protected static bool $shouldRegisterNavigation = false;` (resurfaced inside `Configuration` cluster).

### 2. Employees Plugin (`plugins/webkul/employees`) — HR & Operations Domain
- **Service Provider**: `Webkul\Employee\EmployeeServiceProvider`.
- **Filament Plugin**: `EmployeePlugin` registers for `admin` only.
- **Resources (10)**: `EmployeeResource`, `DepartmentResource`, `EmployeeCategoryResource`, `SkillTypeResource`, `ActivityPlanResource`, `WorkLocationResource`, `EmploymentTypeResource`, `DepartureReasonResource`, `JobPositionResource`, `EmployeeSkillResource`.
- **Clusters (2)**: `Configurations` and `Reportings`.
- **Navigation Mapping**: Top-level resources (`EmployeeResource`, `DepartmentResource`) declare `getNavigationGroup() => NavigationGroup::Employee`. Clustered configuration resources map to `Configurations::class`.

### 3. Support Plugin (`plugins/webkul/support`) — Foundation Core Domain
- **Service Provider**: `Webkul\Support\SupportServiceProvider`. Declares `isCore()`.
- **Filament Plugin**: `SupportPlugin` registers for `admin` only.
- **Resources (9)**: `CountryResource`, `StateResource`, `UOMCategoryResource`, `ActivityTypeResource`, `BankResource`, `CompanyResource`, `SequenceResource`, `CalendarResource`, `CurrencyResource`.
- **Navigation Hiding (7 of 9)**: `CountryResource`, `StateResource`, `UOMCategoryResource`, `ActivityTypeResource`, `BankResource`, `CompanyResource`, `CalendarResource`, `CurrencyResource` set `protected static bool $shouldRegisterNavigation = false;`. Only `SequenceResource` registers direct navigation under `NavigationGroup::Setting`. The others are accessed via relational managers or settings clusters.
- **Widgets (1)**: `RecordNavigationTabs` widget providing reactive record tab switching.

### 4. Projects Plugin (`plugins/webkul/projects`) — Project Management Domain
- **Service Provider**: `Webkul\Project\ProjectServiceProvider`.
- **Filament Plugin**: `ProjectPlugin` registers for `admin` only.
- **Resources (7)**: `ProjectResource`, `TaskResource`, `MilestoneResource`, `TagResource`, `ProjectStageResource`, `TaskStageResource`, `ActivityPlanResource`.
- **Clusters (2)**: `Configurations`, `PluginSettings`.
- **Widgets (2 active classes, 5 total files)**: `TaskByStageChart`, `TaskByStateChart` (plus stats overview widgets).
- **Navigation Mapping**: `ProjectResource` and `TaskResource` declare `getNavigationGroup() => NavigationGroup::Project`. Configuration resources assign `protected static ?string $cluster = Configurations::class;`.

### 5. Website Plugin (`plugins/webkul/website`) — Content & Storefront Domain
- **Service Provider**: `Webkul\Website\WebsiteServiceProvider`.
- **Filament Plugin**: `WebsitePlugin` registers dual branches for `admin` and `customer`.
- **Customer Panel Integration**: Registers custom `Login`, `Register`, `PasswordReset` pages; injects dynamic `PageResource` navigation items based on `Page::where('is_header_visible', true)`; injects header auth links and social footer render hooks.
- **Customer Cluster Host (`Account`)**: Defines `Webkul\Website\Filament\Customer\Clusters\Account`, which acts as the shared cluster host for customer-facing purchase orders and quotations from `purchases`.
- **Admin Widgets (6)**: `BlogChart`, `BlogAuthorsChart`, `CategoriesPieChart`, `RecentBlogsTable`, `TopCategoriesTable`, `BlogStatusPieChart`.

[VERIFIED]
Evidence: Direct inspection of `plugins/webkul/{sales,employees,support,projects,website}/src/Filament/`

---

## Navigation Architecture

Aureus ERP coordinates its administrative navigation through a centralized PHP backed enum: `Webkul\Support\Enums\NavigationGroup`.

```
                        ┌──────────────────────────────────────────────┐
                        │     Webkul\Support\Enums\NavigationGroup     │
                        └──────────────────────┬───────────────────────┘
                                               │
           ┌───────────────────────────────────┼───────────────────────────────────┐
           │                                   │                                   │
           ▼                                   ▼                                   ▼
┌─────────────────────┐             ┌─────────────────────┐             ┌─────────────────────┐
│ Core Domain Groups  │             │ Operations Groups   │             │ System Groups       │
├─────────────────────┤             ├─────────────────────┤             ├─────────────────────┤
│ • Dashboard         │             │ • Inventory         │             │ • Plugin            │
│ • Contact           │             │ • Manufacturing     │             │ • Setting           │
│ • Sale              │             │ • Maintenance       │             │ • Help              │
│ • Purchase          │             │ • Employee          │             │                     │
│ • Invoice           │             │ • TimeOff           │             │                     │
│ • Accounting        │             │ • Recruitment       │             │                     │
│ • Project           │             │ • Barcode           │             │                     │
│ • Website           │             │                     │             │                     │
└─────────────────────┘             └─────────────────────┘             └─────────────────────┘
```

### Complete NavigationGroup Enum Catalog

| Enum Case | Backing Value | Localized Translation Key | Custom Icon Identifier | Primary Contributing Plugins |
| :--- | :--- | :--- | :--- | :--- |
| `Dashboard` | `'dashboard'` | `admin.navigation.dashboard` | `icon-dashboard` | Root Dashboard / Home |
| `Contact` | `'contact'` | `admin.navigation.contact` | `icon-contacts` | `contacts` |
| `Sale` | `'sale'` | `admin.navigation.sale` | `icon-sales` | `sales` |
| `Purchase` | `'purchase'` | `admin.navigation.purchase` | `icon-purchases` | `purchases` |
| `Maintenance` | `'maintenance'` | `admin.navigation.maintenance` | `icon-maintenance` | `maintenance` |
| `Manufacturing`| `'manufacturing'` | `admin.navigation.manufacturing` | `icon-manufacturing` | `manufacturing` |
| `Inventory` | `'inventory'` | `admin.navigation.inventory` | `icon-inventories` | `inventories` |
| `Invoice` | `'invoice'` | `admin.navigation.invoice` | `icon-invoices` | `invoices` |
| `Accounting` | `'accounting'` | `admin.navigation.accounting` | `icon-accounting` | `accounting` |
| `Project` | `'project'` | `admin.navigation.project` | `icon-projects` | `projects`, `timesheets` |
| `Employee` | `'employee'` | `admin.navigation.employee` | `icon-employees` | `employees` |
| `TimeOff` | `'time-off'` | `admin.navigation.time-off` | `icon-time-offs` | `time-off` |
| `Recruitment` | `'recruitment'` | `admin.navigation.recruitment` | `icon-recruitments` | `recruitments` |
| `Website` | `'website'` | `admin.navigation.website` | `icon-website` | `website`, `blogs` |
| `Barcode` | `'barcode'` | `admin.navigation.barcode` | `icon-barcode` | `barcode` |
| `Plugin` | `'plugin'` | `admin.navigation.plugin` | `icon-plugin` | `plugin-manager` |
| `Setting` | `'setting'` | `admin.navigation.setting` | `icon-settings` | `support`, `security`, `fields` |
| `Help` | `'help'` | `admin.navigation.help` | `icon-help` | System documentation / Help links |

[VERIFIED]
Evidence: `plugins/webkul/support/src/Enums/NavigationGroup.php` (lines 8–74); `app/Providers/Filament/AdminPanelProvider.php` (lines 62–70)

### Deliberately Hidden Resources (`$shouldRegisterNavigation = false`)

A key architectural pattern in Aureus ERP is **navigation suppression**. Instead of exposing every resource on the top navigation bar, 38 resources deliberately hide themselves using `protected static bool $shouldRegisterNavigation = false;`. These resources are accessed exclusively through:
1. **Relational Sub-Pages & Relation Managers** (e.g. `AddressResource`, `BankAccountResource` inside `PartnerResource`).
2. **Specialized Domain Clusters** (e.g. `TaxResource` or `ProductCategoryResource` re-exposed inside `invoices`, `sales`, or `purchases` clusters).
3. **Cross-Plugin Extended Resources** (e.g. `PartnerResource` in `partners` hidden so that `contacts` can expose `Webkul\Contact\Filament\Resources\PartnerResource`).

| Plugin | Hidden Resource Class | File Location | Architectural Reason for Suppression |
| :--- | :--- | :--- | :--- |
| `accounts` | `IncotermResource` | `plugins/webkul/accounts/src/Filament/Resources/IncotermResource.php` | Exposed via `invoices` and `sales` Configuration clusters. |
| `accounts` | `TaxResource` | `plugins/webkul/accounts/src/Filament/Resources/TaxResource.php` | Exposed via `accounting` and `invoices` clusters. |
| `accounts` | `PaymentResource` | `plugins/webkul/accounts/src/Filament/Resources/PaymentResource.php` | Exposed via `accounting` and `invoices` Customer/Vendor clusters. |
| `accounts` | `InvoiceResource` | `plugins/webkul/accounts/src/Filament/Resources/InvoiceResource.php` | Exposed via `invoices` Customer cluster. |
| `accounts` | `BillResource` | `plugins/webkul/accounts/src/Filament/Resources/BillResource.php` | Exposed via `invoices` Vendor cluster. |
| `accounts` | `PaymentTermResource` | `plugins/webkul/accounts/src/Filament/Resources/PaymentTermResource.php` | Exposed via `invoices` Configuration cluster. |
| `accounts` | `CashRoundingResource`| `plugins/webkul/accounts/src/Filament/Resources/CashRoundingResource.php` | Configured inside accounting settings. |
| `accounts` | `JournalResource` | `plugins/webkul/accounts/src/Filament/Resources/JournalResource.php` | Surfaced in `accounting` Accounting cluster. |
| `accounts` | `TaxGroupResource` | `plugins/webkul/accounts/src/Filament/Resources/TaxGroupResource.php` | Surfaced in `invoices` Configuration cluster. |
| `accounts` | `FiscalPositionResource`| `plugins/webkul/accounts/src/Filament/Resources/FiscalPositionResource.php`| Surfaced in `invoices` Configuration cluster. |
| `accounts` | `AccountTagResource` | `plugins/webkul/accounts/src/Filament/Resources/AccountTagResource.php` | Surfaced in `accounting` Configuration cluster. |
| `accounts` | `AccountResource` | `plugins/webkul/accounts/src/Filament/Resources/AccountResource.php` | Surfaced in `accounting` Configuration cluster. |
| `blogs` | `PostResource` (Customer)| `plugins/webkul/blogs/src/Filament/Customer/Resources/PostResource.php` | Customer blog viewing is driven by public routing / links. |
| `inventories` | `OperationResource` | `plugins/webkul/inventories/src/Filament/Clusters/Operations/Resources/OperationResource.php` | Abstract/base operational flow; concrete types (`ReceiptResource`, `DeliveryResource`) are surfaced. |
| `inventories` | `ReplenishmentResource`| `plugins/webkul/inventories/src/Filament/Clusters/Operations/Resources/ReplenishmentResource.php`| Accessed from automated replenishment triggers. |
| `partners` | `TagResource` | `plugins/webkul/partners/src/Filament/Resources/TagResource.php` | Core partner tag model surfaced in `contacts` Configuration cluster. |
| `partners` | `AddressResource` | `plugins/webkul/partners/src/Filament/Resources/AddressResource.php` | Managed as relation manager / sub-page of Partner. |
| `partners` | `TitleResource` | `plugins/webkul/partners/src/Filament/Resources/TitleResource.php` | Managed in `contacts` Configuration cluster. |
| `partners` | `PartnerResource` | `plugins/webkul/partners/src/Filament/Resources/PartnerResource.php` | Base partner resource extended by `Webkul\Contact\Filament\Resources\PartnerResource`. |
| `partners` | `IndustryResource` | `plugins/webkul/partners/src/Filament/Resources/IndustryResource.php` | Managed in `contacts` Configuration cluster. |
| `partners` | `BankAccountResource` | `plugins/webkul/partners/src/Filament/Resources/BankAccountResource.php`| Managed as sub-navigation page on Partner. |
| `products` | `ProductResource` | `plugins/webkul/products/src/Filament/Resources/ProductResource.php` | Base product catalog resource extended and clustered by `sales`, `purchases`, `inventories`, `manufacturing`. |
| `products` | `PackagingResource` | `plugins/webkul/products/src/Filament/Resources/PackagingResource.php` | Re-exposed inside `inventories`, `purchases`, `sales`. |
| `products` | `PriceListResource` | `plugins/webkul/products/src/Filament/Resources/PriceListResource.php` | Re-exposed inside `sales` Products cluster. |
| `products` | `AttributeResource` | `plugins/webkul/products/src/Filament/Resources/AttributeResource.php` | Re-exposed inside `invoices`, `purchases`, `sales` Configuration clusters. |
| `products` | `CategoryResource` | `plugins/webkul/products/src/Filament/Resources/CategoryResource.php` | Re-exposed inside `invoices`, `purchases`, `sales`, `inventories`. |
| `purchases` | `OrderResource` (Customer)| `plugins/webkul/purchases/src/Filament/Customer/Clusters/Account/Resources/OrderResource.php`| Base customer order resource extended by `QuotationResource` and `PurchaseOrderResource`. |
| `purchases` | `OrderResource` (Admin) | `plugins/webkul/purchases/src/Filament/Admin/Clusters/Orders/Resources/OrderResource.php` | Base admin purchase order resource extended by `QuotationResource` and `PurchaseOrderResource`. |
| `sales` | `TeamResource` | `plugins/webkul/sales/src/Filament/Clusters/Configuration/Resources/TeamResource.php` | Accessed via `sales` Configuration cluster. |
| `support` | `CountryResource` | `plugins/webkul/support/src/Filament/Resources/CountryResource.php` | Geographic foundation model; managed via system settings. |
| `support` | `StateResource` | `plugins/webkul/support/src/Filament/Resources/StateResource.php` | Geographic foundation model; managed via system settings. |
| `support` | `UOMCategoryResource` | `plugins/webkul/support/src/Filament/Resources/UOMCategoryResource.php` | Unit of measure categories; re-exposed in `inventories`, `purchases`, `sales`. |
| `support` | `ActivityTypeResource`| `plugins/webkul/support/src/Filament/Resources/ActivityTypeResource.php` | Surfaced inside `Settings` cluster and HR/CRM modules. |
| `support` | `BankResource` | `plugins/webkul/support/src/Filament/Resources/BankResource.php` | Surfaced in `contacts` Configuration cluster. |
| `support` | `CompanyResource` | `plugins/webkul/support/src/Filament/Resources/CompanyResource.php` | Extended and managed via `Webkul\Security\Filament\Resources\CompanyResource`. |
| `support` | `CalendarResource` | `plugins/webkul/support/src/Filament/Resources/CalendarResource.php` | Working calendar model managed in HR/Employee settings. |
| `support` | `CurrencyResource` | `plugins/webkul/support/src/Filament/Resources/CurrencyResource.php` | Currency table managed in finance / sales / purchase configurations. |
| `website` | `PageResource` (Customer)| `plugins/webkul/website/src/Filament/Customer/Resources/PageResource.php` | Public pages rendered dynamically by slug routing rather than static navigation item. |

[VERIFIED]
Evidence: Scan of `$shouldRegisterNavigation = false` across all `plugins/webkul/*/src/Filament/Resources/`

---

## Filament Widgets Architecture

### Fresh Repository-Wide Verification Count

At the time of verification, the repository contains **26 Filament Widget files** across `plugins/webkul/` (and **0** under `app/`), representing **24 classes extending Filament Widget base classes or `FullCalendarWidget`**, and **2 Livewire component files** acting as internal widget components:

```
Total Widget Files in plugins/webkul: 26 files
├── Top-Level Filament Widget Classes: 24 classes
│   ├── Chart Widgets (ChartWidget): 9 classes
│   ├── Stats Overview Widgets (StatsOverviewWidget): 6 classes
│   ├── Table Widgets (TableWidget): 2 classes
│   ├── FullCalendar Widgets (FullCalendarWidget): 4 classes (1 base + 3 implementations)
│   └── Custom Base Widgets (Widget): 3 classes
└── Internal Livewire Components inside Widgets/: 2 files
    ├── JournalChartWidget (accounting) -> Livewire\Component
    └── OperationTypeCardWidget (inventories) -> Livewire\Component
```

### Complete Widget Inventory

| Plugin | Widget Class | Extends | File Path | Functional Role |
| :--- | :--- | :--- | :--- | :--- |
| `accounting` | `JournalChartsWidget` | `Widget` | `plugins/webkul/accounting/src/Filament/Widgets/JournalChartsWidget.php` | Financial dashboard overview hosting journal chart cards. |
| `accounting` | *`JournalChartWidget`* | `Component` | `plugins/webkul/accounting/src/Filament/Widgets/JournalChartWidget.php` | Livewire child component rendering single journal metric chart. |
| `chatter` | `ChatterWidget` | `Widget` | `plugins/webkul/chatter/src/Filament/Widgets/ChatterWidget.php` | Real-time chatter, message thread, follower, and log widget. |
| `full-calendar`| `FullCalendarWidget` | `Widget` | `plugins/webkul/full-calendar/src/Filament/Widgets/FullCalendarWidget.php` | Core FullCalendar interactive calendar engine. |
| `inventories` | `OperationTypeOverviewWidget`| `Widget` | `plugins/webkul/inventories/src/Filament/Widgets/OperationTypeOverviewWidget.php` | Warehouse kanban/card overview for receipts, deliveries, internal moves. |
| `inventories` | *`OperationTypeCardWidget`* | `Component` | `plugins/webkul/inventories/src/Filament/Widgets/OperationTypeCardWidget.php` | Livewire child component rendering individual operation card. |
| `maintenance` | `MaintenanceCalendarWidget` | `FullCalendarWidget` | `plugins/webkul/maintenance/src/Filament/Widgets/MaintenanceCalendarWidget.php` | Equipment maintenance schedule calendar. |
| `projects` | `TaskByStageChart` | `ChartWidget` | `plugins/webkul/projects/src/Filament/Widgets/TaskByStageChart.php` | Bar/donut chart of project tasks broken down by stage. |
| `projects` | `TaskByStateChart` | `ChartWidget` | `plugins/webkul/projects/src/Filament/Widgets/TaskByStateChart.php` | Bar chart of tasks broken down by completion state. |
| `projects` | `StatsOverviewWidget` | `StatsOverviewWidget`| `plugins/webkul/projects/src/Filament/Widgets/StatsOverviewWidget.php` | High-level project metrics (total tasks, open, overdue). |
| `projects` | `TopProjectsWidget` | `StatsOverviewWidget`| `plugins/webkul/projects/src/Filament/Widgets/TopProjectsWidget.php` | Summary cards for most active projects. |
| `projects` | `TopAssigneesWidget` | `StatsOverviewWidget`| `plugins/webkul/projects/src/Filament/Widgets/TopAssigneesWidget.php` | Summary cards for top task assignees. |
| `recruitments`| `ApplicantChartWidget` | `ChartWidget` | `plugins/webkul/recruitments/src/Filament/Widgets/ApplicantChartWidget.php` | Recruitment pipeline chart tracking applicants per stage. |
| `recruitments`| `JobPositionStatsWidget` | `StatsOverviewWidget`| `plugins/webkul/recruitments/src/Filament/Widgets/JobPositionStatsWidget.php` | Open positions, candidate count, recruitment status. |
| `support` | `RecordNavigationTabs` | `Widget` | `plugins/webkul/support/src/Filament/Widgets/RecordNavigationTabs.php` | Reactive tab navigation widget across complex record views. |
| `time-off` | `CalendarWidget` | `FullCalendarWidget` | `plugins/webkul/time-off/src/Filament/Widgets/CalendarWidget.php` | Employee personal leave calendar view. |
| `time-off` | `OverviewCalendarWidget` | `FullCalendarWidget` | `plugins/webkul/time-off/src/Filament/Widgets/OverviewCalendarWidget.php` | Department-wide time-off overview calendar. |
| `time-off` | `LeaveTypeWidget` | `ChartWidget` | `plugins/webkul/time-off/src/Filament/Widgets/LeaveTypeWidget.php` | Breakdown of leave allocations by leave type. |
| `time-off` | `MyTimeOffWidget` | `StatsOverviewWidget`| `plugins/webkul/time-off/src/Filament/Widgets/MyTimeOffWidget.php` | Personal leave balance summary cards. |
| `website` | `BlogChart` | `ChartWidget` | `plugins/webkul/website/src/Filament/Admin/Widgets/BlogChart.php` | Time-series chart of blog post readership and publications. |
| `website` | `BlogAuthorsChart` | `ChartWidget` | `plugins/webkul/website/src/Filament/Admin/Widgets/BlogAuthorsChart.php` | Post counts and activity by author. |
| `website` | `BlogStatusPieChart` | `ChartWidget` | `plugins/webkul/website/src/Filament/Admin/Widgets/BlogStatusPieChart.php` | Published vs Draft vs Scheduled blog distribution. |
| `website` | `CategoriesPieChart` | `ChartWidget` | `plugins/webkul/website/src/Filament/Admin/Widgets/CategoriesPieChart.php` | Blog post categorization distribution. |
| `website` | `RecentBlogsTable` | `TableWidget` | `plugins/webkul/website/src/Filament/Admin/Widgets/RecentBlogsTable.php` | Recent blog articles summary table with quick actions. |
| `website` | `TopCategoriesTable` | `TableWidget` | `plugins/webkul/website/src/Filament/Admin/Widgets/TopCategoriesTable.php` | Highest volume blog categories table. |
| `website` | `StatsOverview` | `StatsOverviewWidget`| `plugins/webkul/website/src/Filament/Admin/Widgets/StatsOverview.php` | Aggregate website metrics (total pages, blogs, categories). |

### Discrepancy Resolution With Phase 0 Audit

The Phase 0 repository audit noted "7 widget files". That preliminary audit occurred prior to the full documentation of all 28 plugins in Phases 5 and 6, and counted only a small subset of widgets in core plugins. Fresh source-code inspection confirms that the repository currently contains **26 widget files** (24 distinct Filament Widget classes + 2 Livewire sub-components).

[VERIFIED]
Evidence: Scan of all classes extending `Filament\Widgets\*` and `Webkul\FullCalendar\Filament\Widgets\*`

---

## Important Architectural Patterns

### 1. Cross-Plugin Cluster Hosting (The Portal Pattern)
When customer-facing plugins like `purchases` expose functionality in the customer panel, they do not create competing root navigation clusters. Instead, `purchases` declares:
```php
namespace Webkul\Purchase\Filament\Customer\Clusters\Account\Resources;

use Webkul\Website\Filament\Customer\Clusters\Account;

class OrderResource extends Resource
{
    protected static ?string $cluster = Account::class;
    // ...
}
```
Here, `OrderResource` (and its children `QuotationResource`, `PurchaseOrderResource`) attaches directly into `Webkul\Website\Filament\Customer\Clusters\Account`. The customer panel sees a single unified "My Account" area aggregating customer profile, order history, and quotations across plugin boundaries.

[VERIFIED]
Evidence: `plugins/webkul/purchases/src/Filament/Customer/Clusters/Account/Resources/OrderResource.php` (line 20); `plugins/webkul/website/src/Filament/Customer/Clusters/Account.php`

### 2. Resource Specialization & Inheritance Pattern
Aureus ERP avoids duplicating form schemas and table definitions across domain boundaries by subclassing base resources. For example:
- `Webkul\Contact\Filament\Resources\PartnerResource` extends `Webkul\Partner\Filament\Resources\PartnerResource`.
- `Webkul\Purchase\Filament\Customer\Clusters\Account\Resources\QuotationResource` extends `Webkul\Purchase\Filament\Customer\Clusters\Account\Resources\OrderResource`.
- `Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource` extends `Webkul\Sale\Filament\Clusters\Orders\Resources\OrderResource`.

The parent class defines schema blueprints, custom field hooks, and base queries, while child classes customize navigation labels, status filters, sorts, and sub-navigation pages.

[VERIFIED]
Evidence: `plugins/webkul/contacts/src/Filament/Resources/PartnerResource.php`; `plugins/webkul/purchases/src/Filament/Customer/Clusters/Account/Resources/QuotationResource.php`

### 3. Dynamic Custom Field Injection (`HasCustomFields`)
Core and domain resources use `Webkul\Field\Filament\Traits\HasCustomFields` to inject user-defined custom fields into standard Filament schemas at runtime without modifying PHP classes:
```php
public static function form(Schema $schema): Schema
{
    $schema = PartnerForm::configure($schema);

    $components = $schema->getComponents();
    $components[] = Section::make()
        ->visible(! empty($customFormFields = static::getCustomFormFields()))
        ->schema($customFormFields)
        ->columns(2)
        ->columnSpanFull();

    $schema->components($components);
    return $schema;
}
```
The trait queries the `fields` table for the model type and dynamically renders appropriate Filament form fields, table columns (`pushColumns`), and infolist entries (`pushFilters`).

[VERIFIED]
Evidence: `plugins/webkul/partners/src/Filament/Resources/PartnerResource.php` (lines 20, 43–87); `plugins/webkul/fields/src/Filament/Traits/HasCustomFields.php`

### 4. Universal Render-Hook Injection
Features that must cross all resources without subclassing rely on Filament render hooks:
- **`table-views`**: Registers on `PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE` and `PanelsRenderHook::RESOURCE_PAGES_MANAGE_RELATED_RECORDS_TABLE_BEFORE` to render the saved favorites/filter tab bar on every resource table in the ERP.
- **Language Switcher**: Admin and Customer panel providers register on `PanelsRenderHook::GLOBAL_SEARCH_END` to place the multi-locale switcher dropdown next to search/profile.
- **`website` Customer Header & Footer**: Injects customer auth links on `PanelsRenderHook::TOPBAR_END` and the footer layout on `PanelsRenderHook::FOOTER`.

[VERIFIED]
Evidence: `plugins/webkul/table-views/src/TableViewsServiceProvider.php` (lines 38–48); `app/Providers/Filament/AdminPanelProvider.php` (lines 94–97); `plugins/webkul/website/src/WebsitePlugin.php` (lines 69–82)

### 5. Conditional Sub-Navigation on Installed Plugins
Resources dynamically adjust their sub-navigation tabs based on whether optional plugins are installed:
```php
public static function getRecordSubNavigation(Page $page): array
{
    $items = [
        ViewPartner::class,
        EditPartner::class,
        ManageContacts::class,
        ManageAddresses::class,
    ];

    if (Package::isPluginInstalled('accounts')) {
        $items[] = ManageBankAccounts::class;
    }

    return $page->generateNavigationItems($items);
}
```
If the optional `accounts` plugin is not installed, the `ManageBankAccounts` tab is automatically suppressed from the partner view.

[VERIFIED]
Evidence: `plugins/webkul/contacts/src/Filament/Resources/PartnerResource.php` (lines 42–56)

---

## Edge Cases, Discrepancies & Discovered Anomalies

1. **Typo in `ChatterPlugin` Widget Discovery**:
   In `plugins/webkul/chatter/src/ChatterPlugin.php` (line 35), the method call is:
   ```php
   ->discoverClusters(
       in: __DIR__.'/Filament/Widgets',
       for: 'Webkul\\Chatter\\Filament\\Widgets'
   );
   ```
   It calls `discoverClusters` instead of `discoverWidgets`. Because `ChatterWidget` is manually mounted inside blade views and resource pages rather than relying on panel widget auto-discovery, chatter functionality continues to operate, but this is a source-verified code anomaly.
   [VERIFIED] Evidence: `plugins/webkul/chatter/src/ChatterPlugin.php` (lines 35–38)

2. **`payments` Plugin UI Absence**:
   `plugins/webkul/payments/src/PaymentPlugin.php` exists and registers discovery for `admin`, but its `src/` directory contains no `Filament/` folder. All payment transaction UI is currently handled through `accounts` and `invoices` clusters.
   [VERIFIED] Evidence: Directory inspection of `plugins/webkul/payments/src/`

3. **`analytics` Plugin UI Absence**:
   `plugins/webkul/analytics/` has no `*Plugin.php` class and no `src/Filament/` directory. It operates strictly as a headless backend data layer.
   [VERIFIED] Evidence: `plugins/webkul/analytics/src/AnalyticServiceProvider.php`

4. **Livewire Version Documentation Contradiction**:
   `AGENTS.md` states Livewire is on `v3`. Fresh inspection of `composer.lock` confirms Livewire is installed at `v4.3.3`. `AGENTS.md` is stale in this respect.
   [VERIFIED] Evidence: `composer.lock` → `livewire/livewire` (`v4.3.3`)

---

## Evidence Index

| Architectural Claim | Source File & Symbol | Verification Label |
| :--- | :--- | :---: |
| Admin Panel Configuration & MFA | `app/Providers/Filament/AdminPanelProvider.php` → `AdminPanelProvider::panel()` | [VERIFIED] |
| Customer Panel Configuration & Guard | `app/Providers/Filament/CustomerPanelProvider.php` → `CustomerPanelProvider::panel()` | [VERIFIED] |
| Plugin Lifecycle & Installation Gate | `plugins/webkul/plugin-manager/src/PackageServiceProvider.php` & `Package.php` | [VERIFIED] |
| NavigationGroup Enum Definition | `plugins/webkul/support/src/Enums/NavigationGroup.php` | [VERIFIED] |
| Universal TableViews Render Hook | `plugins/webkul/table-views/src/TableViewsServiceProvider.php` → `packageRegistered()` | [VERIFIED] |
| Universal Chatter Widget | `plugins/webkul/chatter/src/Filament/Widgets/ChatterWidget.php` | [VERIFIED] |
| Core FullCalendar Engine | `plugins/webkul/full-calendar/src/FullCalendarPlugin.php` | [VERIFIED] |
| Cross-Plugin Customer Account Cluster | `plugins/webkul/purchases/src/Filament/Customer/Clusters/Account/Resources/OrderResource.php` | [VERIFIED] |
| Dynamic Custom Field Trait | `plugins/webkul/fields/src/Filament/Traits/HasCustomFields.php` | [VERIFIED] |
| Partner Navigation Suppression | `plugins/webkul/partners/src/Filament/Resources/PartnerResource.php` (`$shouldRegisterNavigation = false`) | [VERIFIED] |
| Contact Partner Resurfacing | `plugins/webkul/contacts/src/Filament/Resources/PartnerResource.php` (`$shouldRegisterNavigation = true`) | [VERIFIED] |
| Master Inventory Counts (204 Res, 474 Pages, 46 Clu, 24 Wid) | Fresh directory & AST scan across `plugins/webkul/*/src/Filament/` | [VERIFIED] |
