---
status: verified
source_of_truth: source-code
last_verified: 2026-09-03
scope: global
confidence: high
---

# Aureus ERP — Canonical Terminology & Misconception Glossary

## How to use this glossary

This glossary defines the authoritative terminology, system concepts, and misconception corrections for Aureus ERP. It establishes exact definitions and distinguishes lookalike terms, legacy habits, and conceptual traps identified across Phases 1–9 of the repository audit.

Every entry in this glossary is backed by verified source code, tests, migrations, configuration, Composer metadata, and verified repository documentation (covering architecture, security, database, workflows, and business rules).

> **Operational Principle:**
> The glossary guides vocabulary and interpretation, but MUST NEVER override source-code evidence when implementation behavior is being verified.

When writing code, developing plugins, generating migrations, configuring security, or authoring documentation:
1. Developers and AI agents MUST adhere to the canonical terms, class namespaces, and architectural distinctions defined herein.
2. Developers and AI agents MUST NOT use deprecated, informal, or non-existent shorthand.
3. Every prescriptive rule stated in this glossary (`MUST`, `MUST NOT`, `SHOULD`, `NEVER`) is binding across the entire repository.

---

## Part 1: Seeded Core Misconceptions & Terminology Corrections

### 1. Company Isolation Mechanisms

- **Term / Class**: Multi-Company Isolation Suite (`BelongsToCompany`, `BelongsToCompanies`, `CompanyContext`, `CompanyScope`, `CompaniesScope`, `RestrictToAllowedCompanies`, `ChecksCompanyConsistency`)
- **What it actually is**: A modular suite of Eloquent traits, global query scopes, middleware, and domain guards provided by `plugins/webkul/support/` and `plugins/webkul/security/` that provide opt-in, session-aware multi-company tenancy filtering and relational referential integrity.
- **Common misconception**: Assuming a trait or query scope named `HasCompanyScope` exists, or assuming that multi-company isolation is enforced automatically without explicit model traits or guards.
- **Evidence**:
  - `plugins/webkul/support/src/Traits/BelongsToCompany.php`
  - `plugins/webkul/support/src/Traits/BelongsToCompanies.php`
  - `plugins/webkul/support/src/Services/CompanyContext.php`
  - `plugins/webkul/support/src/Models/Scopes/CompanyScope.php`
  - `plugins/webkul/support/src/Models/Scopes/CompaniesScope.php`
  - `plugins/webkul/support/src/Traits/ChecksCompanyConsistency.php`
  - `plugins/webkul/security/src/Http/Middleware/RestrictToAllowedCompanies.php`
  - The repository contains zero occurrences of `HasCompanyScope`.
- **Prescriptive Rule**:
  - Developers and AI agents MUST NOT reference, import, or assume the existence of `HasCompanyScope`.
  - For single-company ownership models, developers MUST attach `Webkul\Support\Traits\BelongsToCompany`.
  - For multi-company membership models, developers MUST attach `Webkul\Support\Traits\BelongsToCompanies`.
  - For cross-model referential integrity across company boundaries, models MUST attach `Webkul\Support\Traits\ChecksCompanyConsistency`.
  - Code MUST NOT assume Eloquent query isolation applies to raw queries or un-scoped builder queries.

---

### 2. Bouncer Authorization Service

- **Term / Class**: `Webkul\Security\Bouncer` / `Webkul\Security\Facades\Bouncer`
- **What it actually is**: A proprietary internal service class implemented within `plugins/webkul/security/src/Bouncer.php` that calculates authorized user ID sets for ownership-based query scopes (`OwnershipScope`) and manages cached role assignments.
- **Common misconception**: Assuming Aureus ERP uses the third-party open-source package `silber/bouncer`, or assuming third-party Bouncer methods, database tables, or conventions exist in this project.
- **Evidence**:
  - `plugins/webkul/security/src/Bouncer.php` (`namespace Webkul\Security; class Bouncer ...`)
  - `plugins/webkul/security/src/Facades/Bouncer.php`
  - `plugins/webkul/security/src/Models/Scopes/OwnershipScope.php:26` (`bouncer()->getAuthorizedUserIds(...)`)
  - Neither `composer.json` nor `composer.lock` contains `silber/bouncer`.
- **Prescriptive Rule**:
  - Developers and AI agents MUST NOT document, require, or treat Bouncer as `silber/bouncer`.
  - Code interacting with Bouncer MUST invoke the methods defined in `Webkul\Security\Bouncer` (`can()`, `hasRole()`, `assign()`, `retract()`, `allow()`, `disallow()`, `getAuthorizedUserIds()`).
  - Code MUST NOT call third-party `Bouncer::` methods that are absent from `Webkul\Security\Bouncer`.

---

### 3. Livewire Framework Version

