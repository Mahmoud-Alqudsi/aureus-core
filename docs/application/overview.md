---
status: verified
source_of_truth: source-code
last_verified: 2026-09-04
scope: application-foundation
confidence: high
---

# Aureus ERP — Application Foundation Layer

This document covers the project-level architecture **outside** the plugin layer (`plugins/webkul/`). It describes the providers, middleware, navigation shell, internationalization/RTL, frontend asset pipeline, database foundation, API documentation infrastructure, testing foundation, and project configuration that together form the application skeleton on which all 28 domain plugins operate.

> [!IMPORTANT]
> This document complements — and does not duplicate — the plugin-layer documentation in `docs/plugins/` or the Filament panel architecture in `docs/architecture/filament-architecture.md`. Cross-references are provided where topics overlap.

---

## Application Layer Tree

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Controller.php                    # Empty base controller (Laravel scaffold)
│   └── Middleware/
│       ├── ApplyBrandSettings.php            # Dynamic branding middleware
│       └── SetLocale.php                     # Locale resolution middleware
├── Models/
│   └── User.php                              # Skeleton User (overridden at runtime)
└── Providers/
    ├── AppServiceProvider.php                 # Root service provider
    └── Filament/
        ├── AdminPanelProvider.php             # Admin panel configuration
        └── CustomerPanelProvider.php          # Customer panel configuration

bootstrap/
├── app.php                                   # Routing, middleware, exception rendering
└── providers.php                             # 31 provider registrations

config/                                        # 21 configuration files
database/
├── factories/UserFactory.php
├── migrations/                               # 13 foundational migrations
└── seeders/                                  # 3 seeders (DatabaseSeeder, ShieldSeeder, CurrencySeeder)

resources/
├── css/app.css                               # Tailwind v4 + RTL/LTR support (306 lines)
├── js/app.js, bootstrap.js                   # Axios bootstrap
├── svg/                                      # 21 custom navigation icons
└── views/
    ├── filament/components/                  # Language switcher components
    ├── forms/components/                     # State flow form component
    ├── scribe/index.blade.php                # Scalar API documentation viewer
    └── vendor/filament-panels/livewire/      # Customized topbar + sidebar overrides

routes/
├── web.php                                   # Login redirect only
├── api.php                                   # Empty (plugins register own API routes)
└── console.php                               # Default inspire command only

