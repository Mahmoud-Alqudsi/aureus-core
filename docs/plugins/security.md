---
status: verified
source_of_truth: source-code
last_verified: 2026-08-29
scope: plugins/webkul/security
confidence: high
---

# Plugin: Security (`security`)

## Status
[VERIFIED]
Active Core Module. Registered explicitly in `bootstrap/providers.php:28` as `Webkul\Security\SecurityServiceProvider::class`.

## Core/Optional
[VERIFIED]
**Core Plugin**. Configured via `$package->isCore()` within `SecurityServiceProvider::configureCustomPackage()` (`plugins/webkul/security/src/SecurityServiceProvider.php:21`).

## Enabled/Disabled
[VERIFIED]
**Always Enabled (Core Infrastructure)**. Core plugin status causes `PackageServiceProvider::boot()` to bypass database installation checks (`Package::isInstalled()`) when loading migrations and routes (`plugins/webkul/plugin-manager/src/PackageServiceProvider.php:131-135, 202`). The `security` module executes unconditionally at boot time across the application.

## Purpose
[VERIFIED]
The `security` module is the primary identity, authentication, authorization, and organizational ownership engine for Aureus ERP. It provides the following foundational architectural capabilities:

1. **User Identity & Lifecycle Management**:
   - Extends the baseline framework `App\Models\User` via `Webkul\Security\Models\User` (`plugins/webkul/security/src/Models/User.php`), adding multi-company scoping, language preferences, creator tracking, active status flags, default company assignment, resource permission levels, and automatic bi-directional synchronization with master data `Partner` records.
   - Bound explicitly in the application container as the concrete implementation of `Illuminate\Contracts\Auth\Authenticatable` (`app/Providers/AppServiceProvider.php:19`) and configured as the default Eloquent auth provider in `config/auth.php:73`.

2. **Hierarchical Ownership & Scoped Authorization (`Bouncer` & `OwnershipScope`)**:
   - Defines the three-tier record access model via enum `PermissionType` (`plugins/webkul/security/src/Enums/PermissionType.php`): `GLOBAL` (access all records), `GROUP` (access records owned by self or team members), and `INDIVIDUAL` (access self-owned records only).
   - Implements `Webkul\Security\Bouncer` (`plugins/webkul/security/src/Bouncer.php`) and helper `bouncer()` (`plugins/webkul/security/src/Helpers/helpers.php`) to resolve the set of authorized user IDs for the authenticated user based on team memberships (`user_team`).
   - Implements global query scope `OwnershipScope` (`plugins/webkul/security/src/Models/Scopes/OwnershipScope.php`) and model trait `HasOwnershipScope` (`plugins/webkul/security/src/Traits/HasOwnershipScope.php`) supporting multiple ownership resolution sources (`OwnerSource::column`, `OwnerSource::relation`, `OwnerSource::pivot`, `OwnerSource::followers`).
   - Implements policy authorization trait `HasScopedPermissions` (`plugins/webkul/security/src/Traits/HasScopedPermissions.php`) evaluating `hasAccess($user, $model, $ownerAttribute)` across global, group, and individual permission levels.

3. **Role-Based Access Control (RBAC) & Filament Shield Integration**:
   - Extends Spatie Laravel Permission via custom models `Webkul\Security\Models\Role` (`plugins/webkul/security/src/Models/Role.php`) and `Webkul\Security\Models\Permission` (`plugins/webkul/security/src/Models/Permission.php`), configured in `config/permission.php:19,30`.
   - Protects system roles (`admin`, `super_admin`, and configured Shield roles) from deletion, renaming, or unauthorized guard modification.
   - Implements optimized permissions caching and bulk permission synchronization via `PermissionRegistrar` (`plugins/webkul/security/src/PermissionRegistrar.php`) and `Role::syncPermissionsByNames()`.
   - Delivers the administrative `RoleResource` (`plugins/webkul/security/src/Filament/Resources/RoleResource.php`) providing categorized, tabbed permission management (Resources, Pages, Widgets) with client-side AlpineJS bulk toggle optimizations.

4. **Multi-Factor Authentication (MFA)**:
   - Configures application-based TOTP multi-factor authentication with recovery codes on the Filament Admin Panel (`app/Providers/Filament/AdminPanelProvider.php:114-117`).
   - Implements `HasAppAuthentication`, `HasAppAuthenticationRecovery`, and `HasEmailAuthentication` contracts on `Webkul\Security\Models\User` backed by encrypted database columns (`app_authentication_secret`, `app_authentication_recovery_codes`, `has_email_authentication`).

5. **Organizational Teams & User Invitations**:
   - Manages team structures via `Team` model (`plugins/webkul/security/src/Models/Team.php`) and `TeamResource` (`plugins/webkul/security/src/Filament/Resources/TeamResource.php`), mapping users to teams via `user_team` junction table.
   - Implements signed-URL user invitation workflows via `Invitation` model (`plugins/webkul/security/src/Models/Invitation.php`), `UserInvitationMail` mailable (`plugins/webkul/security/src/Mail/UserInvitationMail.php`), and `AcceptInvitation` Livewire page (`plugins/webkul/security/src/Livewire/AcceptInvitation.php`).

6. **REST API Authentication**:
   - Delivers public API login endpoint `POST admin/api/v1/login` generating Laravel Sanctum personal access tokens with cross-database case-insensitive email matching.
   - Delivers protected API logout endpoint `POST admin/api/v1/logout` revoking active access tokens.

7. **System & Security Configuration**:
   - Manages global security settings via `UserSettings` (`plugins/webkul/security/src/Settings/UserSettings.php`) and `CurrencySettings` (`plugins/webkul/security/src/Settings/CurrencySettings.php`), surfaced in the Admin Panel Settings cluster via `ManageUsers`, `ManageCurrency`, and `ManageActivity` pages.

## Service Provider
[VERIFIED]
- **Class**: `Webkul\Security\SecurityServiceProvider` (`plugins/webkul/security/src/SecurityServiceProvider.php:12`)
- **Inheritance**: Extends `Webkul\PluginManager\PackageServiceProvider`
- **Registration & Configuration**:
  - `configureCustomPackage(Package $package)`:
    - Sets package name to `'security'`
    - Declares package as core (`$package->isCore()`)
    - Configures views (`hasViews()`) and translations (`hasTranslations()`)
    - Configures routes (`hasRoute('web')`, `hasRoute('api')`)
    - Registers 10 database migrations via `hasMigrations([...])` and enables execution via `runsMigrations()`
    - Registers 2 settings migrations via `hasSettings([...])` and enables execution via `runsSettings()`
  - `packageRegistered()`:
    - Hooks into panel configuration via `Panel::configureUsing()`, registering `SecurityPlugin::make()`
    - Registers class alias `'bouncer'` -> `Webkul\Security\Facades\Bouncer` via `AliasLoader`
    - Binds `'bouncer'` singleton (`Webkul\Security\Bouncer::class`) in the service container
    - Binds `Webkul\Security\PermissionRegistrar` singleton in the service container
  - `packageBooted()`:
    - Requires global helper functions file `plugins/webkul/security/src/Helpers/helpers.php`
    - Registers global Gate before-rule: intercepts ability `'bypass_ownership_scope'` and returns `true` if the authenticated user has the `super_admin` role or the role name configured in `config('filament-shield.super_admin.name')` (`SecurityServiceProvider.php:50-63`)

