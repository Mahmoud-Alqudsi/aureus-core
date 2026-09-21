---
status: verified
source_of_truth: source-code
last_verified: 2026-08-29
scope: plugins/webkul/support
confidence: high
---

# Plugin: Support (`support`)

## Status
[VERIFIED]
Active Core Module. Registered explicitly in `bootstrap/providers.php:38` as `Webkul\Support\SupportServiceProvider::class`.

## Core/Optional
[VERIFIED]
**Core Plugin**. Configured via `$package->isCore()` within `SupportServiceProvider::configureCustomPackage()` (`plugins/webkul/support/src/SupportServiceProvider.php:43`).

## Enabled/Disabled
[VERIFIED]
**Always Enabled (Core Infrastructure)**. Core plugin status causes `PackageServiceProvider::boot()` to bypass database installation checks (`Package::isInstalled()`) when loading migrations and routes (`plugins/webkul/plugin-manager/src/PackageServiceProvider.php:131-135, 202`). The `support` module executes unconditionally at boot time across the application.

## Purpose
[VERIFIED]
The `support` module is the primary architectural bedrock and shared foundation for Aureus ERP. It provides foundational multi-tenant data isolation, thread-safe document sequencing, universal master data models, cross-database SQL abstractions, schema injection pipelines, and core UI utilities:

1. **Multi-Company Architecture & Tenant Isolation**:
   - Manages active tenant context resolution via `CompanyContext` (`plugins/webkul/support/src/Services/CompanyContext.php`), storing active company selections in session key `active_company_ids`.
   - Defines standard query scopes: `CompanyScope` (`plugins/webkul/support/src/Models/Scopes/CompanyScope.php`), `CompaniesScope` (`plugins/webkul/support/src/Models/Scopes/CompaniesScope.php`), and `AllowedCompanyScope` (`plugins/webkul/support/src/Models/Scopes/AllowedCompanyScope.php`).
   - Supplies model scoping traits: `BelongsToCompany` (`plugins/webkul/support/src/Traits/BelongsToCompany.php`), `BelongsToCompanies` (`plugins/webkul/support/src/Traits/BelongsToCompanies.php`), and `RestrictToAllowedCompanies` (`plugins/webkul/support/src/Traits/RestrictToAllowedCompanies.php`).
   - Enforces referential company boundaries via `CompanyConsistencyGuard` (`plugins/webkul/support/src/Support/CompanyConsistencyGuard.php`), `ChecksCompanyConsistency` (`plugins/webkul/support/src/Traits/ChecksCompanyConsistency.php`), and `CrossCompanyException` (`plugins/webkul/support/src/Exceptions/CrossCompanyException.php`).
   - Integrates the global company switcher UI into Filament via `PanelsRenderHook::GLOBAL_SEARCH_BEFORE` (`plugins/webkul/support/resources/views/company-switcher.blade.php`).

2. **Thread-Safe Document Sequencing Engine**:
   - Provides `SequenceService` (`plugins/webkul/support/src/Services/SequenceService.php`) and `Sequence` model (`plugins/webkul/support/src/Models/Sequence.php`) to generate serialized, formatted identifiers (e.g. `SO/2026/00001`, `INV/2026/00001`, `PO/00001`, `WH/IN/00001`) with configurable prefixes, suffixes, padding, auto-reset cadences (yearly, monthly, never), and pessimistic database row locking (`lockForUpdate()`).

3. **Core Master & Reference Data Models**:
   - Houses foundational entity definitions: `Company`, `Currency`, `CurrencyRate`, `Country`, `State`, `Bank`, `UOMCategory`, `UOM`, `Calendar`, `CalendarAttendance`, `CalendarLeave`, `ActivityPlan`, `ActivityPlanTemplate`, `ActivityType`, `ActivityTypeSuggestion`, `UtmStage`, `UTMMedium`, `UTMSource`, `UtmCampaign`, `QuickNavigationFavorite`, and `EmailLog`.

4. **Dynamic Schema Extension Engine**:
   - Implements `SchemaRegistry` (`plugins/webkul/support/src/Services/SchemaRegistry.php`) allowing third-party and optional plugins to inject, modify, or reorder Filament form and infolist schemas without modifying core resource files.

5. **Cross-Database Dialect Abstraction**:
   - Provides `DatabaseDialect` interface (`plugins/webkul/support/src/Database/Dialects/DatabaseDialect.php`) with driver-specific implementations for MySQL/MariaDB (`MySqlDialect`) and PostgreSQL (`PostgresDialect`) covering JSON array aggregation, month bucketing, case-insensitive comparison, column type alteration, and PostgreSQL sequence synchronization.

6. **Company-Aware Settings Management**:
   - Extends Spatie Laravel Settings via `CompanyAwareSettingsRepository` (`plugins/webkul/support/src/Settings/CompanyAwareSettingsRepository.php`) and `SettingsRegistry` (`plugins/webkul/support/src/SettingsRegistry.php`) to support company-scoped configuration overrides (specifically `accounts_accounts` and `accounts_taxes`).

7. **Filament UI Enhancements & Quick Navigation**:
   - Delivers the keyboard-driven `QuickNavigation` Livewire modal palette (`plugins/webkul/support/src/Livewire/QuickNavigation.php`, `plugins/webkul/support/src/Services/QuickNavigator.php`) with favorites and recent history tracking (`QuickNavigationFavorites`, `QuickNavigationRecents`).
   - Implements custom form/infolist components (`ProgressBarEntry`, `DashboardDateRange`, custom table repeaters) and table summarizers (`Sum`, `Average`, `Count`, `Range`).
   - Implements RTL language support and bidirectional stylesheet injection (`HasRtlSupport`, `prepare_rtl_html()`).
   - Implements `GlobalSearchProvider` (`plugins/webkul/support/src/GlobalSearchProvider.php`) for sorted multi-resource global search results.

8. **Image Processing & Document Generation**:
   - Compiles and caches dynamic Glide images via `ImageService` (`plugins/webkul/support/src/Services/ImageService.php`), `ImageController`, and `ImageCacheController`.
   - Generates and downloads PDF documents with RTL text normalization via `PDFHandler` (`plugins/webkul/support/src/Traits/PDFHandler.php`).

9. **REST API Infrastructure**:
   - Provides versioned REST API v1 endpoints for currencies, currency rates, banks, countries, states, UOM categories, and UOMs, documented via Scribe with custom OpenAPI generator `ScalarOpenApiGenerator` (`plugins/webkul/support/src/ScalarOpenApiGenerator.php`).

## Service Provider
[VERIFIED]
- **Class**: `Webkul\Support\SupportServiceProvider` (`plugins/webkul/support/src/SupportServiceProvider.php:30`)
- **Inheritance**: Extends `Webkul\PluginManager\PackageServiceProvider`
- **Traits Used**:
  - `Webkul\Support\Traits\HasFilamentDefaults`
  - `Webkul\Support\Traits\HasRouterMacros`
  - `Webkul\Support\Traits\HasRtlSupport`