- **Term / Class**: Livewire Framework
- **What it actually is**: Livewire version 4.3.3, installed as a framework dependency via Composer.
- **Common misconception**: Believing Livewire is on version 3 based on stale statements in repository documentation or configuration notes (such as `AGENTS.md`).
- **Evidence**:
  - `composer.lock` (package `livewire/livewire` installed version: `v4.3.3`)
  - `docs/ai/context.md:34, 42`
  - `AGENTS.md:19` contains a stale string (`livewire/livewire (LIVEWIRE) - v3`) contradicted by `composer.lock`.
- **Prescriptive Rule**:
  - Developers and AI agents MUST adhere to Livewire 4.3.3 APIs and conventions when authoring or debugging Livewire components.
  - Developers MUST NOT rely on deprecated Livewire v3 patterns when newer v4 specifications apply.
  - Per the source-of-truth hierarchy, `composer.lock` MUST override any conflicting documentation or agent guideline statements regarding package versions.

---

### 4. PermissionType Enum

- **Term / Class**: `Webkul\Security\Enums\PermissionType`
- **What it actually is**: A backed string enum defining the three authoritative scoping levels for resource authorization in Aureus ERP:
  - `PermissionType::GLOBAL` (`'global'`)
  - `PermissionType::GROUP` (`'group'`)
  - `PermissionType::INDIVIDUAL` (`'individual'`)
- **Common misconception**: Using the informal historical shorthand `self` or `'self'` as if it were an active enum case.
- **Evidence**:
  - `plugins/webkul/security/src/Enums/PermissionType.php:9-13`
    ```php
    case GLOBAL = 'global';
    case GROUP = 'group';
    case INDIVIDUAL = 'individual';
    ```
- **Prescriptive Rule**:
  - Developers and AI agents MUST use the exact enum cases: `PermissionType::GLOBAL`, `PermissionType::GROUP`, and `PermissionType::INDIVIDUAL`.
  - Code, policies, tests, and documentation MUST NOT use `self` or `'self'` as an enum value or case.

---

### 5. User Models Architecture

- **Term / Class**: `Webkul\Security\Models\User` vs `App\Models\User`
- **What it actually is**: Two distinct User model classes connected by class inheritance:
  - `Webkul\Security\Models\User`: The domain package model in `plugins/webkul/security/src/Models/User.php` containing all ERP core security traits (`HasApiTokens`, `HasFactory`, `Notifiable`, `HasRoles`, `BelongsToCompanies`), company relationships, and status attributes.
  - `App\Models\User`: The root application model in `app/Models/User.php` which directly extends `Webkul\Security\Models\User` and serves as the authenticatable identity for the application `admin` panel and Laravel auth guards.
- **Common misconception**: Confusing the two classes as interchangeable, assuming they are duplicate independent models, or attempting to reimplement ERP security features inside `App\Models\User`.
- **Evidence**:
  - `app/Models/User.php:12` (`class User extends \Webkul\Security\Models\User`)
  - `plugins/webkul/security/src/Models/User.php`
  - `config/auth.php:70` (`'model' => env('AUTH_MODEL', App\Models\User::class)`)
- **Prescriptive Rule**:
  - Local plugin code and domain relationships MUST type-hint and reference `Webkul\Security\Models\User` or resolve through `config('auth.providers.users.model')`.
  - Global application-level services, panel providers, and auth configurations MUST reference `App\Models\User`.
  - Developers MUST NOT redefine core user relationships or traits in `App\Models\User` that are already established in `Webkul\Security\Models\User`.

---

### 6. Plugin Dependencies (Three Distinct Concepts)

- **Term / Class**: Plugin Dependencies (Composer Require vs `Package::hasDependencies()` vs Code-Level Consumption)
- **What it actually is**: Three completely distinct dependency tiers within the Aureus ERP ecosystem:
  1. **Composer Dependency** (`require` in `plugins/webkul/<plugin>/composer.json` or root `composer.json`): Governs PHP class autoloading and external package distribution.
  2. **Runtime / Installation-Order Dependency** (`Package::hasDependencies([...])`): Declared inside a plugin's `*ServiceProvider::configureCustomPackage()`. Consumed exclusively by `InstallPluginCommand` to orchestrate database migration, seed order, and dependency verification.
  3. **Code-Level Consumption**: Mere PHP imports (`use Webkul\...`) or cross-plugin model references in code without any formal runtime declaration.
- **Common misconception**: Believing that adding a dependency to `composer.json` establishes Aureus plugin installation ordering, or assuming that importing a class from another plugin automatically makes it a registered runtime plugin dependency.
- **Evidence**:
  - `plugins/webkul/plugin-manager/src/Package.php:111` (`hasDependencies()`)
  - `plugins/webkul/plugin-manager/src/Console/Commands/InstallPluginCommand.php`
  - `docs/architecture/plugin-registry.md`
  - None of the 9 Core Plugins declare runtime plugin dependencies (`hasDependencies()`), yet they consume code across packages.
