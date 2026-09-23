---
status: verified
source_of_truth: source-code
last_verified: 2026-08-25
scope: global
confidence: high
---

# Aureus ERP — Architecture Overview

## Architectural shape

Aureus ERP is a Laravel 13 application that explicitly registers 28 local `plugins/webkul` service providers alongside the application and two Filament panel providers. Domain functionality is primarily implemented in those local plugin packages rather than in `app/`.

[VERIFIED]
Evidence: `composer.lock` → `laravel/framework` (`v13.31.0`); `bootstrap/providers.php`; `plugins/webkul/`

`bootstrap/app.php` configures the root web/API/console routes, web middleware additions, proxy trust, and exception renderers. `bootstrap/providers.php` is a concrete provider list: it registers `AppServiceProvider`, `AdminPanelProvider`, `CustomerPanelProvider`, all 28 plugin providers, and places `PluginManagerServiceProvider` last.

[VERIFIED]
Evidence: `bootstrap/app.php`; `bootstrap/providers.php`

At the application level, `app/Providers/AppServiceProvider.php` contains application bindings; `app/Providers/Filament/` owns panel configuration; and `app/Http/` contains shared middleware/controllers. Plugin packages conventionally hold their own `src/`, `database/`, `resources/`, `routes/`, `tests/`, and `composer.json`, although the directory mix varies by plugin.

[VERIFIED]
Evidence: `app/`; `plugins/webkul/accounts/`; `plugins/webkul/full-calendar/`

## Local-package and provider architecture

Each plugin's Composer definition supplies its package name, PSR-4 autoload mappings, and Laravel provider metadata. The root `composer.json` configures `wikimedia/composer-merge-plugin` to include `plugins/*/*/composer.json`. Thus the merge plugin brings local plugin Composer metadata—such as autoload mappings and package requirements—into Composer's merged configuration. It does not decide whether a plugin is installed or expose its UI.

[VERIFIED]
Evidence: `composer.json` → `extra.merge-plugin.include`; `plugins/webkul/accounts/composer.json`; `plugins/webkul/plugin-manager/composer.json`

Plugin service providers extend `Webkul\PluginManager\PackageServiceProvider`. During `register()`, that base class constructs a `Webkul\PluginManager\Package`, assigns its base path, calls the plugin provider's `configureCustomPackage()`, merges declared configuration files, merges local Filament Shield configuration where present, and calls `packageRegistered()`.

[VERIFIED]
Evidence: `plugins/webkul/plugin-manager/src/PackageServiceProvider.php` → `PackageServiceProvider::register()`

During `boot()`, the base provider registers publishable assets/configuration, commands, translations, views, and components as configured. Its route gate and its console-time migration loading gate use `Package::$isCore` or `Package::isInstalled()`; plugin-specific `packageBooted()` is then called. This is the repository's lifecycle; do not equate provider registration with runtime installation.

[VERIFIED]
Evidence: `plugins/webkul/plugin-manager/src/PackageServiceProvider.php` → `PackageServiceProvider::boot()`

### Verified registration lifecycle

1. At Composer install/update time, the configured merge plugin includes plugin `composer.json` files in the root Composer configuration.
2. Laravel registers the providers explicitly returned by `bootstrap/providers.php`.
3. Each plugin provider builds and configures a `Package`; the base provider then invokes `packageRegistered()`.
4. The provider boot phase loads configured package capabilities, with routes and console-time migrations gated by core/installed state.
5. Most `packageRegistered()` implementations register a `Panel::configureUsing()` callback. When a Filament panel is configured, that callback supplies a local `*Plugin` instance.
6. The local Filament plugin's `register(Panel $panel)` method performs panel-specific discovery/configuration and can reject an uninstalled optional plugin.

[VERIFIED]
Evidence: `composer.json` → `extra.merge-plugin.include`; `bootstrap/providers.php`; `plugins/webkul/plugin-manager/src/PackageServiceProvider.php` → `PackageServiceProvider::register()`, `PackageServiceProvider::boot()`; `plugins/webkul/accounts/src/AccountServiceProvider.php` → `AccountServiceProvider::packageRegistered()`; `plugins/webkul/accounts/src/AccountPlugin.php` → `AccountPlugin::make()`, `AccountPlugin::register()`

## Plugin installation and dependencies

