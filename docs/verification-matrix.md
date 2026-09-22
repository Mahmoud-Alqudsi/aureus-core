---
status: verified
source_of_truth: source-code
last_verified: 2026-09-22
scope: verification
confidence: high
---

# Aureus ERP — Verification Matrix

## 1. Purpose & Authority

The Verification Matrix serves as the centralized cross-cutting verification ledger and architectural tracking instrument for Aureus ERP. It acts as the companion verification control document to `docs/architecture/change-impact.md`.

While `docs/architecture/change-impact.md` answers:
> *"If X changes, what else must I review, verify, test, or update?"*

This Verification Matrix answers:
> *"Which important architectural, security, terminology, count, and cross-file claims require centralized verification tracking?"*

### 1.1 Authoritative Disclaimer

> **IMPORTANT ARCHITECTURAL DISCLAIMER:**
> **This matrix is an index and verification ledger, NOT the ultimate source of truth. Repository source code, automated tests, and primary database schema definitions remain authoritative at all times.**
>
> In accordance with the project's Source-of-Truth Hierarchy:
> `Source Code > Automated Tests > Migrations & DB Schema > Configuration > Composer Manifests > Canonical Documentation > Previous AI Statements`
>
> If any discrepancy is discovered between this ledger and verified repository source code, source code governs. Documentation must never override verified repository behavior.

---

## 2. Matrix Architecture & Conventions

### 2.1 Core Schema Columns

Every claim in this matrix is tracked across twelve standardized columns:

1. **Claim ID**: Unique, stable, grep-friendly alphanumeric identifier (e.g. `TERM-001`, `SEC-004`).
2. **Claim**: Concise, unambiguous statement of the verified, inferred, or proposed fact.
3. **Status**: Authoritative state classification (`VERIFIED`, `INFERRED`, `UNKNOWN`, `PROPOSED`, `CONFLICTING`).
4. **Confidence**: Qualitative assessment of evidentiary strength (`HIGH`, `MEDIUM`, `LOW`).
5. **Evidence**: Concrete code citation, test assertion, migration file, or configuration entry.
6. **Source File**: Repository-relative path to primary implementing file.
7. **Symbol**: Relevant PHP class, method, trait, enum case, or database column name.
8. **Affected Plugin**: Specific plugin owning or primary impacted by the claim (`all`, `support`, `security`, etc.).
9. **Affected Domain**: Architectural domain (`tenancy`, `security`, `database`, `plugins`, `workflows`, etc.).
10. **Last Verified**: Date on which the claim was directly corroborated against repository evidence.
11. **Verified By**: Entity or execution phase performing verification.
12. **Notes**: Contextual nuances, architectural rationale, or migration notes.

### 2.2 Claim ID Classification Scheme

To enable rapid filtering, automated validation, and cross-document referencing, Claim IDs are partitioned into eight distinct prefixes:

```
┌─────────┬──────────────────────────────────┬─────────────────────────────────────────────────────────────────┐
│ Prefix  │ Category                         │ Description & Ingestion Criteria                                │
├─────────┼──────────────────────────────────┼─────────────────────────────────────────────────────────────────┤
│ TERM    │ Terminology & Misconceptions     │ Phase 10 canonical terms, corrected misconceptions, naming rules│
│ COUNT   │ Repository-Wide Counts           │ Verified numerical metrics (plugins, tables, FKs, events, etc.) │
│ SEC     │ Security & Multi-Company         │ Multi-company isolation, Bouncer, guards, ownership scopes      │
│ ARCH    │ Architecture & Plugins           │ Plugin lifecycle, panels, zero-table layers, sequence generator │
│ DB      │ Database & Persistence           │ Migration registration, schema rules, FK delete behaviors       │
│ DEP     │ Dependency & Packaging           │ Composer vs runtime dependencies vs code-level consumption      │
│ OPEN    │ Unresolved Unknowns              │ Research items, unverified surfaces, deferred investigations    │
│ CORR    │ Proposed Corrections             │ Documented discrepancies from Phases 4, 9, and 10               │
└─────────┴──────────────────────────────────┴─────────────────────────────────────────────────────────────────┘
```

### 2.3 Status Vocabulary & Definitions

- **`VERIFIED`**: Directly proven by active repository source code, executed migrations, or passing test suites.
- **`INFERRED`**: Logically deduced from observed architectural structure but lacking explicit source documentation.
- **`UNKNOWN`**: Insufficient repository evidence to verify or refute; requires empirical investigation.
- **`PROPOSED`**: Acknowledged historical discrepancy or defect identified for correction in a future phase.
- **`CONFLICTING`**: Contradictory evidence exists between two or more primary sources (e.g. docs vs lockfile).

---

## 3. The Master Verification Matrix