- **Prescriptive Rule**:
  - A plugin MUST declare Aureus plugin installation-order dependencies exclusively through `Package::hasDependencies([...])`.
  - A Composer dependency MAY exist when technically required, but `composer.json` MUST NOT be treated as the source of truth for Aureus plugin installation-order dependencies.
  - Mere code-level consumption MUST NOT be treated as equivalent to a declared runtime plugin installation dependency.

---

### 7. OwnerSource Kinds

- **Term / Class**: `OwnerSource` Resolution Kinds (`column`, `relation`, `pivot`, `followers`)
- **What it actually is**: The four established architectural mechanisms by which `Webkul\Security\Traits\HasOwner` and `Webkul\Security\Services\PermissionService` locate the owning party or parties of a record during `INDIVIDUAL` or `GROUP` authorization checks:
  1. `column`: Direct foreign key on the model's physical table (e.g., `user_id`, `creator_id`, `assigned_to`).
  2. `relation`: An Eloquent relation method pointing to the owning entity (e.g., `creator()`, `user()`).
  3. `pivot`: A many-to-many relationship resolving through an intermediate join table.
  4. `followers`: Dynamic followers list resolving ownership through `chatter_followers`.
- **Common misconception**: Assuming that record-level ownership in Aureus ERP is solely determined by a hardcoded `user_id` column on the table.
- **Evidence**:
  - `plugins/webkul/security/src/Traits/HasOwner.php`
  - `plugins/webkul/security/src/Services/PermissionService.php`
  - `docs/security/ownership-scopes.md`
- **Prescriptive Rule**:
  - When configuring models with `HasOwner`, developers MUST explicitly define the owner source using one of the four established kinds (`column`, `relation`, `pivot`, `followers`).
  - Developers MUST NOT assume a default `user_id` column exists on every owned model.

---

### 8. CompanyProperty (EAV Cast vs Model/Table)

- **Term / Class**: `Webkul\Account\Casts\CompanyProperty` vs `Webkul\Partner\Models\PartnerCompanyProperty`
- **What it actually is**: Two completely different system entities serving distinct purposes across different plugins:
  - `Webkul\Account\Casts\CompanyProperty`: An Eloquent custom attribute Cast (`plugins/webkul/accounts/src/Casts/CompanyProperty.php`) implementing `CastsAttributes`. It serializes and deserializes company-specific dynamic configuration properties stored in JSON/text attributes (EAV pattern).
  - `Webkul\Partner\Models\PartnerCompanyProperty`: A concrete Eloquent Model (`plugins/webkul/partners/src/Models/PartnerCompanyProperty.php`) backed by the physical database table `partner_company_properties` (`partners_partner_company_properties`), managing partner accounting property links (such as payable/receivable accounts per company).
- **Common misconception**: Confusing the custom Eloquent Cast `CompanyProperty` with the Eloquent Model `PartnerCompanyProperty`.
- **Evidence**:
  - `plugins/webkul/accounts/src/Casts/CompanyProperty.php`
  - `plugins/webkul/partners/src/Models/PartnerCompanyProperty.php`
  - `plugins/webkul/accounts/src/Models/PartnerCompanyProperty.php` (proxy model)
- **Prescriptive Rule**:
  - Developers and AI agents MUST NOT confuse the `CompanyProperty` cast with the `PartnerCompanyProperty` model.
  - When defining model casts for company-specific serialized properties, developers MUST use `Webkul\Account\Casts\CompanyProperty`.
  - When persisting partner-company accounting configurations, developers MUST query the model `Webkul\Partner\Models\PartnerCompanyProperty` (or `Webkul\Account\Models\PartnerCompanyProperty`).

---

### 9. SequenceService

- **Term / Class**: `Webkul\Support\Services\SequenceService`
- **What it actually is**: The centralized canonical sequence generator for Aureus ERP (`plugins/webkul/support/src/Services/SequenceService.php`), responsible for generating unique, formatted, chronologically incremented document references (e.g., `INV/2026/0001`, `PO0001`, `SO0001`, `WH/IN/0001`) according to configurations stored in the `support_sequences` table.
- **Common misconception**: Implementing ad-hoc, plugin-local autoincrement sequence logic, string formatters, or `MAX(id) + 1` queries within individual modules.
- **Evidence**:
  - `plugins/webkul/support/src/Services/SequenceService.php`
  - `plugins/webkul/support/src/Models/Sequence.php`
  - Table `support_sequences`
- **Prescriptive Rule**:
  - Developers and AI agents MUST use `Webkul\Support\Services\SequenceService` (or its facade/helper) for generating official business document reference codes.
  - Domain plugins MUST NOT introduce custom autoincrement reference counters or manual sequential numbering schemes.

