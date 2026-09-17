---
status: verified
source_of_truth: source-code
last_verified: 2026-09-17
scope: documentation-index
confidence: high
---

# Aureus ERP Documentation

The canonical entry point and navigation index for the Aureus ERP Living Documentation & AI Knowledge Base.

---

## Purpose

The Aureus ERP Living Documentation & AI Knowledge Base provides a centralized, evidence-based reference for human software engineers and autonomous AI coding agents. It comprehensively indexes and connects repository architecture, persistence layers, multi-company isolation, security policies, local plugin lifecycles, end-to-end transactional workflows, domain calculation rules, change impact assessment, and verification tracking.

This index enables engineers and AI agents to quickly orient themselves, locate relevant domain specifications, navigate cross-cutting concerns, and safely implement changes without violating established system invariants.

---

## Documentation Philosophy

1. **Evidence-First Truth**: Documentation narrows the search space; verified repository source code, automated tests, and database schema decide the result.
2. **Strict Source-of-Truth Hierarchy**:
   $$\text{Source Code} > \text{Automated Tests} > \text{Migrations \& DB Schema} > \text{Configuration} > \text{Composer Manifests} > \text{Documentation} > \text{AI Summaries}$$
3. **No Phantom Architecture**: Every documented class, method, trait, and convention must be grounded in verified repository files. Assumed or third-party shorthand is prohibited.
4. **Separation of Concerns**: Documentation describes system invariants and verified behavior; software release history is strictly separated from documentation evolution history.

---

## Source of Truth

When verifying behavior, resolving conflicting statements, or implementing features, adhere strictly to the established evidence hierarchy:

| Level | Evidentiary Tier | Primary Locations | Authority |
| :---: | :--- | :--- | :--- |
| **1** | **Source Code** | `plugins/webkul/*/src/`, `app/`, `bootstrap/` | Absolute authority on executable behavior |
| **2** | **Automated Tests** | `plugins/webkul/*/tests/` (Pest v4 / PHPUnit 12) | Authoritative on intended behavior and contracts |
| **3** | **Database Schema** | `plugins/webkul/*/database/migrations/`, `database/` | Absolute authority on physical persistence & FKs |
| **4** | **Configuration** | `config/*`, `plugins/webkul/*/config/` | Authoritative on runtime environment settings |
| **5** | **Composer Manifests** | `composer.json`, `composer.lock`, plugin `composer.json` | Authoritative on packages & dependency versions |
| **6** | **Canonical Docs** | `docs/*` | Authoritative synthesis of verified phases |
| **7** | **AI Inference** | Model inference, previous conversational transcripts | Non-authoritative; requires code verification |

> [!IMPORTANT]
> **Escalation Rule**: If a claim in this documentation conflicts with active repository source code, the source code governs. Do not assume documentation is correct over executable code.

---

## Quick Start

### For AI Coding Agents
1. **Start Here**: Begin at [`AGENTS.md`](../AGENTS.md) — the canonical AI entry point with operating protocol, discovery workflow, and critical constraints.
2. **Load Context**: Read [`docs/ai/context.md`](ai/context.md) for system architecture baseline.
3. **Determine Reading Scope**: Consult [`docs/ai/reading-order.md`](ai/reading-order.md) to load only the task-specific documents required for your current objective.
4. **Enforce Canonical Vocabulary**: Check [`docs/ai/terminology.md`](ai/terminology.md) before authoring code, models, or documentation to avoid known architectural pitfalls.
5. **Assess Blast Radius**: Review [`docs/architecture/change-impact.md`](architecture/change-impact.md) before altering cross-cutting services, traits, or database tables.
6. **Verify Against Matrix**: Corroborate critical claims against [`docs/verification-matrix.md`](verification-matrix.md).

### For Human Developers
1. **System Overview**: Read [`docs/architecture/overview.md`](architecture/overview.md) to understand provider registration and package architecture.
2. **Plugin Architecture**: Browse [`docs/plugins/README.md`](plugins/README.md) for core vs optional plugin capabilities.
3. **Security & Tenancy**: Study [`docs/security/authorization.md`](security/authorization.md) and [`docs/security/multi-company.md`](security/multi-company.md).
4. **Testing Standards**: Read [`docs/ai/testing-rules.md`](ai/testing-rules.md) for Pest v4 test execution and conventions.