## Filament Plugin class
[VERIFIED]
- **Class**: `Webkul\Security\SecurityPlugin` (`plugins/webkul/security/src/SecurityPlugin.php:9`)
- **Interface**: Implements `Filament\Contracts\Plugin`
- **Identifier**: `getId()` returns `'security'`
- **Panel Registration**:
  - `register(Panel $panel)` executes conditionally when `$panel->getId() == 'admin'` (`plugins/webkul/security/src/SecurityPlugin.php:24`):
    - Enables password reset on panel (`$panel->passwordReset()`)
    - Discovers resources in `src/Filament/Resources` under namespace `Webkul\Security\Filament\Resources` (`CompanyResource`, `RoleResource`, `TeamResource`, `UserResource`)
    - Discovers pages in `src/Filament/Pages` under namespace `Webkul\Security\Filament\Pages`
    - Discovers clusters in `src/Filament/Clusters` under namespace `Webkul\Security\Filament\Clusters` (`ManageActivity`, `ManageCurrency`, `ManageUsers` in `Settings` cluster)
    - [VERIFIED BUG / TYPO] Calls `->discoverClusters(in: __DIR__.'/Filament/Widgets', for: 'Webkul\\Security\\Filament\\Widgets')` (`plugins/webkul/security/src/SecurityPlugin.php:38-41`). This is a copy-paste artifact; no `Widgets` directory exists under `src/Filament/`.
    - Evaluates `UserSettings::class->enable_reset_password`: if disabled and not running in console, disables password reset on the panel (`$panel->passwordReset(false)`).
- **Boot**: `boot(Panel $panel)` is defined as an empty method stub (`plugins/webkul/security/src/SecurityPlugin.php:52-55`).

## Composer dependencies
[VERIFIED]
- **Local Package Definition**: `plugins/webkul/security/composer.json`
  - Name: `webkul/security`
  - Autoload PSR-4: `Webkul\Security\` -> `src/`, `Webkul\Security\Database\Factories\` -> `database/factories/`, `Webkul\Security\Database\Seeders\` -> `database/seeders/`
  - Autoload-dev PSR-4: `Webkul\Security\Tests\` -> `tests/`
  - Extra Laravel Providers: `Webkul\Security\SecurityServiceProvider`
- **External Dependencies Consumed via Root Composer** (`composer.lock`):
  - `spatie/laravel-permission` (`v6.24.0`): Core RBAC permissions and roles engine (`BaseRole`, `BasePermission`, `HasRoles`).
  - `bezhansalleh/filament-shield` (`4.2.0`): Admin UI permissions manager, role utilities (`Utils`), and shield resources (`RolesRoleResource`).
  - `laravel/sanctum` (`v4.3.3`): API token issuance, verification, and revocation (`HasApiTokens`).
  - `spatie/laravel-settings` (`v3.4.4`): Settings class models and migrations (`UserSettings`, `CurrencySettings`).
  - `filament/filament` (`v5.8.1`): Filament resources, pages, clusters, tables, forms, infolists, and MFA app authentication (`AppAuthentication`).
  - `knuckleswtf/scribe` (`v4.44.0`): API documentation annotations on `AuthController`.

## Runtime plugin dependencies
[VERIFIED]
None (`—`). `SecurityServiceProvider::configureCustomPackage()` does not declare any runtime dependencies via `hasDependencies()` or `hasDependency()`.

## Directory structure
[VERIFIED]
Full verified tree of `plugins/webkul/security/`:

```text
plugins/webkul/security/
├── composer.json
├── config/
├── database/
│   ├── factories/
│   │   ├── InvitationFactory.php
│   │   └── TeamFactory.php
│   ├── migrations/
│   │   ├── 2024_11_11_112529_create_user_invitations_table.php
│   │   ├── 2024_11_12_125715_create_teams_table.php
│   │   ├── 2024_11_12_130019_create_user_team_table.php
│   │   ├── 2024_12_10_101127_add_default_company_id_column_to_users_table.php
│   │   ├── 2024_12_13_130906_add_partner_id_to_users_table.php
│   │   ├── 2025_08_01_071239_alter_teams_table.php
│   │   ├── 2025_08_01_073954_alter_users_table.php
│   │   ├── 2025_08_21_082229_alter_roles_table.php
│   │   ├── 2025_08_21_101646_alter_users_table.php
│   │   └── 2026_01_23_074142_add_multi_factor_auth_columns_in_users_table.php
│   ├── seeders/
│   │   └── DatabaseSeeder.php
│   └── settings/
│       ├── 2024_11_05_042358_create_user_settings.php
│       └── 2025_07_29_064223_create_currency_settings.php
├── resources/
│   ├── lang/
│   │   ├── ar/ (19 translation files)
│   │   ├── en/
│   │   │   ├── enums/
│   │   │   │   ├── company-status.php
│   │   │   │   └── permission-type.php
│   │   │   ├── filament/
│   │   │   │   ├── clusters/
│   │   │   │   │   ├── manage-activity.php
│   │   │   │   │   ├── manage-currency.php
│   │   │   │   │   └── manage-users.php
│   │   │   │   └── resources/
│   │   │   │       ├── company.php
│   │   │   │       ├── role.php
│   │   │   │       ├── role/pages/
│   │   │   │       │   ├── create-role.php
│   │   │   │       │   └── edit-role.php
│   │   │   │       ├── team.php
│   │   │   │       ├── team/pages/manage-team.php
│   │   │   │       ├── user.php
│   │   │   │       └── user/pages/
│   │   │   │           ├── create-user.php
│   │   │   │           ├── edit-user.php
│   │   │   │           ├── list-user.php
│   │   │   │           └── view-user.php
│   │   │   ├── livewire/
│   │   │   │   └── accept-invitation.php
│   │   │   ├── mail/
│   │   │   │   └── user-invitation-mail.php
│   │   │   └── views/emails/
│   │   │       └── user-invitation.php
│   │   ├── es/ (19 translation files)
│   │   ├── fr/ (19 translation files)
│   │   └── pt_BR/ (19 translation files)
│   └── views/
│       ├── emails/
│       │   └── user-invitation.blade.php
│       └── livewire/
│           └── accept-invitation.blade.php
├── routes/
│   ├── api.php
│   └── web.php
└── src/
    ├── Bouncer.php
    ├── Package.php
    ├── PermissionRegistrar.php
    ├── SecurityPlugin.php
    ├── SecurityServiceProvider.php
    ├── Data/
    │   ├── countries.json
    │   ├── currencies.json
    │   └── states.json
    ├── Enums/
    │   ├── CompanyStatus.php
    │   └── PermissionType.php
    ├── Facades/
    │   └── Bouncer.php
    ├── Filament/
    │   ├── Clusters/
    │   │   └── Settings/Pages/
    │   │       ├── ManageActivity.php
    │   │       ├── ManageCurrency.php
    │   │       └── ManageUsers.php
    │   └── Resources/
    │       ├── CompanyResource.php
    │       ├── CompanyResource/
    │       │   ├── Pages/
    │       │   │   ├── CreateCompany.php
    │       │   │   ├── EditCompany.php
    │       │   │   ├── ListCompanies.php
    │       │   │   └── ViewCompany.php
    │       │   └── RelationManagers/
    │       │       └── BranchesRelationManager.php
    │       ├── RoleResource.php
    │       ├── RoleResource/
    │       │   ├── Pages/
    │       │   │   ├── CreateRole.php
    │       │   │   ├── EditRole.php
    │       │   │   ├── ListRoles.php
    │       │   │   └── ViewRole.php
    │       │   ├── Schemas/
    │       │   │   └── RoleForm.php
    │       │   └── Tables/
    │       │       └── RolesTable.php
    │       ├── TeamResource.php
    │       ├── TeamResource/
    │       │   ├── Pages/
    │       │   │   └── ManageTeams.php
    │       │   ├── Schemas/
    │       │   │   ├── TeamForm.php
    │       │   │   └── TeamInfolist.php
    │       │   └── Tables/
    │       │       └── TeamsTable.php
    │       ├── UserResource.php
    │       └── UserResource/
    │           ├── Pages/
    │           │   ├── CreateUser.php
    │           │   ├── EditUser.php
    │           │   ├── ListUsers.php
    │           │   └── ViewUsers.php
    │           ├── Schemas/
    │           │   ├── UserForm.php
    │           │   └── UserInfolist.php
    │           └── Tables/
    │               └── UsersTable.php
    ├── Helpers/
    │   └── helpers.php
    ├── Http/
    │   ├── Controllers/API/V1/
    │   │   ├── AuthController.php
    │   │   └── Controller.php
    │   └── Resources/V1/
    │       ├── InvitationResource.php
    │       ├── PermissionResource.php
    │       ├── RoleResource.php
    │       ├── TeamResource.php
    │       └── UserResource.php
    ├── Livewire/
    │   └── AcceptInvitation.php
    ├── Mail/
    │   └── UserInvitationMail.php
    ├── Models/
    │   ├── Company.php
    │   ├── Invitation.php
    │   ├── Permission.php
    │   ├── Role.php
    │   ├── Team.php
    │   ├── User.php
    │   └── Scopes/
    │       └── OwnershipScope.php
    ├── Policies/
    │   ├── CompanyPolicy.php
    │   ├── RolePolicy.php
    │   ├── TeamPolicy.php
    │   └── UserPolicy.php
    ├── Settings/
    │   ├── CurrencySettings.php
    │   └── UserSettings.php
    ├── Support/
    │   └── OwnerSource.php
    └── Traits/
        ├── HasOwnershipScope.php
        └── HasScopedPermissions.php