---

## Part 2: Additional Repository Misconceptions & Terminology Corrections

### 10. Financial Moves vs Invoices and Bills

- **Term / Class**: Double-Entry Account Moves (`accounts_account_moves` & `MoveType` enum)
- **What it actually is**: A unified transactional ledger header table (`accounts_account_moves`) and model (`Webkul\Account\Models\Move`) that stores all financial documents in Aureus ERP:
  - Customer Invoices (`MoveType::OUT_INVOICE`)
  - Customer Credit Notes / Refunds (`MoveType::OUT_REFUND`)
  - Vendor Bills (`MoveType::IN_INVOICE`)
  - Vendor Refunds / Debit Notes (`MoveType::IN_REFUND`)
  - Customer Receipts (`MoveType::OUT_RECEIPT`)
  - Vendor Receipts (`MoveType::IN_RECEIPT`)
  - General Journal Entries (`MoveType::ENTRY`)
  Classes named `Invoice` in downstream plugins (e.g., `Webkul\Invoice\Models\Invoice`, `Webkul\Accounting\Models\Invoice`, `Webkul\Sale\Models\Invoice`) are thin proxy subclasses extending `Move` with no dedicated tables.
- **Common misconception**: Expecting separate physical database tables named `invoices`, `bills`, `credit_notes`, or `journal_entries`.
- **Evidence**:
  - `plugins/webkul/accounts/src/Models/Move.php`
  - `plugins/webkul/accounts/src/Enums/MoveType.php`
  - `plugins/webkul/invoices/src/Models/Invoice.php:7` (`class Invoice extends BaseMove {}`)
  - Table `accounts_account_moves`
- **Prescriptive Rule**:
  - Developers MUST NOT create separate database tables for commercial invoices, bills, credit notes, or receipts.
  - All financial transactions MUST be recorded in `accounts_account_moves` using the appropriate `MoveType` enum case.
  - Subclasses of `Move` MUST preserve the underlying double-entry balanced move line invariants.

---

### 11. The Four Financial Plugins (`accounts` vs `accounting` vs `invoices` vs `payments`)

- **Term / Class**: Financial Domain Plugin Boundaries
- **What it actually is**: A strict four-layer functional division of the financial domain:
  1. `accounts` (`Webkul\Account`): The core headless double-entry ledger engine. Owns physical database tables (`accounts_*`), calculation services, posting rules, and base models.
  2. `accounting` (`Webkul\Accounting`): A presentation, reporting, and dashboard layer. Owns ZERO database tables and ZERO migrations. Mounts the visual financial reporting suite (P&L, Balance Sheet, Trial Balance, Aging) and overview widgets.
  3. `invoices` (`Webkul\Invoice`): A presentation and billing workflow layer. Owns ZERO database tables and ZERO migrations. Mounts customer invoicing, vendor bills, and payment registration UI on top of `accounts` tables.
  4. `payments` (`Webkul\Payment`): An electronic gateway and tokenization schema layer. Owns tables (`payments_payment_methods`, `payments_payment_tokens`, `payments_payment_transactions`) and migrations, but defines ZERO Filament UI resources or pages.
- **Common misconception**: Assuming `accounting` or `invoices` own their own database tables or migrations, or assuming `payments` provides administrative UI screens.
- **Evidence**:
  - `plugins/webkul/accounting/database/migrations` does not exist.
  - `plugins/webkul/invoices/database/migrations` does not exist.
  - `plugins/webkul/payments/src/Filament` does not exist.
  - `plugins/webkul/accounts/database/migrations` contains 47 migrations.
- **Prescriptive Rule**:
  - Developers MUST NOT add database migrations or physical schema definitions to `accounting` or `invoices`.
  - Schema changes for financial transactions MUST be placed in `accounts`. Schema changes for external payment gateways MUST be placed in `payments`.
  - UI additions for payment gateways MUST be placed in consuming presentation plugins, not inside `payments`.

---

### 12. Partner Master Hub vs Contact Book (`partners` vs `contacts`)

- **Term / Class**: `partners` vs `contacts` Architecture
- **What it actually is**:
  - `partners` (`Webkul\Partner`): A Core Plugin (`$package->isCore()`) that owns all database tables (`partners_partners`, `partners_addresses`, `partners_bank_accounts`, `partners_tags`), base models, REST APIs, and Filament resource definitions. Crucially, its Filament resource deliberately sets `$shouldRegisterNavigation = false`.
  - `contacts` (`Webkul\Contact`): An Optional Plugin (`isCore = false`) with ZERO physical database tables and ZERO migrations (`database/` does not exist). It acts as the user-facing Contact Book UI wrapper, surfacing `PartnerResource` in the top-level navigation under `NavigationGroup::Contact`.