- **Registration & Configuration**:
  - `configureCustomPackage(Package $package)`:
    - Sets package name to `'support'`
    - Declares package as core (`$package->isCore()`)
    - Configures views (`hasViews()`), translations (`hasTranslations()`), and routes (`hasRoutes(['api', 'web'])`)
    - Registers 35 database migrations via `hasMigrations([...])` and enables execution via `runsMigrations()`
    - Registers settings migration `'2026_06_12_000001_create_brand_settings'` via `hasSettings([...])` and enables execution via `runsSettings()`
    - Registers root seeder `'Webkul\Support\Database\Seeders\DatabaseSeeder'` via `hasSeeder()`
  - `packageRegistered()`:
    - Scopes `Webkul\Support\SettingsRegistry` in the service container
    - Binds `Webkul\Support\Database\Dialects\DatabaseDialect` singleton dynamically (`MySqlDialect` for mysql/mariadb, `PostgresDialect` for pgsql)
    - Hooks into panel configuration via `Panel::configureUsing()`, registering `SupportPlugin::make()`
    - Scopes `Webkul\Support\Services\CompanyContext` in the service container
    - Registers language switcher configuration (`registerLanguageSwitch()`)
    - Registers header version render hook (`PanelsRenderHook::USER_MENU_PROFILE_BEFORE`) via `registerHooks()`
    - Registers router macros (`softDeletableApiResource`) via `registerRouterMacros()`
  - `packageBooted()`:
    - Registers global Gate before-rule: intercepts ability `'bypass_company_scope'` and returns `true` if the authenticated user has the `super_admin` role (`SupportServiceProvider.php:94-104`)
    - Registers Livewire component `'accept-invitation'` (`Webkul\Security\Livewire\AcceptInvitation::class`)
    - Registers Web route `POST company-context/set` (`CompanyContextController::class, 'set'`)
    - Registers Filament render hook `PanelsRenderHook::GLOBAL_SEARCH_BEFORE` to inject `support::company-switcher` in the `admin` panel
    - Registers Livewire component `'quick-navigation'` (`Webkul\Support\Livewire\QuickNavigation::class`)
    - Deliberately registers security's `RolePolicy` for `Webkul\Security\Models\Role` via `Gate::policy(Role::class, RolePolicy::class)`
    - Registers route `GET cache/{filename}` (`ImageCacheController@getImage`)
    - Registers Filament CSS asset `'support'` (`resources/dist/support.css`)
    - Configures Filament form defaults (forcing `columnSpanFull()` on `Fieldset`, `Grid`, `Section`)
    - Configures RTL view composers, Blade directives (`@rtl`, `@direction`), and render hooks (`PanelsRenderHook::BODY_START`, `PanelsRenderHook::HEAD_END`)

## Filament Plugin class
[VERIFIED]
- **Class**: `Webkul\Support\SupportPlugin` (`plugins/webkul/support/src/SupportPlugin.php:12`)
- **Interface**: Implements `Filament\Contracts\Plugin`
- **Identifier**: `getId()` returns `'support'`
- **Panel Registration**:
  - `register(Panel $panel)` executes conditionally when `$panel->getId() == 'admin'` (`plugins/webkul/support/src/SupportPlugin.php:27`):
    - Enables password reset on panel (`$panel->passwordReset()`)
    - Discovers resources in `src/Filament/Resources` under namespace `Webkul\Support\Filament\Resources`
    - Discovers pages in `src/Filament/Pages` under namespace `Webkul\Support\Filament\Pages`
    - Discovers clusters in `src/Filament/Clusters` under namespace `Webkul\Support\Filament\Clusters`
    - [INFERRED] Calls `->discoverClusters(in: __DIR__.'/Filament/Widgets', for: 'Webkul\\Support\\Filament\\Widgets')` (`plugins/webkul/support/src/SupportPlugin.php:41-44`). This is inferred to be an authoring copy-paste typo for `discoverWidgets()`, which results in no effect at runtime because widget classes do not extend `Filament\Clusters\Cluster`.
    - Registers render hook `'panels::body.end'` to render `@livewire('quick-navigation')` when the user is authenticated
- **Boot**:
  - `boot(Panel $panel)` registers render hook `'panels::scripts.before'` with inline JavaScript listening to `'livewire:navigated'` to auto-scroll the active sidebar menu item (`nav .fi-sidebar-item-active`) into view.

