---
status: verified
source_of_truth: source-code
last_verified: 2026-09-23
scope: plugins/webkul/partners
confidence: high
---

# Plugin: Partners (`partners`)

## Status
[VERIFIED]
Active Core Module. Registered explicitly in `bootstrap/providers.php:27` as `Webkul\Partner\PartnerServiceProvider::class`.

## Core/Optional
[VERIFIED]
**Core Plugin**. Configured via `$package->isCore()` within `PartnerServiceProvider::configureCustomPackage()` (`plugins/webkul/partners/src/PartnerServiceProvider.php:16`).

## Enabled/Disabled
[VERIFIED]
**Always Enabled (Core Infrastructure)**. Core plugin status causes `PackageServiceProvider::boot()` to bypass database installation checks (`Package::isInstalled()`) when loading migrations and routes (`plugins/webkul/plugin-manager/src/PackageServiceProvider.php:131-135, 202`). The `partners` module executes unconditionally at boot time across the application.

## Purpose
[VERIFIED]
The `partners` module serves as the universal party, contact, organization, and master data hub for Aureus ERP. It establishes the central entity model representing legal entities, corporate enterprises, individual contacts, customer accounts, vendor suppliers, financial banking details, and operational addresses across all application domains:

1. **Universal Business Party Master Registry (`Partner`)**:
   - Houses the central `Partner` model (`plugins/webkul/partners/src/Models/Partner.php`) mapping to physical table `partners_partners`.
   - Represents all external and internal parties (customers, suppliers, employees, users, parent corporations, subsidiaries, branch locations).
   - Stores core identifying information (name, avatar, tax ID, company registry, reference, email, job title, phone, mobile, website, color).

2. **Hierarchical Party & Contact Tree (`parent_id`)**:
   - Implements a self-referential parent-child hierarchy on `partners_partners.parent_id`.
   - Differentiates organizational accounts (`account_type = 'company'`) from individual people (`account_type = 'individual'`).
   - Models individual people who belong to commercial organizations (e.g. employees or department contacts within an enterprise client), where the individual partner holds a foreign key to the parent company partner (`$individualPartner->parent_id = $companyPartner->id`).
   - Supports parent partner query relationships: `$partner->contacts()` (child individual partners) and `$partner->addresses()` (child operational address records).

3. **Multi-Purpose Operational Address Book (`Address`)**:
   - Leverages Single Table Inheritance (STI) on `partners_partners` via `Address` model (`plugins/webkul/partners/src/Models/Address.php`), where `account_type = 'address'`.
   - Classifies operational and logistics addresses via `sub_type` using enum `AddressType` (`plugins/webkul/partners/src/Enums/AddressType.php`): `invoice`, `delivery`, `permanent`, `present`, and `other`.
   - Direct geographical binding to `Country` and `State` reference data from the `support` module.

4. **Multi-Bank Account Registry (`BankAccount`)**:
   - Manages banking accounts for partners via `BankAccount` model (`plugins/webkul/partners/src/Models/BankAccount.php`) on table `partners_bank_accounts`.
   - Links partner bank accounts to financial institutions (`Bank` model / `banks` table from `support` module).
   - Tracks routing parameters: `account_number`, `account_holder_name`, `can_send_money` boolean flag, and active status.
   - Automatically synchronizes `account_holder_name` from the parent `Partner.name` upon creation and update.

5. **Industry & Professional Classification**:
   - Provides standardized industry classifications via `Industry` model (`plugins/webkul/partners/src/Models/Industry.php`) and table `partners_industries`.
   - Provides standardized personal/professional honorific titles via `Title` model (`plugins/webkul/partners/src/Models/Title.php`) and table `partners_titles` (e.g., Dr., Mr., Mrs, Prof.).

6. **Tagging & Segmentation Architecture (`Tag`)**:
   - Implements color-coded record tagging via `Tag` model (`plugins/webkul/partners/src/Models/Tag.php`), `partners_tags`, and junction table `partners_partner_tag`.
   - Allows multi-tag assignment to partners with custom hex colors for dynamic visual badges in Filament tables and infolists.

7. **Cross-Plugin Dynamic Schema Extensibility (`PartnerSchemaRegistry`)**:
   - Implements `PartnerSchemaRegistry` (`plugins/webkul/partners/src/Filament/Resources/PartnerResource/Support/PartnerSchemaRegistry.php`) extending `AbstractSchemaRegistry`.
   - Enables downstream plugins (such as `accounts` for invoicing/accounting tabs and financial properties, or `website` for portal authentication actions) to inject form sections, infolist entries, table columns, filter constraints, eager loads, and header actions into `PartnerResource` without modifying core source code.

8. **Comprehensive REST API Suite**:
   - Provides fully documented REST API v1 endpoints under `admin/api/v1/partners` for partners, nested addresses, nested bank accounts, industries, tags, and titles with Scribe OpenAPI metadata and Spatie QueryBuilder filtering/sorting/includes.

## Service Provider
[VERIFIED]
- **Class**: `Webkul\Partner\PartnerServiceProvider` (`plugins/webkul/partners/src/PartnerServiceProvider.php:9`)
- **Inheritance**: Extends `Webkul\PluginManager\PackageServiceProvider`
- **Registration & Configuration**:
  - `configureCustomPackage(Package $package)`:
    - Sets package name to `partners` (`PartnerServiceProvider::$name = 'partners'`).
    - Declares package as core (`$package->isCore()`).
    - Registers translation namespace (`hasTranslations()`).
    - Registers API routing (`hasRoutes(['api'])`).
    - Registers 8 database migrations via `hasMigrations([...])` and enables execution via `runsMigrations()`:
      1. `2024_12_11_101127_create_partners_industries_table`
      2. `2024_12_11_101127_create_partners_titles_table`
      3. `2024_12_11_101220_create_partners_partners_table`
      4. `2024_12_11_101420_create_partners_bank_accounts_table`
      5. `2024_12_11_101927_create_partners_tags_table`
      6. `2024_12_11_111929_create_partners_partner_tag_table`
      7. `2025_03_28_115218_add_address_columns_in_partners_partners_table`
      8. `2026_07_30_100000_null_company_on_non_user_partners`
  - `packageRegistered()`:
    - Configures Filament panel builder via `Panel::configureUsing()`, registering `PartnerPlugin::make()` across panels.
  - `packageBooted()`:
    - Contains empty method stub (`plugins/webkul/partners/src/PartnerServiceProvider.php:32-35`).

## Filament Plugin class
[VERIFIED]
- **Class**: `Webkul\Partner\PartnerPlugin` (`plugins/webkul/partners/src/PartnerPlugin.php:8`)
- **Interface**: Implements `Filament\Contracts\Plugin`
- **Identifier**: `getId()` returns `'partners'` (`plugins/webkul/partners/src/PartnerPlugin.php:12`)
- **Factory Method**: `PartnerPlugin::make()` resolves static singleton via `app(static::class)`
- **Panel Registration**:
  - `register(Panel $panel)` executes conditionally when `$panel->getId() == 'admin'` (`plugins/webkul/partners/src/PartnerPlugin.php:23`):
    - Discovers resources in `src/Filament/Resources` under namespace `Webkul\Partner\Filament\Resources` (`PartnerResource`, `AddressResource`, `BankAccountResource`, `BankResource`, `IndustryResource`, `TagResource`, `TitleResource`).
    - Discovers pages in `src/Filament/Pages` under namespace `Webkul\Partner\Filament\Pages` (none present directly under `Pages/`).
    - Discovers clusters in `src/Filament/Clusters` under namespace `Webkul\Partner\Filament\Clusters` (none present directly under `Clusters/`).
    - Discovers widgets in `src/Filament/Widgets` under namespace `Webkul\Partner\Filament\Widgets` (none present directly under `Widgets/`).
- **Boot**: `boot(Panel $panel)` contains empty method stub (`plugins/webkul/partners/src/PartnerPlugin.php:44-47`).

