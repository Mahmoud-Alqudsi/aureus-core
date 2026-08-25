---
status: verified
source_of_truth: source-code
last_verified: 2026-08-25
scope: global
confidence: high
---

# Aureus ERP — Plugin Registry

## Registration model

The repository contains 28 directories immediately below `plugins/webkul/`, and `bootstrap/providers.php` explicitly imports and returns a corresponding provider for each one. Providers are registered whether their package is core or optional; core/installed checks occur later in the package and Filament-plugin lifecycle.

[VERIFIED]
Evidence: `plugins/webkul/`; `bootstrap/providers.php`; `plugins/webkul/plugin-manager/src/PackageServiceProvider.php`

Each provider configures a `Webkul\PluginManager\Package`. `Package::isCore()` sets the core flag. `Package::hasDependencies()` records runtime plugin names. The base provider gates routes and console-time migration loading on core status or `Package::isInstalled()`, while many optional local Filament plugins independently return early unless `Package::isPluginInstalled($this->getId())` is true.

[VERIFIED]
Evidence: `plugins/webkul/plugin-manager/src/Package.php`; `plugins/webkul/plugin-manager/src/PackageServiceProvider.php`; `plugins/webkul/accounts/src/AccountPlugin.php` → `AccountPlugin::register()`

## Composer, registration, installation, and dependency ordering are distinct

| Concern | Repository mechanism | What it controls |
| --- | --- | --- |
| PHP autoload and external PHP packages | Root `extra.merge-plugin.include` includes every plugin `composer.json` | Composer sees merged plugin Composer metadata, including PSR-4 mappings and any Composer requirements. |
| Laravel provider registration | `bootstrap/providers.php` | Explicit application registration of all local plugin service providers. Plugin Composer files also declare provider metadata. |
| Installed state | `Package::isPluginInstalled()` | Reads the `plugins` table's `is_installed` state; unavailable database/schema produces `false`. |
| Core state | `Package::isCore()` | Allows core packages through the base provider's route/migration gates without the installed-state check. |
| Runtime plugin dependencies and install order | `Package::hasDependencies()` and `InstallCommand` | The install command may install named dependencies first and persists dependency rows; it is not Composer resolution. |
| Filament contribution | `Panel::configureUsing()` plus local `*Plugin.php::register()` | Adds Filament plugin instances; each class determines its panel-specific discovery/configuration and may enforce installed-state checks. |

[VERIFIED]
Evidence: `composer.json` → `extra.merge-plugin`; `bootstrap/providers.php`; `plugins/webkul/plugin-manager/src/Package.php`; `plugins/webkul/plugin-manager/src/PackageServiceProvider.php`; `plugins/webkul/plugin-manager/src/Console/Commands/InstallCommand.php` → `InstallCommand::handle()`

## Verified registry

All entries below are registered in `bootstrap/providers.php`. “Runtime dependencies” lists only names passed to `hasDependencies()`; an em dash means no call was found in that provider. This table does not claim the plugin is installed in any particular database.

