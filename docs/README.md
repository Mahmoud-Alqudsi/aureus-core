---
status: verified
source_of_truth: source-code
last_verified: 2026-10-25
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
| **6** | **Canonical Docs** | `docs/*` | Authoritative synthesis of verified system behavior |
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

### For Gemini CLI Users

Gemini CLI loads the AI entry point and workspace skills automatically
once this repository is trusted. Run `/permissions trust` (or
`gemini trust` from the terminal) the first time you open the
repository so that the six `aureus-*` skills under `.agents/skills/`
become available. The trust decision is persisted per-folder to
`~/.gemini/trustedFolders.json` and applies to future sessions.
The repo-root [`GEMINI.md`](../GEMINI.md) is a one-line pointer that imports
AGENTS.md via the `@AGENTS.md` directive, so both agents land on
the same operating protocol.

### Repository Setup

After cloning, activate the versioned pre-commit KB lint hook once:

```bash
git config core.hooksPath scripts/hooks
```

Every commit touching `docs/`, `AGENTS.md`, or `agents/**` then
runs `scripts/kb_lint.py --root . --strict` and blocks on any
finding. See
[`docs/development/ci-testing-governance.md`](development/ci-testing-governance.md)
for details. The config is local per clone; it is not pushed.

---

## Technology Baseline

The canonical technology baseline (PHP `^8.3`, Laravel `v13.x`, Filament `v5.x`, Livewire `v4.x`, Pest `v4.x`, Filament Shield `v4.x`, and Laravel Sanctum `v4.x`) is maintained in [`AGENTS.md` (Technology Baseline)](../AGENTS.md#technology-baseline). Always verify exact installed package versions directly against [`composer.lock`](../composer.lock) and [`composer.json`](../composer.json).

---

## Critical Project Terminology

Canonical terminology, class mappings, and misconception corrections (including multi-company isolation traits, `Webkul\Security\Bouncer`, dependency taxonomy, zero queue `Job` classes, and Filament panels) are defined solely in [`docs/ai/terminology.md`](ai/terminology.md). Refer directly to that file rather than duplicating definitions across entry documents.

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
├── verification-matrix.md                    # Central verification tracking ledger
├── ai/                                       # AI developer guidance & canonical rules (10 files)
├── application/                              # Application foundation layer (1 file)
├── architecture/                             # Core system architecture & change impact (6 files)
├── business-rules/                           # Domain calculation & operational rules (5 files)
├── database/                                 # Persistence, isolation, schema & ERDs (8 files)
├── development/                              # Development workflow, Git operations, maintenance & AI skills (6 files)
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
| [`docs/architecture/events-catalog.md`](architecture/events-catalog.md) | Complete catalog of 28 domain events, 6 listeners, 8 observers, and 54 services |
| [`docs/architecture/plugin-registry.md`](architecture/plugin-registry.md) | Plugin discovery, registration, lifecycle, installation, and dependency handling |
| [`docs/architecture/change-impact.md`](architecture/change-impact.md) | Change Impact Analysis master control guide and blast radius assessment |

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
| [`docs/business-rules/pricing.md`](business-rules/pricing.md) | Multi-tier price lists, rule item calculation types (fixed, percentage, formula), cascade priority, and PriceListResolver |
| [`docs/business-rules/sales.md`](business-rules/sales.md) | Pricing rules, promotional discounts, quotation expiry, customer credit-limit analysis |

### Development & Workflow Domain (`docs/development/`)
Repository topology, branch hierarchy, commit conventions, merge strategy, GitHub repository governance, and CI/testing baseline:

| File Link | Primary Scope |
| :--- | :--- |
| [`docs/development/git-workflow.md`](development/git-workflow.md) | Git branching, commits, merge strategy, and high-level upstream relationship |
| [`docs/development/github-governance.md`](development/github-governance.md) | GitHub governance, PR controls, branch protection reality, merge rules, and issue templates |
| [`docs/development/ci-testing-governance.md`](development/ci-testing-governance.md) | CI architecture, GitHub Actions workflows, test suites, runtime matrices, and status check gates |
| [`docs/development/upstream-sync.md`](development/upstream-sync.md) | Operational runbook for upstream synchronization, 3-layer safety preflight, conflict resolution, and rollback |
| [`docs/development/change-management.md`](development/change-management.md) | Change lifecycle, knowledge-maintenance triggers, evidence discipline, review cadence, PR documentation record, and routing verification scenarios |
| [`docs/development/ai-skills.md`](development/ai-skills.md) | Repository-scoped AI skill index, automation boundaries, and maintenance rules |

### 8. Verification & Control Domain
Cross-cutting verification tracking and change-impact controls:

| File Link | Primary Scope |
| :--- | :--- |
| [`docs/verification-matrix.md`](verification-matrix.md) | Centralized tracking matrix for critical architectural, security, and count claims |
| [`docs/architecture/change-impact.md`](architecture/change-impact.md) | Master impact analysis guide, blast radius assessment, and verification checklists |

---

## Plugin Ecosystem Overview

Aureus ERP contains **28 domain plugins** located under `plugins/webkul/` (9 Core and 19 Optional).

- **Complete Plugin Directory, Classification & Dependency Matrix**: Maintained solely in [`docs/plugins/README.md`](plugins/README.md).
- **Per-Plugin Test Coverage Audit (11 Tested / 17 Untested)**: Maintained solely in [`docs/ai/testing-rules.md`](ai/testing-rules.md#2-fresh-repository-test-coverage-audit).
- **Plugin Lifecycle & Registration Rules**: Maintained in [`docs/ai/plugin-rules.md`](ai/plugin-rules.md) and [`docs/architecture/plugin-registry.md`](architecture/plugin-registry.md).

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
            └── Tracks knowledge-base evolution, terminology corrections,
                reconciliation milestones, and documentation maintenance.
                Answers: "How did this documentation system evolve?"
```

- **Project Changelog ([`CHANGELOG.md`](../CHANGELOG.md))**:
  Records actual software releases (e.g. `v1.5.0`), software features, code improvements, bug fixes, and deployment changes.
- **Documentation Changelog ([`docs/CHANGELOG.md`](CHANGELOG.md))**:
  Records changes to the Living Documentation itself (structural reorganizations, terminology fixes, verification audits, and historical milestones).

> [!WARNING]
> **Do Not Merge**: The Documentation Changelog must never masquerade as the software project changelog, and software release notes must not overwrite the documentation changelog.

---

## Documentation Maintenance Discipline

To maintain the integrity and stability of the Living Documentation and the repository:

1. **Read-Only Inspection**: During investigations, audits, or verification checks, agents and developers must operate in read-only mode. Never run destructive or file-modifying commands during research.
2. **Strict Write Scope**: Only modify files explicitly within the approved task scope. Do not update unrelated documentation or code files opportunistically.
3. **Repository Cleanliness**: Verify working directory state with `git status` before starting and after finishing work. Ensure zero unintended file modifications exist.
4. **Link Integrity**: Every referenced path in documentation must exist in the repository. Never invent documentation files, classes, or paths.