---

## Technology Baseline

Verified runtime environment and framework versions:

| Component | Installed Version | Verification Evidence |
| :--- | :--- | :--- |
| **PHP** | `8.3.29` | Source baseline (`composer.json` platform constraint `^8.3`) |
| **Laravel Framework** | `13.21.1` | `composer.lock` (`laravel/framework`) |
| **Filament Admin Engine** | `5.7.6` | `composer.lock` (`filament/filament`) |
| **Livewire** | `4.3.3` | `composer.lock` (`livewire/livewire`) — *Adhere to v4 specifications* |
| **Testing Framework** | Pest `4.7.5` / PHPUnit `12` | `composer.lock` (`pestphp/pest`) |
| **Permissions / RBAC** | Filament Shield `4.2.0` | `composer.lock` (`bezhansalleh/filament-shield`) |
| **API Authentication** | Laravel Sanctum `4.3.3` | `composer.lock` (`laravel/sanctum`) |
| **Query Filtering** | Spatie Query Builder | `composer.lock` (`spatie/laravel-query-builder`) |
| **Package Autoloading** | `composer-merge-plugin` | Root `composer.json` (`wikimedia/composer-merge-plugin`) |

---

## Critical Project Terminology

The following architectural distinctions are binding across the entire repository:

### 1. Multi-Company Isolation (No `HasCompanyScope`)
- **No `HasCompanyScope` Trait**: The trait `HasCompanyScope` does not exist in Aureus ERP.
- **Actual Suite**:
  - `Webkul\Support\Services\CompanyContext`: Resolves active company from session/header.
  - `Webkul\Support\Models\Scopes\CompanyScope`: Global query scope filtering by single `company_id`.
  - `Webkul\Support\Models\Scopes\CompaniesScope`: Global query scope filtering across many-to-many company pivots.
  - `Webkul\Support\Traits\BelongsToCompany`: Model trait attaching `CompanyScope` and auto-setting `company_id`.
  - `Webkul\Support\Traits\BelongsToCompanies`: Model trait for entities belonging to multiple companies.
  - `Webkul\Support\Traits\ChecksCompanyConsistency`: Validates foreign key relational consistency across company boundaries.
  - `Webkul\Support\Traits\RestrictToAllowedCompanies`: Model trait applying `AllowedCompanyScope` to restrict company queries to user-assigned companies (unauthorized company switching sanitized via `CompanyContext`).

### 2. Authorization Service (Custom Bouncer)
- `Webkul\Security\Bouncer` (and its facade `Webkul\Security\Facades\Bouncer`) is an **internal Aureus implementation** (`plugins/webkul/security/src/Bouncer.php`).
- It is **NOT** the open-source `silber/bouncer` package. Third-party Bouncer methods or database tables do not exist.

### 3. Dependency Taxonomy
Always distinguish between:
- **Composer Dependencies**: Declared in `composer.json` packages; loaded via Composer.
- **Runtime Plugin Dependencies**: Declared via `Package::hasDependencies([...])` in service providers; enforced during installation.
- **Code-Level Consumption**: Actual symbol imports, class references, services, observers, or dynamic relationships.

### 4. Queue Jobs
- **Precise Invariant**: The repository contains **zero PHP queue Job classes**. Asynchronous work is handled via alternative mechanisms (such as database notifications or command triggers); do not assume queue workers exist.

### 5. Filament Panels
- **Admin Panel**: Accessible at `/admin` (`App\Providers\Filament\AdminPanelProvider`), using default auth guard.
- **Customer Panel**: Accessible at `/` (`App\Providers\Filament\CustomerPanelProvider`), using a separate dedicated customer guard.

---

## Recommended AI Navigation Path

Autonomous AI agents must use a task-scoped route that builds reliable context without context window exhaustion:

```
AGENTS.md (AI Entry Point — Operating Protocol & Constraints)
       ↓
docs/ai/context.md (System Architecture & Baseline)
       ↓
docs/ai/reading-order.md (Task-Specific Route Guide)
       ↓
Task row → selected rules, system knowledge, source, tests, and provider/configuration
```