- **Common misconception**: Assuming `contacts` owns customer and vendor database tables, or assuming that `partners` is a headless-only package without Filament resources.
- **Evidence**:
  - `plugins/webkul/partners/src/PartnerServiceProvider.php` (`$package->isCore()`)
  - `plugins/webkul/contacts/src/ContactServiceProvider.php` (no migrations registered)
  - `plugins/webkul/partners/src/Filament/Resources/PartnerResource.php:24` (`protected static bool $shouldRegisterNavigation = false;`)
  - `plugins/webkul/contacts/src/Filament/Resources/PartnerResource.php:20` (`protected static bool $shouldRegisterNavigation = true;`)
- **Prescriptive Rule**:
  - All database tables, schema migrations, and REST APIs for partners, contacts, addresses, and bank accounts MUST be placed in `plugins/webkul/partners/`.
  - The `contacts` plugin MUST remain a presentation and navigation proxy layer.
  - Developers MUST NOT create physical tables in `contacts`.

---

### 13. Single-Table Product Hierarchy (`Product` vs `ProductTemplate`)

- **Term / Class**: Single-Table Product Hierarchy (`Webkul\Product\Models\Product`)
- **What it actually is**: A single-table hierarchy in `products_products` represented by the single Eloquent model `Webkul\Product\Models\Product`. A product template is a record where `parent_id IS NULL` and `is_configurable = true`. Concrete product variants (SKUs) are child records with `parent_id = <template_id>`.
- **Common misconception**: Assuming Aureus ERP implements Odoo's dual-table architecture (`product_template` and `product_product`) or assuming a separate `ProductTemplate` model/table exists.
- **Evidence**:
  - `plugins/webkul/products/src/Models/Product.php`
  - `plugins/webkul/products/database/migrations/2024_11_25_091807_create_products_products_table.php`
  - `docs/database/erds/operations.md:1149`
  - No `ProductTemplate` model class or `products_templates` table exists in the repository.
- **Prescriptive Rule**:
  - Developers and AI agents MUST NOT create a separate `ProductTemplate` model or migration.
  - All product templates and variant SKUs MUST be stored in `products_products` and queried via `Webkul\Product\Models\Product`.
  - Code distinguishing templates from variants MUST check `is_configurable` and `parent_id`.

---

### 14. Timesheets Storage Hierarchy on `analytic_records`

- **Term / Class**: Timesheet Persistence Hierarchy
- **What it actually is**: A multi-tiered model hierarchy where `Webkul\Timesheet\Models\Timesheet` extends `Webkul\Project\Models\Timesheet`, which in turn extends `Webkul\Analytic\Models\Record`. All timesheet lines are persisted directly in the core `analytic_records` table with discriminator `type = 'projects'`. The `timesheets` plugin owns ZERO physical database tables and ZERO migrations.
- **Common misconception**: Assuming a physical `timesheets` database table or plugin-local migration exists.
- **Evidence**:
  - `plugins/webkul/timesheets/src/Models/Timesheet.php:10` (`class Timesheet extends BaseTimesheet`)
  - `plugins/webkul/projects/src/Models/Timesheet.php:19` (`class Timesheet extends Record`)
  - `plugins/webkul/analytics/src/Models/Record.php:21` (`protected $table = 'analytic_records';`)
  - `plugins/webkul/timesheets/database/migrations` does not exist.
- **Prescriptive Rule**:
  - Developers MUST NOT create a physical `timesheets` database table.
  - All timesheet persistence MUST route through `analytic_records` using the established model inheritance hierarchy.

---

### 15. Filament Plugin Exceptions (`analytics` and `table-views`)

- **Term / Class**: Architectural Filament Plugin Exceptions
- **What it actually is**: Two confirmed architectural exceptions to the repository's standard `*Plugin.php` convention:
  - `analytics` (`Webkul\Analytic`): Defines ZERO Filament plugin classes (`AnalyticPlugin.php` does not exist) and registers no direct UI components.
  - `table-views` (`Webkul\TableViews`): Defines ZERO `*Plugin.php` classes (`TableViewsPlugin.php` does not exist). Instead, it hooks into Filament globally via render hooks registered in `TableViewsServiceProvider::packageRegistered()`.
- **Common misconception**: Assuming every plugin in Aureus ERP must define a `*Plugin.php` implementing `Filament\Contracts\Plugin` registered via `$panel->plugin(...)`.
- **Evidence**:
  - `docs/architecture/overview.md:90-93`
  - `plugins/webkul/analytics/src/AnalyticServiceProvider.php`
  - `plugins/webkul/table-views/src/TableViewsServiceProvider.php:37-48`