| Plugin | Provider | Core state | Runtime dependencies |
| --- | --- | --- | --- |
| `accounting` | `Webkul\Accounting\AccountingServiceProvider` | Optional | `accounts` |
| `accounts` | `Webkul\Account\AccountServiceProvider` | Optional | `products` |
| `analytics` | `Webkul\Analytic\AnalyticServiceProvider` | Core | — |
| `barcode` | `Webkul\Barcode\BarcodeServiceProvider` | Optional | `inventories` |
| `blogs` | `Webkul\Blog\BlogServiceProvider` | Optional | `website` |
| `chatter` | `Webkul\Chatter\ChatterServiceProvider` | Core | — |
| `contacts` | `Webkul\Contact\ContactServiceProvider` | Optional | — |
| `employees` | `Webkul\Employee\EmployeeServiceProvider` | Optional | — |
| `fields` | `Webkul\Field\FieldServiceProvider` | Core | — |
| `full-calendar` | `Webkul\FullCalendar\FullCalendarServiceProvider` | Core | — |
| `inventories` | `Webkul\Inventory\InventoryServiceProvider` | Optional | `products` |
| `invoices` | `Webkul\Invoice\InvoiceServiceProvider` | Optional | `accounts` |
| `maintenance` | `Webkul\Maintenance\MaintenanceServiceProvider` | Optional | — |
| `manufacturing` | `Webkul\Manufacturing\ManufacturingServiceProvider` | Optional | `products`, `inventories` |
| `partners` | `Webkul\Partner\PartnerServiceProvider` | Core | — |
| `payments` | `Webkul\Payment\PaymentServiceProvider` | Optional | `accounts` |
| `plugin-manager` | `Webkul\PluginManager\PluginManagerServiceProvider` | Core | — |
| `products` | `Webkul\Product\ProductServiceProvider` | Optional | — |
| `projects` | `Webkul\Project\ProjectServiceProvider` | Optional | — |
| `purchases` | `Webkul\Purchase\PurchaseServiceProvider` | Optional | `invoices` |
| `recruitments` | `Webkul\Recruitment\RecruitmentServiceProvider` | Optional | `employees` |
| `sales` | `Webkul\Sale\SaleServiceProvider` | Optional | `invoices`, `payments` |
| `security` | `Webkul\Security\SecurityServiceProvider` | Core | — |
| `support` | `Webkul\Support\SupportServiceProvider` | Core | — |
| `table-views` | `Webkul\TableViews\TableViewsServiceProvider` | Core | — |
| `time-off` | `Webkul\TimeOff\TimeOffServiceProvider` | Optional | `employees` |
| `timesheets` | `Webkul\Timesheet\TimesheetServiceProvider` | Optional | `projects` |
| `website` | `Webkul\Website\WebsiteServiceProvider` | Optional | — |

[VERIFIED]
Evidence: `bootstrap/providers.php`; repository search for `->isCore(` and `->hasDependencies(` in `plugins/webkul/*/src/*ServiceProvider.php`

The verified core set is: `analytics`, `chatter`, `fields`, `full-calendar`, `partners`, `plugin-manager`, `security`, `support`, and `table-views` (9 total). The remaining 19 entries are optional.

[VERIFIED]
Evidence: the nine providers calling `Package::isCore()` in `plugins/webkul/*/src/*ServiceProvider.php`

## Filament registration lifecycle

For providers that define `packageRegistered()`, the base `PackageServiceProvider::register()` calls it after package configuration. Most such methods add a local Filament plugin with `Panel::configureUsing()`. Once the panel is being configured, the local plugin's `register(Panel $panel)` method can discover resources/pages/clusters/widgets and inspect `Panel::getId()` to separate admin and customer behaviour. The class can also return immediately when the plugin is not installed.

This explains an important distinction: a provider can register a Filament plugin instance even when an optional plugin contributes no discovered UI because its own `register()` installed-state guard returns early.

[VERIFIED]
Evidence: `plugins/webkul/plugin-manager/src/PackageServiceProvider.php` → `PackageServiceProvider::register()`; `plugins/webkul/accounts/src/AccountServiceProvider.php` → `AccountServiceProvider::packageRegistered()`; `plugins/webkul/accounts/src/AccountPlugin.php` → `AccountPlugin::register()`

Panel participation must be determined from each local plugin implementation. For example, `AccountPlugin` contributes only to `admin`; `PurchasePlugin` and `WebsitePlugin` contain explicit branches for both `admin` and `customer`; `ChatterPlugin` and `FullCalendarPlugin` register without a panel-ID condition. `analytics` has no local Filament plugin class, and `table-views` registers a render hook instead.

[VERIFIED]
Evidence: `plugins/webkul/accounts/src/AccountPlugin.php`; `plugins/webkul/purchases/src/PurchasePlugin.php`; `plugins/webkul/website/src/WebsitePlugin.php`; `plugins/webkul/chatter/src/ChatterPlugin.php`; `plugins/webkul/full-calendar/src/FullCalendarPlugin.php`; `plugins/webkul/analytics/src/AnalyticServiceProvider.php`; `plugins/webkul/table-views/src/TableViewsServiceProvider.php`

## Source reading for plugin work

Before editing a plugin, read its `composer.json`, service provider, any `*Plugin.php`, and the corresponding entry in `bootstrap/providers.php`. Then inspect only directly involved migrations, routes, tests, and coupled code. This registry deliberately does not attempt to document plugin-specific workflows, database design, security behaviour, or a complete event catalog; those are outside Phase 2.