> [!NOTE]
> Read [`docs/ai/terminology.md`](ai/terminology.md) before naming, creating, or changing a model, trait, service, table, policy, or documentation term. For the complete task-oriented reading guide and source inspection checklist, refer directly to [`docs/ai/reading-order.md`](ai/reading-order.md). Do not duplicate its matrix.

---

## Recommended Developer Navigation Path

Human developers should follow this domain progression when onboarding or designing new capabilities:

$$\begin{aligned}
\text{Architecture} &\longrightarrow \text{Database} \longrightarrow \text{Security} \longrightarrow \text{Plugins} \\
&\longrightarrow \text{Workflows} \longrightarrow \text{Business Rules} \longrightarrow \text{Change Impact} \longrightarrow \text{Verification}
\end{aligned}$$

1. **Architecture**: Read [`docs/architecture/overview.md`](architecture/overview.md) and [`docs/architecture/filament-architecture.md`](architecture/filament-architecture.md).
2. **Database**: Read [`docs/database/overview.md`](database/overview.md), [`docs/database/company-isolation.md`](database/company-isolation.md), and relevant [ERD diagrams](database/erds/core.md).
3. **Security**: Review [`docs/security/authorization.md`](security/authorization.md) and [`docs/security/multi-company.md`](security/multi-company.md).
4. **Plugins**: Inspect [`docs/plugins/README.md`](plugins/README.md) and the specific plugin document under `docs/plugins/`.
5. **Workflows**: Follow end-to-end transaction paths in [`docs/workflows/`](workflows/).
6. **Business Rules**: Verify financial, inventory, purchasing, or sales calculation invariants in [`docs/business-rules/`](business-rules/).
7. **Change Impact**: Review blast radius and cross-plugin ripple effects in [`docs/architecture/change-impact.md`](architecture/change-impact.md).
8. **Verification**: Run tests per [`docs/ai/testing-rules.md`](ai/testing-rules.md) and check claims in [`docs/verification-matrix.md`](verification-matrix.md).

---

## Documentation Map

The repository documentation consists of **79 verified files** organized into 10 functional domains:

```
docs/
├── README.md                                 # Canonical documentation entry point (this file)
├── verification-matrix.md                    # Central verification tracking ledger (Phase 11)
├── ai/                                       # AI developer guidance & canonical rules (10 files)
├── application/                              # Application foundation layer (1 file)
├── architecture/                             # Core system architecture & change impact (6 files)
├── business-rules/                           # Domain calculation & operational rules (4 files)
├── database/                                 # Persistence, isolation, schema & ERDs (8 files)
├── development/                              # Development workflow, Git operations, maintenance, AI skills & readiness (7 files)
├── plugins/                                  # 28 local plugin architectural specifications (29 files)
├── security/                                 # Authentication, authorization & tenancy (4 files)
└── workflows/                                # End-to-end transactional business workflows (7 files)
```

### 1. AI Guidance Domain (`docs/ai/`)
Authoritative rules and context governing AI coding behavior:

| File Link | Primary Scope |
| :--- | :--- |
| [`docs/ai/context.md`](ai/context.md) | High-level system context, architecture summary, and technology stack |
| [`docs/ai/reading-order.md`](ai/reading-order.md) | Task-oriented reading order and source code inspection checklist |
| [`docs/ai/terminology.md`](ai/terminology.md) | Canonical terminology, class mappings, and common misconception corrections |
| [`docs/ai/architecture-rules.md`](ai/architecture-rules.md) | Core architectural invariants, provider conventions, and package structures |
| [`docs/ai/security-rules.md`](ai/security-rules.md) | Security invariants, multi-company isolation rules, and Bouncer authorization |
| [`docs/ai/database-rules.md`](ai/database-rules.md) | Database schema rules, migration conventions, and Eloquent patterns |
| [`docs/ai/plugin-rules.md`](ai/plugin-rules.md) | Plugin package structure, lifecycle states, and dependency declaration rules |
| [`docs/ai/coding-rules.md`](ai/coding-rules.md) | PHP 8.3 typing standards, Laravel conventions, and Filament patterns |
| [`docs/ai/testing-rules.md`](ai/testing-rules.md) | Pest v4 test conventions, directory structure, and coverage audit |
| [`docs/ai/forbidden-patterns.md`](ai/forbidden-patterns.md) | Prohibited antipatterns, forbidden classes, and false assumptions |

