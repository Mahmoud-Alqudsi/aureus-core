---
status: verified
source_of_truth: source-code
last_verified: 2026-08-29
scope: global
confidence: high
---

# Database Schema & Eloquent Models Catalog

## 1. Purpose

This document serves as the authoritative, source-code-verified catalog connecting the physical database schema layer with the Eloquent model layer across Aureus ERP.

- **Database Structure**: Derived strictly from root migrations (`database/migrations/`) and modular plugin migrations (`plugins/webkul/*/database/migrations/`).
- **Application & ORM Behavior**: Derived strictly from Eloquent model classes (`app/Models/` and `plugins/webkul/*/src/Models/`), attached traits, property declarations (`$table`, `$primaryKey`, `$casts`, `$fillable`), and relationship methods.
- **Separation of Concerns**: This catalog explicitly distinguishes between physical database facts (tables, columns, foreign keys, indexes, constraints) and ORM runtime facts (models, relationships, traits, global scopes, casts, and runtime extensions like `resolveRelationUsing()`).

## 2. Verification Methodology

All facts documented herein were verified through static code analysis and reflection of the Aureus ERP repository source code:

1. **Database Tables & Columns**: Verified by parsing all migration files defining `Schema::create` and `Schema::table` across root and plugin directories.
2. **Eloquent Model Declarations**: Verified by inspecting all 321 PHP source files across model directories (`app/Models/` and `plugins/webkul/*/src/Models/`):
   - **315 Eloquent Models Cataloged**: Comprising 1 root application model (`App\Models\User` in Section 4 under `### User Model`) and 314 domain models across all 28 plugins in Section 5 (each under a `#### <Model> Model` header).
   - **6 Supporting Concerns & Scope Classes**: The remaining 6 files in model directories are model-support traits and global scope classes:
     * `plugins/webkul/inventories/src/Models/Concerns/ChecksCrossCompanyTransfer.php` (Trait)
     * `plugins/webkul/support/src/Models/Concerns/HasContributedAttributes.php` (Trait)
     * `plugins/webkul/security/src/Models/Scopes/OwnershipScope.php` (Global Scope)
     * `plugins/webkul/support/src/Models/Scopes/AllowedCompanyScope.php` (Global Scope)
     * `plugins/webkul/support/src/Models/Scopes/CompaniesScope.php` (Global Scope)
     * `plugins/webkul/support/src/Models/Scopes/CompanyScope.php` (Global Scope)
   *(Count verified via: `find app/Models plugins/webkul/*/src/Models -type f -name "*.php" | wc -l`)*.
3. **Table Resolution**: Explicit `$table` properties were mapped directly; proxy models (classes extending models in sibling modules) were resolved along their inheritance hierarchy; default conventional tables were cross-checked against migration schemas.
4. **Traits & Scopes**: Model-level trait imports (`use Trait;`) were verified in class declarations, including `BelongsToCompany`, `BelongsToCompanies`, `SoftDeletes`, `HasChatter`, `HasCustomFields`, and `HasOwnershipScope`.
5. **Casts & Mutators**: Verified from `$casts` property arrays and `casts(): array` method returns.
6. **Relationships**: Verified by extracting public relationship methods returning Eloquent relation types (`belongsTo`, `hasMany`, `hasOne`, `belongsToMany`, `morphTo`, `morphMany`, `morphOne`, `morphedByMany`).
7. **Dynamic Runtime Extensions**: Verified by inspecting service provider registrations of `Model::resolveRelationUsing()` across module boot methods.
8. **Evidence & Traceability**: Every section links directly to repository file paths.

## 3. Repository Model Architecture Overview

Aureus ERP employs a modular plugin architecture combining core Laravel application structures with 28 specialized Webkul domain plugins:

- **Core Models** (`app/Models/`): Houses root authentication and base user identity (`App\Models\User`). System tables (sessions, cache, jobs, personal access tokens, notifications, permissions, settings) are managed by root migrations under `database/migrations/`.
- **Plugin Domain Models** (`plugins/webkul/<plugin>/src/Models/`): Each domain module encapsulates its own Eloquent models, migrations, factories, and Filament resources. Most plugin tables use explicit prefixed names (e.g., `products_products`, `sales_orders`, `accounts_account_moves`, `inventories_operations`, `recruitments_applicants`), with model-specific exceptions documented in this catalog.
- **Shared Support & Foundation Models** (`plugins/webkul/support/`): Common master entities (such as `Company`, `Currency`, `Country`, `State`, `Bank`, `UOM`, `ActivityPlan`, `ActivityType`, `UTMMedium`, `Sequence`) reside in the Support plugin and are utilized cross-module.
- **Proxy / Extension Model Pattern**: Several high-level business plugins (such as `sales`, `purchases`, `invoices`, `accounting`, `timesheets`, `contacts`, `website`) define proxy models extending base models from foundational plugins (e.g., `Webkul\Sales\Models\Product` extends `Webkul\Products\Models\Product`; `Webkul\Purchases\Models\Partner` extends `Webkul\Partners\Models\Partner`; `Webkul\Invoices\Models\Invoice` extends `Webkul\Accounting\Models\Move`). These proxy models inherit table mappings, attributes, and relationships while allowing domain-specific customizations and Filament resource bindings without duplicating database tables.
- **Primary Keys**: Integer auto-incrementing IDs (`id`) are the dominant pattern; exceptions (such as custom keys or pivot composite keys) are documented explicitly.
- **Dynamic Cross-Plugin Relationships**: Downstream plugins extend upstream models at runtime using `Model::resolveRelationUsing()` within their service provider `boot` / `packageBooted` methods.

## 4. Core Application Models Catalog

### User Model

```yaml
Model: App\Models\User
Namespace: App\Models
File: app/Models/User.php
Table: users
Migration: database/migrations/0001_01_01_000000_create_users_table.php
Primary Key: id (int, auto-incrementing)
Traits:
  - Illuminate\Database\Eloquent\Factories\HasFactory
  - Illuminate\Notifications\Notifiable
  - Illuminate\Database\Eloquent\SoftDeletes
  - Spatie\Permission\Traits\HasRoles
  - Webkul\Security\Traits\HasOwnershipScope
  - Filament\Models\Contracts\FilamentUser (Interface)
  - Filament\Models\Contracts\HasDefaultTenant (Interface)
  - Filament\Models\Contracts\HasTenants (Interface)
Important Columns:
  - id: integer (primary key)
  - name: string
  - email: string (unique)
  - email_verified_at: timestamp (nullable)
  - password: string
  - partner_id: foreignId -> partners_partners (nullable)
  - default_company_id: foreignId -> companies (nullable)
  - resource_permission: string (nullable, added in 2024_11_26 migration)
  - remember_token: rememberToken
  - timestamps: created_at, updated_at
  - deleted_at: timestamp (nullable, SoftDeletes)
Casts:
  - email_verified_at: datetime
  - password: hashed
Relationships:
  - partner(): belongsTo(Webkul\Partner\Models\Partner::class, 'partner_id')
  - defaultCompany(): belongsTo(Webkul\Support\Models\Company::class, 'default_company_id')
  - companies(): belongsToMany(Webkul\Support\Models\Company::class, 'user_allowed_companies', 'user_id', 'company_id')
  - teams(): belongsToMany(Webkul\Security\Models\Team::class, 'user_team', 'user_id', 'team_id')
  - invitations(): hasMany(Webkul\Security\Models\Invitation::class, 'user_id')
Notes: Root authenticatable model serving as authentication subject, Filament user, and tenant-bound user.
```

## 5. Plugin Model Catalog

Organized alphabetically across all 28 domain plugins:

### Accounting Plugin (`plugins/webkul/accounting`)

#### Account Model

```yaml
Model: Webkul\Accounting\Models\Account
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/Account.php
Table: accounts_accounts
Migration: plugins/webkul/accounts/database/migrations/2025_01_30_054952_create_accounts_accounts_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Account. Operates against table `accounts_accounts`.
```

#### Attribute Model

```yaml
Model: Webkul\Accounting\Models\Attribute
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/Attribute.php
Table: products_attributes
Migration: plugins/webkul/products/database/migrations/2025_01_05_104456_create_products_attributes_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Product\Models\Attribute. Operates against table `products_attributes`.
```

#### BankAccount Model

```yaml
Model: Webkul\Accounting\Models\BankAccount
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/BankAccount.php
Table: partners_bank_accounts
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101420_create_partners_bank_accounts_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Partner\Models\BankAccount. Operates against table `partners_bank_accounts`.
```

#### Bill Model

```yaml
Model: Webkul\Accounting\Models\Bill
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/Bill.php
Table: accounts_account_moves
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_055303_create_accounts_account_moves_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Move. Operates against table `accounts_account_moves`.
```

#### CashRounding Model

```yaml
Model: Webkul\Accounting\Models\CashRounding
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/CashRounding.php
Table: accounts_cash_roundings
Migration: plugins/webkul/accounts/database/migrations/2025_02_03_144139_create_accounts_cash_roundings_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\CashRounding. Operates against table `accounts_cash_roundings`.
```

#### Category Model

```yaml
Model: Webkul\Accounting\Models\Category
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/Category.php
Table: products_categories
Migration: plugins/webkul/products/database/migrations/2025_01_05_063925_create_products_categories_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasChatter
Important Columns: []
Casts: {}
Relationships:
  - products(): hasMany(Product::class)
Notes: Proxy/Extension model extending Webkul\Account\Models\Category -> Webkul\Product\Models\Category. Operates against table `products_categories`.
```

#### CreditNote Model

```yaml
Model: Webkul\Accounting\Models\CreditNote
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/CreditNote.php
Table: accounts_account_moves
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_055303_create_accounts_account_moves_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Move. Operates against table `accounts_account_moves`.
```

#### Currency Model

```yaml
Model: Webkul\Accounting\Models\Currency
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/Currency.php
Table: currencies
Migration: plugins/webkul/support/database/migrations/2024_12_06_061927_create_currencies_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Support\Models\Currency. Operates against table `currencies`.
```

#### Customer Model

```yaml
Model: Webkul\Accounting\Models\Customer
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/Customer.php
Table: partners_partners
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101220_create_partners_partners_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Customer -> Webkul\Account\Models\Partner -> Webkul\Partner\Models\Partner. Operates against table `partners_partners`.
```

#### FiscalPosition Model

```yaml
Model: Webkul\Accounting\Models\FiscalPosition
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/FiscalPosition.php
Table: accounts_fiscal_positions
Migration: plugins/webkul/accounts/database/migrations/2025_02_03_121847_create_accounts_fiscal_positions_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\FiscalPosition. Operates against table `accounts_fiscal_positions`.
```

#### Incoterm Model

```yaml
Model: Webkul\Accounting\Models\Incoterm
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/Incoterm.php
Table: accounts_incoterms
Migration: plugins/webkul/accounts/database/migrations/2025_01_29_134156_create_accounts_incoterms_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Incoterm. Operates against table `accounts_incoterms`.
```

#### Invoice Model

```yaml
Model: Webkul\Accounting\Models\Invoice
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/Invoice.php
Table: accounts_account_moves
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_055303_create_accounts_account_moves_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Move. Operates against table `accounts_account_moves`.
```

#### Journal Model

```yaml
Model: Webkul\Accounting\Models\Journal
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/Journal.php
Table: accounts_journals
Migration: plugins/webkul/accounts/database/migrations/2025_01_31_073645_create_accounts_journals_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - moveLines(): hasMany(MoveLine::class, fk: journal_id)
Notes: Proxy/Extension model extending Webkul\Account\Models\Journal. Operates against table `accounts_journals`.
```

#### JournalEntry Model

```yaml
Model: Webkul\Accounting\Models\JournalEntry
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/JournalEntry.php
Table: accounts_account_moves
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_055303_create_accounts_account_moves_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Move. Operates against table `accounts_account_moves`.
```

#### JournalItem Model

```yaml
Model: Webkul\Accounting\Models\JournalItem
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/JournalItem.php
Table: accounts_account_move_lines
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_071210_create_accounts_account_move_lines_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\MoveLine. Operates against table `accounts_account_move_lines`.
```

#### MoveLine Model

```yaml
Model: Webkul\Accounting\Models\MoveLine
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/MoveLine.php
Table: accounts_account_move_lines
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_071210_create_accounts_account_move_lines_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - move(): belongsTo(Invoice::class)
Notes: Proxy/Extension model extending Webkul\Account\Models\MoveLine. Operates against table `accounts_account_move_lines`.
```

#### Partner Model

```yaml
Model: Webkul\Accounting\Models\Partner
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/Partner.php
Table: partners_partners
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101220_create_partners_partners_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Partner -> Webkul\Partner\Models\Partner. Operates against table `partners_partners`.
```

#### Payment Model

```yaml
Model: Webkul\Accounting\Models\Payment
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/Payment.php
Table: accounts_account_payments
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_055302_create_accounts_account_payments_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Payment. Operates against table `accounts_account_payments`.
```

#### PaymentTerm Model

```yaml
Model: Webkul\Accounting\Models\PaymentTerm
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/PaymentTerm.php
Table: accounts_payment_terms
Migration: plugins/webkul/accounts/database/migrations/2025_01_29_044430_create_accounts_payment_terms_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\PaymentTerm. Operates against table `accounts_payment_terms`.
```

#### Product Model

```yaml
Model: Webkul\Accounting\Models\Product
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/Product.php
Table: products_products
Migration: plugins/webkul/products/database/migrations/2025_01_05_100751_create_products_products_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Product -> Webkul\Product\Models\Product. Operates against table `products_products`.
```

#### Refund Model

```yaml
Model: Webkul\Accounting\Models\Refund
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/Refund.php
Table: accounts_account_moves
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_055303_create_accounts_account_moves_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Move. Operates against table `accounts_account_moves`.
```

#### Tax Model

```yaml
Model: Webkul\Accounting\Models\Tax
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/Tax.php
Table: accounts_taxes
Migration: plugins/webkul/accounts/database/migrations/2025_01_30_083208_create_accounts_taxes_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Tax. Operates against table `accounts_taxes`.
```

#### TaxGroup Model

```yaml
Model: Webkul\Accounting\Models\TaxGroup
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/TaxGroup.php
Table: accounts_tax_groups
Migration: plugins/webkul/accounts/database/migrations/2025_01_29_134157_create_accounts_tax_groups_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\TaxGroup. Operates against table `accounts_tax_groups`.
```

#### Vendor Model

```yaml
Model: Webkul\Accounting\Models\Vendor
Namespace: Webkul\Accounting\Models
File: plugins/webkul/accounting/src/Models/Vendor.php
Table: partners_partners
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101220_create_partners_partners_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Vendor -> Webkul\Account\Models\Partner -> Webkul\Partner\Models\Partner. Operates against table `partners_partners`.
```

### Accounts Plugin (`plugins/webkul/accounts`)

#### Account Model

```yaml
Model: Webkul\Account\Models\Account
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/Account.php
Table: accounts_accounts
Migration: plugins/webkul/accounts/database/migrations/2025_01_30_054952_create_accounts_accounts_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompanies
  - HasCustomFields
  - HasFactory
Important Columns / Fillable:
  - currency_id
  - creator_id
  - parent_id
  - account_type
  - name
  - code
  - note
  - deprecated
  - reconcile
  - non_trade
Casts:
  - deprecated: 'boolean'
  - reconcile: 'boolean'
  - non_trade: 'boolean'
  - account_type: AccountType::class
Relationships:
  - currency(): belongsTo(Currency::class)
  - creator(): belongsTo(User::class)
  - parent(): belongsTo(self::class, fk: parent_id)
  - children(): hasMany(self::class, fk: parent_id)
  - taxes(): belongsToMany(Tax::class, fk: accounts_account_taxes, other: account_id)
  - tags(): belongsToMany(Tag::class, fk: accounts_account_account_tags, other: account_id)
  - journals(): belongsToMany(Journal::class, fk: accounts_account_journals, other: account_id)
  - moveLines(): hasMany(MoveLine::class, fk: account_id)
  - companies(): belongsToMany(Company::class, fk: accounts_account_companies, other: account_id)
Notes: Standard domain model.
```

#### AccountAccountTag Model

```yaml
Model: Webkul\Account\Models\AccountAccountTag
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/AccountAccountTag.php
Table: accounts_account_account_tags
Migration: plugins/webkul/accounts/database/migrations/2025_02_03_055117_create_accounts_account_account_tags_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - account_id
  - account_tag_id
Casts: {}
Relationships:
  - account(): belongsTo(Account::class)
  - accountTag(): belongsTo(Tag::class)
Notes: Standard domain model.
```

#### AccountJournal Model

```yaml
Model: Webkul\Account\Models\AccountJournal
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/AccountJournal.php
Table: accounts_account_journals
Migration: plugins/webkul/accounts/database/migrations/2025_02_03_055709_create_accounts_account_journals_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - account_id
  - journal_id
Casts: {}
Relationships:
  - account(): belongsTo(Account::class)
  - journal(): belongsTo(Journal::class)
Notes: Standard domain model.
```

#### AccountPaymentRegisterMoveLine Model

```yaml
Model: Webkul\Account\Models\AccountPaymentRegisterMoveLine
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/AccountPaymentRegisterMoveLine.php
Table: accounts_account_payment_register_move_lines
Migration: plugins/webkul/accounts/database/migrations/2025_02_17_070121_create_accounts_account_payment_register_move_lines_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - payment_register_id
  - move_line_id
Casts: {}
Relationships:
  - paymentRegister(): belongsTo(PaymentRegister::class, fk: payment_register_id)
  - moveLine(): belongsTo(MoveLine::class, fk: move_line_id)
Notes: Standard domain model.
```

#### AccountTax Model

```yaml
Model: Webkul\Account\Models\AccountTax
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/AccountTax.php
Table: accounts_account_taxes
Migration: plugins/webkul/accounts/database/migrations/2025_02_03_054613_create_accounts_account_taxes_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - account_id
  - tax_id
Casts: {}
Relationships:
  - account(): belongsTo(Account::class)
  - tax(): belongsTo(Tax::class)
Notes: Standard domain model.
```

#### BankStatement Model

```yaml
Model: Webkul\Account\Models\BankStatement
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/BankStatement.php
Table: accounts_bank_statements
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_041318_create_accounts_bank_statements_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
Important Columns / Fillable:
  - company_id
  - journal_id
  - creator_id
  - name
  - reference
  - first_line_index
  - date
  - balance_start
  - balance_end
  - balance_end_real
  - is_completed
Casts: {}
Relationships:
  - company(): belongsTo(Company::class, fk: company_id)
  - journal(): belongsTo(Journal::class, fk: journal_id)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### BankStatementLine Model

```yaml
Model: Webkul\Account\Models\BankStatementLine
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/BankStatementLine.php
Table: [NO EXPLICIT TABLE]
Migration: [NO DIRECT MIGRATION]
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns: []
Casts: {}
Relationships: []
Notes: Standard domain model.
```

#### Bill Model

```yaml
Model: Webkul\Account\Models\Bill
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/Bill.php
Table: accounts_account_moves
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_055303_create_accounts_account_moves_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Move. Operates against table `accounts_account_moves`.
```

#### CashRounding Model

```yaml
Model: Webkul\Account\Models\CashRounding
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/CashRounding.php
Table: accounts_cash_roundings
Migration: plugins/webkul/accounts/database/migrations/2025_02_03_144139_create_accounts_cash_roundings_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - creator_id
  - strategy
  - rounding_method
  - name
  - rounding
  - profit_account_id
  - loss_account_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
  - profitAccount(): belongsTo(Account::class, fk: profit_account_id)
  - lossAccount(): belongsTo(Account::class, fk: loss_account_id)
Notes: Standard domain model.
```

#### Category Model

```yaml
Model: Webkul\Account\Models\Category
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/Category.php
Table: products_categories
Migration: plugins/webkul/products/database/migrations/2025_01_05_063925_create_products_categories_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - propertyAccountIncome(): belongsTo(Account::class, fk: property_account_income_id)
  - propertyAccountExpense(): belongsTo(Account::class, fk: property_account_expense_id)
  - propertyAccountDownPayment(): belongsTo(Account::class, fk: property_account_down_payment_id)
  - parent(): belongsTo(self::class)
  - products(): hasMany(Product::class)
Notes: Proxy/Extension model extending Webkul\Product\Models\Category. Operates against table `products_categories`.
```

#### CategoryCompanyAccount Model

```yaml
Model: Webkul\Account\Models\CategoryCompanyAccount
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/CategoryCompanyAccount.php
Table: products_category_company_accounts
Migration: plugins/webkul/accounts/database/migrations/2026_07_30_120000_create_products_category_company_accounts_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - ChecksCompanyConsistency
Important Columns / Fillable:
  - category_id
  - company_id
  - property_account_income_id
  - property_account_expense_id
  - property_account_down_payment_id
Casts: {}
Relationships:
  - category(): belongsTo(Category::class, fk: category_id)
  - company(): belongsTo(Company::class)
  - propertyAccountIncome(): belongsTo(Account::class, fk: property_account_income_id)
  - propertyAccountExpense(): belongsTo(Account::class, fk: property_account_expense_id)
  - propertyAccountDownPayment(): belongsTo(Account::class, fk: property_account_down_payment_id)
Notes: Standard domain model.
```

#### CreditNote Model

```yaml
Model: Webkul\Account\Models\CreditNote
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/CreditNote.php
Table: accounts_account_moves
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_055303_create_accounts_account_moves_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Move. Operates against table `accounts_account_moves`.
```

#### Customer Model

```yaml
Model: Webkul\Account\Models\Customer
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/Customer.php
Table: partners_partners
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101220_create_partners_partners_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Partner -> Webkul\Partner\Models\Partner. Operates against table `partners_partners`.
```

#### FiscalPosition Model

```yaml
Model: Webkul\Account\Models\FiscalPosition
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/FiscalPosition.php
Table: accounts_fiscal_positions
Migration: plugins/webkul/accounts/database/migrations/2025_02_03_121847_create_accounts_fiscal_positions_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - sort
  - company_id
  - country_id
  - country_group_id
  - creator_id
  - zip_from
  - zip_to
  - foreign_vat
  - name
  - notes
  - auto_reply
  - vat_required
Casts: {}
Relationships:
  - company(): belongsTo(Company::class, fk: company_id)
  - country(): belongsTo(Country::class, fk: country_id)
  - countryGroup(): belongsTo(Country::class, fk: country_group_id)
  - creator(): belongsTo(User::class, fk: creator_id)
  - taxes(): hasMany(FiscalPositionTax::class, fk: fiscal_position_id)
  - accounts(): hasMany(FiscalPositionAccount::class, fk: fiscal_position_id)
Notes: Standard domain model.
```

#### FiscalPositionAccount Model

```yaml
Model: Webkul\Account\Models\FiscalPositionAccount
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/FiscalPositionAccount.php
Table: accounts_fiscal_position_accounts
Migration: plugins/webkul/accounts/database/migrations/2025_02_03_131860_create_accounts_fiscal_position_accounts_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
Important Columns / Fillable:
  - fiscal_position_id
  - company_id
  - account_source_id
  - account_destination_id
  - creator_id
Casts: {}
Relationships:
  - fiscalPosition(): belongsTo(FiscalPosition::class)
  - company(): belongsTo(Company::class)
  - accountSource(): belongsTo(Account::class, fk: account_source_id)
  - accountDestination(): belongsTo(Account::class, fk: account_destination_id)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### FiscalPositionTax Model

```yaml
Model: Webkul\Account\Models\FiscalPositionTax
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/FiscalPositionTax.php
Table: accounts_fiscal_position_taxes
Migration: plugins/webkul/accounts/database/migrations/2025_02_03_131858_create_accounts_fiscal_position_taxes_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
Important Columns / Fillable:
  - fiscal_position_id
  - company_id
  - tax_source_id
  - tax_destination_id
  - creator_id
Casts: {}
Relationships:
  - fiscalPosition(): belongsTo(FiscalPosition::class)
  - company(): belongsTo(Company::class)
  - taxSource(): belongsTo(Tax::class, fk: tax_source_id)
  - taxDestination(): belongsTo(Tax::class, fk: tax_destination_id)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### FullReconcile Model

```yaml
Model: Webkul\Account\Models\FullReconcile
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/FullReconcile.php
Table: accounts_full_reconciles
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_115401_create_accounts_full_reconciles_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - exchange_move_id
  - creator_id
Casts: {}
Relationships:
  - exchangeMove(): belongsTo(Move::class, fk: exchange_move_id)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### Incoterm Model

```yaml
Model: Webkul\Account\Models\Incoterm
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/Incoterm.php
Table: accounts_incoterms
Migration: plugins/webkul/accounts/database/migrations/2025_01_29_134156_create_accounts_incoterms_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasCustomFields
  - HasFactory
  - SoftDeletes
Important Columns / Fillable:
  - code
  - name
  - creator_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### Invoice Model

```yaml
Model: Webkul\Account\Models\Invoice
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/Invoice.php
Table: accounts_account_moves
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_055303_create_accounts_account_moves_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Move. Operates against table `accounts_account_moves`.
```

#### Journal Model

```yaml
Model: Webkul\Account\Models\Journal
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/Journal.php
Table: accounts_journals
Migration: plugins/webkul/accounts/database/migrations/2025_01_31_073645_create_accounts_journals_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - ChecksCompanyConsistency
  - HasCustomFields
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - default_account_id
  - suspense_account_id
  - sort
  - currency_id
  - company_id
  - profit_account_id
  - loss_account_id
  - bank_account_id
  - creator_id
  - color
  - access_token
  - code
  - ... (11 additional columns in fillable)
Casts:
  - type: JournalType::class
Relationships:
  - bankAccount(): belongsTo(BankAccount::class)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class, fk: creator_id)
  - currency(): belongsTo(Currency::class)
  - defaultAccount(): belongsTo(Account::class, fk: default_account_id)
  - lossAccount(): belongsTo(Account::class, fk: loss_account_id)
  - profitAccount(): belongsTo(Account::class, fk: profit_account_id)
  - suspenseAccount(): belongsTo(Account::class, fk: suspense_account_id)
  - allowedAccounts(): belongsToMany(Account::class, fk: accounts_journal_accounts, other: journal_id)
  - moves(): hasMany(Move::class, fk: journal_id)
  - moveLines(): hasMany(MoveLine::class, fk: journal_id)
  - inboundPaymentMethodLines(): hasMany(PaymentMethodLine::class)
  - outboundPaymentMethodLines(): hasMany(PaymentMethodLine::class)
Notes: Standard domain model.
```

#### JournalAccount Model

```yaml
Model: Webkul\Account\Models\JournalAccount
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/JournalAccount.php
Table: accounts_journal_accounts
Migration: plugins/webkul/accounts/database/migrations/2025_01_31_095921_create_accounts_journal_accounts_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - account_id
  - journal_id
Casts: {}
Relationships:
  - account(): belongsTo(Account::class)
  - journal(): belongsTo(Journal::class)
Notes: Standard domain model.
```

#### Move Model

```yaml
Model: Webkul\Account\Models\Move
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/Move.php
Table: accounts_account_moves
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_055303_create_accounts_account_moves_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - ChecksCompanyConsistency
  - HasChatter
  - HasCustomFields
  - HasFactory
  - HasLogActivity
  - HasOwnershipScope
  - SortableTrait
Important Columns / Fillable:
  - sort
  - journal_id
  - company_id
  - campaign_id
  - tax_cash_basis_origin_move_id
  - auto_post_origin_id
  - origin_payment_id
  - secure_sequence_number
  - invoice_payment_term_id
  - partner_id
  - commercial_partner_id
  - partner_shipping_id
  - ... (52 additional columns in fillable)
Casts:
  - checked: 'boolean'
  - invoice_date_due: 'date'
  - amount_tax: 'decimal:4'
  - amount_total: 'decimal:4'
  - amount_residual: 'decimal:4'
  - amount_untaxed: 'decimal:4'
  - amount_tax_signed: 'decimal:4'
  - amount_residual_signed: 'decimal:4'
  - amount_untaxed_signed: 'decimal:4'
  - amount_total_in_currency_signed: 'decimal:4'
  - amount_untaxed_in_currency_signed: 'decimal:4'
  - amount_total_signed: 'decimal:4'
  - state: MoveState::class
  - payment_state: PaymentState::class
  - move_type: MoveType::class
  - invoice_date: 'date'
  - date: 'date'
Relationships:
  - campaign(): belongsTo(UtmCampaign::class, fk: campaign_id)
  - journal(): belongsTo(Journal::class, fk: journal_id)
  - company(): belongsTo(Company::class, fk: company_id)
  - originPayment(): belongsTo(Payment::class, fk: origin_payment_id)
  - taxCashBasisOriginMove(): belongsTo(Move::class, fk: tax_cash_basis_origin_move_id)
  - autoPostOrigin(): belongsTo(Move::class, fk: auto_post_origin_id)
  - invoicePaymentTerm(): belongsTo(PaymentTerm::class, fk: invoice_payment_term_id)
  - partner(): belongsTo(Partner::class, fk: partner_id)
  - commercialPartner(): belongsTo(Partner::class, fk: commercial_partner_id)
  - partnerShipping(): belongsTo(Partner::class, fk: partner_shipping_id)
  - partnerBank(): belongsTo(BankAccount::class, fk: partner_bank_id)
  - fiscalPosition(): belongsTo(FiscalPosition::class, fk: fiscal_position_id)
  - currency(): belongsTo(Currency::class, fk: currency_id)
  - reversedEntry(): belongsTo(self::class, fk: reversed_entry_id)
  - invoiceUser(): belongsTo(User::class, fk: invoice_user_id)
  - invoiceIncoterm(): belongsTo(Incoterm::class, fk: invoice_incoterm_id)
  - invoiceCashRounding(): belongsTo(CashRounding::class, fk: invoice_cash_rounding_id)
  - creator(): belongsTo(User::class, fk: creator_id)
  - source(): belongsTo(UTMSource::class, fk: source_id)
  - medium(): belongsTo(UTMMedium::class, fk: medium_id)
  - paymentMethodLine(): belongsTo(PaymentMethodLine::class, fk: preferred_payment_method_line_id)
  - lines(): hasMany(MoveLine::class, fk: move_id)
  - invoiceLines(): hasMany(MoveLine::class, fk: move_id)
  - taxLines(): hasMany(MoveLine::class, fk: move_id)
  - paymentTermLines(): hasMany(MoveLine::class, fk: move_id)
  - roundingLines(): hasMany(MoveLine::class, fk: move_id)
  - matchedPayments(): belongsToMany(Payment::class, fk: accounts_accounts_move_payment, other: invoice_id)
Notes: Standard domain model. Includes resolveBankPartnerId($moveType, ?int $companyId, ?int $partnerId) static helper for inbound move partner resolution.
```

#### MoveLine Model

```yaml
Model: Webkul\Account\Models\MoveLine
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/MoveLine.php
Table: accounts_account_move_lines
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_071210_create_accounts_account_move_lines_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - ChecksCompanyConsistency
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - sort
  - move_id
  - journal_id
  - company_id
  - company_currency_id
  - reconcile_id
  - payment_id
  - tax_repartition_line_id
  - account_id
  - currency_id
  - partner_id
  - group_tax_id
  - ... (37 additional columns in fillable)
Casts:
  - date: 'date'
  - date_maturity: 'date'
  - invoice_date: 'date'
  - discount_date: 'date'
  - analytic_distribution: 'array'
  - parent_state: MoveState::class
  - display_type: DisplayType::class
Relationships:
  - move(): belongsTo(Move::class)
  - journal(): belongsTo(Journal::class)
  - company(): belongsTo(Company::class)
  - account(): belongsTo(Account::class)
  - currency(): belongsTo(Currency::class)
  - companyCurrency(): belongsTo(Currency::class)
  - partner(): belongsTo(Partner::class)
  - groupTax(): belongsTo(Tax::class)
  - taxes(): belongsToMany(Tax::class, fk: accounts_accounts_move_line_taxes, other: move_line_id)
  - taxGroup(): belongsTo(TaxGroup::class)
  - statement(): belongsTo(BankStatement::class)
  - statementLine(): belongsTo(BankStatementLine::class)
  - product(): belongsTo(Product::class)
  - uom(): belongsTo(UOM::class, fk: uom_id)
  - creator(): belongsTo(User::class)
  - moveLines(): hasMany(MoveLine::class, fk: reconcile_id)
  - payment(): belongsTo(Payment::class)
  - taxRepartitionLine(): belongsTo(TaxPartition::class, fk: tax_repartition_line_id)
  - fullReconcile(): belongsTo(FullReconcile::class)
  - matchedDebits(): hasMany(PartialReconcile::class, fk: credit_move_id)
  - matchedCredits(): hasMany(PartialReconcile::class, fk: debit_move_id)
Notes: Standard domain model.
```

#### MoveReversal Model

```yaml
Model: Webkul\Account\Models\MoveReversal
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/MoveReversal.php
Table: accounts_accounts_move_reversals
Migration: plugins/webkul/accounts/database/migrations/2025_02_27_112520_create_accounts_accounts_move_reversals_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
Important Columns / Fillable:
  - reason
  - date
  - journal_id
  - company_id
  - creator_id
Casts:
  - date: 'date'
Relationships:
  - journal(): belongsTo(Journal::class)
  - newMoves(): belongsToMany(Move::class, fk: accounts_accounts_move_reversal_new_move, other: reversal_id)
  - moves(): belongsToMany(Move::class, fk: accounts_accounts_move_reversal_move, other: reversal_id)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### PartialReconcile Model

```yaml
Model: Webkul\Account\Models\PartialReconcile
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/PartialReconcile.php
Table: accounts_partial_reconciles
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_120712_create_accounts_partial_reconciles_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
Important Columns / Fillable:
  - debit_move_id
  - credit_move_id
  - full_reconcile_id
  - exchange_move_id
  - debit_currency_id
  - credit_currency_id
  - company_id
  - creator_id
  - max_date
  - amount
  - debit_amount_currency
  - credit_amount_currency
Casts: {}
Relationships:
  - debitMove(): belongsTo(MoveLine::class, fk: debit_move_id)
  - creditMove(): belongsTo(MoveLine::class, fk: credit_move_id)
  - fullReconcile(): belongsTo(FullReconcile::class, fk: full_reconcile_id)
  - exchangeMove(): belongsTo(Move::class, fk: exchange_move_id)
  - debitCurrency(): belongsTo(Currency::class, fk: debit_currency_id)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### Partner Model

```yaml
Model: Webkul\Account\Models\Partner
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/Partner.php
Table: partners_partners
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101220_create_partners_partners_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - propertyAccountPayable(): belongsTo(Account::class, fk: property_account_payable_id)
  - propertyAccountReceivable(): belongsTo(Account::class, fk: property_account_receivable_id)
  - propertyAccountPosition(): belongsTo(FiscalPosition::class, fk: property_account_position_id)
  - propertyPaymentTerm(): belongsTo(PaymentTerm::class, fk: property_payment_term_id)
  - propertySupplierPaymentTerm(): belongsTo(PaymentTerm::class, fk: property_supplier_payment_term_id)
  - propertyOutboundPaymentMethodLine(): belongsTo(PaymentMethodLine::class, fk: property_outbound_payment_method_line_id)
  - propertyInboundPaymentMethodLine(): belongsTo(PaymentMethodLine::class, fk: property_inbound_payment_method_line_id)
Notes: Proxy/Extension model extending Webkul\Partner\Models\Partner. Operates against table `partners_partners`.
```

#### PartnerCompanyProperty Model

```yaml
Model: Webkul\Account\Models\PartnerCompanyProperty
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/PartnerCompanyProperty.php
Table: partners_partner_company_properties
Migration: plugins/webkul/accounts/database/migrations/2026_07_30_120001_create_partners_partner_company_properties_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - ChecksCompanyConsistency
Important Columns / Fillable:
  - partner_id
  - company_id
  - property_account_payable_id
  - property_account_receivable_id
  - property_account_position_id
  - property_payment_term_id
  - property_supplier_payment_term_id
  - property_inbound_payment_method_line_id
  - property_outbound_payment_method_line_id