```

## Models
[VERIFIED]
The plugin defines 6 Eloquent models and 1 global query scope:

### 1. `Webkul\Security\Models\User`
- **Class**: `plugins/webkul/security/src/Models/User.php:31`
- **Inheritance & Contracts**: Extends `App\Models\User` (`BaseUser`), implements `FilamentUser`, `HasAppAuthentication`, `HasAppAuthenticationRecovery`, `HasEmailAuthentication`.
- **Traits**: `HasOwnershipScope`, `HasRoles`, `InteractsWithAppAuthentication`, `InteractsWithAppAuthenticationRecovery`, `InteractsWithEmailAuthentication`, `SoftDeletes`.
- **Table**: `users`
- **Fillable Attributes**: `name`, `email`, `password`, `partner_id`, `language`, `creator_id`, `is_active`, `default_company_id`, `resource_permission`, `is_default`.
- **Casts**: `email_verified_at` => `datetime`, `password` => `hashed`, `default_company_id` => `integer`, `resource_permission` => `PermissionType::class`, `is_default` => `boolean`, `is_active` => `boolean`.
- **Guards**: `protected $guard_name = ['web', 'sanctum'];`
- **Relationships**:
  - `creator()`: `BelongsTo` -> `Webkul\Security\Models\User` (`creator_id`)
  - `teams()`: `BelongsToMany` -> `Webkul\Security\Models\Team` via pivot `user_team` (`user_id`, `team_id`)
  - `employee()`: `HasOne` -> `Webkul\Employee\Models\Employee` (`user_id`)
  - `departments()`: `HasMany` -> `Webkul\Employee\Models\Department` (`manager_id`)
  - `companies()`: `HasMany` -> `Webkul\Support\Models\Company` (`user_id`)
  - `partner()`: `BelongsTo` -> `Webkul\Partner\Models\Partner` (`partner_id`, without `CompanyScope`)
  - `allowedCompanies()`: `BelongsToMany` -> `Webkul\Support\Models\Company` via pivot `user_allowed_companies` (`user_id`, `company_id`)
  - `defaultCompany()`: `BelongsTo` -> `Webkul\Support\Models\Company` (`default_company_id`)
- **Lifecycle Events**:
  - `creating`: Automatically defaults `creator_id` to `Auth::id()` if null.
  - `saved`: Automatically synchronizes shadow `Partner` record (`handlePartnerCreation` or `handlePartnerUpdation`) setting `sub_type = 'partner'`, linking `user_id`, and syncing fillable attributes safely filtered via `Arr::only($user->toArray(), app(Partner::class)->getFillable())` to prevent attribute mismatch errors.
- **Panel Access**: `canAccessPanel(Panel $panel): bool => $this->is_active;`
- **Ownership**: `ownershipSources()` returns `[OwnerSource::column('creator_id'), OwnerSource::column('id')]`. `ownershipScopeIsGlobal()` returns `false`.

### Resolution of `Webkul\Security\Models\User` vs `App\Models\User`
[VERIFIED]
- `App\Models\User` (`app/Models/User.php:11`) is the base authenticatable class shipped with Laravel extending `Illuminate\Foundation\Auth\User`. It defines only basic attributes (`name`, `email`, `password`) and standard Sanctum token support.
- `Webkul\Security\Models\User` (`plugins/webkul/security/src/Models/User.php:31`) extends `App\Models\User` via `use App\Models\User as BaseUser; class User extends BaseUser`.
- `AppServiceProvider` (`app/Providers/AppServiceProvider.php:19`) explicitly executes `$this->app->bind(Authenticatable::class, User::class)` referencing `Webkul\Security\Models\User`.
- `config/auth.php:73` explicitly sets `'model' => env('AUTH_MODEL', User::class)` importing `Webkul\Security\Models\User`.
- Every domain model relationship, foreign key audit, policy parameter, API resource, and Filament form across all 28 plugins references `Webkul\Security\Models\User`. `App\Models\User` exists solely as an inheritance base class and is never instantiated directly in runtime operations.

### 2. `Webkul\Security\Models\Team`
- **Class**: `plugins/webkul/security/src/Models/Team.php:12`
- **Traits**: `HasCustomFields`, `HasOwnershipScope`
- **Table**: `teams`
- **Fillable Attributes**: `name`, `creator_id`.
- **Relationships**:
  - `creator()`: `BelongsTo` -> `Webkul\Security\Models\User` (`creator_id`)
  - `users()`: `BelongsToMany` -> `Webkul\Security\Models\User` via pivot `user_team` (`team_id`, `user_id`)
- **Lifecycle Events**:
  - `creating`: Automatically assigns `creator_id ??= Auth::id()`.
- **Ownership**: `ownershipScopeIsGlobal()` returns `false`. Default `ownershipSources()` are `creator_id` and `user_id`.

### 3. `Webkul\Security\Models\Role`
- **Class**: `plugins/webkul/security/src/Models/Role.php:13`
- **Inheritance**: Extends `Spatie\Permission\Models\Role` (`BaseRole`).
- **Table**: `roles`
- **Accessors**: `getNameAttribute()` returns `Str::ucfirst($value)`.
- **System Role Protection**:
  - `SYSTEM_ROLE_FALLBACKS = ['admin', 'super_admin']`.
  - `getSystemRoleNames()` merges fallbacks with `config('filament-shield.panel_user.name')` and `config('filament-shield.super_admin.name')`.
  - `updating` model event blocks modification of `name` or `guard_name` on system roles, throwing `AuthorizationException`.
  - `deleting` model event blocks deletion of system roles, throwing `AuthorizationException`.
- **Bulk Permission Synchronization**: `syncPermissionsByNames(Collection|array $permissionNames)` automatically chunks and ensures all named permissions exist before batch inserting into `role_has_permissions` pivot table and flushing permission cache.

### 4. `Webkul\Security\Models\Permission`
- **Class**: `plugins/webkul/security/src/Models/Permission.php:9`
- **Inheritance**: Extends `Spatie\Permission\Models\Permission` (`BasePermission`).
- **Table**: `permissions`
- **Caching**: `getPermissions(array $params = [], bool $onlyOne = false)` delegates to container singleton `Webkul\Security\PermissionRegistrar`.

### 5. `Webkul\Security\Models\Company`
- **Class**: `plugins/webkul/security/src/Models/Company.php:7`
- **Inheritance**: Extends `Webkul\Support\Models\Company` (`class Company extends BaseCompany {}`).
- **Table**: `companies`
- **Purpose**: Provides a local proxy class for Filament resource binding (`CompanyResource`) within the security plugin namespace.

### 6. `Webkul\Security\Models\Invitation`
- **Class**: `plugins/webkul/security/src/Models/Invitation.php:8`
- **Table**: `user_invitations`
- **Guarded**: `protected $guarded = [];`
- **Traits**: `HasFactory` (`InvitationFactory`)

### 7. `Webkul\Security\Models\Scopes\OwnershipScope`
- **Class**: `plugins/webkul/security/src/Models/Scopes/OwnershipScope.php:13`
- **Interface**: Implements `Illuminate\Database\Eloquent\Scope`
- **Bypass Conditions**: Running in console (`app()->runningInConsole()`), unauthenticated user, user not instance of `User`, or `Gate::allows('bypass_ownership_scope')`.
- **Resolution**: Fetches authorized user IDs via `bouncer()->getAuthorizedUserIds()`. If null (global permission), applies no filter. Otherwise, applies `orWhere` constraints across all configured `OwnerSource` descriptors.

## Database
[VERIFIED]
Migrations declared in `SecurityServiceProvider::configureCustomPackage()`:

| Migration File | Physical Table Affected | Operation | Columns / Keys Added / Constraints |
| :--- | :--- | :--- | :--- |
| `2024_11_11_112529_create_user_invitations_table.php` | `user_invitations` | `CREATE TABLE` | `id` (BIGINT PK), `email` (VARCHAR), `created_at`, `updated_at` |
| `2024_11_12_125715_create_teams_table.php` | `teams` | `CREATE TABLE` | `id` (BIGINT PK), `name` (VARCHAR), `created_at`, `updated_at` |
| `2024_11_12_130019_create_user_team_table.php` | `user_team` | `CREATE TABLE` | `user_id` (FK `users.id`, cascade), `team_id` (FK `teams.id`, cascade) |
| `2024_12_10_101127_add_default_company_id_column_to_users_table.php` | `users` | `ALTER TABLE` | `default_company_id` (BIGINT nullable, FK `companies.id`, restrict on delete) |
| `2024_12_13_130906_add_partner_id_to_users_table.php` | `users` | `ALTER TABLE` | `partner_id` (BIGINT nullable, FK `partners_partners.id`, restrict on delete) |
| `2025_08_01_071239_alter_teams_table.php` | `teams` | `ALTER TABLE` | `creator_id` (BIGINT nullable, FK `users.id`, null on delete) |
| `2025_08_01_073954_alter_users_table.php` | `users` | `ALTER TABLE` | `creator_id` (BIGINT nullable, FK `users.id`, null on delete) |
| `2025_08_21_082229_alter_roles_table.php` | `roles` | `ALTER TABLE` | `is_default` (BOOLEAN, default false) |
| `2025_08_21_101646_alter_users_table.php` | `users` | `ALTER TABLE` | `is_default` (BOOLEAN, default false) |
| `2026_01_23_074142_add_multi_factor_auth_columns_in_users_table.php` | `users` | `ALTER TABLE` | `app_authentication_secret` (TEXT nullable), `app_authentication_recovery_codes` (TEXT nullable), `has_email_authentication` (BOOLEAN, default false) |

### Settings Migrations
- `2024_11_05_042358_create_user_settings.php`: Adds `general.enable_user_invitation` (true), `general.enable_reset_password` (true), `general.default_role_id` (null), `general.default_company_id` (null).
- `2025_07_29_064223_create_currency_settings.php`: Adds `currency.default_currency_id` (null).

### Static Datasets Housed
The plugin houses static reference JSON files in `src/Data/`:
- `countries.json`
- `states.json`
- `currencies.json`
*Note*: These datasets are read directly by `CountrySeeder`, `StateSeeder`, and `CurrencySeeder` in the `support` plugin.

## Filament resources/pages/widgets/clusters
[VERIFIED]

### Resources (Admin Panel)

1. **`UserResource`** (`plugins/webkul/security/src/Filament/Resources/UserResource.php`):
   - Model: `Webkul\Security\Models\User`
   - Navigation: Group `NavigationGroup::Setting` (`Settings`), Sort order `4`.
   - Global Search: Searchable attributes `name`, `email`.
   - Scopes: Applies `->ownership()` on `getEloquentQuery()`.
   - Pages:
     - `ListUsers`: Displays user tabs (`All`, `Archived`), header action `Create`, and header modal action `inviteUser` (visible when `UserSettings->enable_user_invitation` is true). Sends signed `UserInvitationMail`.
     - `CreateUser`: Creates user record, auto-assigns creator, and validates role constraints.
     - `EditUser`: Updates user, enforces admin protection constraints, and manages soft deletion.
     - `ViewUsers`: Displays user details.
   - Schemas & Tables:
     - `UserForm`: Configures general info (name, email, password on create), permission assignment (roles, resource_permission with self-downgrade block, teams required when GROUP), partner avatar upload (`users/avatars`), language, active toggle, and multi-company relations (enforcing default company is among allowed companies).
     - `UserInfolist`: Read-only layout with badges for roles, teams, and allowed companies.
     - `UsersTable`: Reorderable columns, partner avatar, team badges, role names, company tags, and filters for resource_permission, default_company, allowed_companies, teams, and roles.

2. **`RoleResource`** (`plugins/webkul/security/src/Filament/Resources/RoleResource.php`):
   - Model: `Webkul\Security\Models\Role`
   - Inheritance: Extends `BezhanSalleh\FilamentShield\Resources\Roles\RoleResource`
   - Navigation: Group `NavigationGroup::Setting`, Sort order `1`. Global search disabled.
   - Pages: `ListRoles`, `CreateRole`, `ViewRole`, `EditRole`.
   - Schemas & Tables:
     - `RoleForm`: Custom permission grid grouping permissions by plugin namespace into collapsible sections with tabs for Resources, Pages, and Widgets, integrated with client-side AlpineJS bulk toggle handling.
     - `RolesTable`: Displays role name, guard, permission count badge, and updated timestamp. Protects system roles (`isSystemRole()`) from deletion via table actions and bulk actions.

3. **`TeamResource`** (`plugins/webkul/security/src/Filament/Resources/TeamResource.php`):
   - Model: `Webkul\Security\Models\Team`
   - Navigation: Group `NavigationGroup::Setting`, Sort order `3`.
   - Scopes: Applies `->ownership()` on `getEloquentQuery()`.
   - Pages: `ManageTeams` (Simple resource page).
   - Schemas & Tables: `TeamForm` (name field), `TeamInfolist` (name display), `TeamsTable` (name, creator name, delete action hidden if team has assigned users).

4. **`CompanyResource`** (`plugins/webkul/security/src/Filament/Resources/CompanyResource.php`):
   - Model: `Webkul\Security\Models\Company`
   - Inheritance: Extends `Webkul\Support\Filament\Resources\CompanyResource` (`BaseCompanyResource`).
   - Navigation: Group `NavigationGroup::Setting`, Sort order `2`. Explicitly enables navigation (`$shouldRegisterNavigation = true`) to surface company management in Admin Settings.
   - Scopes: Modifies `getEloquentQuery()` applying `->ownership()->withoutGlobalScope(AllowedCompanyScope::class)`.
   - Relations: Attaches `BranchesRelationManager`.
   - Pages: `ListCompanies`, `CreateCompany`, `ViewCompany`, `EditCompany`.

### Settings Pages (Admin Panel Settings Cluster)

1. **`ManageUsers`** (`plugins/webkul/security/src/Filament/Clusters/Settings/Pages/ManageUsers.php`):
   - Cluster: `Webkul\Support\Filament\Clusters\Settings`
   - Permission: `page_security_manage_users` (via `HasPageShield`)
   - Settings Bound: `UserSettings` (`general`)
   - Form Fields: `enable_user_invitation` (toggle), `enable_reset_password` (toggle), `default_role_id` (searchable select from `Role`), `default_company_id` (searchable select from `Company`).

2. **`ManageCurrency`** (`plugins/webkul/security/src/Filament/Clusters/Settings/Pages/ManageCurrency.php`):
   - Cluster: `Webkul\Support\Filament\Clusters\Settings`
   - Permission: `page_security_manage_currency` (via `HasPageShield`)
   - Settings Bound: `CurrencySettings` (`currency`)
   - Form Fields: `default_currency_id` (searchable select from active `Currency` models).

3. **`ManageActivity`** (`plugins/webkul/security/src/Filament/Clusters/Settings/Pages/ManageActivity.php`):
   - Cluster: `Webkul\Support\Filament\Clusters\Settings`
   - Permission: `page_security_manage_activity` (via `HasPageShield`)
   - Settings Bound: `UserSettings`
   - Form Content: Static informational text entry describing activity types with an action link redirecting to `route('filament.admin.settings.resources.activity-types.index')`.

### Widgets
[VERIFIED]
None. `plugins/webkul/security/src/Filament/` contains zero widget classes.

## Panels
[VERIFIED]
- **Admin Panel (`admin`)**:
  - `SecurityPlugin::register()` discovers all 4 resources (`UserResource`, `RoleResource`, `TeamResource`, `CompanyResource`) and all 3 settings cluster pages (`ManageUsers`, `ManageCurrency`, `ManageActivity`).
  - Configures password reset capability conditionally based on `UserSettings->enable_reset_password`.
  - MFA TOTP authentication (`AppAuthentication`) is enforced via `AdminPanelProvider.php:114-117`.
- **Customer Panel (`customer`)**:
  - `SecurityPlugin::register()` checks `$panel->getId() == 'admin'` and contributes zero resources, pages, or widgets to the customer panel. (The customer panel operates under guard `customer` mapped to `Webkul\Website\Models\Partner`).

## Services
[VERIFIED]
1. **`Webkul\Security\Bouncer`** (`plugins/webkul/security/src/Bouncer.php`):
   - Registered as container singleton `'bouncer'` in `SecurityServiceProvider:76` and aliased via facade `Webkul\Security\Facades\Bouncer`.
   - Global helper `bouncer()` returns the singleton instance.
   - Method `getAuthorizedUserIds(): ?array`: Evaluates current authenticated user. If `resource_permission == GLOBAL`, returns `null` (no query restriction). If `GROUP`, queries `User` table joined with `user_team` and `teams` to return an array of user IDs sharing any team with the current user. If `INDIVIDUAL`, returns `[$user->id]`. Results are cached in static property `$authorizedUserIdsCache`.
2. **`Webkul\Security\PermissionRegistrar`** (`plugins/webkul/security/src/PermissionRegistrar.php`):
   - Registered as container singleton `PermissionRegistrar::class` in `SecurityServiceProvider:77`.
   - Manages high-performance cached permissions loading, aliased attribute serialization to reduce cache payload size, and 24-hour cache expiration.
3. **`Webkul\Security\Support\OwnerSource`** (`plugins/webkul/security/src/Support/OwnerSource.php`):
   - Value object defining ownership query strategies: `OwnerSource::column($name)`, `OwnerSource::relation($name, $key)`, `OwnerSource::pivot($table, $foreignKey, $relatedKey)`, and `OwnerSource::followers()`.

## Events
[VERIFIED]
The plugin does not dispatch any custom Laravel event classes. It hooks into Eloquent model lifecycle events:
- `User::creating`: Defaults `creator_id` to current authenticated user.
- `User::saved`: Triggers shadow `Partner` creation or update.
- `Team::creating`: Defaults `creator_id` to current authenticated user.
- `Role::updating`: Protects system roles from name/guard modification.
- `Role::deleting`: Protects system roles from deletion.

## Listeners
[VERIFIED]
None defined in this plugin.

## Observers
[VERIFIED]
None defined. Model lifecycle hooks are implemented directly within model `boot()` / `booted()` methods.

## Policies
[VERIFIED]
The plugin defines 4 policy classes under `plugins/webkul/security/src/Policies/`:

1. **`UserPolicy`** (`plugins/webkul/security/src/Policies/UserPolicy.php:9`):
   - Uses `HandlesAuthorization`, `HasScopedPermissions`.
   - Methods: `viewAny` (`view_any_security_user`), `view` (`view_security_user` + `hasAccess($user, $record, 'creator')`), `create` (`create_security_user`), `update` (`update_security_user` + `hasAccess`), `delete` (`delete_security_user` + `hasAccess`), `deleteAny` (`delete_any_security_user`), `forceDelete` (`force_delete_security_user` + prevents self-deletion + `hasAccess`), `forceDeleteAny` (`force_delete_any_security_user`), `restore` (`restore_security_user` + `hasAccess`), `restoreAny` (`restore_any_security_user`).

2. **`TeamPolicy`** (`plugins/webkul/security/src/Policies/TeamPolicy.php:9`):
   - Uses `HandlesAuthorization`.
   - Methods: `viewAny` (`view_any_security_team`), `view` (`view_security_team`), `create` (`create_security_team`), `update` (`update_security_team`), `delete` (`delete_security_team`).

3. **`RolePolicy`** (`plugins/webkul/security/src/Policies/RolePolicy.php:9`):
   - Uses `HandlesAuthorization`.
   - Explicitly registered for `Webkul\Security\Models\Role` in `SupportServiceProvider.php:92`.
   - Methods: `viewAny` (`view_any_role`), `view` (`view_role`), `create` (`create_role`), `update` (`update_role`), `delete` (`delete_role`), `deleteAny` (`delete_any_role`).
   - [VERIFIED AUTHORING BUG] Lines 66, 74, 82, 90, 98: `forceDelete`, `forceDeleteAny`, `restore`, `restoreAny`, and `reorder` check permissions `force_delete_field`, `force_delete_any_field`, `restore_field`, `restore_any_field`, and `reorder_field` instead of role permissions.

4. **`CompanyPolicy`** (`plugins/webkul/security/src/Policies/CompanyPolicy.php:9`):
   - Uses `HandlesAuthorization`.
   - Methods: `viewAny` (`view_any_security_company`), `view` (`view_security_company`), `create` (`create_security_company`), `update` (`update_security_company`), `delete` (`delete_security_company`).

## Routes
[VERIFIED]

### Web Routes (`plugins/webkul/security/routes/web.php`)
- `GET /invitation/{invitation}/accept`
  - Name: `security.invitation.accept`
  - Controller / Component: `Webkul\Security\Livewire\AcceptInvitation::class`
  - Middleware: `['web', 'signed']`
  - Purpose: Renders the password setup and registration page for an invited user. The `signed` middleware validates the HMAC signature generated in `UserInvitationMail`.

### API Routes (`plugins/webkul/security/routes/api.php`)

1. **`POST /admin/api/v1/login`**:
   - Controller: `Webkul\Security\Http\Controllers\API\V1\AuthController::class, 'login'`
   - Middleware: `api` (from root group). **Deliberately public / unauthenticated (no `auth:sanctum`)**.
   - Parameters:
     - `email` (string, required, email format)
     - `password` (string, required)
   - Authentication Mechanism:
     - Resolves user using cross-database case-insensitive comparison: `User::whereRaw(db_dialect()->caseInsensitiveEquals('email'), [$request->email])->first()`.
     - Validates password hash via `Hash::check($request->password, $user->password)`.
     - Generates Sanctum token: `$user->createToken('api-token')->plainTextToken`.
   - Response: `200 OK` JSON with `message`, `token`, `token_type` (`Bearer`), and user payload (`id`, `name`, `email`). Throws 422 `ValidationException` on bad credentials.

2. **`POST /admin/api/v1/logout`**:
   - Controller: `Webkul\Security\Http\Controllers\API\V1\AuthController::class, 'logout'`
   - Middleware: `['api', 'auth:sanctum']`
   - Purpose: Revokes active access token via `$request->user()->currentAccessToken()->delete()`.
   - Response: `200 OK` JSON `{"message": "Logout successful"}`.

### Unrouted API Resources
[VERIFIED]
The directory `plugins/webkul/security/src/Http/Resources/V1/` defines 5 API resource classes (`UserResource`, `TeamResource`, `RoleResource`, `PermissionResource`, `InvitationResource`). However, `routes/api.php` only exposes `login` and `logout`. `TeamResource` is reused by `plugins/webkul/sales/src/Http/Resources/V1/OrderResource.php`, while the remaining resources have no active endpoints in `routes/api.php`.

## Settings
[VERIFIED]

1. **`Webkul\Security\Settings\UserSettings`** (`plugins/webkul/security/src/Settings/UserSettings.php`):
   - Group: `'general'`
   - Properties:
     - `enable_user_invitation` (bool): Enables user invitation modal on `ListUsers`.
     - `enable_reset_password` (bool): Controls whether password reset is enabled on the Filament admin panel.
     - `default_role_id` (?int): Role assigned to users created via invitation.
     - `default_company_id` (?int): Company assigned to users created via invitation.

2. **`Webkul\Security\Settings\CurrencySettings`** (`plugins/webkul/security/src/Settings/CurrencySettings.php`):
   - Group: `'currency'`
   - Properties:
     - `default_currency_id` (?int): Default currency ID for the system.

## Translations
[VERIFIED]
The plugin defines 19 translation files across 5 locales (`ar`, `en`, `es`, `fr`, `pt_BR`) with complete file-level parity:
- `enums/company-status.php`
- `enums/permission-type.php`
- `filament/clusters/manage-activity.php`
- `filament/clusters/manage-currency.php`
- `filament/clusters/manage-users.php`
- `filament/resources/company.php`
- `filament/resources/role.php`
- `filament/resources/role/pages/create-role.php`
- `filament/resources/role/pages/edit-role.php`
- `filament/resources/team.php`
- `filament/resources/team/pages/manage-team.php`
- `filament/resources/user.php`
- `filament/resources/user/pages/create-user.php`
- `filament/resources/user/pages/edit-user.php`
- `filament/resources/user/pages/list-user.php`
- `filament/resources/user/pages/view-user.php`
- `livewire/accept-invitation.php`
- `mail/user-invitation-mail.php`
- `views/emails/user-invitation.php`

## Tests
[VERIFIED]
**No Plugin-Local Tests Found**. Direct inspection of the repository confirms that `plugins/webkul/security/` does NOT contain a `tests/` directory, and root `tests/` contains no test files directly exercising security plugin classes.

While feature tests in optional domain plugins (such as `accounts`, `purchases`, `sales`, and `manufacturing`) import `Webkul\Security\Models\User` and `Webkul\Security\Enums\PermissionType` as fixtures to authenticate API requests, this does not constitute direct coverage of the security plugin's core authorization implementation. Direct automated testing is absent for:
- `Webkul\Security\Bouncer` and authorized user ID resolution
- `OwnershipScope` query generation across columns, relations, pivots, and followers
- `HasScopedPermissions` policy authorization checks
- `UserPolicy`, `TeamPolicy`, `RolePolicy`, and `CompanyPolicy`
- System role protection invariants on `Role` model
- Admin constraint validation in `UserResource::ensureAdminRoleConstraints`
- `AuthController` login/logout API endpoints and Sanctum token generation
- `AcceptInvitation` Livewire registration flow and signed URL verification
- Multi-factor authentication contracts and recovery flows

## Runtime dependencies
[VERIFIED]
None (`—`). The plugin provider does not declare runtime dependencies via `hasDependencies()`.

## Cross-plugin relationships
[VERIFIED]

1. **`support` (`Webkul\Support`)**:
   - `User` model defines relationships to `Webkul\Support\Models\Company` (`companies()`, `defaultCompany()`, `allowedCompanies()`).
   - `CompanyResource` extends `Webkul\Support\Filament\Resources\CompanyResource` and attaches `BranchesRelationManager`.
   - `ManageUsers`, `ManageCurrency`, and `ManageActivity` belong to `Webkul\Support\Filament\Clusters\Settings`.
   - `SupportServiceProvider` explicitly registers `RolePolicy` for `Webkul\Security\Models\Role`.
   - `CountrySeeder`, `StateSeeder`, and `CurrencySeeder` in `support` read raw JSON datasets from `plugins/webkul/security/src/Data/`.
   - `SupportServiceProvider` defines a `Gate::before` rule checking `super_admin` for `bypass_company_scope`, complementing security's `bypass_ownership_scope`.

2. **`partners` (`Webkul\Partner`)**:
   - `User` maintains a 1:1 shadow relationship with `Webkul\Partner\Models\Partner` (`partner_id`), automatically creating or updating partner records upon user save.
   - `User::getAvatarUrlAttribute()` delegates to `$this->partner?->avatar_url`.
   - `OwnershipScope::applyFollowers()` queries follower partner IDs on `Partner` via `chatter_followers`.

3. **`fields` (`Webkul\Field`)**:
   - `Team` model uses trait `HasCustomFields` (`Webkul\Field\Traits\HasCustomFields`), enabling dynamic metadata field definitions on teams.

4. **`employees` (`Webkul\Employee`)**:
   - `User` defines optional relationships to `Webkul\Employee\Models\Employee` (`employee()`) and `Webkul\Employee\Models\Department` (`departments()`).

5. **`projects` (`Webkul\Project`)** [DANGEROUS COUPLING]:
   - `AcceptInvitation` Livewire component (`plugins/webkul/security/src/Livewire/AcceptInvitation.php:13,89`) hardcodes a redirect to `Webkul\Project\Filament\Pages\Dashboard::getUrl()`. If the optional `projects` plugin is disabled or uninstalled, completing the user invitation registration flow results in a runtime fatal error.

6. **All Optional Plugins**:
   - Every domain plugin that restricts records by user or team ownership applies `HasOwnershipScope` and `OwnerSource`, querying through `Bouncer`.
   - Policies across all domain plugins extend `HasScopedPermissions` to evaluate `hasAccess($user, $record)`.

## Data flow
[VERIFIED]

### 1. Ownership Filtering Data Flow
```
Incoming Request / Eloquent Query
       │
       ▼
