---
status: verified
source_of_truth: source-code
last_verified: 2026-09-03
scope: global
confidence: high
---

# Aureus ERP — Database Rules

## 1. Overview & Core Tenets

This document defines the binding physical schema, migration registration, referential integrity, foreign key lifecycle, and database design rules for Aureus ERP. Across 262 database tables and over 1,000 foreign keys, database integrity is the fundamental foundation of the system.

Every rule herein is prescriptive. Developers and AI agents creating migrations, modifying tables, or defining relationships MUST adhere to these rules.

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                Aureus ERP Database Governance                                    │
├──────────────────────────────┬───────────────────────────────────────────────────────────────────┤
│ Migration Registration Rule  │ Every migration MUST be explicitly registered in hasMigrations()  │
│ Semantic Delete Actions      │ Delete action chosen by lifecycle, NOT by frequency statistics    │
│ Company Tenancy Schema       │ Direct FK (BelongsToCompany) vs Dedicated Pivot (BelongsToCompanies)│
│ Referential Truth            │ Foreign keys enforce RDBMS truth; dynamic relations extend in ORM │
│ Convention Discipline        │ Never invent conventions from general Laravel; use [UNKNOWN]      │
└──────────────────────────────┴───────────────────────────────────────────────────────────────────┘
```

---

## 2. The Migration Registration Rule

### The Fundamental Rule
> **MANDATORY STANDARD:**
> **Every new migration file MUST be confirmed to be registered through the plugin's actual migration-loading mechanism. A migration file existing on disk MUST NOT be treated as proof that the migration runs.**

### Mechanism & Repository Evidence
In Aureus ERP, plugin migrations are NOT discovered by scanning the filesystem. The plugin-manager architecture (`Webkul\PluginManager\PackageServiceProvider`) executes migrations exclusively by iterating over the array explicitly passed to `$package->hasMigrations([...])`:

```php
$package
    ->hasMigrations([
        '2024_11_25_091807_create_products_products_table',
        '2024_12_11_070420_create_products_categories_table',
    ])
    ->runsMigrations();
```

If a migration file is placed in `database/migrations/` on disk but is omitted from `hasMigrations([...])`, Laravel will **NEVER execute it**.

Conversely, if a migration is declared in `hasMigrations([...])` but the file is missing from disk, the package installation or migration runner will fail or produce an inconsistent database state.

### Verified Repository Anomalies ("EmailTemplate" & Newly Discovered Sites)

The repository demonstrates concrete evidence of both failure modes:

1. **The `EmailTemplate` Missing Migration Anomaly**:
   - `plugins/webkul/support/src/SupportServiceProvider.php:60` declares `'2025_01_03_061444_create_email_templates_table'` in `hasMigrations()`.
   - The migration file does NOT exist on disk in `plugins/webkul/support/database/migrations/`.
   - The table `email_templates` is never created, causing runtime database crashes if `EmailTemplate` or `EmailTemplateService` is invoked.
2. **The `plugins` Table Missing Migration Anomaly in `support`**:
   - `plugins/webkul/support/src/SupportServiceProvider.php` declares `'2024_11_05_105102_create_plugins_table'` in `hasMigrations()`, but this file does not exist in `support`'s migration directory.
3. **The `support` Unregistered Companies Index Anomaly**:
   - Migration file `2026_03_09_000001_add_unique_index_to_companies_name.php` exists on disk in `plugins/webkul/support/database/migrations/`.
   - It is OMITTED from `SupportServiceProvider::hasMigrations()`. Consequently, this unique index migration **never runs**.
4. **The `time-off` Unregistered Data Migration Anomaly**:
   - Migration file `2026_08_04_100000_share_default_time_off_leave_types.php` exists on disk in `plugins/webkul/time-off/database/migrations/`.
   - It is OMITTED from `TimeOffServiceProvider::hasMigrations()`. Consequently, this default data migration **never runs**.
5. **The `employees` Orphaned Calendar Migrations Anomaly**:
   - Three migration files exist on disk in `plugins/webkul/employees/database/migrations/`:
     - `2024_12_11_100426_create_employees_calendars_table.php`
     - `2024_12_11_100435_create_employees_calendar_attendances_table.php`
     - `2024_12_11_100442_create_employees_calendar_leaves_table.php`
   - All three are OMITTED from `EmployeeServiceProvider::hasMigrations()` because calendar logic was centralized into `support`. They remain dormant on disk.
6. **The `accounts` Duplicate Pivot Migration Anomaly**:
   - Migration `2025_11_21_100010_create_accounts_account_companies_table.php` exists on disk in `plugins/webkul/accounts/database/migrations/`.
   - It is OMITTED from `AccountServiceProvider::hasMigrations()` because the table was already created by `2025_01_30_054955_create_accounts_account_companies_table.php`.

### Prescriptive Rules: Migration Registration
- When authoring a new migration, developers MUST add the migration filename (without `.php`) to the `$package->hasMigrations([...])` array in the owning plugin's `*ServiceProvider.php`.
- The service provider MUST call `$package->runsMigrations()`.
- Pull request audits and code reviews MUST verify that every migration file in `database/migrations/` has a matching entry in `hasMigrations([...])`.
- Developers MUST NOT leave abandoned or unreferenced migration files on disk.

---

## 3. Foreign-Key Delete Behavior Decision Rule

Across 1016 physical foreign keys, Aureus ERP exhibits the following observed distribution:
- `nullOnDelete()`: 607 keys (59.7%)
- `cascadeOnDelete()`: 226 keys (22.2%)
- `restrictOnDelete()`: 181 keys (17.8%)
- No Action / Default: 2 keys (0.2%)

### Mandatory Semantic Principle
> **CRITICAL ARCHITECTURAL RULE:**
> **New foreign-key delete behavior MUST be selected based on the semantic lifecycle of the relationship and the closest existing repository examples.**
> **Observed frequency statistics MUST NOT by themselves justify `nullOnDelete`, `cascadeOnDelete`, or `restrictOnDelete`.**

The fact that `nullOnDelete()` represents ~60% of existing foreign keys does NOT make it the default or preferred choice for new foreign keys. Each delete rule MUST be justified by relationship semantics.

```
                           What should happen when the
                              referenced record is deleted?
                                           │
         ┌─────────────────────────────────┼─────────────────────────────────┐
         ▼                                 ▼                                 ▼
