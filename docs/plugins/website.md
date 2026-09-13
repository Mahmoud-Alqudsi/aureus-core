---
status: verified
source_of_truth: source-code
last_verified: 2026-08-31
scope: plugins/webkul/website
confidence: high
---

# Plugin: Website (`website`)

## Status
[VERIFIED]
Active Optional Module. Registered in `bootstrap/providers.php:65` as `Webkul\Website\WebsiteServiceProvider::class`.

## Core/Optional
[VERIFIED]
**Optional Plugin**. Configured as an optional domain module without calling `$package->isCore()` (`plugins/webkul/website/src/WebsiteServiceProvider.php:23-48`). Execution and Filament UI contribution are gated by runtime installation verification via `Package::isPluginInstalled('website')` (`plugins/webkul/website/src/WebsitePlugin.php:36`).

## Enabled/Disabled
[VERIFIED]
**Conditionally Enabled (Database-Gated)**. `WebsiteServiceProvider` registers the package and configures panel integration via `Panel::configureUsing()`, but Filament admin and customer panel resources, pages, clusters, and widgets are discovered and registered only when `Package::isPluginInstalled('website')` returns `true` (`plugins/webkul/website/src/WebsitePlugin.php:36-103`). When uninstalled, `WebsiteServiceProvider::packageBooted()` registers a fallback route at `/` redirecting unauthenticated requests directly to the admin login page (`plugins/webkul/website/src/WebsiteServiceProvider.php:54-60`).

## Purpose
[VERIFIED]
The `website` module provides the public web portal, translatable Content Management System (CMS) pages, contact settings, and the customer portal authentication architecture for Aureus ERP:

1. **Customer Portal Authentication Engine (`partners_partners` Auth Extension)**:
   - Extends the Core `partners_partners` table with authentication credentials (`password`, `remember_token`, `email_verified_at`, `is_active`, and `last_login_at`).
   - Powers the dedicated `customer` Filament panel (mounted at `/`) using the `customer` authentication guard and `customers` Eloquent user provider pointing to `Webkul\Website\Models\Partner`.
   - Implements customer authentication lifecycle pages: Login with rate limiting, User Registration, Password Reset Request, Password Reset Execution, and automatic `last_login_at` tracking via Laravel's `Illuminate\Auth\Events\Login` event.

2. **Translatable CMS Page Management (`PageResource` & `website_pages`)**:
   - Manages translatable web pages (`website_pages`) with JSON-backed multi-locale titles, rich text content, and SEO metadata (`meta_title`, `meta_keywords`, `meta_description`).
   - Provides full administrative lifecycle management (Draft, Publish, Soft Delete, Restore, Force Delete) with preset table views (`Archived`).
   - Handles public frontend page rendering on the `customer` panel (`/{record}` by slug) with automatic 404 aborts for unpublished pages.

3. **Public Homepage & Navigation Orchestration**:
   - Implements the default customer homepage (`Webkul\Website\Filament\Customer\Pages\Homepage`) mounted at `/`, dynamically rendering content from the CMS page with slug `home`.
   - Injects published pages marked `is_header_visible = true` into the customer panel top navigation.
   - Registers panel render hooks: `PanelsRenderHook::TOPBAR_END` for login/registration auth links and `PanelsRenderHook::FOOTER` for the multi-column footer displaying useful links, contact info, and 10 social platform links.

4. **Administrative Portal Access Management (`PortalAccess` & `PortalContributions`)**:
   - Injects portal access badges, filter constraints, infolist sections, and action groups into the Core `PartnerResource` via `PartnerSchemaRegistry`.
   - Enables administrators to grant portal access (generating random secure credentials and emailing a set-password link), manually change customer passwords, send reset links, or revoke portal access.

5. **Website Dashboard & Multi-Channel Contact Settings**:
   - Provides `WebsiteDashboard` with date range and author filtering, showing page/draft counts and 6 blog performance widgets when the `blogs` plugin is installed.
   - Manages `ContactSettings` (email, phone, Twitter/X, Facebook, Instagram, WhatsApp, YouTube, LinkedIn, Pinterest, TikTok, GitHub, Slack).

## Service Provider
[VERIFIED]
- **Class**: `Webkul\Website\WebsiteServiceProvider` (`plugins/webkul/website/src/WebsiteServiceProvider.php:17`)
- **Inheritance**: Extends `Webkul\PluginManager\PackageServiceProvider`
- **Registration & Configuration**:
  - `configureCustomPackage(Package $package)`:
    - Sets package name to `website` (`WebsiteServiceProvider::$name = 'website'`).
    - Registers view namespace `website` via `hasViews()`.
    - Registers translation namespace via `hasTranslations()`.
    - Registers 4 database migrations via `hasMigrations()`:
      - `2025_03_10_094011_create_website_pages_table`
      - `2025_03_10_064655_alter_partners_partners_table`
      - `2026_08_03_100000_add_last_login_at_to_partners_partners_table`
      - `2026_08_13_000001_make_website_pages_translatable`
    - Executes migrations via `runsMigrations()`.
    - Registers seeder class: `Webkul\Website\Database\Seeders\DatabaseSeeder`.
    - Registers settings migration: `2025_03_10_094021_create_website_contact_settings` via `hasSettings()` and `runsSettings()`.
    - Registers installation hook via `hasInstallCommand()` (`installDependencies()`, `runsMigrations()`, `runsSeeders()`).
    - Registers uninstallation hook via `hasUninstallCommand()`.
    - Sets package icon identifier to `website` (`icon('website')`).
    - Does **not** call `$package->isCore()` (confirming optional plugin status).
    - Does **not** declare runtime dependencies (`hasDependencies()` is omitted).
  - `packageBooted()`:
    - Registers custom stylesheet: `resources/dist/website.css` via `FilamentAsset::register()`.
    - Checks `Package::isPluginInstalled('website')`. If not installed, defines fallback route `Route::get('/', fn () => redirect()->route('filament.admin.auth.login'))` and returns early.
    - If installed, calls `PortalContributions::register()`.
    - Listens for `Illuminate\Auth\Events\Login` event; if the login event guard is `'customer'`, updates `$event->user->forceFill(['last_login_at' => now()])->saveQuietly()`.
  - `packageRegistered()`:
    - Registers `WebsitePlugin::make()` with all Filament panels via `Panel::configureUsing()`.
    - Binds `Filament\Auth\Http\Responses\Contracts\LogoutResponse` to `Webkul\Website\Http\Responses\LogoutResponse`.

