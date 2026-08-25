---
status: verified
source_of_truth: source-code
last_verified: 2026-08-25
scope: database
confidence: high
---

# Aureus ERP database architecture

## Scope and evidence

This is the Phase 4 architectural baseline, not a table or model catalog. Migration files establish schema facts; Eloquent models establish ORM mappings and relationships. An Eloquent relationship does not by itself prove a foreign-key constraint.

## Connection strategy

[VERIFIED]

`config/database.php` defines five Laravel SQL connections: `sqlite`, `mysql`, `mariadb`, `pgsql`, and `sqlsrv`; the default is selected by `DB_CONNECTION` and falls back to `sqlite`. The configured migration repository is the ordinary `migrations` table.

Evidence: `config/database.php`

[VERIFIED]

No application or plugin model declares a custom `$connection`, and no application persistence code switches a model connection. The support layer selects SQL dialect behavior from `DB::connection()->getDriverName()`.

Evidence: `plugins/webkul/support/src/SupportServiceProvider.php`

Symbol: `SupportServiceProvider::packageBooted()`

[UNKNOWN]

The repository does not establish which configured driver/database is used by a deployed environment. Therefore this documentation does not claim a particular production driver, a single physical database, or database-per-company deployment. The persistence model implemented in migrations and models is a shared-table company model; see [company isolation](company-isolation.md).

## Migration and schema ownership

[VERIFIED]

Root application migrations reside in `database/migrations/` and own framework/application infrastructure such as `users`, `cache`, `jobs`, `settings`, permissions, imports/exports, notifications, and personal access tokens.

Evidence: `database/migrations/`

[VERIFIED]

Domain schema is authored beside its plugin in `plugins/webkul/<plugin>/database/migrations/`. This is an ownership boundary, not a separate database or schema namespace. Plugin migrations can alter tables owned by another plugin; for example, Accounts adds columns/constraints to `partners_partners`, and Inventories adds columns to `sales_orders` and `purchases_orders`.

Evidence: `plugins/webkul/accounts/database/migrations/2026_02_16_063000_alter_partners_partners_table.php`; `plugins/webkul/inventories/database/migrations/2026_04_08_043411_add_procurement_group_id_column_in_sales_orders_table_from_inventories.php`

[VERIFIED]

Plugin migration discovery is explicit, not a blanket scan of migration directories. Each `PackageServiceProvider` declares `hasMigrations([...])`; during console boot, `PackageServiceProvider` calls `loadMigrationsFrom()` only for declared migrations of packages that both `runsMigrations()` and are core or installed. Core packages bypass installed-state; non-core packages depend on `Package::isInstalled()`, which reads the `plugins` table.

Evidence: `plugins/webkul/plugin-manager/src/PackageServiceProvider.php`

Symbols: `PackageServiceProvider::boot()`, `PackageServiceProvider::register()`

Evidence: `plugins/webkul/plugin-manager/src/Package.php`

Symbol: `Package::isInstalled()`

[VERIFIED]

The plugin installer has a second execution path: it collects the package's declared migration names and calls Artisan `migrate` with explicit `--path` values. Thus a migration file present on disk but absent from a package's registration list is not established as part of normal plugin installation or provider-loaded migration execution.

Evidence: `plugins/webkul/plugin-manager/src/Console/Commands/InstallCommand.php`

Symbol: `InstallCommand::runMigrations()`

## Eloquent persistence and relationships

[VERIFIED]

Models primarily use Laravel's conventional integer `id` key and timestamps, but plugin table names are commonly explicit because they do not follow the model's conventional plural name. Examples include `Product` → `products_products`, `Account` → `accounts_accounts`, and `Move` → `accounts_account_moves`. Models without `$table` use Laravel's default mapping, such as `Company` → `companies` and the application `User` base model → `users`.

Evidence: `plugins/webkul/products/src/Models/Product.php`; `plugins/webkul/accounts/src/Models/Account.php`; `plugins/webkul/accounts/src/Models/Move.php`; `plugins/webkul/support/src/Models/Company.php`; `app/Models/User.php`

[VERIFIED]

The model layer uses `belongsTo`, `hasOne`, `hasMany`, `hasManyThrough`, `belongsToMany`, `morphTo`, and `morphMany`. Many-to-many relations name their pivot table and keys explicitly where the table is non-conventional; `Account::companies()` uses `accounts_account_companies`, while `User::allowedCompanies()` uses `user_allowed_companies`.

Evidence: `plugins/webkul/accounts/src/Models/Account.php`; `plugins/webkul/security/src/Models/User.php`

[VERIFIED]

Some relationships are registered at runtime rather than declared on the base model. Accounts adds Partner/Product/Category relationships; Inventories adds Product relationships; Manufacturing adds Product relationships; Purchases adds Product relationships. Inspect the relevant installed plugin service provider when a relationship is not visible in the model class.

Evidence: `plugins/webkul/accounts/src/AccountServiceProvider.php`; `plugins/webkul/inventories/src/InventoryServiceProvider.php`; `plugins/webkul/manufacturing/src/ManufacturingServiceProvider.php`; `plugins/webkul/purchases/src/PurchaseServiceProvider.php`

Symbol: `Model::resolveRelationUsing()` calls

## Integrity and flexible persistence

[VERIFIED]

Migrations use database foreign keys, including `cascadeOnDelete()`, `nullOnDelete()`, and `restrictOnDelete()` according to the relationship. They are not interchangeable: `products_products.company_id` is nullable with `nullOnDelete()`, while `manufacturing_orders.company_id` is required with `restrictOnDelete()`.

Evidence: `plugins/webkul/products/database/migrations/2025_01_05_100751_create_products_products_table.php`; `plugins/webkul/manufacturing/database/migrations/2026_03_31_064247_create_manufacturing_orders_table.php`

[VERIFIED]

JSON/JSONB columns and array casts are used where a flexible payload is needed. Examples include product `images`, chatter message `properties`, settings `payload`, and account move-line `analytic_distribution`. Polymorphic pairs are also persisted: `chatter_messages` has `messageable` and nullable `causer` morph columns. These morph references are not conventional foreign keys to one table.

Evidence: `plugins/webkul/products/database/migrations/2025_01_05_100751_create_products_products_table.php`; `plugins/webkul/chatter/database/migrations/2024_12_23_062355_create_chatter_messages_table.php`; `database/migrations/2022_12_14_083707_create_settings_table.php`; `plugins/webkul/accounts/database/migrations/2025_02_11_071210_create_accounts_account_move_lines_table.php`

## Investigation procedure

For a persistence change or schema question:

1. Start with the relevant model's `$table`, traits, casts, and relation methods.
2. Locate every create and later alter migration for that exact table, including migrations in other plugins.
3. Verify the final column definition, foreign key, delete action, indexes, and unique constraints from migrations; do not infer them from a relation method.
4. Check the owning service provider's `hasMigrations()` list and whether the package is core/installed before assuming the migration runs.
5. Check contributing plugin service providers for `resolveRelationUsing()` calls.
6. For company-related data, inspect both the schema and the relevant trait/scope using the distinctions in [company isolation](company-isolation.md).