Parent Owns Child Life            Child Has Independent Life        Child Represents Critical Audit
    (Composition)                     (Optional Reference)           or Financial Ledger Record
         │                                 │                                 │
         ▼                                 ▼                                 ▼
  cascadeOnDelete()                 nullOnDelete()                   restrictOnDelete()
         │                                 │                                 │
• Line items (order_lines)        • Creator / assigned user         • Currency on orders / moves
• Pivot junction tables           • Category on products            • Company on ledger moves
• Child chatter attachments       • Parent category on category     • Warehouse on stock pickings
• Detail distributions            • Partner on draft transaction    • Unit of Measure on items
```

### Semantic Criteria for Each Behavior

#### 1. When to Use `cascadeOnDelete()`
- **Lifecycle Rule**: The child record cannot exist without the parent; deleting the parent renders the child completely orphaned and meaningless.
- **Schema Requirement**: Foreign key column MUST NOT be nullable unless intermediate states require it.
- **Approved Examples**:
  - `sales_order_lines.order_id` (deleting order deletes line items)
  - `purchases_order_lines.order_id`
  - `accounts_account_move_lines.move_id`
  - `chatter_attachments.messageable_id`
  - Pivot table foreign keys (`user_team.user_id`, `sales_order_line_taxes.order_line_id`)

#### 2. When to Use `nullOnDelete()`
- **Lifecycle Rule**: The child record represents an independent business entity that must survive the parent's deletion, but its association to the parent is severed.
- **Schema Requirement**: Foreign key column MUST be explicitly defined as `->nullable()`.
- **Approved Examples**:
  - `creator_id` / `user_id` on domain records (retains record if user is purged)
  - `partner_id` on commercial records where history must persist
  - `parent_id` in self-referencing tree structures (`products_categories.parent_id`)
  - `products_products.company_id` (fallback to global product)

#### 3. When to Use `restrictOnDelete()`
- **Lifecycle Rule**: Deleting the parent would compromise referential integrity, financial auditability, legal compliance, or active operations. The parent record MUST NOT be deleted while dependent children exist.
- **Schema Requirement**: Foreign key column is typically required (non-nullable).
- **Approved Examples**:
  - `currency_id` on `sales_orders`, `purchases_orders`, and `accounts_account_moves`
  - `company_id` on `manufacturing_orders` and posted financial ledgers
  - `uom_id` on products and order lines
  - `operation_type_id` on `inventories_moves`

---

## 4. Multi-Company Schema Design Rule

When modeling company sensitivity in the database schema, developers MUST adhere to the following strict distinction:

```
┌───────────────────────────────────────────────┐ ┌───────────────────────────────────────────────┐
│     Single-Company Schema (BelongsToCompany)  │ │   Multi-Company Schema (BelongsToCompanies)   │
├───────────────────────────────────────────────┤ ├───────────────────────────────────────────────┤
│ • Column: company_id on target table          │ │ • Table: <plugin>_<entity>_companies pivot    │
│ • Foreign Key: constrained('companies')       │ │ • Columns: <entity>_id, company_id            │
│ • Nullability: Nullable for shared/global;    │ │ • Foreign Keys: cascadeOnDelete() on both FKs │
│   Non-nullable for transactional ledgers.     │ │ • Composite Unique: [entity_id, company_id]   │
│ • Scope: Webkul\Support\Traits\BelongsToCompany│ │ • Scope: Webkul\Support\Traits\BelongsToCompanies│
└───────────────────────────────────────────────┘ └───────────────────────────────────────────────┘
```

### Prescriptive Rules: Company Schema
- A plugin adding a company-sensitive table MUST deliberately determine whether the business entity belongs to a single company or multiple companies before writing migrations.
- Single-company tables MUST include:
  ```php
  $table->foreignId('company_id')->constrained('companies')->restrictOnDelete(); // Or nullOnDelete() if global rows permitted
  ```
- Multi-company tables MUST create a dedicated pivot table:
  ```php
  Schema::create('<plugin>_<entity>_companies', function (Blueprint $table) {
      $table->foreignId('<entity>_id')->constrained('<plugin>_<entity_plural>')->cascadeOnDelete();
      $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
      $table->unique(['<entity>_id', 'company_id']);
  });
  ```
- Developers MUST NOT use comma-separated strings or JSON arrays to store multi-company IDs.

---

## 5. Unknown Conventions & Architectural Discipline

### The Anti-Invention Rule
> **MANDATORY PRINCIPLE:**
> **If the repository does not establish a convention for a specific database pattern, developers and AI agents MUST NOT invent one based on generic Laravel practices.**
> **Uncertain patterns MUST be designated `[UNKNOWN]` and submitted for architectural review.**

### Established Naming Standards
1. **Primary Keys**:
   - Primary domain tables MUST use `$table->id()` (BIGINT auto-increment).
   - Pure junction/pivot tables MAY omit `$table->id()` and define a composite primary key or composite unique index.
   - UUIDs MUST NOT be introduced for domain models; they are reserved exclusively for framework tables (`notifications.id`, `failed_jobs.uuid`).
2. **Table Names**:
   - Domain plugin tables MUST use snake_case plural names prefixed with `<plugin>_` (e.g., `products_products`, `sales_orders`, `inventories_moves`).
   - Exceptions are restricted to core shared tables in `support` (`companies`, `currencies`, `countries`, `states`, `sequences`, `unit_of_measures`), `fields` (`custom_fields`), `security` (`teams`, `user_team`), `table-views` (`table_views`), and `analytics` (`analytic_records`).
3. **State / Status Columns**:
   - State columns MUST be defined as `$table->string('state')` or `$table->string('status')`.
   - Native database ENUMs (`$table->enum(...)`) MUST NOT be introduced for new transactional tables; domain state validation and type-safety MUST be handled by PHP 8 backed Enums in Eloquent casts.
4. **JSON Columns**:
   - Non-relational payloads, UI configurations, dynamic custom field definitions, and audit properties SHOULD use `$table->json(...)`.
   - Core relational data that requires indexing, querying, or foreign key constraints MUST NOT be buried inside JSON columns.

---

## 6. Dynamic Schema Awareness in Database Design

Developers and AI agents MUST remember that the database schema is extended dynamically at runtime:

1. **`resolveRelationUsing()` Dynamic Relations**:
   - Not all Eloquent relationships correspond to static foreign keys declared in the owning plugin's migrations. Optional plugins attach relationships to core models dynamically at boot time.
   - Cross-reference: `docs/architecture/dynamic-schema.md` (22 verified dynamic relation sites).
2. **`CompanyProperty` Attribute Casts**:
   - Company-specific configuration values on master records (such as receivable/payable accounts on partners) are stored in EAV mapping tables (`partner_company_properties`) via Eloquent casts, NOT as foreign keys on base tables.
3. **Custom Fields Engine**:
   - Physical table columns are altered dynamically at runtime via `FieldsColumnManager` when custom fields are defined. Migrations MUST NOT hardcode one-off customer-specific custom attributes.