- **Prescriptive Rule**:
  - Developers and AI agents MUST NOT create or look for `AnalyticPlugin` or `TableViewsPlugin`.
  - When designing cross-cutting UI augmentations that attach unconditionally to all Filament tables or pages, developers SHOULD evaluate global render hooks in service providers before introducing panel plugin boilerplate.

---

### 16. Filament Multi-Panel Boundaries (`admin` vs `customer`)

- **Term / Class**: Multi-Panel Boundaries (`admin` Panel vs `customer` Panel)
- **What it actually is**: Two separate Filament panel runtime environments:
  - `admin` Panel: Mounted at `/admin`. Uses authentication guard `web`, authenticatable model `App\Models\User` (extending `Webkul\Security\Models\User`), Filament Shield RBAC, and internal MFA.
  - `customer` Panel: Mounted at `/`. Uses authentication guard `customer`, authenticatable model `Webkul\Partner\Models\Partner`, and dedicated customer portal auth.
- **Common misconception**: Assuming that all Filament resources are panel-agnostic, or assuming that the authenticated user in both panels is an instance of `User`.
- **Evidence**:
  - `app/Providers/Filament/AdminPanelProvider.php:40, 48`
  - `app/Providers/Filament/CustomerPanelProvider.php:38, 48`
  - `config/auth.php:39-55`
- **Prescriptive Rule**:
  - When authoring Filament resources, pages, or widgets, developers MUST explicitly specify the target panel.
  - Code operating within the `customer` panel MUST treat the authenticated user as `Webkul\Partner\Models\Partner`, NOT `App\Models\User`.
  - Code operating within the `admin` panel MUST treat the authenticated user as `App\Models\User`.
  - Resource access logic MUST NOT assume a single universal guard across both panels.

---

### 17. Queue Processing vs Synchronous Execution

- **Term / Class**: Queue Processing Architecture
- **What it actually is**: Zero traditional PHP queue Job classes were verified in the repository (`app/Jobs` and `plugins/webkul/*/src/Jobs` are absent, and zero Job classes implement `Illuminate\Contracts\Queue\ShouldQueue`). However, zero queue Job classes does NOT mean zero asynchronous behavior. The custom database notification class `Webkul\Chatter\Notifications\ChatterDatabaseNotification` implements `Illuminate\Contracts\Queue\ShouldQueue` and uses `Queueable`. In addition, deferred mechanisms, client-side polling (e.g. 30s topbar notification polling), and scheduled console tasks exist. Operations such as document posting, recalculations, invoice PDF generation, and inventory quant updates execute synchronously during request processing or via Artisan commands.
- **Common misconception**: Assuming background asynchronous queue workers process invoice PDFs or stock ledger entries, or conversely treating "zero queue Job classes" and "zero asynchronous behavior" as equivalent.
- **Evidence**:
  - `plugins/webkul/chatter/src/Notifications/ChatterDatabaseNotification.php:11` implements `ShouldQueue`
  - Zero traditional queue Job classes in `app/` or `plugins/webkul/*/src/`
  - `docs/architecture/change-impact.md:553, 590`
  - `docs/verification-matrix.md:95` (`TERM-011`)
- **Prescriptive Rule**:
  - Developers and AI agents MUST NOT assume background processing is handled by queued Job classes.
  - Developers and AI agents MUST NOT treat "zero queue Job classes" and "zero asynchronous behavior" as equivalent, as `ChatterDatabaseNotification` implements `ShouldQueue`.
  - Developers MUST NOT silently introduce new `ShouldQueue` implementations without explicitly documenting the architectural addition and verifying worker infrastructure.

---

### 18. Custom Fields Dual Trait (`Webkul\Field\Traits` vs `Webkul\Field\Filament\Traits`)

- **Term / Class**: `HasCustomFields` (Model Trait vs Filament Resource Trait)
- **What it actually is**: Two distinct traits sharing the exact same unqualified name `HasCustomFields`:
  - `Webkul\Field\Traits\HasCustomFields`: Attached to Eloquent Models. Dynamically merges custom field columns into `$fillable` and attaches attribute casts.
  - `Webkul\Field\Filament\Traits\HasCustomFields`: Attached to Filament Resources and Pages. Dynamically injects form components, table columns, table filters, and infolist entries at request time.
- **Common misconception**: Confusing the model trait with the Filament resource trait, or attempting to import the resource trait onto an Eloquent model.
- **Evidence**:
  - `plugins/webkul/fields/src/Traits/HasCustomFields.php`
  - `plugins/webkul/fields/src/Filament/Traits/HasCustomFields.php`
- **Prescriptive Rule**:
  - Developers MUST import `Webkul\Field\Traits\HasCustomFields` ONLY on Eloquent models.
  - Developers MUST import `Webkul\Field\Filament\Traits\HasCustomFields` ONLY on Filament resources, pages, or relation managers.
  - Model definitions MUST NOT import the Filament trait, and Filament resources MUST NOT import the model trait.