### 2. Application Foundation Domain (`docs/application/`)
Project-level architecture outside the plugin layer:

| File Link | Primary Scope |
| :--- | :--- |
| [`docs/application/overview.md`](application/overview.md) | Providers, middleware (branding/locale), navigation shell (topbar/sidebar/language switcher), RTL/i18n CSS, database foundation, Scribe/API docs, build pipeline, E2E testing |

### 3. Architecture Domain (`docs/architecture/`)
Foundational frameworks and cross-cutting architectural mechanisms:

| File Link | Primary Scope |
| :--- | :--- |
| [`docs/architecture/overview.md`](architecture/overview.md) | Architectural shape, local-package integration, and provider lifecycle |
| [`docs/architecture/filament-architecture.md`](architecture/filament-architecture.md) | Filament Admin/Customer panels, resources, pages, widgets, and clusters |
| [`docs/architecture/dynamic-schema.md`](architecture/dynamic-schema.md) | Custom fields dynamic schema mutation, DDL operations, and UI injection |
| [`docs/architecture/events-catalog.md`](architecture/events-catalog.md) | Complete catalog of 28 domain events, 6 listeners, 7 observers, and 53 services |
| [`docs/architecture/plugin-registry.md`](architecture/plugin-registry.md) | Plugin discovery, registration, lifecycle, installation, and dependency handling |
| [`docs/architecture/change-impact.md`](architecture/change-impact.md) | Change Impact Analysis master control guide, blast radius assessment (Phase 11) |

### 4. Database & Persistence Domain (`docs/database/`)
Single-database multi-company persistence models, conventions, and ERDs:

| File Link | Primary Scope |
| :--- | :--- |
| [`docs/database/overview.md`](database/overview.md) | Persistence architecture, shared-table multi-company persistence model |
| [`docs/database/company-isolation.md`](database/company-isolation.md) | Company scoping traits (`BelongsToCompany`, `BelongsToCompanies`, `CompanyScope`) |
| [`docs/database/schema-conventions.md`](database/schema-conventions.md) | Physical column naming, foreign key delete rules, indexing, and migrations |
| [`docs/database/relationships.md`](database/relationships.md) | Eloquent relationship standards and dynamic runtime relation registration |
| [`docs/database/models-index.md`](database/models-index.md) | Comprehensive index of Eloquent models across all 28 plugins |
| [`docs/database/erds/core.md`](database/erds/core.md) | Core Foundation ERD (`partners`, `support`, `security`, `fields`, `chatter`) |
| [`docs/database/erds/finance.md`](database/erds/finance.md) | Financial Domain ERD (`accounts`, `invoices`, `payments`, `accounting`) |
| [`docs/database/erds/operations.md`](database/erds/operations.md) | Operations ERD (`products`, `inventories`, `manufacturing`, `purchases`, `sales`) |

### 5. Security & Tenancy Domain (`docs/security/`)
Authentication, authorization, multi-company access, and threat models:

| File Link | Primary Scope |
| :--- | :--- |
| [`docs/security/authorization.md`](security/authorization.md) | Proprietary Bouncer service, role definitions, permission sets, and model policies |
| [`docs/security/multi-company.md`](security/multi-company.md) | Multi-company session management, company switching, and allowed companies guard |
| [`docs/security/ownership-scopes.md`](security/ownership-scopes.md) | Record-level ownership scoping, user hierarchy, and authorized user ID resolution |
| [`docs/security/threat-model.md`](security/threat-model.md) | Threat boundaries, tenant cross-contamination hazards, and mitigation controls |

### 6. Business Workflows Domain (`docs/workflows/`)
Step-by-step state machine flows, actor roles, transactional operations, and audit trails:

| File Link | Primary Scope |
| :--- | :--- |
| [`docs/workflows/accounting.md`](workflows/accounting.md) | Fiscal periods, journal entries, account reconciliations, and financial close |
| [`docs/workflows/sales.md`](workflows/sales.md) | Quotation $\to$ Sales Order $\to$ Delivery Order $\to$ Customer Invoice $\to$ Payment |
| [`docs/workflows/purchasing.md`](workflows/purchasing.md) | Requisition $\to$ RFQ $\to$ Purchase Order $\to$ Goods Receipt $\to$ Vendor Bill $\to$ Payment |
| [`docs/workflows/inventory.md`](workflows/inventory.md) | Stock moves, warehouse transfers, adjustments, scrapping, and putaway operations |
| [`docs/workflows/manufacturing.md`](workflows/manufacturing.md) | Production planning, Bill of Materials (BOM) consumption, and Work Order execution |
| [`docs/workflows/hr.md`](workflows/hr.md) | Recruitment pipelines, job applications, employee onboarding, and leave requests |
| [`docs/workflows/projects.md`](workflows/projects.md) | Project lifecycle, milestone scheduling, task management, and timesheet logging |