## Composer dependencies
[VERIFIED]
- **Local Package Definition**: `plugins/webkul/support/composer.json`
  - Name: `webkul/support`
  - Autoload PSR-4: `Webkul\Support\` -> `src/`, `Webkul\Support\Database\Factories\` -> `database/factories/`, `Webkul\Support\Database\Seeders\` -> `database/seeders/`
  - Autoload Files: `src/helpers.php`
  - Autoload-dev PSR-4: `Webkul\Support\Tests\` -> `tests/`
  - Extra Laravel Providers: `Webkul\Support\SupportServiceProvider`
- **External Dependencies Consumed via Root Composer** (`composer.lock`):
  - `spatie/laravel-package-tools` (`v1.93.0`): Extends `BasePackage` and `BasePackageServiceProvider`.
  - `spatie/laravel-settings` (`v3.4.4`): Powers `BrandSettings` and `CompanyAwareSettingsRepository`.
  - `spatie/laravel-query-builder` (`v6.3.3`): Powers filtering, sorting, and relationship inclusion in API V1 controllers.
  - `filament/filament` (`v5.7.6`): Filament resources, pages, clusters, widgets, forms, tables, infolists, and assets.
  - `bezhansalleh/filament-shield` (`4.2.0`): Policy authorization and permissions integration.
  - `bezhansalleh/filament-language-switch` (`v4.0.0`): Multilingual locale switching.
  - `barryvdh/laravel-dompdf` (`v3.1.1`): Powers `PDFHandler` HTML-to-PDF rendering.
  - `league/glide` (`2.3.0`): Powers `ImageService` on-the-fly image transformations and caching.
  - `knuckleswtf/scribe` (`v5.2.0`): Powers REST API documentation generation with `ScalarOpenApiGenerator`.
  - `khaled.alshamaa/ar-php` (`6.4.4`): Used in `prepare_rtl_html()` helper for Arabic glyph reshaping and RTL table restructuring.

## Runtime plugin dependencies
[VERIFIED]
None (`—`). `SupportServiceProvider::configureCustomPackage()` does not declare any runtime dependencies via `hasDependencies()` or `hasDependency()`.

## Directory structure
[VERIFIED]
Full verified tree of `plugins/webkul/support/`:

```text
plugins/webkul/support/
├── .gitignore
├── composer.json
├── package.json
├── postcss.config.js
├── config/
│   └── filament-shield.php
├── database/
│   ├── factories/
│   │   ├── ActivityPlanTemplateFactory.php
│   │   ├── ActivityTypeSuggestionFactory.php
│   │   ├── BankFactory.php
│   │   ├── CalendarAttendanceFactory.php
│   │   ├── CalendarFactory.php
│   │   ├── CalendarLeaveFactory.php
│   │   ├── CompanyFactory.php
│   │   ├── CountryFactory.php
│   │   ├── CurrencyFactory.php
│   │   ├── CurrencyRateFactory.php
│   │   ├── EmailLogFactory.php
│   │   ├── EmailTemplateFactory.php
│   │   ├── StateFactory.php
│   │   ├── UOMCategoryFactory.php
│   │   ├── UOMFactory.php
│   │   ├── UTMMediumFactory.php
│   │   ├── UTMSourceFactory.php
│   │   ├── UtmCampaignFactory.php
│   │   └── UtmStageFactory.php
│   ├── migrations/
│   │   ├── 2024_11_05_105112_create_plugin_dependencies_table.php
│   │   ├── 2024_12_06_061927_create_currencies_table.php
│   │   ├── 2024_12_10_092651_create_countries_table.php
│   │   ├── 2024_12_10_092657_create_companies_table.php
│   │   ├── 2024_12_10_092657_create_states_table.php
│   │   ├── 2024_12_10_100944_create_user_allowed_companies_table.php
│   │   ├── 2024_12_10_101420_create_banks_table.php
│   │   ├── 2024_12_12_114620_create_activity_plans_table.php
│   │   ├── 2024_12_12_115256_create_activity_types_table.php
│   │   ├── 2024_12_12_115728_create_activity_plan_templates_table.php
│   │   ├── 2024_12_17_082318_create_activity_type_suggestions_table.php
│   │   ├── 2025_01_03_061445_create_email_logs_table.php
│   │   ├── 2025_01_03_105625_create_unit_of_measure_categories_table.php
│   │   ├── 2025_01_03_105627_create_unit_of_measures_table.php
│   │   ├── 2025_01_07_125015_add_partner_id_to_companies_table.php
│   │   ├── 2025_01_09_111545_create_utm_mediums_table.php
│   │   ├── 2025_01_09_114324_create_utm_sources_table.php
│   │   ├── 2025_01_10_094256_create_utm_stages_table.php
│   │   ├── 2025_01_10_094325_create_utm_campaigns_table.php
│   │   ├── 2025_04_04_061507_add_address_columns_in_companies_table.php
│   │   ├── 2025_04_04_062023_alter_companies_table.php
│   │   ├── 2025_08_08_104317_alter_utm_stages_table.php
│   │   ├── 2025_08_08_104814_alter_utm_campaigns_table.php
│   │   ├── 2025_10_10_080114_create_currency_rates_table.php
│   │   ├── 2025_11_14_102615_alter_currency_rates_table.php
│   │   ├── 2026_03_09_000001_add_unique_index_to_companies_name.php
│   │   ├── 2026_03_18_000001_alter_unit_of_measures_factor_precision.php
│   │   ├── 2026_04_02_000001_create_calendars_table.php
│   │   ├── 2026_04_29_065935_add_resource_columns_in_calendar_leaves_table.php
│   │   ├── 2026_05_01_065935_add_resource_columns_in_calendar_attendances_table.php
│   │   ├── 2026_07_10_000000_fix_unit_of_measures_factor_precision.php
│   │   ├── 2026_07_16_000001_create_quick_navigation_favorites_table.php
│   │   ├── 2026_07_30_110000_null_company_on_utm_campaigns.php
│   │   └── 2026_08_03_120000_create_sequences_table.php
│   ├── seeders/
│   │   ├── ActivityPlanSeeder.php
│   │   ├── ActivityTypeSeeder.php
│   │   ├── CalendarAttendanceSeeder.php
│   │   ├── CalendarSeeder.php
│   │   ├── CompanySeeder.php
│   │   ├── CountrySeeder.php
│   │   ├── CurrencySeeder.php
│   │   ├── DatabaseSeeder.php
│   │   ├── StateSeeder.php
│   │   ├── UOMCategorySeeder.php
│   │   ├── UOMSeeder.php
│   │   ├── UTMMediumSeeder.php
│   │   ├── UTMSourceSeeder.php
│   │   ├── UtmCampaignSeeder.php
│   │   └── UtmStageSeeder.php
│   └── settings/
│       └── 2026_06_12_000001_create_brand_settings.php
├── resources/
│   ├── css/
│   │   └── index.css
│   ├── dist/
│   │   └── support.css
│   ├── lang/
│   │   ├── ar/
│   │   ├── en/
│   │   ├── es/
│   │   ├── fr/
│   │   └── pt_BR/
│   └── views/
│       ├── company-switcher.blade.php
│       ├── quick-navigation.blade.php
│       ├── components/
│       │   ├── column-manager.blade.php
│       │   └── emails/
│       │       └── layout.blade.php
│       ├── filament/
│       │   ├── forms/components/repeater/table.blade.php
│       │   ├── infolists/components/repeatable-entry/table.blade.php
│       │   └── widgets/record-navigation-tabs.blade.php
│       ├── pages/
│       │   ├── help.blade.php
│       │   ├── profile.blade.php
│       │   └── partials/help-card.blade.php
│       ├── rtl/
│       │   ├── script.blade.php
│       │   └── styles.blade.php
│       └── tables/
│           ├── columns/progress-bar-entry.blade.php
│           └── infolists/progress-bar-entry.blade.php
├── routes/
│   ├── api.php
│   └── web.php
├── src/
│   ├── GlobalSearchProvider.php
│   ├── helpers.php
│   ├── ScalarOpenApiGenerator.php
│   ├── SettingsRegistry.php
│   ├── SupportPlugin.php
│   ├── SupportServiceProvider.php
│   ├── Database/
│   │   └── Dialects/
│   │       ├── DatabaseDialect.php
│   │       ├── MySqlDialect.php
│   │       └── PostgresDialect.php
│   ├── Enums/
│   │   ├── ActivityChainingType.php
│   │   ├── ActivityDecorationType.php
│   │   ├── ActivityDelayFrom.php
│   │   ├── ActivityDelayInterval.php
│   │   ├── ActivityDelayUnit.php
│   │   ├── ActivityResponsibleType.php
│   │   ├── ActivityTypeAction.php
│   │   ├── CalendarDisplayType.php
│   │   ├── CompanyStatus.php
│   │   ├── DayOfWeek.php
│   │   ├── DayPeriod.php
│   │   ├── NavigationGroup.php
│   │   ├── SequenceResetFrequency.php
│   │   ├── UOMType.php
│   │   ├── Week.php
│   │   └── WeekType.php
│   ├── Exceptions/
│   │   └── CrossCompanyException.php
│   ├── Filament/
│   │   ├── Clusters/
│   │   │   ├── Settings.php
│   │   │   └── Settings/Pages/ManageBranding.php
│   │   ├── Concerns/
│   │   │   ├── CanBeHidden.php
│   │   │   ├── CanBeSummarized.php
│   │   │   ├── HandlesCrossCompanyException.php
│   │   │   ├── HasRepeatableEntryColumnManager.php
│   │   │   ├── HasRepeaterColumnManager.php
│   │   │   ├── HasTranslationFallback.php
│   │   │   ├── TranslatableCreateRecord.php
│   │   │   ├── TranslatableEditRecord.php
│   │   │   ├── TranslatableListRecords.php
│   │   │   ├── TranslatableManageRecords.php
│   │   │   └── TranslatableViewRecord.php
│   │   ├── Contributions/
│   │   │   ├── AbstractSchemaRegistry.php
│   │   │   └── SchemaRegistry.php
│   │   ├── Forms/Components/
│   │   │   ├── DashboardDateRange.php
│   │   │   ├── Repeater.php
│   │   │   └── Repeater/TableColumn.php
│   │   ├── Infolists/Components/
│   │   │   ├── RepeatableEntry.php
│   │   │   └── Repeater/TableColumn.php
│   │   ├── Pages/
│   │   │   ├── Help.php
│   │   │   └── Profile.php
│   │   ├── Resources/
│   │   │   ├── ActivityTypeResource.php
│   │   │   ├── ActivityTypeResource/
│   │   │   ├── BankResource.php
│   │   │   ├── BankResource/
│   │   │   ├── CalendarResource.php
│   │   │   ├── CalendarResource/
│   │   │   ├── CompanyResource.php
│   │   │   ├── CompanyResource/
│   │   │   ├── CountryResource.php
│   │   │   ├── CurrencyResource.php
│   │   │   ├── CurrencyResource/
│   │   │   ├── SequenceResource.php
│   │   │   ├── SequenceResource/
│   │   │   ├── StateResource.php
│   │   │   ├── UOMCategoryResource.php
│   │   │   └── UOMCategoryResource/
│   │   ├── Summarizers/
│   │   │   ├── Average.php
│   │   │   ├── Count.php
│   │   │   ├── Range.php
│   │   │   ├── Sum.php
│   │   │   └── Summarizer.php
│   │   ├── Tables/
│   │   │   ├── Columns/ProgressBarEntry.php
│   │   │   └── Infolists/ProgressBarEntry.php
│   │   ├── TranslatableContentDriver.php
│   │   └── Widgets/
│   │       └── RecordNavigationTabs.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── CompanyContextController.php
│   │   │   ├── ImageCacheController.php
│   │   │   ├── ImageController.php
│   │   │   └── API/V1/
│   │   │       ├── Controller.php
│   │   │       ├── BankController.php
│   │   │       ├── CountryController.php
│   │   │       ├── CurrencyController.php
│   │   │       ├── CurrencyRateController.php
│   │   │       ├── StateController.php
│   │   │       ├── UOMCategoryController.php
│   │   │       └── UOMController.php
│   │   ├── Requests/
│   │   │   ├── BankRequest.php
│   │   │   ├── CountryRequest.php
│   │   │   ├── CurrencyRateRequest.php
│   │   │   ├── CurrencyRequest.php
│   │   │   ├── StateRequest.php
│   │   │   ├── UOMCategoryRequest.php
│   │   │   └── UOMRequest.php
│   │   └── Resources/V1/
│   │       ├── ActivityPlanResource.php
│   │       ├── ActivityPlanTemplateResource.php
│   │       ├── ActivityTypeResource.php
│   │       ├── ActivityTypeSuggestionResource.php
│   │       ├── BankResource.php
│   │       ├── CalendarAttendanceResource.php
│   │       ├── CalendarResource.php
│   │       ├── CompanyResource.php
│   │       ├── CountryResource.php
│   │       ├── CurrencyRateResource.php
│   │       ├── CurrencyResource.php
│   │       ├── EmailLogResource.php
│   │       ├── EmailTemplateResource.php
│   │       ├── StateResource.php
│   │       ├── UOMCategoryResource.php
│   │       ├── UOMResource.php
│   │       ├── UTMMediumResource.php
│   │       ├── UTMSourceResource.php
│   │       ├── UtmCampaignResource.php
│   │       └── UtmStageResource.php
│   ├── Livewire/
│   │   └── QuickNavigation.php
│   ├── Models/
│   │   ├── ActivityPlan.php
│   │   ├── ActivityPlanTemplate.php
│   │   ├── ActivityType.php
│   │   ├── ActivityTypeSuggestion.php
│   │   ├── Bank.php
│   │   ├── Calendar.php
│   │   ├── CalendarAttendance.php
│   │   ├── CalendarLeave.php
│   │   ├── Company.php
│   │   ├── Concerns/HasContributedAttributes.php
│   │   ├── Country.php
│   │   ├── Currency.php
│   │   ├── CurrencyRate.php
│   │   ├── EmailLog.php
│   │   ├── EmailTemplate.php
│   │   ├── QuickNavigationFavorite.php
│   │   ├── Scopes/
│   │   │   ├── AllowedCompanyScope.php
│   │   │   ├── CompaniesScope.php
│   │   │   └── CompanyScope.php
│   │   ├── Sequence.php
│   │   ├── State.php
│   │   ├── UOM.php
│   │   ├── UOMCategory.php
│   │   ├── UTMMedium.php
│   │   ├── UTMSource.php
│   │   ├── UtmCampaign.php
│   │   └── UtmStage.php
│   ├── Policies/
│   │   ├── ActivityPlanPolicy.php
│   │   ├── ActivityTypePolicy.php
│   │   ├── BankPolicy.php
│   │   ├── CalendarPolicy.php
│   │   ├── CompanyPolicy.php
│   │   ├── CountryPolicy.php
│   │   ├── CurrencyPolicy.php
│   │   ├── StatePolicy.php
│   │   ├── UOMCategoryPolicy.php
│   │   └── UOMPolicy.php
│   ├── Services/
│   │   ├── CompanyContext.php
│   │   ├── EmailService.php
│   │   ├── EmailTemplateService.php
│   │   ├── ImageService.php
│   │   ├── QuickNavigationFavorites.php
│   │   ├── QuickNavigationRecents.php
│   │   ├── QuickNavigator.php
│   │   ├── SchemaRegistry.php
│   │   └── SequenceService.php
│   ├── Settings/
│   │   ├── BrandSettings.php
│   │   └── CompanyAwareSettingsRepository.php
│   ├── Support/
│   │   └── CompanyConsistencyGuard.php
│   └── Traits/
│       ├── BelongsToCompanies.php
│       ├── BelongsToCompany.php
│       ├── ChecksCompanyConsistency.php
│       ├── HasFilamentDefaults.php
│       ├── HasRecordNavigationTabs.php
│       ├── HasRouterMacros.php
│       ├── HasRtlSupport.php
│       ├── PDFHandler.php
│       ├── RefreshesRecordState.php
│       └── RestrictToAllowedCompanies.php
└── tests/
    ├── Feature/
    │   ├── API/V1/
    │   │   ├── BankTest.php
    │   │   ├── CountryTest.php
    │   │   ├── CurrencyRateTest.php
    │   │   ├── CurrencyTest.php
    │   │   ├── StateTest.php
    │   │   ├── UOMCategoryTest.php
    │   │   └── UOMTest.php
    │   ├── Filament/
    │   │   └── ResourceGlobalSearchSmokeTest.php
    │   ├── Locale/
    │   │   ├── ProfileLanguageUpdateTest.php
    │   │   └── SetLocaleMiddlewareTest.php
    │   └── Workflows/
    │       ├── CompanyIsolationTest.php
    │       └── CompanyScopingInvariantsTest.php
    └── Helpers/
        ├── CompanyHelper.php
        ├── CompanyScopeHelper.php
        ├── FilamentHelper.php
        ├── SecurityHelper.php
        └── TestBootstrapHelper.php