tests/
├── Pest.php, TestCase.php                    # Test bootstrap and configuration
└── e2e-pw/                                   # Playwright E2E test suite
```

---

## Providers

### AppServiceProvider

**File**: [`app/Providers/AppServiceProvider.php`](app/Providers/AppServiceProvider.php)

Responsibilities:

1. **Auth binding override**: Binds `Illuminate\Contracts\Auth\Authenticatable` → `Webkul\Security\Models\User`. This means the application's authenticatable model is **not** `App\Models\User` but the Security plugin's User model. `App\Models\User` is a scaffold remnant.
2. **HTTPS enforcement**: Forces HTTPS scheme in production via `URL::forceScheme('https')`.
3. **Livewire notification persistence**: Intercepts Livewire `dehydrate` events during redirect to move Filament notifications from `filament.notifications` to `filament.claimed_notifications` in the session, preventing notification loss across Livewire redirects.

[VERIFIED]
Evidence: `app/Providers/AppServiceProvider.php` lines 17–45

### AdminPanelProvider & CustomerPanelProvider

Documented in detail in [`docs/architecture/filament-architecture.md`](../architecture/filament-architecture.md). Key facts relevant to the application layer:

- Both panels register `SetLocale` and `ApplyBrandSettings` middleware.
- Both panels inject the language switcher via `PanelsRenderHook::GLOBAL_SEARCH_END`.
- The admin panel uses the `web` guard; the customer panel uses the `customer` guard.

### Provider Registration Order

`bootstrap/providers.php` registers **31 providers** in this order:

1. `AppServiceProvider` (root application bindings)
2. `AdminPanelProvider` (default Filament panel)
3. `CustomerPanelProvider` (customer-facing panel)
4. 27 plugin service providers (alphabetical by domain)
5. `PluginManagerServiceProvider` (registered **last** — manages plugin lifecycle)

[VERIFIED]
Evidence: `bootstrap/providers.php` lines 35–67

---

## Middleware

### ApplyBrandSettings

**File**: [`app/Http/Middleware/ApplyBrandSettings.php`](app/Http/Middleware/ApplyBrandSettings.php)

Dynamically applies company-specific branding at request time by reading from `Webkul\Support\Settings\BrandSettings`:

- **Color palette**: For each of 6 color keys (primary, success, danger, warning, info, gray), converts the stored hex color to an OKLCH-based 11-shade palette using perceptually uniform lightness/chroma scaling. Only overrides panel defaults when the brand color actually differs.
- **Logos**: Applies light logo, dark logo, and favicon from brand settings. Resolves assets from public storage disk or falls back to `asset()`.
- **Logo height**: Applies custom logo height from brand settings.
- **Failure isolation**: Wraps all brand application in a try/catch that silently continues on error (graceful degradation).

**Registration**: Panel middleware in both `AdminPanelProvider` and `CustomerPanelProvider`.

[VERIFIED]
Evidence: `app/Http/Middleware/ApplyBrandSettings.php` lines 48–107

### SetLocale

**File**: [`app/Http/Middleware/SetLocale.php`](app/Http/Middleware/SetLocale.php)

Resolves the application locale using a priority chain:

1. Query parameter `?lang=xx` (highest priority; also persisted to session)
2. Session value `locale`
3. Authenticated user's `language` attribute
4. `config('app.locale')` fallback
5. `config('app.fallback_locale')` fallback
6. First supported locale or `'en'`

All candidates are validated against `config('app.supported_locales')` keys before acceptance.

**Registration**: Global web middleware via `bootstrap/app.php` AND panel middleware in both panel providers.

[VERIFIED]
Evidence: `app/Http/Middleware/SetLocale.php` lines 18–43; `bootstrap/app.php` lines 23–26

---

## Root Models

### App\Models\User

**File**: [`app/Models/User.php`](app/Models/User.php)

A standard Laravel scaffold User model with `HasApiTokens`, `HasFactory`, `Notifiable`. **This model is effectively unused at runtime** because `AppServiceProvider` binds `Authenticatable` → `Webkul\Security\Models\User`. The Security plugin's User model is the actual authenticatable entity used by both guards.

The root `UserFactory` (`database/factories/UserFactory.php`) also references `Webkul\Security\Models\User` as its model class, not `App\Models\User`.

[VERIFIED]
Evidence: `app/Models/User.php`; `app/Providers/AppServiceProvider.php` line 19; `database/factories/UserFactory.php` line 25

---

## Navigation Shell

### Customized Topbar

**File**: [`resources/views/vendor/filament-panels/livewire/topbar.blade.php`](resources/views/vendor/filament-panels/livewire/topbar.blade.php) (339 lines)

This is a **vendor override** of Filament's default topbar that adds significant custom behavior:

1. **App Switcher Grid** (admin panel only): A dropdown activated by a custom menu icon (`icon-menu`) that renders all navigation groups as a 3-column grid of 64×64 SVG icons. Each group links to the first item URL in that group. This provides an Odoo/SAP-style module launcher.
2. **Active Group Heading**: In the admin panel, only the **active** navigation group is shown in the topbar. Its label is displayed as a bold heading, and its child items are rendered as individual topbar items. Inactive groups are hidden from the topbar (accessible only via the app switcher grid).
3. **Non-admin panel behavior**: For panels other than admin (e.g., customer), navigation renders as standard Filament grouped dropdowns.
4. **RTL awareness**: Reads `$isRtl` from `__('filament-panels::layout.direction')` and flips sidebar expand/collapse chevron icons accordingly.
5. **Render hooks**: Preserves all Filament render hooks (`TOPBAR_START`, `TOPBAR_END`, `TOPBAR_LOGO_BEFORE/AFTER`, `GLOBAL_SEARCH_BEFORE/AFTER`).

[VERIFIED]
Evidence: `resources/views/vendor/filament-panels/livewire/topbar.blade.php` lines 1–339

### Customized Sidebar

**File**: [`resources/views/vendor/filament-panels/livewire/sidebar.blade.php`](resources/views/vendor/filament-panels/livewire/sidebar.blade.php) (172 lines)

A **vendor override** of Filament's default sidebar with these modifications:

1. **Active-group-only rendering** (admin panel): Like the topbar, the sidebar only shows items from the **active** navigation group, filtering out inactive groups.
2. **Flat item rendering**: Replaces Filament's default `<x-filament-panels::sidebar.group>` component with a flat `<ul>` of items (the group component usage is commented out), providing a simplified non-grouped sidebar appearance.
3. **Collapsed group persistence**: Inline JavaScript persists collapsed group states in `localStorage` and applies them before Alpine.js loads to prevent layout flash.

[VERIFIED]
Evidence: `resources/views/vendor/filament-panels/livewire/sidebar.blade.php` lines 1–172

### Language Switcher

**Files**:
- [`resources/views/filament/components/language-switcher.blade.php`](resources/views/filament/components/language-switcher.blade.php) — used in both admin and customer panels via `PanelsRenderHook::GLOBAL_SEARCH_END`
- [`resources/views/filament/components/auth-language-switcher.blade.php`](resources/views/filament/components/auth-language-switcher.blade.php) — used on authentication pages

Both components:
- Read supported locales from `config('app.supported_locales')`
- Display country flag SVGs from `public/flags/{code}.svg`
- Switch locale via `?lang=xx` query parameter (processed by `SetLocale` middleware)
- Use AlpineJS for dropdown toggle animation
- Are RTL-aware (adjust dropdown positioning based on `$isRtl`)

[VERIFIED]
Evidence: `resources/views/filament/components/language-switcher.blade.php`; `app/Providers/Filament/AdminPanelProvider.php` lines 94–97

### Navigation Icons

**Directory**: [`resources/svg/`](resources/svg/) (21 SVG files)

Custom SVG icons used by the `NavigationGroup` enum and the app switcher grid for module identification:

`accounting`, `barcode`, `contacts`, `dashboard`, `employees`, `help`, `inventories`, `invoices`, `maintenance`, `manufacturing`, `menu`, `pin`, `plugin`, `projects`, `purchases`, `recruitments`, `sales`, `settings`, `time-offs`, `un-pin`, `website`

These icons are referenced by `Webkul\Support\Enums\NavigationGroup` via `getIcon()` and rendered at 64×64 in the topbar app switcher grid.

[VERIFIED]
Evidence: `resources/svg/` directory listing; topbar.blade.php lines 74–77

---

## Internationalization & RTL Architecture

### Supported Locales

Defined in `config/app.php` → `supported_locales`:

| Code | Label | Native | RTL |
|:---|:---|:---|:---:|
| `en` | English | English | No |
| `ar` | Arabic | العربية | **Yes** |
| `es` | Spanish | Español | No |
| `pt_BR` | Portuguese (Brazil) | Português (Brasil) | No |
| `fr` | French | Français | No |

Translation files exist under `lang/` for all 5 locales.

[VERIFIED]
Evidence: `config/app.php` lines 99–130; `lang/` directory listing

### RTL CSS Architecture

**File**: [`resources/css/app.css`](resources/css/app.css) (306 lines)

The application implements RTL support through a comprehensive CSS override strategy:

1. **Activation mechanism**: RTL is **not globally enforced**. It is conditionally activated when the resolved locale has `rtl: true` in `supported_locales`. The HTML `dir` attribute is set by Filament based on `__('filament-panels::layout.direction')`.

2. **CSS strategy**: Uses `[dir="rtl"]` attribute selectors to apply directional overrides. Categories:
   - **Layout flips**: `flex-direction`, `text-align`, margin/padding logical properties
   - **Form inputs**: Form fields use `direction: rtl` except email/URL/tel/number fields which remain LTR
   - **Filament component overrides**: Specific overrides for `fi-topbar`, `fi-sidebar`, `fi-breadcrumbs`, `fi-btn`, `fi-modal`, `fi-notification`, `fi-tabs`, `fi-section`, `fi-stats`, `fi-dropdown`, `fi-in` (infolist), `fi-ta-header-cell-label` (table headers)
   - **Icon flips**: Chevrons, arrows, and breadcrumb separators are horizontally flipped via `scaleX(-1)`
   - **Number preservation**: Numbers, prices, currencies always remain LTR via `direction: ltr`
   - **Arabic font**: Cairo and Noto Sans Arabic fonts applied for `[dir="rtl"]` and `[lang="ar"]`
   - **Transition smoothing**: `margin`, `padding`, `transform` transitions on all elements for smooth direction changes

3. **Build pipeline**: `app.css` is processed via Vite with `@tailwindcss/postcss` (Tailwind v4) and includes `@import "tailwindcss"` at the top.

[VERIFIED]
Evidence: `resources/css/app.css` lines 1–306; `vite.config.js`; `postcss.config.js`; `package.json`

---

## Database Foundation Layer

### Root Migrations (13 files)

These migrations create the foundational tables shared across all plugins:

| Migration | Tables Created | Architectural Role |
|:---|:---|:---|
| `create_users_table` | `users`, `password_reset_tokens`, `sessions` | Authentication foundation |
| `create_cache_table` | `cache`, `cache_locks` | Framework caching |
| `create_jobs_table` | `jobs`, `job_batches`, `failed_jobs` | Queue infrastructure (no Job classes exist) |
| `create_settings_table` | `settings` | Spatie Laravel Settings |
| `create_permission_tables` | `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions` | Spatie Permission / Filament Shield RBAC |
| `add_resource_permission_column_to_users_table` | — (alters `users`) | Adds `resource_permission` column |
| `create_imports_table` | `imports` | Filament import tracking |
| `create_exports_table` | `exports` | Filament export tracking |
| `create_failed_import_rows_table` | `failed_import_rows` | Failed import tracking |
| `create_notifications_table` | `notifications` | Laravel database notifications |
| `create_personal_access_tokens_table` | `personal_access_tokens` | Sanctum API tokens |
| `alter_notifications_table_data_column_to_json` | — (alters `notifications`) | Changes `data` column to JSON type |
| `add_company_id_to_settings_table` | — (alters `settings`) | Adds `company_id` for multi-company settings isolation |

**Registration**: Auto-discovered by Laravel's standard migration mechanism (no manual registration needed).

**Distinction**: Plugin migrations reside in `plugins/webkul/*/database/migrations/` and are loaded conditionally by `PackageServiceProvider::boot()` gated on `Package::isCore()` or `Package::isInstalled()`.

[VERIFIED]
Evidence: `database/migrations/` directory listing; `plugins/webkul/plugin-manager/src/PackageServiceProvider.php`

### Seeders

| Seeder | Role | Called By |
|:---|:---|:---|
| [`DatabaseSeeder`](database/seeders/DatabaseSeeder.php) | Orchestrates: calls `SecurityDatabaseSeeder` → `SupportDatabaseSeeder` → `PluginSeeder` | `db:seed` or `erp:install` |
| [`ShieldSeeder`](database/seeders/ShieldSeeder.php) | Creates `panel_user` role and 56 direct permissions (CRUD for roles, users, teams, fields, tasks) | Called during `erp:install` flow |
| [`CurrencySeeder`](database/seeders/CurrencySeeder.php) | Seeds 40 international currencies into `Webkul\Support\Models\Currency` | Called during `erp:install` flow |

**Installation chain**: `erp:install` (in `plugin-manager`) → runs migrations → calls `DatabaseSeeder` → delegates to plugin-specific seeders for permissions, default data, and system configuration.

[VERIFIED]
Evidence: `database/seeders/DatabaseSeeder.php`; `database/seeders/ShieldSeeder.php`; `database/seeders/CurrencySeeder.php`

### Factories

| Factory | Model | Note |
|:---|:---|:---|
| [`UserFactory`](database/factories/UserFactory.php) | `Webkul\Security\Models\User` | **Not** `App\Models\User`; generates name, email, verified timestamp, hashed password |

[VERIFIED]
Evidence: `database/factories/UserFactory.php` lines 25, 32–41

---

## API Documentation Infrastructure (Scribe)

### Configuration

**File**: [`config/scribe.php`](config/scribe.php)

| Setting | Value |
|:---|:---|
| Route matching | `admin/api/*` prefix |
| Output type | `external_laravel` (Blade view + OpenAPI spec) |
| OpenAPI generator | `Webkul\Support\ScalarOpenApiGenerator` |
| Base URL | `config('app.url')` |
| Auth strategy | Bearer token (`POST /admin/api/v1/login`) |

### Architecture

- **Root `routes/api.php` is empty**. All API routes are registered by individual plugins (accounts, partners, products, sales, security, support).
- **Scribe generates** OpenAPI YAML files to `.scribe/endpoints/` (10 files covering 9 API groups + 1 custom).
- **Scalar viewer** at [`resources/views/scribe/index.blade.php`](resources/views/scribe/index.blade.php) renders the Scalar API reference using the generated OpenAPI spec.
- **Customization files**: `.scribe/auth.md` and `.scribe/intro.md` provide the authentication guide and introduction text injected into the generated documentation.

[VERIFIED]
Evidence: `config/scribe.php`; `.scribe/` directory; `resources/views/scribe/index.blade.php`; `routes/api.php`

---

## Frontend Build Pipeline

### Vite Configuration

**File**: [`vite.config.js`](vite.config.js)

Entry points:
1. `resources/css/app.css` — Application CSS with Tailwind and RTL support
2. `resources/js/app.js` — Application JavaScript (imports `bootstrap.js` which sets up Axios)
3. `plugins/webkul/barcode/resources/dist/barcode.css` — Barcode plugin styles
4. `plugins/webkul/barcode/resources/dist/barcode.js` — Barcode plugin scripts

Hot module replacement is enabled (`refresh: true`).

### CSS Processing

`postcss.config.js` uses `@tailwindcss/postcss` (Tailwind CSS v4 PostCSS plugin). `tailwind.config.js` scans Laravel pagination views, Filament language-switch vendor views, storage compiled views, and all resource files.

### JavaScript

`resources/js/app.js` imports `bootstrap.js` which only sets up Axios with the `X-Requested-With: XMLHttpRequest` header.

[VERIFIED]
Evidence: `vite.config.js`; `postcss.config.js`; `tailwind.config.js`; `package.json`; `resources/js/app.js`; `resources/js/bootstrap.js`

---

## Bootstrap & Exception Handling

### Application Bootstrap

**File**: [`bootstrap/app.php`](bootstrap/app.php)

1. **Routing**: Registers `routes/web.php`, `routes/api.php`, `routes/console.php`, and health check at `/up`.
2. **Global web middleware**: Appends `SetLocale` to the web middleware stack.
3. **Proxy trust**: Trusts all proxies (`at: '*'`).
4. **Exception handling**: Renders 7 exception types with structured JSON responses for API requests (`api/*` or `expectsJson()`):

| Exception | HTTP Status | Notes |
|:---|:---:|:---|
| `MissingJournalException` | 422 | Also shows Filament notification for web requests |
| `ValidationException` | 422 | Includes validation errors array |
| `AuthenticationException` | 401 | "Unauthenticated." |
| `AuthorizationException` | 403 | "This action is unauthorized." |
| `AccessDeniedHttpException` | 403 | "This action is unauthorized." |
| `ModelNotFoundException` | 404 | "Resource not found." |
| `NotFoundHttpException` | 404 | "The requested resource was not found." |
| `Throwable` (generic) | 500 | Debug info in non-production; generic message in production |

[VERIFIED]
Evidence: `bootstrap/app.php` lines 16–113

---

## Testing Foundation

### PHP Testing (Pest v4)

Documented in [`docs/ai/testing-rules.md`](../ai/testing-rules.md). Application-layer specifics:

- **Base TestCase** (`tests/TestCase.php`): Calls `TestBootstrapHelper::ensureERPInstalled()` before each test to ensure the ERP is installed and seeded.
- **Pest configuration** (`tests/Pest.php`): Uses `DatabaseTransactions` for isolation. Discovers plugin tests via `'../plugins/*/*/tests/Feature'`.
- **PHPUnit config** (`phpunit.xml`): Defines 11 plugin-specific test suites. **No root-level Feature or Unit test directories exist** — all PHP tests live within plugin directories.