### 7. Business Rules Domain (`docs/business-rules/`)
Mathematical calculation engines, validation constraints, and financial invariants:

| File Link | Primary Scope |
| :--- | :--- |
| [`docs/business-rules/accounting.md`](business-rules/accounting.md) | Double-entry balancing ($Debit = Credit$), foreign exchange, tax engines |
| [`docs/business-rules/inventory.md`](business-rules/inventory.md) | Quantitative stock tracking, physical removal strategies (FIFO/LIFO), reservations, on-hand calculations |
| [`docs/business-rules/purchasing.md`](business-rules/purchasing.md) | 3-way matching rules, vendor price lists, purchase approval thresholds |
| [`docs/business-rules/sales.md`](business-rules/sales.md) | Pricing rules, promotional discounts, quotation expiry, customer credit-limit analysis |

### Development & Workflow Domain (`docs/development/`)
Repository topology, branch hierarchy, commit conventions, merge strategy, GitHub repository governance, and CI/testing baseline:

| File Link | Primary Scope |
| :--- | :--- |
| [`docs/development/git-workflow.md`](development/git-workflow.md) | Git branching, commits, merge strategy, and high-level upstream relationship |
| [`docs/development/github-governance.md`](development/github-governance.md) | GitHub governance, PR controls, branch protection reality, merge rules, and issue templates |
| [`docs/development/ci-testing-governance.md`](development/ci-testing-governance.md) | CI architecture, GitHub Actions workflows, test suites, runtime matrices, and governance findings |
| [`docs/development/upstream-sync.md`](development/upstream-sync.md) | Operational runbook for upstream synchronization, 3-layer safety preflight, conflict resolution, and rollback |
| [`docs/development/change-management.md`](development/change-management.md) | Change lifecycle, knowledge-maintenance triggers, evidence discipline, review cadence, and PR documentation record |
| [`docs/development/ai-skills.md`](development/ai-skills.md) | Repository-scoped AI skill index, automation boundaries, and maintenance rules |
| [`docs/development/knowledge-base-readiness-audit.md`](development/knowledge-base-readiness-audit.md) | Scenario-based O10 readiness evidence, deferred controls, and final-revalidation procedure |

### 8. Verification & Control Domain
Cross-cutting verification tracking and change-impact controls:

| File Link | Primary Scope |
| :--- | :--- |
| [`docs/verification-matrix.md`](verification-matrix.md) | Centralized tracking matrix for critical architectural, security, and count claims |
| [`docs/architecture/change-impact.md`](architecture/change-impact.md) | Master impact analysis guide, blast radius assessment, and verification checklists |

---

## Plugin Ecosystem Overview

Aureus ERP contains **28 domain plugins** located under `plugins/webkul/`. Plugins are classified as **Core** (system-critical, cannot be uninstalled) or **Optional** (runtime installable).

- **Core Status**: Defined by `Package::isCore()` in the plugin's `PackageServiceProvider`.
- **Runtime Dependencies**: Declared via `Package::hasDependencies([...])`.
- **Test Coverage Status**: Sourced authoritatively from the audit in [`docs/ai/testing-rules.md`](ai/testing-rules.md) (11 Tested / 17 Untested).

### Complete Plugin Directory & Status Index

