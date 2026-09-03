---
status: verified
source_of_truth: source-code
last_verified: 2026-09-03
scope: global
confidence: high
---

# Aureus ERP — Forbidden Patterns

## 1. Overview & Purpose

This document is the capstone file of the Phase 10 AI Rules system. It codifies the twelve critical anti-patterns, conceptual traps, and recurring mistakes identified across Phases 1–9 of the Aureus ERP audit.

While other rule files define what developers and AI agents *must* do, this document explicitly defines what developers and AI agents **MUST NOT DO**.

Every pattern documented here has occurred in this repository or in historical audits of this codebase. For each forbidden pattern, this document specifies:
- **Pattern Name**
- **What it looks like**
- **Why it is tempting**
- **Specific Aureus ERP instance(s)**
- **Evidence**
- **Binding Rule that prevents it**

---

## 2. The Twelve Real Project Anti-Patterns

### 1. Declaration ≠ Enforcement

- **What it looks like**: Assuming that because a validation rule exists in a Form Request, a permission exists in Filament Shield, or a policy method exists, the underlying database and business operations are universally secure.
- **Why it is tempting**: In Laravel, declaring `$this->authorize('update', $record)` or defining form validation creates the mental illusion that the entire operation is protected everywhere.
- **Specific Aureus ERP instance(s)**:
  - Laravel Policies and Filament Shield permissions exist for domain resources, but direct API routes in `routes/api.php`, console commands, and service methods bypass policies completely unless explicitly called.
  - Multi-company relational consistency between parent and child models (e.g. verifying that a warehouse belongs to the same company as the sales order) was historically filtered in UI select dropdowns, but lacked backend enforcement until `ChecksCompanyConsistency` was introduced.
- **Evidence**:
  - `docs/security/authorization.md:25-28`
  - `docs/business-rules/accounting.md:376`
  - `plugins/webkul/support/src/Traits/ChecksCompanyConsistency.php`
- **Rule that prevents it**:
  - Developers and AI agents MUST NOT treat policy declarations, Filament Shield permissions, or UI form rules as proof of complete backend authorization or validation coverage.
  - Core security constraints, multi-company referential boundaries, and financial balances MUST be enforced at the service or model layer.

---

### 2. Enum/Schema ≠ Workflow

- **What it looks like**: Observing a database table, an enum case, or a UI button and assuming that the full operational workflow is implemented and running.
- **Why it is tempting**: The presence of the schema or UI suggests that the engineering work was completed.
- **Specific Aureus ERP instance(s)**:
  - **Timer / Stopwatch** in `manufacturing`: Work order models contained timer columns and UI buttons, but lacked full background duration accumulation logic.
  - **Unbuild Orders** in `manufacturing`: Tables and enum states existed for unbuilding manufactured goods, but inverse disassembly costing and inventory reversal workflows were not fully implemented.
  - **Automatic Reordering Rules** in `inventories`: `inventories_orderpoints` schema existed, but lacked background scheduling triggers to automatically convert stock deficits into purchase requisitions.
- **Evidence**:
  - `docs/workflows/manufacturing.md`
  - `docs/workflows/inventory.md`
  - `plugins/webkul/manufacturing/src/Models/WorkOrder.php`
- **Rule that prevents it**:
  - Developers and AI agents MUST NOT assume a business workflow is functional merely because an enum case, database column, or UI button represents it.
  - Operational capabilities MUST be verified by tracing the execution path from trigger to persistence.

---

### 3. Dependency Conflation (Composer ≠ Runtime Dependencies ≠ Code Consumption)

- **What it looks like**: Adding a requirement to `composer.json` and assuming it dictates Aureus ERP plugin installation order, or adding `use Webkul\Account\Models\Move;` and assuming the `accounts` plugin is installed in the database.
- **Why it is tempting**: In standard standalone PHP packages, `composer.json` is the sole dependency mechanism.
- **Specific Aureus ERP instance(s)**:
  - Aureus ERP plugin installation order is governed exclusively by `$package->hasDependencies([...])` executed by `InstallPluginCommand`.
  - None of the 9 Core Plugins declare `hasDependencies()`, yet they import classes across packages.
  - Optional plugins importing other optional plugin models without declaring `hasDependencies()` or checking `Package::isPluginInstalled()` crash at runtime when the dependency is uninstalled.