---

### 19. Chatter Dual Trait (`HasChatter` vs `HasLogActivity`)

- **Term / Class**: `HasChatter` vs `HasLogActivity`
- **What it actually is**: Two separate traits in `plugins/webkul/chatter/src/Traits/` with decoupled responsibilities:
  - `HasLogActivity`: Intercepts Eloquent model lifecycle events (`created`, `updated`, `deleted`, `restored`), computes attribute deltas, and logs structured audit entries to `chatter_messages` (with `type = 'notification'`).
  - `HasChatter`: Provides relational methods for interactive feeds (`messages()`, `attachments()`, `activities()`, `followers()`) and chat UI components.
- **Common misconception**: Assuming that attaching `HasChatter` automatically enables audit trail change logging, or assuming that `HasLogActivity` equips a model with interactive chatter relationships.
- **Evidence**:
  - `plugins/webkul/chatter/src/Traits/HasChatter.php`
  - `plugins/webkul/chatter/src/Traits/HasLogActivity.php`
- **Prescriptive Rule**:
  - Models requiring audit trail logging MUST explicitly attach `Webkul\Chatter\Traits\HasLogActivity`.
  - Models requiring chatter messages, activities, attachments, or followers MUST explicitly attach `Webkul\Chatter\Traits\HasChatter`.
  - If both capabilities are required, the model MUST explicitly declare both traits.

---

### 20. Relational Consistency Guard vs Cross-Company Transfer Guard

- **Term / Class**: `ChecksCompanyConsistency` vs `ChecksCrossCompanyTransfer`
- **What it actually is**:
  - `ChecksCompanyConsistency` (`Webkul\Support\Traits\ChecksCompanyConsistency`): A generic referential integrity trait used across models (`Order`, `Move`, `Journal`, `Lot`) that delegates to `CompanyConsistencyGuard` to ensure that related foreign entities (e.g., currency, partner, warehouse, journal) belong to the active company.
  - `ChecksCrossCompanyTransfer` (`Webkul\Inventory\Models\Concerns\ChecksCrossCompanyTransfer`): A domain-specific concern on inventory `Operation` and `Scrap` models that invokes `CrossCompanyTransferGuard` to validate whether inventory location moves between two locations cross company boundaries.
- **Common misconception**: Assuming cross-company inventory transfers are checked by `ChecksCompanyConsistency`, or assuming `ChecksCrossCompanyTransfer` is a generic multi-company trait.
- **Evidence**:
  - `plugins/webkul/support/src/Traits/ChecksCompanyConsistency.php`
  - `plugins/webkul/support/src/Support/CompanyConsistencyGuard.php`
  - `plugins/webkul/inventories/src/Models/Concerns/ChecksCrossCompanyTransfer.php`
  - `plugins/webkul/inventories/src/Support/CrossCompanyTransferGuard.php`
- **Prescriptive Rule**:
  - For standard relational company consistency checks on master or transactional records, developers MUST use `Webkul\Support\Traits\ChecksCompanyConsistency`.
  - For inventory stock movements between source and destination locations, developers MUST use `Webkul\Inventory\Models\Concerns\ChecksCrossCompanyTransfer`.

---

### 21. Order Locking Mechanisms (Sales vs Purchases)

- **Term / Class**: Document Locking Mechanisms
- **What it actually is**: Two distinct document locking patterns in sales versus purchases:
  - Sales Orders (`Webkul\Sale\Models\Order`): Uses a dedicated boolean column `is_locked` (`sales_orders.is_locked`). Locking is independent of the order `state` enum (Draft, Sent, Sale, Cancelled).
  - Purchase Orders (`Webkul\Purchase\Models\Order`): Uses state-based locking. Transitioning the order `state` to `OrderState::DONE` represents administrative locking (`purchases_orders.state`).
- **Common misconception**: Assuming sales and purchases use identical locking mechanics, or looking for an `is_locked` column on purchase orders.
- **Evidence**:
  - `plugins/webkul/sales/src/Models/Order.php` (`is_locked` boolean)
  - `plugins/webkul/purchases/src/Models/Order.php` (`OrderState::DONE`)
  - `docs/workflows/purchasing.md:403`
- **Prescriptive Rule**:
  - Code evaluating sales order lock states MUST check the `is_locked` boolean attribute.
  - Code evaluating purchase order lock states MUST check whether `state === OrderState::DONE`.
  - Developers MUST NOT add an `is_locked` check to purchase orders or assume sales orders use `OrderState::DONE`.

---

### 22. Core Ownership of UOM and Currency Master Data