## Filament Plugin Class
[VERIFIED]
- **Class**: `Webkul\Website\WebsitePlugin` (`plugins/webkul/website/src/WebsitePlugin.php:22`)
- **Implements**: `Filament\Contracts\Plugin`
- **Plugin ID**: `website` (`getId(): string`)
- **Singleton Factory**: `WebsitePlugin::make()` resolves `app(static::class)`.
- **Panel Registration Logic**:
  - Checks if plugin is installed in database via `Package::isPluginInstalled($this->getId())`; returns early if false (`plugins/webkul/website/src/WebsitePlugin.php:36-38`).
  - **When Panel ID is `'customer'` (`$panel->getId() == 'customer'`)**:
    - Registers authentication pages:
      - `login(Login::class)` (`Webkul\Website\Filament\Customer\Auth\Login`)
      - `registration(Register::class)` (`Webkul\Website\Filament\Customer\Auth\Register`)
      - `passwordReset(RequestPasswordReset::class, ResetPassword::class)`
    - Discovers Customer Resources: `plugins/webkul/website/src/Filament/Customer/Resources` (`Webkul\Website\Filament\Customer\Resources`)
    - Discovers Customer Pages: `plugins/webkul/website/src/Filament/Customer/Pages` (`Webkul\Website\Filament\Customer\Pages`)
    - Discovers Customer Clusters: `plugins/webkul/website/src/Filament/Customer/Clusters` (`Webkul\Website\Filament\Customer\Clusters`)
    - Registers User Menu Item `'my_account'` pointing to `Account::getUrl()`.
    - Injects dynamic header navigation items from published pages with `is_header_visible = true` (`getNavigationItems()`).
    - Registers render hook `PanelsRenderHook::TOPBAR_END` rendering `website::filament.customer.header.auth-links`.
    - Registers render hook `PanelsRenderHook::FOOTER` rendering `website::filament.customer.footer.index` with footer pages, contact settings, and social links.
  - **When Panel ID is `'admin'` (`$panel->getId() == 'admin'`)**:
    - Discovers Admin Resources: `plugins/webkul/website/src/Filament/Admin/Resources` (`Webkul\Website\Filament\Admin\Resources`)
    - Discovers Admin Pages: `plugins/webkul/website/src/Filament/Admin/Pages` (`Webkul\Website\Filament\Admin\Pages`)
    - Discovers Admin Clusters: `plugins/webkul/website/src/Filament/Admin/Clusters` (`Webkul\Website\Filament\Admin\Clusters`)
    - Discovers Admin Widgets: `plugins/webkul/website/src/Filament/Admin/Widgets` (`Webkul\Website\Filament\Admin\Widgets`)
- **Boot**: Empty method stub (`boot(Panel $panel)`).

## Composer Dependencies
[VERIFIED]
Defined in `plugins/webkul/website/composer.json`:
- **Package Name**: `webkul/website`
- **Description**: `Website for customer`
- **Autoload PSR-4**:
  - `Webkul\Website\`: `src/`
  - `Webkul\Website\Database\Factories\`: `database/factories/`
  - `Webkul\Website\Database\Seeders\`: `database/seeders/`
- **Autoload-dev PSR-4**:
  - `Webkul\Website\Tests\`: `tests/`
- **Require Dependencies**: None declared in plugin `composer.json` (inherits root Laravel, Filament, Spatie Translatable, and Spatie LaravelSettings dependencies).

## Runtime Plugin Dependencies
[VERIFIED]
- **Declared Runtime Dependencies (`Package::hasDependencies([...])`)**: None (`—`). The plugin does not declare dependencies in `WebsiteServiceProvider`.
- **Implicit Core Dependencies**:
  - `partners`: Target of schema alterations on `partners_partners`, base `Partner` model, and `PartnerSchemaRegistry` extension hooks.
  - `security`: Authenticated `User` model (`creator_id` foreign keys and audit tracking).
  - `support`: Core settings infrastructure, `NavigationGroup` enums, and Translatable traits.
  - `plugin-manager`: Base `PackageServiceProvider` and `Package::isPluginInstalled()` runtime gates.
  - `table-views`: Preset table tab filtering on `PageResource` (`HasTableViews`).

## Directory Structure
[VERIFIED]
```text
plugins/webkul/website/
├── composer.json
├── package.json
├── postcss.config.js
├── tailwind.config.js
├── config/
│   └── filament-shield.php
├── database/
│   ├── factories/
│   │   ├── PageFactory.php
│   │   └── PartnerFactory.php
│   ├── migrations/
│   │   ├── 2025_03_10_064655_alter_partners_partners_table.php
│   │   ├── 2025_03_10_094011_create_website_pages_table.php
│   │   ├── 2026_08_03_100000_add_last_login_at_to_partners_partners_table.php
│   │   └── 2026_08_13_000001_make_website_pages_translatable.php
│   ├── seeders/
│   │   ├── DatabaseSeeder.php
│   │   └── WebsitePageSeeder.php
│   └── settings/
│       └── 2025_03_10_094021_create_website_contact_settings.php
├── resources/
│   ├── css/
│   │   └── index.css
│   ├── dist/
│   │   └── website.css
│   ├── lang/
│   │   ├── ar/
│   │   ├── en/
│   │   │   └── filament/
│   │   │       ├── admin/
│   │   │       │   ├── clusters/
│   │   │       │   │   ├── configurations.php
│   │   │       │   │   └── settings/
│   │   │       │   │       └── pages/
│   │   │       │   │           └── manage-contacts.php
│   │   │       │   ├── pages/
│   │   │       │   │   └── dashboard.php
│   │   │       │   ├── portal-access.php
│   │   │       │   ├── resources/
│   │   │       │   │   ├── page.php
│   │   │       │   │   ├── page/
│   │   │       │   │   │   └── pages/
│   │   │       │   │   │       ├── create-record.php
│   │   │       │   │   │       ├── edit-record.php
│   │   │       │   │   │       ├── list-records.php
│   │   │       │   │   │       └── view-record.php
│   │   │       │   │   └── partner.php
│   │   │       │   └── widgets/
│   │   │       │       ├── blog-chart.php
│   │   │       │       └── stats-overview.php
│   │   │       ├── app.php
│   │   │       └── customer/
│   │   │           ├── clusters/
│   │   │           │   └── account.php
│   │   │           └── pages/
│   │   │               └── auth/
│   │   │                   ├── login.php
│   │   │                   ├── register.php
│   │   │                   └── password-reset/
│   │   │                       ├── request-password-reset.php
│   │   │                       └── reset-password.php
│   │   ├── es/
│   │   ├── fr/
│   │   └── pt_BR/
│   └── views/
│       └── filament/
│           └── customer/
│               ├── footer/
│               │   └── index.blade.php
│               ├── header/
│               │   └── auth-links.blade.php
│               ├── pages/
│               │   ├── auth/
│               │   │   ├── login.blade.php
│               │   │   ├── register.blade.php
│               │   │   └── password-reset/
│               │   │       ├── request-password-reset.blade.php
│               │   │       └── reset-password.blade.php
│               │   └── homepage.blade.php
│               └── resources/
│                   └── page/
│                       └── pages/
│                           └── view-record.blade.php
└── src/
    ├── Filament/
    │   ├── Admin/
    │   │   ├── Actions/
    │   │   │   └── Portal/
    │   │   │       ├── ChangePortalPasswordAction.php
    │   │   │       ├── GrantPortalAccessAction.php
    │   │   │       ├── PortalAccessActionGroup.php
    │   │   │       ├── RevokePortalAccessAction.php
    │   │   │       └── SendPortalPasswordResetAction.php
    │   │   ├── Clusters/
    │   │   │   ├── Configurations.php
    │   │   │   ├── PluginSettings.php
    │   │   │   └── Settings/
    │   │   │       └── Pages/
    │   │   │           └── ManageContacts.php
    │   │   ├── Pages/
    │   │   │   ├── Settings/
    │   │   │   │   └── ManageContacts.php
    │   │   │   └── WebsiteDashboard.php
    │   │   ├── Resources/
    │   │   │   ├── PageResource.php
    │   │   │   ├── PageResource/
    │   │   │   │   ├── Pages/
    │   │   │   │   │   ├── CreatePage.php
    │   │   │   │   │   ├── EditPage.php
    │   │   │   │   │   ├── ListPages.php
    │   │   │   │   │   └── ViewPage.php
    │   │   │   │   ├── Schemas/
    │   │   │   │   │   ├── PageForm.php
    │   │   │   │   │   └── PageInfolist.php
    │   │   │   │   └── Tables/
    │   │   │   │       └── PagesTable.php
    │   │   │   ├── PartnerResource.php
    │   │   │   └── PartnerResource/
    │   │   │       └── Pages/
    │   │   │           ├── CreatePartner.php
    │   │   │           ├── EditPartner.php
    │   │   │           ├── ListPartners.php
    │   │   │           ├── ManageAddresses.php
    │   │   │           ├── ManageContacts.php
    │   │   │           └── ViewPartner.php
    │   │   └── Widgets/
    │   │       ├── BlogAuthorsChart.php
    │   │       ├── BlogChart.php
    │   │       ├── BlogStatusPieChart.php
    │   │       ├── CategoriesPieChart.php
    │   │       ├── RecentBlogsTable.php
    │   │       ├── StatsOverview.php
    │   │       └── TopCategoriesTable.php
    │   └── Customer/
    │       ├── Auth/
    │       │   ├── Login.php
    │       │   ├── Register.php
    │       │   └── PasswordReset/
    │       │       ├── RequestPasswordReset.php
    │       │       └── ResetPassword.php
    │       ├── Clusters/
    │       │   └── Account.php
    │       ├── Pages/
    │       │   └── Homepage.php
    │       └── Resources/
    │           ├── PageResource.php
    │           └── PageResource/
    │               └── Pages/
    │                   └── ViewPage.php
    ├── Http/
    │   ├── Resources/
    │   │   └── V1/
    │   │       └── PageResource.php
    │   └── Responses/
    │       └── LogoutResponse.php
    ├── Models/
    │   ├── Page.php
    │   └── Partner.php
    ├── Policies/
    │   ├── PagePolicy.php
    │   └── PartnerPolicy.php
    ├── PortalContributions.php
    ├── Settings/
    │   └── ContactSettings.php
    ├── Support/
    │   └── PortalAccess.php
    ├── WebsitePlugin.php
    └── WebsiteServiceProvider.php