- **Evidence**:
  - `plugins/webkul/plugin-manager/src/Package.php:111`
  - `plugins/webkul/plugin-manager/src/Console/Commands/InstallPluginCommand.php`
  - `docs/architecture/plugin-registry.md`
- **Rule that prevents it**:
  - A plugin MUST declare installation-order prerequisites exclusively via `Package::hasDependencies([...])`.
  - `composer.json` MUST NOT be treated as the source of truth for plugin installation order.
  - Code-level consumption (`use Webkul\...`) MUST NOT be treated as equivalent to a declared plugin installation dependency.

---

### 4. Migration File ≠ Executed Migration

- **What it looks like**: Seeing a migration file in `plugins/webkul/<plugin>/database/migrations/` on disk and assuming that the corresponding database table or column exists in MySQL/PostgreSQL.
- **Why it is tempting**: Standard Laravel executes all `.php` files in `database/migrations/`.
- **Specific Aureus ERP instance(s)**:
  - **`EmailTemplate` Anomaly**: Declared in `SupportServiceProvider::hasMigrations()`, but missing from disk.
  - **`support` Unique Companies Index Anomaly**: File `2026_03_09_000001_add_unique_index_to_companies_name.php` exists on disk in `support`, but was omitted from `hasMigrations()` and therefore never executed.
  - **`time-off` Default Leave Types Anomaly**: File `2026_08_04_100000_share_default_time_off_leave_types.php` exists on disk in `time-off`, but was omitted from `hasMigrations()` and therefore never executed.
  - **`employees` Orphaned Calendar Migrations**: Three migration files exist on disk in `employees`, but were omitted from `hasMigrations()` and remain dormant.
- **Evidence**:
  - `plugins/webkul/support/src/SupportServiceProvider.php:60`
  - `plugins/webkul/time-off/src/TimeOffServiceProvider.php`
  - `plugins/webkul/employees/src/EmployeeServiceProvider.php`
- **Rule that prevents it**:
  - A migration file existing on disk MUST NOT be treated as proof that the migration runs.
  - Every new migration file MUST be explicitly registered inside the `$package->hasMigrations([...])` array in the plugin's service provider and executed via `$package->runsMigrations()`.

---

### 5. Architectural Rationale ≠ Verified Behavior

- **What it looks like**: Observing a code pattern or statistical frequency in the repository and inventing an unevidenced "architectural design philosophy" or author intent to explain it.
- **Why it is tempting**: Human analysts and AI agents naturally seek satisfying narrative justifications for why code was written in a certain way.
- **Specific Aureus ERP instance(s)**:
  - **Foreign-Key Nullability**: Observing that 59.7% of repository foreign keys use `nullOnDelete()` tempted past audits to claim that Aureus ERP had an "explicit architecture-wide preference for nullable cascading", whereas source inspection proved it was merely uncoordinated historical author choice, and financial/accounting tables strictly require `restrictOnDelete()` or cascade integrity.
  - **Extension Plugin Separation**: Assuming `contacts` was separated from `partners` for complex DDD bounded-context rationale, whereas source code reveals it is simply an administrative presentation wrapper around `partners` models.
- **Evidence**:
  - `docs/database/schema-conventions.md:210-225`
  - `docs/ai/database-rules.md:75-79`
  - `docs/ai/terminology.md`
- **Rule that prevents it**:
  - Developers and AI agents MUST distinguish verified observed behavior from inferred architectural rationale.
  - Inferred author intent MUST NOT be elevated to an architectural rule unless explicitly corroborated by design documentation, ADRs, or source code comments.

---

### 6. Absolute Path ≠ Canonical Documentation Path

- **What it looks like**: Inserting URI schemas such as `file://` or machine-local absolute paths (e.g., `~/projects/...`, `/absolute/path/...`, drive letter paths) into markdown documentation files under `docs/`.
- **Why it is tempting**: AI assistants and IDE terminal outputs frequently use machine-specific absolute file URIs.
- **Specific Aureus ERP instance(s)**:
  - Early historical phase audits leaked machine-local user paths, breaking portability across developer workstations and failing automated CI path audits.
- **Evidence**:
  - `Aureus ERP — Phase 10_Remaining AI Rules — Final Master Execution Prompt.md:231-246, 1750-1755`