## Composer dependencies
[VERIFIED]
- **Local Package Definition**: `plugins/webkul/partners/composer.json`
  - Name: `webkul/partners`
  - Autoload PSR-4:
    - `Webkul\Partner\` -> `src/`
    - `Webkul\Partner\Database\Factories\` -> `database/factories/`
    - `Webkul\Partner\Database\Seeders\` -> `database/seeders/`
  - Autoload-dev PSR-4:
    - `Webkul\Partner\Tests\` -> `tests/`
  - Extra Laravel Providers:
    - `Webkul\Partner\PartnerServiceProvider`

## Runtime plugin dependencies
[VERIFIED]
`partners` is a foundational Core plugin and declares **no runtime dependencies** via `$package->hasDependencies([...])`. It is loaded early in the application boot cycle.

## Directory structure
[VERIFIED]
```text
plugins/webkul/partners/
├── composer.json
├── config/
│   └── filament-shield.php
├── database/
│   ├── factories/
│   │   ├── BankAccountFactory.php
│   │   ├── IndustryFactory.php
│   │   ├── PartnerFactory.php
│   │   ├── TagFactory.php
│   │   └── TitleFactory.php
│   ├── migrations/
│   │   ├── 2024_12_11_101127_create_partners_industries_table.php
│   │   ├── 2024_12_11_101127_create_partners_titles_table.php
│   │   ├── 2024_12_11_101220_create_partners_partners_table.php
│   │   ├── 2024_12_11_101420_create_partners_bank_accounts_table.php
│   │   ├── 2024_12_11_101927_create_partners_tags_table.php
│   │   ├── 2024_12_11_111929_create_partners_partner_tag_table.php
│   │   ├── 2025_03_28_115218_add_address_columns_in_partners_partners_table.php
│   │   └── 2026_07_30_100000_null_company_on_non_user_partners.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── IndustrySeeder.php
│       └── TitleSeeder.php
├── resources/
│   └── lang/
│       ├── ar/
│       ├── en/
│       ├── es/
│       ├── fr/
│       └── pt_BR/
├── routes/
│   └── api.php
├── src/
│   ├── Enums/
│   │   ├── AccountType.php
│   │   └── AddressType.php
│   ├── Filament/
│   │   └── Resources/
│   │       ├── AddressResource/
│   │       │   ├── Schemas/
│   │       │   │   └── AddressForm.php
│   │       │   └── Tables/
│   │       │       └── AddressesTable.php
│   │       ├── AddressResource.php
│   │       ├── BankAccountResource/
│   │       │   ├── Pages/
│   │       │   │   └── ManageBankAccounts.php
│   │       │   ├── Schemas/
│   │       │   │   └── BankAccountForm.php
│   │       │   └── Tables/
│   │       │       └── BankAccountsTable.php
│   │       ├── BankAccountResource.php
│   │       ├── BankResource/
│   │       │   └── Pages/
│   │       │       └── ManageBanks.php
│   │       ├── BankResource.php
│   │       ├── IndustryResource/
│   │       │   ├── Pages/
│   │       │   │   └── ManageIndustries.php
│   │       │   ├── Schemas/
│   │       │   │   └── IndustryForm.php
│   │       │   └── Tables/
│   │       │       └── IndustriesTable.php
│   │       ├── IndustryResource.php
│   │       ├── PartnerResource/
│   │       │   ├── Pages/
│   │       │   │   ├── CreatePartner.php
│   │       │   │   ├── EditPartner.php
│   │       │   │   ├── ListPartners.php
│   │       │   │   ├── ManageAddresses.php
│   │       │   │   ├── ManageContacts.php
│   │       │   │   └── ViewPartner.php
│   │       │   ├── RelationManagers/
│   │       │   │   ├── AddressesRelationManager.php
│   │       │   │   └── ContactsRelationManager.php
│   │       │   ├── Schemas/
│   │       │   │   ├── PartnerForm.php
│   │       │   │   └── PartnerInfolist.php
│   │       │   ├── Support/
│   │       │   │   └── PartnerSchemaRegistry.php
│   │       │   └── Tables/
│   │       │       └── PartnersTable.php
│   │       ├── PartnerResource.php
│   │       ├── TagResource/
│   │       │   ├── Pages/
│   │       │   │   └── ManageTags.php
│   │       │   ├── Schemas/
│   │       │   │   └── TagForm.php
│   │       │   └── Tables/
│   │       │       └── TagsTable.php
│   │       ├── TagResource.php
│   │       ├── TitleResource/
│   │       │   ├── Pages/
│   │       │   │   └── ManageTitles.php
│   │       │   ├── Schemas/
│   │       │   │   └── TitleForm.php
│   │       │   └── Tables/
│   │       │       └── TitlesTable.php
│   │       └── TitleResource.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── API/
│   │   │       └── V1/
│   │   │           ├── AddressController.php
│   │   │           ├── BankAccountController.php
│   │   │           ├── Controller.php
│   │   │           ├── IndustryController.php
│   │   │           ├── PartnerController.php
│   │   │           ├── TagController.php
│   │   │           └── TitleController.php
│   │   ├── Requests/
│   │   │   ├── AddressRequest.php
│   │   │   ├── BankAccountRequest.php
│   │   │   ├── IndustryRequest.php
│   │   │   ├── PartnerRequest.php
│   │   │   ├── TagRequest.php
│   │   │   └── TitleRequest.php
│   │   └── Resources/
│   │       └── V1/
│   │           ├── AddressResource.php
│   │           ├── BankAccountResource.php
│   │           ├── IndustryResource.php
│   │           ├── PartnerResource.php
│   │           ├── TagResource.php
│   │           └── TitleResource.php
│   ├── Models/
│   │   ├── Address.php
│   │   ├── Bank.php
│   │   ├── BankAccount.php
│   │   ├── Industry.php
│   │   ├── Partner.php
│   │   ├── Tag.php
│   │   └── Title.php
│   ├── PartnerPlugin.php
│   ├── PartnerServiceProvider.php
│   └── Policies/
│       ├── AddressPolicy.php
│       ├── BankAccountPolicy.php
│       ├── BankPolicy.php
│       ├── IndustryPolicy.php
│       ├── PartnerPolicy.php
│       ├── TagPolicy.php
│       └── TitlePolicy.php
└── tests/
    └── Feature/
        ├── API/
        │   └── V1/
        │       ├── AddressTest.php
        │       ├── BankAccountTest.php
        │       ├── IndustryTest.php
        │       ├── PartnerTest.php
        │       ├── TagTest.php
        │       └── TitleTest.php
        ├── Filament/
        │   └── ResourceGlobalSearchSmokeTest.php
        └── Workflows/
            ├── CompanyIsolationTest.php
            └── CompanyScopingInvariantsTest.php
