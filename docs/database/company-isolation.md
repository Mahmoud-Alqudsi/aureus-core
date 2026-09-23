---
status: verified
source_of_truth: source-code
last_verified: 2026-09-23
scope: database
confidence: high
---

# Aureus ERP company data model

## Database representation

[VERIFIED]

`companies` is the company anchor table. It has the conventional `id` primary key, a separate unique string `company_id`, optional self-referencing `parent_id`, optional currency and creator foreign keys, timestamps, and soft deletes. The `Company` model uses the default `companies` table and exposes parent/branch relations.

Evidence: `plugins/webkul/support/database/migrations/2024_12_10_092657_create_companies_table.php`; `plugins/webkul/support/src/Models/Company.php`

[VERIFIED]

Single-company association is represented by a `company_id` column on the associated table. It is not uniformly nullable or uniformly configured with the same delete action:

- `products_products.company_id`, `partners_partners.company_id`, and `inventories_operations.company_id` are nullable foreign keys with `nullOnDelete()`.
- `manufacturing_orders.company_id` is required with `restrictOnDelete()`.
- `chatter_messages.company_id` is nullable but uses an explicitly declared foreign key with `onDelete('cascade')`.

Evidence: `plugins/webkul/products/database/migrations/2025_01_05_100751_create_products_products_table.php`; `plugins/webkul/partners/database/migrations/2024_12_11_101220_create_partners_partners_table.php`; `plugins/webkul/inventories/database/migrations/2025_01_14_133233_create_inventories_operations_table.php`; `plugins/webkul/manufacturing/database/migrations/2026_03_31_064247_create_manufacturing_orders_table.php`; `plugins/webkul/chatter/database/migrations/2024_12_23_062355_create_chatter_messages_table.php`

`NULL` in a nullable company foreign key is a database state. Its business meaning and visibility must be determined from the model/runtime context; it must not be treated as a universal “global record” rule.

## Single-company model trait

[VERIFIED]

`BelongsToCompany` adds `CompanyScope` and, when `autoAssignsCompany()` remains true, sets a missing `company_id` from `CompanyContext` on creation. The trait itself neither creates a column nor creates a foreign-key constraint; each adopting model must have a compatible schema. Some models deliberately override automatic assignment, including Product and Partner.

Evidence: `plugins/webkul/support/src/Traits/BelongsToCompany.php`

Symbols: `BelongsToCompany::bootBelongsToCompany()`, `BelongsToCompany::autoAssignsCompany()`

Evidence: `plugins/webkul/products/src/Models/Product.php`; `plugins/webkul/partners/src/Models/Partner.php`

## Multi-company association

[VERIFIED]

The verified `BelongsToCompanies` adopter is `Webkul\Account\Models\Account`. Its `companies()` relation uses the `accounts_account_companies` pivot with `account_id` and `company_id`. The registered migration creates those foreign keys with cascade deletion; the initial registered version permits a nullable `company_id` and has no dedicated key/timestamps.

Evidence: `plugins/webkul/accounts/src/Models/Account.php`; `plugins/webkul/accounts/src/AccountServiceProvider.php`; `plugins/webkul/accounts/database/migrations/2025_01_30_054955_create_accounts_account_companies_table.php`

`BelongsToCompanies` supplies no pivot name or schema itself; it adds `CompaniesScope` and expects a relation named `companies` unless the model overrides `companyScopeRelation()`. When querying cross-tenant records for an explicitly specified allowed company in form schemas (such as `AccountProductSchema::accountOptions`), queries verify `$companyId` against `allowed_company_ids()` before calling `withoutGlobalScope(CompaniesScope::class)`.

Evidence: `plugins/webkul/support/src/Traits/BelongsToCompanies.php`; `plugins/webkul/accounts/src/Filament/Resources/ProductResource/Schemas/AccountProductSchema.php`

## Other company-oriented structures

[VERIFIED]

User access and preference are represented separately from a domain row's company owner: `user_allowed_companies` is a user/company pivot with cascade foreign keys, while `users.default_company_id` is a nullable foreign key with `nullOnDelete()`.

Evidence: `plugins/webkul/support/database/migrations/2024_12_10_100944_create_user_allowed_companies_table.php`; `plugins/webkul/security/database/migrations/2024_12_10_101127_add_default_company_id_column_to_users_table.php`; `plugins/webkul/security/src/Models/User.php`

[VERIFIED]

Some tables hold per-company properties for another entity rather than representing a generic many-to-many relation. `partners_partner_company_properties` is mapped by `PartnerCompanyProperty`; `products_product_company_accounts` has a unique `(product_id, company_id)` pair and foreign keys to the product, company, and optional accounts.

Evidence: `plugins/webkul/accounts/src/Models/PartnerCompanyProperty.php`; `plugins/webkul/accounts/database/migrations/2026_07_30_090000_create_products_product_company_accounts_table.php`

[VERIFIED]

`settings.company_id` is nullable and changes the settings uniqueness constraint from `(group, name)` to `(group, name, company_id)`. `sequences` uses nullable `company_id`, a generated `company_scope` expression, and unique constraints that include that generated value. These are table-specific structures, not a repository-wide company convention.

Evidence: `database/migrations/2026_07_21_100000_add_company_id_to_settings_table.php`; `plugins/webkul/support/database/migrations/2026_08_03_120000_create_sequences_table.php`

## Runtime filtering is separate

[VERIFIED]

`CompanyScope` filters models using the single-column pattern by active company IDs or a null company ID. `CompaniesScope` filters the multi-company pattern through the configured relation or admits rows with no related companies. These are Eloquent query behaviors; foreign keys, nullability, pivots, and delete actions remain schema facts independent of the scopes.

Evidence: `plugins/webkul/support/src/Models/Scopes/CompanyScope.php`; `plugins/webkul/support/src/Models/Scopes/CompaniesScope.php`

This document intentionally does not analyze authorization or scope-bypass security behavior; that belongs to Phase 3.