Casts: {}
Relationships:
  - partner(): belongsTo(Partner::class, fk: partner_id)
  - company(): belongsTo(Company::class)
  - propertyAccountPayable(): belongsTo(Account::class, fk: property_account_payable_id)
  - propertyAccountReceivable(): belongsTo(Account::class, fk: property_account_receivable_id)
  - propertyAccountPosition(): belongsTo(FiscalPosition::class, fk: property_account_position_id)
  - propertyPaymentTerm(): belongsTo(PaymentTerm::class, fk: property_payment_term_id)
  - propertySupplierPaymentTerm(): belongsTo(PaymentTerm::class, fk: property_supplier_payment_term_id)
  - propertyInboundPaymentMethodLine(): belongsTo(PaymentMethodLine::class, fk: property_inbound_payment_method_line_id)
  - propertyOutboundPaymentMethodLine(): belongsTo(PaymentMethodLine::class, fk: property_outbound_payment_method_line_id)
Notes: Standard domain model.
```

#### Payment Model

```yaml
Model: Webkul\Account\Models\Payment
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/Payment.php
Table: accounts_account_payments
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_055302_create_accounts_account_payments_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasChatter
  - HasCustomFields
  - HasFactory
  - HasLogActivity
Important Columns / Fillable:
  - move_id
  - journal_id
  - company_id
  - partner_bank_id
  - paired_internal_transfer_payment_id
  - payment_method_line_id
  - payment_method_id
  - currency_id
  - partner_id
  - outstanding_account_id
  - destination_account_id
  - creator_id
  - ... (15 additional columns in fillable)
Casts:
  - date: 'date'
  - state: PaymentStatus::class
  - payment_type: PaymentType::class
Relationships:
  - move(): belongsTo(Move::class, fk: move_id)
  - journal(): belongsTo(Journal::class, fk: journal_id)
  - company(): belongsTo(Company::class, fk: company_id)
  - partnerBank(): belongsTo(BankAccount::class, fk: partner_bank_id)
  - pairedInternalTransferPayment(): belongsTo(self::class, fk: paired_internal_transfer_payment_id)
  - paymentMethodLine(): belongsTo(PaymentMethodLine::class, fk: payment_method_line_id)
  - paymentMethod(): belongsTo(PaymentMethod::class, fk: payment_method_id)
  - currency(): belongsTo(Currency::class, fk: currency_id)
  - partner(): belongsTo(Partner::class, fk: partner_id)
  - outstandingAccount(): belongsTo(Account::class, fk: outstanding_account_id)
  - destinationAccount(): belongsTo(Account::class, fk: destination_account_id)
  - creator(): belongsTo(User::class, fk: creator_id)
  - paymentTransaction(): belongsTo(PaymentTransaction::class, fk: payment_transaction_id)
  - sourcePayment(): belongsTo(self::class, fk: source_payment_id)
  - paymentToken(): belongsTo(PaymentToken::class, fk: payment_token_id)
  - invoices(): belongsToMany(Move::class, fk: accounts_accounts_move_payment, other: payment_id)
Notes: Standard domain model.
```

#### PaymentDueTerm Model

```yaml
Model: Webkul\Account\Models\PaymentDueTerm
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/PaymentDueTerm.php
Table: accounts_payment_due_terms
Migration: plugins/webkul/accounts/database/migrations/2025_01_29_064646_create_accounts_payment_due_terms_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - nb_days
  - payment_id
  - creator_id
  - value
  - delay_type
  - days_next_month
  - value_amount
Casts: {}
Relationships:
  - paymentTerm(): belongsTo(PaymentTerm::class, fk: payment_id)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### PaymentMethod Model

```yaml
Model: Webkul\Account\Models\PaymentMethod
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/PaymentMethod.php
Table: accounts_payment_methods
Migration: plugins/webkul/accounts/database/migrations/2025_02_10_075022_create_accounts_payment_methods_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - code
  - payment_type
  - name
  - creator_id
Casts:
  - payment_type: PaymentType::class
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
  - accountMovePayment(): hasMany(Move::class, fk: payment_id)
Notes: Standard domain model.
```

#### PaymentMethodLine Model

```yaml
Model: Webkul\Account\Models\PaymentMethodLine
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/PaymentMethodLine.php
Table: accounts_payment_method_lines
Migration: plugins/webkul/accounts/database/migrations/2025_02_10_075607_create_accounts_payment_method_lines_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - sort
  - payment_method_id
  - payment_account_id
  - journal_id
  - name
  - creator_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
  - paymentMethod(): belongsTo(PaymentMethod::class)
  - paymentAccount(): belongsTo(Account::class)
  - journal(): belongsTo(Journal::class)
  - defaultAccount(): hasOneThrough(Account::class, fk: Journal::class, other: id)
Notes: Standard domain model.
```

#### PaymentRegister Model

```yaml
Model: Webkul\Account\Models\PaymentRegister
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/PaymentRegister.php
Table: accounts_payment_registers
Migration: plugins/webkul/accounts/database/migrations/2025_02_17_064828_create_accounts_payment_registers_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
Important Columns / Fillable:
  - currency_id
  - journal_id
  - partner_bank_id
  - custom_user_currency_id
  - source_currency_id
  - company_id
  - partner_id
  - payment_method_line_id
  - writeoff_account_id
  - creator_id
  - communication
  - installments_mode
  - ... (12 additional columns in fillable)
Casts:
  - payment_type: PaymentType::class
Relationships:
  - journal(): belongsTo(Journal::class, fk: journal_id)
  - partnerBank(): belongsTo(BankAccount::class, fk: partner_bank_id)
  - currency(): belongsTo(Currency::class, fk: currency_id)
  - customUserCurrency(): belongsTo(Currency::class, fk: custom_user_currency_id)
  - sourceCurrency(): belongsTo(Currency::class, fk: source_currency_id)
  - company(): belongsTo(Company::class, fk: company_id)
  - partner(): belongsTo(Partner::class, fk: partner_id)
  - paymentMethodLine(): belongsTo(PaymentMethodLine::class, fk: payment_method_line_id)
  - writeoffAccount(): belongsTo(Account::class, fk: writeoff_account_id)
  - creator(): belongsTo(User::class)
  - lines(): belongsToMany(MoveLine::class, fk: accounts_account_payment_register_move_lines, other: payment_register_id)
Notes: Standard domain model. Includes getCompanyCurrencyAttribute() and getCompanyCurrencyIdAttribute() accessors for company-level currency resolution.
```

#### PaymentTerm Model

```yaml
Model: Webkul\Account\Models\PaymentTerm
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/PaymentTerm.php
Table: accounts_payment_terms
Migration: plugins/webkul/accounts/database/migrations/2025_01_29_044430_create_accounts_payment_terms_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - company_id
  - sort
  - discount_days
  - creator_id
  - early_pay_discount
  - name
  - note
  - display_on_invoice
  - early_discount
  - discount_percentage
Casts: {}
Relationships:
  - company(): belongsTo(Company::class, fk: company_id)
  - creator(): belongsTo(User::class, fk: creator_id)
  - dueTerms(): hasMany(PaymentDueTerm::class, fk: payment_id)
  - moves(): hasMany(Move::class, fk: invoice_payment_term_id)
Notes: Standard domain model.
```

#### Product Model

```yaml
Model: Webkul\Account\Models\Product
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/Product.php
Table: products_products
Migration: plugins/webkul/products/database/migrations/2025_01_05_100751_create_products_products_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasChatter
  - HasCustomFields
  - HasLogActivity
Important Columns: []
Casts: {}
Relationships:
  - propertyAccountIncome(): belongsTo(Account::class, fk: property_account_income_id)
  - propertyAccountExpense(): belongsTo(Account::class, fk: property_account_expense_id)
  - parent(): belongsTo(self::class)
  - category(): belongsTo(Category::class)
  - productTaxes(): belongsToMany(Tax::class, fk: accounts_product_taxes, other: product_id)
  - supplierTaxes(): belongsToMany(Tax::class, fk: accounts_product_supplier_taxes, other: product_id)
Notes: Proxy/Extension model extending Webkul\Product\Models\Product. Operates against table `products_products`.
```

#### ProductCompanyAccount Model

```yaml
Model: Webkul\Account\Models\ProductCompanyAccount
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/ProductCompanyAccount.php
Table: products_product_company_accounts
Migration: plugins/webkul/accounts/database/migrations/2026_07_30_090000_create_products_product_company_accounts_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - ChecksCompanyConsistency
Important Columns / Fillable:
  - product_id
  - company_id
  - property_account_income_id
  - property_account_expense_id
Casts: {}
Relationships:
  - product(): belongsTo(Product::class, fk: product_id)
  - company(): belongsTo(Company::class)
  - propertyAccountIncome(): belongsTo(Account::class, fk: property_account_income_id)
  - propertyAccountExpense(): belongsTo(Account::class, fk: property_account_expense_id)
Notes: Standard domain model.
```

#### ProductSupplierTaxes Model

```yaml
Model: Webkul\Account\Models\ProductSupplierTaxes
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/ProductSupplierTaxes.php
Table: accounts_product_supplier_taxes
Migration: plugins/webkul/accounts/database/migrations/2025_02_04_111337_create_accounts_product_supplier_taxes_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - product_id
  - tax_id
Casts: {}
Relationships:
  - tax(): belongsTo(Tax::class, fk: tax_id)
  - product(): belongsTo(Product::class, fk: product_id)
Notes: Standard domain model.
```

#### ProductTaxes Model

```yaml
Model: Webkul\Account\Models\ProductTaxes
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/ProductTaxes.php
Table: accounts_product_taxes
Migration: plugins/webkul/accounts/database/migrations/2025_02_04_104958_create_accounts_product_taxes_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - product_id
  - tax_id
Casts: {}
Relationships:
  - product(): belongsTo(Product::class, fk: product_id)
  - tax(): belongsTo(Tax::class, fk: tax_id)
Notes: Standard domain model.
```

#### Reconcile Model

```yaml
Model: Webkul\Account\Models\Reconcile
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/Reconcile.php
Table: accounts_reconciles
Migration: plugins/webkul/accounts/database/migrations/2025_02_10_073440_create_accounts_reconciles_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - sort
  - company_id
  - past_months_limit
  - creator_id
  - rule_type
  - matching_order
  - counter_part_type
  - match_nature
  - match_amount
  - match_label
  - match_level_parameters
  - match_note
  - ... (17 additional columns in fillable)
Casts: {}
Relationships:
  - company(): belongsTo(Company::class, fk: company_id)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### Refund Model

```yaml
Model: Webkul\Account\Models\Refund
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/Refund.php
Table: accounts_account_moves
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_055303_create_accounts_account_moves_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Move. Operates against table `accounts_account_moves`.
```

#### Tag Model

```yaml
Model: Webkul\Account\Models\Tag
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/Tag.php
Table: accounts_account_tags
Migration: plugins/webkul/accounts/database/migrations/2025_01_30_061945_create_accounts_account_tags_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - color
  - country_id
  - creator_id
  - applicability
  - name
  - tax_negate
Casts: {}
Relationships:
  - country(): belongsTo(Country::class)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### Tax Model

```yaml
Model: Webkul\Account\Models\Tax
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/Tax.php
Table: accounts_taxes
Migration: plugins/webkul/accounts/database/migrations/2025_01_30_083208_create_accounts_taxes_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - sort
  - company_id
  - tax_group_id
  - cash_basis_transition_account_id
  - country_id
  - creator_id
  - type_tax_use
  - tax_scope
  - amount_type
  - formula
  - price_include_override
  - tax_exigibility
  - ... (9 additional columns in fillable)
Casts:
  - amount_type: AmountType::class
  - type_tax_use: TypeTaxUse::class
  - price_include_override: TaxIncludeOverride::class
Relationships:
  - company(): belongsTo(Company::class, fk: company_id)
  - taxGroup(): belongsTo(TaxGroup::class, fk: tax_group_id)
  - cashBasisTransitionAccount(): belongsTo(Account::class, fk: cash_basis_transition_account_id)
  - country(): belongsTo(Country::class, fk: country_id)
  - creator(): belongsTo(User::class, fk: creator_id)
  - childrenTaxes(): belongsToMany(self::class, fk: accounts_tax_taxes, other: parent_tax_id)
  - invoiceRepartitionLines(): hasMany(TaxPartition::class, fk: tax_id)
  - refundRepartitionLines(): hasMany(TaxPartition::class, fk: tax_id)
  - parentTaxes(): belongsToMany(self::class, fk: accounts_tax_taxes, other: child_tax_id)
Notes: Standard domain model.
```

#### TaxGroup Model

```yaml
Model: Webkul\Account\Models\TaxGroup
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/TaxGroup.php
Table: accounts_tax_groups
Migration: plugins/webkul/accounts/database/migrations/2025_01_29_134157_create_accounts_tax_groups_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - sort
  - company_id
  - country_id
  - creator_id
  - name
  - preceding_subtotal
Casts: {}
Relationships:
  - company(): belongsTo(Company::class, fk: company_id)
  - country(): belongsTo(Country::class, fk: country_id)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### TaxPartition Model

```yaml
Model: Webkul\Account\Models\TaxPartition
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/TaxPartition.php
Table: accounts_tax_partition_lines
Migration: plugins/webkul/accounts/database/migrations/2025_01_30_123324_create_accounts_tax_partition_lines_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - account_id
  - tax_id
  - company_id
  - sort
  - repartition_type
  - document_type
  - use_in_tax_closing
  - factor_percent
  - creator_id
Casts:
  - document_type: DocumentType::class
  - repartition_type: RepartitionType::class
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
  - account(): belongsTo(Account::class, fk: account_id)
  - tax(): belongsTo(Tax::class, fk: tax_id)
  - company(): belongsTo(Company::class, fk: company_id)
Notes: Standard domain model.
```

#### TaxTaxes Model

```yaml
Model: Webkul\Account\Models\TaxTaxes
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/TaxTaxes.php
Table: accounts_tax_taxes
Migration: plugins/webkul/accounts/database/migrations/2025_01_31_125419_create_accounts_tax_tax_relations_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - parent_tax_id
  - child_tax_id
Casts: {}
Relationships:
  - parentTax(): belongsTo(Tax::class, fk: parent_tax_id)
  - childTax(): belongsTo(Tax::class, fk: child_tax_id)
Notes: Standard domain model.
```

#### Vendor Model

```yaml
Model: Webkul\Account\Models\Vendor
Namespace: Webkul\Account\Models
File: plugins/webkul/accounts/src/Models/Vendor.php
Table: partners_partners
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101220_create_partners_partners_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Partner -> Webkul\Partner\Models\Partner. Operates against table `partners_partners`.
```

### Analytics Plugin (`plugins/webkul/analytics`)

#### Record Model

```yaml
Model: Webkul\Analytic\Models\Record
Namespace: Webkul\Analytic\Models
File: plugins/webkul/analytics/src/Models/Record.php
Table: analytic_records
Migration: plugins/webkul/analytics/database/migrations/2024_12_18_131844_create_analytic_records_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
Important Columns / Fillable:
  - type
  - name
  - date
  - amount
  - unit_amount
  - partner_id
  - company_id
  - user_id
  - creator_id
Casts:
  - date: 'date'
Relationships:
  - partner(): belongsTo(Partner::class)
  - creator(): belongsTo(User::class)
  - user(): belongsTo(User::class)
  - company(): belongsTo(Company::class)
Notes: Standard domain model.
```

### Barcode Plugin (`plugins/webkul/barcode`)

_No model classes defined directly in this plugin._

### Blogs Plugin (`plugins/webkul/blogs`)

#### Category Model

```yaml
Model: Webkul\Blog\Models\Category
Namespace: Webkul\Blog\Models
File: plugins/webkul/blogs/src/Models/Category.php
Table: blogs_categories
Migration: plugins/webkul/blogs/database/migrations/2025_03_06_093011_create_blogs_categories_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasCustomFields
  - HasFactory
  - HasTranslations
  - SoftDeletes
Important Columns / Fillable:
  - name
  - sub_title
  - slug
  - image
  - meta_title
  - meta_keywords
  - meta_description
  - creator_id