```

## Models
[VERIFIED]
The `partners` module defines 7 Eloquent models:

### 1. `Partner` (`Webkul\Partner\Models\Partner`)
- **Table**: `partners_partners` (`plugins/webkul/partners/src/Models/Partner.php:36`)
- **Inheritance**: Extends `Illuminate\Foundation\Auth\User as Authenticatable`, implements `Filament\Models\Contracts\FilamentUser`
- **Traits Used**:
  - `Webkul\Support\Traits\BelongsToCompany`
  - `Webkul\Chatter\Traits\HasChatter`
  - `Webkul\Support\Models\Concerns\HasContributedAttributes`
  - `Webkul\Field\Traits\HasCustomFields`
  - `Illuminate\Database\Eloquent\Factories\HasFactory`
  - `Webkul\Chatter\Traits\HasLogActivity`
  - `Webkul\Security\Traits\HasOwnershipScope`
  - `Illuminate\Notifications\Notifiable`
  - `Illuminate\Database\Eloquent\SoftDeletes`
- **Constants**:
  - `ACTIVITY_PLAN_PLUGIN = 'partners'` (`plugins/webkul/partners/src/Models/Partner.php:34`)
- **Casts**:
  - `'account_type' => AccountType::class`
  - `'is_active' => 'boolean'`
- **Company Scoping Mechanism & Isolation**:
  - Declares `BelongsToCompany`, but explicitly overrides `autoAssignsCompany(): bool { return false; }` (`plugins/webkul/partners/src/Models/Partner.php:171-174`).
  - New partners are created with `company_id = null` by default unless explicitly bound to a company or created as an internal user partner (see `docs/database/company-isolation.md`).
  - Query scoping via `CompanyScope` permits records where `company_id IS NULL` (shared across all companies) or `company_id = active_company_id`.
- **Ownership Scope**:
  - Declares `ownershipScopeIsGlobal(): bool { return false; }` (`plugins/webkul/partners/src/Models/Partner.php:38-41`), allowing scoped ownership resolution via `creator_id` / `user_id`.
- **Key Relationships**:
  - `parent()`: `belongsTo(self::class, 'parent_id')` — Reference to parent company or parent entity.
  - `addresses()`: `hasMany(self::class, 'parent_id')->where('account_type', AccountType::ADDRESS)` — Operational addresses attached to this partner.
  - `contacts()`: `hasMany(self::class, 'parent_id')->where('account_type', '!=', AccountType::ADDRESS)` — Individual contacts attached to this corporate partner.
  - `bankAccounts()`: `hasMany(BankAccount::class, 'partner_id')` — Bank accounts owned by this partner.
  - `tags()`: `belongsToMany(Tag::class, 'partners_partner_tag', 'partner_id', 'tag_id')` — Tag classifications.
  - `country()`: `belongsTo(Country::class)` — Country location.
  - `state()`: `belongsTo(State::class)` — State/province location.
  - `title()`: `belongsTo(Title::class)` — Honorific title.
  - `company()`: `belongsTo(Company::class)` — Owning company tenant (if company-specific).
  - `industry()`: `belongsTo(Industry::class)` — Industry classification.
  - `creator()`: `belongsTo(User::class, 'creator_id')` — User who created the record.
  - `user()`: `belongsTo(User::class, 'user_id')` — User account / responsible sales rep linked to this partner.
- **Lifecycle Hooks**:
  - `boot()`: In `creating` event, sets `$partner->creator_id ??= Auth::id()`.

### 2. `Address` (`Webkul\Partner\Models\Address`)
- **Table**: `partners_partners` (`plugins/webkul/partners/src/Models/Address.php:5`)
- **Inheritance**: Extends `Webkul\Partner\Models\Partner` (Single Table Inheritance)
- **Behavior**: Represents operational addresses (invoicing, delivery, permanent, present, other) stored directly in `partners_partners` where `account_type = 'address'` and `parent_id` points to the owning partner.

### 3. `BankAccount` (`Webkul\Partner\Models\BankAccount`)
- **Table**: `partners_bank_accounts` (`plugins/webkul/partners/src/Models/BankAccount.php:18`)
- **Inheritance**: Extends `Illuminate\Database\Eloquent\Model`
- **Traits Used**:
  - `Illuminate\Database\Eloquent\Factories\HasFactory`
  - `Illuminate\Database\Eloquent\SoftDeletes`
- **Casts**:
  - `'is_active' => 'boolean'`
  - `'can_send_money' => 'boolean'`
- **Key Relationships**:
  - `partner()`: `belongsTo(Partner::class, 'partner_id')`
  - `bank()`: `belongsTo(Bank::class, 'bank_id')`
  - `creator()`: `belongsTo(User::class, 'creator_id')`
- **Lifecycle Hooks**:
  - `boot()`: In `creating`, sets `$bankAccount->creator_id ??= Auth::id()` and sets `$bankAccount->account_holder_name = $bankAccount->partner->name`.
  - In `updating`, synchronizes `$bankAccount->account_holder_name = $bankAccount->partner->name`.

### 4. `Bank` (`Webkul\Partner\Models\Bank`)
- **Table**: `banks` (`plugins/webkul/partners/src/Models/Bank.php:7`)
- **Inheritance**: Extends `Webkul\Support\Models\Bank`
- **Behavior**: Local proxy/alias for the foundational `Bank` entity defined in the `support` module.

### 5. `Industry` (`Webkul\Partner\Models\Industry`)
- **Table**: `partners_industries` (`plugins/webkul/partners/src/Models/Industry.php:18`)
- **Inheritance**: Extends `Illuminate\Database\Eloquent\Model`
- **Traits Used**:
  - `Illuminate\Database\Eloquent\Factories\HasFactory`
  - `Illuminate\Database\Eloquent\SoftDeletes`
- **Key Relationships**:
  - `creator()`: `belongsTo(User::class, 'creator_id')`
- **Lifecycle Hooks**:
  - `boot()`: In `creating`, sets `$industry->creator_id ??= Auth::id()`.

### 6. `Tag` (`Webkul\Partner\Models\Tag`)
- **Table**: `partners_tags` (`plugins/webkul/partners/src/Models/Tag.php:18`)
- **Inheritance**: Extends `Illuminate\Database\Eloquent\Model`
- **Traits Used**:
  - `Illuminate\Database\Eloquent\Factories\HasFactory`
  - `Illuminate\Database\Eloquent\SoftDeletes`
- **Key Relationships**:
  - `creator()`: `belongsTo(User::class, 'creator_id')`
- **Lifecycle Hooks**:
  - `boot()`: In `creating`, sets `$tag->creator_id ??= Auth::id()`.

### 7. `Title` (`Webkul\Partner\Models\Title`)
- **Table**: `partners_titles` (`plugins/webkul/partners/src/Models/Title.php:17`)
- **Inheritance**: Extends `Illuminate\Database\Eloquent\Model`
- **Traits Used**:
  - `Illuminate\Database\Eloquent\Factories\HasFactory`
  - *(Note: `Title` does not use `SoftDeletes`)*
- **Key Relationships**:
  - `creator()`: `belongsTo(User::class, 'creator_id')`
- **Lifecycle Hooks**:
  - `boot()`: In `creating`, sets `$title->creator_id ??= Auth::id()`.

## Database
[VERIFIED]
The `partners` module defines 6 physical tables, verified in `docs/database/erds/core.md`:

### 1. `partners_partners`
- **Migration**: `2024_12_11_101220_create_partners_partners_table.php`, modified by `2025_03_28_115218_add_address_columns_in_partners_partners_table.php` and `2026_07_30_100000_null_company_on_non_user_partners.php`
- **Columns**:
  - `id` (bigint unsigned, PK)
  - `account_type` (varchar(255), default `'individual'`, indexed) — `individual`, `company`, `address`
  - `sub_type` (varchar(255), nullable, default `'partner'`, indexed) — `partner` or `AddressType` (`invoice`, `delivery`, `permanent`, `present`, `other`)
  - `name` (varchar(255), not null, indexed)
  - `avatar` (varchar(255), nullable)
  - `email` (varchar(255), nullable)
  - `job_title` (varchar(255), nullable)
  - `website` (varchar(255), nullable)
  - `tax_id` (varchar(255), nullable, indexed)
  - `phone` (varchar(255), nullable, indexed)
  - `mobile` (varchar(255), nullable, indexed)
  - `color` (varchar(255), nullable)
  - `company_registry` (varchar(255), nullable, indexed)
  - `reference` (varchar(255), nullable, indexed)
  - `street1` (varchar(255), nullable)
  - `street2` (varchar(255), nullable)
  - `city` (varchar(255), nullable)
  - `zip` (varchar(255), nullable)
  - `state_id` (foreignId -> `states.id`, nullable, `restrictOnDelete`)
  - `country_id` (foreignId -> `countries.id`, nullable, `restrictOnDelete`)
  - `parent_id` (foreignId -> `partners_partners.id`, nullable, `nullOnDelete`)
  - `creator_id` (foreignId -> `users.id`, nullable, `nullOnDelete`)
  - `user_id` (foreignId -> `users.id`, nullable, `nullOnDelete`)
  - `title_id` (foreignId -> `partners_titles.id`, nullable, `nullOnDelete`)
  - `company_id` (foreignId -> `companies.id`, nullable, `nullOnDelete`)
  - `industry_id` (foreignId -> `partners_industries.id`, nullable, `nullOnDelete`)
  - `deleted_at` (timestamp, nullable) — Soft deletes
  - `created_at`, `updated_at` (timestamps)

### 2. `partners_bank_accounts`
- **Migration**: `2024_12_11_101420_create_partners_bank_accounts_table.php`
- **Columns**:
  - `id` (bigint unsigned, PK)
  - `account_number` (varchar(255), unique)
  - `account_holder_name` (varchar(255), not null)
  - `is_active` (boolean, default `1`)
  - `can_send_money` (boolean, default `0`)
  - `creator_id` (foreignId -> `users.id`, nullable, `nullOnDelete`)
  - `partner_id` (foreignId -> `partners_partners.id`, not null, `cascadeOnDelete`)
  - `bank_id` (foreignId -> `banks.id`, not null, `cascadeOnDelete`)
  - `deleted_at` (timestamp, nullable) — Soft deletes
  - `created_at`, `updated_at` (timestamps)

### 3. `partners_industries`
- **Migration**: `2024_12_11_101127_create_partners_industries_table.php`
- **Columns**:
  - `id` (bigint unsigned, PK)
  - `name` (varchar(255), not null)
  - `description` (text, nullable)
  - `is_active` (boolean, default `1`)
  - `creator_id` (foreignId -> `users.id`, nullable, `nullOnDelete`)
  - `deleted_at` (timestamp, nullable) — Soft deletes
  - `created_at`, `updated_at` (timestamps)

### 4. `partners_titles`
- **Migration**: `2024_12_11_101127_create_partners_titles_table.php`
- **Columns**:
  - `id` (bigint unsigned, PK)
  - `name` (varchar(255), not null)
  - `short_name` (varchar(255), not null)
  - `creator_id` (foreignId -> `users.id`, nullable, `nullOnDelete`)
  - `created_at`, `updated_at` (timestamps)

### 5. `partners_tags`
- **Migration**: `2024_12_11_101927_create_partners_tags_table.php`
- **Columns**:
  - `id` (bigint unsigned, PK)
  - `name` (varchar(255), unique)
  - `color` (varchar(255), nullable)
  - `creator_id` (foreignId -> `users.id`, nullable, `nullOnDelete`)
  - `deleted_at` (timestamp, nullable) — Soft deletes
  - `created_at`, `updated_at` (timestamps)

### 6. `partners_partner_tag` (Junction Table)
- **Migration**: `2024_12_11_111929_create_partners_partner_tag_table.php`
- **Columns**:
  - `tag_id` (foreignId -> `partners_tags.id`, `cascadeOnDelete`)
  - `partner_id` (foreignId -> `partners_partners.id`, `cascadeOnDelete`)

### Seeders
- `DatabaseSeeder` (`plugins/webkul/partners/database/seeders/DatabaseSeeder.php`): Invokes `IndustrySeeder` and `TitleSeeder`.
- `IndustrySeeder` (`plugins/webkul/partners/database/seeders/IndustrySeeder.php`): Populates 21 standardized industry sectors (Agriculture, Construction, Energy Supply, Finance/Insurance, Manufacturing, Real Estate, etc.).
- `TitleSeeder` (`plugins/webkul/partners/database/seeders/TitleSeeder.php`): Populates 5 standard titles (Doctor/Dr., Madam/Mrs, Miss/Miss, Mister/Mr., Professor/Prof.).

## Filament resources/pages/widgets/clusters
[VERIFIED]

The `partners` module implements 7 Filament resources under `Webkul\Partner\Filament\Resources`. All 7 resources intentionally configure `protected static bool $shouldRegisterNavigation = false;`, serving as headless base resources. Navigation registration and clustering in the Admin panel is decoupled and activated by the optional `contacts` module (`plugins/webkul/contacts`).

### 1. `PartnerResource` (`Webkul\Partner\Filament\Resources\PartnerResource`)
- **Model**: `Webkul\Partner\Models\Partner`
- **Navigation**: Hidden by default (`$shouldRegisterNavigation = false`)
- **Global Search**: Disabled on base resource (`$isGloballySearchable = false`), but implements `getGloballySearchableAttributes()` returning `['name', 'email', 'phone']` and `getGlobalSearchResultDetails()`.
- **Custom Fields Integration**: Uses `Webkul\Field\Filament\Traits\HasCustomFields`. Injects dynamic custom fields into `form()`, `infolist()`, and `table()`.
- **Eager Loading & Scoping**: `getEloquentQuery()` applies `->ownership()` and eager loads relations declared in `PartnerSchemaRegistry::eagerLoads()`.
- **Components**:
  - **Form** (`PartnerForm.php`):
    - `generalSection()`: Radio selector for `account_type` (`individual` vs `company`), partner `name` input (placeholder adapts dynamically), `parent_id` company selector (visible when `account_type == individual`), `avatar` image upload with cover crop, `tax_id`, `job_title` (visible for individuals), `phone`, `mobile`, `email`, `website`, `title_id` (visible for individuals), `tags` multi-select with inline creation, and nested Address fieldset (`street1`, `street2`, `city`, `zip`, dynamic `country_id`, and reactive `state_id` filter).
    - `salesPurchaseTab()`: Responsible user selector (`user_id`), `company_registry` / company ID, `reference`, and `industry_id`.
    - Extension injection points: `Registry::renderForm('general.after')`, `Registry::renderForm('tabs.append')`, `Registry::renderForm('sales.fields')`, `Registry::renderForm('salesPurchase.append')`.
  - **Infolist** (`PartnerInfolist.php`):
    - `generalSection()`: Badge for `account_type`, bold large name, parent company link for individuals, circular avatar entry, grid with tax ID, job title, phone, mobile, email, website, title, dynamic hex-color tag badges, and address details.
    - `salesPurchaseTab()`: Sales responsible user, company registry, reference, and industry name.
    - Extension injection points: `Registry::renderInfolist('general.after')`, `Registry::renderInfolist('tabs.append')`, `Registry::renderInfolist('sales.fields')`, `Registry::renderInfolist('salesPurchase.append')`.
  - **Table** (`PartnersTable.php`):
    - Presentation: Responsive card-grid layout (`contentGrid(['sm' => 1, 'md' => 2, 'xl' => 3, '2xl' => 4])`) with vertically stacked attributes (`avatar`, `name`, `parent.name`, `job_title`, `email`, `phone`, color-coded `tags.name`).
    - Filtering: Excludes `account_type == address` from the main grid view. Implements slide-over modal `QueryBuilder` with constraints for `account_type`, `name`, `email`, `job_title`, `website`, `tax_id`, `phone`, `mobile`, `company_registry`, `reference`, `parent`, `creator`, `user`, `title`, `company`, `industry`.
    - Grouping: Supports grouping by `account_type`, `parent.name`, `title.name`, `job_title`, and `industry.name`.
    - Record Actions: `ActivityTableAction` (Chatter activity schedule), `ViewAction`, `EditAction`, `RestoreAction`, `DeleteAction`, and `ForceDeleteAction` (with SQL exception catching).
    - Bulk Actions: `RestoreBulkAction`, `DeleteBulkAction`, `ForceDeleteBulkAction`.
- **Pages**:
  - `ListPartners` (`Pages/ListPartners.php`): Uses `HasTableViews` to provide preset tab filters: `'individuals'`, `'companies'`, `'employees'`, `'customers'` (`customer_rank > 0`), and `'vendors'` (`supplier_rank > 0`).
  - `CreatePartner` (`Pages/CreatePartner.php`): Standard record creation.
  - `EditPartner` (`Pages/EditPartner.php`): Uses `HasRecordNavigationTabs` and injects `ChatterAction` with attached activity plans in header actions.
  - `ViewPartner` (`Pages/ViewPartner.php`): Uses `HasRecordNavigationTabs` and injects `ChatterAction` in header actions.
  - `ManageAddresses` (`Pages/ManageAddresses.php`): Uses `HasRecordNavigationTabs` to render child operational addresses via `AddressResource::form()` and `AddressResource::table()`.
  - `ManageContacts` (`Pages/ManageContacts.php`): Uses `HasRecordNavigationTabs` to manage child contact partners, automatically injecting `creator_id` and matching `company_id`.
- **Relation Managers**:
  - `AddressesRelationManager` (`RelationManagers/AddressesRelationManager.php`): Manages `$partner->addresses()` relationship.
  - `ContactsRelationManager` (`RelationManagers/ContactsRelationManager.php`): Manages `$partner->contacts()` relationship.

### 2. `AddressResource` (`Webkul\Partner\Filament\Resources\AddressResource`)
- **Model**: `Webkul\Partner\Models\Address`
- **Navigation**: Hidden (`$shouldRegisterNavigation = false`)
- **Form** (`AddressForm.php`): Radio `sub_type` (`AddressType`), `parent_id` (hidden when rendered in `ManageAddresses`), `name`, `email`, `phone`, `mobile`, `street1`, `street2`, `city`, `zip`, `country_id`, and country-filtered `state_id`.
- **Table** (`AddressesTable.php`): Columns for `sub_type`, `name`, `country.name`, `state.name`, `street1`, `street2`, `city`, `zip`. Header create action sets `account_type = Address` and syncs `company_id` from the owner partner.

### 3. `BankAccountResource` (`Webkul\Partner\Filament\Resources\BankAccountResource`)
- **Model**: `Webkul\Partner\Models\BankAccount`
- **Navigation**: Hidden (`$shouldRegisterNavigation = false`)
- **Query Scoping**: `getEloquentQuery()` restricts to records `whereHas('partner')`.
- **Form** (`BankAccountForm.php`): `account_number`, `can_send_money` toggle, `bank_id` (with inline bank creation and `hide_deleted_unless_selected($state)` soft-delete query scoping), and `partner_id`.
- **Table** (`BankAccountsTable.php`): Columns for `account_number`, `bank.name`, `partner.name`, `can_send_money` boolean icon, timestamps. Filters for `can_send_money` (ternary), `bank_id`, `partner_id`, and `creator_id`.
- **Pages**: `ManageBankAccounts` (`Pages/ManageBankAccounts.php`) with `'all'` and `'archived'` tabs.

### 4. `BankResource` (`Webkul\Partner\Filament\Resources\BankResource`)
- **Model**: `Webkul\Partner\Models\Bank` (extends `Webkul\Support\Models\Bank`)
- **Navigation**: Hidden (`$shouldRegisterNavigation = false`)
- **Pages**: `ManageBanks` (`Pages/ManageBanks.php`) extending `Webkul\Support\Filament\Resources\BankResource\Pages\ManageBanks`.

### 5. `IndustryResource` (`Webkul\Partner\Filament\Resources\IndustryResource`)
- **Model**: `Webkul\Partner\Models\Industry`
- **Navigation**: Hidden (`$shouldRegisterNavigation = false`)
- **Form** (`IndustryForm.php`): `name` (unique), `description` (unique).
- **Table** (`IndustriesTable.php`): Columns for `name`, `description`, with soft-delete restore and force delete actions.
- **Pages**: `ManageIndustries` (`Pages/ManageIndustries.php`) with `'all'` and `'archived'` tabs.

### 6. `TagResource` (`Webkul\Partner\Filament\Resources\TagResource`)
- **Model**: `Webkul\Partner\Models\Tag`
- **Navigation**: Hidden (`$shouldRegisterNavigation = false`)
- **Form** (`TagForm.php`): `name` (unique), `color` (ColorPicker hex color).
- **Table** (`TagsTable.php`): Columns for `name` and `color` (`ColorColumn`), with soft-delete actions.
- **Pages**: `ManageTags` (`Pages/ManageTags.php`) with `'all'` and `'archived'` tabs.

### 7. `TitleResource` (`Webkul\Partner\Filament\Resources\TitleResource`)
- **Model**: `Webkul\Partner\Models\Title`
- **Navigation**: Hidden (`$shouldRegisterNavigation = false`)
- **Form** (`TitleForm.php`): `name`, `short_name`.
- **Table** (`TitlesTable.php`): Columns for `name`, `short_name`, timestamps.
- **Pages**: `ManageTitles` (`Pages/ManageTitles.php`).

## Panels
[VERIFIED]
- **Admin Panel (`admin`)**: Resources are registered conditionally for the `admin` panel via `PartnerPlugin::register()` (`plugins/webkul/partners/src/PartnerPlugin.php:23`).
- **Customer Panel (`customer`)**: No resources are registered directly in the customer panel by this plugin. (Customer portal authentication on partners is contributed by the `website` plugin).

## Services
[VERIFIED]
- **Schema Contribution Registry (`PartnerSchemaRegistry`)**:
  - Class: `Webkul\Partner\Filament\Resources\PartnerResource\Support\PartnerSchemaRegistry` (`plugins/webkul/partners/src/Filament/Resources/PartnerResource/Support/PartnerSchemaRegistry.php:7`)
  - Extends `Webkul\Support\Filament\Contributions\AbstractSchemaRegistry`.
  - Defines scope `'partner'`.
  - Provides extension hooks for downstream modules:
    - Form sections & tabs: `PartnerSchemaRegistry::form('sales.fields', ...)`, `PartnerSchemaRegistry::form('tabs.append', ...)`
    - Infolist sections: `PartnerSchemaRegistry::infolist('general.after', ...)`, `PartnerSchemaRegistry::infolist('tabs.append', ...)`
    - Table columns & filters: `PartnerSchemaRegistry::table('columns', ...)`, `PartnerSchemaRegistry::table('filters.append', ...)`
    - Actions: `PartnerSchemaRegistry::actions('view.header', ...)`, `PartnerSchemaRegistry::actions('edit.header', ...)`
    - Eager loading: `PartnerSchemaRegistry::eagerLoad([...])`

## Events
[NOT APPLICABLE]
The `partners` module does not define custom Event classes. Domain events on partners are broadcast via standard Eloquent lifecycle events and activity logging through `Webkul\Chatter\Traits\HasLogActivity`.

## Listeners
[NOT APPLICABLE]
No event listener classes exist in `plugins/webkul/partners/src/Listeners`.

## Observers
[NOT APPLICABLE]
No dedicated Observer classes exist. Lifecycle events are registered inline via Eloquent `boot()` closures:
- `Partner::creating`: Sets default `creator_id` from authenticated user (`Auth::id()`).
- `BankAccount::creating` / `updating`: Sets `creator_id` and synchronizes `account_holder_name = $bankAccount->partner->name`.
- `Industry::creating`, `Tag::creating`, `Title::creating`: Sets default `creator_id`.

## Policies
[VERIFIED]
The `partners` plugin provides 7 authorization policies under `Webkul\Partner\Policies`, configured with Filament Shield permissions in `plugins/webkul/partners/config/filament-shield.php`:

1. **`PartnerPolicy` (`Webkul\Partner\Policies\PartnerPolicy`)**:
   - `viewAny`: `'view_any_partner_partner'`
   - `view`: `'view_partner_partner'`
   - `create`: `'create_partner_partner'`
   - `update`: `'update_partner_partner'`
   - `delete`: `'delete_partner_partner'`
   - `deleteAny`: `'delete_any_partner_partner'`
   - `restore`: `'restore_partner_partner'`
   - `restoreAny`: `'restore_any_partner_partner'`
   - `forceDelete`: `'force_delete_partner_partner'`
   - `forceDeleteAny`: `'force_delete_any_partner_partner'`

2. **`AddressPolicy` (`Webkul\Partner\Policies\AddressPolicy`)**:
   - Mapped abilities: `'view_any_partner_address'`, `'view_partner_address'`, `'create_partner_address'`, `'update_partner_address'`, `'delete_partner_address'`, `'delete_any_partner_address'`, `'restore_partner_address'`, `'restore_any_partner_address'`, `'force_delete_partner_address'`, `'force_delete_any_partner_address'`.

3. **`BankAccountPolicy` (`Webkul\Partner\Policies\BankAccountPolicy`)**:
   - Mapped abilities: `'view_any_partner_bank::account'`, `'view_partner_bank::account'`, `'create_partner_bank::account'`, `'update_partner_bank::account'`, `'delete_partner_bank::account'`, `'delete_any_partner_bank::account'`, `'restore_partner_bank::account'`, `'restore_any_partner_bank::account'`, `'force_delete_partner_bank::account'`, `'force_delete_any_partner_bank::account'`.

4. **`BankPolicy` (`Webkul\Partner\Policies\BankPolicy`)**:
   - Mapped abilities: `'view_any_partner_bank'`, `'view_partner_bank'`, `'create_partner_bank'`, `'update_partner_bank'`, `'delete_partner_bank'`, `'delete_any_partner_bank'`, `'restore_partner_bank'`, `'restore_any_partner_bank'`, `'force_delete_partner_bank'`, `'force_delete_any_partner_bank'`.

5. **`IndustryPolicy` (`Webkul\Partner\Policies\IndustryPolicy`)**:
   - Mapped abilities: `'view_any_partner_industry'`, `'view_partner_industry'`, `'create_partner_industry'`, `'update_partner_industry'`, `'delete_partner_industry'`, `'delete_any_partner_industry'`, `'restore_partner_industry'`, `'restore_any_partner_industry'`, `'force_delete_partner_industry'`, `'force_delete_any_partner_industry'`.

6. **`TagPolicy` (`Webkul\Partner\Policies\TagPolicy`)**:
   - Mapped abilities: `'view_any_partner_tag'`, `'view_partner_tag'`, `'create_partner_tag'`, `'update_partner_tag'`, `'delete_partner_tag'`, `'delete_any_partner_tag'`, `'restore_partner_tag'`, `'restore_any_partner_tag'`, `'force_delete_partner_tag'`, `'force_delete_any_partner_tag'`.

7. **`TitlePolicy` (`Webkul\Partner\Policies\TitlePolicy`)**:
   - Mapped abilities: `'view_any_partner_title'`, `'create_partner_title'`, `'update_partner_title'`, `'delete_partner_title'`, `'delete_any_partner_title'`. (No restore/forceDelete abilities since `Title` does not use soft deletes).

## Routes
[VERIFIED]
The plugin registers REST API routes under `plugins/webkul/partners/routes/api.php` within route group:
- **Prefix**: `admin/api/v1/partners`
- **Name**: `admin.api.v1.partners.`
- **Middleware**: `['auth:sanctum']`

| Verb | URI | Action / Controller | Route Name | Description |
|---|---|---|---|---|
| `GET` | `admin/api/v1/partners/titles` | `TitleController@index` | `titles.index` | List titles with filters/sort |
| `POST` | `admin/api/v1/partners/titles` | `TitleController@store` | `titles.store` | Create title |
| `GET` | `admin/api/v1/partners/titles/{title}` | `TitleController@show` | `titles.show` | Show single title |
| `PUT/PATCH` | `admin/api/v1/partners/titles/{title}` | `TitleController@update` | `titles.update` | Update title |
| `DELETE` | `admin/api/v1/partners/titles/{title}` | `TitleController@destroy` | `titles.destroy` | Delete title |
| `GET` | `admin/api/v1/partners/tags` | `TagController@index` | `tags.index` | List tags |
| `POST` | `admin/api/v1/partners/tags` | `TagController@store` | `tags.store` | Create tag |
| `GET` | `admin/api/v1/partners/tags/{tag}` | `TagController@show` | `tags.show` | Show single tag |
| `PUT/PATCH` | `admin/api/v1/partners/tags/{tag}` | `TagController@update` | `tags.update` | Update tag |
| `DELETE` | `admin/api/v1/partners/tags/{tag}` | `TagController@destroy` | `tags.destroy` | Soft delete tag |
| `POST` | `admin/api/v1/partners/tags/{tag}/restore` | `TagController@restore` | `tags.restore` | Restore soft-deleted tag |
| `DELETE` | `admin/api/v1/partners/tags/{tag}/force-destroy` | `TagController@forceDestroy` | `tags.force-destroy` | Permanently delete tag |
| `GET` | `admin/api/v1/partners/industries` | `IndustryController@index` | `industries.index` | List industries |
| `POST` | `admin/api/v1/partners/industries` | `IndustryController@store` | `industries.store` | Create industry |
| `GET` | `admin/api/v1/partners/industries/{industry}` | `IndustryController@show` | `industries.show` | Show single industry |
| `PUT/PATCH` | `admin/api/v1/partners/industries/{industry}` | `IndustryController@update` | `industries.update` | Update industry |
| `DELETE` | `admin/api/v1/partners/industries/{industry}` | `IndustryController@destroy` | `industries.destroy` | Soft delete industry |
| `POST` | `admin/api/v1/partners/industries/{industry}/restore` | `IndustryController@restore` | `industries.restore` | Restore industry |
| `DELETE` | `admin/api/v1/partners/industries/{industry}/force-destroy` | `IndustryController@forceDestroy` | `industries.force-destroy` | Permanently delete industry |
| `GET` | `admin/api/v1/partners/partners` | `PartnerController@index` | `partners.index` | List partners |
| `POST` | `admin/api/v1/partners/partners` | `PartnerController@store` | `partners.store` | Create partner |
| `GET` | `admin/api/v1/partners/partners/{partner}` | `PartnerController@show` | `partners.show` | Show single partner |
| `PUT/PATCH` | `admin/api/v1/partners/partners/{partner}` | `PartnerController@update` | `partners.update` | Update partner |
| `DELETE` | `admin/api/v1/partners/partners/{partner}` | `PartnerController@destroy` | `partners.destroy` | Soft delete partner |
| `POST` | `admin/api/v1/partners/partners/{partner}/restore` | `PartnerController@restore` | `partners.restore` | Restore partner |
| `DELETE` | `admin/api/v1/partners/partners/{partner}/force-destroy` | `PartnerController@forceDestroy` | `partners.force-destroy` | Permanently delete partner |
| `GET` | `admin/api/v1/partners/partners/{partner}/addresses` | `AddressController@index` | `partners.addresses.index` | List partner addresses |
| `POST` | `admin/api/v1/partners/partners/{partner}/addresses` | `AddressController@store` | `partners.addresses.store` | Create partner address |
| `GET` | `admin/api/v1/partners/partners/{partner}/addresses/{address}` | `AddressController@show` | `partners.addresses.show` | Show partner address |
| `PUT/PATCH` | `admin/api/v1/partners/partners/{partner}/addresses/{address}` | `AddressController@update` | `partners.addresses.update` | Update partner address |
| `DELETE` | `admin/api/v1/partners/partners/{partner}/addresses/{address}` | `AddressController@destroy` | `partners.addresses.destroy` | Soft delete partner address |
| `POST` | `admin/api/v1/partners/partners/{partner}/addresses/{address}/restore` | `AddressController@restore` | `partners.addresses.restore` | Restore partner address |
| `DELETE` | `admin/api/v1/partners/partners/{partner}/addresses/{address}/force-destroy` | `AddressController@forceDestroy` | `partners.addresses.force-destroy` | Permanently delete address |
| `GET` | `admin/api/v1/partners/partners/{partner}/bank-accounts` | `BankAccountController@index` | `partners.bank-accounts.index` | List partner bank accounts |
| `POST` | `admin/api/v1/partners/partners/{partner}/bank-accounts` | `BankAccountController@store` | `partners.bank-accounts.store` | Create bank account |
| `GET` | `admin/api/v1/partners/partners/{partner}/bank-accounts/{bank_account}` | `BankAccountController@show` | `partners.bank-accounts.show` | Show bank account |
| `PUT/PATCH` | `admin/api/v1/partners/partners/{partner}/bank-accounts/{bank_account}` | `BankAccountController@update` | `partners.bank-accounts.update` | Update bank account |
| `DELETE` | `admin/api/v1/partners/partners/{partner}/bank-accounts/{bank_account}` | `BankAccountController@destroy` | `partners.bank-accounts.destroy` | Soft delete bank account |
| `POST` | `admin/api/v1/partners/partners/{partner}/bank-accounts/{bank_account}/restore` | `BankAccountController@restore` | `partners.bank-accounts.restore` | Restore bank account |
| `DELETE` | `admin/api/v1/partners/partners/{partner}/bank-accounts/{bank_account}/force-destroy` | `BankAccountController@forceDestroy` | `partners.bank-accounts.force-destroy` | Permanently delete bank account |

## Settings
[NOT APPLICABLE]
The `partners` plugin defines no custom Spatie settings classes or settings migrations.

## Translations
[VERIFIED]
Translation files are located in `plugins/webkul/partners/resources/lang/` supporting 5 locales:
- `ar` (Arabic)
- `en` (English)
- `es` (Spanish)
- `fr` (French)
- `pt_BR` (Portuguese - Brazil)

Dictionary structures:
- `enums/account-type.php`, `enums/address-type.php`
- `filament/resources/address.php`
- `filament/resources/bank-account.php`, `filament/resources/bank-account/pages/manage-bank-accounts.php`
- `filament/resources/industry.php`, `filament/resources/industry/pages/manage-industries.php`
- `filament/resources/partner.php`, `filament/resources/partner/pages/list-partners.php`, `create-partner.php`, `edit-partner.php`, `view-partner.php`, `manage-addresses.php`, `manage-contacts.php`, `relation-managers/contacts.php`
- `filament/resources/tag.php`, `filament/resources/tag/pages/manage-tags.php`
- `filament/resources/title.php`, `filament/resources/title/pages/manage-titles.php`

## Tests
[VERIFIED]
The `partners` plugin contains an active test suite located under `plugins/webkul/partners/tests/`:

1. **Filament Feature Tests**:
   - `PartnerTypeViewsTest.php` (`plugins/webkul/partners/tests/Feature/Filament/PartnerTypeViewsTest.php`): Verifies preset table views (individuals, companies, employees, customers, vendors) on `ListPartners` and partner type ranking filters.

2. **API V1 Feature Tests**:
   - `PartnerTest.php` (`plugins/webkul/partners/tests/Feature/API/V1/PartnerTest.php`): Verifies unauthenticated 401, unauthorized 403, listing, creation, validation, show, update, soft delete, restore, and permanent deletion.
   - `AddressTest.php` (`plugins/webkul/partners/tests/Feature/API/V1/AddressTest.php`): Verifies nested partner address CRUD lifecycle and soft deletes.
   - `BankAccountTest.php` (`plugins/webkul/partners/tests/Feature/API/V1/BankAccountTest.php`): Verifies nested partner bank account CRUD lifecycle and soft deletes.
   - `IndustryTest.php` (`plugins/webkul/partners/tests/Feature/API/V1/IndustryTest.php`): Verifies industry CRUD lifecycle and soft deletes.
   - `TagTest.php` (`plugins/webkul/partners/tests/Feature/API/V1/TagTest.php`): Verifies tag CRUD lifecycle and soft deletes.
   - `TitleTest.php` (`plugins/webkul/partners/tests/Feature/API/V1/TitleTest.php`): Verifies title CRUD lifecycle.

3. **Filament Integration Smoke Tests**:
   - `ResourceGlobalSearchSmokeTest.php` (`plugins/webkul/partners/tests/Feature/Filament/ResourceGlobalSearchSmokeTest.php`): Verifies global search titles and result details across empty and populated relationship states.

4. **Workflow & Multi-Tenant Invariants Tests**:
   - `CompanyIsolationTest.php` (`plugins/webkul/partners/tests/Feature/Workflows/CompanyIsolationTest.php`): Verifies cross-company data visibility invariants, ensuring company-bound partners are isolated while unassigned partners remain globally shared across tenants.
   - `CompanyScopingInvariantsTest.php` (`plugins/webkul/partners/tests/Feature/Workflows/CompanyScopingInvariantsTest.php`): Asserts that `Partner` is explicitly registered as shared master data and is not automatically stamped with an active company context.

## Runtime dependencies
[VERIFIED]
None (`—`). `PartnerServiceProvider::configureCustomPackage()` declares no runtime plugin dependencies via `Package::hasDependencies()`.

Architectural and code-level interactions with other core packages are detailed below under Cross-plugin relationships.

## Cross-plugin relationships
[VERIFIED]

The `partners` module sits at the center of Aureus ERP's entity graph:

1. **`security` (`Webkul\Security`)**:
   - `User` <-> `Partner` 1:1 Synchronization: When a user is created or updated in the security module, an associated `Partner` record is synchronized (`users.partner_id` references `partners_partners.id` and `partners_partners.user_id` references `users.id`).
   - Ownership: `partners_partners.creator_id` references `users.id` to record who created the master record.

2. **`support` (`Webkul\Support`)**:
   - `Company` <-> `Partner`: Every tenant company in `companies` has an associated legal partner record (`companies.partner_id` references `partners_partners.id` with `restrictOnDelete`).
   - `Bank` <-> `BankAccount`: `partners_bank_accounts.bank_id` references `banks.id`.
   - `Country` / `State`: `partners_partners.country_id` and `state_id` reference `countries.id` and `states.id`.

3. **`chatter` (`Webkul\Chatter`)**:
   - Follower Identification: All record followers in `chatter_followers` store a `partner_id` (`chatter_followers.partner_id` references `partners_partners.id` with `cascadeOnDelete`). When users subscribe to any model in the ERP, their corresponding Partner identity is recorded as the subscriber.
   - Chatter on Partners: `Partner` uses `HasChatter` and `HasLogActivity`, allowing users to follow partner records, post internal notes, log audit trails, and schedule activity plans.

4. **`contacts` (`Webkul\Contact`)**:
   - The optional `contacts` plugin extends `PartnerResource` and configuration resources (`IndustryResource`, `TagResource`, `BankResource`, `BankAccountResource`, `TitleResource`) to register them in the Filament main menu navigation and organize configuration resources into the `Configurations` cluster.

5. **`accounts` / `invoices` (`Webkul\Account` / `Webkul\Invoice`)**:
   - Dynamic Property Injection: The `accounts` plugin dynamically binds financial properties to `Partner` via `Partner::resolveRelationUsing()`:
     - `propertyAccountPayable` -> `belongsTo(Account::class, 'property_account_payable_id')`
     - `propertyAccountReceivable` -> `belongsTo(Account::class, 'property_account_receivable_id')`
     - `propertyAccountPosition` -> `belongsTo(FiscalPosition::class, 'property_account_position_id')`
     - `propertyPaymentTerm` / `propertySupplierPaymentTerm` -> `belongsTo(PaymentTerm::class)`
   - Schema Registry Injection: Injects `Invoicing` and `Internal Notes` tabs and sales accounting fields into `PartnerResource` via `PartnerSchemaRegistry`.

6. **`website` (`Webkul\Website`)**:
   - Contributes customer portal credentials, password management actions, and portal access indicators via `PartnerSchemaRegistry`.

7. **Transactional Plugins (`sales`, `purchases`, `projects`, `recruitments`, `maintenance`, `analytics`)**:
   - `sales`: `Order.partner_id` references customer `Partner`.
   - `purchases`: `Order.partner_id` and `Requisition.partner_id` reference vendor `Partner`.
   - `projects`: `Project.partner_id` and `Task.partner_id` reference client `Partner`.
   - `recruitments`: `Candidate.partner_id` references candidate `Partner`.
   - `maintenance`: `Equipment.partner_id` references vendor/manufacturer `Partner`.
   - `analytics`: `Record.partner_id` references commercial `Partner`.

## Data flow
[VERIFIED]

1. **Partner Master Record Ingestion**:
   - User navigates to Partner/Contacts creation UI or issues `POST admin/api/v1/partners/partners`.
   - Payload validates name, account type (`individual` vs `company`), optional tax ID, addresses, and parent organization.
   - `Partner::creating` hook populates `creator_id = Auth::id()`.
   - `Partner::autoAssignsCompany()` returns `false`, ensuring the new partner remains globally accessible across all company tenants unless explicitly assigned to a specific company ID.
   - Custom fields registered via the `fields` module are validated and persisted in `custom_fields` JSON metadata.

2. **Contact Hierarchy Linking**:
   - When an individual contact is created under a company, `parent_id` is set to the company partner's ID (`account_type = individual`, `parent_id = $company->id`).
   - The company's `contacts()` relation (`hasMany(Partner::class, 'parent_id')->where('account_type', '!=', 'address')`) resolves all employee and contact records.

3. **Operational Address Attachment**:
   - When operational addresses (e.g. Invoicing, Delivery) are added to a partner via the UI or `POST admin/api/v1/partners/partners/{id}/addresses`, records are inserted into `partners_partners` with `account_type = 'address'`, `sub_type = AddressType` (e.g. `'delivery'`), and `parent_id = $partner->id`.
   - Address records inherit the company scoping of the parent record.

4. **Bank Account Registration & Synchronization**:
   - A bank account record is created in `partners_bank_accounts` referencing `partner_id` and `bank_id`.
   - `BankAccount` model hook automatically copies and synchronizes the account holder name from `Partner.name`.

5. **Follower & Communication Flow**:
   - When a user logs a note, attaches an activity plan, or follows a partner, `HasChatter` records messages in `chatter_messages` and subscribers in `chatter_followers` using the follower's `partner_id`.

## Business rules
[VERIFIED]

1. **Company Isolation Bypass for Master Data**:
   - Partners represent shared business entities that can interact across multiple company branches in a conglomerate.
   - By default, `Partner::autoAssignsCompany()` returns `false`. A partner created without an explicit `company_id` is shared globally across all companies.
   - If a partner has a specific `company_id`, it is isolated to that tenant company via `CompanyScope`.

2. **Account Type & Contact Hierarchy**:
   - `account_type` must be one of `individual`, `company`, or `address`.
   - Individual partners can belong to a parent company (`parent_id` points to a partner where `account_type == company`).
   - Company partners cannot have a parent company in the standard form UI (`parent_id` is only editable when `account_type == individual`).
   - Address records (`account_type = address`) are treated as auxiliary location records and are filtered out of main partner grid listings.

3. **Bank Account Holder Synchronization**:
   - `BankAccount` enforces that `account_holder_name` always reflects the parent partner's current `name`. Whenever a bank account is created or updated, `$bankAccount->account_holder_name = $bankAccount->partner->name` is executed automatically.

4. **Cascading Deletion Rules**:
   - Deleting a `Partner` cascades to all attached `partners_bank_accounts` and `partners_partner_tag` junction entries.
   - Deleting a `Partner` soft deletes the partner record; child addresses and contacts retain their `parent_id` foreign key.
   - If a `Partner` is referenced by `companies.partner_id`, database `restrictOnDelete` constraints prevent deletion of the partner until the company reference is reallocated.

5. **Ownership & Access Control**:
   - Scoped access evaluates `PartnerPolicy` abilities (`view_partner_partner`, `update_partner_partner`, etc.) combined with `OwnershipScope` evaluating `creator_id` and `user_id`.

## Extension points
[VERIFIED]

1. **`PartnerSchemaRegistry` Extension API**:
   - Downstream plugins register UI contributions using `PartnerSchemaRegistry`:
     - `PartnerSchemaRegistry::form('sales.fields', fn () => [...])`
     - `PartnerSchemaRegistry::form('tabs.append', fn () => [...])`
     - `PartnerSchemaRegistry::infolist('general.after', fn () => [...])`
     - `PartnerSchemaRegistry::infolist('tabs.append', fn () => [...])`
     - `PartnerSchemaRegistry::table('columns', fn () => [...])`
     - `PartnerSchemaRegistry::table('filters.append', fn () => [...])`
     - `PartnerSchemaRegistry::actions('view.header', fn () => [...])`
     - `PartnerSchemaRegistry::actions('edit.header', fn () => [...])`
     - `PartnerSchemaRegistry::eagerLoad([...])`

2. **Dynamic Eloquent Relationships (`resolveRelationUsing`)**:
   - Models in optional domain plugins extend `Partner` dynamically at boot time without modifying core source code (e.g. `AccountServiceProvider` registering `propertyAccountPayable`, `propertyAccountReceivable`, `propertyPaymentTerm`).

3. **Dynamic Contributed Attributes (`HasContributedAttributes`)**:
   - `Partner` uses `HasContributedAttributes`, allowing external modules to define computed and contributed attributes dynamically.

4. **Custom Metadata Attributes (`HasCustomFields`)**:
   - Users and administrators can define runtime custom fields on the `Partner` model via the `fields` module, automatically rendering them in `PartnerResource`.

## Dangerous areas
[VERIFIED]

1. **Test Coverage Explicit Declaration**:
   - **Test files exist**: Yes. 9 dedicated test suites exist under `plugins/webkul/partners/tests/` verifying API v1 CRUD endpoints, company scoping invariants, company isolation, and Filament smoke tests.

2. **Multi-Company Data Leakage Hazard**:
   - Because `Partner` overrides automatic company stamping (`autoAssignsCompany() => false`), partners default to `company_id = null` (shared). If an engineer adds custom queries bypassing `CompanyScope`, partner records could be exposed across organizational boundaries unintentionally.

3. **Foreign Key Cascade on Bank Accounts**:
   - `partners_bank_accounts` defines `cascadeOnDelete()` on `partner_id` and `bank_id`. Hard deleting a partner or a bank permanently cascades to all linked bank account records.

4. **Single Table Inheritance (STI) Data Overlaps**:
   - Because partners, individual contacts, and operational addresses share the same physical `partners_partners` table, raw queries on `partners_partners` must explicitly filter on `account_type` (e.g. `where('account_type', '!=', AccountType::ADDRESS)`) to avoid mixing operational addresses with billing parties.

5. **`BankAccount` Automatic Name Overwrite**:
   - `BankAccount`'s `creating` and `updating` Eloquent hooks automatically overwrite `account_holder_name` with `$partner->name`. Any attempt to set a distinct custom account holder name directly on the bank account will be overwritten on model save.

6. **Hard Deletion Blocking via Referential Constraints**:
   - Foundational tables use `restrictOnDelete` referencing `partners_partners` (e.g. `companies.partner_id`, `partners_partners.country_id`, `partners_partners.state_id`). Attempting to force delete a partner that represents a company's legal identity will throw a `QueryException` and be blocked.

## Change impact
[VERIFIED]

- **Database Schema**: Modifying `partners_partners` impacts almost all ERP plugins (`sales`, `purchases`, `accounts`, `invoices`, `projects`, `website`, `security`, `support`, `recruitments`, `maintenance`, `analytics`).
- **Authorization**: Modifying `PartnerPolicy` or `config/filament-shield.php` alters permissions across both API and Filament interfaces.
- **Dynamic Relations**: Alterations to `Partner` relationship naming affect dynamic extensions registered by `accounts` and other optional plugins.

## Evidence
[VERIFIED]
- `PartnerServiceProvider`: `plugins/webkul/partners/src/PartnerServiceProvider.php:9-43`
- `PartnerPlugin`: `plugins/webkul/partners/src/PartnerPlugin.php:8-48`
- `Partner` Model: `plugins/webkul/partners/src/Models/Partner.php:29-175`
- `Address` Model: `plugins/webkul/partners/src/Models/Address.php:5-8`
- `BankAccount` Model: `plugins/webkul/partners/src/Models/BankAccount.php:14-69`
- `Industry` Model: `plugins/webkul/partners/src/Models/Industry.php:13-43`
- `Tag` Model: `plugins/webkul/partners/src/Models/Tag.php:13-43`
- `Title` Model: `plugins/webkul/partners/src/Models/Title.php:12-42`
- `AccountType` Enum: `plugins/webkul/partners/src/Enums/AccountType.php:7-32`
- `AddressType` Enum: `plugins/webkul/partners/src/Enums/AddressType.php:7-29`
- `PartnerResource`: `plugins/webkul/partners/src/Filament/Resources/PartnerResource.php:18-99`
- `PartnerForm`: `plugins/webkul/partners/src/Filament/Resources/PartnerResource/Schemas/PartnerForm.php:25-293`
- `PartnerInfolist`: `plugins/webkul/partners/src/Filament/Resources/PartnerResource/Schemas/PartnerInfolist.php:21-184`
- `PartnersTable`: `plugins/webkul/partners/src/Filament/Resources/PartnerResource/Tables/PartnersTable.php:38-344`
- `PartnerSchemaRegistry`: `plugins/webkul/partners/src/Filament/Resources/PartnerResource/Support/PartnerSchemaRegistry.php:7-13`
- `PartnerPolicy`: `plugins/webkul/partners/src/Policies/PartnerPolicy.php:9-92`
- `routes/api.php`: `plugins/webkul/partners/routes/api.php:1-24`
- Migrations: `plugins/webkul/partners/database/migrations/`
- Seeders: `plugins/webkul/partners/database/seeders/`
- Tests: `plugins/webkul/partners/tests/`