### E2E Testing (Playwright)

**Directory**: [`tests/e2e-pw/`](tests/e2e-pw/)

A Playwright-based end-to-end test suite with the following structure:

| Component | Files |
|:---|:---|
| **Page Objects** | 6 files: plugin management, company management, user management, sales, purchases, inventories, website |
| **Test Specs** | 15 spec files covering plugins, companies, users, sales (4 specs), purchases (5 specs), inventories (5 specs), website (2 specs) |
| **Utilities** | `setup.ts` (test setup/authentication), `utils/admin.ts` (admin helpers), `locator/erp_locator.ts` (page element locators) |
| **Config** | `playwright.config.ts` |

[VERIFIED]
Evidence: `tests/` directory listing; `tests/e2e-pw/` complete file listing

---

## Project Configuration

### Architecturally Significant Configuration Files

| Config File | Architectural Significance |
|:---|:---|
| [`config/app.php`](config/app.php) | `supported_locales` (5 locales with RTL flags), `currency` (base currency code) |
| [`config/auth.php`](config/auth.php) | Dual guards (`web` → `Webkul\Security\Models\User`, `customer` → `Webkul\Website\Models\Partner`), dual password brokers |
| [`config/filament-shield.php`](config/filament-shield.php) | Permission generation rules, super admin configuration |
| [`config/permission.php`](config/permission.php) | Spatie Permission model classes, cache configuration |
| [`config/sanctum.php`](config/sanctum.php) | Stateful domains, API token guard |
| [`config/scribe.php`](config/scribe.php) | API documentation generation, route matching, authentication strategy |
| [`config/query-builder.php`](config/query-builder.php) | Spatie QueryBuilder defaults for API filtering |
| [`config/settings.php`](config/settings.php) | Spatie Laravel Settings configuration |

Standard Laravel configuration files (cache, database, filesystems, logging, mail, queue, session) use default framework settings and are not architecturally significant.

### Other Project-Level Files

| File | Purpose |
|:---|:---|
| [`.env.example`](.env.example) | Environment template: MySQL default, database sessions/cache/queue, log channel, NativePHP barcode config |
| [`pint.json`](pint.json) | Laravel Pint code style: `laravel` preset, `concat_space: none`, `=> align` operator spacing |
| [`docker-compose.yml`](docker-compose.yml) | Docker configuration with production setup under `docker/production/` |

[VERIFIED]
Evidence: `config/` directory listing; `.env.example`; `pint.json`; `docker-compose.yml`