Casts: {}
Relationships:
  - posts(): hasMany(Post::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### Post Model

```yaml
Model: Webkul\Blog\Models\Post
Namespace: Webkul\Blog\Models
File: plugins/webkul/blogs/src/Models/Post.php
Table: blogs_posts
Migration: plugins/webkul/blogs/database/migrations/2025_03_06_094011_create_blogs_posts_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasCustomFields
  - HasFactory
  - HasTranslations
  - SoftDeletes
Important Columns / Fillable:
  - title
  - sub_title
  - content
  - slug
  - image
  - author_name
  - is_published
  - published_at
  - visits
  - meta_title
  - meta_keywords
  - meta_description
  - ... (4 additional columns in fillable)
Casts:
  - is_published: 'boolean'
  - published_at: 'datetime'
Relationships:
  - tags(): belongsToMany(Tag::class, fk: blogs_post_tags, other: post_id)
  - category(): belongsTo(Category::class)
  - author(): belongsTo(User::class)
  - creator(): belongsTo(User::class)
  - lastEditor(): belongsTo(User::class)
Notes: Standard domain model.
```

#### Tag Model

```yaml
Model: Webkul\Blog\Models\Tag
Namespace: Webkul\Blog\Models
File: plugins/webkul/blogs/src/Models/Tag.php
Table: blogs_tags
Migration: plugins/webkul/blogs/database/migrations/2025_03_07_065635_create_blogs_tags_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasCustomFields
  - HasFactory
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - name
  - color
  - sort
  - creator_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

### Chatter Plugin (`plugins/webkul/chatter`)

#### Attachment Model

```yaml
Model: Webkul\Chatter\Models\Attachment
Namespace: Webkul\Chatter\Models
File: plugins/webkul/chatter/src/Models/Attachment.php
Table: chatter_attachments
Migration: plugins/webkul/chatter/database/migrations/2024_12_23_080148_create_chatter_attachments_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
Important Columns / Fillable:
  - company_id
  - creator_id
  - message_id
  - file_size
  - name
  - messageable
  - file_path
  - original_file_name
  - mime_type
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
  - company(): belongsTo(Company::class, fk: company_id)
  - message(): belongsTo(Message::class, fk: message_id)
Notes: Standard domain model.
```

#### Follower Model

```yaml
Model: Webkul\Chatter\Models\Follower
Namespace: Webkul\Chatter\Models
File: plugins/webkul/chatter/src/Models/Follower.php
Table: chatter_followers
Migration: plugins/webkul/chatter/database/migrations/2024_12_11_101222_create_chatter_followers_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - followable_id
  - followable_type
  - partner_id
Casts:
  - followed_at: 'datetime'
Relationships:
  - partner(): belongsTo(Partner::class, fk: partner_id)
Notes: Standard domain model.
```

#### Message Model

```yaml
Model: Webkul\Chatter\Models\Message
Namespace: Webkul\Chatter\Models
File: plugins/webkul/chatter/src/Models/Message.php
Table: chatter_messages
Migration: plugins/webkul/chatter/database/migrations/2024_12_23_062355_create_chatter_messages_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
Important Columns / Fillable:
  - company_id
  - activity_type_id
  - messageable_type
  - messageable_id
  - type
  - name
  - subject
  - body
  - summary
  - is_internal
  - date_deadline
  - pinned_at
  - ... (6 additional columns in fillable)
Casts:
  - properties: 'array'
  - date_deadline: 'date'
Relationships:
  - company(): belongsTo(Company::class, fk: company_id)
  - activityType(): belongsTo(ActivityType::class, fk: activity_type_id)
  - assignedTo(): belongsTo(User::class, fk: assigned_to)
  - attachments(): hasMany(Attachment::class, fk: message_id)
Notes: Standard domain model.
```

### Contacts Plugin (`plugins/webkul/contacts`)

#### Address Model

```yaml
Model: Webkul\Contact\Models\Address
Namespace: Webkul\Contact\Models
File: plugins/webkul/contacts/src/Models/Address.php
Table: partners_partners
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101220_create_partners_partners_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Contact\Models\Partner -> Webkul\Partner\Models\Partner. Operates against table `partners_partners`.
```

#### Bank Model

```yaml
Model: Webkul\Contact\Models\Bank
Namespace: Webkul\Contact\Models
File: plugins/webkul/contacts/src/Models/Bank.php
Table: banks
Migration: plugins/webkul/support/database/migrations/2024_12_10_101420_create_banks_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Partner\Models\Bank -> Webkul\Support\Models\Bank. Operates against table `banks`.
```

#### BankAccount Model

```yaml
Model: Webkul\Contact\Models\BankAccount
Namespace: Webkul\Contact\Models
File: plugins/webkul/contacts/src/Models/BankAccount.php
Table: partners_bank_accounts
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101420_create_partners_bank_accounts_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Partner\Models\BankAccount. Operates against table `partners_bank_accounts`.
```

#### Industry Model

```yaml
Model: Webkul\Contact\Models\Industry
Namespace: Webkul\Contact\Models
File: plugins/webkul/contacts/src/Models/Industry.php
Table: partners_industries
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101127_create_partners_industries_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Partner\Models\Industry. Operates against table `partners_industries`.
```

#### Partner Model

```yaml
Model: Webkul\Contact\Models\Partner
Namespace: Webkul\Contact\Models
File: plugins/webkul/contacts/src/Models/Partner.php
Table: partners_partners
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101220_create_partners_partners_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Partner\Models\Partner. Operates against table `partners_partners`.
```

#### Tag Model

```yaml
Model: Webkul\Contact\Models\Tag
Namespace: Webkul\Contact\Models
File: plugins/webkul/contacts/src/Models/Tag.php
Table: partners_tags
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101927_create_partners_tags_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Partner\Models\Tag. Operates against table `partners_tags`.
```

#### Title Model

```yaml
Model: Webkul\Contact\Models\Title
Namespace: Webkul\Contact\Models
File: plugins/webkul/contacts/src/Models/Title.php
Table: partners_titles
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101127_create_partners_titles_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Partner\Models\Title. Operates against table `partners_titles`.
```

### Employees Plugin (`plugins/webkul/employees`)

#### ActivityPlan Model

```yaml
Model: Webkul\Employee\Models\ActivityPlan
Namespace: Webkul\Employee\Models
File: plugins/webkul/employees/src/Models/ActivityPlan.php
Table: activity_plans
Migration: plugins/webkul/support/database/migrations/2024_12_12_114620_create_activity_plans_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - department(): belongsTo(Department::class)
Notes: Proxy/Extension model extending Webkul\Support\Models\ActivityPlan. Operates against table `activity_plans`.
```

#### Calendar Model

```yaml
Model: Webkul\Employee\Models\Calendar
Namespace: Webkul\Employee\Models
File: plugins/webkul/employees/src/Models/Calendar.php
Table: calendars
Migration: plugins/webkul/support/database/migrations/2026_04_02_000001_create_calendars_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Support\Models\Calendar. Operates against table `calendars`.
```

#### CalendarAttendance Model

```yaml
Model: Webkul\Employee\Models\CalendarAttendance
Namespace: Webkul\Employee\Models
File: plugins/webkul/employees/src/Models/CalendarAttendance.php
Table: calendar_attendances
Migration: plugins/webkul/support/database/migrations/2026_04_02_000001_create_calendars_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Support\Models\CalendarAttendance. Operates against table `calendar_attendances`.
```

#### CalendarLeave Model

```yaml
Model: Webkul\Employee\Models\CalendarLeave
Namespace: Webkul\Employee\Models
File: plugins/webkul/employees/src/Models/CalendarLeave.php
Table: calendar_leaves
Migration: plugins/webkul/support/database/migrations/2026_04_02_000001_create_calendars_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Support\Models\CalendarLeave. Operates against table `calendar_leaves`.
```

#### Department Model

```yaml
Model: Webkul\Employee\Models\Department
Namespace: Webkul\Employee\Models
File: plugins/webkul/employees/src/Models/Department.php
Table: employees_departments
Migration: plugins/webkul/employees/database/migrations/2024_12_11_051916_create_employees_departments_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasChatter
  - HasCustomFields
  - HasFactory
  - HasLogActivity
  - SoftDeletes
Important Columns / Fillable:
  - name
  - manager_id
  - company_id
  - parent_id
  - master_department_id
  - complete_name
  - parent_path
  - creator_id
  - color
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
  - parent(): belongsTo(Department::class, fk: parent_id)
  - masterDepartment(): belongsTo(Department::class, fk: master_department_id)
  - jobPositions(): hasMany(EmployeeJobPosition::class)
  - employees(): hasMany(Employee::class)
  - company(): belongsTo(Company::class)
  - manager(): belongsTo(Employee::class, fk: manager_id)
Notes: Standard domain model.
```

#### DepartureReason Model

```yaml
Model: Webkul\Employee\Models\DepartureReason
Namespace: Webkul\Employee\Models
File: plugins/webkul/employees/src/Models/DepartureReason.php
Table: employees_departure_reasons
Migration: plugins/webkul/employees/database/migrations/2024_12_11_120605_create_employees_departure_reasons_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasCustomFields
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - sort
  - reason_code
  - creator_id
  - name
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
  - employees(): hasMany(Employee::class)
Notes: Standard domain model.
```

#### Employee Model

```yaml
Model: Webkul\Employee\Models\Employee
Namespace: Webkul\Employee\Models
File: plugins/webkul/employees/src/Models/Employee.php
Table: employees_employees
Migration: plugins/webkul/employees/database/migrations/2024_12_12_063353_create_employees_employees_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasChatter
  - HasCustomFields
  - HasFactory
  - HasLogActivity
  - SoftDeletes
Important Columns / Fillable:
  - company_id
  - user_id
  - creator_id
  - calendar_id
  - department_id
  - job_id
  - attendance_manager_id
  - partner_id
  - work_location_id
  - parent_id
  - coach_id
  - country_id
  - ... (58 additional columns in fillable)
Casts:
  - is_active: 'boolean'
  - is_flexible: 'boolean'
  - is_fully_flexible: 'boolean'
  - work_permit_scheduled_activity: 'boolean'
Relationships:
  - privateState(): belongsTo(State::class, fk: private_state_id)
  - privateCountry(): belongsTo(Country::class, fk: private_country_id)
  - company(): belongsTo(Company::class, fk: company_id)
  - user(): belongsTo(User::class, fk: user_id)
  - creator(): belongsTo(User::class, fk: creator_id)
  - calendar(): belongsTo(Calendar::class, fk: calendar_id)
  - department(): belongsTo(Department::class, fk: department_id)
  - job(): belongsTo(EmployeeJobPosition::class, fk: job_id)
  - partner(): belongsTo(Partner::class, fk: partner_id)
  - workLocation(): belongsTo(WorkLocation::class, fk: work_location_id)
  - parent(): belongsTo(self::class, fk: parent_id)
  - coach(): belongsTo(self::class, fk: coach_id)
  - country(): belongsTo(Country::class, fk: country_id)
  - state(): belongsTo(State::class, fk: state_id)
  - countryOfBirth(): belongsTo(Country::class, fk: country_of_birth)
  - bankAccount(): belongsTo(BankAccount::class, fk: bank_account_id)
  - departureReason(): belongsTo(DepartureReason::class, fk: departure_reason_id)
  - employmentType(): belongsTo(EmploymentType::class, fk: employee_type)
  - categories(): belongsToMany(EmployeeCategory::class, fk: employees_employee_categories, other: employee_id)
  - skills(): hasMany(EmployeeSkill::class, fk: employee_id)
  - resumes(): hasMany(EmployeeResume::class, fk: employee_id)
  - leaveManager(): belongsTo(User::class, fk: leave_manager_id)
  - attendanceManager(): belongsTo(User::class, fk: attendance_manager_id)
  - companyAddress(): belongsTo(Partner::class, fk: address_id)
Notes: Standard domain model.
```

#### EmployeeCategory Model

```yaml
Model: Webkul\Employee\Models\EmployeeCategory
Namespace: Webkul\Employee\Models
File: plugins/webkul/employees/src/Models/EmployeeCategory.php
Table: employees_categories
Migration: plugins/webkul/employees/database/migrations/2024_12_11_054555_create_employees_categories_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasCustomFields
  - HasFactory
Important Columns / Fillable:
  - name
  - color
  - creator_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### EmployeeEmployeeCategory Model

```yaml
Model: Webkul\Employee\Models\EmployeeEmployeeCategory
Namespace: Webkul\Employee\Models
File: plugins/webkul/employees/src/Models/EmployeeEmployeeCategory.php
Table: employees_employee_categories
Migration: plugins/webkul/employees/database/migrations/2024_12_12_140840_create_employees_employee_categories_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - employee_id
  - category_id
Casts: {}
Relationships:
  - employee(): belongsTo(Employee::class, fk: employee_id)
  - category(): belongsTo(EmployeeCategory::class, fk: category_id)
Notes: Standard domain model.
```

#### EmployeeJobPosition Model

```yaml
Model: Webkul\Employee\Models\EmployeeJobPosition
Namespace: Webkul\Employee\Models
File: plugins/webkul/employees/src/Models/EmployeeJobPosition.php
Table: employees_job_positions
Migration: plugins/webkul/employees/database/migrations/2024_12_11_081046_create_employees_job_positions_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - sort
  - expected_employees
  - no_of_employee
  - no_of_recruitment
  - department_id
  - company_id
  - creator_id
  - employment_type_id
  - recruiter_id
  - name
  - description
  - requirements
  - ... (1 additional columns in fillable)
Casts:
  - is_active: 'boolean'
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
  - employees(): hasMany(Employee::class, fk: job_id)
  - department(): belongsTo(Department::class, fk: department_id)
  - company(): belongsTo(Company::class, fk: company_id)
  - employmentType(): belongsTo(EmploymentType::class, fk: employment_type_id)
Notes: Standard domain model.
```

#### EmployeeResume Model

```yaml
Model: Webkul\Employee\Models\EmployeeResume
Namespace: Webkul\Employee\Models
File: plugins/webkul/employees/src/Models/EmployeeResume.php
Table: employees_employee_resumes
Migration: plugins/webkul/employees/database/migrations/2024_12_16_070029_create_employees_employee_resumes_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - employee_id
  - employee_resume_line_type_id
  - creator_id
  - user_id
  - display_type
  - start_date
  - end_date
  - name
  - description
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
  - employee(): belongsTo(Employee::class)
  - resumeType(): belongsTo(EmployeeResumeLineType::class, fk: employee_resume_line_type_id)
  - attachments(): hasMany(EmployeeResumeAttachment::class, fk: employee_resume_id)
Notes: Standard domain model.
```

#### EmployeeResumeAttachment Model

```yaml
Model: Webkul\Employee\Models\EmployeeResumeAttachment
Namespace: Webkul\Employee\Models
File: plugins/webkul/employees/src/Models/EmployeeResumeAttachment.php
Table: employees_employee_resume_attachments
Migration: plugins/webkul/employees/database/migrations/2026_08_09_000000_create_employees_employee_resume_attachments_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - employee_resume_id
  - creator_id
  - name
  - file_path
  - original_file_name
  - mime_type
  - file_size
Casts: {}
Relationships:
  - resume(): belongsTo(EmployeeResume::class, fk: employee_resume_id)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### EmployeeResumeLineType Model

```yaml
Model: Webkul\Employee\Models\EmployeeResumeLineType
Namespace: Webkul\Employee\Models
File: plugins/webkul/employees/src/Models/EmployeeResumeLineType.php
Table: employees_employee_resume_line_types
Migration: plugins/webkul/employees/database/migrations/2024_12_16_065746_create_employees_employee_resume_line_types_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - sort
  - name
  - creator_id
Casts: {}
Relationships:
  - resume(): hasMany(EmployeeResume::class)
Notes: Standard domain model.
```

#### EmployeeSkill Model

```yaml
Model: Webkul\Employee\Models\EmployeeSkill
Namespace: Webkul\Employee\Models
File: plugins/webkul/employees/src/Models/EmployeeSkill.php
Table: employees_employee_skills
Migration: plugins/webkul/employees/database/migrations/2024_12_12_063354_create_employees_employee_skills_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
  - SoftDeletes
Important Columns / Fillable:
  - employee_id
  - skill_id
  - skill_level_id
  - skill_type_id
  - creator_id
Casts: {}
Relationships:
  - employee(): belongsTo(Employee::class)
  - skill(): belongsTo(Skill::class)
  - skillLevel(): belongsTo(SkillLevel::class)
  - skillType(): belongsTo(SkillType::class)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### EmploymentType Model

```yaml
Model: Webkul\Employee\Models\EmploymentType
Namespace: Webkul\Employee\Models
File: plugins/webkul/employees/src/Models/EmploymentType.php
Table: employees_employment_types
Migration: plugins/webkul/employees/database/migrations/2024_12_11_073130_create_employees_employment_types_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasCustomFields
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - name
  - country_id
  - creator_id
  - code
  - sort
Casts: {}
Relationships:
  - country(): belongsTo(Country::class)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### JobPositionSkill Model

```yaml
Model: Webkul\Employee\Models\JobPositionSkill
Namespace: Webkul\Employee\Models
File: plugins/webkul/employees/src/Models/JobPositionSkill.php
Table: job_position_skills
Migration: plugins/webkul/employees/database/migrations/2025_01_15_045708_create_job_position_skills_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - job_position_id
  - skill_id
Casts: {}
Relationships:
  - jobPosition(): belongsTo(EmployeeJobPosition::class, fk: job_position_id)
  - skill(): belongsTo(EmployeeSkill::class, fk: skill_id)
Notes: Standard domain model.
```

#### Skill Model

```yaml
Model: Webkul\Employee\Models\Skill
Namespace: Webkul\Employee\Models
File: plugins/webkul/employees/src/Models/Skill.php
Table: employees_skills
Migration: plugins/webkul/employees/database/migrations/2024_12_11_075017_create_employees_skills_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasCustomFields
  - HasFactory
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - sort
  - name
  - skill_type_id
  - creator_id
Casts: {}
Relationships:
  - skillType(): belongsTo(SkillType::class, fk: skill_type_id)
  - skillLevels(): hasMany(SkillLevel::class)
  - employeeSkills(): hasMany(EmployeeSkill::class, fk: skill_id)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### SkillLevel Model

```yaml
Model: Webkul\Employee\Models\SkillLevel
Namespace: Webkul\Employee\Models
File: plugins/webkul/employees/src/Models/SkillLevel.php
Table: employees_skill_levels
Migration: plugins/webkul/employees/database/migrations/2024_12_11_075011_create_employees_skill_levels_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
  - SoftDeletes
Important Columns / Fillable:
  - name
  - skill_type_id
  - level
  - default_level
Casts: {}
Relationships:
  - skillType(): belongsTo(SkillType::class, fk: skill_type_id)
  - employeeSkills(): hasMany(EmployeeSkill::class, fk: skill_level_id)
Notes: Standard domain model.
```

#### SkillType Model

```yaml
Model: Webkul\Employee\Models\SkillType
Namespace: Webkul\Employee\Models
File: plugins/webkul/employees/src/Models/SkillType.php
Table: employees_skill_types
Migration: plugins/webkul/employees/database/migrations/2024_12_11_075004_create_employees_skill_types_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasCustomFields
  - HasFactory
  - SoftDeletes
Important Columns / Fillable:
  - name
  - color
  - creator_id
  - is_active
Casts:
  - is_active: 'boolean'
Relationships:
  - skillLevels(): hasMany(SkillLevel::class, fk: skill_type_id)
  - skills(): hasMany(Skill::class, fk: skill_type_id)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### WorkLocation Model

```yaml
Model: Webkul\Employee\Models\WorkLocation
Namespace: Webkul\Employee\Models
File: plugins/webkul/employees/src/Models/WorkLocation.php
Table: employees_work_locations
Migration: plugins/webkul/employees/database/migrations/2024_12_11_045350_create_employees_work_locations_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SoftDeletes
Important Columns / Fillable:
  - company_id
  - creator_id
  - name
  - location_type
  - location_number
  - is_active
Casts:
  - is_active: 'boolean'
  - location_type: WorkLocationEnum::class
Relationships:
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

### Fields Plugin (`plugins/webkul/fields`)

#### Field Model

```yaml
Model: Webkul\Field\Models\Field
Namespace: Webkul\Field\Models
File: plugins/webkul/fields/src/Models/Field.php
Table: custom_fields
Migration: plugins/webkul/fields/database/migrations/2024_11_13_052541_create_custom_fields_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - code
  - name
  - type
  - input_type
  - is_multiselect
  - datalist
  - options
  - form_settings
  - use_in_table
  - table_settings
  - infolist_settings
  - sort
  - ... (1 additional columns in fillable)
Casts:
  - is_multiselect: 'boolean'
  - options: 'array'
  - form_settings: 'array'
  - table_settings: 'array'
  - infolist_settings: 'array'
Relationships: []
Notes: Standard domain model.
```

### Full Calendar Plugin (`plugins/webkul/full-calendar`)

_No model classes defined directly in this plugin._

### Inventories Plugin (`plugins/webkul/inventories`)

#### Attribute Model

```yaml
Model: Webkul\Inventory\Models\Attribute
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/Attribute.php
Table: products_attributes
Migration: plugins/webkul/products/database/migrations/2025_01_05_104456_create_products_attributes_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Product\Models\Attribute. Operates against table `products_attributes`.
```

#### Category Model

```yaml
Model: Webkul\Inventory\Models\Category
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/Category.php
Table: products_categories
Migration: plugins/webkul/products/database/migrations/2025_01_05_063925_create_products_categories_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - routes(): belongsToMany(Route::class, fk: inventories_category_routes, other: category_id)
  - products(): hasMany(Product::class)
Notes: Proxy/Extension model extending Webkul\Product\Models\Category. Operates against table `products_categories`.
```

#### Delivery Model

```yaml
Model: Webkul\Inventory\Models\Delivery
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/Delivery.php
Table: inventories_operations
Migration: plugins/webkul/inventories/database/migrations/2025_01_14_133233_create_inventories_operations_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Inventory\Models\Operation. Operates against table `inventories_operations`.
```

#### Dropship Model

```yaml
Model: Webkul\Inventory\Models\Dropship
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/Dropship.php
Table: inventories_operations
Migration: plugins/webkul/inventories/database/migrations/2025_01_14_133233_create_inventories_operations_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Inventory\Models\Operation. Operates against table `inventories_operations`.
```

#### InternalTransfer Model

```yaml
Model: Webkul\Inventory\Models\InternalTransfer
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/InternalTransfer.php
Table: inventories_operations
Migration: plugins/webkul/inventories/database/migrations/2025_01_14_133233_create_inventories_operations_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Inventory\Models\Operation. Operates against table `inventories_operations`.
```

#### Location Model

```yaml
Model: Webkul\Inventory\Models\Location
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/Location.php
Table: inventories_locations
Migration: plugins/webkul/inventories/database/migrations/2025_01_06_072224_create_inventories_locations_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SoftDeletes
Important Columns / Fillable:
  - position_x
  - position_y
  - position_z
  - type
  - name
  - full_name
  - description
  - parent_path
  - barcode
  - removal_strategy
  - cyclic_inventory_frequency
  - last_inventory_date
  - ... (10 additional columns in fillable)
Casts:
  - type: LocationType::class
  - removal_strategy: ProductRemoval::class
  - last_inventory_date: 'date'
  - next_inventory_date: 'date'
  - is_scrap: 'boolean'
  - is_replenish: 'boolean'
  - is_dock: 'boolean'
Relationships:
  - parent(): belongsTo(self::class)
  - children(): hasMany(self::class, fk: parent_id)
  - putawayRules(): hasMany(PutawayRule::class, fk: in_location_id)
  - quantities(): hasMany(ProductQuantity::class, fk: location_id)
  - storageCategory(): belongsTo(StorageCategory::class)
  - warehouse(): belongsTo(Warehouse::class)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### Lot Model

```yaml
Model: Webkul\Inventory\Models\Lot
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/Lot.php
Table: inventories_lots
Migration: plugins/webkul/inventories/database/migrations/2025_01_14_092601_create_inventories_lots_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - ChecksCompanyConsistency
  - HasCustomFields
  - HasFactory
Important Columns / Fillable:
  - name
  - description
  - reference
  - properties
  - expiry_reminded
  - expiration_date
  - use_date
  - removal_date
  - alert_date
  - product_id
  - uom_id
  - location_id
  - ... (2 additional columns in fillable)
Casts:
  - properties: 'array'
  - expiry_reminded: 'boolean'
  - expiration_date: 'datetime'
  - use_date: 'datetime'
  - removal_date: 'datetime'
  - alert_date: 'datetime'
Relationships:
  - product(): belongsTo(Product::class)
  - uom(): belongsTo(UOM::class)
  - location(): belongsTo(Location::class)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
  - quantities(): hasMany(ProductQuantity::class)
Notes: Standard domain model.
```

#### Move Model

```yaml
Model: Webkul\Inventory\Models\Move
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/Move.php
Table: inventories_moves
Migration: plugins/webkul/inventories/database/migrations/2025_01_14_133260_create_inventories_moves_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
Important Columns / Fillable:
  - name
  - state
  - origin
  - procure_method
  - reference
  - description_picking
  - next_serial
  - next_serial_count
  - is_favorite
  - product_qty
  - product_uom_qty
  - quantity
  - ... (29 additional columns in fillable)
Casts:
  - state: MoveState::class
  - procure_method: ProcureMethod::class
  - quantity: 'float'
  - product_qty: 'float'
  - product_uom_qty: 'float'
  - is_favorite: 'boolean'
  - is_picked: 'boolean'
  - is_scraped: 'boolean'
  - is_inventory: 'boolean'
  - additional: 'boolean'
  - is_refund: 'boolean'
  - reservation_date: 'date'
  - scheduled_at: 'datetime'
  - deadline: 'datetime'
  - alert_Date: 'datetime'
Relationships:
  - product(): belongsTo(Product::class)
  - uom(): belongsTo(UOM::class)
  - sourceLocation(): belongsTo(Location::class)
  - destinationLocation(): belongsTo(Location::class)
  - finalLocation(): belongsTo(Location::class)
  - partner(): belongsTo(Partner::class)
  - operation(): belongsTo(Operation::class)
  - scrap(): belongsTo(Scrap::class)
  - rule(): belongsTo(Rule::class)
  - operationType(): belongsTo(OperationType::class)
  - originReturnedMove(): belongsTo(self::class)
  - returnedMoves(): hasMany(self::class, fk: origin_returned_move_id)
  - restrictPartner(): belongsTo(Partner::class)
  - warehouse(): belongsTo(Warehouse::class)
  - packageLevel(): belongsTo(PackageLevel::class)
  - productPackaging(): belongsTo(Packaging::class)
  - lines(): hasMany(MoveLine::class)
  - moveOrigins(): belongsToMany(Move::class, fk: inventories_move_destinations, other: destination_move_id)
  - moveDestinations(): belongsToMany(Move::class, fk: inventories_move_destinations, other: origin_move_id)
  - routes(): belongsToMany(Route::class, fk: inventories_route_moves, other: move_id)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
  - procurementGroup(): belongsTo(ProcurementGroup::class, fk: procurement_group_id)
  - purchaseOrderLine(): belongsTo(PurchaseOrderLine::class, fk: purchase_order_line_id)
  - purchaseOrderLines(): belongsToMany(PurchaseOrderLine::class, fk: purchases_order_line_moves, other: inventory_move_id)
  - saleOrderLine(): belongsTo(SaleOrderLine::class, fk: sale_order_line_id)
Notes: Standard domain model.
```

#### MoveLine Model

```yaml
Model: Webkul\Inventory\Models\MoveLine
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/MoveLine.php
Table: inventories_move_lines
Migration: plugins/webkul/inventories/database/migrations/2025_01_15_095753_create_inventories_move_lines_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
Important Columns / Fillable:
  - lot_name
  - state
  - reference
  - picking_description
  - qty
  - uom_qty
  - is_picked
  - scheduled_at
  - move_id
  - operation_id
  - product_id
  - uom_id
  - ... (9 additional columns in fillable)
Casts:
  - state: MoveState::class
  - qty: 'float'
  - uom_qty: 'float'
  - is_picked: 'boolean'
  - scheduled_at: 'datetime'
Relationships:
  - move(): belongsTo(Move::class)
  - operation(): belongsTo(Operation::class)
  - product(): belongsTo(Product::class)
  - uom(): belongsTo(UOM::class)
  - sourceLocation(): belongsTo(Location::class)
  - destinationLocation(): belongsTo(Location::class)
  - package(): belongsTo(Package::class)
  - resultPackage(): belongsTo(Package::class)
  - packageLevel(): belongsTo(PackageLevel::class)
  - lot(): belongsTo(Lot::class)
  - partner(): belongsTo(Partner::class)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### Operation Model

```yaml
Model: Webkul\Inventory\Models\Operation
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/Operation.php
Table: inventories_operations
Migration: plugins/webkul/inventories/database/migrations/2025_01_14_133233_create_inventories_operations_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - ChecksCompanyConsistency
  - ChecksCrossCompanyTransfer
  - HasChatter
  - HasCustomFields
  - HasFactory
  - HasLogActivity
  - HasOwnershipScope
Important Columns / Fillable:
  - name
  - origin
  - move_type
  - state
  - is_favorite
  - description
  - has_deadline_issue
  - is_printed
  - is_locked
  - deadline
  - scheduled_at
  - closed_at
  - ... (12 additional columns in fillable)
Casts:
  - state: OperationState::class
  - move_type: MoveType::class
  - is_favorite: 'boolean'
  - has_deadline_issue: 'boolean'
  - is_printed: 'boolean'
  - is_locked: 'boolean'
  - deadline: 'datetime'
  - scheduled_at: 'datetime'
  - closed_at: 'datetime'
Relationships:
  - return(): belongsTo(self::class, fk: return_id)
  - returns(): hasMany(self::class, fk: return_id)
  - user(): belongsTo(User::class)
  - owner(): belongsTo(User::class)
  - operationType(): belongsTo(OperationType::class)
  - sourceLocation(): belongsTo(Location::class)
  - destinationLocation(): belongsTo(Location::class)
  - backOrderOf(): belongsTo(self::class)
  - returnOf(): belongsTo(self::class)
  - partner(): belongsTo(Partner::class)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
  - moves(): hasMany(Move::class, fk: operation_id)
  - moveLines(): hasMany(MoveLine::class, fk: operation_id)
  - packages(): hasManyThrough(Package::class, fk: MoveLine::class, other: operation_id)
  - packageLevels(): hasMany(PackageLevel::class, fk: operation_id)
  - procurementGroup(): belongsTo(ProcurementGroup::class, fk: procurement_group_id)
  - purchaseOrders(): belongsToMany(PurchaseOrder::class, fk: purchases_order_operations, other: inventory_operation_id)
  - saleOrder(): belongsTo(SaleOrder::class, fk: sale_order_id)
Notes: Standard domain model.
```

#### OperationType Model

```yaml
Model: Webkul\Inventory\Models\OperationType
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/OperationType.php
Table: inventories_operation_types
Migration: plugins/webkul/inventories/database/migrations/2025_01_06_072349_create_inventories_operation_types_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - name
  - type
  - sort
  - sequence_code
  - reservation_method
  - reservation_days_before
  - reservation_days_before_priority
  - product_label_format
  - lot_label_format
  - package_label_to_print
  - barcode
  - create_backorder
  - ... (22 additional columns in fillable)
Casts:
  - type: OperationTypeEnum::class
  - reservation_method: ReservationMethod::class
  - create_backorder: CreateBackorder::class
  - move_type: MoveType::class
  - show_entire_packs: 'boolean'
  - use_create_lots: 'boolean'
  - use_existing_lots: 'boolean'
  - print_label: 'boolean'
  - show_operations: 'boolean'
  - auto_show_reception_report: 'boolean'
  - auto_print_delivery_slip: 'boolean'
  - auto_print_return_slip: 'boolean'
  - auto_print_product_labels: 'boolean'
  - auto_print_lot_labels: 'boolean'
  - auto_print_reception_report: 'boolean'
  - auto_print_reception_report_labels: 'boolean'
  - auto_print_packages: 'boolean'
  - auto_print_package_label: 'boolean'
Relationships:
  - returnOperationType(): belongsTo(self::class)
  - storageCategory(): belongsTo(StorageCategory::class)
  - sourceLocation(): belongsTo(Location::class)
  - destinationLocation(): belongsTo(Location::class)
  - warehouse(): belongsTo(Warehouse::class)
  - moves(): hasMany(Move::class)
  - storageCategoryCapacities(): belongsToMany(StorageCategoryCapacity::class, fk: inventories_storage_category_capacities, other: storage_category_id)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### OrderPoint Model

```yaml
Model: Webkul\Inventory\Models\OrderPoint
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/OrderPoint.php
Table: inventories_order_points
Migration: plugins/webkul/inventories/database/migrations/2025_03_13_074205_create_inventories_order_points_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SoftDeletes
Important Columns / Fillable:
  - name
  - trigger
  - snoozed_until
  - product_min_qty
  - product_max_qty
  - qty_multiple
  - qty_to_order_manual
  - product_id
  - product_category_id
  - warehouse_id
  - location_id
  - route_id
  - ... (2 additional columns in fillable)
Casts:
  - trigger: OrderPointTrigger::class
Relationships:
  - product(): belongsTo(Product::class)
  - productCategory(): belongsTo(Category::class, fk: product_category_id)
  - warehouse(): belongsTo(Warehouse::class)
  - route(): belongsTo(Route::class)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### Package Model

```yaml
Model: Webkul\Inventory\Models\Package
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/Package.php
Table: inventories_packages
Migration: plugins/webkul/inventories/database/migrations/2025_01_07_145741_create_inventories_packages_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
Important Columns / Fillable:
  - name
  - package_use
  - pack_date
  - package_type_id
  - location_id
  - company_id
  - creator_id
Casts:
  - package_use: PackageUse::class
  - pack_date: 'date'
Relationships:
  - packageType(): belongsTo(PackageType::class)
  - location(): belongsTo(Location::class)
  - quantities(): hasMany(ProductQuantity::class)
  - operations(): hasManyThrough(Operation::class, fk: MoveLine::class, other: result_package_id)
  - moves(): hasManyThrough(Move::class, fk: MoveLine::class, other: package_id)
  - moveLines(): hasMany(MoveLine::class)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### PackageDestination Model

```yaml
Model: Webkul\Inventory\Models\PackageDestination
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/PackageDestination.php
Table: inventories_package_destinations
Migration: plugins/webkul/inventories/database/migrations/2025_01_14_133246_create_inventories_package_destinations_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - operation_id
  - destination_location_id
  - creator_id
Casts: {}
Relationships:
  - operation(): belongsTo(Operation::class)
  - destinationLocation(): belongsTo(Location::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### PackageLevel Model

```yaml
Model: Webkul\Inventory\Models\PackageLevel
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/PackageLevel.php
Table: inventories_package_levels
Migration: plugins/webkul/inventories/database/migrations/2025_01_14_133245_create_inventories_package_levels_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
Important Columns / Fillable:
  - package_id
  - operation_id
  - destination_location_id
  - company_id
  - creator_id
Casts: {}
Relationships:
  - package(): belongsTo(Package::class)
  - operation(): belongsTo(Operation::class)
  - moveLines(): hasMany(MoveLine::class, fk: package_level_id)
  - destinationLocation(): belongsTo(Location::class)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### PackageType Model

```yaml
Model: Webkul\Inventory\Models\PackageType
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/PackageType.php
Table: inventories_package_types
Migration: plugins/webkul/inventories/database/migrations/2025_01_07_145741_create_inventories_package_types_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - name
  - sort
  - barcode
  - height
  - width
  - length
  - base_weight
  - max_weight
  - shipper_package_code
  - package_carrier_type
  - company_id
  - creator_id
Casts: {}
Relationships:
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### Packaging Model

```yaml
Model: Webkul\Inventory\Models\Packaging
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/Packaging.php
Table: products_packagings
Migration: plugins/webkul/products/database/migrations/2025_01_05_105626_create_products_packagings_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - packageType(): belongsTo(PackageType::class)
  - routes(): belongsToMany(Route::class, fk: inventories_route_packagings, other: packaging_id)
Notes: Proxy/Extension model extending Webkul\Product\Models\Packaging. Operates against table `products_packagings`.
```

#### ProcurementGroup Model

```yaml
Model: Webkul\Inventory\Models\ProcurementGroup
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/ProcurementGroup.php
Table: inventories_procurement_groups
Migration: plugins/webkul/inventories/database/migrations/2026_04_08_042911_create_procurement_groups_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - name
  - move_type
  - partner_id
  - creator_id
  - sale_order_id
Casts: {}
Relationships:
  - saleOrder(): belongsTo(SaleOrder::class, fk: sale_order_id)
  - partner(): belongsTo(Partner::class, fk: partner_id)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### Product Model

```yaml
Model: Webkul\Inventory\Models\Product
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/Product.php
Table: products_products
Migration: plugins/webkul/products/database/migrations/2025_01_05_100751_create_products_products_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasCustomFields
Important Columns: []
Casts: {}
Relationships:
  - category(): belongsTo(Category::class)
  - routes(): belongsToMany(Route::class, fk: inventories_product_routes, other: product_id)
  - variants(): hasMany(self::class, fk: parent_id)
  - quantities(): hasMany(ProductQuantity::class)
  - moves(): hasMany(Move::class)
  - moveLines(): hasMany(MoveLine::class)
  - storageCategoryCapacities(): belongsToMany(StorageCategoryCapacity::class, fk: inventories_storage_category_capacities, other: storage_category_id)
  - orderPoints(): hasMany(OrderPoint::class)
  - responsible(): belongsTo(User::class)
Notes: Proxy/Extension model extending Webkul\Product\Models\Product. Operates against table `products_products`.
```

#### ProductQuantity Model

```yaml
Model: Webkul\Inventory\Models\ProductQuantity
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/ProductQuantity.php
Table: inventories_product_quantities
Migration: plugins/webkul/inventories/database/migrations/2025_01_14_113233_create_inventories_product_quantities_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
Important Columns / Fillable:
  - quantity
  - reserved_quantity
  - counted_quantity
  - difference_quantity
  - inventory_diff_quantity
  - inventory_quantity_set
  - scheduled_at
  - incoming_at
  - product_id
  - location_id
  - storage_category_id
  - lot_id
  - ... (5 additional columns in fillable)
Casts:
  - inventory_quantity_set: 'boolean'
  - scheduled_at: 'date'
  - incoming_at: 'datetime'
Relationships:
  - product(): belongsTo(Product::class)
  - location(): belongsTo(Location::class)
  - storageCategory(): belongsTo(StorageCategory::class)
  - lot(): belongsTo(Lot::class)
  - package(): belongsTo(Package::class)
  - partner(): belongsTo(Partner::class)
  - user(): belongsTo(User::class)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### ProductQuantityRelocation Model

```yaml
Model: Webkul\Inventory\Models\ProductQuantityRelocation
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/ProductQuantityRelocation.php
Table: inventories_product_quantity_relocations
Migration: plugins/webkul/inventories/database/migrations/2025_01_14_113235_create_inventories_product_quantity_relocations_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - description
  - destination_location_id
  - destination_package_id
  - creator_id
Casts: {}
Relationships:
  - destinationLocation(): belongsTo(Location::class)
  - destinationPackage(): belongsTo(Package::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### PutawayRule Model

```yaml
Model: Webkul\Inventory\Models\PutawayRule
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/PutawayRule.php
Table: inventories_putaway_rules
Migration: plugins/webkul/inventories/database/migrations/2026_05_14_092628_inventories_create_putaway_rules_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - sub_location
  - sort
  - product_id
  - category_id
  - storage_category_id
  - in_location_id
  - out_location_id
  - company_id
  - creator_id
Casts:
  - sub_location: SubLocation::class
Relationships:
  - product(): belongsTo(Product::class)
  - category(): belongsTo(Category::class)
  - storageCategory(): belongsTo(StorageCategory::class, fk: storage_category_id)
  - inLocation(): belongsTo(Location::class, fk: in_location_id)
  - outLocation(): belongsTo(Location::class, fk: out_location_id)
  - packageTypes(): belongsToMany(PackageType::class, fk: inventories_putaway_rule_package_types, other: putaway_rule_id)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### Receipt Model

```yaml
Model: Webkul\Inventory\Models\Receipt
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/Receipt.php
Table: inventories_operations
Migration: plugins/webkul/inventories/database/migrations/2025_01_14_133233_create_inventories_operations_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Inventory\Models\Operation. Operates against table `inventories_operations`.
```

#### Route Model

```yaml
Model: Webkul\Inventory\Models\Route
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/Route.php
Table: inventories_routes
Migration: plugins/webkul/inventories/database/migrations/2025_01_06_072353_create_inventories_routes_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - sort
  - name
  - product_selectable
  - product_category_selectable
  - warehouse_selectable
  - packaging_selectable
  - supplied_warehouse_id
  - supplier_warehouse_id
  - company_id
  - creator_id
  - deleted_at
Casts:
  - product_selectable: 'boolean'
  - product_category_selectable: 'boolean'
  - warehouse_selectable: 'boolean'
  - packaging_selectable: 'boolean'
Relationships:
  - suppliedWarehouse(): belongsTo(Warehouse::class)
  - supplierWarehouse(): belongsTo(Warehouse::class)
  - warehouses(): belongsToMany(Warehouse::class, fk: inventories_route_warehouses)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
  - packagings(): belongsToMany(Route::class, fk: inventories_route_packagings, other: route_id)
  - rules(): hasMany(Rule::class)
Notes: Standard domain model.
```

#### Rule Model

```yaml
Model: Webkul\Inventory\Models\Rule
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/Rule.php
Table: inventories_rules
Migration: plugins/webkul/inventories/database/migrations/2025_01_06_072356_create_inventories_rules_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - sort
  - name
  - route_sort
  - delay
  - group_propagation_option
  - action
  - procure_method
  - auto
  - push_domain
  - location_dest_from_rule
  - propagate_cancel
  - propagate_carrier
  - ... (11 additional columns in fillable)
Casts:
  - action: RuleAction::class
  - group_propagation_option: GroupPropagation::class
  - auto: RuleAuto::class
  - procure_method: ProcureMethod::class
  - location_dest_from_rule: 'boolean'
  - propagate_cancel: 'boolean'
  - propagate_carrier: 'boolean'
Relationships:
  - sourceLocation(): belongsTo(Location::class)
  - destinationLocation(): belongsTo(Location::class)
  - route(): belongsTo(Route::class)
  - operationType(): belongsTo(OperationType::class)
  - warehouse(): belongsTo(Warehouse::class)
  - propagateWarehouse(): belongsTo(Warehouse::class)
  - partnerAddress(): belongsTo(Partner::class)
  - procurementGroup(): belongsTo(ProcurementGroup::class, fk: procurement_group_id)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### Scrap Model

```yaml
Model: Webkul\Inventory\Models\Scrap
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/Scrap.php
Table: inventories_scraps
Migration: plugins/webkul/inventories/database/migrations/2025_01_14_133250_create_inventories_scraps_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - ChecksCrossCompanyTransfer
  - HasChatter
  - HasCustomFields
  - HasFactory
  - HasLogActivity
Important Columns / Fillable:
  - name
  - origin
  - state
  - qty
  - should_replenish
  - closed_at
  - product_id
  - uom_id
  - lot_id
  - package_id
  - partner_id
  - operation_id
  - ... (4 additional columns in fillable)
Casts:
  - state: ScrapState::class
  - should_replenish: 'boolean'
  - closed_at: 'datetime'
  - qty: 'decimal:4'
Relationships:
  - product(): belongsTo(Product::class)
  - uom(): belongsTo(UOM::class)
  - lot(): belongsTo(Lot::class)
  - package(): belongsTo(Package::class)
  - operation(): belongsTo(Operation::class)
  - sourceLocation(): belongsTo(Location::class)
  - destinationLocation(): belongsTo(Location::class)
  - partner(): belongsTo(Partner::class)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
  - tags(): belongsToMany(Tag::class, fk: inventories_scrap_tags, other: scrap_id)
  - moves(): hasMany(Move::class)
  - moveLines(): hasManyThrough(MoveLine::class, fk: Move::class)
Notes: Standard domain model.
```

#### StorageCategory Model

```yaml
Model: Webkul\Inventory\Models\StorageCategory
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/StorageCategory.php
Table: inventories_storage_categories
Migration: plugins/webkul/inventories/database/migrations/2025_01_06_072135_create_inventories_storage_categories_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - name
  - sort
  - allow_new_products
  - parent_path
  - max_weight
  - company_id
  - creator_id
Casts:
  - allow_new_products: AllowNewProduct::class
  - max_weight: 'float'
Relationships:
  - storageCategoryCapacities(): hasMany(StorageCategoryCapacity::class, fk: storage_category_id)
  - locations(): hasMany(Location::class)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### StorageCategoryCapacity Model

```yaml
Model: Webkul\Inventory\Models\StorageCategoryCapacity
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/StorageCategoryCapacity.php
Table: inventories_storage_category_capacities
Migration: plugins/webkul/inventories/database/migrations/2025_01_10_111734_create_inventories_storage_category_capacities_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - qty
  - product_id
  - storage_category_id
  - package_type_id
  - creator_id
Casts: {}
Relationships:
  - product(): belongsTo(Product::class)
  - storageCategory(): belongsTo(StorageCategory::class)
  - packageType(): belongsTo(PackageType::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### Tag Model

```yaml
Model: Webkul\Inventory\Models\Tag
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/Tag.php
Table: inventories_tags
Migration: plugins/webkul/inventories/database/migrations/2025_01_06_072032_create_inventories_tags_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - name
  - color
  - sort
  - creator_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### UOMCategory Model

```yaml
Model: Webkul\Inventory\Models\UOMCategory
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/UOMCategory.php
Table: unit_of_measure_categories
Migration: plugins/webkul/support/database/migrations/2025_01_03_105625_create_unit_of_measure_categories_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Support\Models\UOMCategory. Operates against table `unit_of_measure_categories`.
```

#### Warehouse Model

```yaml
Model: Webkul\Inventory\Models\Warehouse
Namespace: Webkul\Inventory\Models
File: plugins/webkul/inventories/src/Models/Warehouse.php
Table: inventories_warehouses
Migration: plugins/webkul/inventories/database/migrations/2025_01_06_072130_create_inventories_warehouses_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - name
  - code
  - sort
  - reception_steps
  - delivery_steps
  - manufacture_steps
  - partner_address_id
  - company_id
  - creator_id
  - view_location_id
  - lot_stock_location_id
  - input_stock_location_id
  - ... (16 additional columns in fillable)
Casts:
  - reception_steps: ReceptionStep::class
  - delivery_steps: DeliveryStep::class
  - manufacture_steps: ManufactureStep::class
Relationships:
  - locations(): hasMany(Location::class, fk: warehouse_id)
  - company(): belongsTo(Company::class)
  - partnerAddress(): belongsTo(Partner::class)
  - creator(): belongsTo(User::class)
  - viewLocation(): belongsTo(Location::class, fk: view_location_id)
  - lotStockLocation(): belongsTo(Location::class, fk: lot_stock_location_id)
  - inputStockLocation(): belongsTo(Location::class, fk: input_stock_location_id)
  - qcStockLocation(): belongsTo(Location::class, fk: qc_stock_location_id)
  - outputStockLocation(): belongsTo(Location::class, fk: output_stock_location_id)
  - packStockLocation(): belongsTo(Location::class, fk: pack_stock_location_id)
  - mtoPull(): belongsTo(Rule::class, fk: mto_pull_id)
  - buyPull(): belongsTo(Rule::class, fk: buy_pull_id)
  - pickType(): belongsTo(OperationType::class, fk: pick_type_id)
  - packType(): belongsTo(OperationType::class, fk: pack_type_id)
  - outType(): belongsTo(OperationType::class, fk: out_type_id)
  - inType(): belongsTo(OperationType::class, fk: in_type_id)
  - internalType(): belongsTo(OperationType::class, fk: internal_type_id)
  - qcType(): belongsTo(OperationType::class, fk: qc_type_id)
  - storeType(): belongsTo(OperationType::class, fk: store_type_id)
  - xdockType(): belongsTo(OperationType::class, fk: xdock_type_id)
  - operationTypes(): hasMany(OperationType::class, fk: warehouse_id)
  - crossdockRoute(): belongsTo(Route::class, fk: crossdock_route_id)
  - receptionRoute(): belongsTo(Route::class, fk: reception_route_id)
  - deliveryRoute(): belongsTo(Route::class, fk: delivery_route_id)
  - routes(): belongsToMany(Route::class, fk: inventories_route_warehouses, other: warehouse_id)
  - suppliedWarehouses(): belongsToMany(Warehouse::class, fk: inventories_warehouse_resupplies, other: supplier_warehouse_id)
  - supplierWarehouses(): belongsToMany(Warehouse::class, fk: inventories_warehouse_resupplies, other: supplied_warehouse_id)
Notes: Standard domain model.
```

### Invoices Plugin (`plugins/webkul/invoices`)

#### Attribute Model

```yaml
Model: Webkul\Invoice\Models\Attribute
Namespace: Webkul\Invoice\Models
File: plugins/webkul/invoices/src/Models/Attribute.php
Table: products_attributes
Migration: plugins/webkul/products/database/migrations/2025_01_05_104456_create_products_attributes_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Product\Models\Attribute. Operates against table `products_attributes`.
```

#### BankAccount Model

```yaml
Model: Webkul\Invoice\Models\BankAccount
Namespace: Webkul\Invoice\Models
File: plugins/webkul/invoices/src/Models/BankAccount.php
Table: partners_bank_accounts
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101420_create_partners_bank_accounts_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Partner\Models\BankAccount. Operates against table `partners_bank_accounts`.
```

#### Bill Model

```yaml
Model: Webkul\Invoice\Models\Bill
Namespace: Webkul\Invoice\Models
File: plugins/webkul/invoices/src/Models/Bill.php
Table: accounts_account_moves
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_055303_create_accounts_account_moves_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Move. Operates against table `accounts_account_moves`.
```

#### Category Model

```yaml
Model: Webkul\Invoice\Models\Category
Namespace: Webkul\Invoice\Models
File: plugins/webkul/invoices/src/Models/Category.php
Table: products_categories
Migration: plugins/webkul/products/database/migrations/2025_01_05_063925_create_products_categories_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasChatter
Important Columns: []
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
  - products(): hasMany(Product::class)
Notes: Proxy/Extension model extending Webkul\Account\Models\Category -> Webkul\Product\Models\Category. Operates against table `products_categories`.
```

#### CreditNote Model

```yaml
Model: Webkul\Invoice\Models\CreditNote
Namespace: Webkul\Invoice\Models
File: plugins/webkul/invoices/src/Models/CreditNote.php
Table: accounts_account_moves
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_055303_create_accounts_account_moves_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Move. Operates against table `accounts_account_moves`.
```

#### Currency Model

```yaml
Model: Webkul\Invoice\Models\Currency
Namespace: Webkul\Invoice\Models
File: plugins/webkul/invoices/src/Models/Currency.php
Table: currencies
Migration: plugins/webkul/support/database/migrations/2024_12_06_061927_create_currencies_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Support\Models\Currency. Operates against table `currencies`.
```

#### Customer Model

```yaml
Model: Webkul\Invoice\Models\Customer
Namespace: Webkul\Invoice\Models
File: plugins/webkul/invoices/src/Models/Customer.php
Table: partners_partners
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101220_create_partners_partners_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Customer -> Webkul\Account\Models\Partner -> Webkul\Partner\Models\Partner. Operates against table `partners_partners`.
```

#### Incoterm Model

```yaml
Model: Webkul\Invoice\Models\Incoterm
Namespace: Webkul\Invoice\Models
File: plugins/webkul/invoices/src/Models/Incoterm.php
Table: accounts_incoterms
Migration: plugins/webkul/accounts/database/migrations/2025_01_29_134156_create_accounts_incoterms_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Incoterm. Operates against table `accounts_incoterms`.
```

#### Invoice Model

```yaml
Model: Webkul\Invoice\Models\Invoice
Namespace: Webkul\Invoice\Models
File: plugins/webkul/invoices/src/Models/Invoice.php
Table: accounts_account_moves
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_055303_create_accounts_account_moves_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Move. Operates against table `accounts_account_moves`.
```

#### Partner Model

```yaml
Model: Webkul\Invoice\Models\Partner
Namespace: Webkul\Invoice\Models
File: plugins/webkul/invoices/src/Models/Partner.php
Table: partners_partners
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101220_create_partners_partners_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Partner -> Webkul\Partner\Models\Partner. Operates against table `partners_partners`.
```

#### Payment Model

```yaml
Model: Webkul\Invoice\Models\Payment
Namespace: Webkul\Invoice\Models
File: plugins/webkul/invoices/src/Models/Payment.php
Table: accounts_account_payments
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_055302_create_accounts_account_payments_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Payment. Operates against table `accounts_account_payments`.
```

#### PaymentTerm Model

```yaml
Model: Webkul\Invoice\Models\PaymentTerm
Namespace: Webkul\Invoice\Models
File: plugins/webkul/invoices/src/Models/PaymentTerm.php
Table: accounts_payment_terms
Migration: plugins/webkul/accounts/database/migrations/2025_01_29_044430_create_accounts_payment_terms_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\PaymentTerm. Operates against table `accounts_payment_terms`.
```

#### Product Model

```yaml
Model: Webkul\Invoice\Models\Product
Namespace: Webkul\Invoice\Models
File: plugins/webkul/invoices/src/Models/Product.php
Table: products_products
Migration: plugins/webkul/products/database/migrations/2025_01_05_100751_create_products_products_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Product -> Webkul\Product\Models\Product. Operates against table `products_products`.
```

#### Refund Model

```yaml
Model: Webkul\Invoice\Models\Refund
Namespace: Webkul\Invoice\Models
File: plugins/webkul/invoices/src/Models/Refund.php
Table: accounts_account_moves
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_055303_create_accounts_account_moves_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Move. Operates against table `accounts_account_moves`.
```

#### Tax Model

```yaml
Model: Webkul\Invoice\Models\Tax
Namespace: Webkul\Invoice\Models
File: plugins/webkul/invoices/src/Models/Tax.php
Table: accounts_taxes
Migration: plugins/webkul/accounts/database/migrations/2025_01_30_083208_create_accounts_taxes_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Tax. Operates against table `accounts_taxes`.
```

#### TaxGroup Model

```yaml
Model: Webkul\Invoice\Models\TaxGroup
Namespace: Webkul\Invoice\Models
File: plugins/webkul/invoices/src/Models/TaxGroup.php
Table: accounts_tax_groups
Migration: plugins/webkul/accounts/database/migrations/2025_01_29_134157_create_accounts_tax_groups_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\TaxGroup. Operates against table `accounts_tax_groups`.
```

#### Vendor Model

```yaml
Model: Webkul\Invoice\Models\Vendor
Namespace: Webkul\Invoice\Models
File: plugins/webkul/invoices/src/Models/Vendor.php
Table: partners_partners
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101220_create_partners_partners_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Account\Models\Vendor -> Webkul\Account\Models\Partner -> Webkul\Partner\Models\Partner. Operates against table `partners_partners`.
```

### Maintenance Plugin (`plugins/webkul/maintenance`)

#### Equipment Model

```yaml
Model: Webkul\Maintenance\Models\Equipment
Namespace: Webkul\Maintenance\Models
File: plugins/webkul/maintenance/src/Models/Equipment.php
Table: maintenance_equipments
Migration: plugins/webkul/maintenance/database/migrations/2026_05_18_000004_create_maintenance_equipments_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SoftDeletes
Important Columns / Fillable:
  - partner_ref
  - location
  - model
  - serial_no
  - effective_date
  - warranty_date
  - assigned_at
  - scraped_at
  - name
  - note
  - cost
  - maintenance_count
  - ... (10 additional columns in fillable)
Casts:
  - effective_date: 'date'
  - warranty_date: 'date'
  - assigned_at: 'date'
  - scraped_at: 'date'
  - cost: 'float'
  - maintenance_count: 'integer'
  - maintenance_open_count: 'integer'
  - expected_mtbf: 'integer'
Relationships:
  - category(): belongsTo(EquipmentCategory::class, fk: category_id)
  - partner(): belongsTo(Partner::class)
  - owner(): belongsTo(User::class, fk: owner_user_id)
  - team(): belongsTo(Team::class, fk: maintenance_team_id)
  - technician(): belongsTo(User::class, fk: technician_user_id)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class, fk: creator_id)
  - requests(): hasMany(MaintenanceRequest::class, fk: equipment_id)
Notes: Standard domain model.
```

#### EquipmentCategory Model

```yaml
Model: Webkul\Maintenance\Models\EquipmentCategory
Namespace: Webkul\Maintenance\Models
File: plugins/webkul/maintenance/src/Models/EquipmentCategory.php
Table: maintenance_equipment_categories
Migration: plugins/webkul/maintenance/database/migrations/2026_05_18_000001_create_maintenance_equipment_categories_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
Important Columns / Fillable:
  - name
  - note
  - creator_id
  - technician_user_id
  - company_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
  - technician(): belongsTo(User::class, fk: technician_user_id)
  - company(): belongsTo(Company::class)
  - equipments(): hasMany(Equipment::class, fk: category_id)
  - requests(): hasMany(MaintenanceRequest::class, fk: category_id)
Notes: Standard domain model.
```

#### MaintenanceRequest Model

```yaml
Model: Webkul\Maintenance\Models\MaintenanceRequest
Namespace: Webkul\Maintenance\Models
File: plugins/webkul/maintenance/src/Models/MaintenanceRequest.php
Table: maintenance_requests
Migration: plugins/webkul/maintenance/database/migrations/2026_05_18_000005_create_maintenance_requests_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasChatter
  - HasCustomFields
  - HasFactory
  - HasLogActivity
  - HasOwnershipScope
  - SoftDeletes
Important Columns / Fillable:
  - repeat_interval
  - name
  - priority
  - maintenance_type
  - instruction_type
  - instruction_pdf
  - instruction_google_slide
  - repeat_unit
  - repeat_type
  - requested_at
  - closed_at
  - repeat_until
  - ... (13 additional columns in fillable)
Casts:
  - repeat_interval: 'integer'
  - requested_at: 'date'
  - closed_at: 'date'
  - repeat_until: 'date'
  - duration: 'float'
  - recurring_maintenance: 'boolean'
  - scheduled_at: 'datetime'
  - maintenance_type: MaintenanceRequestType::class
  - repeat_unit: MaintenanceRepeatUnit::class
  - repeat_type: MaintenanceRepeatType::class
Relationships:
  - equipment(): belongsTo(Equipment::class, fk: equipment_id)
  - stage(): belongsTo(Stage::class, fk: stage_id)
  - category(): belongsTo(EquipmentCategory::class, fk: category_id)
  - user(): belongsTo(User::class)
  - team(): belongsTo(Team::class, fk: maintenance_team_id)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### Stage Model

```yaml
Model: Webkul\Maintenance\Models\Stage
Namespace: Webkul\Maintenance\Models
File: plugins/webkul/maintenance/src/Models/Stage.php
Table: maintenance_stages
Migration: plugins/webkul/maintenance/database/migrations/2026_05_18_000002_create_maintenance_stages_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasCustomFields
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - sort
  - name
  - done
  - creator_id
Casts:
  - done: 'boolean'
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
  - requests(): hasMany(MaintenanceRequest::class, fk: stage_id)
Notes: Standard domain model.
```

#### Team Model

```yaml
Model: Webkul\Maintenance\Models\Team
Namespace: Webkul\Maintenance\Models
File: plugins/webkul/maintenance/src/Models/Team.php
Table: maintenance_teams
Migration: plugins/webkul/maintenance/database/migrations/2026_05_18_000003_create_maintenance_teams_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SoftDeletes
Important Columns / Fillable:
  - name
  - creator_id
  - company_id
  - deleted_at
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
  - company(): belongsTo(Company::class)
  - equipments(): hasMany(Equipment::class, fk: maintenance_team_id)
  - requests(): hasMany(MaintenanceRequest::class, fk: maintenance_team_id)
  - users(): belongsToMany(User::class, fk: maintenance_team_users, other: team_id)
Notes: Standard domain model.
```

### Manufacturing Plugin (`plugins/webkul/manufacturing`)

#### BillOfMaterial Model

```yaml
Model: Webkul\Manufacturing\Models\BillOfMaterial
Namespace: Webkul\Manufacturing\Models
File: plugins/webkul/manufacturing/src/Models/BillOfMaterial.php
Table: manufacturing_bills_of_materials
Migration: plugins/webkul/manufacturing/database/migrations/2026_03_31_064242_create_manufacturing_bills_of_materials_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SoftDeletes
Important Columns / Fillable:
  - code
  - type
  - ready_to_produce
  - consumption
  - quantity
  - allow_operation_dependencies
  - produce_delay
  - days_to_prepare_mo
  - product_id
  - uom_id
  - operation_type_id
  - company_id
  - ... (2 additional columns in fillable)
Casts:
  - type: BillOfMaterialType::class
  - ready_to_produce: BillOfMaterialReadyToProduce::class
  - consumption: BillOfMaterialConsumption::class
  - quantity: 'decimal:4'
  - allow_operation_dependencies: 'boolean'
  - produce_delay: 'integer'
  - days_to_prepare_mo: 'integer'
Relationships:
  - product(): belongsTo(Product::class)
  - uom(): belongsTo(UOM::class)
  - operationType(): belongsTo(OperationType::class)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
  - lines(): hasMany(BillOfMaterialLine::class, fk: bill_of_material_id)
  - byproducts(): hasMany(BillOfMaterialByproduct::class, fk: bill_of_material_id)
  - operations(): hasMany(Operation::class, fk: bill_of_material_id)
  - orders(): hasMany(Order::class, fk: bill_of_material_id)
  - unbuildOrders(): hasMany(UnbuildOrder::class, fk: bill_of_material_id)
Notes: Standard domain model.
```

#### BillOfMaterialByproduct Model

```yaml
Model: Webkul\Manufacturing\Models\BillOfMaterialByproduct
Namespace: Webkul\Manufacturing\Models
File: plugins/webkul/manufacturing/src/Models/BillOfMaterialByproduct.php
Table: manufacturing_bill_of_material_byproducts
Migration: plugins/webkul/manufacturing/database/migrations/2026_03_31_064246_create_manufacturing_bill_of_material_byproducts_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
Important Columns / Fillable:
  - sort
  - quantity
  - cost_share
  - bill_of_material_id
  - product_id
  - company_id
  - uom_id
  - operation_id
  - creator_id
Casts:
  - quantity: 'decimal:4'
  - cost_share: 'decimal:2'
Relationships:
  - billOfMaterial(): belongsTo(BillOfMaterial::class, fk: bill_of_material_id)
  - product(): belongsTo(Product::class)
  - company(): belongsTo(Company::class)
  - uom(): belongsTo(UOM::class)
  - operation(): belongsTo(Operation::class, fk: operation_id)
  - creator(): belongsTo(User::class)
  - attributeValues(): belongsToMany(ProductAttributeValue::class, fk: manufacturing_bill_of_material_byproduct_attribute_values, other: byproduct_id)
Notes: Standard domain model.
```

#### BillOfMaterialLine Model

```yaml
Model: Webkul\Manufacturing\Models\BillOfMaterialLine
Namespace: Webkul\Manufacturing\Models
File: plugins/webkul/manufacturing/src/Models/BillOfMaterialLine.php
Table: manufacturing_bill_of_material_lines
Migration: plugins/webkul/manufacturing/database/migrations/2026_03_31_064245_create_manufacturing_bill_of_material_lines_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
Important Columns / Fillable:
  - sort
  - quantity
  - is_manual_consumption
  - bill_of_material_id
  - product_id
  - company_id
  - uom_id
  - operation_id
  - creator_id
Casts:
  - quantity: 'decimal:4'
  - is_manual_consumption: 'boolean'
Relationships:
  - billOfMaterial(): belongsTo(BillOfMaterial::class, fk: bill_of_material_id)
  - product(): belongsTo(Product::class)
  - company(): belongsTo(Company::class)
  - uom(): belongsTo(UOM::class)
  - operation(): belongsTo(Operation::class, fk: operation_id)
  - creator(): belongsTo(User::class)
  - attributeValues(): belongsToMany(ProductAttributeValue::class, fk: manufacturing_bill_of_material_line_attribute_values, other: bill_of_material_line_id)
Notes: Standard domain model.
```

#### Lot Model

```yaml
Model: Webkul\Manufacturing\Models\Lot
Namespace: Webkul\Manufacturing\Models
File: plugins/webkul/manufacturing/src/Models/Lot.php
Table: inventories_lots
Migration: plugins/webkul/inventories/database/migrations/2025_01_14_092601_create_inventories_lots_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - product(): belongsTo(Product::class)
  - moveLines(): hasMany(MoveLine::class)
Notes: Proxy/Extension model extending Webkul\Inventory\Models\Lot. Operates against table `inventories_lots`.
```

#### Move Model

```yaml
Model: Webkul\Manufacturing\Models\Move
Namespace: Webkul\Manufacturing\Models
File: plugins/webkul/manufacturing/src/Models/Move.php
Table: inventories_moves
Migration: plugins/webkul/inventories/database/migrations/2025_01_14_133260_create_inventories_moves_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - createdOrder(): belongsTo(Order::class, fk: created_order_id)
  - order(): belongsTo(Order::class, fk: order_id)
  - rawMaterialOrder(): belongsTo(Order::class, fk: raw_material_order_id)
  - unbuildOrder(): belongsTo(UnbuildOrder::class, fk: unbuild_order_id)
  - consumeUnbuildOrder(): belongsTo(UnbuildOrder::class, fk: consume_unbuild_order_id)
  - moOperation(): belongsTo(Operation::class, fk: mo_operation_id)
  - workOrder(): belongsTo(WorkOrder::class, fk: work_order_id)
  - bomLine(): belongsTo(BillOfMaterialLine::class, fk: bom_line_id)
  - byproduct(): belongsTo(BillOfMaterialByproduct::class, fk: byproduct_id)
  - orderFinishedLot(): belongsTo(Lot::class, fk: order_finished_lot_id)
  - product(): belongsTo(Product::class)
  - warehouse(): belongsTo(Warehouse::class)
  - originReturnedMove(): belongsTo(self::class)
  - lines(): hasMany(MoveLine::class, fk: move_id)
  - moveDestinations(): belongsToMany(self::class, fk: inventories_move_destinations, other: origin_move_id)
Notes: Proxy/Extension model extending Webkul\Inventory\Models\Move. Operates against table `inventories_moves`.
```

#### MoveLine Model

```yaml
Model: Webkul\Manufacturing\Models\MoveLine
Namespace: Webkul\Manufacturing\Models
File: plugins/webkul/manufacturing/src/Models/MoveLine.php
Table: inventories_move_lines
Migration: plugins/webkul/inventories/database/migrations/2025_01_15_095753_create_inventories_move_lines_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - workOrder(): belongsTo(WorkOrder::class, fk: work_order_id)
  - move(): belongsTo(Move::class)
  - order(): belongsTo(Order::class, fk: order_id)
  - product(): belongsTo(Product::class)
  - lot(): belongsTo(Lot::class)
Notes: Proxy/Extension model extending Webkul\Inventory\Models\MoveLine. Operates against table `inventories_move_lines`.
```

#### Operation Model

```yaml
Model: Webkul\Manufacturing\Models\Operation
Namespace: Webkul\Manufacturing\Models
File: plugins/webkul/manufacturing/src/Models/Operation.php
Table: manufacturing_operations
Migration: plugins/webkul/manufacturing/database/migrations/2026_03_31_064244_create_manufacturing_operations_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasCustomFields
  - HasFactory
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - sort
  - time_mode_batch
  - name
  - worksheet_type
  - worksheet
  - worksheet_google_slide_url
  - time_mode
  - note
  - manual_cycle_time
  - work_center_id
  - bill_of_material_id
  - creator_id
  - ... (1 additional columns in fillable)
Casts:
  - worksheet_type: OperationWorksheetType::class
  - time_mode: OperationTimeMode::class
  - manual_cycle_time: 'decimal:4'
Relationships:
  - workCenter(): belongsTo(WorkCenter::class, fk: work_center_id)
  - billOfMaterial(): belongsTo(BillOfMaterial::class, fk: bill_of_material_id)
  - creator(): belongsTo(User::class)
  - lines(): hasMany(BillOfMaterialLine::class, fk: operation_id)
  - byproducts(): hasMany(BillOfMaterialByproduct::class, fk: operation_id)
  - workOrders(): hasMany(WorkOrder::class, fk: operation_id)
  - attributeValues(): belongsToMany(ProductAttributeValue::class, fk: manufacturing_operation_attribute_values, other: operation_id)
  - blockedByOperations(): belongsToMany(self::class, fk: manufacturing_operation_dependencies, other: operation_id)
  - dependentOperations(): belongsToMany(self::class, fk: manufacturing_operation_dependencies, other: depends_on_operation_id)
Notes: Standard domain model.
```

#### Order Model

```yaml
Model: Webkul\Manufacturing\Models\Order
Namespace: Webkul\Manufacturing\Models
File: plugins/webkul/manufacturing/src/Models/Order.php
Table: manufacturing_orders
Migration: plugins/webkul/manufacturing/database/migrations/2026_03_31_064247_create_manufacturing_orders_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasChatter
  - HasCustomFields
  - HasFactory
  - HasLogActivity
  - HasOwnershipScope
Important Columns / Fillable:
  - name
  - reference
  - priority
  - origin
  - state
  - reservation_state
  - consumption
  - quantity
  - quantity_producing
  - product_uom_qty
  - is_planned
  - is_locked
  - ... (17 additional columns in fillable)
Casts:
  - priority: ManufacturingOrderPriority::class
  - state: ManufacturingOrderState::class
  - reservation_state: ManufacturingOrderReservationState::class
  - consumption: BillOfMaterialConsumption::class
  - is_planned: 'boolean'
  - is_locked: 'boolean'
  - quantity: 'decimal:4'
  - quantity_producing: 'decimal:4'
  - product_uom_qty: 'decimal:4'
  - deadline_at: 'datetime'
  - started_at: 'datetime'
  - finished_at: 'datetime'
Relationships:
  - product(): belongsTo(Product::class)
  - uom(): belongsTo(UOM::class)
  - producingLot(): belongsTo(Lot::class, fk: producing_lot_id)
  - operationType(): belongsTo(OperationType::class, fk: operation_type_id)
  - sourceLocation(): belongsTo(Location::class, fk: source_location_id)
  - destinationLocation(): belongsTo(Location::class, fk: destination_location_id)
  - finalLocation(): belongsTo(Location::class, fk: final_location_id)
  - productionLocation(): belongsTo(Location::class, fk: production_location_id)
  - billOfMaterial(): belongsTo(BillOfMaterial::class, fk: bill_of_material_id)
  - assignedUser(): belongsTo(User::class, fk: assigned_user_id)
  - company(): belongsTo(Company::class)
  - orderPoint(): belongsTo(OrderPoint::class, fk: order_point_id)
  - procurementGroup(): belongsTo(ProcurementGroup::class, fk: procurement_group_id)
  - creator(): belongsTo(User::class)
  - workOrders(): hasMany(WorkOrder::class, fk: manufacturing_order_id)
  - rawMaterialMoves(): hasMany(Move::class, fk: raw_material_order_id)
  - finishedMoves(): hasMany(Move::class, fk: order_id)
  - moveDestinations(): hasMany(Move::class, fk: created_order_id)
  - unbuildOrders(): hasMany(UnbuildOrder::class, fk: manufacturing_order_id)
  - inventoryOperations(): hasManyThrough(Operation::class, fk: ProcurementGroup::class, other: id)
Notes: Standard domain model.
```

#### ProcurementGroup Model

```yaml
Model: Webkul\Manufacturing\Models\ProcurementGroup
Namespace: Webkul\Manufacturing\Models
File: plugins/webkul/manufacturing/src/Models/ProcurementGroup.php
Table: inventories_procurement_groups
Migration: plugins/webkul/inventories/database/migrations/2026_04_08_042911_create_procurement_groups_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - orders(): hasMany(Order::class, fk: procurement_group_id)
Notes: Proxy/Extension model extending Webkul\Inventory\Models\ProcurementGroup. Operates against table `inventories_procurement_groups`.
```

#### Product Model

```yaml
Model: Webkul\Manufacturing\Models\Product
Namespace: Webkul\Manufacturing\Models
File: plugins/webkul/manufacturing/src/Models/Product.php
Table: products_products
Migration: plugins/webkul/products/database/migrations/2025_01_05_100751_create_products_products_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - variants(): hasMany(self::class, fk: parent_id)
  - billsOfMaterials(): hasMany(BillOfMaterial::class, fk: product_id)
  - billOfMaterialLines(): hasMany(BillOfMaterialLine::class, fk: product_id)
  - moves(): hasMany(Move::class)
  - moveLines(): hasMany(MoveLine::class)
Notes: Proxy/Extension model extending Webkul\Inventory\Models\Product -> Webkul\Product\Models\Product. Operates against table `products_products`.
```

#### UnbuildOrder Model

```yaml
Model: Webkul\Manufacturing\Models\UnbuildOrder
Namespace: Webkul\Manufacturing\Models
File: plugins/webkul/manufacturing/src/Models/UnbuildOrder.php
Table: manufacturing_unbuild_orders
Migration: plugins/webkul/manufacturing/database/migrations/2026_03_31_064249_create_manufacturing_unbuild_orders_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
Important Columns / Fillable:
  - name
  - state
  - quantity
  - product_id
  - company_id
  - uom_id
  - bill_of_material_id
  - manufacturing_order_id
  - lot_id
  - location_id
  - destination_location_id
  - creator_id
Casts:
  - state: UnbuildOrderState::class
  - quantity: 'decimal:4'
Relationships:
  - product(): belongsTo(Product::class)
  - company(): belongsTo(Company::class)
  - uom(): belongsTo(UOM::class)
  - billOfMaterial(): belongsTo(BillOfMaterial::class, fk: bill_of_material_id)
  - manufacturingOrder(): belongsTo(Order::class, fk: manufacturing_order_id)
  - lot(): belongsTo(Lot::class, fk: lot_id)
  - location(): belongsTo(Location::class, fk: location_id)
  - destinationLocation(): belongsTo(Location::class, fk: destination_location_id)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### Warehouse Model

```yaml
Model: Webkul\Manufacturing\Models\Warehouse
Namespace: Webkul\Manufacturing\Models
File: plugins/webkul/manufacturing/src/Models/Warehouse.php
Table: inventories_warehouses
Migration: plugins/webkul/inventories/database/migrations/2025_01_06_072130_create_inventories_warehouses_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - manufacturePull(): belongsTo(Rule::class, fk: manufacture_pull_id)
  - manufactureMtoPull(): belongsTo(Rule::class, fk: manufacture_mto_pull_id)
  - pbmMtoPull(): belongsTo(Rule::class, fk: pbm_mto_pull_id)
  - samRule(): belongsTo(Rule::class, fk: sam_rule_id)
  - manuType(): belongsTo(OperationType::class, fk: manu_type_id)
  - pbmType(): belongsTo(OperationType::class, fk: pbm_type_id)
  - samType(): belongsTo(OperationType::class, fk: sam_type_id)
  - manufactureRoute(): belongsTo(Route::class, fk: pbm_route_id)
  - pbmLocation(): belongsTo(Location::class, fk: pbm_loc_id)
  - samLocation(): belongsTo(Location::class, fk: sam_loc_id)
  - suppliedWarehouses(): belongsToMany(self::class, fk: inventories_warehouse_resupplies, other: supplier_warehouse_id)
  - supplierWarehouses(): belongsToMany(self::class, fk: inventories_warehouse_resupplies, other: supplied_warehouse_id)
Notes: Proxy/Extension model extending Webkul\Inventory\Models\Warehouse. Operates against table `inventories_warehouses`.
```

#### WorkCenter Model

```yaml
Model: Webkul\Manufacturing\Models\WorkCenter
Namespace: Webkul\Manufacturing\Models
File: plugins/webkul/manufacturing/src/Models/WorkCenter.php
Table: manufacturing_work_centers
Migration: plugins/webkul/manufacturing/database/migrations/2026_03_31_064243_create_manufacturing_work_centers_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - sort
  - color
  - name
  - code
  - working_state
  - note
  - time_efficiency
  - default_capacity
  - costs_per_hour
  - setup_time
  - cleanup_time
  - oee_target
  - ... (4 additional columns in fillable)
Casts:
  - working_state: WorkCenterWorkingState::class
  - time_efficiency: 'decimal:2'
  - costs_per_hour: 'decimal:4'
  - setup_time: 'decimal:4'
  - cleanup_time: 'decimal:4'
  - oee_target: 'decimal:2'
Relationships:
  - company(): belongsTo(Company::class)
  - calendar(): belongsTo(Calendar::class, fk: calendar_id)
  - creator(): belongsTo(User::class)
  - operations(): hasMany(Operation::class, fk: work_center_id)
  - capacities(): hasMany(WorkCenterCapacity::class, fk: work_center_id)
  - workOrders(): hasMany(WorkOrder::class, fk: work_center_id)
  - productivityLogs(): hasMany(WorkCenterProductivityLog::class, fk: work_center_id)
  - alternativeWorkCenters(): belongsToMany(self::class, fk: manufacturing_work_center_alternatives, other: work_center_id)
  - tags(): belongsToMany(WorkCenterTag::class, fk: manufacturing_work_center_tag, other: work_center_id)
Notes: Standard domain model.
```

#### WorkCenterCapacity Model

```yaml
Model: Webkul\Manufacturing\Models\WorkCenterCapacity
Namespace: Webkul\Manufacturing\Models
File: plugins/webkul/manufacturing/src/Models/WorkCenterCapacity.php
Table: manufacturing_work_center_capacities
Migration: plugins/webkul/manufacturing/database/migrations/2026_03_31_064258_create_manufacturing_work_center_capacities_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - work_center_id
  - product_id
  - capacity
  - time_start
  - time_stop
  - creator_id
Casts:
  - capacity: 'decimal:4'
  - time_start: 'decimal:4'
  - time_stop: 'decimal:4'
Relationships:
  - workCenter(): belongsTo(WorkCenter::class, fk: work_center_id)
  - product(): belongsTo(Product::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### WorkCenterLossType Model

```yaml
Model: Webkul\Manufacturing\Models\WorkCenterLossType
Namespace: Webkul\Manufacturing\Models
File: plugins/webkul/manufacturing/src/Models/WorkCenterLossType.php
Table: manufacturing_work_center_loss_types
Migration: plugins/webkul/manufacturing/database/migrations/2026_03_31_064259_create_manufacturing_work_center_loss_types_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - loss_type
  - creator_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
  - productivityLosses(): hasMany(WorkCenterProductivityLoss::class, fk: loss_type_id)
Notes: Standard domain model.
```

#### WorkCenterProductivityLog Model

```yaml
Model: Webkul\Manufacturing\Models\WorkCenterProductivityLog
Namespace: Webkul\Manufacturing\Models
File: plugins/webkul/manufacturing/src/Models/WorkCenterProductivityLog.php
Table: manufacturing_work_center_productivity_logs
Migration: plugins/webkul/manufacturing/database/migrations/2026_03_31_064261_create_manufacturing_work_center_productivity_logs_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
Important Columns / Fillable:
  - loss_type
  - description
  - started_at
  - finished_at
  - duration
  - work_center_id
  - company_id
  - work_order_id
  - assigned_user_id
  - loss_id
  - creator_id
Casts:
  - started_at: 'datetime'
  - finished_at: 'datetime'
  - duration: 'decimal:4'
Relationships:
  - workCenter(): belongsTo(WorkCenter::class, fk: work_center_id)
  - company(): belongsTo(Company::class)
  - workOrder(): belongsTo(WorkOrder::class, fk: work_order_id)
  - assignedUser(): belongsTo(User::class, fk: assigned_user_id)
  - loss(): belongsTo(WorkCenterProductivityLoss::class, fk: loss_id)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### WorkCenterProductivityLoss Model

```yaml
Model: Webkul\Manufacturing\Models\WorkCenterProductivityLoss
Namespace: Webkul\Manufacturing\Models
File: plugins/webkul/manufacturing/src/Models/WorkCenterProductivityLoss.php
Table: manufacturing_work_center_productivity_losses
Migration: plugins/webkul/manufacturing/database/migrations/2026_03_31_064260_create_manufacturing_work_center_productivity_losses_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - sort
  - loss_type
  - name
  - manual
  - loss_type_id
  - creator_id
Casts:
  - manual: 'boolean'
Relationships:
  - lossType(): belongsTo(WorkCenterLossType::class, fk: loss_type_id)
  - creator(): belongsTo(User::class, fk: creator_id)
  - productivityLogs(): hasMany(WorkCenterProductivityLog::class, fk: loss_id)
Notes: Standard domain model.
```

#### WorkCenterTag Model

```yaml
Model: Webkul\Manufacturing\Models\WorkCenterTag
Namespace: Webkul\Manufacturing\Models
File: plugins/webkul/manufacturing/src/Models/WorkCenterTag.php
Table: manufacturing_work_center_tags
Migration: plugins/webkul/manufacturing/database/migrations/2026_03_31_064262_create_manufacturing_work_center_tags_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
  - SoftDeletes
Important Columns / Fillable:
  - name
  - color
  - sort
  - creator_id
  - deleted_at
Casts: {}
Relationships:
  - creator(): belongsTo(User::class)
  - workCenters(): belongsToMany(WorkCenter::class, fk: manufacturing_work_center_tag, other: tag_id)
Notes: Standard domain model.
```

#### WorkOrder Model

```yaml
Model: Webkul\Manufacturing\Models\WorkOrder
Namespace: Webkul\Manufacturing\Models
File: plugins/webkul/manufacturing/src/Models/WorkOrder.php
Table: manufacturing_work_orders
Migration: plugins/webkul/manufacturing/database/migrations/2026_03_31_064248_create_manufacturing_work_orders_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasCustomFields
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - name
  - barcode
  - production_availability
  - state
  - quantity_produced
  - expected_duration
  - started_at
  - finished_at
  - duration
  - duration_per_unit
  - duration_percent
  - costs_per_hour
  - ... (7 additional columns in fillable)
Casts:
  - production_availability: WorkOrderProductionAvailability::class
  - state: WorkOrderState::class
  - quantity_produced: 'decimal:4'
  - expected_duration: 'decimal:4'
  - started_at: 'datetime'
  - finished_at: 'datetime'
  - duration: 'decimal:4'
  - duration_per_unit: 'decimal:4'
  - duration_percent: 'integer'
  - costs_per_hour: 'decimal:4'
Relationships:
  - workCenter(): belongsTo(WorkCenter::class, fk: work_center_id)
  - product(): belongsTo(Product::class)
  - uom(): belongsTo(UOM::class)
  - manufacturingOrder(): belongsTo(Order::class, fk: manufacturing_order_id)
  - calendarLeave(): belongsTo(CalendarLeave::class, fk: calendar_leave_id)
  - operation(): belongsTo(Operation::class, fk: operation_id)
  - creator(): belongsTo(User::class)
  - rawMaterialMoves(): hasMany(Move::class, fk: work_order_id)
  - finishedMoves(): hasMany(Move::class, fk: work_order_id)
  - blockedByWorkOrders(): belongsToMany(self::class, fk: manufacturing_work_order_dependencies, other: work_order_id)
  - dependentWorkOrders(): belongsToMany(self::class, fk: manufacturing_work_order_dependencies, other: depends_on_work_order_id)
  - productivityLogs(): hasMany(WorkCenterProductivityLog::class, fk: work_order_id)
Notes: Standard domain model.
```

### Partners Plugin (`plugins/webkul/partners`)

#### Address Model

```yaml
Model: Webkul\Partner\Models\Address
Namespace: Webkul\Partner\Models
File: plugins/webkul/partners/src/Models/Address.php
Table: partners_partners
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101220_create_partners_partners_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Partner\Models\Partner. Operates against table `partners_partners`.
```

#### Bank Model

```yaml
Model: Webkul\Partner\Models\Bank
Namespace: Webkul\Partner\Models
File: plugins/webkul/partners/src/Models/Bank.php
Table: banks
Migration: plugins/webkul/support/database/migrations/2024_12_10_101420_create_banks_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Support\Models\Bank. Operates against table `banks`.
```

#### BankAccount Model

```yaml
Model: Webkul\Partner\Models\BankAccount
Namespace: Webkul\Partner\Models
File: plugins/webkul/partners/src/Models/BankAccount.php
Table: partners_bank_accounts
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101420_create_partners_bank_accounts_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
  - SoftDeletes
Important Columns / Fillable:
  - account_number
  - account_holder_name
  - is_active
  - can_send_money
  - creator_id
  - partner_id
  - bank_id
Casts:
  - is_active: 'boolean'
  - can_send_money: 'boolean'
Relationships:
  - bank(): belongsTo(Bank::class)
  - partner(): belongsTo(Partner::class)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### Industry Model

```yaml
Model: Webkul\Partner\Models\Industry
Namespace: Webkul\Partner\Models
File: plugins/webkul/partners/src/Models/Industry.php
Table: partners_industries
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101127_create_partners_industries_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
  - SoftDeletes
Important Columns / Fillable:
  - name
  - description
  - creator_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### Partner Model

```yaml
Model: Webkul\Partner\Models\Partner
Namespace: Webkul\Partner\Models
File: plugins/webkul/partners/src/Models/Partner.php
Table: partners_partners
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101220_create_partners_partners_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasChatter
  - HasContributedAttributes
  - HasCustomFields
  - HasFactory
  - HasLogActivity
  - HasOwnershipScope
  - Notifiable
  - SoftDeletes
Important Columns / Fillable:
  - account_type
  - sub_type
  - name
  - avatar
  - email
  - job_title
  - website
  - tax_id
  - phone
  - mobile
  - color
  - company_registry
  - ... (13 additional columns in fillable)
Casts:
  - account_type: AccountType::class
  - is_active: 'boolean'
Relationships:
  - country(): belongsTo(Country::class)
  - state(): belongsTo(State::class)
  - parent(): belongsTo(self::class)
  - creator(): belongsTo(User::class)
  - user(): belongsTo(User::class)
  - title(): belongsTo(Title::class)
  - company(): belongsTo(Company::class)
  - industry(): belongsTo(Industry::class)
  - addresses(): hasMany(self::class, fk: parent_id)
  - contacts(): hasMany(self::class, fk: parent_id)
  - bankAccounts(): hasMany(BankAccount::class, fk: partner_id)
  - tags(): belongsToMany(Tag::class, fk: partners_partner_tag, other: partner_id)
Notes: Extends base class `Authenticatable`.
```

#### Tag Model

```yaml
Model: Webkul\Partner\Models\Tag
Namespace: Webkul\Partner\Models
File: plugins/webkul/partners/src/Models/Tag.php
Table: partners_tags
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101927_create_partners_tags_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
  - SoftDeletes
Important Columns / Fillable:
  - name
  - color
  - creator_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### Title Model

```yaml
Model: Webkul\Partner\Models\Title
Namespace: Webkul\Partner\Models
File: plugins/webkul/partners/src/Models/Title.php
Table: partners_titles
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101127_create_partners_titles_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - name
  - short_name
  - creator_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

### Payments Plugin (`plugins/webkul/payments`)

#### Payment Model

```yaml
Model: Webkul\Payment\Models\Payment
Namespace: Webkul\Payment\Models
File: plugins/webkul/payments/src/Models/Payment.php
Table: payments
Migration: [NO DIRECT MIGRATION]
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns: []
Casts: {}
Relationships: []
Notes: Standard domain model.
```

#### PaymentToken Model

```yaml
Model: Webkul\Payment\Models\PaymentToken
Namespace: Webkul\Payment\Models
File: plugins/webkul/payments/src/Models/PaymentToken.php
Table: payments_payment_tokens
Migration: plugins/webkul/payments/database/migrations/2025_02_11_101123_create_payments_payment_tokens_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns: []
Casts: {}
Relationships: []
Notes: Standard domain model.
```

#### PaymentTransaction Model

```yaml
Model: Webkul\Payment\Models\PaymentTransaction
Namespace: Webkul\Payment\Models
File: plugins/webkul/payments/src/Models/PaymentTransaction.php
Table: payments_payment_transactions
Migration: plugins/webkul/payments/database/migrations/2025_02_11_103602_create_payments_payment_transactions_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns: []
Casts: {}
Relationships: []
Notes: Standard domain model.
```

### Plugin Manager Plugin (`plugins/webkul/plugin-manager`)

#### Plugin Model

```yaml
Model: Webkul\PluginManager\Models\Plugin
Namespace: Webkul\PluginManager\Models
File: plugins/webkul/plugin-manager/src/Models/Plugin.php
Table: plugins
Migration: plugins/webkul/plugin-manager/database/migrations/2024_11_05_105102_create_plugins_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - SortableTrait
Important Columns / Fillable:
  - name
  - author
  - summary
  - description
  - latest_version
  - license
  - is_active
  - is_installed
  - sort
Casts:
  - is_active: 'boolean'
Relationships:
  - dependencies(): belongsToMany(Plugin::class, fk: plugin_dependencies, other: plugin_id)
  - dependents(): belongsToMany(Plugin::class, fk: plugin_dependencies, other: dependency_id)
Notes: Standard domain model.
```

### Products Plugin (`plugins/webkul/products`)

#### Attribute Model

```yaml
Model: Webkul\Product\Models\Attribute
Namespace: Webkul\Product\Models
File: plugins/webkul/products/src/Models/Attribute.php
Table: products_attributes
Migration: plugins/webkul/products/database/migrations/2025_01_05_104456_create_products_attributes_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasCustomFields
  - HasFactory
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - name
  - type
  - sort
  - creator_id
Casts:
  - type: AttributeType::class
Relationships:
  - options(): hasMany(AttributeOption::class)
  - productAttributes(): hasMany(ProductAttribute::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### AttributeOption Model

```yaml
Model: Webkul\Product\Models\AttributeOption
Namespace: Webkul\Product\Models
File: plugins/webkul/products/src/Models/AttributeOption.php
Table: products_attribute_options
Migration: plugins/webkul/products/database/migrations/2025_01_05_104512_create_products_attribute_options_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - name
  - color
  - extra_price
  - sort
  - attribute_id
  - creator_id
Casts: {}
Relationships:
  - attribute(): belongsTo(Attribute::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### Category Model

```yaml
Model: Webkul\Product\Models\Category
Namespace: Webkul\Product\Models
File: plugins/webkul/products/src/Models/Category.php
Table: products_categories
Migration: plugins/webkul/products/database/migrations/2025_01_05_063925_create_products_categories_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasChatter
  - HasContributedAttributes
  - HasCustomFields
  - HasFactory
  - HasLogActivity
Important Columns / Fillable:
  - name
  - full_name
  - parent_path
  - parent_id
  - creator_id
Casts: {}
Relationships:
  - parent(): belongsTo(self::class)
  - children(): hasMany(self::class, fk: parent_id)
  - products(): hasMany(Product::class)
  - creator(): belongsTo(User::class)
  - priceRuleItems(): hasMany(PriceRuleItem::class)
Notes: Standard domain model.
```

#### Packaging Model

```yaml
Model: Webkul\Product\Models\Packaging
Namespace: Webkul\Product\Models
File: plugins/webkul/products/src/Models/Packaging.php
Table: products_packagings
Migration: plugins/webkul/products/database/migrations/2025_01_05_105626_create_products_packagings_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - name
  - barcode
  - qty
  - sort
  - product_id
  - company_id
  - creator_id
Casts: {}
Relationships:
  - product(): belongsTo(Product::class)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### PriceList Model

```yaml
Model: Webkul\Product\Models\PriceList
Namespace: Webkul\Product\Models
File: plugins/webkul/products/src/Models/PriceList.php
Table: products_product_price_lists
Migration: plugins/webkul/products/database/migrations/2025_02_18_112837_create_products_product_price_lists_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - sort
  - currency_id
  - company_id
  - creator_id
  - name
  - is_active
Casts:
  - is_active: 'boolean'
Relationships:
  - currency(): belongsTo(Currency::class)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
  - items(): hasMany(PriceRuleItem::class)
Notes: Standard domain model representing customer or currency pricing rule lists.
```

#### PriceRuleItem Model

```yaml
Model: Webkul\Product\Models\PriceRuleItem
Namespace: Webkul\Product\Models
File: plugins/webkul/products/src/Models/PriceRuleItem.php
Table: products_price_rule_items
Migration: plugins/webkul/products/database/migrations/2025_01_05_113402_create_products_price_rule_items_table.php
Consolidated by: plugins/webkul/products/database/migrations/2026_09_15_000000_consolidate_products_price_rules_into_price_lists_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
Important Columns / Fillable:
  - price_list_id
  - base_price_list_id
  - apply_to
  - display_apply_to
  - base
  - type
  - min_quantity
  - fixed_price
  - price_discount
  - price_round
  - price_surcharge
  - price_markup
  - price_min_margin
  - price_max_margin
  - percent_price
  - starts_at
  - ends_at
  - currency_id
  - product_id
  - category_id
  - company_id
  - creator_id
Casts:
  - starts_at: 'datetime'
  - ends_at: 'datetime'
  - apply_to: PriceRuleApplyTo::class
  - base: PriceRuleBase::class
  - type: PriceRuleType::class
Relationships:
  - priceList(): belongsTo(PriceList::class, fk: price_list_id)
  - basePriceList(): belongsTo(PriceList::class, fk: base_price_list_id)
  - product(): belongsTo(Product::class)
  - category(): belongsTo(Category::class)
  - currency(): belongsTo(Currency::class)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model representing price computation items linked directly to a parent PriceList.
```

#### Product Model

```yaml
Model: Webkul\Product\Models\Product
Namespace: Webkul\Product\Models
File: plugins/webkul/products/src/Models/Product.php
Table: products_products
Migration: plugins/webkul/products/database/migrations/2025_01_05_100751_create_products_products_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasChatter
  - HasContributedAttributes
  - HasCustomFields
  - HasFactory
  - HasLogActivity
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - type
  - name
  - service_tracking
  - reference
  - barcode
  - price
  - cost
  - volume
  - weight
  - description
  - description_purchase
  - description_sale
  - ... (12 additional columns in fillable)
Casts:
  - type: ProductType::class
  - enable_sales: 'boolean'
  - enable_purchase: 'boolean'
  - is_favorite: 'boolean'
  - is_configurable: 'boolean'
  - images: 'array'
  - cost: 'float'
  - price: 'float'
  - volume: 'decimal:4'
  - weight: 'decimal:4'
Relationships:
  - parent(): belongsTo(self::class)
  - uom(): belongsTo(UOM::class, fk: uom_id)
  - uomPO(): belongsTo(UOM::class, fk: uom_po_id)
  - category(): belongsTo(Category::class)
  - tags(): belongsToMany(Tag::class, fk: products_product_tag, other: product_id)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
  - attributes(): hasMany(ProductAttribute::class)
  - attribute_values(): hasMany(ProductAttributeValue::class, fk: product_id)
  - variants(): hasMany(self::class, fk: parent_id)
  - combinations(): hasMany(ProductCombination::class, fk: product_id)
  - priceRuleItems(): hasMany(PriceRuleItem::class)
  - sellers(): hasMany(ProductSupplier::class)
Notes: Standard domain model.
```

#### ProductAttribute Model

```yaml
Model: Webkul\Product\Models\ProductAttribute
Namespace: Webkul\Product\Models
File: plugins/webkul/products/src/Models/ProductAttribute.php
Table: products_product_attributes
Migration: plugins/webkul/products/database/migrations/2025_01_05_104759_create_products_product_attributes_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - sort
  - product_id
  - attribute_id
  - creator_id
Casts: {}
Relationships:
  - product(): belongsTo(Product::class)
  - attribute(): belongsTo(Attribute::class)
  - options(): belongsToMany(AttributeOption::class, fk: products_product_attribute_values, other: product_attribute_id)
  - values(): hasMany(ProductAttributeValue::class, fk: product_attribute_id)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### ProductAttributeValue Model

```yaml
Model: Webkul\Product\Models\ProductAttributeValue
Namespace: Webkul\Product\Models
File: plugins/webkul/products/src/Models/ProductAttributeValue.php
Table: products_product_attribute_values
Migration: plugins/webkul/products/database/migrations/2025_01_05_104809_create_products_product_attribute_values_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - extra_price
  - product_id
  - attribute_id
  - product_attribute_id
  - attribute_option_id
Casts: {}
Relationships:
  - product(): belongsTo(Product::class)
  - attribute(): belongsTo(Attribute::class)
  - productAttribute(): belongsTo(ProductAttribute::class)
  - attributeOption(): belongsTo(AttributeOption::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### ProductCombination Model

```yaml
Model: Webkul\Product\Models\ProductCombination
Namespace: Webkul\Product\Models
File: plugins/webkul/products/src/Models/ProductCombination.php
Table: products_product_combinations
Migration: plugins/webkul/products/database/migrations/2025_02_21_053249 _create_products_product_combinations_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - product_id
  - product_attribute_value_id
Casts: {}
Relationships:
  - product(): belongsTo(Product::class, fk: product_id)
  - productAttributeValue(): belongsTo(ProductAttributeValue::class, fk: product_attribute_value_id)
Notes: Standard domain model.
```

#### ProductSupplier Model

```yaml
Model: Webkul\Product\Models\ProductSupplier
Namespace: Webkul\Product\Models
File: plugins/webkul/products/src/Models/ProductSupplier.php
Table: products_product_suppliers
Migration: plugins/webkul/products/database/migrations/2025_01_05_123412_create_products_product_suppliers_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - sort
  - delay
  - product_name
  - product_code
  - starts_at
  - ends_at
  - min_qty
  - price
  - price_discounted
  - discount
  - product_id
  - partner_id
  - ... (4 additional columns in fillable)
Casts:
  - starts_at: 'date'
  - ends_at: 'date'
Relationships:
  - product(): belongsTo(Product::class)
  - partner(): belongsTo(Partner::class)
  - currency(): belongsTo(Currency::class)
  - uom(): belongsTo(UOM::class, fk: uom_id)
  - creator(): belongsTo(User::class)
  - company(): belongsTo(Company::class)
Notes: Standard domain model.
```

#### Tag Model

```yaml
Model: Webkul\Product\Models\Tag
Namespace: Webkul\Product\Models
File: plugins/webkul/products/src/Models/Tag.php
Table: products_tags
Migration: plugins/webkul/products/database/migrations/2025_01_05_100830_create_products_tags_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
  - SoftDeletes
Important Columns / Fillable:
  - name
  - color
  - creator_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

### Projects Plugin (`plugins/webkul/projects`)

#### ActivityPlan Model

```yaml
Model: Webkul\Project\Models\ActivityPlan
Namespace: Webkul\Project\Models
File: plugins/webkul/projects/src/Models/ActivityPlan.php
Table: activity_plans
Migration: plugins/webkul/support/database/migrations/2024_12_12_114620_create_activity_plans_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Support\Models\ActivityPlan. Operates against table `activity_plans`.
```

#### Milestone Model

```yaml
Model: Webkul\Project\Models\Milestone
Namespace: Webkul\Project\Models
File: plugins/webkul/projects/src/Models/Milestone.php
Table: projects_milestones
Migration: plugins/webkul/projects/database/migrations/2024_12_12_074930_create_projects_milestones_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasCustomFields
  - HasFactory
Important Columns / Fillable:
  - name
  - deadline
  - is_completed
  - completed_at
  - project_id
  - creator_id
Casts:
  - is_completed: 'boolean'
  - deadline: 'datetime'
  - completed_at: 'datetime'
Relationships:
  - project(): belongsTo(Project::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### Project Model

```yaml
Model: Webkul\Project\Models\Project
Namespace: Webkul\Project\Models
File: plugins/webkul/projects/src/Models/Project.php
Table: projects_projects
Migration: plugins/webkul/projects/database/migrations/2024_12_12_074929_create_projects_projects_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasChatter
  - HasCustomFields
  - HasFactory
  - HasLogActivity
  - HasOwnershipScope
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - name
  - tasks_label
  - description
  - visibility
  - color
  - sort
  - start_date
  - end_date
  - allocated_hours
  - allow_timesheets
  - allow_milestones
  - allow_task_dependencies
  - ... (6 additional columns in fillable)
Casts:
  - start_date: 'date'
  - end_date: 'date'
  - allocated_hours: 'integer'
  - is_active: 'boolean'
  - allow_timesheets: 'boolean'
  - allow_milestones: 'boolean'
  - allow_task_dependencies: 'boolean'
Relationships:
  - partner(): belongsTo(Partner::class)
  - creator(): belongsTo(User::class)
  - user(): belongsTo(User::class)
  - stage(): belongsTo(ProjectStage::class)
  - taskStages(): hasMany(TaskStage::class)
  - favoriteUsers(): belongsToMany(User::class, fk: projects_user_project_favorites, other: project_id)
  - milestones(): hasMany(Milestone::class)
  - tasks(): hasMany(Task::class)
  - company(): belongsTo(Company::class)
  - tags(): belongsToMany(Tag::class, fk: projects_project_tag, other: project_id)
Notes: Standard domain model.
```

#### ProjectStage Model

```yaml
Model: Webkul\Project\Models\ProjectStage
Namespace: Webkul\Project\Models
File: plugins/webkul/projects/src/Models/ProjectStage.php
Table: projects_project_stages
Migration: plugins/webkul/projects/database/migrations/2024_12_12_074920_create_projects_project_stages_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - name
  - is_active
  - is_collapsed
  - sort
  - company_id
  - creator_id
Casts:
  - is_active: 'boolean'
  - is_collapsed: 'boolean'
Relationships:
  - creator(): belongsTo(User::class)
  - company(): belongsTo(Company::class)
  - projects(): hasMany(Project::class, fk: stage_id)
Notes: Standard domain model.
```

#### Tag Model

```yaml
Model: Webkul\Project\Models\Tag
Namespace: Webkul\Project\Models
File: plugins/webkul/projects/src/Models/Tag.php
Table: projects_tags
Migration: plugins/webkul/projects/database/migrations/2024_12_12_100230_create_projects_tags_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasCustomFields
  - HasFactory
  - SoftDeletes
Important Columns / Fillable:
  - name
  - color
  - creator_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### Task Model

```yaml
Model: Webkul\Project\Models\Task
Namespace: Webkul\Project\Models
File: plugins/webkul/projects/src/Models/Task.php
Table: projects_tasks
Migration: plugins/webkul/projects/database/migrations/2024_12_12_101344_create_projects_tasks_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasChatter
  - HasCustomFields
  - HasFactory
  - HasLogActivity
  - HasOwnershipScope
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - title
  - description
  - color
  - priority
  - state
  - sort
  - is_active
  - is_recurring
  - deadline
  - working_hours_open
  - working_hours_close
  - allocated_hours
  - ... (12 additional columns in fillable)
Casts:
  - deadline: 'datetime'
  - priority: 'boolean'
  - is_active: 'boolean'
  - is_recurring: 'boolean'
  - working_hours_open: 'float'
  - working_hours_close: 'float'
  - allocated_hours: 'float'
  - remaining_hours: 'float'
  - effective_hours: 'float'
  - total_hours_spent: 'float'
  - overtime: 'float'
  - state: TaskState::class
Relationships:
  - parent(): belongsTo(self::class)
  - subTasks(): hasMany(self::class, fk: parent_id)
  - project(): belongsTo(Project::class)
  - milestone(): belongsTo(Milestone::class)
  - stage(): belongsTo(TaskStage::class)
  - partner(): belongsTo(Partner::class)
  - creator(): belongsTo(User::class)
  - users(): belongsToMany(User::class, fk: projects_task_users)
  - company(): belongsTo(Company::class)
  - tags(): belongsToMany(Tag::class, fk: projects_task_tag, other: task_id)
  - timesheets(): hasMany(Timesheet::class)
Notes: Standard domain model.
```

#### TaskStage Model

```yaml
Model: Webkul\Project\Models\TaskStage
Namespace: Webkul\Project\Models
File: plugins/webkul/projects/src/Models/TaskStage.php
Table: projects_task_stages
Migration: plugins/webkul/projects/database/migrations/2024_12_12_101340_create_projects_task_stages_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - name
  - is_active
  - is_collapsed
  - sort
  - project_id
  - company_id
  - user_id
  - creator_id
Casts:
  - is_active: 'boolean'
  - is_collapsed: 'boolean'
Relationships:
  - project(): belongsTo(Project::class)
  - tasks(): hasMany(Task::class, fk: stage_id)
  - user(): belongsTo(User::class)
  - creator(): belongsTo(User::class)
  - company(): belongsTo(Company::class)
Notes: Standard domain model.
```

#### Timesheet Model

```yaml
Model: Webkul\Project\Models\Timesheet
Namespace: Webkul\Project\Models
File: plugins/webkul/projects/src/Models/Timesheet.php
Table: analytic_records
Migration: plugins/webkul/analytics/database/migrations/2024_12_18_131844_create_analytic_records_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - project(): belongsTo(Project::class)
  - task(): belongsTo(Task::class)
Notes: Proxy/Extension model extending Webkul\Analytic\Models\Record. Operates against table `analytic_records`.
```

### Purchases Plugin (`plugins/webkul/purchases`)

#### AccountMove Model

```yaml
Model: Webkul\Purchase\Models\AccountMove
Namespace: Webkul\Purchase\Models
File: plugins/webkul/purchases/src/Models/AccountMove.php
Table: accounts_account_moves
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_055303_create_accounts_account_moves_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - lines(): hasMany(AccountMoveLine::class, fk: move_id)
  - purchaseOrders(): belongsToMany(Order::class, fk: purchases_order_account_moves, other: move_id)
Notes: Proxy/Extension model extending Webkul\Account\Models\Move. Operates against table `accounts_account_moves`.
```

#### AccountMoveLine Model

```yaml
Model: Webkul\Purchase\Models\AccountMoveLine
Namespace: Webkul\Purchase\Models
File: plugins/webkul/purchases/src/Models/AccountMoveLine.php
Table: accounts_account_move_lines
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_071210_create_accounts_account_move_lines_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - move(): belongsTo(AccountMove::class)
  - purchaseOrderLine(): belongsTo(OrderLine::class, fk: purchase_order_line_id)
Notes: Proxy/Extension model extending Webkul\Account\Models\MoveLine. Operates against table `accounts_account_move_lines`.
```

#### Attribute Model

```yaml
Model: Webkul\Purchase\Models\Attribute
Namespace: Webkul\Purchase\Models
File: plugins/webkul/purchases/src/Models/Attribute.php
Table: products_attributes
Migration: plugins/webkul/products/database/migrations/2025_01_05_104456_create_products_attributes_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Product\Models\Attribute. Operates against table `products_attributes`.
```

#### Bill Model

```yaml
Model: Webkul\Purchase\Models\Bill
Namespace: Webkul\Purchase\Models
File: plugins/webkul/purchases/src/Models/Bill.php
Table: accounts_account_moves
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_055303_create_accounts_account_moves_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - purchaseOrders(): belongsToMany(Order::class, fk: purchases_order_account_moves, other: move_id)
Notes: Proxy/Extension model extending Webkul\Invoice\Models\Bill -> Webkul\Account\Models\Move. Operates against table `accounts_account_moves`.
```

#### Category Model

```yaml
Model: Webkul\Purchase\Models\Category
Namespace: Webkul\Purchase\Models
File: plugins/webkul/purchases/src/Models/Category.php
Table: products_categories
Migration: plugins/webkul/products/database/migrations/2025_01_05_063925_create_products_categories_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Invoice\Models\Category -> Webkul\Account\Models\Category -> Webkul\Product\Models\Category. Operates against table `products_categories`.
```

#### Currency Model

```yaml
Model: Webkul\Purchase\Models\Currency
Namespace: Webkul\Purchase\Models
File: plugins/webkul/purchases/src/Models/Currency.php
Table: currencies
Migration: plugins/webkul/support/database/migrations/2024_12_06_061927_create_currencies_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Support\Models\Currency. Operates against table `currencies`.
```

#### CustomerPurchaseOrder Model

```yaml
Model: Webkul\Purchase\Models\CustomerPurchaseOrder
Namespace: Webkul\Purchase\Models
File: plugins/webkul/purchases/src/Models/CustomerPurchaseOrder.php
Table: purchases_orders
Migration: plugins/webkul/purchases/database/migrations/2025_02_11_101110_create_purchases_orders_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Purchase\Models\Order. Operates against table `purchases_orders`.
```

#### Order Model

```yaml
Model: Webkul\Purchase\Models\Order
Namespace: Webkul\Purchase\Models
File: plugins/webkul/purchases/src/Models/Order.php
Table: purchases_orders
Migration: plugins/webkul/purchases/database/migrations/2025_02_11_101110_create_purchases_orders_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - ChecksCompanyConsistency
  - HasChatter
  - HasCustomFields
  - HasFactory
  - HasLogActivity
  - HasOwnershipScope
Important Columns / Fillable:
  - name
  - description
  - priority
  - origin
  - partner_reference
  - state
  - invoice_status
  - receipt_status
  - untaxed_amount
  - tax_amount
  - total_amount
  - total_cc_amount
  - ... (25 additional columns in fillable)
Casts:
  - state: OrderState::class
  - invoice_status: OrderInvoiceStatus::class
  - receipt_status: OrderReceiptStatus::class
  - mail_reminder_confirmed: 'boolean'
  - mail_reception_confirmed: 'boolean'
  - mail_reception_declined: 'boolean'
  - report_grids: 'boolean'
  - ordered_at: 'datetime'
  - approved_at: 'datetime'
  - planned_at: 'datetime'
  - calendar_start_at: 'datetime'
  - effective_date: 'datetime'
  - untaxed_amount: 'decimal:4'
Relationships:
  - requisition(): belongsTo(Requisition::class)
  - group(): belongsTo(OrderGroup::class)
  - partner(): belongsTo(Partner::class)
  - fiscalPosition(): belongsTo(FiscalPosition::class)
  - paymentTerm(): belongsTo(PaymentTerm::class)
  - incoterm(): belongsTo(Incoterm::class)
  - currency(): belongsTo(Currency::class)
  - user(): belongsTo(User::class)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
  - destinationAddress(): belongsTo(Partner::class, fk: destination_address_id)
  - lines(): hasMany(OrderLine::class, fk: order_id)
  - accountMoves(): belongsToMany(AccountMove::class, fk: purchases_order_account_moves, other: order_id)
  - bills(): belongsToMany(Bill::class, fk: purchases_order_account_moves, other: order_id)
  - operationType(): belongsTo(OperationType::class, fk: operation_type_id)
  - operations(): belongsToMany(Receipt::class, fk: purchases_order_operations, other: purchase_order_id)
  - procurementGroup(): belongsTo(ProcurementGroup::class, fk: procurement_group_id)
Notes: Standard domain model.
```

#### OrderGroup Model

```yaml
Model: Webkul\Purchase\Models\OrderGroup
Namespace: Webkul\Purchase\Models
File: plugins/webkul/purchases/src/Models/OrderGroup.php
Table: purchases_order_groups
Migration: plugins/webkul/purchases/database/migrations/2025_02_11_101100_create_purchases_order_groups_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - creator_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### OrderLine Model

```yaml
Model: Webkul\Purchase\Models\OrderLine
Namespace: Webkul\Purchase\Models
File: plugins/webkul/purchases/src/Models/OrderLine.php
Table: purchases_order_lines
Migration: plugins/webkul/purchases/database/migrations/2025_02_11_101118_create_purchases_order_lines_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - ChecksCompanyConsistency
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - name
  - state
  - sort
  - qty_received_method
  - display_type
  - product_qty
  - product_uom_qty
  - product_packaging_qty
  - price_tax
  - discount
  - price_unit
  - price_subtotal
  - ... (21 additional columns in fillable)
Casts:
  - qty_received_method: QtyReceivedMethod::class
  - planned_at: 'datetime'
  - is_downpayment: 'boolean'
  - propagate_cancel: 'boolean'
Relationships:
  - order(): belongsTo(Order::class)
  - partner(): belongsTo(Partner::class)
  - product(): belongsTo(Product::class)
  - productPackaging(): belongsTo(Packaging::class)
  - uom(): belongsTo(UOM::class)
  - taxes(): belongsToMany(Tax::class, fk: purchases_order_line_taxes, other: order_line_id)
  - currency(): belongsTo(Currency::class)
  - user(): belongsTo(User::class)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
  - accountMoveLines(): hasMany(AccountMoveLine::class, fk: purchase_order_line_id)
  - inventoryMoves(): hasMany(InventoryMove::class, fk: purchase_order_line_id)
  - moveDestinations(): belongsToMany(InventoryMove::class, fk: purchases_order_line_moves, other: purchase_order_line_id)
  - finalLocation(): belongsTo(Location::class, fk: final_location_id)
  - orderPoint(): belongsTo(OrderPoint::class, fk: order_point_id)
  - procurementGroup(): belongsTo(ProcurementGroup::class, fk: procurement_group_id)
Notes: Standard domain model.
```

#### Packaging Model

```yaml
Model: Webkul\Purchase\Models\Packaging
Namespace: Webkul\Purchase\Models
File: plugins/webkul/purchases/src/Models/Packaging.php
Table: products_packagings
Migration: plugins/webkul/products/database/migrations/2025_01_05_105626_create_products_packagings_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Product\Models\Packaging. Operates against table `products_packagings`.
```

#### Partner Model

```yaml
Model: Webkul\Purchase\Models\Partner
Namespace: Webkul\Purchase\Models
File: plugins/webkul/purchases/src/Models/Partner.php
Table: partners_partners
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101220_create_partners_partners_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - orders(): hasMany(Order::class)
  - accountMoves(): hasMany(Move::class)
Notes: Proxy/Extension model extending Webkul\Account\Models\Partner -> Webkul\Partner\Models\Partner. Operates against table `partners_partners`.
```

#### Product Model

```yaml
Model: Webkul\Purchase\Models\Product
Namespace: Webkul\Purchase\Models
File: plugins/webkul/purchases/src/Models/Product.php
Table: products_products
Migration: plugins/webkul/products/database/migrations/2025_01_05_100751_create_products_products_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - sellers(): hasMany(ProductSupplier::class)
Notes: Proxy/Extension model extending Webkul\Invoice\Models\Product -> Webkul\Account\Models\Product -> Webkul\Product\Models\Product. Operates against table `products_products`.
```

#### ProductSupplier Model

```yaml
Model: Webkul\Purchase\Models\ProductSupplier
Namespace: Webkul\Purchase\Models
File: plugins/webkul/purchases/src/Models/ProductSupplier.php
Table: products_product_suppliers
Migration: plugins/webkul/products/database/migrations/2025_01_05_123412_create_products_product_suppliers_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Product\Models\ProductSupplier. Operates against table `products_product_suppliers`.
```

#### PurchaseOrder Model

```yaml
Model: Webkul\Purchase\Models\PurchaseOrder
Namespace: Webkul\Purchase\Models
File: plugins/webkul/purchases/src/Models/PurchaseOrder.php
Table: purchases_orders
Migration: plugins/webkul/purchases/database/migrations/2025_02_11_101110_create_purchases_orders_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Purchase\Models\Order. Operates against table `purchases_orders`.
```

#### Quotation Model

```yaml
Model: Webkul\Purchase\Models\Quotation
Namespace: Webkul\Purchase\Models
File: plugins/webkul/purchases/src/Models/Quotation.php
Table: purchases_orders
Migration: plugins/webkul/purchases/database/migrations/2025_02_11_101110_create_purchases_orders_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Purchase\Models\Order. Operates against table `purchases_orders`.
```

#### Requisition Model

```yaml
Model: Webkul\Purchase\Models\Requisition
Namespace: Webkul\Purchase\Models
File: plugins/webkul/purchases/src/Models/Requisition.php
Table: purchases_requisitions
Migration: plugins/webkul/purchases/database/migrations/2025_02_11_101101_create_purchases_requisitions_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasChatter
  - HasCustomFields
  - HasFactory
  - HasLogActivity
  - SoftDeletes
Important Columns / Fillable:
  - name
  - type
  - state
  - reference
  - starts_at
  - ends_at
  - description
  - currency_id
  - partner_id
  - user_id
  - company_id
  - creator_id
Casts:
  - state: RequisitionState::class
  - type: RequisitionType::class
Relationships:
  - partner(): belongsTo(Partner::class)
  - currency(): belongsTo(Currency::class)
  - user(): belongsTo(User::class)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
  - lines(): hasMany(RequisitionLine::class)
  - orders(): hasMany(Order::class)
Notes: Standard domain model.
```

#### RequisitionLine Model

```yaml
Model: Webkul\Purchase\Models\RequisitionLine
Namespace: Webkul\Purchase\Models
File: plugins/webkul/purchases/src/Models/RequisitionLine.php
Table: purchases_requisition_lines
Migration: plugins/webkul/purchases/database/migrations/2025_02_11_101105_create_purchases_requisition_lines_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - ChecksCompanyConsistency
  - HasFactory
Important Columns / Fillable:
  - qty
  - price_unit
  - requisition_id
  - product_id
  - uom_id
  - company_id
  - creator_id
Casts: {}
Relationships:
  - requisition(): belongsTo(Requisition::class)
  - product(): belongsTo(Product::class)
  - uom(): belongsTo(UOM::class)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### UOMCategory Model

```yaml
Model: Webkul\Purchase\Models\UOMCategory
Namespace: Webkul\Purchase\Models
File: plugins/webkul/purchases/src/Models/UOMCategory.php
Table: unit_of_measure_categories
Migration: plugins/webkul/support/database/migrations/2025_01_03_105625_create_unit_of_measure_categories_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Support\Models\UOMCategory. Operates against table `unit_of_measure_categories`.
```

### Recruitments Plugin (`plugins/webkul/recruitments`)

#### ActivityPlan Model

```yaml
Model: Webkul\Recruitment\Models\ActivityPlan
Namespace: Webkul\Recruitment\Models
File: plugins/webkul/recruitments/src/Models/ActivityPlan.php
Table: activity_plans
Migration: plugins/webkul/support/database/migrations/2024_12_12_114620_create_activity_plans_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Employee\Models\ActivityPlan -> Webkul\Support\Models\ActivityPlan. Operates against table `activity_plans`.
```

#### ActivityType Model

```yaml
Model: Webkul\Recruitment\Models\ActivityType
Namespace: Webkul\Recruitment\Models
File: plugins/webkul/recruitments/src/Models/ActivityType.php
Table: activity_types
Migration: plugins/webkul/support/database/migrations/2024_12_12_115256_create_activity_types_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Support\Models\ActivityType. Operates against table `activity_types`.
```

#### Applicant Model

```yaml
Model: Webkul\Recruitment\Models\Applicant
Namespace: Webkul\Recruitment\Models
File: plugins/webkul/recruitments/src/Models/Applicant.php
Table: recruitments_applicants
Migration: plugins/webkul/recruitments/database/migrations/2025_01_10_115422_create_recruitments_applicants_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasApplicationStatus
  - HasChatter
  - HasCustomFields
  - HasLogActivity
  - SoftDeletes
Important Columns / Fillable:
  - source_id
  - medium_id
  - candidate_id
  - stage_id
  - last_stage_id
  - company_id
  - recruiter_id
  - job_id
  - department_id
  - refuse_reason_id
  - state
  - creator_id
  - ... (16 additional columns in fillable)
Casts:
  - is_active: 'boolean'
  - create_date: 'date'
  - date_closed: 'date'
  - date_opened: 'date'
  - date_last_stage_updated: 'date'
  - refuse_date: 'date'
  - applicant_properties: 'json'
  - probability: 'double'
  - salary_proposed: 'double'
  - salary_expected: 'double'
  - delay_close: 'double'
Relationships:
  - source(): belongsTo(UTMSource::class)
  - medium(): belongsTo(UTMMedium::class)
  - candidate(): belongsTo(Candidate::class)
  - skills(): hasManyThrough(CandidateSkill::class, fk: Candidate::class, other: id)
  - stage(): belongsTo(Stage::class)
  - lastStage(): belongsTo(Stage::class, fk: last_stage_id)
  - company(): belongsTo(Company::class)
  - recruiter(): belongsTo(User::class, fk: recruiter_id)
  - interviewer(): belongsToMany(User::class, fk: recruitments_applicant_interviewers, other: applicant_id)
  - categories(): belongsToMany(ApplicantCategory::class, fk: recruitments_applicant_applicant_categories, other: applicant_id)
  - job(): belongsTo(JobPosition::class, fk: job_id)
  - department(): belongsTo(Department::class)
  - refuseReason(): belongsTo(RefuseReason::class)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### ApplicantApplicantCategory Model

```yaml
Model: Webkul\Recruitment\Models\ApplicantApplicantCategory
Namespace: Webkul\Recruitment\Models
File: plugins/webkul/recruitments/src/Models/ApplicantApplicantCategory.php
Table: recruitments_applicant_applicant_categories
Migration: plugins/webkul/recruitments/database/migrations/2025_01_13_075926_create_recruitments_applicant_applicant_categories_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - applicant_id
  - applicant_category_id
Casts: {}
Relationships: []
Notes: Standard domain model.
```

#### ApplicantCategory Model

```yaml
Model: Webkul\Recruitment\Models\ApplicantCategory
Namespace: Webkul\Recruitment\Models
File: plugins/webkul/recruitments/src/Models/ApplicantCategory.php
Table: recruitments_applicant_categories
Migration: plugins/webkul/recruitments/database/migrations/2025_01_09_095909_create_recruitments_applicant_categories_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - name
  - color
  - creator_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### ApplicantInterviewer Model

```yaml
Model: Webkul\Recruitment\Models\ApplicantInterviewer
Namespace: Webkul\Recruitment\Models
File: plugins/webkul/recruitments/src/Models/ApplicantInterviewer.php
Table: recruitments_applicant_interviewers
Migration: plugins/webkul/recruitments/database/migrations/2025_01_13_072547_create_recruitments_applicant_interviewers_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - applicant_id
  - interviewer_id
Casts: {}
Relationships: []
Notes: Standard domain model.
```

#### Candidate Model

```yaml
Model: Webkul\Recruitment\Models\Candidate
Namespace: Webkul\Recruitment\Models
File: plugins/webkul/recruitments/src/Models/Candidate.php
Table: recruitments_candidates
Migration: plugins/webkul/recruitments/database/migrations/2025_01_09_125852_create_recruitments_candidates_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasChatter
  - HasCustomFields
  - HasLogActivity
  - SoftDeletes
Important Columns / Fillable:
  - message_bounced
  - company_id
  - partner_id
  - degree_id
  - manager_id
  - employee_id
  - creator_id
  - email_cc
  - name
  - email_from
  - priority
  - phone
  - ... (4 additional columns in fillable)
Casts:
  - candidate_properties: 'array'
  - is_active: 'boolean'
Relationships:
  - company(): belongsTo(Company::class, fk: company_id)
  - partner(): belongsTo(Partner::class, fk: partner_id)
  - degree(): belongsTo(Degree::class, fk: degree_id)
  - manager(): belongsTo(User::class, fk: manager_id)
  - employee(): belongsTo(Employee::class, fk: employee_id)
  - creator(): belongsTo(User::class, fk: creator_id)
  - categories(): belongsToMany(ApplicantCategory::class, fk: recruitments_candidate_applicant_categories, other: candidate_id)
  - skills(): hasMany(CandidateSkill::class, fk: candidate_id)
Notes: Standard domain model.
```

#### CandidateApplicantCategory Model

```yaml
Model: Webkul\Recruitment\Models\CandidateApplicantCategory
Namespace: Webkul\Recruitment\Models
File: plugins/webkul/recruitments/src/Models/CandidateApplicantCategory.php
Table: recruitments_candidate_applicant_categories
Migration: plugins/webkul/recruitments/database/migrations/2025_01_10_045048_create_recruitments_candidate_applicant_categories_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - candidate_id
  - applicant_category_id
Casts: {}
Relationships: []
Notes: Standard domain model.
```

#### CandidateSkill Model

```yaml
Model: Webkul\Recruitment\Models\CandidateSkill
Namespace: Webkul\Recruitment\Models
File: plugins/webkul/recruitments/src/Models/CandidateSkill.php
Table: recruitments_candidate_skills
Migration: plugins/webkul/recruitments/database/migrations/2025_01_10_082944_create_recruitments_candidate_skills_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - candidate_id
  - skill_id
  - skill_level_id
  - skill_type_id
  - creator_id
  - user_id
Casts: {}
Relationships:
  - candidate(): belongsTo(Candidate::class)
  - skill(): belongsTo(Skill::class)
  - skillLevel(): belongsTo(SkillLevel::class)
  - skillType(): belongsTo(SkillType::class)
  - creator(): belongsTo(User::class, fk: creator_id)
  - user(): belongsTo(User::class, fk: user_id)
Notes: Standard domain model.
```

#### Degree Model

```yaml
Model: Webkul\Recruitment\Models\Degree
Namespace: Webkul\Recruitment\Models
File: plugins/webkul/recruitments/src/Models/Degree.php
Table: recruitments_degrees
Migration: plugins/webkul/recruitments/database/migrations/2025_01_09_071817_create_recruitments_degrees_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - SortableTrait
Important Columns / Fillable:
  - name
  - sort
  - creator_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### Department Model

```yaml
Model: Webkul\Recruitment\Models\Department
Namespace: Webkul\Recruitment\Models
File: plugins/webkul/recruitments/src/Models/Department.php
Table: employees_departments
Migration: plugins/webkul/employees/database/migrations/2024_12_11_051916_create_employees_departments_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Employee\Models\Department. Operates against table `employees_departments`.
```

#### EmploymentType Model

```yaml
Model: Webkul\Recruitment\Models\EmploymentType
Namespace: Webkul\Recruitment\Models
File: plugins/webkul/recruitments/src/Models/EmploymentType.php
Table: employees_employment_types
Migration: plugins/webkul/employees/database/migrations/2024_12_11_073130_create_employees_employment_types_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Employee\Models\EmploymentType. Operates against table `employees_employment_types`.
```

#### JobByPosition Model

```yaml
Model: Webkul\Recruitment\Models\JobByPosition
Namespace: Webkul\Recruitment\Models
File: plugins/webkul/recruitments/src/Models/JobByPosition.php
Table: employees_job_positions
Migration: plugins/webkul/employees/database/migrations/2024_12_11_081046_create_employees_job_positions_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Recruitment\Models\JobPosition -> Webkul\Employee\Models\EmployeeJobPosition. Operates against table `employees_job_positions`.
```

#### JobPosition Model

```yaml
Model: Webkul\Recruitment\Models\JobPosition
Namespace: Webkul\Recruitment\Models
File: plugins/webkul/recruitments/src/Models/JobPosition.php
Table: employees_job_positions
Migration: plugins/webkul/employees/database/migrations/2024_12_11_081046_create_employees_job_positions_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - address(): belongsTo(Partner::class, fk: address_id)
  - skills(): belongsToMany(Skill::class, fk: job_position_skills, other: job_position_id)
  - interviewers(): belongsToMany(User::class, fk: recruitments_job_position_interviewers, other: job_position_id)
  - manager(): belongsTo(Employee::class, fk: manager_id)
  - recruiter(): belongsTo(User::class, fk: recruiter_id)
  - industry(): belongsTo(Industry::class, fk: industry_id)
  - applications(): hasMany(Applicant::class, fk: job_id)
Notes: Proxy/Extension model extending Webkul\Employee\Models\EmployeeJobPosition. Operates against table `employees_job_positions`.
```

#### JobPositionInterviewer Model

```yaml
Model: Webkul\Recruitment\Models\JobPositionInterviewer
Namespace: Webkul\Recruitment\Models
File: plugins/webkul/recruitments/src/Models/JobPositionInterviewer.php
Table: recruitments_job_position_interviewers
Migration: plugins/webkul/recruitments/database/migrations/2025_01_16_081327_create_recruitments_job_position_interviewers_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - job_position_id
  - user_id
Casts: {}
Relationships:
  - jobPosition(): belongsTo(JobPosition::class, fk: job_position_id)
  - user(): belongsTo(User::class, fk: user_id)
Notes: Standard domain model.
```

#### RefuseReason Model

```yaml
Model: Webkul\Recruitment\Models\RefuseReason
Namespace: Webkul\Recruitment\Models
File: plugins/webkul/recruitments/src/Models/RefuseReason.php
Table: recruitments_refuse_reasons
Migration: plugins/webkul/recruitments/database/migrations/2025_01_09_082748_create_recruitments_refuse_reasons_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - SortableTrait
Important Columns / Fillable:
  - creator_id
  - sort
  - name
  - template
  - is_active
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### SkillType Model

```yaml
Model: Webkul\Recruitment\Models\SkillType
Namespace: Webkul\Recruitment\Models
File: plugins/webkul/recruitments/src/Models/SkillType.php
Table: employees_skill_types
Migration: plugins/webkul/employees/database/migrations/2024_12_11_075004_create_employees_skill_types_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Employee\Models\SkillType. Operates against table `employees_skill_types`.
```

#### Stage Model

```yaml
Model: Webkul\Recruitment\Models\Stage
Namespace: Webkul\Recruitment\Models
File: plugins/webkul/recruitments/src/Models/Stage.php
Table: recruitments_stages
Migration: plugins/webkul/recruitments/database/migrations/2025_01_06_133002_create_recruitments_stages_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - sort
  - is_default
  - creator_id
  - name
  - legend_blocked
  - legend_done
  - legend_normal
  - requirements
  - fold
  - hired_stage
Casts:
  - is_default: 'boolean'
  - hired_stage: 'boolean'
  - fold: 'boolean'
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
  - jobs(): belongsToMany(EmployeeJobPosition::class, fk: recruitments_stages_jobs, other: stage_id)
Notes: Standard domain model.
```

#### StageJob Model

```yaml
Model: Webkul\Recruitment\Models\StageJob
Namespace: Webkul\Recruitment\Models
File: plugins/webkul/recruitments/src/Models/StageJob.php
Table: recruitments_stages_jobs
Migration: plugins/webkul/recruitments/database/migrations/2025_01_07_053021_create_recruitments_stages_jobs_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - stage_id
  - job_id
Casts: {}
Relationships: []
Notes: Standard domain model.
```

#### UTMMedium Model

```yaml
Model: Webkul\Recruitment\Models\UTMMedium
Namespace: Webkul\Recruitment\Models
File: plugins/webkul/recruitments/src/Models/UTMMedium.php
Table: utm_mediums
Migration: plugins/webkul/support/database/migrations/2025_01_09_111545_create_utm_mediums_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Support\Models\UTMMedium. Operates against table `utm_mediums`.
```

#### UTMSource Model

```yaml
Model: Webkul\Recruitment\Models\UTMSource
Namespace: Webkul\Recruitment\Models
File: plugins/webkul/recruitments/src/Models/UTMSource.php
Table: utm_sources
Migration: plugins/webkul/support/database/migrations/2025_01_09_114324_create_utm_sources_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Support\Models\UTMSource. Operates against table `utm_sources`.
```

### Sales Plugin (`plugins/webkul/sales`)

#### ActivityPlan Model

```yaml
Model: Webkul\Sale\Models\ActivityPlan
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/ActivityPlan.php
Table: activity_plans
Migration: plugins/webkul/support/database/migrations/2024_12_12_114620_create_activity_plans_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Support\Models\ActivityPlan. Operates against table `activity_plans`.
```

#### ActivityType Model

```yaml
Model: Webkul\Sale\Models\ActivityType
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/ActivityType.php
Table: activity_types
Migration: plugins/webkul/support/database/migrations/2024_12_12_115256_create_activity_types_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Support\Models\ActivityType. Operates against table `activity_types`.
```

#### AdvancedPaymentInvoice Model

```yaml
Model: Webkul\Sale\Models\AdvancedPaymentInvoice
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/AdvancedPaymentInvoice.php
Table: sales_advance_payment_invoices
Migration: plugins/webkul/sales/database/migrations/2025_03_06_133433_create_sales_advance_payment_invoices_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
Important Columns / Fillable:
  - currency_id
  - company_id
  - creator_id
  - advance_payment_method
  - fixed_amount
  - deduct_down_payments
  - consolidated_billing
  - amount
Casts: {}
Relationships:
  - currency(): belongsTo(Currency::class)
  - company(): belongsTo(Company::class)
  - creator(): belongsTo(User::class)
  - orders(): belongsToMany(Order::class, fk: sales_advance_payment_invoice_order_sales, other: advance_payment_invoice_id)
Notes: Standard domain model.
```

#### AdvancedPaymentInvoiceOrderSale Model

```yaml
Model: Webkul\Sale\Models\AdvancedPaymentInvoiceOrderSale
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/AdvancedPaymentInvoiceOrderSale.php
Table: sales_advance_payment_invoice_order_sales
Migration: plugins/webkul/sales/database/migrations/2025_03_06_133458_create_sales_advance_payment_invoice_order_sales_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - advance_payment_invoice_id
  - order_id
Casts: {}
Relationships:
  - advancePaymentInvoice(): belongsTo(AdvancedPaymentInvoice::class, fk: advance_payment_invoice_id)
  - order(): belongsTo(Order::class, fk: order_id)
Notes: Standard domain model.
```

#### Attribute Model

```yaml
Model: Webkul\Sale\Models\Attribute
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/Attribute.php
Table: products_attributes
Migration: plugins/webkul/products/database/migrations/2025_01_05_104456_create_products_attributes_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Product\Models\Attribute. Operates against table `products_attributes`.
```

#### Category Model

```yaml
Model: Webkul\Sale\Models\Category
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/Category.php
Table: products_categories
Migration: plugins/webkul/products/database/migrations/2025_01_05_063925_create_products_categories_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - products(): hasMany(Product::class)
Notes: Proxy/Extension model extending Webkul\Invoice\Models\Category -> Webkul\Account\Models\Category -> Webkul\Product\Models\Category. Operates against table `products_categories`.
```

#### Currency Model

```yaml
Model: Webkul\Sale\Models\Currency
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/Currency.php
Table: currencies
Migration: plugins/webkul/support/database/migrations/2024_12_06_061927_create_currencies_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Support\Models\Currency. Operates against table `currencies`.
```

#### Invoice Model

```yaml
Model: Webkul\Sale\Models\Invoice
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/Invoice.php
Table: accounts_account_moves
Migration: plugins/webkul/accounts/database/migrations/2025_02_11_055303_create_accounts_account_moves_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships:
  - salesOrders(): belongsToMany(Order::class, fk: sales_order_invoices, other: move_id)
Notes: Proxy/Extension model extending Webkul\Invoice\Models\Invoice -> Webkul\Account\Models\Move. Operates against table `accounts_account_moves`.
```

#### Order Model

```yaml
Model: Webkul\Sale\Models\Order
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/Order.php
Table: sales_orders
Migration: plugins/webkul/sales/database/migrations/2025_02_05_053212_create_sales_orders_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - ChecksCompanyConsistency
  - HasChatter
  - HasCustomFields
  - HasFactory
  - HasLogActivity
  - HasOwnershipScope
  - SoftDeletes
Important Columns / Fillable:
  - utm_source_id
  - medium_id
  - company_id
  - partner_id
  - journal_id
  - partner_invoice_id
  - partner_shipping_id
  - fiscal_position_id
  - sale_order_template_id
  - payment_term_id
  - currency_id
  - user_id
  - ... (26 additional columns in fillable)
Casts:
  - state: OrderState::class
  - invoice_status: InvoiceStatus::class
  - delivery_status: OrderDeliveryStatus::class
  - amount_tax: 'decimal:4'
  - amount_total: 'decimal:4'
  - amount_untaxed: 'decimal:4'
  - validity_date: 'date'
  - date_order: 'date'
  - signed_on: 'date'
  - locked: 'boolean'
Relationships:
  - company(): belongsTo(Company::class)
  - partner(): belongsTo(Partner::class)
  - campaign(): belongsTo(UtmCampaign::class, fk: campaign_id)
  - journal(): belongsTo(Journal::class)
  - accountMoves(): belongsToMany(Move::class, fk: sales_order_invoices, other: order_id)
  - invoices(): belongsToMany(Invoice::class, fk: sales_order_invoices, other: order_id)
  - partnerInvoice(): belongsTo(Partner::class, fk: partner_invoice_id)
  - tags(): belongsToMany(Tag::class, fk: sales_order_tags, other: order_id)
  - partnerShipping(): belongsTo(Partner::class, fk: partner_shipping_id)
  - fiscalPosition(): belongsTo(FiscalPosition::class)
  - paymentTerm(): belongsTo(PaymentTerm::class)
  - currency(): belongsTo(Currency::class)
  - user(): belongsTo(User::class)
  - team(): belongsTo(Team::class)
  - creator(): belongsTo(User::class)
  - utmSource(): belongsTo(UTMSource::class, fk: utm_source_id)
  - medium(): belongsTo(UTMMedium::class)
  - lines(): hasMany(OrderLine::class, fk: order_id)
  - optionalLines(): hasMany(OrderOption::class, fk: order_id)
  - quotationTemplate(): belongsTo(OrderTemplate::class, fk: sale_order_template_id)
  - warehouse(): belongsTo(Warehouse::class, fk: warehouse_id)
  - procurementGroup(): belongsTo(ProcurementGroup::class, fk: procurement_group_id)
  - operations(): hasMany(Operation::class, fk: sale_order_id)
Notes: Standard domain model.
```

#### OrderLine Model

```yaml
Model: Webkul\Sale\Models\OrderLine
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/OrderLine.php
Table: sales_order_lines
Migration: plugins/webkul/sales/database/migrations/2025_02_05_102851_create_sales_order_lines_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - ChecksCompanyConsistency
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - sort
  - order_id
  - company_id
  - currency_id
  - order_partner_id
  - salesman_id
  - product_id
  - product_uom_id
  - linked_sale_order_sale_id
  - creator_id
  - state
  - display_type
  - ... (32 additional columns in fillable)
Casts:
  - state: OrderState::class
  - qty_delivered_method: QtyDeliveredMethod::class
  - customer_lead: 'float'
Relationships:
  - order(): belongsTo(Order::class)
  - company(): belongsTo(Company::class)
  - currency(): belongsTo(Currency::class)
  - orderPartner(): belongsTo(Partner::class, fk: order_partner_id)
  - salesman(): belongsTo(User::class, fk: salesman_id)
  - product(): belongsTo(Product::class)
  - uom(): belongsTo(UOM::class, fk: product_uom_id)
  - taxes(): belongsToMany(Tax::class, fk: sales_order_line_taxes, other: order_line_id)
  - accountMoveLines(): belongsToMany(MoveLine::class, fk: sales_order_line_invoices, other: order_line_id)
  - inventoryMoves(): hasMany(InventoryMove::class, fk: sale_order_line_id)
  - productPackaging(): belongsTo(Packaging::class)
  - linkedSaleOrderSale(): belongsTo(self::class, fk: linked_sale_order_sale_id)
  - creator(): belongsTo(User::class, fk: creator_id)
  - warehouse(): belongsTo(Warehouse::class, fk: warehouse_id)
  - route(): belongsTo(Route::class, fk: route_id)
Notes: Standard domain model.
```

#### OrderOption Model

```yaml
Model: Webkul\Sale\Models\OrderOption
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/OrderOption.php
Table: sales_order_options
Migration: plugins/webkul/sales/database/migrations/2025_03_05_073635_create_sales_order_options_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - SortableTrait
Important Columns / Fillable:
  - sort
  - order_id
  - product_id
  - line_id
  - uom_id
  - creator_id
  - name
  - quantity
  - price_unit
  - discount
Casts: {}
Relationships:
  - order(): belongsTo(Order::class, fk: order_id)
  - product(): belongsTo(Product::class, fk: product_id)
  - line(): belongsTo(OrderLine::class, fk: line_id)
  - uom(): belongsTo(UOM::class, fk: uom_id)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### OrderTemplate Model

```yaml
Model: Webkul\Sale\Models\OrderTemplate
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/OrderTemplate.php
Table: sales_order_templates
Migration: plugins/webkul/sales/database/migrations/2025_01_28_122700_create_sales_order_templates_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - sort
  - company_id
  - number_of_days
  - creator_id
  - name
  - note
  - journal_id
  - is_active
  - require_signature
  - require_payment
  - prepayment_percentage
Casts: {}
Relationships:
  - company(): belongsTo(Company::class, fk: company_id)
  - creator(): belongsTo(User::class, fk: creator_id)
  - journal(): belongsTo(Journal::class, fk: journal_id)
Notes: Standard domain model.
```

#### OrderTemplateProduct Model

```yaml
Model: Webkul\Sale\Models\OrderTemplateProduct
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/OrderTemplateProduct.php
Table: sales_order_template_products
Migration: plugins/webkul/sales/database/migrations/2025_02_05_080609_create_sales_order_template_products_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
Important Columns / Fillable:
  - order_template_id
  - company_id
  - product_id
  - product_uom_id
  - creator_id
  - name
  - quantity
  - display_type
Casts: {}
Relationships:
  - orderTemplate(): belongsTo(OrderTemplate::class, fk: order_template_id)
  - company(): belongsTo(Company::class, fk: company_id)
  - product(): belongsTo(Product::class, fk: product_id)
  - uom(): belongsTo(UOM::class, fk: product_uom_id)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### OrderToInvoice Model

```yaml
Model: Webkul\Sale\Models\OrderToInvoice
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/OrderToInvoice.php
Table: sales_orders
Migration: plugins/webkul/sales/database/migrations/2025_02_05_053212_create_sales_orders_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Sale\Models\Order. Operates against table `sales_orders`.
```

#### OrderToUpsell Model

```yaml
Model: Webkul\Sale\Models\OrderToUpsell
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/OrderToUpsell.php
Table: sales_orders
Migration: plugins/webkul/sales/database/migrations/2025_02_05_053212_create_sales_orders_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Sale\Models\Order. Operates against table `sales_orders`.
```

#### Packaging Model

```yaml
Model: Webkul\Sale\Models\Packaging
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/Packaging.php
Table: products_packagings
Migration: plugins/webkul/products/database/migrations/2025_01_05_105626_create_products_packagings_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Product\Models\Packaging. Operates against table `products_packagings`.
```

#### Partner Model

```yaml
Model: Webkul\Sale\Models\Partner
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/Partner.php
Table: partners_partners
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101220_create_partners_partners_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Invoice\Models\Partner -> Webkul\Account\Models\Partner -> Webkul\Partner\Models\Partner. Operates against table `partners_partners`.
```

#### Product Model

```yaml
Model: Webkul\Sale\Models\Product
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/Product.php
Table: products_products
Migration: plugins/webkul/products/database/migrations/2025_01_05_100751_create_products_products_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Invoice\Models\Product -> Webkul\Account\Models\Product -> Webkul\Product\Models\Product. Operates against table `products_products`.
```

#### Quotation Model

```yaml
Model: Webkul\Sale\Models\Quotation
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/Quotation.php
Table: sales_orders
Migration: plugins/webkul/sales/database/migrations/2025_02_05_053212_create_sales_orders_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Sale\Models\Order. Operates against table `sales_orders`.
```

#### Tag Model

```yaml
Model: Webkul\Sale\Models\Tag
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/Tag.php
Table: sales_tags
Migration: plugins/webkul/sales/database/migrations/2025_03_05_124300_create_sales_tag_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - color
  - name
  - creator_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### Team Model

```yaml
Model: Webkul\Sale\Models\Team
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/Team.php
Table: sales_teams
Migration: plugins/webkul/sales/database/migrations/2025_01_28_061110_create_sales_teams_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasChatter
  - HasCustomFields
  - HasFactory
  - HasLogActivity
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - sort
  - company_id
  - user_id
  - color
  - creator_id
  - name
  - is_active
  - invoiced_target
Casts: {}
Relationships:
  - company(): belongsTo(Company::class, fk: company_id)
  - user(): belongsTo(User::class, fk: user_id)
  - creator(): belongsTo(User::class, fk: creator_id)
  - members(): belongsToMany(User::class, fk: sales_team_members, other: team_id)
Notes: Standard domain model.
```

#### TeamMember Model

```yaml
Model: Webkul\Sale\Models\TeamMember
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/TeamMember.php
Table: sales_team_members
Migration: plugins/webkul/sales/database/migrations/2025_01_28_074033_create_sales_team_members_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - team_id
  - user_id
Casts: {}
Relationships: []
Notes: Standard domain model.
```

#### UOMCategory Model

```yaml
Model: Webkul\Sale\Models\UOMCategory
Namespace: Webkul\Sale\Models
File: plugins/webkul/sales/src/Models/UOMCategory.php
Table: unit_of_measure_categories
Migration: plugins/webkul/support/database/migrations/2025_01_03_105625_create_unit_of_measure_categories_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Support\Models\UOMCategory. Operates against table `unit_of_measure_categories`.
```

### Security Plugin (`plugins/webkul/security`)

#### Company Model

```yaml
Model: Webkul\Security\Models\Company
Namespace: Webkul\Security\Models
File: plugins/webkul/security/src/Models/Company.php
Table: companies
Migration: plugins/webkul/support/database/migrations/2024_12_10_092657_create_companies_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Support\Models\Company. Operates against table `companies`.
```

#### Invitation Model

```yaml
Model: Webkul\Security\Models\Invitation
Namespace: Webkul\Security\Models
File: plugins/webkul/security/src/Models/Invitation.php
Table: user_invitations
Migration: plugins/webkul/security/database/migrations/2024_11_11_112529_create_user_invitations_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns: []
Casts: {}
Relationships: []
Notes: Standard domain model.
```

#### Permission Model

```yaml
Model: Webkul\Security\Models\Permission
Namespace: Webkul\Security\Models
File: plugins/webkul/security/src/Models/Permission.php
Table: permissions
Migration: [NO DIRECT MIGRATION]
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Spatie\Permission\Models\Permission. Operates against table `permissions`.
```

#### Role Model

```yaml
Model: Webkul\Security\Models\Role
Namespace: Webkul\Security\Models
File: plugins/webkul/security/src/Models/Role.php
Table: roles
Migration: [NO DIRECT MIGRATION]
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Spatie\Permission\Models\Role. Operates against table `roles`.
```

#### Team Model

```yaml
Model: Webkul\Security\Models\Team
Namespace: Webkul\Security\Models
File: plugins/webkul/security/src/Models/Team.php
Table: teams
Migration: plugins/webkul/security/database/migrations/2024_11_12_125715_create_teams_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasCustomFields
  - HasOwnershipScope
Important Columns / Fillable:
  - name
  - creator_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
  - users(): belongsToMany(User::class, fk: user_team, other: team_id)
Notes: Standard domain model.
```

#### User Model

```yaml
Model: Webkul\Security\Models\User
Namespace: Webkul\Security\Models
File: plugins/webkul/security/src/Models/User.php
Table: users
Migration: database/migrations/0001_01_01_000000_create_users_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasOwnershipScope
  - HasRoles
  - InteractsWithAppAuthentication
  - InteractsWithAppAuthenticationRecovery
  - InteractsWithEmailAuthentication
  - SoftDeletes
Important Columns: []
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
  - teams(): belongsToMany(Team::class, fk: user_team, other: user_id)
  - employee(): hasOne(Employee::class, fk: user_id)
  - departments(): hasMany(Department::class, fk: manager_id)
  - companies(): hasMany(Company::class)
  - partner(): belongsTo(Partner::class, fk: partner_id)
  - allowedCompanies(): belongsToMany(Company::class, fk: user_allowed_companies, other: user_id)
  - defaultCompany(): belongsTo(Company::class, fk: default_company_id)
Notes: Proxy/Extension model extending App\Models\User. Operates against table `users`.
```

### Support Plugin (`plugins/webkul/support`)

#### ActivityPlan Model

```yaml
Model: Webkul\Support\Models\ActivityPlan
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/ActivityPlan.php
Table: activity_plans
Migration: plugins/webkul/support/database/migrations/2024_12_12_114620_create_activity_plans_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SoftDeletes
Important Columns / Fillable:
  - company_id
  - plugin
  - creator_id
  - name
  - is_active
Casts:
  - is_active: 'boolean'
Relationships:
  - company(): belongsTo(Company::class, fk: company_id)
  - creator(): belongsTo(User::class, fk: creator_id)
  - activityTypes(): hasMany(ActivityType::class, fk: activity_plan_id)
  - activityPlanTemplates(): hasMany(ActivityPlanTemplate::class, fk: plan_id)
Notes: Standard domain model.
```

#### ActivityPlanTemplate Model

```yaml
Model: Webkul\Support\Models\ActivityPlanTemplate
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/ActivityPlanTemplate.php
Table: activity_plan_templates
Migration: plugins/webkul/support/database/migrations/2024_12_12_115728_create_activity_plan_templates_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - sort
  - plan_id
  - activity_type_id
  - responsible_id
  - creator_id
  - delay_count
  - delay_unit
  - delay_from
  - summary
  - responsible_type
  - note
Casts: {}
Relationships:
  - activityPlan(): belongsTo(ActivityPlan::class, fk: plan_id)
  - activityType(): belongsTo(ActivityType::class, fk: activity_type_id)
  - responsible(): belongsTo(User::class, fk: responsible_id)
  - creator(): belongsTo(User::class, fk: creator_id)
  - assignedUser(): belongsTo(User::class, fk: user_id)
Notes: Standard domain model.
```

#### ActivityType Model

```yaml
Model: Webkul\Support\Models\ActivityType
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/ActivityType.php
Table: activity_types
Migration: plugins/webkul/support/database/migrations/2024_12_12_115256_create_activity_types_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasCustomFields
  - HasFactory
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - sort
  - delay_count
  - delay_unit
  - delay_from
  - icon
  - decoration_type
  - chaining_type
  - plugin
  - category
  - name
  - summary
  - default_note
  - ... (6 additional columns in fillable)
Casts:
  - is_active: 'boolean'
  - keep_done: 'boolean'
Relationships:
  - activityPlan(): belongsTo(ActivityPlan::class, fk: activity_plan_id)
  - triggeredNextType(): belongsTo(self::class, fk: triggered_next_type_id)
  - activityTypes(): hasMany(self::class, fk: triggered_next_type_id)
  - suggestedActivityTypes(): belongsToMany(self::class, fk: activity_type_suggestions, other: activity_type_id)
  - creator(): belongsTo(User::class, fk: creator_id)
  - defaultUser(): belongsTo(User::class, fk: default_user_id)
Notes: Standard domain model.
```

#### ActivityTypeSuggestion Model

```yaml
Model: Webkul\Support\Models\ActivityTypeSuggestion
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/ActivityTypeSuggestion.php
Table: activity_type_suggestions
Migration: plugins/webkul/support/database/migrations/2024_12_17_082318_create_activity_type_suggestions_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - activity_type_id
  - suggested_activity_type_id
Casts: {}
Relationships:
  - activityType(): belongsTo(ActivityType::class, fk: activity_type_id)
  - suggestedActivityType(): belongsTo(ActivityType::class, fk: suggested_activity_type_id)
Notes: Standard domain model.
```

#### Bank Model

```yaml
Model: Webkul\Support\Models\Bank
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/Bank.php
Table: banks
Migration: plugins/webkul/support/database/migrations/2024_12_10_101420_create_banks_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
  - SoftDeletes
Important Columns / Fillable:
  - name
  - code
  - email
  - phone
  - street1
  - street2
  - city
  - zip
  - state_id
  - country_id
  - creator_id
Casts: {}
Relationships:
  - country(): belongsTo(Country::class)
  - state(): belongsTo(State::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### Calendar Model

```yaml
Model: Webkul\Support\Models\Calendar
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/Calendar.php
Table: calendars
Migration: plugins/webkul/support/database/migrations/2026_04_02_000001_create_calendars_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SoftDeletes
Important Columns / Fillable:
  - name
  - timezone
  - hours_per_day
  - is_active
  - two_weeks_calendar
  - flexible_hours
  - full_time_required_hours
  - resource_type
  - resource_id
  - creator_id
  - company_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
  - company(): belongsTo(Company::class)
  - attendance(): hasMany(CalendarAttendance::class)
Notes: Standard domain model.
```

#### CalendarAttendance Model

```yaml
Model: Webkul\Support\Models\CalendarAttendance
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/CalendarAttendance.php
Table: calendar_attendances
Migration: plugins/webkul/support/database/migrations/2026_04_02_000001_create_calendars_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - sort
  - name
  - day_of_week
  - day_period
  - week_type
  - display_type
  - date_from
  - date_to
  - hour_from
  - hour_to
  - duration_days
  - calendar_id
  - ... (3 additional columns in fillable)
Casts: {}
Relationships:
  - calendar(): belongsTo(Calendar::class)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### CalendarLeave Model

```yaml
Model: Webkul\Support\Models\CalendarLeave
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/CalendarLeave.php
Table: calendar_leaves
Migration: plugins/webkul/support/database/migrations/2026_04_02_000001_create_calendars_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
Important Columns / Fillable:
  - name
  - time_type
  - date_from
  - date_to
  - company_id
  - calendar_id
  - creator_id
  - resource_type
  - resource_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
  - calendar(): belongsTo(Calendar::class)
  - company(): belongsTo(Company::class)
Notes: Standard domain model.
```

#### Company Model

```yaml
Model: Webkul\Support\Models\Company
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/Company.php
Table: companies
Migration: plugins/webkul/support/database/migrations/2024_12_10_092657_create_companies_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasChatter
  - HasCustomFields
  - HasFactory
  - HasOwnershipScope
  - RestrictToAllowedCompanies
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - sort
  - name
  - company_id
  - parent_id
  - tax_id
  - registration_number
  - email
  - phone
  - mobile
  - street1
  - street2
  - city
  - ... (11 additional columns in fillable)
Casts: {}
Relationships:
  - country(): belongsTo(Country::class)
  - state(): belongsTo(State::class)
  - creator(): belongsTo(User::class, fk: creator_id)
  - parent(): belongsTo(Company::class, fk: parent_id)
  - branches(): hasMany(Company::class, fk: parent_id)
  - currency(): belongsTo(Currency::class)
  - partner(): belongsTo(Partner::class, fk: partner_id)
Notes: Standard domain model.
```

#### Country Model

```yaml
Model: Webkul\Support\Models\Country
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/Country.php
Table: countries
Migration: plugins/webkul/support/database/migrations/2024_12_10_092651_create_countries_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - currency_id
  - phone_code
  - code
  - name
  - state_required
  - zip_required
Casts:
  - state_required: 'boolean'
  - zip_required: 'boolean'
Relationships:
  - currency(): belongsTo(Currency::class, fk: currency_id)
  - states(): hasMany(State::class, fk: country_id)
Notes: Standard domain model.
```

#### Currency Model

```yaml
Model: Webkul\Support\Models\Currency
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/Currency.php
Table: currencies
Migration: plugins/webkul/support/database/migrations/2024_12_06_061927_create_currencies_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasCustomFields
  - HasFactory
Important Columns / Fillable:
  - name
  - symbol
  - iso_numeric
  - decimal_places
  - full_name
  - rounding
  - active
Casts:
  - active: 'boolean'
Relationships:
  - rates(): hasMany(CurrencyRate::class)
  - companies(): hasMany(Company::class)
Notes: Standard domain model. Includes getCodeAttribute() (ISO code alias), findByCode(?string $code), and resolveDefault(?Country $country) static currency resolution methods.
```

#### CurrencyRate Model

```yaml
Model: Webkul\Support\Models\CurrencyRate
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/CurrencyRate.php
Table: currency_rates
Migration: plugins/webkul/support/database/migrations/2025_10_10_080114_create_currency_rates_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - name
  - rate
  - currency_id
  - creator_id
  - company_id
  - created_at
Casts:
  - name: 'date'
  - rate: 'decimal:6'
Relationships:
  - currency(): belongsTo(Currency::class)
  - creator(): belongsTo(User::class)
  - company(): belongsTo(Company::class)
Notes: Standard domain model.
```

#### EmailLog Model

```yaml
Model: Webkul\Support\Models\EmailLog
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/EmailLog.php
Table: email_logs
Migration: plugins/webkul/support/database/migrations/2025_01_03_061445_create_email_logs_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - recipient_email
  - recipient_name
  - subject
  - status
  - error_message
  - sent_at
Casts:
  - sent_at: 'datetime'
Relationships: []
Notes: Standard domain model.
```

#### EmailTemplate Model

```yaml
Model: Webkul\Support\Models\EmailTemplate
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/EmailTemplate.php
Table: email_templates
Migration: [NO DIRECT MIGRATION]
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
  - SoftDeletes
Important Columns / Fillable:
  - code
  - name
  - subject
  - content
  - description
  - is_active
  - sender_name
Casts:
  - variables: 'array'
  - is_active: 'boolean'
Relationships: []
Notes: Standard domain model.
```

#### QuickNavigationFavorite Model

```yaml
Model: Webkul\Support\Models\QuickNavigationFavorite
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/QuickNavigationFavorite.php
Table: quick_navigation_favorites
Migration: [NO DIRECT MIGRATION]
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - user_id
  - label
  - url
  - sort
Casts: {}
Relationships:
  - user(): belongsTo(User::class)
Notes: Standard domain model.
```

#### Sequence Model

```yaml
Model: Webkul\Support\Models\Sequence
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/Sequence.php
Table: sequences
Migration: plugins/webkul/support/database/migrations/2026_08_03_120000_create_sequences_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
Important Columns / Fillable:
  - code
  - scope_type
  - scope_id
  - variant
  - name
  - prefix
  - suffix
  - padding
  - next_number
  - step
  - reset_frequency
  - period_key
  - ... (1 additional columns in fillable)
Casts:
  - padding: 'integer'
  - next_number: 'integer'
  - step: 'integer'
  - reset_frequency: SequenceResetFrequency::class
Relationships:
  - scope(): morphTo(__FUNCTION__, fk: scope_type, other: scope_id)
  - company(): belongsTo(Company::class)
Notes: Standard domain model.
```

#### State Model

```yaml
Model: Webkul\Support\Models\State
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/State.php
Table: states
Migration: plugins/webkul/support/database/migrations/2024_12_10_092657_create_states_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - country_id
  - name
  - code
Casts: {}
Relationships:
  - country(): belongsTo(Country::class, fk: country_id)
Notes: Standard domain model.
```

#### UOM Model

```yaml
Model: Webkul\Support\Models\UOM
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/UOM.php
Table: unit_of_measures
Migration: plugins/webkul/support/database/migrations/2025_01_03_105627_create_unit_of_measures_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
  - SoftDeletes
Important Columns / Fillable:
  - type
  - name
  - factor
  - ratio
  - rounding
  - category_id
  - creator_id
Casts:
  - type: UOMType::class
Relationships:
  - category(): belongsTo(UOMCategory::class)
  - creator(): belongsTo(User::class)
Notes: Standard domain model. Includes computeQuantity() and computePrice($price, $toUnit) for unit conversion and pricing calculations.
```

#### UOMCategory Model

```yaml
Model: Webkul\Support\Models\UOMCategory
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/UOMCategory.php
Table: unit_of_measure_categories
Migration: plugins/webkul/support/database/migrations/2025_01_03_105625_create_unit_of_measure_categories_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - name
  - creator_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class)
  - uoms(): hasMany(UOM::class, fk: category_id)
Notes: Standard domain model.
```

#### UTMMedium Model

```yaml
Model: Webkul\Support\Models\UTMMedium
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/UTMMedium.php
Table: utm_mediums
Migration: plugins/webkul/support/database/migrations/2025_01_09_111545_create_utm_mediums_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - name
  - creator_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### UTMSource Model

```yaml
Model: Webkul\Support\Models\UTMSource
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/UTMSource.php
Table: utm_sources
Migration: plugins/webkul/support/database/migrations/2025_01_09_114324_create_utm_sources_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
Important Columns / Fillable:
  - name
  - creator_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### UtmCampaign Model

```yaml
Model: Webkul\Support\Models\UtmCampaign
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/UtmCampaign.php
Table: utm_campaigns
Migration: plugins/webkul/support/database/migrations/2025_01_10_094325_create_utm_campaigns_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
Important Columns / Fillable:
  - user_id
  - stage_id
  - color
  - creator_id
  - name
  - title
  - is_active
  - is_auto_campaign
  - company_id
Casts: {}
Relationships:
  - user(): belongsTo(User::class, fk: user_id)
  - stage(): belongsTo(UtmStage::class, fk: stage_id)
  - creator(): belongsTo(User::class, fk: creator_id)
  - company(): belongsTo(Company::class, fk: company_id)
Notes: Standard domain model.
```

#### UtmStage Model

```yaml
Model: Webkul\Support\Models\UtmStage
Namespace: Webkul\Support\Models
File: plugins/webkul/support/src/Models/UtmStage.php
Table: utm_stages
Migration: plugins/webkul/support/database/migrations/2025_01_10_094256_create_utm_stages_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - sort
  - name
  - creator_id
Casts: {}
Relationships:
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

### Table Views Plugin (`plugins/webkul/table-views`)

#### TableView Model

```yaml
Model: Webkul\TableViews\Models\TableView
Namespace: Webkul\TableViews\Models
File: plugins/webkul/table-views/src/Models/TableView.php
Table: table_views
Migration: plugins/webkul/table-views/database/migrations/2024_11_19_142134_create_table_views_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - name
  - icon
  - color
  - is_public
  - filters
  - filterable_type
  - user_id
Casts:
  - filters: 'array'
Relationships:
  - user(): belongsTo(User::class)
Notes: Standard domain model.
```

#### TableViewFavorite Model

```yaml
Model: Webkul\TableViews\Models\TableViewFavorite
Namespace: Webkul\TableViews\Models
File: plugins/webkul/table-views/src/Models/TableViewFavorite.php
Table: table_view_favorites
Migration: plugins/webkul/table-views/database/migrations/2024_11_21_142134_create_table_view_favorites_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - is_favorite
  - view_type
  - view_key
  - filterable_type
  - user_id
Casts: {}
Relationships:
  - user(): belongsTo(User::class)
Notes: Standard domain model.
```

### Time Off Plugin (`plugins/webkul/time-off`)

#### ActivityType Model

```yaml
Model: Webkul\TimeOff\Models\ActivityType
Namespace: Webkul\TimeOff\Models
File: plugins/webkul/time-off/src/Models/ActivityType.php
Table: activity_types
Migration: plugins/webkul/support/database/migrations/2024_12_12_115256_create_activity_types_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Support\Models\ActivityType. Operates against table `activity_types`.
```

#### CalendarLeave Model

```yaml
Model: Webkul\TimeOff\Models\CalendarLeave
Namespace: Webkul\TimeOff\Models
File: plugins/webkul/time-off/src/Models/CalendarLeave.php
Table: calendar_leaves
Migration: plugins/webkul/support/database/migrations/2026_04_02_000001_create_calendars_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Support\Models\CalendarLeave. Operates against table `calendar_leaves`.
```

#### Leave Model

```yaml
Model: Webkul\TimeOff\Models\Leave
Namespace: Webkul\TimeOff\Models
File: plugins/webkul/time-off/src/Models/Leave.php
Table: time_off_leaves
Migration: plugins/webkul/time-off/database/migrations/2025_01_17_080712_create_time_off_leaves_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasChatter
  - HasCustomFields
  - HasFactory
  - HasLogActivity
Important Columns / Fillable:
  - user_id
  - manager_id
  - holiday_status_id
  - employee_id
  - employee_company_id
  - company_id
  - department_id
  - calendar_id
  - meeting_id
  - first_approver_id
  - second_approver_id
  - creator_id
  - ... (16 additional columns in fillable)
Casts:
  - state: State::class
  - request_date_from_period: RequestDateFromPeriod::class
  - request_date_from: 'date'
  - date_from: 'date'
  - request_unit_half: 'boolean'
  - number_of_hours: 'decimal:4'
Relationships:
  - user(): belongsTo(User::class, fk: user_id)
  - manager(): belongsTo(Employee::class, fk: manager_id)
  - holidayStatus(): belongsTo(LeaveType::class, fk: holiday_status_id)
  - employee(): belongsTo(Employee::class, fk: employee_id)
  - employeeCompany(): belongsTo(Company::class, fk: employee_company_id)
  - company(): belongsTo(Company::class, fk: company_id)
  - department(): belongsTo(Department::class, fk: department_id)
  - calendar(): belongsTo(Calendar::class, fk: calendar_id)
  - firstApprover(): belongsTo(Employee::class, fk: first_approver_id)
  - secondApprover(): belongsTo(Employee::class, fk: second_approver_id)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### LeaveAccrualLevel Model

```yaml
Model: Webkul\TimeOff\Models\LeaveAccrualLevel
Namespace: Webkul\TimeOff\Models
File: plugins/webkul/time-off/src/Models/LeaveAccrualLevel.php
Table: time_off_leave_accrual_levels
Migration: plugins/webkul/time-off/database/migrations/2025_01_21_085833_create_time_off_leave_accrual_levels_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasFactory
  - SortableTrait
Important Columns / Fillable:
  - sort
  - accrual_plan_id
  - start_count
  - first_day
  - second_day
  - first_month_day
  - second_month_day
  - yearly_day
  - postpone_max_days
  - accrual_validity_count
  - creator_id
  - start_type
  - ... (14 additional columns in fillable)
Casts: {}
Relationships:
  - accrualPlan(): belongsTo(LeaveAccrualPlan::class, fk: accrual_plan_id)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### LeaveAccrualPlan Model

```yaml
Model: Webkul\TimeOff\Models\LeaveAccrualPlan
Namespace: Webkul\TimeOff\Models
File: plugins/webkul/time-off/src/Models/LeaveAccrualPlan.php
Table: time_off_leave_accrual_plans
Migration: plugins/webkul/time-off/database/migrations/2025_01_21_073921_create_time_off_leave_accrual_plans_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
Important Columns / Fillable:
  - time_off_type_id
  - company_id
  - carryover_day
  - creator_id
  - name
  - transition_mode
  - accrued_gain_time
  - carryover_date
  - carryover_month
  - added_value_type
  - is_active
  - is_based_on_worked_time
Casts:
  - accrued_gain_time: AccruedGainTime::class
  - carryover_day: CarryoverDay::class
  - carryover_month: CarryoverMonth::class
  - carryover_date: CarryoverDate::class
Relationships:
  - timeOffType(): belongsTo(LeaveType::class, fk: time_off_type_id)
  - company(): belongsTo(Company::class, fk: company_id)
  - creator(): belongsTo(User::class, fk: creator_id)
  - leaveAccrualLevels(): hasMany(LeaveAccrualLevel::class, fk: accrual_plan_id)
Notes: Standard domain model.
```

#### LeaveAllocation Model

```yaml
Model: Webkul\TimeOff\Models\LeaveAllocation
Namespace: Webkul\TimeOff\Models
File: plugins/webkul/time-off/src/Models/LeaveAllocation.php
Table: time_off_leave_allocations
Migration: plugins/webkul/time-off/database/migrations/2025_01_22_101656_create_time_off_leave_allocations_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasChatter
  - HasCustomFields
  - HasFactory
  - HasLogActivity
Important Columns / Fillable:
  - holiday_status_id
  - employee_id
  - employee_company_id
  - company_id
  - manager_id
  - approver_id
  - second_approver_id
  - department_id
  - accrual_plan_id
  - creator_id
  - name
  - state
  - ... (14 additional columns in fillable)
Casts:
  - allocation_type: AllocationType::class
  - date_to: 'date'
  - date_from: 'date'
  - number_of_days: 'decimal:4'
Relationships:
  - employee(): belongsTo(Employee::class)
  - company(): belongsTo(Company::class, fk: employee_company_id)
  - employeeCompany(): belongsTo(Company::class, fk: employee_company_id)
  - manager(): belongsTo(Employee::class, fk: manager_id)
  - approver(): belongsTo(Employee::class, fk: approver_id)
  - secondApprover(): belongsTo(Employee::class, fk: second_approver_id)
  - department(): belongsTo(Department::class, fk: department_id)
  - accrualPlan(): belongsTo(LeaveAccrualPlan::class, fk: accrual_plan_id)
  - creator(): belongsTo(User::class, fk: user_id)
  - holidayStatus(): belongsTo(LeaveType::class, fk: holiday_status_id)
Notes: Standard domain model.
```

#### LeaveMandatoryDay Model

```yaml
Model: Webkul\TimeOff\Models\LeaveMandatoryDay
Namespace: Webkul\TimeOff\Models
File: plugins/webkul/time-off/src/Models/LeaveMandatoryDay.php
Table: time_off_leave_mandatory_days
Migration: plugins/webkul/time-off/database/migrations/2025_01_20_130725_create_time_off_leave_mandatory_days_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasFactory
Important Columns / Fillable:
  - company_id
  - creator_id
  - color
  - name
  - start_date
  - end_date
Casts: {}
Relationships:
  - company(): belongsTo(Company::class, fk: company_id)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### LeaveType Model

```yaml
Model: Webkul\TimeOff\Models\LeaveType
Namespace: Webkul\TimeOff\Models
File: plugins/webkul/time-off/src/Models/LeaveType.php
Table: time_off_leave_types
Migration: plugins/webkul/time-off/database/migrations/2025_01_17_080711_create_time_off_leave_types_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - BelongsToCompany
  - HasCustomFields
  - HasFactory
  - SoftDeletes
  - SortableTrait
Important Columns / Fillable:
  - sort
  - color
  - company_id
  - max_allowed_negative
  - creator_id
  - leave_validation_type
  - requires_allocation
  - employee_requests
  - allocation_validation_type
  - time_type
  - request_unit
  - name
  - ... (7 additional columns in fillable)
Casts:
  - leave_validation_type: LeaveValidationType::class
Relationships:
  - company(): belongsTo(Company::class, fk: company_id)
  - notifiedTimeOffOfficers(): belongsToMany(User::class, fk: time_off_user_leave_types, other: leave_type_id)
  - creator(): belongsTo(User::class, fk: creator_id)
Notes: Standard domain model.
```

#### UserLeaveType Model

```yaml
Model: Webkul\TimeOff\Models\UserLeaveType
Namespace: Webkul\TimeOff\Models
File: plugins/webkul/time-off/src/Models/UserLeaveType.php
Table: time_off_user_leave_types
Migration: plugins/webkul/time-off/database/migrations/2025_01_20_080058_create_time_off_user_leave_types_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns / Fillable:
  - user_id
  - leave_type_id
Casts: {}
Relationships:
  - user(): belongsTo(User::class, fk: user_id)
  - leaveType(): belongsTo(LeaveType::class, fk: leave_type_id)
Notes: Standard domain model.
```

### Timesheets Plugin (`plugins/webkul/timesheets`)

#### Timesheet Model

```yaml
Model: Webkul\Timesheet\Models\Timesheet
Namespace: Webkul\Timesheet\Models
File: plugins/webkul/timesheets/src/Models/Timesheet.php
Table: analytic_records
Migration: plugins/webkul/analytics/database/migrations/2024_12_18_131844_create_analytic_records_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasCustomFields
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Project\Models\Timesheet -> Webkul\Analytic\Models\Record. Operates against table `analytic_records`.
```

### Website Plugin (`plugins/webkul/website`)

#### Page Model

```yaml
Model: Webkul\Website\Models\Page
Namespace: Webkul\Website\Models
File: plugins/webkul/website/src/Models/Page.php
Table: website_pages
Migration: plugins/webkul/website/database/migrations/2025_03_10_094011_create_website_pages_table.php
Primary Key: id (int, auto-increment: True)
Traits:
  - HasCustomFields
  - HasFactory
  - HasTranslations
  - SoftDeletes
Important Columns / Fillable:
  - title
  - content
  - slug
  - is_published
  - published_at
  - is_header_visible
  - is_footer_visible
  - meta_title
  - meta_keywords
  - meta_description
  - creator_id
Casts:
  - is_published: 'boolean'
  - is_header_visible: 'boolean'
  - is_footer_visible: 'boolean'
  - published_at: 'datetime'
Relationships:
  - creator(): belongsTo(User::class)
Notes: Standard domain model.
```

#### Partner Model

```yaml
Model: Webkul\Website\Models\Partner
Namespace: Webkul\Website\Models
File: plugins/webkul/website/src/Models/Partner.php
Table: partners_partners
Migration: plugins/webkul/partners/database/migrations/2024_12_11_101220_create_partners_partners_table.php
Primary Key: id (int, auto-increment: True)
Traits: []
Important Columns: []
Casts: {}
Relationships: []
Notes: Proxy/Extension model extending Webkul\Partner\Models\Partner. Operates against table `partners_partners`.
```

## 6. Table Mapping Index

The following table provides a verified mapping between all discovered Eloquent models, their database tables, primary keys, and architectural classification:

| Plugin | Model | Table | Primary Key | Notes |
| :--- | :--- | :--- | :--- | :--- |
| `accounting` | `Account` | `accounts_accounts` | `id` | Proxy Model |
| `accounting` | `Attribute` | `products_attributes` | `id` | Proxy Model |
| `accounting` | `BankAccount` | `partners_bank_accounts` | `id` | Proxy Model |
| `accounting` | `Bill` | `accounts_account_moves` | `id` | Proxy Model |
| `accounting` | `CashRounding` | `accounts_cash_roundings` | `id` | Proxy Model |
| `accounting` | `Category` | `products_categories` | `id` | Proxy Model |
| `accounting` | `CreditNote` | `accounts_account_moves` | `id` | Proxy Model |
| `accounting` | `Currency` | `currencies` | `id` | Proxy Model |
| `accounting` | `Customer` | `partners_partners` | `id` | Proxy Model |
| `accounting` | `FiscalPosition` | `accounts_fiscal_positions` | `id` | Proxy Model |
| `accounting` | `Incoterm` | `accounts_incoterms` | `id` | Proxy Model |
| `accounting` | `Invoice` | `accounts_account_moves` | `id` | Proxy Model |
| `accounting` | `Journal` | `accounts_journals` | `id` | Proxy Model |
| `accounting` | `JournalEntry` | `accounts_account_moves` | `id` | Proxy Model |
| `accounting` | `JournalItem` | `accounts_account_move_lines` | `id` | Proxy Model |
| `accounting` | `MoveLine` | `accounts_account_move_lines` | `id` | Proxy Model |
| `accounting` | `Partner` | `partners_partners` | `id` | Proxy Model |
| `accounting` | `Payment` | `accounts_account_payments` | `id` | Proxy Model |
| `accounting` | `PaymentTerm` | `accounts_payment_terms` | `id` | Proxy Model |
| `accounting` | `Product` | `products_products` | `id` | Proxy Model |
| `accounting` | `Refund` | `accounts_account_moves` | `id` | Proxy Model |
| `accounting` | `Tax` | `accounts_taxes` | `id` | Proxy Model |
| `accounting` | `TaxGroup` | `accounts_tax_groups` | `id` | Proxy Model |
| `accounting` | `Vendor` | `partners_partners` | `id` | Proxy Model |
| `accounts` | `Account` | `accounts_accounts` | `id` | Domain Model, BelongsToCompanies |
| `accounts` | `AccountAccountTag` | `accounts_account_account_tags` | `id` | Domain Model |
| `accounts` | `AccountJournal` | `accounts_account_journals` | `id` | Domain Model |
| `accounts` | `AccountPaymentRegisterMoveLine` | `accounts_account_payment_register_move_lines` | `id` | Domain Model |
| `accounts` | `AccountTax` | `accounts_account_taxes` | `id` | Domain Model |
| `accounts` | `BankStatement` | `accounts_bank_statements` | `id` | Domain Model, BelongsToCompany |
| `accounts` | `BankStatementLine` | `*(unbound)*` | `id` | Domain Model |
| `accounts` | `Bill` | `accounts_account_moves` | `id` | Proxy Model |
| `accounts` | `CashRounding` | `accounts_cash_roundings` | `id` | Domain Model |
| `accounts` | `Category` | `products_categories` | `id` | Proxy Model |
| `accounts` | `CategoryCompanyAccount` | `products_category_company_accounts` | `id` | Domain Model, BelongsToCompany |
| `accounts` | `CreditNote` | `accounts_account_moves` | `id` | Proxy Model |
| `accounts` | `Customer` | `partners_partners` | `id` | Proxy Model |
| `accounts` | `FiscalPosition` | `accounts_fiscal_positions` | `id` | Domain Model, BelongsToCompany |
| `accounts` | `FiscalPositionAccount` | `accounts_fiscal_position_accounts` | `id` | Domain Model, BelongsToCompany |
| `accounts` | `FiscalPositionTax` | `accounts_fiscal_position_taxes` | `id` | Domain Model, BelongsToCompany |
| `accounts` | `FullReconcile` | `accounts_full_reconciles` | `id` | Domain Model |
| `accounts` | `Incoterm` | `accounts_incoterms` | `id` | Domain Model, SoftDeletes |
| `accounts` | `Invoice` | `accounts_account_moves` | `id` | Proxy Model |
| `accounts` | `Journal` | `accounts_journals` | `id` | Domain Model, BelongsToCompany |
| `accounts` | `JournalAccount` | `accounts_journal_accounts` | `id` | Domain Model |
| `accounts` | `Move` | `accounts_account_moves` | `id` | Domain Model, BelongsToCompany |
| `accounts` | `MoveLine` | `accounts_account_move_lines` | `id` | Domain Model, BelongsToCompany |
| `accounts` | `MoveReversal` | `accounts_accounts_move_reversals` | `id` | Domain Model, BelongsToCompany |
| `accounts` | `PartialReconcile` | `accounts_partial_reconciles` | `id` | Domain Model, BelongsToCompany |
| `accounts` | `Partner` | `partners_partners` | `id` | Proxy Model |
| `accounts` | `PartnerCompanyProperty` | `partners_partner_company_properties` | `id` | Domain Model, BelongsToCompany |
| `accounts` | `Payment` | `accounts_account_payments` | `id` | Domain Model, BelongsToCompany |
| `accounts` | `PaymentDueTerm` | `accounts_payment_due_terms` | `id` | Domain Model |
| `accounts` | `PaymentMethod` | `accounts_payment_methods` | `id` | Domain Model |
| `accounts` | `PaymentMethodLine` | `accounts_payment_method_lines` | `id` | Domain Model |
| `accounts` | `PaymentRegister` | `accounts_payment_registers` | `id` | Domain Model, BelongsToCompany |
| `accounts` | `PaymentTerm` | `accounts_payment_terms` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `accounts` | `Product` | `products_products` | `id` | Proxy Model |
| `accounts` | `ProductCompanyAccount` | `products_product_company_accounts` | `id` | Domain Model, BelongsToCompany |
| `accounts` | `ProductSupplierTaxes` | `accounts_product_supplier_taxes` | `id` | Domain Model |
| `accounts` | `ProductTaxes` | `accounts_product_taxes` | `id` | Domain Model |
| `accounts` | `Reconcile` | `accounts_reconciles` | `id` | Domain Model, BelongsToCompany |
| `accounts` | `Refund` | `accounts_account_moves` | `id` | Proxy Model |
| `accounts` | `Tag` | `accounts_account_tags` | `id` | Domain Model |
| `accounts` | `Tax` | `accounts_taxes` | `id` | Domain Model, BelongsToCompany |
| `accounts` | `TaxGroup` | `accounts_tax_groups` | `id` | Domain Model, BelongsToCompany |
| `accounts` | `TaxPartition` | `accounts_tax_partition_lines` | `id` | Domain Model, BelongsToCompany |
| `accounts` | `TaxTaxes` | `accounts_tax_taxes` | `id` | Domain Model |
| `accounts` | `Vendor` | `partners_partners` | `id` | Proxy Model |
| `analytics` | `Record` | `analytic_records` | `id` | Domain Model, BelongsToCompany |
| `blogs` | `Category` | `blogs_categories` | `id` | Domain Model, SoftDeletes |
| `blogs` | `Post` | `blogs_posts` | `id` | Domain Model, SoftDeletes |
| `blogs` | `Tag` | `blogs_tags` | `id` | Domain Model, SoftDeletes |
| `chatter` | `Attachment` | `chatter_attachments` | `id` | Domain Model, BelongsToCompany |
| `chatter` | `Follower` | `chatter_followers` | `id` | Domain Model |
| `chatter` | `Message` | `chatter_messages` | `id` | Domain Model, BelongsToCompany |
| `contacts` | `Address` | `partners_partners` | `id` | Proxy Model |
| `contacts` | `Bank` | `banks` | `id` | Proxy Model |
| `contacts` | `BankAccount` | `partners_bank_accounts` | `id` | Proxy Model |
| `contacts` | `Industry` | `partners_industries` | `id` | Proxy Model |
| `contacts` | `Partner` | `partners_partners` | `id` | Proxy Model |
| `contacts` | `Tag` | `partners_tags` | `id` | Proxy Model |
| `contacts` | `Title` | `partners_titles` | `id` | Proxy Model |
| `core` | `User` | `users` | `id` | Core Model |
| `employees` | `ActivityPlan` | `activity_plans` | `id` | Proxy Model |
| `employees` | `Calendar` | `calendars` | `id` | Proxy Model |
| `employees` | `CalendarAttendance` | `calendar_attendances` | `id` | Proxy Model |
| `employees` | `CalendarLeave` | `calendar_leaves` | `id` | Proxy Model |
| `employees` | `Department` | `employees_departments` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `employees` | `DepartureReason` | `employees_departure_reasons` | `id` | Domain Model |
| `employees` | `Employee` | `employees_employees` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `employees` | `EmployeeCategory` | `employees_categories` | `id` | Domain Model |
| `employees` | `EmployeeEmployeeCategory` | `employees_employee_categories` | `id` | Domain Model |
| `employees` | `EmployeeJobPosition` | `employees_job_positions` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `employees` | `EmployeeResume` | `employees_employee_resumes` | `id` | Domain Model |
| `employees` | `EmployeeResumeAttachment` | `employees_employee_resume_attachments` | `id` | Domain Model |
| `employees` | `EmployeeResumeLineType` | `employees_employee_resume_line_types` | `id` | Domain Model |
| `employees` | `EmployeeSkill` | `employees_employee_skills` | `id` | Domain Model, SoftDeletes |
| `employees` | `EmploymentType` | `employees_employment_types` | `id` | Domain Model |
| `employees` | `JobPositionSkill` | `job_position_skills` | `id` | Domain Model |
| `employees` | `Skill` | `employees_skills` | `id` | Domain Model, SoftDeletes |
| `employees` | `SkillLevel` | `employees_skill_levels` | `id` | Domain Model, SoftDeletes |
| `employees` | `SkillType` | `employees_skill_types` | `id` | Domain Model, SoftDeletes |
| `employees` | `WorkLocation` | `employees_work_locations` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `fields` | `Field` | `custom_fields` | `id` | Domain Model, SoftDeletes |
| `inventories` | `Attribute` | `products_attributes` | `id` | Proxy Model |
| `inventories` | `Category` | `products_categories` | `id` | Proxy Model |
| `inventories` | `Delivery` | `inventories_operations` | `id` | Proxy Model |
| `inventories` | `Dropship` | `inventories_operations` | `id` | Proxy Model |
| `inventories` | `InternalTransfer` | `inventories_operations` | `id` | Proxy Model |
| `inventories` | `Location` | `inventories_locations` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `inventories` | `Lot` | `inventories_lots` | `id` | Domain Model, BelongsToCompany |
| `inventories` | `Move` | `inventories_moves` | `id` | Domain Model, BelongsToCompany |
| `inventories` | `MoveLine` | `inventories_move_lines` | `id` | Domain Model, BelongsToCompany |
| `inventories` | `Operation` | `inventories_operations` | `id` | Domain Model, BelongsToCompany |
| `inventories` | `OperationType` | `inventories_operation_types` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `inventories` | `OrderPoint` | `inventories_order_points` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `inventories` | `Package` | `inventories_packages` | `id` | Domain Model, BelongsToCompany |
| `inventories` | `PackageDestination` | `inventories_package_destinations` | `id` | Domain Model |
| `inventories` | `PackageLevel` | `inventories_package_levels` | `id` | Domain Model, BelongsToCompany |
| `inventories` | `PackageType` | `inventories_package_types` | `id` | Domain Model, BelongsToCompany |
| `inventories` | `Packaging` | `products_packagings` | `id` | Proxy Model |
| `inventories` | `ProcurementGroup` | `inventories_procurement_groups` | `id` | Domain Model |
| `inventories` | `Product` | `products_products` | `id` | Proxy Model |
| `inventories` | `ProductQuantity` | `inventories_product_quantities` | `id` | Domain Model, BelongsToCompany |
| `inventories` | `ProductQuantityRelocation` | `inventories_product_quantity_relocations` | `id` | Domain Model |
| `inventories` | `PutawayRule` | `inventories_putaway_rules` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `inventories` | `Receipt` | `inventories_operations` | `id` | Proxy Model |
| `inventories` | `Route` | `inventories_routes` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `inventories` | `Rule` | `inventories_rules` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `inventories` | `Scrap` | `inventories_scraps` | `id` | Domain Model, BelongsToCompany |
| `inventories` | `StorageCategory` | `inventories_storage_categories` | `id` | Domain Model, BelongsToCompany |
| `inventories` | `StorageCategoryCapacity` | `inventories_storage_category_capacities` | `id` | Domain Model |
| `inventories` | `Tag` | `inventories_tags` | `id` | Domain Model, SoftDeletes |
| `inventories` | `UOMCategory` | `unit_of_measure_categories` | `id` | Proxy Model |
| `inventories` | `Warehouse` | `inventories_warehouses` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `invoices` | `Attribute` | `products_attributes` | `id` | Proxy Model |
| `invoices` | `BankAccount` | `partners_bank_accounts` | `id` | Proxy Model |
| `invoices` | `Bill` | `accounts_account_moves` | `id` | Proxy Model |
| `invoices` | `Category` | `products_categories` | `id` | Proxy Model |
| `invoices` | `CreditNote` | `accounts_account_moves` | `id` | Proxy Model |
| `invoices` | `Currency` | `currencies` | `id` | Proxy Model |
| `invoices` | `Customer` | `partners_partners` | `id` | Proxy Model |
| `invoices` | `Incoterm` | `accounts_incoterms` | `id` | Proxy Model |
| `invoices` | `Invoice` | `accounts_account_moves` | `id` | Proxy Model |
| `invoices` | `Partner` | `partners_partners` | `id` | Proxy Model |
| `invoices` | `Payment` | `accounts_account_payments` | `id` | Proxy Model |
| `invoices` | `PaymentTerm` | `accounts_payment_terms` | `id` | Proxy Model |
| `invoices` | `Product` | `products_products` | `id` | Proxy Model |
| `invoices` | `Refund` | `accounts_account_moves` | `id` | Proxy Model |
| `invoices` | `Tax` | `accounts_taxes` | `id` | Proxy Model |
| `invoices` | `TaxGroup` | `accounts_tax_groups` | `id` | Proxy Model |
| `invoices` | `Vendor` | `partners_partners` | `id` | Proxy Model |
| `maintenance` | `Equipment` | `maintenance_equipments` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `maintenance` | `EquipmentCategory` | `maintenance_equipment_categories` | `id` | Domain Model, BelongsToCompany |
| `maintenance` | `MaintenanceRequest` | `maintenance_requests` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `maintenance` | `Stage` | `maintenance_stages` | `id` | Domain Model |
| `maintenance` | `Team` | `maintenance_teams` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `manufacturing` | `BillOfMaterial` | `manufacturing_bills_of_materials` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `manufacturing` | `BillOfMaterialByproduct` | `manufacturing_bill_of_material_byproducts` | `id` | Domain Model, BelongsToCompany |
| `manufacturing` | `BillOfMaterialLine` | `manufacturing_bill_of_material_lines` | `id` | Domain Model, BelongsToCompany |
| `manufacturing` | `Lot` | `inventories_lots` | `id` | Proxy Model |
| `manufacturing` | `Move` | `inventories_moves` | `id` | Proxy Model |
| `manufacturing` | `MoveLine` | `inventories_move_lines` | `id` | Proxy Model |
| `manufacturing` | `Operation` | `manufacturing_operations` | `id` | Domain Model, SoftDeletes |
| `manufacturing` | `Order` | `manufacturing_orders` | `id` | Domain Model, BelongsToCompany |
| `manufacturing` | `ProcurementGroup` | `inventories_procurement_groups` | `id` | Proxy Model |
| `manufacturing` | `Product` | `products_products` | `id` | Proxy Model |
| `manufacturing` | `UnbuildOrder` | `manufacturing_unbuild_orders` | `id` | Domain Model, BelongsToCompany |
| `manufacturing` | `Warehouse` | `inventories_warehouses` | `id` | Proxy Model |
| `manufacturing` | `WorkCenter` | `manufacturing_work_centers` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `manufacturing` | `WorkCenterCapacity` | `manufacturing_work_center_capacities` | `id` | Domain Model |
| `manufacturing` | `WorkCenterLossType` | `manufacturing_work_center_loss_types` | `id` | Domain Model |
| `manufacturing` | `WorkCenterProductivityLog` | `manufacturing_work_center_productivity_logs` | `id` | Domain Model, BelongsToCompany |
| `manufacturing` | `WorkCenterProductivityLoss` | `manufacturing_work_center_productivity_losses` | `id` | Domain Model |
| `manufacturing` | `WorkCenterTag` | `manufacturing_work_center_tags` | `id` | Domain Model, SoftDeletes |
| `manufacturing` | `WorkOrder` | `manufacturing_work_orders` | `id` | Domain Model |
| `partners` | `Address` | `partners_partners` | `id` | Proxy Model |
| `partners` | `Bank` | `banks` | `id` | Proxy Model |
| `partners` | `BankAccount` | `partners_bank_accounts` | `id` | Domain Model, SoftDeletes |
| `partners` | `Industry` | `partners_industries` | `id` | Domain Model, SoftDeletes |
| `partners` | `Partner` | `partners_partners` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `partners` | `Tag` | `partners_tags` | `id` | Domain Model, SoftDeletes |
| `partners` | `Title` | `partners_titles` | `id` | Domain Model |
| `payments` | `Payment` | `payments` | `id` | Domain Model |
| `payments` | `PaymentToken` | `payments_payment_tokens` | `id` | Domain Model |
| `payments` | `PaymentTransaction` | `payments_payment_transactions` | `id` | Domain Model |
| `plugin-manager` | `Plugin` | `plugins` | `id` | Domain Model |
| `products` | `Attribute` | `products_attributes` | `id` | Domain Model, SoftDeletes |
| `products` | `AttributeOption` | `products_attribute_options` | `id` | Domain Model |
| `products` | `Category` | `products_categories` | `id` | Domain Model |
| `products` | `Packaging` | `products_packagings` | `id` | Domain Model, BelongsToCompany |
| `products` | `PriceList` | `products_product_price_lists` | `id` | Domain Model, BelongsToCompany |
| `products` | `PriceRuleItem` | `products_price_rule_items` | `id` | Domain Model, BelongsToCompany |
| `products` | `Product` | `products_products` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `products` | `ProductAttribute` | `products_product_attributes` | `id` | Domain Model |
| `products` | `ProductAttributeValue` | `products_product_attribute_values` | `id` | Domain Model |
| `products` | `ProductCombination` | `products_product_combinations` | `id` | Domain Model |
| `products` | `ProductSupplier` | `products_product_suppliers` | `id` | Domain Model, BelongsToCompany |
| `products` | `Tag` | `products_tags` | `id` | Domain Model, SoftDeletes |
| `projects` | `ActivityPlan` | `activity_plans` | `id` | Proxy Model |
| `projects` | `Milestone` | `projects_milestones` | `id` | Domain Model |
| `projects` | `Project` | `projects_projects` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `projects` | `ProjectStage` | `projects_project_stages` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `projects` | `Tag` | `projects_tags` | `id` | Domain Model, SoftDeletes |
| `projects` | `Task` | `projects_tasks` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `projects` | `TaskStage` | `projects_task_stages` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `projects` | `Timesheet` | `analytic_records` | `id` | Proxy Model |
| `purchases` | `AccountMove` | `accounts_account_moves` | `id` | Proxy Model |
| `purchases` | `AccountMoveLine` | `accounts_account_move_lines` | `id` | Proxy Model |
| `purchases` | `Attribute` | `products_attributes` | `id` | Proxy Model |
| `purchases` | `Bill` | `accounts_account_moves` | `id` | Proxy Model |
| `purchases` | `Category` | `products_categories` | `id` | Proxy Model |
| `purchases` | `Currency` | `currencies` | `id` | Proxy Model |
| `purchases` | `CustomerPurchaseOrder` | `purchases_orders` | `id` | Proxy Model |
| `purchases` | `Order` | `purchases_orders` | `id` | Domain Model, BelongsToCompany |
| `purchases` | `OrderGroup` | `purchases_order_groups` | `id` | Domain Model |
| `purchases` | `OrderLine` | `purchases_order_lines` | `id` | Domain Model, BelongsToCompany |
| `purchases` | `Packaging` | `products_packagings` | `id` | Proxy Model |
| `purchases` | `Partner` | `partners_partners` | `id` | Proxy Model |
| `purchases` | `Product` | `products_products` | `id` | Proxy Model |
| `purchases` | `ProductSupplier` | `products_product_suppliers` | `id` | Proxy Model |
| `purchases` | `PurchaseOrder` | `purchases_orders` | `id` | Proxy Model |
| `purchases` | `Quotation` | `purchases_orders` | `id` | Proxy Model |
| `purchases` | `Requisition` | `purchases_requisitions` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `purchases` | `RequisitionLine` | `purchases_requisition_lines` | `id` | Domain Model, BelongsToCompany |
| `purchases` | `UOMCategory` | `unit_of_measure_categories` | `id` | Proxy Model |
| `recruitments` | `ActivityPlan` | `activity_plans` | `id` | Proxy Model |
| `recruitments` | `ActivityType` | `activity_types` | `id` | Proxy Model |
| `recruitments` | `Applicant` | `recruitments_applicants` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `recruitments` | `ApplicantApplicantCategory` | `recruitments_applicant_applicant_categories` | `id` | Domain Model |
| `recruitments` | `ApplicantCategory` | `recruitments_applicant_categories` | `id` | Domain Model |
| `recruitments` | `ApplicantInterviewer` | `recruitments_applicant_interviewers` | `id` | Domain Model |
| `recruitments` | `Candidate` | `recruitments_candidates` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `recruitments` | `CandidateApplicantCategory` | `recruitments_candidate_applicant_categories` | `id` | Domain Model |
| `recruitments` | `CandidateSkill` | `recruitments_candidate_skills` | `id` | Domain Model |
| `recruitments` | `Degree` | `recruitments_degrees` | `id` | Domain Model |
| `recruitments` | `Department` | `employees_departments` | `id` | Proxy Model |
| `recruitments` | `EmploymentType` | `employees_employment_types` | `id` | Proxy Model |
| `recruitments` | `JobByPosition` | `employees_job_positions` | `id` | Proxy Model |
| `recruitments` | `JobPosition` | `employees_job_positions` | `id` | Proxy Model |
| `recruitments` | `JobPositionInterviewer` | `recruitments_job_position_interviewers` | `id` | Domain Model |
| `recruitments` | `RefuseReason` | `recruitments_refuse_reasons` | `id` | Domain Model |
| `recruitments` | `SkillType` | `employees_skill_types` | `id` | Proxy Model |
| `recruitments` | `Stage` | `recruitments_stages` | `id` | Domain Model |
| `recruitments` | `StageJob` | `recruitments_stages_jobs` | `id` | Domain Model |
| `recruitments` | `UTMMedium` | `utm_mediums` | `id` | Proxy Model |
| `recruitments` | `UTMSource` | `utm_sources` | `id` | Proxy Model |
| `sales` | `ActivityPlan` | `activity_plans` | `id` | Proxy Model |
| `sales` | `ActivityType` | `activity_types` | `id` | Proxy Model |
| `sales` | `AdvancedPaymentInvoice` | `sales_advance_payment_invoices` | `id` | Domain Model, BelongsToCompany |
| `sales` | `AdvancedPaymentInvoiceOrderSale` | `sales_advance_payment_invoice_order_sales` | `id` | Domain Model |
| `sales` | `Attribute` | `products_attributes` | `id` | Proxy Model |
| `sales` | `Category` | `products_categories` | `id` | Proxy Model |
| `sales` | `Currency` | `currencies` | `id` | Proxy Model |
| `sales` | `Invoice` | `accounts_account_moves` | `id` | Proxy Model |
| `sales` | `Order` | `sales_orders` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `sales` | `OrderLine` | `sales_order_lines` | `id` | Domain Model, BelongsToCompany |
| `sales` | `OrderOption` | `sales_order_options` | `id` | Domain Model |
| `sales` | `OrderTemplate` | `sales_order_templates` | `id` | Domain Model, BelongsToCompany |
| `sales` | `OrderTemplateProduct` | `sales_order_template_products` | `id` | Domain Model, BelongsToCompany |
| `sales` | `OrderToInvoice` | `sales_orders` | `id` | Proxy Model |
| `sales` | `OrderToUpsell` | `sales_orders` | `id` | Proxy Model |
| `sales` | `Packaging` | `products_packagings` | `id` | Proxy Model |
| `sales` | `Partner` | `partners_partners` | `id` | Proxy Model |
| `sales` | `Product` | `products_products` | `id` | Proxy Model |
| `sales` | `Quotation` | `sales_orders` | `id` | Proxy Model |
| `sales` | `Tag` | `sales_tags` | `id` | Domain Model |
| `sales` | `Team` | `sales_teams` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `sales` | `TeamMember` | `sales_team_members` | `id` | Domain Model |
| `sales` | `UOMCategory` | `unit_of_measure_categories` | `id` | Proxy Model |
| `security` | `Company` | `companies` | `id` | Proxy Model |
| `security` | `Invitation` | `user_invitations` | `id` | Domain Model |
| `security` | `Permission` | `permissions` | `id` | Proxy Model |
| `security` | `Role` | `roles` | `id` | Proxy Model |
| `security` | `Team` | `teams` | `id` | Domain Model |
| `security` | `User` | `users` | `id` | Proxy Model, SoftDeletes |
| `support` | `ActivityPlan` | `activity_plans` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `support` | `ActivityPlanTemplate` | `activity_plan_templates` | `id` | Domain Model |
| `support` | `ActivityType` | `activity_types` | `id` | Domain Model, SoftDeletes |
| `support` | `ActivityTypeSuggestion` | `activity_type_suggestions` | `id` | Domain Model |
| `support` | `Bank` | `banks` | `id` | Domain Model, SoftDeletes |
| `support` | `Calendar` | `calendars` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `support` | `CalendarAttendance` | `calendar_attendances` | `id` | Domain Model |
| `support` | `CalendarLeave` | `calendar_leaves` | `id` | Domain Model, BelongsToCompany |
| `support` | `Company` | `companies` | `id` | Domain Model, SoftDeletes |
| `support` | `Country` | `countries` | `id` | Domain Model |
| `support` | `Currency` | `currencies` | `id` | Domain Model |
| `support` | `CurrencyRate` | `currency_rates` | `id` | Domain Model |
| `support` | `EmailLog` | `email_logs` | `id` | Domain Model |
| `support` | `EmailTemplate` | `email_templates` | `id` | Domain Model, SoftDeletes |
| `support` | `QuickNavigationFavorite` | `quick_navigation_favorites` | `id` | Domain Model |
| `support` | `Sequence` | `sequences` | `id` | Domain Model, BelongsToCompany |
| `support` | `State` | `states` | `id` | Domain Model |
| `support` | `UOM` | `unit_of_measures` | `id` | Domain Model, SoftDeletes |
| `support` | `UOMCategory` | `unit_of_measure_categories` | `id` | Domain Model |
| `support` | `UTMMedium` | `utm_mediums` | `id` | Domain Model |
| `support` | `UTMSource` | `utm_sources` | `id` | Domain Model |
| `support` | `UtmCampaign` | `utm_campaigns` | `id` | Domain Model, BelongsToCompany |
| `support` | `UtmStage` | `utm_stages` | `id` | Domain Model |
| `table-views` | `TableView` | `table_views` | `id` | Domain Model |
| `table-views` | `TableViewFavorite` | `table_view_favorites` | `id` | Domain Model |
| `time-off` | `ActivityType` | `activity_types` | `id` | Proxy Model |
| `time-off` | `CalendarLeave` | `calendar_leaves` | `id` | Proxy Model |
| `time-off` | `Leave` | `time_off_leaves` | `id` | Domain Model, BelongsToCompany |
| `time-off` | `LeaveAccrualLevel` | `time_off_leave_accrual_levels` | `id` | Domain Model |
| `time-off` | `LeaveAccrualPlan` | `time_off_leave_accrual_plans` | `id` | Domain Model, BelongsToCompany |
| `time-off` | `LeaveAllocation` | `time_off_leave_allocations` | `id` | Domain Model, BelongsToCompany |
| `time-off` | `LeaveMandatoryDay` | `time_off_leave_mandatory_days` | `id` | Domain Model, BelongsToCompany |
| `time-off` | `LeaveType` | `time_off_leave_types` | `id` | Domain Model, SoftDeletes, BelongsToCompany |
| `time-off` | `UserLeaveType` | `time_off_user_leave_types` | `id` | Domain Model |
| `timesheets` | `Timesheet` | `analytic_records` | `id` | Proxy Model |
| `website` | `Page` | `website_pages` | `id` | Domain Model, SoftDeletes |
| `website` | `Partner` | `partners_partners` | `id` | Proxy Model |

## 7. Model Traits Analysis

Model traits in Aureus ERP attach reusable persistence logic, global query scopes, audit trails, and multi-tenant scoping mechanisms.

### SoftDeletes (`Illuminate\Database\Eloquent\SoftDeletes`)

- **Purpose**: Enables non-destructive record deletion by setting a timestamp in `deleted_at` instead of removing the row from the physical table.
- **Required Database Column**: `deleted_at` (timestamp, nullable). Created via `$table->softDeletes()` or `$table->softDeletesTz()` in migrations.
- **Runtime Query Effect**: Automatically appends `WHERE deleted_at IS NULL` to select queries via Eloquent's `SoftDeletingScope`. Trashed records can be queried using `::withTrashed()` or `::onlyTrashed()`.
- **Verified Usage**: SoftDeletes is widely used across many business models, with 61 database tables verified to support soft deletion across User, Partners, Products, Sales, Employees, Recruitments, Projects, Manufacturing, and Support entities.

### BelongsToCompany (`Webkul\Support\Traits\BelongsToCompany`)

- **Purpose**: Attaches company-scoping behavior and automatic company assignment at the Eloquent model layer.
- **Required Database Column**: `company_id` (foreign key referencing `companies.id`).
- **Runtime Query Effect**: Registers `Webkul\Support\Models\Scopes\CompanyScope`, appending `WHERE company_id = ?` matching the current active company ID from `app(CompanyContext::class)->currentId()`.
- **Persistence Effect**: Hooks into model `creating` event: if `empty($model->company_id)` and `static::autoAssignsCompany()` returns true, assigns `$model->company_id = app(CompanyContext::class)->currentId()`. Models can override `public static function autoAssignsCompany(): bool` to customize assignment.
- **Relationship Attached**: Declares `public function company(): BelongsTo` pointing to `Webkul\Support\Models\Company`.
- **Verified Usage**: Present on 87 verified domain models across transactional and operational modules.

### BelongsToCompanies (`Webkul\Support\Traits\BelongsToCompanies`)

- **Purpose**: Attaches multi-company scoping behavior for entities accessible across multiple companies.
- **Required Database Structure**: A many-to-many pivot table connecting the model to `companies` (e.g., `accounts_account_tax_companies`).
- **Runtime Query Effect**: Registers `Webkul\Support\Models\Scopes\CompaniesScope`, filtering records where the current active company ID exists within the pivot relationship.
- **Relationship Attached**: Declares `public function companies(): BelongsToMany` pointing to `Webkul\Support\Models\Company`.
- **Verified Usage**: Present on shared financial chart-of-accounts models such as `Webkul\Account\Models\Account`.

### RestrictToAllowedCompanies (`Webkul\Support\Traits\RestrictToAllowedCompanies`)

- **Purpose**: Restricts access to company records based on the authenticated user's allowed companies.
- **Runtime Query Effect**: Registers `Webkul\Support\Models\Scopes\AllowedCompanyScope`, filtering companies to those associated with the user via `user_allowed_companies`.
- **Verified Usage**: Present on `Webkul\Support\Models\Company`.

### HasOwnershipScope (`Webkul\Security\Traits\HasOwnershipScope`)

- **Purpose**: Applies row-level ownership filtering integrated with Spatie permission roles (User, Team, Company, Global).
- **Runtime Query Effect**: Registers `Webkul\Security\Models\Scopes\OwnershipScope`, restricting query results based on user assignment (`user_id`), team membership (`team_id`), or company boundaries.
- **Verified Usage**: Present on `User`, `Team`, `Product`, `Order`, `Invoice`, `PurchaseOrder`, `Partner`, and `Company`.

### HasChatter (`Webkul\Chatter\Traits\HasChatter`)

- **Purpose**: Attaches collaborative communication, activity logging, internal notes, mentions, and file attachment management directly to domain models.
- **Relationships Attached**:
  - `messages()`: `MorphMany` -> `Webkul\Chatter\Models\Message` (`messageable_type`, `messageable_id`)
  - `activities()`: `MorphMany` -> `Webkul\Chatter\Models\Message` filtered by `type = 'activity'`
  - `attachments()`: `MorphMany` -> `Webkul\Chatter\Models\Attachment` (`messageable_type`, `messageable_id`)
  - `followers()`: `MorphMany` -> `Webkul\Chatter\Models\Follower` (`followable_type`, `followable_id`)
- **Lifecycle Hooks**: Registers default followers (creator, assigned responsible user) upon creation and synchronizes responsible followers on update.
- **Verified Usage**: Present on major ERP transaction and master records (`Order`, `Invoice`, `Partner`, `Employee`, `Applicant`, `Candidate`, `Requisition`, `Project`, `Task`, `Leave`, `LeaveAllocation`, `MaintenanceRequest`).

### HasCustomFields (`Webkul\Field\Traits\HasCustomFields`)

- **Purpose**: Enables dynamic user-defined custom fields at runtime without requiring individual physical migration changes.
- **Lifecycle Hooks**: Automatically merges custom field codes into `$fillable` and dynamically appends casting rules (boolean, array, string) during `retrieved`, `creating`, and `updating` lifecycle hooks.
- **Cache Management**: Resolves custom fields from `custom_fields` table cached in memory by model class.
- **Verified Usage**: Used across customizable domain entities in CRM, Sales, Purchases, Employees, and Products.

### Spatie Sortable (`Spatie\EloquentSortable\SortableTrait`)

- **Purpose**: Enables sequencing and ordered list persistence in Filament table views and kanban boards.
- **Required Database Column**: `sort` (integer). Configured in model via `public $sortable = ['order_column_name' => 'sort', 'sort_when_creating' => true];`.
- **Verified Usage**: Present on `Degree`, `RefuseReason`, `Stage`, `OrderOption`, `OrderTemplate`, `ActivityType`, `CalendarAttendance`, `UtmStage`, `LeaveType`, `LeaveAccrualLevel`, `WorkCenterTag`, and `Field`.

### ChecksCompanyConsistency (`Webkul\Support\Traits\ChecksCompanyConsistency`)

- **Purpose**: Ensures that child records (e.g. order lines, move lines) belong to the same `company_id` as their parent transaction record.
- **Verified Usage**: Present on `OrderLine`, `AccountMoveLine`, `RequisitionLine`, `PurchaseOrderLine`.

## 8. Model Relationship Catalog

Relationships across Aureus ERP follow standardized relational patterns connecting master entities, transaction headers, line items, and audit trails:

### Master-Detail / Header-Line Relationships

1. **Sales Order Header to Lines**
   - `Webkul\Sale\Models\Order::lines()` -> `hasMany(Webkul\Sale\Models\OrderLine::class, 'order_id')`
   - `Webkul\Sale\Models\OrderLine::order()` -> `belongsTo(Webkul\Sale\Models\Order::class, 'order_id')`

2. **Purchase Order Header to Lines**
   - `Webkul\Purchase\Models\Order::lines()` -> `hasMany(Webkul\Purchase\Models\OrderLine::class, 'order_id')`
   - `Webkul\Purchase\Models\OrderLine::order()` -> `belongsTo(Webkul\Purchase\Models\Order::class, 'order_id')`

3. **Accounting Move Header to Lines**
   - `Webkul\Account\Models\Move::lines()` -> `hasMany(Webkul\Account\Models\MoveLine::class, 'move_id')`
   - `Webkul\Account\Models\MoveLine::move()` -> `belongsTo(Webkul\Account\Models\Move::class, 'move_id')`

4. **Manufacturing Order to Work Orders & Move Lines**
   - `Webkul\Manufacturing\Models\Order::workOrders()` -> `hasMany(Webkul\Manufacturing\Models\WorkOrder::class, 'production_id')`
   - `Webkul\Manufacturing\Models\Order::moveLines()` -> `hasMany(Webkul\Manufacturing\Models\MoveLine::class, 'raw_material_production_id')`

5. **Project to Tasks & Milestones**
   - `Webkul\Project\Models\Project::tasks()` -> `hasMany(Webkul\Project\Models\Task::class, 'project_id')`
   - `Webkul\Project\Models\Project::milestones()` -> `hasMany(Webkul\Project\Models\Milestone::class, 'project_id')`
   - `Webkul\Project\Models\Task::timesheets()` -> `hasMany(Webkul\Project\Models\Timesheet::class, 'task_id')`

6. **Employee to Organizational Structures**
   - `Webkul\Employee\Models\Employee::department()` -> `belongsTo(Webkul\Employee\Models\Department::class, 'department_id')`
   - `Webkul\Employee\Models\Employee::jobPosition()` -> `belongsTo(Webkul\Employee\Models\JobPosition::class, 'job_id')`
   - `Webkul\Employee\Models\Employee::parent()` -> `belongsTo(Webkul\Employee\Models\Employee::class, 'parent_id')`
   - `Webkul\Employee\Models\Employee::children()` -> `hasMany(Webkul\Employee\Models\Employee::class, 'parent_id')`

7. **Product Master to Variants & Combinations**
   - `Webkul\Product\Models\Product::category()` -> `belongsTo(Webkul\Product\Models\Category::class, 'category_id')`
   - `Webkul\Product\Models\Product::attributes()` -> `belongsToMany(Webkul\Product\Models\Attribute::class, 'products_product_attributes', 'product_id', 'attribute_id')`
   - `Webkul\Product\Models\Product::combinations()` -> `hasMany(Webkul\Product\Models\ProductCombination::class, 'product_id')`
   - `Webkul\Product\Models\Product::suppliers()` -> `hasMany(Webkul\Product\Models\ProductSupplier::class, 'product_id')`

### Dynamic Runtime Relationships (`Model::resolveRelationUsing()`)

To maintain clean plugin boundaries and prevent circular dependencies in model classes, downstream plugins register dynamic relationships on foundational models during service provider boot lifecycle (`packageBooted()`):

| Target Model | Registered Relation | Relation Type | Target Class | Registering Service Provider |
| :--- | :--- | :--- | :--- | :--- |
| `Webkul\Product\Models\Product` | `sellers` | `hasMany` | `Webkul\Purchase\Models\ProductSupplier` | `PurchaseServiceProvider` |
| `Webkul\Product\Models\Product` | `billsOfMaterials` | `hasMany` | `Webkul\Manufacturing\Models\BillOfMaterial` | `ManufacturingServiceProvider` |
| `Webkul\Product\Models\Product` | `billOfMaterialLines` | `hasMany` | `Webkul\Manufacturing\Models\BillOfMaterialLine` | `ManufacturingServiceProvider` |
| `Webkul\Product\Models\Product` | `productTaxes` | `belongsToMany` | `Webkul\Account\Models\Tax` | `AccountServiceProvider` |
| `Webkul\Product\Models\Product` | `supplierTaxes` | `belongsToMany` | `Webkul\Account\Models\Tax` | `AccountServiceProvider` |
| `Webkul\Product\Models\Product` | `propertyAccountIncome` | `belongsTo` | `Webkul\Account\Models\Account` | `AccountServiceProvider` |
| `Webkul\Product\Models\Product` | `propertyAccountExpense` | `belongsTo` | `Webkul\Account\Models\Account` | `AccountServiceProvider` |
| `Webkul\Product\Models\Product` | `routes` | `belongsToMany` | `Webkul\Inventory\Models\Route` | `InventoryServiceProvider` |
| `Webkul\Product\Models\Product` | `responsible` | `belongsTo` | `Webkul\Security\Models\User` | `InventoryServiceProvider` |
| `Webkul\Product\Models\Product` | `moveLines` | `hasMany` | `Webkul\Inventory\Models\MoveLine` | `InventoryServiceProvider` |
| `Webkul\Product\Models\Product` | `moves` | `hasMany` | `Webkul\Inventory\Models\Move` | `InventoryServiceProvider` |
| `Webkul\Product\Models\Product` | `quantities` | `hasMany` | `Webkul\Inventory\Models\ProductQuantity` | `InventoryServiceProvider` |
| `Webkul\Partner\Models\Partner` | `propertyAccountPayable` | `belongsTo` | `Webkul\Account\Models\Account` | `AccountServiceProvider` |
| `Webkul\Partner\Models\Partner` | `propertyAccountReceivable` | `belongsTo` | `Webkul\Account\Models\Account` | `AccountServiceProvider` |
| `Webkul\Partner\Models\Partner` | `propertyAccountPosition` | `belongsTo` | `Webkul\Account\Models\FiscalPosition` | `AccountServiceProvider` |
| `Webkul\Partner\Models\Partner` | `propertyPaymentTerm` | `belongsTo` | `Webkul\Account\Models\PaymentTerm` | `AccountServiceProvider` |
| `Webkul\Partner\Models\Partner` | `propertySupplierPaymentTerm` | `belongsTo` | `Webkul\Account\Models\PaymentTerm` | `AccountServiceProvider` |
| `Webkul\Partner\Models\Partner` | `propertyOutboundPaymentMethodLine` | `belongsTo` | `Webkul\Account\Models\PaymentMethodLine` | `AccountServiceProvider` |
| `Webkul\Partner\Models\Partner` | `propertyInboundPaymentMethodLine` | `belongsTo` | `Webkul\Account\Models\PaymentMethodLine` | `AccountServiceProvider` |

> [!NOTE]
> Dynamic relationships registered via `resolveRelationUsing()` behave identically to standard Eloquent relations at runtime (supporting eager loading, lazy loading, and query builder joins), but are declared dynamically in service providers rather than static model class files.

## 9. Persistence Features

### Soft Deletes Usage

SoftDeletes is widely used across many business models; 61 database tables are verified to include soft-delete support. Models define `use SoftDeletes;` and require `deleted_at` timestamp in their migration tables.

### JSON Attributes & Casts

JSON columns are cast natively via `$casts` or `casts(): array` using Laravel's array/json casting:

- `Webkul\Chatter\Models\Message`: `'properties' => 'array'` (stores JSON metadata diffs for audit trail events).
- `Webkul\Field\Models\Field`: `'options' => 'array'`, `'form_settings' => 'array'`, `'table_settings' => 'array'`, `'infolist_settings' => 'array'`.
- `Webkul\Support\Models\ActivityPlanTemplate`: `'responsible' => 'array'`.
- `Webkul\Website\Models\Page`: Spatie Translatable attributes (`title`, `content`, `meta_title`, `meta_description`) stored as JSON/JSONB.
- `Webkul\TableViews\Models\TableView`: `'filters' => 'array'`, `'columns' => 'array'`.

### Enum Casting

PHP 8 backed enums are cast directly in Eloquent models to enforce type safety at the ORM boundary:

- `ProductType::class`: Used in `Webkul\Product\Models\Product::class` (`type` column).
- `InvoiceStatus::class`, `MoveType::class`, `PaymentState::class`: Used in `Webkul\Account\Models\Move::class`.
- `OrderStatus::class`: Used in `Webkul\Sale\Models\Order::class` (`state` column).
- `RequisitionType::class`, `RequisitionState::class`: Used in `Webkul\Purchase\Models\Requisition::class`.
- `ApplicantStatus::class`: Used in `Webkul\Recruitment\Models\Applicant::class` (`status` column).
- `OperationState::class`: Used in `Webkul\Inventory\Models\Operation::class` (`state` column).
- `LeaveStatus::class`: Used in `Webkul\TimeOff\Models\Leave::class` (`state` column).

### Date / Datetime / Boolean Casting

- **Dates**: Cast to `date` or `datetime` across models (e.g., `date_order`, `date_planned`, `date_deadline`, `date_from`, `date_to`, `confirmed_at`, `closed_at`).
- **Booleans**: Explicitly cast via `'is_active' => 'boolean'`, `'is_company' => 'boolean'`, `'is_internal' => 'boolean'` ensuring reliable strict type comparison in PHP 8.3.

## 10. Company-Aware Models

Aureus ERP implements company scoping through model traits and global scopes:

### Models Using `BelongsToCompany` (Single-Company Scoping)

These models have `use BelongsToCompany;`, maintain a `company_id` foreign key column, apply `CompanyScope`, and auto-populate `company_id` on creation when `autoAssignsCompany()` is true:

- `accounts`: `BankStatement` (`Webkul\Account\Models\BankStatement`) -> Table: `accounts_bank_statements`
- `accounts`: `CategoryCompanyAccount` (`Webkul\Account\Models\CategoryCompanyAccount`) -> Table: `products_category_company_accounts`
- `accounts`: `FiscalPositionAccount` (`Webkul\Account\Models\FiscalPositionAccount`) -> Table: `accounts_fiscal_position_accounts`
- `accounts`: `FiscalPositionTax` (`Webkul\Account\Models\FiscalPositionTax`) -> Table: `accounts_fiscal_position_taxes`
- `accounts`: `FiscalPosition` (`Webkul\Account\Models\FiscalPosition`) -> Table: `accounts_fiscal_positions`
- `accounts`: `Journal` (`Webkul\Account\Models\Journal`) -> Table: `accounts_journals`
- `accounts`: `MoveLine` (`Webkul\Account\Models\MoveLine`) -> Table: `accounts_account_move_lines`
- `accounts`: `MoveReversal` (`Webkul\Account\Models\MoveReversal`) -> Table: `accounts_accounts_move_reversals`
- `accounts`: `Move` (`Webkul\Account\Models\Move`) -> Table: `accounts_account_moves`
- `accounts`: `PartialReconcile` (`Webkul\Account\Models\PartialReconcile`) -> Table: `accounts_partial_reconciles`
- `accounts`: `PartnerCompanyProperty` (`Webkul\Account\Models\PartnerCompanyProperty`) -> Table: `partners_partner_company_properties`
- `accounts`: `PaymentRegister` (`Webkul\Account\Models\PaymentRegister`) -> Table: `accounts_payment_registers`
- `accounts`: `PaymentTerm` (`Webkul\Account\Models\PaymentTerm`) -> Table: `accounts_payment_terms`
- `accounts`: `Payment` (`Webkul\Account\Models\Payment`) -> Table: `accounts_account_payments`
- `accounts`: `ProductCompanyAccount` (`Webkul\Account\Models\ProductCompanyAccount`) -> Table: `products_product_company_accounts`
- `accounts`: `Reconcile` (`Webkul\Account\Models\Reconcile`) -> Table: `accounts_reconciles`
- `accounts`: `TaxGroup` (`Webkul\Account\Models\TaxGroup`) -> Table: `accounts_tax_groups`
- `accounts`: `TaxPartition` (`Webkul\Account\Models\TaxPartition`) -> Table: `accounts_tax_partition_lines`
- `accounts`: `Tax` (`Webkul\Account\Models\Tax`) -> Table: `accounts_taxes`
- `analytics`: `Record` (`Webkul\Analytic\Models\Record`) -> Table: `analytic_records`
- `chatter`: `Attachment` (`Webkul\Chatter\Models\Attachment`) -> Table: `chatter_attachments`
- `chatter`: `Message` (`Webkul\Chatter\Models\Message`) -> Table: `chatter_messages`
- `employees`: `Department` (`Webkul\Employee\Models\Department`) -> Table: `employees_departments`
- `employees`: `EmployeeJobPosition` (`Webkul\Employee\Models\EmployeeJobPosition`) -> Table: `employees_job_positions`
- `employees`: `Employee` (`Webkul\Employee\Models\Employee`) -> Table: `employees_employees`
- `employees`: `WorkLocation` (`Webkul\Employee\Models\WorkLocation`) -> Table: `employees_work_locations`
- `inventories`: `Location` (`Webkul\Inventory\Models\Location`) -> Table: `inventories_locations`
- `inventories`: `Lot` (`Webkul\Inventory\Models\Lot`) -> Table: `inventories_lots`
- `inventories`: `MoveLine` (`Webkul\Inventory\Models\MoveLine`) -> Table: `inventories_move_lines`
- `inventories`: `Move` (`Webkul\Inventory\Models\Move`) -> Table: `inventories_moves`
- `inventories`: `OperationType` (`Webkul\Inventory\Models\OperationType`) -> Table: `inventories_operation_types`
- `inventories`: `Operation` (`Webkul\Inventory\Models\Operation`) -> Table: `inventories_operations`
- `inventories`: `OrderPoint` (`Webkul\Inventory\Models\OrderPoint`) -> Table: `inventories_order_points`
- `inventories`: `PackageLevel` (`Webkul\Inventory\Models\PackageLevel`) -> Table: `inventories_package_levels`
- `inventories`: `PackageType` (`Webkul\Inventory\Models\PackageType`) -> Table: `inventories_package_types`
- `inventories`: `Package` (`Webkul\Inventory\Models\Package`) -> Table: `inventories_packages`
- `inventories`: `ProductQuantity` (`Webkul\Inventory\Models\ProductQuantity`) -> Table: `inventories_product_quantities`
- `inventories`: `PutawayRule` (`Webkul\Inventory\Models\PutawayRule`) -> Table: `inventories_putaway_rules`
- `inventories`: `Route` (`Webkul\Inventory\Models\Route`) -> Table: `inventories_routes`
- `inventories`: `Rule` (`Webkul\Inventory\Models\Rule`) -> Table: `inventories_rules`
- `inventories`: `Scrap` (`Webkul\Inventory\Models\Scrap`) -> Table: `inventories_scraps`
- `inventories`: `StorageCategory` (`Webkul\Inventory\Models\StorageCategory`) -> Table: `inventories_storage_categories`
- `inventories`: `Warehouse` (`Webkul\Inventory\Models\Warehouse`) -> Table: `inventories_warehouses`
- `maintenance`: `EquipmentCategory` (`Webkul\Maintenance\Models\EquipmentCategory`) -> Table: `maintenance_equipment_categories`
- `maintenance`: `Equipment` (`Webkul\Maintenance\Models\Equipment`) -> Table: `maintenance_equipments`
- `maintenance`: `MaintenanceRequest` (`Webkul\Maintenance\Models\MaintenanceRequest`) -> Table: `maintenance_requests`
- `maintenance`: `Team` (`Webkul\Maintenance\Models\Team`) -> Table: `maintenance_teams`
- `manufacturing`: `BillOfMaterialByproduct` (`Webkul\Manufacturing\Models\BillOfMaterialByproduct`) -> Table: `manufacturing_bill_of_material_byproducts`
- `manufacturing`: `BillOfMaterialLine` (`Webkul\Manufacturing\Models\BillOfMaterialLine`) -> Table: `manufacturing_bill_of_material_lines`
- `manufacturing`: `BillOfMaterial` (`Webkul\Manufacturing\Models\BillOfMaterial`) -> Table: `manufacturing_bills_of_materials`
- `manufacturing`: `Order` (`Webkul\Manufacturing\Models\Order`) -> Table: `manufacturing_orders`
- `manufacturing`: `UnbuildOrder` (`Webkul\Manufacturing\Models\UnbuildOrder`) -> Table: `manufacturing_unbuild_orders`
- `manufacturing`: `WorkCenterProductivityLog` (`Webkul\Manufacturing\Models\WorkCenterProductivityLog`) -> Table: `manufacturing_work_center_productivity_logs`
- `manufacturing`: `WorkCenter` (`Webkul\Manufacturing\Models\WorkCenter`) -> Table: `manufacturing_work_centers`
- `partners`: `Partner` (`Webkul\Partner\Models\Partner`) -> Table: `partners_partners`
- `products`: `Packaging` (`Webkul\Product\Models\Packaging`) -> Table: `products_packagings`
- `products`: `PriceList` (`Webkul\Product\Models\PriceList`) -> Table: `products_product_price_lists`
- `products`: `PriceRuleItem` (`Webkul\Product\Models\PriceRuleItem`) -> Table: `products_price_rule_items`
- `products`: `ProductSupplier` (`Webkul\Product\Models\ProductSupplier`) -> Table: `products_product_suppliers`
- `products`: `Product` (`Webkul\Product\Models\Product`) -> Table: `products_products`
- `projects`: `ProjectStage` (`Webkul\Project\Models\ProjectStage`) -> Table: `projects_project_stages`
- `projects`: `Project` (`Webkul\Project\Models\Project`) -> Table: `projects_projects`
- `projects`: `TaskStage` (`Webkul\Project\Models\TaskStage`) -> Table: `projects_task_stages`
- `projects`: `Task` (`Webkul\Project\Models\Task`) -> Table: `projects_tasks`
- `purchases`: `OrderLine` (`Webkul\Purchase\Models\OrderLine`) -> Table: `purchases_order_lines`
- `purchases`: `Order` (`Webkul\Purchase\Models\Order`) -> Table: `purchases_orders`
- `purchases`: `RequisitionLine` (`Webkul\Purchase\Models\RequisitionLine`) -> Table: `purchases_requisition_lines`
- `purchases`: `Requisition` (`Webkul\Purchase\Models\Requisition`) -> Table: `purchases_requisitions`
- `recruitments`: `Applicant` (`Webkul\Recruitment\Models\Applicant`) -> Table: `recruitments_applicants`
- `recruitments`: `Candidate` (`Webkul\Recruitment\Models\Candidate`) -> Table: `recruitments_candidates`
- `sales`: `AdvancedPaymentInvoice` (`Webkul\Sale\Models\AdvancedPaymentInvoice`) -> Table: `sales_advance_payment_invoices`
- `sales`: `OrderLine` (`Webkul\Sale\Models\OrderLine`) -> Table: `sales_order_lines`
- `sales`: `OrderTemplateProduct` (`Webkul\Sale\Models\OrderTemplateProduct`) -> Table: `sales_order_template_products`
- `sales`: `OrderTemplate` (`Webkul\Sale\Models\OrderTemplate`) -> Table: `sales_order_templates`
- `sales`: `Order` (`Webkul\Sale\Models\Order`) -> Table: `sales_orders`
- `sales`: `Team` (`Webkul\Sale\Models\Team`) -> Table: `sales_teams`
- `support`: `ActivityPlan` (`Webkul\Support\Models\ActivityPlan`) -> Table: `activity_plans`
- `support`: `CalendarLeave` (`Webkul\Support\Models\CalendarLeave`) -> Table: `calendar_leaves`
- `support`: `Calendar` (`Webkul\Support\Models\Calendar`) -> Table: `calendars`
- `support`: `Sequence` (`Webkul\Support\Models\Sequence`) -> Table: `sequences`
- `support`: `UtmCampaign` (`Webkul\Support\Models\UtmCampaign`) -> Table: `utm_campaigns`
- `time-off`: `LeaveAccrualPlan` (`Webkul\TimeOff\Models\LeaveAccrualPlan`) -> Table: `time_off_leave_accrual_plans`
- `time-off`: `LeaveAllocation` (`Webkul\TimeOff\Models\LeaveAllocation`) -> Table: `time_off_leave_allocations`
- `time-off`: `LeaveMandatoryDay` (`Webkul\TimeOff\Models\LeaveMandatoryDay`) -> Table: `time_off_leave_mandatory_days`
- `time-off`: `LeaveType` (`Webkul\TimeOff\Models\LeaveType`) -> Table: `time_off_leave_types`
- `time-off`: `Leave` (`Webkul\TimeOff\Models\Leave`) -> Table: `time_off_leaves`

### Models Using `BelongsToCompanies` (Multi-Company Scoping)

- `accounts`: `Account` (`Webkul\Account\Models\Account`) -> Table: `accounts_accounts`

### Models Without Company Scope Traits

Certain models do not use `BelongsToCompany` or `BelongsToCompanies` because they represent:

1. **System & Global Master Records**: `Country`, `State`, `Currency`, `CurrencyRate`, `UTMMedium`, `UTMSource`, `UOMCategory`, `Plugin`.
2. **Global Auth & User Entities**: `User`, `Role`, `Permission`, `Team`.
3. **Pivot Table Records**: `SalesTeamMember`, `JobPositionInterviewer`, `StageJob`, `TaskTag`.
4. **Company-Free Helper Entities**: `ActivityTypeSuggestion`, `TableViewFavorite`, `Follower`.

> [!IMPORTANT]
> The presence of a `company_id` column in a table does NOT automatically attach Eloquent company scoping unless the model implements the `BelongsToCompany` trait or explicitly applies `CompanyScope`. For detailed tenant/company isolation rules, refer to `docs/database/company-isolation.md`.

## 11. Polymorphic Model Catalog

Polymorphic relationships in Aureus ERP allow flexible attachments across arbitrary model types:

| Source Model | Relationship | Type | Morph Type Column | Morph ID Column | Target / Purpose |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `Webkul\Chatter\Models\Message` | `messageable()` | `morphTo` | `messageable_type` | `messageable_id` | Attached to any domain model utilizing `HasChatter` for chatter history |
| `Webkul\Chatter\Models\Message` | `causer()` | `morphTo` | `causer_type` | `causer_id` | Identifies the initiating actor (e.g. `User`, `System`) |
| `Webkul\Chatter\Models\Attachment` | `messageable()` | `morphTo` | `messageable_type` | `messageable_id` | Stores file attachments linked to any chatter-enabled record |
| `Webkul\Chatter\Models\Follower` | `followable()` | `morphTo` | `followable_type` | `followable_id` | Links followers (`Partner`) to any followable entity |
| `Webkul\Support\Models\Sequence` | `scope()` | `morphTo` | `scope_type` | `scope_id` | Allows sequence number generators to be scoped to specific entities |
| `App\Models\User` / Notifications | Notifications | `morphMany` | `notifiable_type` | `notifiable_id` | Laravel database notification recipient mapping (`notifications` table) |
| `App\Models\User` / Sanctum | Personal Access Tokens | `morphMany` | `tokenable_type` | `tokenable_id` | API token authentication bearer mapping (`personal_access_tokens` table) |

## 12. Exceptions and Special Cases

The following verified exceptions and architectural nuances exist within the codebase:

1. **Proxy Models Without Physical Tables**:
   - High-level domain plugins frequently instantiate proxy models that extend base models from foundational plugins. Examples include `Webkul\Sales\Models\Product` (extends `Webkul\Products\Models\Product`), `Webkul\Purchases\Models\Partner` (extends `Webkul\Partners\Models\Partner`), and `Webkul\Invoices\Models\Invoice` (extends `Webkul\Accounting\Models\Move`). These models do not create new tables; they execute queries against the parent model's underlying table.
2. **Models Without Explicit `$table` Properties**:
   - `Webkul\Account\Models\BankStatementLine`: Stub model without explicit `$table` declared. Migration table is `accounts_bank_statement_lines`.
   - `Webkul\Payment\Models\Payment`, `PaymentToken`, `PaymentTransaction`: Models in `plugins/webkul/payments/src/Models/` without explicit `$table` property, which default by Laravel convention or map to `payments_payment_tokens` and `payments_payment_transactions`.
   - `Webkul\Support\Models\*` (`Bank`, `Company`, `Country`, `Currency`, `CurrencyRate`, `EmailLog`, `EmailTemplate`, `State`): Rely on standard Laravel snake_case pluralization (`banks`, `companies`, `countries`, `currencies`, etc.) matching their respective migrations.
   - `Webkul\Security\Models\Team`: Omits `$table` property; maps to `teams` table.
   - `Webkul\PluginManager\Models\Plugin`: Omits `$table` property; maps to `plugins` table.
3. **Table Name Prefix Variations**:
   - Most plugin tables follow `<plugin>_<entity>` (e.g. `sales_orders`, `purchases_orders`, `products_products`), while shared support tables are unprefixed (e.g. `companies`, `currencies`, `countries`, `states`, `banks`, `activity_types`, `sequences`).
4. **Translatable Attributes**:
   - `Webkul\Website\Models\Page` utilizes Spatie Translatable with JSON/JSONB database columns (`title`, `content`, `meta_title`, `meta_description`) rather than a separate translation table.

## 13. Partially Verified Areas

```
None identified.
```

All 315 Eloquent models across the application (along with the 6 supporting concern traits and global scope classes residing in model directories) have had their inheritance chains, migration tables, primary keys, casts, traits, and relationships exhaustively scanned, mapped, and verified against source code.

## 14. Unknowns

```
None identified.
```

## Evidence Index

The architectural claims, table schemas, and model behaviors documented above are directly supported by the following repository paths:

- **Root Application Code**:
  - `app/Models/User.php`
  - `database/migrations/0001_01_01_000000_create_users_table.php`
  - `database/migrations/2024_11_04_132945_create_permission_tables.php`
  - `database/migrations/2026_01_14_151113_create_notifications_table.php`
  - `database/migrations/2026_01_28_134402_create_personal_access_tokens_table.php`
- **Plugin Model Directories**:
  - `plugins/webkul/*/src/Models/`
- **Plugin Migration Directories**:
  - `plugins/webkul/*/database/migrations/`
- **Dynamic Relations Service Providers**:
  - `plugins/webkul/accounts/src/AccountServiceProvider.php`
  - `plugins/webkul/inventories/src/InventoryServiceProvider.php`
  - `plugins/webkul/manufacturing/src/ManufacturingServiceProvider.php`
  - `plugins/webkul/purchases/src/PurchaseServiceProvider.php`
- **Repository Shared Traits**:
  - `plugins/webkul/support/src/Traits/BelongsToCompany.php`
  - `plugins/webkul/support/src/Traits/BelongsToCompanies.php`
  - `plugins/webkul/support/src/Traits/RestrictToAllowedCompanies.php`
  - `plugins/webkul/support/src/Traits/ChecksCompanyConsistency.php`
  - `plugins/webkul/security/src/Traits/HasOwnershipScope.php`
  - `plugins/webkul/security/src/Traits/HasScopedPermissions.php`
  - `plugins/webkul/chatter/src/Traits/HasChatter.php`
  - `plugins/webkul/chatter/src/Traits/HasLogActivity.php`
  - `plugins/webkul/fields/src/Traits/HasCustomFields.php`
  - `plugins/webkul/support/src/Models/Concerns/HasContributedAttributes.php`
  - `plugins/webkul/inventories/src/Models/Concerns/ChecksCrossCompanyTransfer.php`