`Package::isPluginInstalled()` loads records from the `plugins` table and returns true only when the named record has `is_installed` truthy. It returns false when the connection/schema is unavailable. The method does not inspect the model's `is_active` field.

[VERIFIED]
Evidence: `plugins/webkul/plugin-manager/src/Package.php` → `Package::isPluginInstalled()`; `plugins/webkul/plugin-manager/src/Models/Plugin.php`

`Package::hasDependencies([...])` populates the package's in-memory `dependencies` list. It is a runtime plugin dependency declaration, not a Composer dependency. The plugin-manager `InstallCommand` can recursively invoke each dependency's `:install` command before installing the requested package; after installation it persists relationships in `plugin_dependencies`. It does not alter Composer's dependency resolver.

[VERIFIED]
Evidence: `plugins/webkul/plugin-manager/src/Package.php` → `Package::hasDependencies()`; `plugins/webkul/plugin-manager/src/Console/Commands/InstallCommand.php` → `InstallCommand::handle()`; `plugins/webkul/plugin-manager/src/Models/Plugin.php` → `Plugin::dependencies()`

The core flag is set by `Package::isCore()`. It is separate from both Composer metadata and the installation-table state. See the complete verified list and declared runtime dependencies in `docs/architecture/plugin-registry.md`.

## Filament panel architecture

The repository configures two Filament panels:

| Panel | Provider | ID | Path | Direct configuration evidence |
| --- | --- | --- | --- | --- |
| Admin | `app/Providers/Filament/AdminPanelProvider.php` | `admin` | `admin` | `AdminPanelProvider::panel()` |
| Customer | `app/Providers/Filament/CustomerPanelProvider.php` | `customer` | `/` | `CustomerPanelProvider::panel()` |

The admin panel is the default panel and adds its authentication middleware, database notifications, global search provider, navigation groups, Filament Shield plugin, and multi-factor application authentication. The customer panel uses the `customer` guard and its own password broker. These are panel configuration facts, not a substitute for security architecture documentation.

[VERIFIED]
Evidence: `app/Providers/Filament/AdminPanelProvider.php` → `AdminPanelProvider::panel()`; `app/Providers/Filament/CustomerPanelProvider.php` → `CustomerPanelProvider::panel()`

Most plugin providers call `Panel::configureUsing()` from `packageRegistered()` and add a local Filament plugin instance with `$panel->plugin(SomePlugin::make())`. The common `make()` implementation resolves the class from Laravel's container. The corresponding `*Plugin.php` implements `Filament\Contracts\Plugin`; its `register(Panel $panel)` method controls which resources, pages, clusters, widgets, or panel options it contributes. Optional plugins normally check `Package::isPluginInstalled()` inside that method before contributing UI. Core plugin behaviour and special providers must be checked individually.

[VERIFIED]
Evidence: `plugins/webkul/accounts/src/AccountServiceProvider.php` → `AccountServiceProvider::packageRegistered()`; `plugins/webkul/accounts/src/AccountPlugin.php` → `AccountPlugin::register()`; `plugins/webkul/chatter/src/ChatterPlugin.php` → `ChatterPlugin::register()`

Two special cases prevent a blanket “every plugin has a Filament plugin class” claim: `analytics` has no local `*Plugin.php` registration, and `table-views` registers a Filament render hook rather than a local `Filament\Contracts\Plugin` instance.

[VERIFIED]
Evidence: `plugins/webkul/analytics/src/AnalyticServiceProvider.php`; `plugins/webkul/table-views/src/TableViewsServiceProvider.php` → `TableViewsServiceProvider::packageRegistered()`

## Cross-plugin investigation boundary

Plugins can couple through provider hooks, Laravel events/listeners, and dynamically registered Eloquent relations. These mechanisms mean that a class's directory is not a complete dependency boundary. Before changing a shared model or an event, search for its direct consumers and `resolveRelationUsing` registrations. Detailed event, workflow, database, and security behaviour remains outside this phase.

[VERIFIED]
Evidence: `plugins/webkul/sales/src/SaleServiceProvider.php` → `SaleServiceProvider::packageBooted()`; repository search for `resolveRelationUsing`

## Background-job observation

[VERIFIED] No PHP files were found under `app/Jobs` or `plugins/webkul/*/src/Jobs`, and no `ShouldQueue` implementation was found in `app/` or `plugins/webkul/` during this verification. This observation does not establish whether queues are used indirectly by dependencies or infrastructure.