```

*Note: The `website` module contains **no `routes/` directory** and **no `tests/` directory**.*

## Models
[VERIFIED]
The `website` plugin defines 2 Eloquent models:

| Model Class | Base Class / Table | Traits | Company Isolation | Description |
| :--- | :--- | :--- | :--- | :--- |
| `Page` | `Illuminate\Database\Eloquent\Model` / `website_pages` | `HasCustomFields`, `HasFactory`, `HasTranslations`, `SoftDeletes` | No (Global Master) | Translatable CMS web page layout, content, slug, header/footer visibility toggles, and SEO attributes. |
| `Partner` | `Webkul\Partner\Models\Partner` / `partners_partners` | Inherited from base `Partner` (`BelongsToCompany`, `HasChatter`, `HasCustomFields`, `HasOwnershipScope`, `Notifiable`, `SoftDeletes`) | Optional (`BelongsToCompany`) | Authenticatable partner entity proxy configured with password hashing, hidden credentials, and login tracking. |

### Model Details

#### `Webkul\Website\Models\Page`
- **Table**: `website_pages`
- **Translatable Columns (`$translatable`)**: `title`, `content`, `meta_title`, `meta_keywords`, `meta_description`
- **Fillable**: `title`, `content`, `slug`, `is_published`, `published_at`, `is_header_visible`, `is_footer_visible`, `meta_title`, `meta_keywords`, `meta_description`, `creator_id`
- **Casts**: `is_published => boolean`, `is_header_visible => boolean`, `is_footer_visible => boolean`, `published_at => datetime`
- **Relationships**:
  - `creator(): BelongsTo` -> `Webkul\Security\Models\User`
- **Boot**: Automatically fills `creator_id ??= Auth::id()` on record creation.

#### `Webkul\Website\Models\Partner`
- **Extends**: `Webkul\Partner\Models\Partner` (which extends `Illuminate\Foundation\Auth\User as Authenticatable` and implements `Filament\Models\Contracts\FilamentUser`).
- **Hidden Attributes (`$hidden`)**: `password`, `remember_token`
- **Constructor Extension**:
  - Merges fillable: `password`, `is_active`
  - Merges casts: `email_verified_at => datetime`, `password => hashed`, `last_login_at => datetime`
- **Panel Authorization**: Inherits `canAccessPanel(Panel $panel): bool` returning `true`.

## Database
[VERIFIED]

### Physical Tables Owned (1 Table)

#### `website_pages`
- `id`: `bigint unsigned` (Primary Key, Auto Increment)
- `title`: `json` (Translatable title string)
- `content`: `json` (Translatable HTML rich-text content)
- `slug`: `varchar(255)` (Unique page routing slug)
- `is_published`: `boolean` (Default `0`)
- `is_header_visible`: `boolean` (Default `0` — controls dynamic top-nav inclusion)
- `is_footer_visible`: `boolean` (Default `0` — controls dynamic footer inclusion)
- `published_at`: `datetime` (Nullable publication timestamp)
- `meta_title`: `json` (Translatable SEO meta title, Nullable)
- `meta_keywords`: `json` (Translatable SEO meta keywords, Nullable)
- `meta_description`: `json` (Translatable SEO meta description, Nullable)
- `creator_id`: `foreignId` -> `users.id` (Nullable, `nullOnDelete()`)
- `deleted_at`: `timestamp` (Soft deletes support)
- `created_at`, `updated_at`: `timestamps`

### Physical Table Mutations (1 Table Altered)

#### `partners_partners` (Core `partners` table)
- `email_verified_at`: `timestamp` (Nullable) — Added by `2025_03_10_064655_alter_partners_partners_table.php`
- `is_active`: `boolean` (Default `true`) — Added by `2025_03_10_064655_alter_partners_partners_table.php`
- `password`: `varchar(255)` (Nullable, Hashed) — Added by `2025_03_10_064655_alter_partners_partners_table.php`
- `remember_token`: `varchar(100)` (Nullable) — Added by `2025_03_10_064655_alter_partners_partners_table.php`
- `last_login_at`: `timestamp` (Nullable) — Added by `2026_08_03_100000_add_last_login_at_to_partners_partners_table.php`

### Database Settings Registry (`settings` table via Spatie LaravelSettings)
Registered via `2025_03_10_094021_create_website_contact_settings.php` under group `website_contact`:
- `website_contact.email`: `support@example.com`
- `website_contact.phone`: `+1234567890`
- `website_contact.twitter`: `username`
- `website_contact.facebook`: `username`
- `website_contact.instagram`: `username`
- `website_contact.linkedin`: `username`
- `website_contact.pinterest`: `username`
- `website_contact.tiktok`: `username`
- `website_contact.github`: `username`
- `website_contact.slack`: `username`
- `website_contact.whatsapp`: `username`
- `website_contact.youtube`: `username`

## Customer Panel Authentication Architecture & Login Flow
[VERIFIED]

The `website` module provides the complete authentication backbone for Aureus ERP's secondary customer-facing panel (`customer` panel mounted at `/`):

### 1. Panel & Guard Configuration
- **Panel Provider**: `App\Providers\Filament\CustomerPanelProvider`
  - Panel ID: `'customer'`
  - Route Prefix: `'/'` (`path('/')`)
  - Home URL: `url('/')` (`homeUrl(url('/'))`)
  - Auth Guard: `'customer'` (`authGuard('customer')`)
  - Password Broker: `'customers'` (`authPasswordBroker('customers')`)
- **Guard & Provider Definitions (`config/auth.php`)**:
  ```php
  'guards' => [
      'customer' => [
          'driver'   => 'session',
          'provider' => 'customers',
      ],
  ],
  'providers' => [
      'customers' => [
          'driver' => 'eloquent',
          'model'  => Webkul\Website\Models\Partner::class,
      ],
  ],
  'passwords' => [
      'customers' => [
          'provider' => 'customers',
          'table'    => 'password_reset_tokens',
          'expire'   => 60,
          'throttle' => 60,
      ],
  ],
  ```

### 2. End-to-End Customer Login Flow
1. **Request Submission**: The customer visits `/login` (rendered by `Webkul\Website\Filament\Customer\Auth\Login`) and submits their `email`, `password`, and optional `remember` checkbox.
2. **Rate Limiting**: `Login::authenticate()` invokes `WithRateLimiting` with a limit of 5 requests before throttling (`plugins/webkul/website/src/Filament/Customer/Auth/Login.php:46`).
3. **Guard Attempt**: `Filament::auth()->attempt(...)` executes against the `customer` session guard. The `customers` provider queries `partners_partners` by `email` and validates the plaintext password against the `partners_partners.password` bcrypt hash.
4. **Panel Authorization Check**: The authenticated `Partner` instance is checked via `$user->canAccessPanel($panel)` (which returns `true` via `Webkul\Partner\Models\Partner::canAccessPanel`).
5. **Session Regeneration & Login Response**: Session is regenerated (`session()->regenerate()`), and `LoginResponse` redirects the customer to their intended URL or customer portal dashboard.
6. **Login Event & Timestamp Tracking**:
   - Laravel fires the core `Illuminate\Auth\Events\Login` event with `$event->guard = 'customer'`.
   - `WebsiteServiceProvider::packageBooted()` intercepts the event:
     ```php
     Event::listen(Login::class, function (Login $event): void {
         if ($event->guard !== 'customer') {
             return;
         }
         $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
     });
     ```
   - Silently persists the current timestamp to `partners_partners.last_login_at` without triggering Eloquent observer events.

### 3. Customer Registration & Password Reset
- **Registration (`Register.php`)**: Rate-limited to 2 attempts. Resolves model via `Filament::auth()->getProvider()->getModel()` (`Webkul\Website\Models\Partner`), creates the partner record with a hashed password, dispatches `Registered` event, and automatically logs the user in.
- **Request Password Reset (`RequestPasswordReset.php`)**: Rate-limited to 2 attempts. Calls `Password::broker('customers')->sendResetLink(...)`, generating a token in `password_reset_tokens` and dispatching `Filament\Auth\Notifications\ResetPassword`.
- **Reset Password Execution (`ResetPassword.php`)**: Validates token and email against `password_reset_tokens`, updates `partners_partners.password` with `Hash::make()`, refreshes `remember_token`, and fires `Illuminate\Auth\Events\PasswordReset`.

### 4. Admin-Side Portal Management (`PortalAccess` & `PortalContributions`)
- **Portal Availability Check**: `PortalAccess::isAvailable()` verifies `Package::isPluginInstalled('website')` and customer panel presence.
- **Access Check**: `PortalAccess::hasAccess($partner)` verifies `filled($partner->password)`.
- **Grant Access Action (`GrantPortalAccessAction`)**: Generates a secure random 40-character hashed password on the partner record and triggers `PortalAccess::sendSetPasswordLink($partner)` to email the customer a setup link.
- **Direct Password Change (`ChangePortalPasswordAction`)**: Allows an administrator to manually override a partner's portal password directly from the admin panel with confirmation validation.
- **Send Reset Link Action (`SendPortalPasswordResetAction`)**: Resends the customer password reset email.
- **Revoke Access Action (`RevokePortalAccessAction`)**: Calls `PortalAccess::revoke($partner)`, deleting active reset tokens and setting `partners_partners.password = null`.

## Filament Resources, Pages, Widgets & Clusters
[VERIFIED]

### 1. Customer Panel Components (`src/Filament/Customer/`)

#### Pages
- **`Homepage` (`Webkul\Website\Filament\Customer\Pages\Homepage`)**:
  - Route: `/` (`$routePath = '/'`)
  - Navigation Sort: `-2`
  - Content Resolver: Fetches `Page::where('slug', 'home')->first()?->content`.

#### Resources
- **`PageResource` (`Webkul\Website\Filament\Customer\Resources\PageResource`)**:
  - Model: `Webkul\Website\Models\Page`
  - Record Route Key: `slug` (`$recordRouteKeyName = 'slug'`)
  - Navigation Registration: `protected static bool $shouldRegisterNavigation = false;`
  - Authorization Skip: `protected static bool $shouldSkipAuthorization = true;`
  - Pages: `ViewPage` (`/{record}`)
- **`ViewPage` (`Webkul\Website\Filament\Customer\Resources\PageResource\Pages\ViewPage`)**:
  - Subclasses `Filament\Resources\Pages\ViewRecord`
  - View: `website::filament.customer.resources.page.pages.view-record`
  - `mount($record)`: Resolves record by slug; triggers `abort(404)` if `!$page->is_published`.
  - Dynamic Title Translation: Attempts translation key `website::filament/app.page_titles.{$slug}`, falling back to `$page->title`.

#### Clusters
- **`Account` Cluster (`Webkul\Website\Filament\Customer\Clusters\Account`)**:
  - Base cluster for authenticated customer portal features.
  - Navigation Sort: `1000`
  - Navigation Registration: `shouldRegisterNavigation(): bool => false`
  - Component Access Gate: `canAccessClusteredComponents(): bool => false` (hardcoded gate).

### 2. Admin Panel Components (`src/Filament/Admin/`)

#### Resources

##### `PageResource` (`Webkul\Website\Filament\Admin\Resources\PageResource`)
- **Model**: `Webkul\Website\Models\Page`
- **Slug**: `website/pages`
- **Navigation Group**: `NavigationGroup::Website`
- **Sub-Navigation Position**: `SubNavigationPosition::Top`
- **Traits**: `HasCustomFields`, `LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable`
- **Pages**:
  - `ListPages` (`/`): Implements `HasTableViews`, `TranslatableListRecords`. Provides `Archived` preset tab (`modifyQueryUsing(fn ($q) => $q->onlyTrashed())`), `LocaleSwitcher`, and create action.
  - `CreatePage` (`/create`): Implements `TranslatableCreateRecord` and `LocaleSwitcher`.
  - `ViewPage` (`/{record}`): Implements `TranslatableViewRecord`, `LocaleSwitcher`, and delete action.
  - `EditPage` (`/{record}/edit`): Implements `TranslatableEditRecord`, `LocaleSwitcher`, publish action (`is_published => true, published_at => now()`), draft action (`is_published => false, published_at => null`), and delete action.
- **Schemas & Tables**:
  - `PageForm`: Multi-column schema featuring title with automatic slug generation (`Str::slug`), translatable RichEditor content (with image upload to `website/pages`), translatable SEO section (`meta_title`, `meta_keywords`, `meta_description`), header/footer visibility toggles, and dynamic custom fields.
  - `PageInfolist`: Displays page title, markdown content, SEO metadata entries, audit details (`creator.name`, `published_at`, `created_at`, `updated_at`), publication status icon, and header/footer visibility icons.
  - `PagesTable`: Reorderable columns (`title`, `slug`, `creator.name`, `is_published`, `is_header_visible`, `is_footer_visible`, `updated_at`, `created_at`), grouping by created date, filters (`is_published`, `creator_id` select filter), record actions (View, Edit, Restore, Delete, ForceDelete), and bulk actions (RestoreBulkAction, DeleteBulkAction, ForceDeleteBulkAction).

##### `PartnerResource` (`Webkul\Website\Filament\Admin\Resources\PartnerResource`)
- **Extends**: `Webkul\Partner\Filament\Resources\PartnerResource`
- **Model**: `Webkul\Website\Models\Partner`
- **Slug**: `website/contacts`
- **Navigation Group**: `NavigationGroup::Website`
- **Navigation Label**: `__('website::filament/admin/resources/partner.navigation.title')`
- **Sub-Navigation Position**: `SubNavigationPosition::Top`
- **Record Sub-Navigation Tabs**: `ViewPartner`, `EditPartner`, `ManageContacts`, `ManageAddresses`.
- **Relation Groups**: `Contacts` (`ContactsRelationManager`) and `Addresses` (`AddressesRelationManager`).
- **Pages**:
  - `ListPartners` (`/`), `CreatePartner` (`/create`), `ViewPartner` (`/{record}`), `EditPartner` (`/{record}/edit`), `ManageContacts` (`/{record}/contacts`), `ManageAddresses` (`/{record}/addresses`).

#### Clusters
- **`Configurations` Cluster (`Webkul\Website\Filament\Admin\Clusters\Configurations`)**:
  - Slug: `website/configurations`
  - Navigation Group: `NavigationGroup::Website`
  - Navigation Sort: `3`
- **`PluginSettings` Cluster (`Webkul\Website\Filament\Admin\Clusters\PluginSettings`)**:
  - Slug: `website/settings`
  - Navigation Group: `NavigationGroup::Website`
  - Navigation Sort: `5`

#### Pages & Settings
- **`WebsiteDashboard` (`Webkul\Website\Filament\Admin\Pages\WebsiteDashboard`)**:
  - Route: `website`
  - Navigation Group: `NavigationGroup::Dashboard`
  - Traits: `HasFiltersForm`, `HasPageShield`
  - Filters: `DashboardDateRange` (`from_date`, `to_date`) and Author `Select` filter.
  - Widgets: `StatsOverview`, plus 6 blog widgets when `blogs` is installed (`BlogChart`, `CategoriesPieChart`, `BlogAuthorsChart`, `BlogStatusPieChart`, `TopCategoriesTable`, `RecentBlogsTable`).
- **`ManageContacts` Settings Pages**:
  - `Webkul\Website\Filament\Admin\Clusters\Settings\Pages\ManageContacts`: Registered under Core `Webkul\Support\Filament\Clusters\Settings` cluster, group `'Website'`, permission `'page_website_manage_contacts'`.
  - `Webkul\Website\Filament\Admin\Pages\Settings\ManageContacts`: Registered under local `PluginSettings` cluster (`website/settings`), slug `'manage-contacts'`.
  - Manages `ContactSettings`: contact info (email, phone) and social profile links (Twitter/X, Facebook, Instagram, WhatsApp, YouTube, LinkedIn, Pinterest, TikTok, GitHub, Slack).

#### Widgets (`src/Filament/Admin/Widgets/`)
- **`StatsOverview`**: Displays KPI cards for Total Pages, Published Pages, Draft Pages, and (if `blogs` installed) Total Blogs, Published Blogs, Draft Blogs.
- **`BlogChart`**: Bar chart showing published vs. draft blogs grouped by month bucket (`db_dialect()->monthBucket('created_at')`).
- **`BlogAuthorsChart`**: Bar chart displaying published blog counts per author.
- **`CategoriesPieChart`**: Pie chart displaying blog distribution across categories.
- **`BlogStatusPieChart`**: Pie chart comparing published vs. draft blogs.
- **`TopCategoriesTable`**: Table widget listing top 5 categories ordered by associated blog counts.
- **`RecentBlogsTable`**: Table widget listing the 10 most recent blog posts with author, status badge, and creation timestamp.

## Panels
[VERIFIED]
- **`admin` Panel**: Fully registered. Discovers admin resources (`PageResource`, `PartnerResource`), admin pages (`WebsiteDashboard`), clusters (`Configurations`, `PluginSettings`), and dashboard widgets.
- **`customer` Panel**: Fully registered. Configures auth overrides (`Login`, `Register`, `RequestPasswordReset`, `ResetPassword`), discovers customer resources (`PageResource`), customer pages (`Homepage`), customer clusters (`Account`), top navigation header items, auth links topbar hook, and customer footer hook.

## Services
[VERIFIED]
- **`Webkul\Website\Support\PortalAccess`**: Static domain helper managing customer portal accessibility, password generation, credential validation, reset link dispatching, and access revocation.
- **`Webkul\Website\PortalContributions`**: Static UI registry wiring portal access columns, filter constraints, infolist sections, and action groups into `PartnerSchemaRegistry`.

## Events & Listeners
[VERIFIED]
- **`Illuminate\Auth\Events\Login`**: Intercepted in `WebsiteServiceProvider::packageBooted()`. When `$event->guard === 'customer'`, automatically records the timestamp to `partners_partners.last_login_at` via `$event->user->forceFill(['last_login_at' => now()])->saveQuietly()`.
- **`Filament\Auth\Events\Registered`**: Dispatched by `Register::register()` upon successful customer registration.
- **`Illuminate\Auth\Events\PasswordReset`**: Dispatched by `ResetPassword::resetPassword()` upon successful password reset.

## Observers
[NOT APPLICABLE]
The `website` plugin does not define standalone Eloquent observer classes; lifecycle hooks (`creator_id` assignment on `Page`, last login timestamp tracking) are handled via model boot callbacks and Laravel event listeners.

## Policies
[VERIFIED]
Authorization is governed by Filament Shield and dedicated model policy classes:
- **`Webkul\Website\Policies\PagePolicy`**:
  - Mapped to `Webkul\Website\Models\Page`.
  - Methods: `viewAny`, `view`, `create`, `update`, `delete`, `deleteAny`, `forceDelete`, `forceDeleteAny`, `restore`, `restoreAny` checking permissions `view_any_website_page`, `create_website_page`, etc.
- **`Webkul\Website\Policies\PartnerPolicy`**:
  - Mapped to `Webkul\Website\Models\Partner`.
  - Methods: `viewAny`, `view`, `create`, `update`, `delete`, `deleteAny`, `forceDelete`, `forceDeleteAny`, `restore`, `restoreAny` checking permissions `view_any_website_partner`, `create_website_partner`, etc.
- **Filament Shield Configuration (`plugins/webkul/website/config/filament-shield.php`)**:
  - Registers CRUD, delete, restore, and force-delete permissions for `Admin\PageResource`, `Admin\PartnerResource`, and `Customer\PageResource`.
  - Excludes `Configurations` cluster page from permission generation.

## Routes
[VERIFIED]
- **API Routes (`routes/api.php`)**: None. The plugin defines an API resource transformer (`Webkul\Website\Http\Resources\V1\PageResource`), but owns no dedicated `routes/api.php` file.
- **Web Routes (`routes/web.php`)**: None. Routing is handled entirely through Filament panel discovery, with one programmatic fallback route registered in `WebsiteServiceProvider::packageBooted()`:
  - `Route::get('/', fn () => redirect()->route('filament.admin.auth.login'))` (active only when `website` is uninstalled).

## Settings
[VERIFIED]
- **Settings Class**: `Webkul\Website\Settings\ContactSettings` (`plugins/webkul/website/src/Settings/ContactSettings.php`)
- **Group**: `website_contact` (`ContactSettings::group()`)
- **Schema Migration**: `plugins/webkul/website/database/settings/2025_03_10_094021_create_website_contact_settings.php`
- **Properties**: `email`, `phone`, `twitter`, `facebook`, `instagram`, `whatsapp`, `youtube`, `linkedin`, `pinterest`, `tiktok`, `github`, `slack`.

## Translations
[VERIFIED]
Registered via `$package->hasTranslations()` under namespace `website`. Stored under `plugins/webkul/website/resources/lang/` across 5 supported languages:
- Arabic (`ar`)
- English (`en`)
- Spanish (`es`)
- French (`fr`)
- Brazilian Portuguese (`pt_BR`)

Translation structure:
- `filament/app.php`: Navigation titles, homepage labels, page titles translation key map (`website::filament/app.page_titles.*`), and footer copyright/branding text.
- `filament/admin/resources/page.php`: Page form section titles, fields, SEO labels, settings, table columns, filters, and actions.
- `filament/admin/resources/partner.php`: Partner navigation label.
- `filament/admin/portal-access.php`: Portal access badge labels, modal headings, action confirmations, notifications (email missing, email taken, granted, reset sent, revoked), and infolist section entries.
- `filament/admin/pages/dashboard.php`: Dashboard labels, date range, and author filter placeholders.
- `filament/admin/widgets/stats-overview.php`: KPI card titles and descriptions.
- `filament/admin/widgets/blog-chart.php`: Chart headings, dataset labels, and status badges.
- `filament/admin/clusters/configurations.php`: Configurations cluster navigation label.
- `filament/admin/clusters/settings/pages/manage-contacts.php`: Contact settings section titles and field placeholders.
- `filament/customer/pages/auth/*`: Customer login, registration, password reset request, and password reset form labels, button text, and validation error messages.
- `filament/customer/clusters/account.php`: Customer account cluster navigation label.

## Tests
[VERIFIED]
- **Backend PHP Tests**: **0 test files**. No unit, feature, or integration Pest/PHPUnit test files exist in `plugins/webkul/website/tests/` or in the root `tests/` directory.
- **Browser E2E Tests**: Playwright TypeScript test specs exist at `tests/e2e-pw/tests/06_website/01_websitePages.spec.ts` and `tests/e2e-pw/tests/06_website/02_websiteBlogs.spec.ts`.

## Runtime Dependencies
[VERIFIED]
- **Mandatory Runtime Dependencies**: None declared in `PackageServiceProvider`.
- **Required Core Modules**: `partners`, `security`, `support`, `plugin-manager`, `table-views`.
- **Optional Downstream Consumer Modules**:
  - `blogs`: Declares runtime plugin dependency on `website` (`Package::hasDependencies(['website'])`). Consumes `WebsiteDashboard` to render blog analytics charts and tables.
  - `purchases`: Directly references `Webkul\Website\Filament\Customer\Clusters\Account` and `Webkul\Website\Models\Partner` for customer-side purchase order and quotation self-service.

## Cross-Plugin Relationships
[VERIFIED]

| Interacting Plugin | Nature of Integration | Mechanism |
| :--- | :--- | :--- |
| `partners` [CORE] | Authentication Extension & Schema Mutation | Alters `partners_partners` to add `password`, `remember_token`, `email_verified_at`, `is_active`, and `last_login_at`. Injects portal management UI via `PartnerSchemaRegistry`. |
| `security` [CORE] | Admin Identity & Audit Tracking | `Page` links to `User` via `creator_id`. Shield config integrates role permissions. |
| `support` [CORE] | UI Infrastructure & Settings Registry | Uses `NavigationGroup::Website`, `NavigationGroup::Dashboard`, `Translatable` traits, and Spatie `ContactSettings`. |
| `table-views` [CORE] | Table Filtering Presets | Injects `HasTableViews` and `PresetView` (Archived tab) into `PageResource\Pages\ListPages`. |
| `fields` [CORE] | Custom Field Injection | Uses `HasCustomFields` on `Page` model and `PageResource`. |
| `blogs` [OPTIONAL] | Dashboard Analytics & Downstream Dependency | `blogs` declares `website` as a runtime dependency. `WebsiteDashboard` dynamically renders 6 blog widgets when `blogs` is installed. |
| `purchases` [OPTIONAL] | Customer Portal Cluster Sharing | `purchases` places its customer-facing `PurchaseOrderResource` and `QuotationResource` inside `Webkul\Website\Filament\Customer\Clusters\Account`. |

## Data Flow
[VERIFIED]

```mermaid
flowchart TD
    Customer([Public / Customer User]) -->|Visits /| Homepage[Homepage /]
    Customer -->|Visits /{slug}| PublicPage[Customer PageResource /{slug}]
    Customer -->|Visits /login| CustomerLogin[Customer Login]
    Customer -->|Visits /register| CustomerRegister[Customer Register]
    
    AdminUser([Administrator]) -->|Navigates to /admin/website/pages| AdminPages[Admin PageResource]
    AdminUser -->|Navigates to /admin/website/contacts| AdminContacts[Admin PartnerResource]
    AdminUser -->|Navigates to /admin/website| AdminDashboard[WebsiteDashboard]
    AdminUser -->|Navigates to /admin/settings/website/manage-contacts| ContactSettingsPage[ManageContacts Settings]

    subgraph Customer_Panel ["Filament Customer Panel (Path: /)"]
        Homepage --> PageModel[Page::where('slug', 'home')]
        PublicPage --> ViewRecord[ViewPage::mount -> Checks is_published]
        CustomerLogin --> AuthAttempt[Filament::auth()->attempt()]
        AuthAttempt --> CustomerGuard[Guard: customer / Provider: customers]
        CustomerRegister --> PartnerCreate[Partner::create() with Hashed Password]
    end

    subgraph Auth_Tracking ["Authentication & Event Pipeline"]
        AuthAttempt -->|On Success| LoginEvent[Illuminate\Auth\Events\Login]
        LoginEvent -->|Guard == customer| UpdateLastLogin[Update partners_partners.last_login_at]
    end

    subgraph Admin_Portal_Management ["Portal Access Integration"]
        AdminContacts --> PortalActions[PortalAccessActionGroup]
        PortalActions --> GrantAction[GrantPortalAccessAction -> Generates Password & Sends Link]
        PortalActions --> ChangePassword[ChangePortalPasswordAction -> Updates Password Hash]
        PortalActions --> ResetLink[SendPortalPasswordResetAction -> Sends Broker Link]
        PortalActions --> RevokeAction[RevokePortalAccessAction -> Sets Password = Null]
    end

    subgraph Database_Layer ["Database Layer"]
        PageModel --> DB_Pages[(website_pages)]
        ViewRecord --> DB_Pages
        AdminPages --> DB_Pages
        CustomerGuard --> DB_Partners[(partners_partners)]
        PartnerCreate --> DB_Partners
        UpdateLastLogin --> DB_Partners
        GrantAction --> DB_Partners
        RevokeAction --> DB_Partners
        ContactSettingsPage --> DB_Settings[(settings)]
    end
```

## Business Rules
[VERIFIED]
1. **Customer Panel Guard Isolation**:
   - The customer portal is completely isolated from the administrative authentication layer. It uses the `customer` guard backed by `Webkul\Website\Models\Partner` on the `partners_partners` table, while the `admin` panel uses the `web` guard backed by `Webkul\Security\Models\User` on `users`.
2. **Portal Access Gatekeeper**:
   - A partner is considered to have portal access if and only if `partners_partners.password` is populated (`filled($partner->password)`). Revoking access sets `password = null`, instantly preventing subsequent logins.
3. **Unique Email Constraint for Portal Partners**:
   - When granting portal access, `PortalAccess::isEmailTaken()` verifies that no other partner record across global scopes shares the same email with an active password.
4. **Public CMS Page Visibility**:
   - Unpublished CMS pages (`is_published = false`) immediately trigger an HTTP 404 response on the customer panel (`ViewPage::mount()`), ensuring draft content is never leaked publicly.
5. **Translatable CMS Content Fallback**:
   - CMS titles, rich text content, and SEO attributes are stored as JSON multi-locale dictionaries. Frontend title resolution checks for localization keys under `website::filament/app.page_titles.{$slug}` before falling back to the database title.
6. **Dynamic Top Navigation & Footer Rendering**:
   - Pages appear in the customer panel header only if both `is_published = true` and `is_header_visible = true`.
   - Pages appear in the customer panel footer only if both `is_published = true` and `is_footer_visible = true`.
7. **Uninstalled Plugin Fallback Routing**:
   - When the `website` plugin is not installed in the database, requests to `/` are intercepted and redirected to `filament.admin.auth.login`, preventing an unconfigured customer panel from rendering.

## Extension Points
[VERIFIED]
1. **`PartnerSchemaRegistry` Integration**:
   - Allows other plugins to contribute custom actions, infolist entries, and columns to the partner directory alongside `PortalContributions`.
2. **`Account` Customer Cluster**:
   - Serves as the central extension cluster (`Webkul\Website\Filament\Customer\Clusters\Account`) for downstream modules (such as `purchases`) to register authenticated customer portal resources and pages.
3. **Filament Shield Configuration**:
   - `plugins/webkul/website/config/filament-shield.php` registers resource permissions for administrative role management.
4. **Translatable Content Architecture**:
   - Leverages `Spatie\Translatable\HasTranslations` and `LaraZeus\SpatieTranslatable`, allowing additional application locales to be added seamlessly without schema changes.

## Dangerous Areas
[VERIFIED]
1. **Complete Absence of Backend PHP Tests**:
   - The `website` plugin contains **0 Pest/PHPUnit test files**. Changes to customer login logic, registration password hashing, rate limiting, or portal access granting must be manually validated or covered in E2E suites.
2. **Root Route Interception When Uninstalled**:
   - In `WebsiteServiceProvider::packageBooted()`, if `Package::isPluginInstalled('website')` is false, it registers `Route::get('/', fn () => redirect()->route('filament.admin.auth.login'))`. This aggressively hijacks the application root route `/` whenever `website` is uninstalled.
3. **Hardcoded Customer Account Access Block**:
   - In `Webkul\Website\Filament\Customer\Clusters\Account`, `canAccessClusteredComponents()` contains `return false;` preceding an unreachable `return Filament::auth()->check();`. This prevents clustered customer components from being accessed unless overridden by custom routing or modified.
4. **Immediate Credential Invalidation on Revocation**:
   - `PortalAccess::revoke()` forces `password = null` directly in the database. While preventing new logins, existing active HTTP sessions may remain active until session authentication middleware revalidates password hashes.
5. **Global Unscoped CMS Pages**:
   - `Page` has no `company_id` column and does not use `BelongsToCompany`. In multi-company environments, all CMS pages, headers, footers, and slugs are shared globally across all company tenants.

## Change Impact
[VERIFIED]
- **Database Layer**:
  - Owns `website_pages` table.
  - Alters Core `partners_partners` table (adds `password`, `remember_token`, `email_verified_at`, `is_active`, `last_login_at`).
  - Registers `website_contact` settings in `settings` table.
- **Authentication Layer**:
  - Directly powers the `customer` guard, `customers` provider, and `customers` password broker.
- **UI Layer**:
  - Mounts the entire public customer portal and CMS frontend at `/`.
  - Contributes `PageResource`, `PartnerResource`, `WebsiteDashboard`, `ManageContacts`, and `PortalAccessActionGroup` to the Admin Panel.

## Evidence
[VERIFIED]

| Evidence ID | File Citation | Symbol / Line Citation | Verified Fact |
| :--- | :--- | :--- | :--- |
| E-WEB-001 | `bootstrap/providers.php` | Line 65 | Registration of `Webkul\Website\WebsiteServiceProvider::class` in provider list. |
| E-WEB-002 | `plugins/webkul/website/src/WebsiteServiceProvider.php` | Lines 17–48 | Package configuration with name `'website'`, views, translations, 4 migrations, settings, seeder, and omission of `->isCore()`. |
| E-WEB-003 | `plugins/webkul/website/src/WebsiteServiceProvider.php` | Lines 50–71 | Fallback route redirect to admin login when uninstalled; `PortalContributions::register()` and `Login` event listener tracking `last_login_at`. |
| E-WEB-004 | `plugins/webkul/website/src/WebsitePlugin.php` | Lines 22–103 | Registration of customer panel auth pages, resources, pages, clusters, user menu, topbar/footer render hooks, and admin panel discovery. |
| E-WEB-005 | `plugins/webkul/website/composer.json` | Lines 1–30 | Package name `webkul/website`, description, and PSR-4 autoload configuration. |
| E-WEB-006 | `config/auth.php` | Lines 47–50, 76–79, 114–119 | Definition of `customer` guard (session), `customers` provider (`Webkul\Website\Models\Partner`), and `customers` password broker. |
| E-WEB-007 | `app/Providers/Filament/CustomerPanelProvider.php` | Lines 21–58 | Customer panel configuration with ID `'customer'`, path `'/'`, auth guard `'customer'`, and password broker `'customers'`. |
| E-WEB-008 | `plugins/webkul/website/src/Models/Partner.php` | Lines 1–40 | Authenticatable partner proxy model merging fillable (`password`, `is_active`) and casts (`password => hashed`, `last_login_at => datetime`). |
| E-WEB-009 | `plugins/webkul/website/src/Models/Page.php` | Lines 1–69 | CMS `Page` model with table `website_pages`, translatable attributes, `creator()` relation, and global scoping. |
| E-WEB-010 | `plugins/webkul/website/database/migrations/2025_03_10_094011_create_website_pages_table.php` | Lines 1–45 | Schema definition of `website_pages` table with translatable columns, visibility toggles, and creator foreign key. |
| E-WEB-011 | `plugins/webkul/website/database/migrations/2025_03_10_064655_alter_partners_partners_table.php` | Lines 1–35 | Schema mutation adding `email_verified_at`, `is_active`, `password`, and `remember_token` to `partners_partners`. |
| E-WEB-012 | `plugins/webkul/website/database/migrations/2026_08_03_100000_add_last_login_at_to_partners_partners_table.php` | Lines 1–23 | Schema mutation adding `last_login_at` timestamp to `partners_partners`. |
| E-WEB-013 | `plugins/webkul/website/database/migrations/2026_08_13_000001_make_website_pages_translatable.php` | Lines 1–112 | Migration converting `title`, `content`, `meta_title`, `meta_keywords`, `meta_description` on `website_pages` to JSON. |
| E-WEB-014 | `plugins/webkul/website/src/Support/PortalAccess.php` | Lines 1–85 | Portal access helper methods (`isAvailable`, `hasAccess`, `isEmailTaken`, `sendSetPasswordLink`, `revoke`). |
| E-WEB-015 | `plugins/webkul/website/src/PortalContributions.php` | Lines 1–89 | Registration of portal access badge column, filter constraint, infolist section, and header actions via `PartnerSchemaRegistry`. |
| E-WEB-016 | `plugins/webkul/website/src/Filament/Customer/Auth/Login.php` | Lines 1–190 | Customer login implementation with rate limiting (5), `Filament::auth()->attempt()`, and credential extraction. |
| E-WEB-017 | `plugins/webkul/website/src/Filament/Customer/Auth/Register.php` | Lines 1–257 | Customer registration implementation with rate limiting (2), model resolution, password hashing, and auto-login. |
| E-WEB-018 | `plugins/webkul/website/src/Filament/Customer/Pages/Homepage.php` | Lines 1–48 | Public customer homepage mounted at `/`, resolving content from `Page::where('slug', 'home')`. |
| E-WEB-019 | `plugins/webkul/website/src/Filament/Customer/Resources/PageResource.php` | Lines 1–26 | Customer page viewer resource with record route key `slug` and authorization bypass. |
| E-WEB-020 | `plugins/webkul/website/src/Filament/Admin/Pages/WebsiteDashboard.php` | Lines 1–84 | Website dashboard with date/author filters, `StatsOverview`, and conditional `blogs` widget integration. |
| E-WEB-021 | `plugins/webkul/website/src/Settings/ContactSettings.php` | Lines 1–38 | Spatie settings class defining 12 contact/social properties under group `website_contact`. |
| E-WEB-022 | `plugins/webkul/blogs/src/BlogServiceProvider.php` | Lines 37–39 | Verification that `blogs` declares runtime dependency `hasDependencies(['website'])`. |