```

## Models
[VERIFIED]
The `support` plugin defines 23 Eloquent models under `plugins/webkul/support/src/Models/`:

| Model Class | Table | Scoping Trait | Soft Deletes | Key Relationships |
|---|---|---|---|---|
| `Company` | `companies` | `RestrictToAllowedCompanies` (`AllowedCompanyScope`) | Yes | Self (`parent`), Children (`branches`), `creator` (`User`), `currency`, `partner`, `state`, `country`, `calendar` |
| `Bank` | `banks` | Global Master | Yes | `state`, `country`, `creator` (`User`) |
| `Country` | `countries` | Global Reference | No | `currency`, `states` (hasMany) |
| `State` | `states` | Global Reference | No | `country` |
| `Currency` | `currencies` | Global Reference | No | `rates` (hasMany `CurrencyRate`), `companies` (hasMany). Methods: `getCodeAttribute()`, `findByCode(?string $code)`, `resolveDefault(?Country $country)` |
| `CurrencyRate` | `currency_rates` | Explicit `company_id` | No | `currency`, `company`, `creator` (`User`) |
| `Sequence` | `sequences` | `BelongsToCompany` (manual assignment) | No | `company`, `scope` (`MorphTo` `scope_type`/`scope_id`) |
| `Calendar` | `calendars` | `BelongsToCompany` (manual assignment) | Yes | `company`, `creator`, `attendances` (hasMany), `leaves` (hasMany), `resource` (`MorphTo`) |
| `CalendarAttendance` | `calendar_attendances` | Via `calendar_id` | No | `calendar`, `creator`, `resource` (`MorphTo`) |
| `CalendarLeave` | `calendar_leaves` | `BelongsToCompany` | No | `calendar`, `company`, `creator`, `resource` (`MorphTo`) |
| `ActivityPlan` | `activity_plans` | `BelongsToCompany` | Yes | `company`, `creator`, `activityTypes` (hasMany), `templates` (hasMany `ActivityPlanTemplate`) |
| `ActivityPlanTemplate` | `activity_plan_templates` | Via `activity_plan_id` | No | `activityPlan`, `activityType`, `responsible` (`User`), `creator` |
| `ActivityType` | `activity_types` | Global Setup | Yes | `activityPlan`, `defaultNextActivityType` (self), `suggestedActivityTypes` (belongsToMany self), `creator` |
| `ActivityTypeSuggestion` | `activity_type_suggestions` | Junction Pivot | No | `activity_type_id`, `suggested_activity_type_id` |
| `UOMCategory` | `unit_of_measure_categories` | Global Master | No | `unitOfMeasures` (hasMany `UOM`), `creator` |
| `UOM` | `unit_of_measures` | Global Master | Yes | `category` (`UOMCategory`), `creator`. Methods: `computePrice($price, $toUnit)`, `computeQuantity($qty, $toUnit)` |
| `UtmStage` | `utm_stages` | Global Master | No | `creator` |
| `UTMMedium` | `utm_mediums` | Global Master | No | `creator` |
| `UTMSource` | `utm_sources` | Global Master | No | `creator` |
| `UtmCampaign` | `utm_campaigns` | `BelongsToCompany` (manual assignment) | No | `company` (`nullOnDelete`), `stage` (`UtmStage`), `user` (`User`), `creator` |
| `EmailLog` | `email_logs` | System Audit | No | None |
| `QuickNavigationFavorite` | `quick_navigation_favorites` | Via `user_id` | No | `user` (`User`) |
| `EmailTemplate` | `email_templates` | Domain Model | Yes | **Missing Table**: declared on model and provider, but migration file missing from disk |

## Database
[VERIFIED]
- **Schema Overview**:
  Foundational relational schema maintaining tenant boundaries, multi-currency conversion rates, ISO country/state master tables, work calendars, activity blueprints, metric units, marketing attribution tags, and document sequences.
- **Foreign Key Conventions**:
  - `cascadeOnDelete()`: Junction pivot tables (`user_allowed_companies`, `activity_type_suggestions`, `plugin_dependencies`), child items (`calendar_attendances`, `calendar_leaves`, `activity_plan_templates`, `unit_of_measures`, `quick_navigation_favorites`, `currency_rates`).
  - `restrictOnDelete()`: Structural parent links (`companies.state_id`, `sequences.company_id`, `utm_campaigns.stage_id`).
  - `nullOnDelete()`: Auditing and creator references (`creator_id`, `utm_campaigns.company_id`, `utm_campaigns.user_id`, `companies.parent_id`).
- **Migrations Registered in Provider** (`SupportServiceProvider::configureCustomPackage()`):
  35 migrations declared in registration order (`plugins/webkul/support/src/SupportServiceProvider.php:48-83`):
  1. `2024_11_05_105102_create_plugins_table` (*File lives physically in `plugins/webkul/plugin-manager`*)
  2. `2024_11_05_105112_create_plugin_dependencies_table`
  3. `2024_12_06_061927_create_currencies_table`
  4. `2024_12_10_092651_create_countries_table`
  5. `2024_12_10_092657_create_states_table`
  6. `2024_12_10_092657_create_companies_table`
  7. `2024_12_10_100944_create_user_allowed_companies_table`
  8. `2024_12_10_101420_create_banks_table`
  9. `2024_12_12_114620_create_activity_plans_table`
  10. `2024_12_12_115256_create_activity_types_table`
  11. `2024_12_12_115728_create_activity_plan_templates_table`
  12. `2024_12_17_082318_create_activity_type_suggestions_table`
  13. `2025_01_03_061444_create_email_templates_table` (*ANOMALY: Missing from disk*)
  14. `2025_01_03_061445_create_email_logs_table`
  15. `2025_01_03_105625_create_unit_of_measure_categories_table`
  16. `2025_01_03_105627_create_unit_of_measures_table`
  17. `2025_01_07_125015_add_partner_id_to_companies_table`
  18. `2025_01_09_111545_create_utm_mediums_table`
  19. `2025_01_09_114324_create_utm_sources_table`
  20. `2025_01_10_094256_create_utm_stages_table`
  21. `2025_01_10_094325_create_utm_campaigns_table`
  22. `2025_04_04_061507_add_address_columns_in_companies_table`
  23. `2025_04_04_062023_alter_companies_table`
  24. `2025_08_08_104317_alter_utm_stages_table`
  25. `2025_08_08_104814_alter_utm_campaigns_table`
  26. `2025_10_10_080114_create_currency_rates_table`
  27. `2025_11_14_102615_alter_currency_rates_table`
  28. `2026_03_18_000001_alter_unit_of_measures_factor_precision`
  29. `2026_04_02_000001_create_calendars_table`
  30. `2026_04_29_065935_add_resource_columns_in_calendar_leaves_table`
  31. `2026_05_01_065935_add_resource_columns_in_calendar_attendances_table`
  32: `2026_07_10_000000_fix_unit_of_measures_factor_precision`
  33. `2026_07_16_000001_create_quick_navigation_favorites_table`
  34. `2026_07_30_110000_null_company_on_utm_campaigns`
  35. `2026_08_03_120000_create_sequences_table`
- **Unregistered Migration on Disk**:
  `plugins/webkul/support/database/migrations/2026_03_09_000001_add_unique_index_to_companies_name.php` exists on disk but is not listed in `SupportServiceProvider::hasMigrations()`.
  [UNKNOWN] Whether this omission was an unintentional oversight during migration staging or a deliberate design decision to allow duplicate branch names under distinct parent companies remains unknown from repository git history.

## Filament resources/pages/widgets/clusters
[VERIFIED]
### Resources (9 Resources in `src/Filament/Resources/`):
1. `CompanyResource` (`Webkul\Support\Filament\Resources\CompanyResource`):
   - Model: `Company`
   - Navigation Group: `Settings`
   - Pages: `ListCompanies`, `CreateCompany`, `EditCompany`, `ViewCompany`
   - Relation Managers: `BranchesRelationManager`
   - Schema: `CompanyForm`, `CompanyInfolist`, `CompaniesTable`
2. `CurrencyResource` (`Webkul\Support\Filament\Resources\CurrencyResource`):
   - Model: `Currency`
   - Pages: `ListCurrencies`, `CreateCurrency`, `EditCurrency`, `ViewCurrency`
   - Schema: `CurrencyForm`, `CurrencyInfolist`, `CurrenciesTable`
3. `SequenceResource` (`Webkul\Support\Filament\Resources\SequenceResource`):
   - Model: `Sequence`
   - Pages: `ManageSequences` (modal-driven table resource)
   - Features: Format preview badge (`next_preview`), code/company uniqueness check, date token helpers.
4. `BankResource` (`Webkul\Support\Filament\Resources\BankResource`):
   - Model: `Bank`
   - Pages: `ManageBanks` (modal-driven table resource)
   - Schema: `BankForm`, `BanksTable`
5. `CalendarResource` (`Webkul\Support\Filament\Resources\CalendarResource`):
   - Model: `Calendar`
   - Pages: `ListCalendars`, `CreateCalendar`, `EditCalendar`, `ViewCalendar`
   - Relation Managers: `CalendarAttendance` (Working Hours)
   - Schema: `CalendarForm`, `CalendarInfolist`, `CalendarsTable`
6. `ActivityTypeResource` (`Webkul\Support\Filament\Resources\ActivityTypeResource`):
   - Model: `ActivityType`
   - Pages: `ListActivityTypes`, `CreateActivityType`, `EditActivityType`, `ViewActivityType`
   - Schema: `ActivityTypeForm`, `ActivityTypeInfolist`, `ActivityTypesTable`
7. `UOMCategoryResource` (`Webkul\Support\Filament\Resources\UOMCategoryResource`):
   - Model: `UOMCategory`
   - Pages: `ListUOMCategories`, `CreateUOMCategory`, `EditUOMCategory`, `ViewUOMCategory`
   - Schema: `UOMCategoryForm`, `UOMCategoriesTable` (includes embedded UOM repeater)
8. `CountryResource` (`Webkul\Support\Filament\Resources\CountryResource`):
   - Model: `Country`
   - Stub resource (`shouldRegisterNavigation = false`, `isGloballySearchable = false`).
9. `StateResource` (`Webkul\Support\Filament\Resources\StateResource`):
   - Model: `State`
   - Stub resource (`shouldRegisterNavigation = false`, `isGloballySearchable = false`).

### Clusters & Cluster Pages:
- **Cluster**: `Settings` (`Webkul\Support\Filament\Clusters\Settings`, navigation sort 1000).
- **Cluster Page**: `ManageBranding` (`Webkul\Support\Filament\Clusters\Settings\Pages\ManageBranding`), managing `BrandSettings` (light/dark logos, favicon, logo height, 6-palette color picker with reset action). Protected by permission `page_support_manage_branding`.

### Pages:
- `Help` (`Webkul\Support\Filament\Pages\Help`): ERP documentation hub and resource links.
- `Profile` (`Webkul\Support\Filament\Pages\Profile`): User profile settings, avatar, password update, and locale preference.

### Widgets:
- `RecordNavigationTabs` (`Webkul\Support\Filament\Widgets\RecordNavigationTabs`): Renders sub-navigation tab widgets atop view/edit pages.

### Custom Components & Summarizers:
- **Forms**: `DashboardDateRange`, `Repeater`, `Repeater\TableColumn`.
- **Infolists**: `RepeatableEntry`, `RepeatableEntry\TableColumn`.
- **Table Columns**: `ProgressBarEntry`.
- **Table Summarizers**: `Sum`, `Average`, `Count`, `Range`, `Summarizer`.

## Panels
[VERIFIED]
- **`admin` Panel**:
  - Full discovery of `support` resources, pages, clusters, and widgets.
  - Injects `support::company-switcher` before global search.
  - Injects `@livewire('quick-navigation')` at `panels::body.end`.
  - Configures sidebar auto-scroll on `livewire:navigated`.
- **`customer` Panel**:
  - No resources or admin pages registered.
  - Multi-company scoping rules, database dialects, and helpers remain active at runtime.

## Services
[VERIFIED]

### Global Helper Functions (`src/helpers.php`)
The `support` plugin provides foundational utility functions loaded globally at boot time:
1. **`default_currency_code(): string`**: Resolves the system default ISO currency code via cached closure, evaluating `CurrencySettings::$default_currency_id`, falling back to `config('app.currency')`, and defaulting to `'USD'`.
2. **`default_currency_id(): ?int`**: Resolves the database ID of the default currency from settings or via `Currency::findByCode(default_currency_code())`.
3. **`hide_deleted_unless_selected(?string $state): Closure`**: Returns an Eloquent query constraint closure (`whereNull('deleted_at')->orWhere('id', $state)`) used across Filament forms (inventories, purchases, sales, manufacturing, accounts) to filter soft-deleted records out of selection dropdowns while preserving already-selected legacy values.
4. **`money(...)`**: Localized currency formatter supporting standard Latin and Arabic numerals, locale handling, and division factors.

### 1. `SequenceService` (`plugins/webkul/support/src/Services/SequenceService.php`)
The centralized document numbering service.
- **Architecture**:
  - Reads and updates counters in the `sequences` table (`Webkul\Support\Models\Sequence`).
  - **Thread Safety**: All counter mutations execute inside `DB::transaction()` using `lockedQuery()` with pessimistic database row locking (`lockForUpdate()`).
  - **Dual Scope Support**: Supports both string-coded sequences (`next('sales.order')`) and polymorphically scoped model sequences (`nextFor($journal, 'refund')`).
  - **Hierarchical Company Fallback**: `SequenceService::next($code, $companyId)` checks for a company-specific sequence record first (`['code' => $code, 'company_id' => $companyId]`); if not found, it falls back to the global company-null record (`['code' => $code, 'company_id' => null]`).
  - **Date Interpolation**: Replaces `%(year)` (4-digit year), `%(y)` (2-digit year), `%(month)` (2-digit month), and `%(day)` (2-digit day) in prefix/suffix templates.
  - **Cadence Reset**: Compares `period_key` (`Y` for yearly, `Y-m` for monthly); resets counter to 1 when entering a new period.
  - **Uninstall Cleanliness**: Provides `purge($codes, $scopeModels)` and `purgeScoped($scopeModel, $scopeIds)` to remove sequences when consuming plugins are uninstalled.
- **Method Catalog**:
  - `next(string $code, ?int $companyId = null, array $defaults = [], ?CarbonInterface $date = null): string`
  - `nextFor(Model $scope, string $variant = '', ?int $companyId = null, array $defaults = [], ?CarbonInterface $date = null): string`
  - `ensure(string $code, ?int $companyId = null, array $defaults = []): Sequence`
  - `ensureFor(Model $scope, string $variant = '', ?int $companyId = null, array $defaults = []): Sequence`
  - `initialFromNames(Builder $query, string $column = 'name'): int`
  - `purge(array $codes = [], array $scopeModels = []): void`
  - `purgeScoped(string $scopeModel, array $scopeIds): void`
- **Verified Callers Across Repository**:
  - `purchases`: `Order::boot()` generates `purchases.order`; `PurchaseServiceProvider` purges `purchases.order`; `SequenceSeeder` ensures default.
  - `sales`: `Order::boot()` generates `sales.order`; `SaleServiceProvider` purges `sales.order`; `SequenceSeeder` ensures default.
  - `manufacturing`: `Order::boot()` generates `manufacturing.order`; `ManufacturingServiceProvider` purges `manufacturing.order` and `OperationType`; `SequenceSeeder` ensures default.
  - `accounts`: `Move::boot()` generates `nextFor($journal, $variant)`; `Journal` ensures sequences; `AccountServiceProvider` purges `Journal` sequences.
  - `inventories`: `Operation::boot()` generates `nextFor($operationType)`; `Scrap::boot()` generates `inventories.scrap`; `InventoryServiceProvider` purges `inventories.scrap` and `OperationType`; `SequenceSeeder` ensures default.

### 2. `CompanyContext` (`plugins/webkul/support/src/Services/CompanyContext.php`)
- Manages active tenant state in session key `active_company_ids`.
- Methods: `allowedCompanies()`, `allowedIds()`, `activeIds()`, `currentId()`, `currentCompany()`, `toggle($id)`, `setActive(array $ids, ?int $current = null)`, `bypassed()`, `seesAllCompanies()`.

### 3. `QuickNavigator` (`plugins/webkul/support/src/Services/QuickNavigator.php`)
- Inspects registered Filament panels, clusters, pages, and resources to build a searchable hierarchical navigation tree.
- Supplies `createNodes()` dynamically for any resource where `pages['create']::canAccess()` is true.

### 4. `QuickNavigationFavorites` & `QuickNavigationRecents`
- `QuickNavigationFavorites` (`plugins/webkul/support/src/Services/QuickNavigationFavorites.php`): Persists user favorite links in `quick_navigation_favorites` table.
- `QuickNavigationRecents` (`plugins/webkul/support/src/Services/QuickNavigationRecents.php`): Stores up to 6 recently visited URLs in user session.

### 5. `SchemaRegistry` (`plugins/webkul/support/src/Services/SchemaRegistry.php`)
- Central registry for runtime Filament schema modifications.
- Allows plugins to register callbacks modifying forms/infolists of specific resources (`register()`) or globally across all resources (`registerGlobal()`) ordered by priority.

### 6. `CompanyAwareSettingsRepository` (`plugins/webkul/support/src/Settings/CompanyAwareSettingsRepository.php`)
- Extends Spatie's `DatabaseSettingsRepository`.
- For declared company-scoped groups (`accounts_accounts`, `accounts_taxes`), loads base default settings (`company_id IS NULL` or default company) and merges active company overrides (`company_id = current_company_id()`).

### 7. `ImageService` (`plugins/webkul/support/src/Services/ImageService.php`)
- Integrates League Glide server for signed image resizing and transformations (`BASE_PATH = 'img/'`).
- Validates HMAC signatures via `validate()` and streams responses with 1-year public cache headers.

### 8. `EmailService` (`plugins/webkul/support/src/Services/EmailService.php`)
- Dispatches mailables with sender identity from current user/company and records transmission in `email_logs`.

### 9. `EmailTemplateService` (`plugins/webkul/support/src/Services/EmailTemplateService.php`)
- Composes dynamic template emails and variable replacement.
- *Anomaly Warning*: Relies on `EmailTemplate` model (missing database table) and non-existent `DynamicEmail` mailable. Unused in runtime code.

## Events
[VERIFIED]
None (`—`). There are zero dedicated Event classes defined in `plugins/webkul/support/src`.

## Listeners
[VERIFIED]
None (`—`). There are zero dedicated Listener classes defined in `plugins/webkul/support/src`.

## Observers
[VERIFIED]
None (`—`). There are zero dedicated Eloquent Observer classes defined in `plugins/webkul/support/src`. Model lifecycle hooks are registered directly inside model `boot()` methods (e.g. `creator_id` assignment on `creating`, `Sequence` reset frequency cache invalidation on `saving`).

## Policies
[VERIFIED]
The `support` plugin defines 10 Policy classes under `plugins/webkul/support/src/Policies/`, enforcing standard Filament Shield permission keys (`view_any_*`, `view_*`, `create_*`, `update_*`, `delete_*`, `delete_any_*`, `force_delete_*`, `restore_*`):

1. `CompanyPolicy` (`plugins/webkul/support/src/Policies/CompanyPolicy.php`) -> `Company`
2. `BankPolicy` (`plugins/webkul/support/src/Policies/BankPolicy.php`) -> `Bank`
3. `CountryPolicy` (`plugins/webkul/support/src/Policies/CountryPolicy.php`) -> `Country`
4. `StatePolicy` (`plugins/webkul/support/src/Policies/StatePolicy.php`) -> `State`
5. `CurrencyPolicy` (`plugins/webkul/support/src/Policies/CurrencyPolicy.php`) -> `Currency`
6. `CalendarPolicy` (`plugins/webkul/support/src/Policies/CalendarPolicy.php`) -> `Calendar`
7. `ActivityPlanPolicy` (`plugins/webkul/support/src/Policies/ActivityPlanPolicy.php`) -> `ActivityPlan`
8. `ActivityTypePolicy` (`plugins/webkul/support/src/Policies/ActivityTypePolicy.php`) -> `ActivityType`
9. `UOMCategoryPolicy` (`plugins/webkul/support/src/Policies/UOMCategoryPolicy.php`) -> `UOMCategory`
10. `UOMPolicy` (`plugins/webkul/support/src/Policies/UOMPolicy.php`) -> `UOM`

### Deliberate Cross-Plugin Policy Registration:
- `SupportServiceProvider::packageBooted()` explicitly executes `Gate::policy(Role::class, RolePolicy::class)` (`plugins/webkul/support/src/SupportServiceProvider.php:128`).
- **Rationale**: `RolePolicy` controls access to Filament Shield's role and permission management. Because `SupportServiceProvider` initializes core authorization gates (`bypass_company_scope`) and admin panel hooks, registering `RolePolicy` here ensures role authorization operates reliably regardless of plugin boot sequencing.

## Routes
[VERIFIED]

### API Routes (`plugins/webkul/support/routes/api.php`):
Prefix: `admin/api/v1/support`, Middleware: `['auth:sanctum']`, Route Name Prefix: `admin.api.v1.support.`
- `currencies`: `CurrencyController` (`apiResource`)
- `currencies.rates`: `CurrencyRateController` (`apiResource`)
- `banks`: `BankController` (`softDeletableApiResource` -> index, store, show, update, destroy, restore, force-destroy)
- `countries`: `CountryController` (`apiResource` -> `['index', 'show']`)
- `states`: `StateController` (`apiResource`)
- `uom-categories`: `UOMCategoryController` (`apiResource`)
- `uom-categories.uoms`: `UOMController` (`softDeletableApiResource`)

### Web Routes:
- `routes/web.php`:
  - `GET img/{path}` -> `ImageController` (`name('support.image')`)
- Registered in `SupportServiceProvider::packageBooted()`:
  - `POST company-context/set` -> `CompanyContextController@set` (`middleware(['web', 'auth'])`, `name('company-context.set')`)
  - `GET cache/{filename}` -> `ImageCacheController@getImage` (`name('image_cache')`)

### Router Macros (`HasRouterMacros`):
- `Router::softDeletableApiResource($name, $controller, array $options = [])`:
  - Generates standard `apiResource` routes.
  - Automatically appends `POST {path}/{id}/restore` (`{$name}.restore`) and `DELETE {path}/{id}/force` (`{$name}.force-destroy`).

## Settings
[VERIFIED]
- **Settings Migration**: `plugins/webkul/support/database/settings/2026_06_12_000001_create_brand_settings.php`
- **Settings Class**: `Webkul\Support\Settings\BrandSettings` (`plugins/webkul/support/src/Settings/BrandSettings.php`)
  - Group: `'branding'`
  - Properties: `primary_color`, `gray_color`, `danger_color`, `info_color`, `success_color`, `warning_color`, `light_logo`, `dark_logo`, `favicon`, `logo_height`.
- **Settings Repository**: `CompanyAwareSettingsRepository` (`plugins/webkul/support/src/Settings/CompanyAwareSettingsRepository.php`)
  - Overrides Spatie's `DatabaseSettingsRepository` to inject tenant-specific database overrides for `accounts_accounts` and `accounts_taxes`.

## Translations
[VERIFIED]
- **Supported Locales**: `ar` (Arabic, RTL), `en` (English baseline), `es` (Spanish), `fr` (French), `pt_BR` (Portuguese Brazil).
- **Structure**:
  - `enums/*.php`: Localized labels for all enums (`activity-chaining-type`, `activity-decoration-type`, `activity-delay-from`, `activity-delay-interval`, `activity-delay-unit`, `activity-responsible-type`, `activity-type-action`, `calendar-display-type`, `day-of-week`, `day-period`, `sequence-reset-frequency`, `uom-type`, `week-type`).
  - `filament/clusters/manage-branding.php`, `filament/clusters/settings/pages/settings.php`.
  - `filament/resources/{resource}/*.php`: Form sections, fields, table columns, actions, notifications for `activity-type`, `bank`, `calendar`, `company`, `currency`, `sequence`, `uom-category`.
  - `filament/pages/*.php`: `help.php`, `profile.php`.
  - `quick-navigation.php`: Search, recent, favorite labels.
  - `support.php`: Core system strings, cross-company error messages, versioning.

## Tests
[VERIFIED]
**Test Coverage Status: HAS TESTS.**
The `support` plugin contains a dedicated Pest test suite under `plugins/webkul/support/tests/` comprising 13 feature test files and 5 test helper classes:

### Feature Tests:
1. `plugins/webkul/support/tests/Feature/API/V1/BankTest.php`: CRUD, soft delete, restore, force delete, Spatie query builder filtering/sorting on banks API.
2. `plugins/webkul/support/tests/Feature/API/V1/CountryTest.php`: Country index and show endpoints, relation includes.
3. `plugins/webkul/support/tests/Feature/API/V1/CurrencyTest.php`: Currency CRUD, formatting, active status.
4. `plugins/webkul/support/tests/Feature/API/V1/CurrencyRateTest.php`: Nested currency conversion rate endpoints.
5. `plugins/webkul/support/tests/Feature/API/V1/StateTest.php`: State index, create, update, delete endpoints.
6. `plugins/webkul/support/tests/Feature/API/V1/UOMCategoryTest.php`: Unit of measure category management.
7. `plugins/webkul/support/tests/Feature/API/V1/UOMTest.php`: Nested unit of measure endpoints with ratio precision.
8. `plugins/webkul/support/tests/Feature/Filament/ResourceGlobalSearchSmokeTest.php`: Verifies global search provider across resources.
9. `plugins/webkul/support/tests/Feature/Locale/ProfileLanguageUpdateTest.php`: Verifies user locale updates.
10. `plugins/webkul/support/tests/Feature/Locale/SetLocaleMiddlewareTest.php`: Verifies application locale resolution.
11. `plugins/webkul/support/tests/Feature/Workflows/CompanyIsolationTest.php`: Verifies multi-company tenant isolation invariants (record hiding across companies, active company switching, allowed company scoping).
12. `plugins/webkul/support/tests/Feature/Workflows/CompanyScopingInvariantsTest.php`: Verifies automated company assignment and shared model declarations (`ActivityPlan`, `Calendar`, `Sequence`, `UtmCampaign`).
13. `plugins/webkul/support/tests/Feature/Workflows/DefaultCurrencyResolutionTest.php`: Verifies default currency resolution hierarchy (country currency -> app.currency config fallback -> first active currency), ISO code lookup via `findByCode()`, and company currency assignment activation.

### Test Helpers:
- `CompanyHelper.php`: User authentication with company contexts and session state setup.
- `CompanyScopeHelper.php`: Introspection utilities auditing model company traits and scoping invariants.
- `FilamentHelper.php`: Filament panel testing helpers.
- `SecurityHelper.php`: Disables and restores user permission events during tests.
- `TestBootstrapHelper.php`: Test environment initialization.

## Runtime dependencies
[VERIFIED]
None (`—`).

## Cross-plugin relationships
[VERIFIED]
The `support` plugin provides shared infrastructure consumed across core and optional plugins in the application (architectural and code-level consumption rather than declared `Package::hasDependencies()` runtime plugin dependencies):
- **`security`**: `User` model defines `allowedCompanies()` and `defaultCompany()`. `SupportServiceProvider` registers `RolePolicy` and `AcceptInvitation` Livewire component.
- **`partners`**: `Partner` model utilizes `BelongsToCompany` and links to `Bank`, `Country`, `State`, and `Currency`.
- **`accounts`**: Uses `SequenceService` for invoice/bill/payment numbering (`nextFor($journal)`), `CompanyAwareSettingsRepository` for account/tax settings, and `Currency`/`CurrencyRate` for multi-currency general ledger accounting.
- **`sales`**: Uses `SequenceService::next('sales.order')`, `SequenceSeeder`, `Currency`, and `UOM`.
- **`purchases`**: Uses `SequenceService::next('purchases.order')`, `SequenceSeeder`, `Currency`, and `UOM`.
- **`manufacturing`**: Uses `SequenceService::next('manufacturing.order')`, `SequenceService::purgeScoped()`, and `UOM`.
- **`inventories`**: Uses `SequenceService` for warehouse transfers (`OperationType`) and scraps (`inventories.scrap`), and `UOM`/`UOMCategory` for stock tracking.
- **`chatter`**: Uses `ActivityType` and `ActivityPlan` models from `support` for scheduled activity management.

## Data flow
[VERIFIED]
```
                                 ┌───────────────────────────────┐
                                 │      Incoming HTTP Request    │
                                 └───────────────┬───────────────┘
                                                 │
                                                 ▼
                                 ┌───────────────────────────────┐
                                 │       CompanyContext          │
                                 │  - Reads session active IDs   │
                                 │  - Intersects with user allowed│
                                 │  - Checks bypass Gate         │
                                 └───────────────┬───────────────┘
                                                 │
                 ┌───────────────────────────────┼───────────────────────────────┐
                 ▼                               ▼                               ▼
     ┌───────────────────────┐       ┌───────────────────────┐       ┌───────────────────────┐
     │     Eloquent Model    │       │    SequenceService    │       │   SchemaRegistry      │
     │   (BelongsToCompany)  │       │  - lockedQuery()      │       │ - applyModifiers()    │
     │ - Injects CompanyScope│       │  - lockForUpdate()    │       │ - Applies custom      │
     │ - Auto-stamps active  │       │  - Date interpolate   │       │   form/infolist fields│
     │   company on save     │       │  - Consumes counter   │       └───────────────────────┘
     └───────────────────────┘       └───────────────────────┘
```

## Business rules
[VERIFIED]
1. **Multi-Company Global Scoping**:
   - Models using `BelongsToCompany` automatically filter queries by active session companies (`whereIn('company_id', $activeIds)` or `whereNull('company_id')`).
   - Models with `autoAssignsCompany() === true` stamp `current_company_id()` on creation if `company_id` is null. Models declaring `autoAssignsCompany() === false` (`Sequence`, `Calendar`, `ActivityPlan`, `UtmCampaign`) remain shared across companies unless explicitly assigned.
2. **Super Admin Scope Bypass**:
   - The Gate rule `bypass_company_scope` permits users with the `super_admin` role to view and query records across all companies unconditionally.
3. **Cross-Company Consistency**:
   - `CompanyConsistencyGuard::assert()` ensures that related foreign records referenced on a model (e.g. warehouse, partner, journal) belong to the same company as the parent record.
4. **Sequence Number Generation**:
   - Sequences lock rows with `lockForUpdate()` during consumption.
   - Lookups evaluate company-specific sequence first, then global company-null fallback.
   - If a sequence's reset frequency period has elapsed, the counter resets to 1.
5. **Unit of Measure Conversions**:
   - UOM categories enforce exactly one `Reference` UOM (`ratio = 1.0`). Larger units divide by ratio; smaller units multiply by ratio.
   - Precision comparisons are governed by `float_compare()`, `float_round()`, and `float_is_zero()`.

## Extension points
[VERIFIED]
1. **`SchemaRegistry::register()` and `registerGlobal()`**:
   Allows external plugins to inject fields, sections, tabs, or infolists into any Filament resource without modifying core files.
2. **`DatabaseDialect`**:
   New database engines can be supported by implementing `DatabaseDialect` and registering the singleton in the service container.
3. **`CompanyAwareSettingsRepository::COMPANY_SCOPED_GROUPS`**:
   New settings groups can be added to enable company-specific override inheritance.
4. **`Router::softDeletableApiResource()`**:
   Provides uniform RESTful restore and force-delete endpoint registration for soft-deletable models.
5. **`PDFHandler` Trait**:
   Can be used by any controller or page to generate and stream RTL-compliant PDF documents.

## Dangerous areas
[VERIFIED]
**Test Coverage Status: HAS TESTS.**

1. **`EmailTemplate` Missing Database Migration Anomaly**:
   - `SupportServiceProvider.php:60` registers `'2025_01_03_061444_create_email_templates_table'` in `hasMigrations()`, but the migration file `2025_01_03_061444_create_email_templates_table.php` is completely missing from disk.
   - Consequently, the `email_templates` database table is not created.
   - `EmailTemplateService` (`plugins/webkul/support/src/Services/EmailTemplateService.php`) queries `EmailTemplate` and imports `Webkul\Support\Mail\DynamicEmail` (which also does not exist on disk).
   - [INFERRED] It is inferred that `EmailTemplate` was an early prototype for database-driven mail templating that was abandoned in favor of direct Blade view mailables (`EmailService.php`), leaving orphaned model, service, factory, and resource classes.
   - *Impact*: No runtime code in Aureus ERP currently invokes `EmailTemplate` or `EmailTemplateService`. However, any future feature calling `EmailTemplateService` will immediately throw a fatal database exception or class not found error.

2. **Unregistered Migration on Disk (`2026_03_09_000001_add_unique_index_to_companies_name.php`)**:
   - The migration file adding a unique index on `companies.name` exists in `plugins/webkul/support/database/migrations/` but was omitted from the `$package->hasMigrations([...])` array in `SupportServiceProvider.php`.
   - *Impact*: The unique index on `companies.name` is never executed during automated plugin migrations.

3. **Sequence Counter Gaps on Transaction Rollback**:
   - `SequenceService::consume()` consumes sequence numbers inside database transactions with pessimistic row locks (`lockForUpdate()`).
   - If an outer transaction (e.g. creating a Sales Order or Posting an Invoice) fails and rolls back after calling `SequenceService::next()`, the incremented sequence counter in the database remains committed or burned, creating a permanent gap in numbering.

4. **Cross-Company Consistency Check Bypassed in CLI / Console**:
   - `ChecksCompanyConsistency::bootChecksCompanyConsistency()` explicitly skips assertion when `app()->runningInConsole()` is true.
   - *Impact*: Bulk imports, seeders, or queued console commands can accidentally associate records belonging to different companies without triggering `CrossCompanyException`.

5. **Super Admin Scope Bypass Blast Radius**:
   - The global `bypass_company_scope` Gate rule bypasses `CompanyScope` queries for any user with the `super_admin` role. In multi-tenant deployments, super admins seeing cross-company records in dropdowns must exercise caution not to link foreign company entities together manually.

## Change impact
[VERIFIED]
- **Blast Radius**: Critical / System-Wide.
- `support` is the foundation of the entire ERP. Any modification to `CompanyContext`, `SequenceService`, `BelongsToCompany`, `CompanyScope`, `DatabaseDialect`, or `SchemaRegistry` directly impacts the shared infrastructure consumed across core and optional plugins in the repository (see canonical architecture overview in [`docs/architecture/overview.md`](../architecture/overview.md)).

## Evidence
[VERIFIED]

| Fact / Feature | Source File | Line / Symbol |
|---|---|---|
| Service Provider Registration | `bootstrap/providers.php` | Line 38 (`SupportServiceProvider::class`) |
| Package & Core Configuration | `plugins/webkul/support/src/SupportServiceProvider.php` | Lines 40–90 (`configureCustomPackage()`) |
| Super Admin Gate Bypass | `plugins/webkul/support/src/SupportServiceProvider.php` | Lines 94–104 (`Gate::before('bypass_company_scope')`) |
| RolePolicy Registration | `plugins/webkul/support/src/SupportServiceProvider.php` | Line 128 (`Gate::policy(Role::class, RolePolicy::class)`) |
| Filament Plugin Discovery | `plugins/webkul/support/src/SupportPlugin.php` | Lines 24–52 (`register()`) |
| SequenceService Implementation | `plugins/webkul/support/src/Services/SequenceService.php` | Lines 15–164 (`next()`, `nextFor()`, `consume()`, `lockedQuery()`, `purge()`) |
| Sequence Model & Number Formatting | `plugins/webkul/support/src/Models/Sequence.php` | Lines 12–120 (`consumeNumber()`, `formatNumber()`, `interpolate()`) |
| Company Context Resolution | `plugins/webkul/support/src/Services/CompanyContext.php` | Lines 10–134 (`activeIds()`, `allowedCompanies()`, `currentCompany()`) |
| Company Scoping Traits | `plugins/webkul/support/src/Traits/BelongsToCompany.php` | Lines 9–37 (`BelongsToCompany`) |
| Company Consistency Guard | `plugins/webkul/support/src/Support/CompanyConsistencyGuard.php` | Lines 7–67 (`detect()`, `assert()`) |
| Schema Registry Engine | `plugins/webkul/support/src/Services/SchemaRegistry.php` | Lines 9–269 (`register()`, `registerGlobal()`, `applyModifiers()`) |
| Database Dialect Abstraction | `plugins/webkul/support/src/Database/Dialects/DatabaseDialect.php` | Lines 12–54 (`DatabaseDialect`) |
| Company Aware Settings | `plugins/webkul/support/src/Settings/CompanyAwareSettingsRepository.php` | Lines 9–204 (`CompanyAwareSettingsRepository`) |
| Quick Navigator Palette | `plugins/webkul/support/src/Services/QuickNavigator.php` | Lines 11–278 (`groups()`, `createNodes()`, `flat()`) |
| Soft Delete Router Macro | `plugins/webkul/support/src/Traits/HasRouterMacros.php` | Lines 9–35 (`registerRouterMacros()`) |
| Missing EmailTemplate Migration | `plugins/webkul/support/src/SupportServiceProvider.php` | Line 60 (declared), `database/migrations/` (absent) |