Model with HasOwnershipScope Trait
       │
       ▼
OwnershipScope::apply()
       │
       ├─► Console / Unauthenticated? ──► [Return, No Scoping]
       ├─► Gate::allows('bypass_ownership_scope')? ──► [Return, Bypass Scoping]
       │
       ▼
Bouncer::getAuthorizedUserIds()
       │
       ├─► PermissionType::GLOBAL ──► [Returns null -> No Query Scope]
       ├─► PermissionType::GROUP  ──► [Queries user_team -> Returns array of team member IDs]
       └─► PermissionType::INDIVIDUAL ──► [Returns [auth()->id()]]
       │
       ▼
OwnershipScope builds query constraint:
       ├─► KIND_COLUMN: orWhereIn('<table>.<column>', $userIds)
       ├─► KIND_RELATION: orWhereHas('<relation>', whereIn('<key>', $userIds))
       ├─► KIND_PIVOT: orWhereExists(pivot join matching $userIds)
       └─► KIND_FOLLOWERS: orWhereHas('followers', whereIn('partner_id', $partnerIds))
```

### 2. User Invitation Data Flow
```
Admin in ListUsers Page
       │
       ▼
Clicks "Invite User" Header Action (requires UserSettings.enable_user_invitation)
       │
       ▼
Creates Invitation Record (user_invitations table: email)
       │
       ▼