- **Rule that prevents it**:
  - Canonical documentation in `docs/` MUST use repository-relative paths exclusively (e.g. `plugins/webkul/sales/src/Models/Order.php`).
  - Machine-specific absolute paths and `file://` URIs are STRICTLY FORBIDDEN in canonical documentation files.

---

### 7. Silent Protected-File Modification

- **What it looks like**: Discovering a bug or typo in an existing plugin or historical documentation file (such as `AGENTS.md` or a core service) and directly editing that file while tasked with a scoped documentation phase.
- **Why it is tempting**: Developers and AI agents instinctively want to fix bugs immediately upon discovery.
- **Specific Aureus ERP instance(s)**:
  - During Phase 10, typos in `AGENTS.md` (Livewire v3) and missing migrations in `support` were identified. Directly editing those files would violate phase isolation and create unreviewed code modifications.
- **Evidence**:
  - `Aureus ERP — Phase 10_Remaining AI Rules — Final Master Execution Prompt.md:464-494, 1867-1872`
- **Rule that prevents it**:
  - Developers and AI agents MUST NOT modify files outside the authorized write scope of the current task.
  - Discovered bugs or proposed corrections outside the active scope MUST be documented in the Exit Report under "Proposed corrections (not applied)".

---

### 8. Zero Unknowns ≠ Complete Understanding

- **What it looks like**: Concluding that a subsystem is 100% bug-free and fully understood because an earlier phase document concluded with `## Unknowns: None identified`.
- **Why it is tempting**: "Zero Unknowns" provides a false sense of finality and completeness.
- **Specific Aureus ERP instance(s)**:
  - Earlier database and security documentation recorded "None identified" under Unknowns, yet subsequent audits discovered that `security` had zero tests, that `EmailTemplate` had a missing migration file, and that 17 of 28 plugins lacked automated tests.
- **Evidence**:
  - `docs/database/schema-conventions.md:363-365`
  - `docs/ai/context.md:90`
- **Rule that prevents it**:
  - The absence of explicit `[UNKNOWN]` markers MUST NOT be treated as proof of exhaustive understanding.
  - Critical implementation details MUST be verified directly against source code and lockfiles before making architectural commitments.

---

### 9. Aggregate Summary ≠ Fresh Verification

- **What it looks like**: Copying historical summary numbers (e.g. "6 observers", "52 services", "9 tested plugins") from past reports into new architectural documentation.
- **Why it is tempting**: Re-scanning dozens of directories and running fresh AST parsing scripts requires extra effort.
- **Specific Aureus ERP instance(s)**:
  - Historical documentation reported 6 observers and 52 services. Fresh verification in Phase 3/10 revealed **7 observers** (discovering `ProductAttributeObserver` in `products`) and **53 services**.
  - Similarly, assuming 19 optional plugins have 10 tested without fresh checking leads to inaccurate audit conclusions.
- **Evidence**:
  - `docs/architecture/events-catalog.md:398-412`
  - `docs/ai/testing-rules.md:23-55`
- **Rule that prevents it**:
  - Whenever an architectural rule, security review, or documentation metric depends on numerical precision, developers and AI agents MUST freshly verify the count from source code.
  - Historical summaries MUST NOT be trusted without direct verification.

---

### 10. Stale Documentation ≠ Source of Truth

- **What it looks like**: Reading `AGENTS.md` where it states `livewire/livewire (LIVEWIRE) - v3` and developing Livewire v3 components.
- **Why it is tempting**: Root project guideline files like `AGENTS.md` and `README.md` are presumed to be authoritative by AI agents.
- **Specific Aureus ERP instance(s)**:
  - `AGENTS.md:19` explicitly claims Livewire is on v3. However, `composer.lock` proves that `livewire/livewire` is installed at `v4.3.3`. Writing v3 code leads to syntax errors and broken component lifecycles.
- **Evidence**:
  - `AGENTS.md:19`
  - `composer.lock` (line entries for `livewire/livewire` specifying `v4.3.3`)
  - `docs/ai/context.md:40-43`
- **Rule that prevents it**:
  - The Source-of-Truth Hierarchy MUST be obeyed:
    `Source Code > Tests > Migrations/Schema > Config > Composer Metadata > Documentation > Previous AI`.
  - Documentation MUST NOT override current source-code evidence or lockfiles.

---

### 11. Schema/API/UI Surface ≠ Operational Capability

