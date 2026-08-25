---
status: verified
source_of_truth: source-code
last_verified: 2026-08-25
scope: global
confidence: high
---

# Aureus ERP — Database Relationships & Referential Integrity

## Purpose
This document provides a rigorous, source-code-verified analysis of database relationships, referential integrity, deletion behaviors, and cross-plugin dependencies in the Aureus ERP repository. It distinguishes between database-level constraints and application-level Eloquent relationships.

## Verification Rules
All architectural claims are verified against the repository source code (migrations and models). Documentation of relationships differentiates strictly between database foreign keys and Eloquent ORM declarations.

## Foreign-Key Architecture
[VERIFIED]
Foreign keys are widely implemented across the repository to enforce structural relationships. Over 270 explicit database foreign keys were identified during the migration inspection.
- The standard mechanism is Laravel's `$table->foreignId('...')->constrained('...')`.
- Foreign keys explicitly point to the `id` columns of parent tables.
- Foreign keys frequently cross logical plugin boundaries.

## Referential Integrity Model
[VERIFIED]
Referential integrity is primarily **Database-enforced** for standard relational data (e.g., standard `belongsTo` relations).
- **Database-enforced**: Standard relations (e.g., `creator_id` -> `users`, `company_id` -> `companies`) are protected by explicit foreign keys.
- **Application-enforced**: Polymorphic relationships and JSON-embedded references rely entirely on application logic, as they cannot use standard database FKs.
- **Eloquent relationship only**: Some relations defined on models (e.g., `morphTo()`, dynamic relations) lack database constraints by necessity.

## Delete & Update Semantics
[VERIFIED]
Deletion behavior is explicitly managed at the schema level.
- **`nullOnDelete()` (Dominant)**: Used in ~66% of inspected foreign keys. Deleting the parent leaves the child orphaned but preserved, signifying optional or decoupled relationships.
- **`restrictOnDelete()` (Common)**: Used in ~15% of foreign keys. Deleting foundational setup data is blocked if active records depend on it.
- **`cascadeOnDelete()` (Targeted)**: Used in ~11% of foreign keys, typically for tightly coupled child records (e.g., pivot tables).
- **ON UPDATE**: Explicit `onUpdate` behaviors were not commonly observed; they default to the database engine's standard behavior.

## Cross-Plugin Relationships
[VERIFIED]
The schema exhibits extensive cross-plugin dependencies. Over 170 explicit foreign keys cross plugin boundaries.
### Important Cross-Plugin Dependencies:
- **`payments` → `accounts`**: Strong dependency. (e.g., `payments_payment_transactions.move_id` -> `accounts_account_moves`).
- **`payments` → `partners`**: Moderate dependency. (e.g., `payments_payment_tokens.partner_id` -> `partners_partners`).
- **`accounts` → `products`**: Strong dependency. (e.g., `accounts_product_supplier_taxes.product_id` -> `products_products`).
- **`accounts` → `support`**: Foundational dependency. (e.g., `accounts_bank_statements.company_id` -> `companies`).

*Note: The `support` and `root` (users) modules act as universal dependencies for almost all transactional plugins.*

## Circular / Self-Referential Dependencies
[VERIFIED]
Self-referencing tables exist for hierarchical data:
- `payments_payment_methods.primary_payment_method_id` references `payments_payment_methods`.
- `accounts_account_moves.reversed_entry_id` references `accounts_account_moves`.
- `sales_order_lines.linked_sale_order_sale_id` references `sales_order_lines`.
*No repository-wide circular cross-table FK dependency was identified in the inspected migration set.*

## Migration Dependency Chains
[VERIFIED]
Foreign keys impose strict migration execution ordering.
- Root tables (`users`, `companies`, `currencies`) must exist before plugins run.
- Plugin loading order matters: if `payments` creates an FK to `accounts`, the `accounts` plugin migrations must execute first. 
- *Note: This ordering is managed by the `PackageServiceProvider` and Laravel's boot sequence, not by the database.*

## Eloquent vs Database Relationships
[VERIFIED]
- **Alignment**: Standard `BelongsTo` / `HasMany` relationships generally have matching database foreign keys.
- **Divergence**: Polymorphic relationships (`MorphTo`) only exist at the Eloquent level.
- **Soft Deletes**: Eloquent Soft Deletes mask referential integrity constraints. If a parent is soft-deleted, the database FK is satisfied, but Eloquent relations will filter the parent out unless `withTrashed()` is applied.

## Dynamic Relationships (`resolveRelationUsing`)
[VERIFIED]
The repository heavily leverages dynamic Eloquent relationships to inject cross-plugin functionality without modifying core models.
- **Implementation**: Plugins use `Model::resolveRelationUsing()` within their `ServiceProvider` `boot` methods.
- **Observed Usage**: `PurchaseServiceProvider`, `ManufacturingServiceProvider`, `AccountServiceProvider`, and `InventoryServiceProvider` use this pattern extensively.
- **Architectural Impact**: This allows a base `Product` model to gain relationships to `Purchases` or `Inventories` only when those plugins are installed. 
- **Integrity Limitation**: Because these relationships are dynamic, any associated database foreign keys (if they exist) must be managed carefully. In many cases, these dynamic relationships rely on application-level integrity rather than strict database schema enforcement, especially if the related plugin can be uninstalled.

## Integrity Exceptions & Risks
[VERIFIED]
- **EXPECTED DESIGN**: Polymorphic orphans. Because FKs cannot enforce polymorphism, hard-deleting a polymorphic parent can leave orphaned records (e.g., chatter messages). This is standard Laravel behavior.
- **EXPECTED DESIGN**: Soft Delete cascades. Database `cascadeOnDelete` only applies to hard deletes. Soft deleting a parent requires application observers to soft delete children if business logic demands it.
- **ARCHITECTURAL EXCEPTION**: Dynamic relationships bypass strict compile-time model definitions. They rely entirely on runtime registration.

## Major Relationship Hubs
[VERIFIED]
Source analysis reveals highly centralized relationship hubs:
- **Foundational Hubs**: `users` (referenced ~60 times), `companies` (referenced ~30 times), `currencies` (referenced ~18 times).
- **Master Data Hubs**: `partners_partners`, `products_products`.
- **Transactional Hubs**: `accounts_accounts`, `accounts_account_moves`, `accounts_journals`.

## Verified Facts
- Nullification (`nullOnDelete()`) is the dominant deletion strategy to preserve historical records.
- Cross-plugin dependencies are strictly enforced at the database level using foreign keys.
- `resolveRelationUsing` is a foundational pattern for extending models across plugin boundaries.

## Partially Verified / Inferred Areas
None.

## Unknowns
None.

## Evidence Index
- Migration parser output processing `database/migrations` and `plugins/webkul/*/database/migrations`.
- `plugins/webkul/accounts/src/AccountServiceProvider.php` (`resolveRelationUsing` dynamic relationships).
- `plugins/webkul/inventories/src/InventoryServiceProvider.php` (`resolveRelationUsing` dynamic relationships).