- **Term / Class**: Unit of Measure (`UOM`) and `Currency` Ownership
- **What it actually is**:
  - `UOM` (`Webkul\Support\Models\UOM`, table `unit_of_measures`): Owned exclusively by the `support` core plugin, NOT by `products` or `inventories`.
  - `Currency` (`Webkul\Support\Models\Currency`, table `currencies`): Owned exclusively by the `support` core plugin, NOT by `accounts` or `accounting`.
  Downstream domain plugins (`purchases`, `sales`, `invoices`, `accounting`) define thin proxy classes extending `BaseCurrency` or reference `UOM` directly.
- **Common misconception**: Believing that Units of Measure belong to the `products` plugin, or that Currencies belong to `accounts`.
- **Evidence**:
  - `plugins/webkul/support/src/Models/UOM.php`
  - `plugins/webkul/support/src/Models/Currency.php`
  - `plugins/webkul/purchases/src/Models/Currency.php:7` (`class Currency extends BaseCurrency`)
- **Prescriptive Rule**:
  - All migrations, core schema additions, or relationship modifications for Units of Measure and Currencies MUST be placed in `plugins/webkul/support/`.
  - Domain plugins MUST NOT create competing UOM or currency tables or migrations.

---

### 23. Navigation Composition Pattern (`shouldRegisterNavigation = false`)

- **Term / Class**: Deliberate Navigation Composition Pattern
- **What it actually is**: An intentional architectural design where a foundational core plugin (e.g., `partners`, `accounts`) defines Filament resources with `protected static bool $shouldRegisterNavigation = false;` to suppress them from the navigation sidebar. A sibling presentation or optional plugin (e.g., `contacts`, `invoices`, `accounting`) then surfaces the user-facing navigation items under organized clusters and navigation groups.
- **Common misconception**: Assuming that a Filament resource with `shouldRegisterNavigation = false` is broken, deprecated, or dead code.
- **Evidence**:
  - `plugins/webkul/partners/src/Filament/Resources/PartnerResource.php:24`
  - `plugins/webkul/contacts/src/Filament/Resources/PartnerResource.php:20`
  - `Aureus ERP — Phase 10_Remaining AI Rules — Final Master Execution Prompt.md:719-724`
- **Prescriptive Rule**:
  - Developers MUST NOT change `shouldRegisterNavigation` to `true` on foundational core resources without verifying whether a presentation plugin owns user-facing navigation.
  - When exposing customized or clustered views of core resources, presentation plugins MUST control navigation registration.

---

### 24. Dynamic Eloquent Relationships (`Model::resolveRelationUsing()`)

- **Term / Class**: Dynamic Runtime Relation Registration (`resolveRelationUsing`)
- **What it actually is**: A Laravel Eloquent mechanism executed in `packageBooted()` of consuming plugin service providers (e.g., `SaleServiceProvider::packageBooted()`) that dynamically attaches relationships to models owned by other plugins at runtime without modifying the target model's source file.
- **Common misconception**: Assuming that all Eloquent relationships on a model are declared statically in that model's PHP class file, and concluding that a relationship does not exist merely because it is absent from the model's source file.
- **Evidence**:
  - `plugins/webkul/sales/src/SaleServiceProvider.php` (`packageBooted()`)
  - `docs/architecture/overview.md:96-101`
  - `docs/database/relationships.md`
- **Prescriptive Rule**:
  - Before concluding that a model lacks an Eloquent relationship, developers MUST search the repository for `resolveRelationUsing`.
  - Optional plugins attaching relationships to core models MUST register them via `resolveRelationUsing()` inside `packageBooted()`, preserving modular package boundaries.

---

### 25. Declaration vs Enforcement Principle

- **Term / Class**: Declaration ≠ Enforcement Principle
- **What it actually is**: The foundational architectural truth that declaring a capability in a Policy, Filament Shield permission, model `$fillable` array, PHP enum case, Form Request rule, or UI component toggle does NOT guarantee active enforcement across all execution paths (e.g., raw SQL queries, API endpoints, console commands, or internal service workflows).
- **Common misconception**: Assuming that because a permission, validation rule, or state restriction is declared in a Filament form or Policy, the backend logic and database are automatically protected from unauthorized or inconsistent state transitions.
- **Evidence**:
  - `docs/security/authorization.md:25-28`
  - `docs/business-rules/accounting.md:376`
  - `Aureus ERP — Phase 10_Remaining AI Rules — Final Master Execution Prompt.md:312, 1160-1170, 1861-1862`
- **Prescriptive Rule**:
  - Future documentation and code MUST distinguish between a capability being declared and that capability actually being enforced on the relevant execution path.
  - Security-critical constraints, financial balances, and company boundaries MUST be enforced at the service or model level, NOT solely in UI schemas or form requests.
  - AI agents and developers MUST NOT treat UI or policy declarations as proof of complete backend authorization or validation coverage.