Dispatches UserInvitationMail (generates signed URL for security.invitation.accept)
       │
       ▼
Recipient clicks signed email link
       │
       ▼
AcceptInvitation Livewire Component (validates signed signature)
       │
       ▼
Recipient inputs Name and Password
       │
       ▼
Creates User record (assigns default_company_id and default_role_id from UserSettings)
       │
       ▼
Deletes Invitation record
       │
       ▼
Redirects to Dashboard (coupled to Webkul\Project\Filament\Pages\Dashboard)
```

## Business rules
[VERIFIED]

1. **System Role Immutability**:
   - Roles named `admin`, `super_admin`, or matching `config('filament-shield.panel_user.name')` / `config('filament-shield.super_admin.name')` are protected.
   - Any attempt to delete or alter the `name` or `guard_name` of a system role throws `Illuminate\Auth\Access\AuthorizationException`.

2. **Admin User Invariants**:
   - The user with the lowest database ID (`User::min('id')`) is permanently protected and must retain an admin role.
   - If only one user in the system holds an admin role, that role cannot be removed from that user.
   - A user cannot delete their own account (`$record->id !== Auth::id()`), and cannot delete default users (`! $record->is_default`).

3. **Resource Permission Constraints**:
   - A user editing their own profile in `UserResource` cannot change or downgrade their own `resource_permission` setting (`disabled` and `dehydrated(false)`).
   - If `resource_permission` is set to `GROUP`, the user must be assigned to at least one `Team`. Switching away from `GROUP` automatically clears assigned teams.

4. **Multi-Company Assignment Rules**:
   - A user's `default_company_id` MUST be present in the user's `allowed_companies` set. Form validation rejects saving if `default_company_id` is not among `allowed_companies`.

5. **Super Admin Scope Bypass**:
   - Users possessing the `super_admin` role automatically pass the `bypass_ownership_scope` Gate check, granting unrestricted visibility across all records regardless of individual or group ownership settings.

## Extension points
[VERIFIED]

1. **Custom Ownership Sources**:
   - Models implementing `HasOwnershipScope` can override `ownershipSources(): array` to supply tailored `OwnerSource` descriptors using column, relation, pivot, or follower strategies.
2. **Custom Policy Access Checks**:
   - Domain policies can utilize `HasScopedPermissions` to evaluate record-level access using custom owner attribute names via `$this->hasAccess($user, $record, $ownerAttribute)`.
3. **Role & Permission Customization**:
   - System administrators can define granular roles and assign resource-, page-, and widget-level permissions via the customized `RoleResource` with Shield integration.

## Dangerous areas
[VERIFIED]

1. **Absence of Plugin-Local Test Suite for Core Security Primitives**:
   - [VERIFIED] No plugin-local automated test suite exists for the `security` plugin (`plugins/webkul/security/tests/` does not exist). While downstream modules exercise authentication fixtures, every authorization decision, ownership query scope, token generation path, and policy check lacks dedicated unit or feature tests within the plugin itself.
   - A regression in `Bouncer`, `OwnershipScope`, or `UserPolicy` could silently compromise tenant isolation or record confidentiality across all 28 modules without failing a security plugin test.

2. **Hardcoded Coupling to Optional `projects` Plugin in User Invitation Flow**:
   - `Webkul\Security\Livewire\AcceptInvitation` (`plugins/webkul/security/src/Livewire/AcceptInvitation.php:13,89`) hardcodes a redirect to `Webkul\Project\Filament\Pages\Dashboard::getUrl()`. If the optional `projects` plugin is uninstalled or disabled, newly invited users encounter a fatal runtime error upon submitting their password.

3. **Authoring Bugs in `RolePolicy`**:
   - `RolePolicy.php:66,74,82,90,98` mistakenly checks `*_field` permissions (`force_delete_field`, `restore_field`, etc.) instead of `*_role` permissions for soft deletion, restore, and reordering actions.

4. **Authoring Bug in `UsersTable` Teams Filter**:
   - `UsersTable.php:99` configures the `teams` select filter options using `Role::query()->pluck('name', 'id')` instead of querying the `Team` model, resulting in roles being displayed inside the teams filter dropdown.

5. **Discrepancy Between `InvitationFactory` / `InvitationResource` and Database Schema**:
   - `InvitationFactory.php` and `InvitationResource.php` reference columns `role_id`, `token`, `expires_at`, `invited_by`, and `accepted_at`. None of these columns exist in the physical `user_invitations` table (which contains only `id`, `email`, and timestamps). Calling the factory or consuming the API resource will result in SQL exceptions or undefined property warnings.

6. **Notification Severity Typo on Failed Invitations**:
   - `ListUsers.php:82` sends a notification configured with `->success()` instead of `->danger()` when an invitation email fails to send due to a caught exception.

7. **Dead Code / Leftover Class**:
   - `Webkul\Security\Package` (`plugins/webkul/security/src/Package.php`) is an obsolete local class extending Spatie Package that is never imported or utilized by `SecurityServiceProvider`.

## Change impact
[VERIFIED]
Changes to the `security` plugin have maximum systemic blast radius:
- **Tenant & Ownership Security**: Modifying `Bouncer`, `OwnershipScope`, or `HasScopedPermissions` directly alters data filtering and record access rules across all 28 plugins in the ERP.
- **Authentication**: Modifying `User` or `AuthController` impacts admin panel login, password resets, MFA validation, and REST API token authorization.
- **RBAC**: Modifying `Role`, `Permission`, or `PermissionRegistrar` impacts permission caching and role authorization checks throughout the application.

## Evidence
[VERIFIED]
- Service provider & package registration: `plugins/webkul/security/src/SecurityServiceProvider.php:12-80`, `bootstrap/providers.php:28`
- Filament plugin registration & widget typo: `plugins/webkul/security/src/SecurityPlugin.php:9-57`
- Authenticatable container binding: `app/Providers/AppServiceProvider.php:19`
- Auth configuration: `config/auth.php:3,73`
- Permission configuration: `config/permission.php:3-30`
- User model & MFA implementation: `plugins/webkul/security/src/Models/User.php:1-178`
- Base User model: `app/Models/User.php:1-55`
- Bouncer service & facade: `plugins/webkul/security/src/Bouncer.php:1-55`, `plugins/webkul/security/src/Facades/Bouncer.php:1-17`, `plugins/webkul/security/src/Helpers/helpers.php:1-14`
- Ownership scope & sources: `plugins/webkul/security/src/Models/Scopes/OwnershipScope.php:1-112`, `plugins/webkul/security/src/Support/OwnerSource.php:1-44`, `plugins/webkul/security/src/Traits/HasOwnershipScope.php:1-38`, `plugins/webkul/security/src/Traits/HasScopedPermissions.php:1-86`
- Role model & system role protection: `plugins/webkul/security/src/Models/Role.php:1-200`
- Team model & relationships: `plugins/webkul/security/src/Models/Team.php:1-45`
- Permission registrar: `plugins/webkul/security/src/PermissionRegistrar.php:1-410`
- Policies: `plugins/webkul/security/src/Policies/UserPolicy.php:1-117`, `plugins/webkul/security/src/Policies/TeamPolicy.php:1-53`, `plugins/webkul/security/src/Policies/RolePolicy.php:1-101`, `plugins/webkul/security/src/Policies/CompanyPolicy.php:1-53`
- Routes & API controller: `plugins/webkul/security/routes/web.php:1-11`, `plugins/webkul/security/routes/api.php:1-15`, `plugins/webkul/security/src/Http/Controllers/API/V1/AuthController.php:1-70`
- User invitation flow & projects coupling: `plugins/webkul/security/src/Livewire/AcceptInvitation.php:1-124`, `plugins/webkul/security/src/Mail/UserInvitationMail.php:1-66`, `plugins/webkul/security/src/Filament/Resources/UserResource/Pages/ListUsers.php:1-89`
- Filament resources, schemas, and tables: `plugins/webkul/security/src/Filament/Resources/UserResource.php:1-165`, `plugins/webkul/security/src/Filament/Resources/RoleResource.php:1-375`, `plugins/webkul/security/src/Filament/Resources/TeamResource.php:1-59`, `plugins/webkul/security/src/Filament/Resources/CompanyResource.php:1-73`
- Settings models & pages: `plugins/webkul/security/src/Settings/UserSettings.php:1-22`, `plugins/webkul/security/src/Settings/CurrencySettings.php:1-16`, `plugins/webkul/security/src/Filament/Clusters/Settings/Pages/ManageUsers.php:1-77`, `plugins/webkul/security/src/Filament/Clusters/Settings/Pages/ManageCurrency.php:1-62`, `plugins/webkul/security/src/Filament/Clusters/Settings/Pages/ManageActivity.php:1-73`
- Database migrations: `plugins/webkul/security/database/migrations/` (10 files)
- MFA panel configuration: `app/Providers/Filament/AdminPanelProvider.php:114-117`
- Static data files consumed by support: `plugins/webkul/security/src/Data/`, `plugins/webkul/support/database/seeders/`