- **What it looks like**: Assuming that because an API route, an Eloquent model relation, a form toggle, or a database column is declared and visible on the surface, the full end-to-end operational capability is functioning.
- **Why it is tempting**: The API endpoint responds with HTTP 200, the dropdown shows data, or the relation returns models, conveying the impression of complete feature maturity.
- **Specific Aureus ERP instance(s)**:
  - **Price Rules** in `products`: `PriceRule` model, schema, and API routes exist, but the sales pricing engine does not evaluate or apply price rules to sales order lines at checkout.
  - **Dynamic Relations** in `partners`: Relations dynamically injected via `resolveRelationUsing()` onto `Partner` exist on the model, but are not automatically exposed in API resources or UI tables without explicit wiring.
  - **Journal Item Reconciliation**: UI toggle to reconcile invoice payments exists, but multi-currency exchange gain/loss journal adjustments were not automatically generated until dedicated services were wired up.
- **Evidence**:
  - `plugins/webkul/products/src/Models/PriceRule.php`
  - `plugins/webkul/partners/src/Models/Partner.php`
  - `docs/business-rules/sales.md`
- **Rule that prevents it**:
  - Surface presence (an API route, Eloquent relation, UI toggle, or database column) MUST NOT be equated with complete operational capability.
  - Developers and AI agents MUST verify that the backend engine actually computes, applies, and persists the required business effects.

---

### 12. Naming ≠ Behavior

- **What it looks like**: Assuming that because a class, service, enum, or method carries a strong, familiar, or enterprise-grade name, it implements the full behavior normally associated with that name.
- **Why it is tempting**: Names like `Bouncer` or `EmailTemplateService` evoke powerful external packages or enterprise patterns, leading developers to assume capabilities without reading the implementation.
- **Specific Aureus ERP instance(s)**:
  - `Webkul\Security\Bouncer`: Named "Bouncer", tempting developers to invoke `silber/bouncer` methods (`allow()`, `disallow()`, `can()`), whereas it is a custom internal class providing only `isSuperAdmin()`, `getAuthorizedUserIds()`, and `hasRole()`.
  - `Webkul\Support\Services\EmailTemplateService`: Elegantly named service class, but imports non-existent `DynamicEmail` and queries non-existent table `email_templates`.
  - `PermissionType::SELF`: Mentioned informally in conversation, but does not exist in `Webkul\Security\Enums\PermissionType` (the canonical case is `INDIVIDUAL`).
- **Evidence**:
  - `plugins/webkul/security/src/Bouncer.php`
  - `plugins/webkul/support/src/Services/EmailTemplateService.php`
  - `plugins/webkul/security/src/Enums/PermissionType.php`
  - `docs/ai/terminology.md`
- **Rule that prevents it**:
  - Class, service, and enum names MUST NOT be equated with external library behavior or assumed functionality.
  - Developers and AI agents MUST inspect the actual concrete method signatures, properties, and constants of the symbol before consuming it.

---

## 3. Consolidation & Boundary Audit

Consolidation of these twelve anti-patterns into `docs/ai/forbidden-patterns.md` creates a dedicated negative rule layer that reinforces the positive standards codified in the other seven rule files:

| Rule File | Primary Positive Domain | Forbidden Pattern Boundary Maintained |
| :--- | :--- | :--- |
| `docs/ai/terminology.md` | Canonical names and misconception glossary | Distinguishes terms; defers behavioral anti-patterns to this file. |
| `docs/ai/architecture-rules.md` | Structural lifecycles, panels, zero-table layers | Defines structural requirements; references Anti-Patterns #3, #5, and #6. |
| `docs/ai/plugin-rules.md` | Creation & modification checklists, company traits | Defines checklists; references Anti-Patterns #3, #4, and #7. |
| `docs/ai/database-rules.md` | Migration registration, FK delete rules | Defines schema rules; references Anti-Patterns #4, #5, and #11. |
| `docs/ai/security-rules.md` | Company queries, Bouncer review, OwnerSource | Defines security standards; references Anti-Patterns #1, #8, and #12. |
| `docs/ai/coding-rules.md` | Naming, Filament components, background tasks | Defines implementation rules; references Anti-Patterns #2, #11, and #12. |
| `docs/ai/testing-rules.md` | Pest conventions, priority matrix, new plugin rules | Defines testing standards; references Anti-Patterns #8, #9, and #10. |

Duplication has been eliminated: specific implementation instructions remain in their respective domain files, while universal project-level hazards are unified herein.