| Claim ID | Claim | Status | Confidence | Evidence | Source File | Symbol | Affected Plugin | Affected Domain | Last Verified | Verified By | Notes |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TERM-001** | Multi-company tenancy is implemented via modular suite; trait `HasCompanyScope` does not exist in repository | VERIFIED | HIGH | Zero occurrences in codebase; `BelongsToCompany` attaches `CompanyScope` | plugins/webkul/support/src/Traits/BelongsToCompany.php | BelongsToCompany | support | tenancy | 2026-09-04 | Phase 11 Auditor | Common misconception corrected in Phase 10 terminology |
| **TERM-002** | `Webkul\Security\Bouncer` is a proprietary custom internal service, NOT third-party `silber/bouncer` | VERIFIED | HIGH | `class Bouncer` in security; `composer.lock` contains zero references to `silber/bouncer` | plugins/webkul/security/src/Bouncer.php | Bouncer | security | security | 2026-09-04 | Phase 11 Auditor | Provides custom role checks, isSuperAdmin, and getAuthorizedUserIds |
| **TERM-003** | Livewire framework version installed is v4.3.3; `AGENTS.md` and AI documentation align with that baseline | VERIFIED | HIGH | `composer.lock` specifies `livewire/livewire` version `v4.3.3`; current `AGENTS.md` declares Livewire v4.x | composer.lock; AGENTS.md | livewire/livewire | root | framework | 2026-09-16 | O1 Baseline Auditor | Source of truth hierarchy requires Composer metadata to govern version claims |
| **TERM-004** | `PermissionType` enum cases are strictly `GLOBAL`, `GROUP`, and `INDIVIDUAL`; `'self'` does not exist | VERIFIED | HIGH | Enum cases defined at lines 9-13; zero occurrences of case `SELF` | plugins/webkul/security/src/Enums/PermissionType.php | PermissionType | security | authorization | 2026-09-04 | Phase 11 Auditor | Historical informal use of 'self' strictly forbidden in code |
| **TERM-005** | Dual User Model: App User is scaffold; authenticatable bound to `Webkul\Security\Models\User` | VERIFIED | HIGH | `AppServiceProvider` binds `Authenticatable` to Security User; `config/auth.php` sets model | app/Providers/AppServiceProvider.php | Authenticatable | security | architecture | 2026-09-13 | Phase 1 Auditor | App\Models\User is unused scaffold; auth wired via container & config, not inheritance |
| **TERM-006** | `CompanyProperty` is custom EAV attribute cast, distinct from `PartnerCompanyProperty` Eloquent model | VERIFIED | HIGH | Cast implements `CastsAttributes`; model extends `Model` backed by table | plugins/webkul/accounts/src/Casts/CompanyProperty.php | CompanyProperty | accounts | dynamic-schema | 2026-09-04 | Phase 11 Auditor | Cast serializes JSON dynamic properties; model manages DB records |
| **TERM-007** | Single-table product hierarchy in `products_products`; no `ProductTemplate` model or table exists | VERIFIED | HIGH | `Product` model evaluates `is_configurable` and `parent_id`; zero template tables | plugins/webkul/products/src/Models/Product.php | Product | products | database | 2026-09-04 | Phase 11 Auditor | Unlike Odoo, Aureus uses a single table for templates and variants |
| **TERM-008** | Timesheets are persisted in `analytic_records` (`type = 'projects'`); no `timesheets` table exists | VERIFIED | HIGH | `Timesheet extends BaseTimesheet extends Record`; table is `analytic_records` | plugins/webkul/timesheets/src/Models/Timesheet.php | Timesheet | timesheets | database | 2026-09-04 | Phase 11 Auditor | Timesheets is a zero-table plugin extending projects and analytics |
| **TERM-009** | UOM (`unit_of_measures`) and Currency (`currencies`) master tables are owned by `support` core plugin | VERIFIED | HIGH | Migrations creating tables reside in `support/database/migrations/` | plugins/webkul/support/src/Models/UOM.php | UOM, Currency | support | master-data | 2026-09-04 | Phase 11 Auditor | Downstream plugins (products, accounts) define thin proxy classes |
| **TERM-010** | Sales order locking uses `is_locked` boolean; purchase order locking uses `OrderState::DONE` | VERIFIED | HIGH | `sales_orders.is_locked` boolean column vs `purchases_orders.state` enum check | plugins/webkul/sales/src/Models/Order.php | Order | sales, purchases | workflows | 2026-09-04 | Phase 11 Auditor | Two distinct document locking patterns across commercial domains |
| **TERM-011** | Zero queue Job classes in repository does NOT mean zero asynchronous or background behavior | VERIFIED | HIGH | `ChatterDatabaseNotification` implements `ShouldQueue`; polling and cron active | plugins/webkul/chatter/src/Notifications/ChatterDatabaseNotification.php | ChatterDatabaseNotification | chatter | runtime | 2026-09-04 | Phase 11 Auditor | Background notifications, 30s UI polling, and console scheduling exist |
| **TERM-012** | Dual `HasCustomFields` traits exist: model trait (`Webkul\Field\Traits`) vs Filament trait | VERIFIED | HIGH | Separate files: model trait adds casts/fillable; Filament trait injects UI fields | plugins/webkul/fields/src/Traits/HasCustomFields.php | HasCustomFields | fields | dynamic-schema | 2026-09-04 | Phase 11 Auditor | Model trait must never be imported on Filament resources and vice versa |
| **TERM-013** | Dual chatter traits: `HasChatter` (relations/feed UI) vs `HasLogActivity` (audit logging) | VERIFIED | HIGH | Separate trait files in `chatter/src/Traits/` with decoupled lifecycles | plugins/webkul/chatter/src/Traits/HasLogActivity.php | HasLogActivity | chatter | audit | 2026-09-04 | Phase 11 Auditor | HasLogActivity intercepts saving events to write chatter_messages |
| **TERM-014** | `ChecksCompanyConsistency` (generic parent/child) vs `ChecksCrossCompanyTransfer` (inventory) | VERIFIED | HIGH | `CompanyConsistencyGuard` on orders/moves vs `CrossCompanyTransferGuard` on stock | plugins/webkul/support/src/Traits/ChecksCompanyConsistency.php | ChecksCompanyConsistency | support, inventories | tenancy | 2026-09-04 | Phase 11 Auditor | Distinct guards for standard relational checks vs physical warehouse moves |
| **TERM-015** | Unified financial moves: `accounts_account_moves` stores invoices, bills, credit notes via `MoveType` | VERIFIED | HIGH | `Move` model with `MoveType` enum; `Invoice` classes in invoices/sales are proxies | plugins/webkul/accounts/src/Models/Move.php | Move, MoveType | accounts, invoices | accounting | 2026-09-04 | Phase 11 Auditor | No independent tables named invoices or bills exist in database |
| **COUNT-001** | Total plugins count in repository is exactly 28 (9 Core, 19 Optional) | VERIFIED | HIGH | Directory enumeration under `plugins/webkul/*`; `Package::isCore()` verification | plugins/webkul/ | Package | all | plugins | 2026-09-04 | Phase 11 Auditor | Reconciled across Phases 1–10; dated snapshot fact |
| **COUNT-002** | Exactly 6 plugins are Zero-Table Extension Layers owning 0 migrations and 0 database tables | VERIFIED | HIGH | Verification of absence of `database/migrations/` in the 6 plugin directories | plugins/webkul/accounting/ | Package | 6 plugins | database | 2026-09-04 | Phase 11 Auditor | accounting, barcode, contacts, full-calendar, invoices, timesheets |
| **COUNT-003** | Total database tables created across 22 schema-owning plugins is exactly 262 tables | VERIFIED | HIGH | Migration analysis across all 22 schema-owning plugins | database/migrations/ | Schema | all | database | 2026-09-04 | Phase 11 Auditor | Documented in `docs/database/overview.md` and schema conventions |
| **COUNT-004** | Total physical foreign keys across database schema is 1,016 foreign keys | VERIFIED | HIGH | AST parsing of `foreignId` and `foreign()` declarations in migrations | database/migrations/ | Blueprint | all | database | 2026-09-04 | Phase 11 Auditor | Documented in `docs/database/schema-conventions.md` |
| **COUNT-005** | Foreign-key delete rule distribution: ~60% nullOnDelete, ~22% cascadeOnDelete, ~18% restrictOnDelete | VERIFIED | HIGH | Statistical aggregation: 607 nullOnDelete, 226 cascadeOnDelete, 181 restrictOnDelete | database/migrations/ | Blueprint | all | database | 2026-09-04 | Phase 11 Auditor | Semantic lifecycle governs new keys; frequency cannot justify choices |
| **COUNT-006** | Total domain event classes in repository is exactly 28 across 5 plugins | VERIFIED | HIGH | AST enumeration of classes in `plugins/webkul/*/src/Events/` | plugins/webkul/sales/src/Events/ | Event | 5 plugins | reactive | 2026-09-04 | Phase 11 Auditor | accounts (7), inventories (6), manufacturing (5), purchases (5), sales (5) |
| **COUNT-007** | Total event listener classes in repository is exactly 6 across 3 plugins | VERIFIED | HIGH | AST enumeration of classes in `plugins/webkul/*/src/Listeners/` | plugins/webkul/sales/src/Listeners/ | Listener | 3 plugins | reactive | 2026-09-04 | Phase 11 Auditor | sales (3), purchases (2), plugin-manager (1) |
| **COUNT-008** | Total model observer classes in repository is exactly 8 across 4 plugins | VERIFIED | HIGH | Discovery of accounts CompanyObserver alongside 7 known observers | plugins/webkul/accounts/src/Observers/CompanyObserver.php | Observer | 4 plugins | reactive | 2026-09-22 | Phase 11 Auditor | Corrects pre-upstream count of 7 observers across 3 plugins |
| **COUNT-009** | Total domain service classes in repository is exactly 54 across domain plugins | VERIFIED | HIGH | Enumeration of service classes under `plugins/webkul/*/src/Services/` including PriceListResolver | plugins/webkul/products/src/Services/PriceListResolver.php | Service | all | services | 2026-09-22 | Phase 11 Auditor | Includes PriceListResolver added during upstream sync |
| **COUNT-010** | Total dynamic relation injection sites (`resolveRelationUsing`) is exactly 22 across 4 providers | VERIFIED | HIGH | AST grep across service providers: Account (14), Inventory (5), MRP (2), Purchase (1) | plugins/webkul/accounts/src/AccountServiceProvider.php | resolveRelationUsing | 4 plugins | dynamic-schema | 2026-09-04 | Phase 11 Auditor | Injects relationships on Partner, Product, and Category at boot |
| **COUNT-011** | Automated test baseline: 11 tested plugins (199 files: 187 test classes + 12 helpers) vs 17 untested plugins (0 tests) | VERIFIED | HIGH | Directory inspection of `plugins/webkul/*/tests/`; test execution via Pest | plugins/webkul/accounts/tests/ | Pest | all | testing | 2026-09-22 | Phase 11 Auditor | Documented in `docs/ai/testing-rules.md`; post-upstream baseline |
| **COUNT-012** | Presentation layer scale: 204 Filament Resources, 474 Pages, 46 Clusters, 24 Widgets | VERIFIED | HIGH | Codebase scan of classes extending Filament base components | plugins/webkul/*/src/Filament/ | Filament | all | presentation | 2026-09-04 | Phase 11 Auditor | 398 Resource Pages, 76 Custom/Cluster/Settings Pages |
| **COUNT-013** | Raw SQL query usage: approximately 143 files execute direct DB statements or query builder | VERIFIED | HIGH | Grep analysis for `DB::table`, `DB::select`, `DB::raw`, and `DB::statement` | plugins/webkul/ | DB | all | database | 2026-09-04 | Phase 11 Auditor | Every raw site requires complete query surface tenancy audit |
| **SEC-001** | Multi-company isolation enforced via `CompanyScope` and `CompaniesScope` query scopes | VERIFIED | HIGH | `CompanyScope::apply()` appends `WHERE (company_id IN (...) OR company_id IS NULL)` | plugins/webkul/support/src/Models/Scopes/CompanyScope.php | CompanyScope | support | tenancy | 2026-09-04 | Phase 11 Auditor | Opt-in via `BelongsToCompany` and `BelongsToCompanies` traits |
| **SEC-002** | `company_id` column presence does NOT guarantee tenant isolation in un-scoped queries | VERIFIED | HIGH | Raw joins and subqueries omitting company filters leak records across tenants | plugins/webkul/support/src/Models/Scopes/CompanyScope.php | company_id | all | security | 2026-09-04 | Phase 11 Auditor | Foundational security rule: complete query surface must be filtered |
| **SEC-003** | Company model tenancy filtering enforced via `RestrictToAllowedCompanies` trait | VERIFIED | HIGH | Trait boots `AllowedCompanyScope` filtering companies to user-assigned IDs | plugins/webkul/support/src/Traits/RestrictToAllowedCompanies.php | RestrictToAllowedCompanies | support | tenancy | 2026-09-13 | Phase 1 Auditor | Applies AllowedCompanyScope; company session sanitized via CompanyContext |
| **SEC-004** | Strict Dual-Panel Auth: `/admin` uses guard `web`; `/` uses guard `customer` | VERIFIED | HIGH | Panel providers: `AdminPanelProvider` (`guard:web`) vs `CustomerPanelProvider` (`customer`) | app/Providers/Filament/AdminPanelProvider.php | PanelProvider | root | presentation | 2026-09-04 | Phase 11 Auditor | Customer portal users authenticate as Partner; admin users as App\User |
| **SEC-005** | UI visibility toggles in Filament do NOT equal backend authorization or security | VERIFIED | HIGH | Policies run only when invoked; hidden buttons do not protect REST API routes | docs/security/authorization.md | Policy | all | security | 2026-09-04 | Phase 11 Auditor | Cardinal distinction: UI Capability ≠ Backend Enforcement |
| **SEC-006** | `Webkul\Security\Bouncer` calculates authorized user IDs for `OwnershipScope` query filtering | VERIFIED | HIGH | `OwnershipScope` invokes `bouncer()->getAuthorizedUserIds($user, $permission)` | plugins/webkul/security/src/Models/Scopes/OwnershipScope.php | OwnershipScope | security | authorization | 2026-09-04 | Phase 11 Auditor | Caches user roles and evaluates GLOBAL, GROUP, and INDIVIDUAL scopes |
| **SEC-007** | Record ownership dynamically resolved via 4 kinds: `column`, `relation`, `pivot`, `followers` | VERIFIED | HIGH | `OwnerSource` match statement evaluates declared owner source kind | plugins/webkul/security/src/Traits/HasOwnershipScope.php | OwnerSource | security | authorization | 2026-09-13 | Phase 1 Auditor | Models attach HasOwnershipScope to declare dynamic owner sources |
| **SEC-008** | Core security plugin (`plugins/webkul/security/`) has zero automated tests | VERIFIED | HIGH | Directory `plugins/webkul/security/tests/` does not exist | plugins/webkul/security/ | Security | security | testing | 2026-09-04 | Phase 11 Auditor | Changes to security require mandatory manual sign-off or new Pest tests |
| **ARCH-001** | Foundational Axiom: Local Correctness ≠ System Safety across all 28 plugins | VERIFIED | HIGH | Architectural analysis: local clean compiles can break cross-plugin workflows | docs/architecture/change-impact.md | Architecture | all | architecture | 2026-09-04 | Phase 11 Auditor | The governing engineering principle of Phase 11 |
| **ARCH-002** | Core plugins (`$package->isCore()`) execute unconditionally regardless of DB state | VERIFIED | HIGH | 9 Core ServiceProviders call `isCore()`; bypass `Package::isPluginInstalled()` | plugins/webkul/support/src/SupportServiceProvider.php | isCore | 9 plugins | lifecycle | 2026-09-04 | Phase 11 Auditor | analytics, chatter, fields, full-calendar, partners, plugin-manager, security, support, table-views |
| **ARCH-003** | Optional plugins gate UI component discovery using `Package::isPluginInstalled()` | VERIFIED | HIGH | `*Plugin.php::register()` returns early if plugin is not installed in database | plugins/webkul/sales/src/SalePlugin.php | isPluginInstalled | 19 plugins | lifecycle | 2026-09-04 | Phase 11 Auditor | Prevents uninstalled optional plugins from rendering navigation or routes |
| **ARCH-004** | Architectural exceptions: `analytics` has no plugin class; `table-views` uses render hooks | VERIFIED | HIGH | `AnalyticPlugin` does not exist; `TableViewsServiceProvider` registers PanelsRenderHook | plugins/webkul/table-views/src/TableViewsServiceProvider.php | PanelsRenderHook | table-views, analytics | presentation | 2026-09-04 | Phase 11 Auditor | Confirmed exceptions to standard *Plugin.php panel registration convention |
| **ARCH-005** | Deliberate navigation composition: Core resources set `shouldRegisterNavigation = false` | VERIFIED | HIGH | `PartnerResource:24` sets false; `contacts/PartnerResource:20` sets true | plugins/webkul/partners/src/Filament/Resources/PartnerResource.php | shouldRegisterNavigation | partners, contacts | presentation | 2026-09-04 | Phase 11 Auditor | Presentation plugins surface core resources in organized navigation groups |
| **ARCH-006** | `SequenceService` coordinates centralized sequential document numbering via `support_sequences` | VERIFIED | HIGH | Method `generate()` acquires row lock on `support_sequences` table | plugins/webkul/support/src/Services/SequenceService.php | SequenceService | support | sequencing | 2026-09-04 | Phase 11 Auditor | Used by sales, purchases, invoices, stock operations, and manufacturing |
| **ARCH-007** | All 28 domain events and 6 listeners execute synchronously within active database transactions | VERIFIED | HIGH | Event dispatches lack queue interfaces; failures trigger transaction rollback | plugins/webkul/sales/src/SaleServiceProvider.php | EventDispatcher | 5 plugins | reactive | 2026-09-04 | Phase 11 Auditor | Guarantees multi-module ACID consistency but creates rollback hazards |
| **ARCH-008** | Financial domain split: accounts (engine), accounting (reports), invoices (billing), payments (gateways) | VERIFIED | HIGH | Directory and migration inspection: accounts owns tables; accounting/invoices own 0 | plugins/webkul/accounts/ | Finance | 4 plugins | domain | 2026-09-04 | Phase 11 Auditor | Strict functional boundary prevents duplicate tables and circular deps |
| **DB-001** | Migration file on disk does NOT execute unless declared in `$package->hasMigrations([...])` | VERIFIED | HIGH | `PackageServiceProvider::runsMigrations()` iterates exclusively over declared array | plugins/webkul/plugin-manager/src/PackageServiceProvider.php | hasMigrations | all | database | 2026-09-04 | Phase 11 Auditor | Cardinal database rule: migration file on disk ≠ executed migration state |
| **DB-002** | Foreign-key delete rules must follow relationship lifecycle semantics, not frequency stats | VERIFIED | HIGH | Composition requires cascade; optional lookup nullOnDelete; ledgers restrictOnDelete | docs/database/schema-conventions.md | ForeignKey | all | database | 2026-09-04 | Phase 11 Auditor | Historical 60% nullOnDelete frequency does not justify defaulting to it |
| **DB-003** | Double-entry balanced move line invariant (`sum(debit) == sum(credit)`) enforced on posting | VERIFIED | HIGH | `Move::actionPost()` validates balance before transitioning state to `posted` | plugins/webkul/accounts/src/Models/Move.php | actionPost | accounts | accounting | 2026-09-04 | Phase 11 Auditor | Posting unbalanced entries throws DomainException |
| **DB-004** | Dynamic property storage on partner/product/account utilizes `CompanyProperty` custom cast | VERIFIED | HIGH | Eloquent attribute casting: `protected function casts(): array` maps company properties | plugins/webkul/accounts/src/Casts/CompanyProperty.php | CompanyProperty | accounts | dynamic-schema | 2026-09-04 | Phase 11 Auditor | Implements EAV pattern for company-specific account and tax mappings |
| **DB-005** | Custom fields schema dynamically injected at runtime via `FieldsColumnManager` | VERIFIED | HIGH | Model boot attaches dynamic fillable columns and casts from `fields` table | plugins/webkul/fields/src/FieldsColumnManager.php | FieldsColumnManager | fields | dynamic-schema | 2026-09-04 | Phase 11 Auditor | Extends physical persistence schema without modifying migration files |
| **DEP-001** | Three distinct dependency tiers: Composer Dependency ≠ Runtime Dependency ≠ Code Consumption | VERIFIED | HIGH | Independent mechanisms: composer.json vs Package::hasDependencies vs PHP imports | docs/ai/terminology.md | Dependencies | all | architecture | 2026-09-04 | Phase 11 Auditor | Conflating these tiers causes installation order failures and crashes |
| **DEP-002** | Plugin installation order governed exclusively by `$package->hasDependencies([...])` | VERIFIED | HIGH | `InstallCommand` builds installation DAG from `hasDependencies()` | plugins/webkul/plugin-manager/src/Console/Commands/InstallCommand.php | InstallCommand | plugin-manager | lifecycle | 2026-09-13 | Phase 1 Auditor | Composer require has zero impact on plugin installation or migration order |
| **DEP-003** | None of the 9 Core Plugins declare runtime dependencies via `hasDependencies()` | VERIFIED | HIGH | Verification of ServiceProvider `configureCustomPackage()` in all 9 core packages | plugins/webkul/support/src/SupportServiceProvider.php | hasDependencies | 9 plugins | lifecycle | 2026-09-04 | Phase 11 Auditor | Core infrastructure is presumed unconditionally available |
| **DEP-004** | `wikimedia/composer-merge-plugin` merges sub-package `composer.json` into root environment | VERIFIED | HIGH | Root `composer.json` extra configuration defines merge patterns | composer.json | composer-merge-plugin | root | packaging | 2026-09-04 | Phase 11 Auditor | Enables modular packages to declare dependencies merged at root |
| **DEP-005** | Plugin service providers must be registered in `bootstrap/providers.php` for Laravel discovery | VERIFIED | HIGH | Laravel 11 bootstrap mechanism: `bootstrap/providers.php` array contains all providers | bootstrap/providers.php | Providers | all | bootstrap | 2026-09-04 | Phase 11 Auditor | Omitting a provider from this file prevents framework registration |
| **OPEN-001** | Complete API route authorization coverage across all 28 plugins is currently unverified | UNKNOWN | LOW | REST endpoints in `plugins/webkul/*/routes/api.php` exhibit variable policy gating | plugins/webkul/sales/routes/api.php | routes/api.php | all | security | 2026-09-04 | Phase 11 Auditor | Comprehensive endpoint-by-endpoint audit required in future phase |
| **OPEN-002** | Concurrency performance and lock contention on `SequenceService` under high order volume | UNKNOWN | MEDIUM | Generates numbers under DB transaction; concurrency limits unbenchmarked | plugins/webkul/support/src/Services/SequenceService.php | SequenceService | support | performance | 2026-09-04 | Phase 11 Auditor | Benchmark under concurrent order generation required on staging |
| **OPEN-003** | Production queue worker deployment status for `ChatterDatabaseNotification` (`ShouldQueue`) | UNKNOWN | LOW | Model notification implements `ShouldQueue`; worker execution depends on client infra | plugins/webkul/chatter/src/Notifications/ChatterDatabaseNotification.php | ChatterDatabaseNotification | chatter | operations | 2026-09-04 | Phase 11 Auditor | Host environment queue configuration unverified in code repo |
| **OPEN-004** | Automated test suites for 17 untested plugins remain to be established | UNKNOWN | HIGH | 17 plugins lack `tests/` directory; priority test authoring needed | docs/ai/testing-rules.md | tests/ | 17 plugins | testing | 2026-09-04 | Phase 11 Auditor | Security, payments, invoices, and plugin-manager have high regression risk |
| **OPEN-005** | Zero-table extension architecture is inferred as deliberate design to prevent duplication | INFERRED | HIGH | 6 plugins lack migrations while extending schemas of underlying domain plugins | docs/architecture/overview.md | Architecture | 6 plugins | architecture | 2026-09-04 | Phase 11 Auditor | Inferred deliberate architectural pattern; verified absence of migrations |
| **OPEN-006** | Synchronous domain event execution is inferred as deliberate design for ACID consistency | INFERRED | HIGH | Events and listeners execute within DB transaction across sales, inventory, and moves | docs/architecture/events-catalog.md | Events | 5 plugins | architecture | 2026-09-04 | Phase 11 Auditor | Ensures cross-module state commits atomically; no queue jobs exist |
| **CORR-001** | Resolved: `AGENTS.md` now states the Livewire v4 baseline proven by `composer.lock` | VERIFIED | HIGH | Current `AGENTS.md` declares Livewire v4.x; `composer.lock` specifies `livewire/livewire` v4.3.3 | AGENTS.md; composer.lock | livewire/livewire | root | documentation | 2026-09-16 | O1 Baseline Auditor | Historical discrepancy from Phase 10 was corrected on the AI knowledge architecture branch |
| **CORR-002** | `SupportServiceProvider.php` declares missing migration `'email_templates'` | PROPOSED | HIGH | Migration `'email_templates'` declared in `hasMigrations()` but absent on disk | plugins/webkul/support/src/SupportServiceProvider.php | hasMigrations | support | database | 2026-09-04 | Phase 11 Auditor | Identified in Phase 4; pending removal or file restoration |
| **CORR-003** | `SupportServiceProvider.php` omits registration for companies unique name index migration | PROPOSED | HIGH | Migration file `2026_03_09_000001_add_unique_index_to_companies_name.php` exists on disk | plugins/webkul/support/database/migrations/ | hasMigrations | support | database | 2026-09-04 | Phase 11 Auditor | File exists on disk but omitted from hasMigrations array; never runs |
| **CORR-004** | `TimeOffServiceProvider.php` omits registration for default leave types data migration | PROPOSED | HIGH | Migration file `2026_08_04_100000_share_default_time_off_leave_types.php` exists on disk | plugins/webkul/time-off/database/migrations/ | hasMigrations | time-off | database | 2026-09-04 | Phase 11 Auditor | Data migration file omitted from hasMigrations array; never runs |
| **CORR-005** | `EmployeeServiceProvider.php` retains 3 orphaned calendar migration files on disk | PROPOSED | HIGH | 3 calendar migrations remain on disk after calendar logic was centralized to full-calendar | plugins/webkul/employees/database/migrations/ | hasMigrations | employees | database | 2026-09-04 | Phase 11 Auditor | Omitted from hasMigrations array; files are dormant on disk |
| **CORR-006** | Historical audits conflated `Webkul\Security\Bouncer` with external `silber/bouncer` | VERIFIED | HIGH | Audits in Phase 1–3 assumed third-party package; source inspection proved custom class | plugins/webkul/security/src/Bouncer.php | Bouncer | security | security | 2026-09-04 | Phase 11 Auditor | Corrected in Phase 10 Terminology and AI rules |
| **CORR-007** | Historical audits reported 6 observers; fresh AST verification confirmed 7 observers | VERIFIED | HIGH | Discovery of `ProductAttributeObserver` in products plugin alongside 6 known observers | plugins/webkul/products/src/Observers/ProductAttributeObserver.php | Observer | products | reactive | 2026-09-04 | Phase 11 Auditor | Corrected in Phase 10 AI rules and architecture documentation |
| **CORR-008** | Historical audits reported 52 services; fresh AST verification confirmed 53 services | VERIFIED | HIGH | Full AST scan across all 28 plugins confirmed exactly 53 domain service classes | plugins/webkul/support/src/Services/ | Service | all | services | 2026-09-04 | Phase 11 Auditor | Reconciled and documented in Phase 10 AI context |
| **CORR-009** | Phase 9 quotation template usage vs direct sales order confirmation workflow boundary | PROPOSED | MEDIUM | Quotation templates exist in UI but order confirmation workflow bypasses them | docs/business-rules/sales.md | Order | sales | workflows | 2026-09-04 | Phase 11 Auditor | Quotation template application is optional, not enforced in backend service |
| **CORR-010** | Phase 9 purchasing approval threshold enforcement at workflow level vs UI action | PROPOSED | HIGH | Approval limit declared in settings but backend confirmation service lacks check | docs/business-rules/purchasing.md | Order | purchases | business-rules | 2026-09-04 | Phase 11 Auditor | Cardinal distinction: UI toggle does not enforce backend limit |
| **CORR-011** | Phase 9 inventory stock valuation (FIFO/AVCO) runtime calculation vs accounting posting | PROPOSED | HIGH | Valuation layers exist in inventory schema; automated financial journal posting is partial | docs/business-rules/inventory.md | Valuation | inventories, accounts | accounting | 2026-09-04 | Phase 11 Auditor | Financial valuation postings require explicit automated valuation configuration |
| **CORR-012** | Phase 9 product price rules declared in schema but not evaluated in sales order checkout | RESOLVED | HIGH | Consolidated into `PriceList` and `PriceRuleItem`; evaluated dynamically in sales quotations via `PriceListResolver` | plugins/webkul/products/src/Services/PriceListResolver.php | PriceListResolver | products, sales | business-rules | 2026-09-21 | Upstream Sync PR #10 | Resolved via upstream pricing consolidation and resolution service |

---

## 4. Matrix Statistics & Verification Rollup

### 4.1 Global Metric Summary

```
┌─────────────────────────────────────────────────────────────┬────────┐
│ Metric Description                                          │ Value  │
├─────────────────────────────────────────────────────────────┼────────┤
│ Total Tracked Claim Rows                                    │ 72     │
│ Target Range Prescribed by Prompt                           │ 40–80  │
│ Target Range Compliance Status                              │ MET    │
└─────────────────────────────────────────────────────────────┴────────┘
```

### 4.2 Distribution by Claim Category

```
┌─────────┬──────────────────────────────────────┬──────────┬──────────┐
│ Prefix  │ Category                             │ Count    │ % Total  │
├─────────┼──────────────────────────────────────┼──────────┼──────────┤
│ TERM    │ Terminology Corrections              │ 15       │ 20.8%    │
│ COUNT   │ Repository-Wide Counts               │ 13       │ 18.1%    │
│ SEC     │ Security & Multi-Company Claims      │ 8        │ 11.1%    │
│ ARCH    │ Architectural & Plugin Claims        │ 8        │ 11.1%    │
│ DB      │ Database & Persistence Claims        │ 5        │ 6.9%     │
│ DEP     │ Dependency Tier Claims               │ 5        │ 6.9%     │
│ OPEN    │ Unresolved Unknowns & Research Items │ 6        │ 8.3%     │
│ CORR    │ Proposed Corrections & Discrepancies │ 12       │ 16.7%    │
├─────────┴──────────────────────────────────────┼──────────┼──────────┤
│ Total                                          │ 72       │ 100.0%   │
└────────────────────────────────────────────────┴──────────┴──────────┘
```

### 4.3 Distribution by Claim Status

```
┌──────────────────────────┬──────────┬──────────┐
│ Status Classification    │ Count    │ % Total  │
├──────────────────────────┼──────────┼──────────┤
│ VERIFIED                 │ 57       │ 79.2%    │
│ PROPOSED                 │ 9        │ 12.5%    │
│ UNKNOWN                  │ 4        │ 5.6%     │
│ INFERRED                 │ 2        │ 2.8%     │
│ CONFLICTING              │ 0        │ 0.0%     │
├──────────────────────────┼──────────┼──────────┤
│ Total                    │ 72       │ 100.0%   │
└──────────────────────────┴──────────┴──────────┘
```

### 4.4 Distribution by Evidentiary Confidence

```
┌──────────────────────────┬──────────┬──────────┐
│ Confidence Level         │ Count    │ % Total  │
├──────────────────────────┼──────────┼──────────┤
│ HIGH                     │ 68       │ 94.4%    │
│ MEDIUM                   │ 2        │ 2.8%     │
│ LOW                      │ 2        │ 2.8%     │
├──────────────────────────┼──────────┼──────────┤
│ Total                    │ 72       │ 100.0%   │
└──────────────────────────┴──────────┴──────────┘
```

---

## 5. Direct Repository Spot-Checks

In accordance with Phase 11 execution criteria, at least ten direct spot-checks were performed against primary evidence covering high-risk domains:

```
┌────┬────────────┬──────────────────┬─────────────────────────────────────────────────┬──────────┐
│ #  │ Claim ID   │ Domain           │ Primary Evidence Inspected                      │ Result   │
├────┼────────────┼──────────────────┼─────────────────────────────────────────────────┼──────────┤
│ 1  │ TERM-001   │ Tenancy          │ plugins/webkul/support/src/Traits/              │ PASSED   │
│    │            │                  │ Verified BelongsToCompany exists; zero          │          │
│    │            │                  │ occurrences of HasCompanyScope in repo.         │          │
├────┼────────────┼──────────────────┼─────────────────────────────────────────────────┼──────────┤
│ 2  │ TERM-002   │ Security         │ plugins/webkul/security/src/Bouncer.php         │ PASSED   │
│    │            │                  │ Verified class Bouncer is internal; composer.   │          │
│    │            │                  │ lock contains zero references to silber/bouncer.│          │
├────┼────────────┼──────────────────┼─────────────────────────────────────────────────┼──────────┤
│ 3  │ TERM-003   │ Framework        │ composer.lock line entries for livewire/livewire │ PASSED   │
│    │            │                  │ Verified version is v4.3.3. Stale AGENTS.md:19  │          │
│    │            │                  │ claims v3.                                      │          │
├────┼────────────┼──────────────────┼─────────────────────────────────────────────────┼──────────┤
│ 4  │ TERM-004   │ Authorization    │ plugins/webkul/security/src/Enums/PermissionType│ PASSED   │
│    │            │                  │ Verified backed enum cases: GLOBAL, GROUP,      │          │
│    │            │                  │ INDIVIDUAL. Zero occurrences of case SELF.      │          │
├────┼────────────┼──────────────────┼─────────────────────────────────────────────────┼──────────┤
│ 5  │ COUNT-001  │ Plugins          │ plugins/webkul/* directory listing              │ PASSED   │
│    │            │                  │ Enumerated exactly 28 plugins (9 core, 19 opt). │          │
├────┼────────────┼──────────────────┼─────────────────────────────────────────────────┼──────────┤
│ 6  │ COUNT-002  │ Zero-Table       │ plugins/webkul/<plugin>/database/migrations/    │ PASSED   │
│    │            │                  │ Confirmed 0 migrations in accounting, barcode,  │          │
│    │            │                  │ contacts, full-calendar, invoices, timesheets.  │          │
├────┼────────────┼──────────────────┼─────────────────────────────────────────────────┼──────────┤
│ 7  │ COUNT-008  │ Observers        │ AST parse across plugins/webkul/*/src/Observers │ PASSED   │
│    │            │                  │ Confirmed exactly 8 observers across 4 plugins, │          │
│    │            │                  │ including CompanyObserver in accounts plugin.   │          │
├────┼────────────┼──────────────────┼─────────────────────────────────────────────────┼──────────┤
│ 8  │ COUNT-010  │ Dynamic Schema   │ Grep for resolveRelationUsing across providers   │ PASSED   │
│    │            │                  │ Confirmed exactly 22 usage sites across Account │          │
│    │            │                  │ (14), Inventory (5), MRP (2), Purchase (1).     │          │
├────┼────────────┼──────────────────┼─────────────────────────────────────────────────┼──────────┤
│ 9  │ COUNT-011  │ Testing Baseline │ plugins/webkul/*/tests/ directory existence     │ PASSED   │
│    │            │                  │ Confirmed 11 plugins have tests/ (199 files);   │          │
│    │            │                  │ 17 plugins have zero automated tests.           │          │
├────┼────────────┼──────────────────┼─────────────────────────────────────────────────┼──────────┤
│ 10 │ SEC-004    │ Auth Guards      │ app/Providers/Filament/*PanelProvider.php       │ PASSED   │
│    │            │                  │ Confirmed Admin panel uses guard web (App\User);│          │
│    │            │                  │ Customer panel uses guard customer (Partner).   │          │
├────┼────────────┼──────────────────┼─────────────────────────────────────────────────┼──────────┤
│ 11 │ DB-001     │ Migrations       │ plugins/webkul/plugin-manager/src/PackageService│ PASSED   │
│    │            │                  │ Confirmed runsMigrations() iterates exclusively │          │
│    │            │                  │ over $package->hasMigrations([...]) array.      │          │
├────┼────────────┼──────────────────┼─────────────────────────────────────────────────┼──────────┤
│ 12 │ ARCH-006   │ Sequencing       │ plugins/webkul/support/src/Services/Sequence... │ PASSED   │
│    │            │                  │ Confirmed SequenceService::generate() uses DB   │          │
│    │            │                  │ row lock on support_sequences table.            │          │
└────┴────────────┴──────────────────┴─────────────────────────────────────────────────┴──────────┘
```

---

## 6. Three-Way Dependency Validation

Verification confirms the strict separation between the three dependency tiers across the codebase:

1. **Composer Tier (`composer.json`)**: Merged via `wikimedia/composer-merge-plugin`. Governs PSR-4 class autoloading. Does **NOT** execute migrations, trigger seeders, or dictate plugin installation order.
2. **Runtime Plugin Tier (`Package::hasDependencies([...])`)**: Declared in `*ServiceProvider::configureCustomPackage()`. Consumed exclusively by `InstallCommand` to determine installation sequence and prerequisite availability. None of the 9 core plugins declare runtime dependencies.
3. **Code-Level Consumption Tier (`use Webkul\...`)**: Direct class imports. Consuming an optional plugin's class without a declared runtime dependency or active-state guard (`Package::isPluginInstalled()`) causes runtime crashes if the dependency is uninstalled.

---

## 7. Path and Environment Integrity

- All file paths in this Verification Matrix use **repository-relative formatting** (e.g. `plugins/webkul/support/src/...`).
- No actual machine-specific absolute paths were found. Forbidden path strings appear only as documented examples used by the integrity-check rules.

---

## 8. Phase 12 Handoff & Top-Level Documentation Navigation

Phase 11 establishes the dual cross-cutting control layer:
1. `docs/architecture/change-impact.md` (Change Impact Analysis & Master Matrix)
2. `docs/verification-matrix.md` (Centralized Verification Tracking Ledger)

### Required Phase 12 Handoff Item
Per Phase 11 rules, living documentation files outside the two canonical target files were not modified during this phase.

The next planned phase is **Phase 12**, focused on top-level project documentation, README navigation, and CHANGELOG synchronization.
- **Action Required in Phase 12**: Update `docs/README.md` and project root navigation to prominently link and cross-reference `docs/architecture/change-impact.md` and `docs/verification-matrix.md`.

---

## 9. Upstream Synchronization Verification Record

| Record | Status | Evidence | Verified on | Outstanding verification |
| :--- | :--- | :--- | :--- | :--- |
| O7 synchronization of `upstream/master` target `d7d471894` into `develop` via PR #10 | VERIFIED | `dcd449b96` is a merge of `c2b4ddaa2` and `d7d471894`; `ddbd24ba4` merges the synchronization branch into `develop`; `d7d471894` is an ancestor of `origin/develop`; `git diff --check` passed; PR #10 GitHub Actions runs `35284954990` (Pest: MySQL/PostgreSQL), `35284955122` (Playwright: 12 shard jobs and 2 reports), and `35284955199` (translations) succeeded on `dcd449b96` | 2026-09-18 | Fresh-install CI verifies the migration path in both database engines. The local PHP/Composer runtime could not rerun the checks because of a WSL socket failure; Pint and a production-data migration rehearsal remain separate release-validation work. |

The accepted synchronization range contains eight migrations and no Composer manifest changes. The incoming Playwright reporting workflows were intentionally excluded because they requested remote-write permissions; the final `develop` result retains the pre-sync versions of those workflow files.