| Plugin Slug | Type | Runtime Dependencies | Test Coverage Status | Documentation Link |
| :--- | :---: | :---: | :---: | :--- |
| `plugin-manager` | **Core** | None | Untested (`tests/` absent) | [`docs/plugins/plugin-manager.md`](plugins/plugin-manager.md) |
| `analytics` | **Core** | None | Untested (`tests/` absent) | [`docs/plugins/analytics.md`](plugins/analytics.md) |
| `chatter` | **Core** | None | Untested (`tests/` absent) | [`docs/plugins/chatter.md`](plugins/chatter.md) |
| `fields` | **Core** | None | Untested (`tests/` absent) | [`docs/plugins/fields.md`](plugins/fields.md) |
| `full-calendar` | **Core** | None | Untested (`tests/` absent) | [`docs/plugins/full-calendar.md`](plugins/full-calendar.md) |
| `partners` | **Core** | None | **Tested** (9 test files) | [`docs/plugins/partners.md`](plugins/partners.md) |
| `security` | **Core** | None | Untested (`tests/` absent) | [`docs/plugins/security.md`](plugins/security.md) |
| `support` | **Core** | None | **Tested** (17 test files) | [`docs/plugins/support.md`](plugins/support.md) |
| `table-views` | **Core** | None | Untested (`tests/` absent) | [`docs/plugins/table-views.md`](plugins/table-views.md) |
| `accounting` | **Optional** | `accounts` | **Tested** (9 test files) | [`docs/plugins/accounting.md`](plugins/accounting.md) |
| `accounts` | **Optional** | `products` | **Tested** (42 test files) | [`docs/plugins/accounts.md`](plugins/accounts.md) |
| `barcode` | **Optional** | `inventories` | Untested (`tests/` absent) | [`docs/plugins/barcode.md`](plugins/barcode.md) |
| `blogs` | **Optional** | `website` | Untested (`tests/` absent) | [`docs/plugins/blogs.md`](plugins/blogs.md) |
| `contacts` | **Optional** | None | Untested (`tests/` absent) | [`docs/plugins/contacts.md`](plugins/contacts.md) |
| `employees` | **Optional** | None | **Tested** (5 test files) | [`docs/plugins/employees.md`](plugins/employees.md) |
| `inventories` | **Optional** | `products` | **Tested** (41 test files) | [`docs/plugins/inventories.md`](plugins/inventories.md) |
| `invoices` | **Optional** | `accounts` | Untested (`tests/` absent) | [`docs/plugins/invoices.md`](plugins/invoices.md) |
| `maintenance` | **Optional** | None | Untested (`tests/` absent) | [`docs/plugins/maintenance.md`](plugins/maintenance.md) |
| `manufacturing` | **Optional** | `products`, `inventories` | **Tested** (8 test files) | [`docs/plugins/manufacturing.md`](plugins/manufacturing.md) |
| `payments` | **Optional** | `accounts` | Untested (`tests/` absent) | [`docs/plugins/payments.md`](plugins/payments.md) |
| `products` | **Optional** | None | **Tested** (16 test files) | [`docs/plugins/products.md`](plugins/products.md) |
| `projects` | **Optional** | None | **Tested** (8 test files) | [`docs/plugins/projects.md`](plugins/projects.md) |
| `purchases` | **Optional** | `invoices` | **Tested** (15 test files) | [`docs/plugins/purchases.md`](plugins/purchases.md) |
| `recruitments` | **Optional** | `employees` | Untested (`tests/` absent) | [`docs/plugins/recruitments.md`](plugins/recruitments.md) |
| `sales` | **Optional** | `invoices`, `payments` | **Tested** (17 test files) | [`docs/plugins/sales.md`](plugins/sales.md) |
| `time-off` | **Optional** | `employees` | Untested (`tests/` absent) | [`docs/plugins/time-off.md`](plugins/time-off.md) |
| `timesheets` | **Optional** | `projects` | Untested (`tests/` absent) | [`docs/plugins/timesheets.md`](plugins/timesheets.md) |
| `website` | **Optional** | None | Untested (`tests/` absent) | [`docs/plugins/website.md`](plugins/website.md) |

> [!TIP]
> For detailed plugin architectural overviews, panel participation, database table allocations, and cross-plugin dependency graphs, consult [`docs/plugins/README.md`](plugins/README.md).

---

## Key Architectural References

Quick links to primary architectural mechanisms:

- **Application Foundation Layer**: [`docs/application/overview.md`](application/overview.md)
- **Company Isolation Suite**: [`docs/database/company-isolation.md`](database/company-isolation.md) & [`docs/security/multi-company.md`](security/multi-company.md)
- **Security & Authorization Model**: [`docs/security/authorization.md`](security/authorization.md) & [`docs/security/ownership-scopes.md`](security/ownership-scopes.md)
- **Filament Panels & UI Engine**: [`docs/architecture/filament-architecture.md`](architecture/filament-architecture.md)
- **Dynamic Schema & Custom Fields**: [`docs/architecture/dynamic-schema.md`](architecture/dynamic-schema.md) & [`docs/plugins/fields.md`](plugins/fields.md)
- **Domain Events & Reactive Infrastructure**: [`docs/architecture/events-catalog.md`](architecture/events-catalog.md)
- **Plugin Lifecycle & Discovery**: [`docs/architecture/overview.md`](architecture/overview.md) & [`docs/architecture/plugin-registry.md`](architecture/plugin-registry.md)
- **Database ERDs & Persistence Models**: [`docs/database/overview.md`](database/overview.md) & [`docs/database/erds/`](database/erds/)
- **Business Logic & Workflows**: [`docs/workflows/`](workflows/) & [`docs/business-rules/`](business-rules/)
- **Change Impact & Blast Radius Analysis**: [`docs/architecture/change-impact.md`](architecture/change-impact.md)
- **Verification Matrix & Claim Tracking**: [`docs/verification-matrix.md`](verification-matrix.md)

---

## Documentation History

The Aureus ERP Living Documentation & AI Knowledge Base evolved across structured phases:

- **Phase 0 — Repository Audit**: Established the initial repository baseline, inventory of 28 plugins, and structural audit.
- **Phase 1 — AI Context**: Established the core AI context, technical stack baseline, and foundational instructions.
- **Phase 2 — Architecture Overview**: Established high-level architecture documentation, provider integration, and packaging model.
- **Phase 3 — Security**: Documented security architecture, proprietary Bouncer service, and multi-company isolation mechanisms.
- **Phase 4 — Database Architecture & Data Model**: Documented persistence conventions, models catalog, and domain ERDs (`core`, `finance`, `operations`).
- **Phase 5 — Core Plugin Documentation**: Documented individual architectural specifications for all 9 core plugins.
- **Phase 6 — Optional Plugin Documentation**: Documented individual architectural specifications for all 19 optional plugins.
- **Phase 7 — Cross-Cutting Architecture**: Documented Filament panels, dynamic custom fields schema, events catalog, observers, and services.
- **Phase 8 — Workflows**: Documented 7 major transactional workflows and state transitions with concrete source evidence.
- **Phase 9 — Business Rules**: Documented mathematical calculation engines and business rules across accounting, inventory, sales, and purchasing.
- **Phase 10 — AI Rules**: Established canonical AI rules covering terminology, architecture, plugins, database, security, coding, testing, and forbidden patterns.
- **Phase 11 — Change Impact & Verification Matrix**: Established change-impact blast radius assessment and the centralized verification tracking ledger (**COMPLETE / LOCKED**).
- **Phase 12 — Documentation Knowledge-Base Index, Navigation & Documentation Changelog**: Created the canonical documentation entry point (`docs/README.md`) and separated knowledge-base history from application release history (**COMPLETE**).
- **Phase 13 — Read-Only / Final Documentation Audit**: Comprehensive read-only verification across all documentation assets (**COMPLETE / LOCKED**).

---

## Documentation Changelog vs Project Changelog

Aureus ERP strictly separates documentation system history from software application release history:

```
Aureus ERP Repository
    │
    ├── Project / Software Changelog (CHANGELOG.md at repository root)
    │   └── Tracks software releases, application features, bug fixes,
    │       database migrations, dependency updates, and platform changes.
    │       Answers: "How did the Aureus ERP software evolve?"
    │
    └── Living Documentation / AI Knowledge Base (docs/)
        ├── docs/README.md (Documentation Entry Point & Master Index)
        ├── Specialized Knowledge Domains (ai, architecture, database, security, etc.)
        │
        └── Documentation Changelog (docs/CHANGELOG.md)
            └── Tracks knowledge-base evolution, phase completion, terminology corrections,
                reconciliation milestones, and documentation maintenance.
                Answers: "How did this documentation system evolve?"
```

- **Project Changelog ([`CHANGELOG.md`](../CHANGELOG.md))**:
  Records actual software releases (e.g. `v1.5.0`), software features, code improvements, bug fixes, and deployment changes.
- **Documentation Changelog ([`docs/CHANGELOG.md`](CHANGELOG.md))**:
  Records changes to the Living Documentation itself (phases, structural reorganizations, terminology fixes, verification audits).

> [!WARNING]
> **Do Not Merge**: The Documentation Changelog must never masquerade as the software project changelog, and software release notes must not overwrite the documentation changelog.

---

## Documentation Maintenance Discipline

To maintain the integrity and stability of the Living Documentation and the repository:

1. **Read-Only Inspection**: During investigations, audits, or verification checks, agents and developers must operate in read-only mode. Never run destructive or file-modifying commands during research.
2. **Strict Write Scope**: Only modify files explicitly within the approved task scope. Do not update unrelated documentation or code files opportunistically.
3. **Repository Cleanliness**: Verify working directory state with `git status` before starting and after finishing work. Ensure zero unintended file modifications exist.
4. **Link Integrity**: Every referenced path in documentation must exist in the repository. Never invent documentation files, classes, or paths.

---

## Historical Documentation Status

The phases in this section are the closed historical documentation program. They describe how the knowledge base was built; they do not assign work in the active governance roadmap below.

| Phase | Description | Status |
| :---: | :--- | :---: |
| **Phases 0–10** | Foundation, Architecture, Security, DB, Plugins, Workflows, Rules | **Complete** |
| **Phase 11** | Change Impact Analysis & Verification Matrix | **Complete / Locked** |
| **Phase 12** | Documentation Entry Point (`docs/README.md`) & Documentation Changelog (`docs/CHANGELOG.md`) | **Complete** |
| **Phase 13** | Read-Only Final Documentation Audit | **Complete / Locked** |

---

## Operational Remediation Roadmap

The active governance and remediation program uses **Operational Stages `O0`–`O10`**. It is intentionally separate from the historical documentation phases above. A stage status describes this branch's implementation state; it does not imply that a control is enforced on GitHub or merged into `develop`.

| Stage | Scope | Branch status | Completion boundary |
| :---: | :--- | :--- | :--- |
| **O0** | Scope control and working-tree inventory | **Complete** | Read-only inventory completed; no source or configuration changes found. |
| **O1** | Documentation baseline reconciliation | **Complete / Committed** | Platform facts, stale Livewire references, and internal links reconciled against repository evidence. |
| **O2** | Operational terminology and status model | **Complete / Committed** | Historical phases and operational stages are explicitly separated. |
| **O3** | AI knowledge architecture and operating protocol | **Complete / Committed** | Plugin, company-scoped, API, security, and upstream synchronization routes are available from the canonical navigation set. |
| **O4** | Git operating model | **Complete / Committed — Release-branch transition pending** | `develop` is the integration branch; verified `develop`-to-`master` PRs create releases and tags originate from `master`. No `hotfix/*` or `release/*` branches are used. The first release promotion completes the transition. |
| **O5** | GitHub governance | **Complete / Verified — Solo-maintainer mode** | Active no-bypass rulesets protect `develop` and release `master`; self-reviewed PRs, conversation resolution, deletion, and force-push controls are verified. Both rulesets require zero approvals and disable latest-pusher approval. CI status checks remain O6 work. |
| **O6** | CI, testing, and quality gates | **Baseline audited / Remediation pending** | Workflow and quality findings are documented; implementation is separate work. |
| **O7** | Upstream integration runbook | **Complete / Committed — Release-branch transition pending** | Upstream changes enter `develop` through a protected-branch PR; a separate verified release PR promotes `develop` to `master`. Conflict protocol, workflow review, recovery, and tag safety are documented; execution remains explicitly authorized work. |
| **O8** | Change management and knowledge maintenance | **Complete / Committed** | Event-driven lifecycle, documentation-impact triggers, evidence discipline, ownership-by-role, quarterly review, and PR recording requirements are adopted. |
| **O9** | AI skills and developer automation | **Complete / Committed** | Six repository-scoped, instruction-only skills route plugin, API, schema, testing/CI, documentation, and upstream work to canonical controls without creating parallel policy. |
| **O10** | Final knowledge-base readiness audit | **Initial audit complete / final revalidation pending** | Seven scenario routes pass read-only evidence checks; the release-branch upstream route was rechecked. Final closure awaits upstream execution, first release promotion/review, and O6 outcomes. |
