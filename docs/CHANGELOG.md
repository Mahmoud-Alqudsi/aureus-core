---
status: verified
source_of_truth: git-history-and-execution-records
last_verified: 2026-09-22
scope: documentation-changelog
confidence: high
---

# Aureus ERP — Living Documentation & AI Knowledge Base Changelog

This changelog records the evolution, phases, structural reorganizations, and material corrections of the Aureus ERP Living Documentation & AI Knowledge Base (`docs/`).

> [!IMPORTANT]
> **Separation from Software Project Changelog**:
> This changelog answers: *"How did this documentation and AI knowledge base evolve?"*
> It does **NOT** record software releases, bug fixes, or application features.
> For the Aureus ERP software release history (e.g. `v1.5.0`), refer to the project changelog at the repository root: [`CHANGELOG.md`](../CHANGELOG.md).

---

## 1. Evidentiary Classification of History

In accordance with the repository's documentation accuracy rules, historical events recorded herein are categorized into three evidentiary tiers:

1. **Git-Verified History**: Backed directly by immutable Git commit hashes, author timestamps, and repository diffs (`git log -- docs/`).
2. **Documented Execution History**: Backed by documented phase execution reports, verification matrices, and structured project logs where exact commit hashes were synthesized or consolidated.
3. **Reconstructed / Active History**: Actively in progress or reconstructed from conversational context; explicitly flagged to prevent false claims of Git verification.

---

## Operational Governance Maintenance

### O10 Scope Realignment, Post-Merge Cleanup & Stage Closure (2026-09-22)

- **Evidentiary Tier**: Git-Verified history (`git log -- docs/`) on branch `docs/sync-domain-knowledge-base`.
- **Status**: **Stage O10 Complete / Closed**.
- **Recorded Scope**:
  - Realigned Operational Stage O10 scope by decoupling the deferred `develop`-to-`master` release promotion from initial knowledge-base readiness criteria, focusing O10 strictly on post-merge workspace hygiene and living documentation closure.
  - Executed post-merge branch and worktree cleanup pursuant to Section 15 (R8):
    - Pruned stale linked worktree in `/tmp/aureuserp-upstream-sync-20260918-c2b4ddaa2` using `git worktree prune -v`.
    - Safely deleted seven local branches fully merged into `develop` using `git branch -d`: `chore/upstream-sync-20260918-c2b4ddaa2`, `chore/enforce-ci-status-checks`, `chore/update-dependencies`, `docs/record-upstream-sync`, `docs/record-o6-enforcement`, `refactor/ai-knowledge-architecture`, and `feature/privacy-and-localization`.
    - Preserved protected branches (`master`, `develop`), checkpoints (`checkpoint/*`), active topic branch (`docs/sync-domain-knowledge-base`), and active feature branches/worktrees.
  - Completed final scenario revalidation and closed Operational Stage O10 in [`docs/development/knowledge-base-readiness-audit.md`](development/knowledge-base-readiness-audit.md) and [`docs/README.md`](README.md).

### Post-Upstream Metrics & Governance Reconciliation (2026-09-22)

- **Evidentiary Tier**: Git-Verified history (`git log -- docs/`) on branch `docs/sync-domain-knowledge-base`.
- **Status**: **Implemented and verified across living documentation**.
- **Recorded Scope**:
  - Reconciled numerical metrics and inventory claims resulting from comprehensive knowledge-base review report:
    - Document count: Updated total verified living documentation files to 80 and business-rules domain to 5 files in [`docs/README.md`](README.md) (reflecting [`docs/business-rules/pricing.md`](business-rules/pricing.md)).
    - Test baseline: Updated plugin test file counts in [`docs/README.md`](README.md), [`docs/ai/testing-rules.md`](ai/testing-rules.md), and claim `COUNT-011` in [`docs/verification-matrix.md`](verification-matrix.md) from 174 to 199 files (187 `*Test.php` test classes + 12 shared helpers/fixtures across 11 tested plugins).
    - Observers: Updated claim `COUNT-008` in [`docs/verification-matrix.md`](verification-matrix.md) and [`docs/architecture/events-catalog.md`](architecture/events-catalog.md) from 7 across 3 plugins to 8 across 4 plugins, documenting `Webkul\Account\Observers\CompanyObserver` currency guard in `accounts`.
    - Services: Updated claim `COUNT-009` in [`docs/verification-matrix.md`](verification-matrix.md) and [`docs/architecture/events-catalog.md`](architecture/events-catalog.md) from 53 to 54 services, cataloging `PriceListResolver` in `products`.
  - Documented GitHub Issue, Topic Branch, and Pull Request lifecycle in [`docs/development/git-workflow.md`](development/git-workflow.md), [`docs/development/github-governance.md`](development/github-governance.md), and [`docs/development/change-management.md`](development/change-management.md), establishing issue creation criteria, closing keywords, and PR traceability.

### Upstream Domain Knowledge-Base Alignment (2026-09-21)

- **Evidentiary Tier**: Git-Verified history (`git log -- docs/`) on branch `docs/sync-domain-knowledge-base`.
- **Status**: **Implemented across documentation and verified against source code**.
- **Recorded Scope**:
  - Aligned physical schema conventions, ERD diagrams, and models with upstream PR #10 / `d7d471894` consolidation of `products_price_rules` into `products_product_price_lists` and `products_price_rule_items`.
  - Created canonical business rules document [`docs/business-rules/pricing.md`](business-rules/pricing.md) covering multi-tier price lists, rule application scopes, quantity breaks, date validity, and calculation types (`FIXED`, `PERCENTAGE`, `FORMULA`).
  - Documented `PriceListResolver` service and `ResolvedPrice` DTO in products documentation and quotation/sales orders integration.
  - Documented cross-plugin upstream enhancements:
    - Accounts: `Move::resolveBankPartnerId()`, `PaymentRegister` company currency accessors and safe bank resolution, `AccountingSetupService`, and `CompanyObserver` currency guard.
    - Purchases: Multi-currency price conversion in `OrderForm`, `OrderSummary`, `PurchaseAgreementForm`, and `VendorPriceInfolist` (`OrderCurrencyConversionTest.php`).
    - Support: Currency resolution hierarchy (`DefaultCurrencyResolutionTest.php`), `UOM::computePrice()`, global helpers (`default_currency_code`, `default_currency_id`, `money`, `hide_deleted_unless_selected`), and `CurrencyResource` deletion error handling.
    - Plugin Manager: Cross-platform utilities in `Package` (`phpBinaryPath`, `buildTimeoutCommand`, `openInBrowser`) and `InstallERP` localization options.
    - Inventories: Warehouse receipt move line deletion confirmation modal, `hide_deleted_unless_selected()` on product selectors, and soft-deleted product stock filtering in reporting.
    - Partners: Preset table views (`individuals`, `companies`, `employees`, `customers`, `vendors`) on `ListPartners` (`PartnerTypeViewsTest.php`), and `price_list_id` relationship.
    - Blogs: `ListsBlogPosts` concern trait with tag-based search and query URL parameters.
    - Employees: `EmployeeFactory` enum constraints (`EmployeeFactoryTest.php`) and employee partner provisioning without `parent_id`.
    - Security: Safe fillable attribute filtering via `Arr::only()` on user partner creation and update.
  - Cleaned all obsolete standalone `PriceRule` references and resolved finding `CORR-012` in [`docs/verification-matrix.md`](verification-matrix.md).

### O6 — CI Required-Check Enforcement (2026-09-19)

- **Evidentiary Tier**: Direct GitHub API inspection plus GitHub Actions execution evidence.
- **Status**: **Implemented and verified on `develop` and `master`**.
- **Recorded Result**:
  - PR #12 added the stable `Playwright E2E Gate` and entered `develop` as merge commit `439950402663107c42ffd7d7d3570c3d2e4ccc07` after Pest (MySQL/PostgreSQL), translation consistency, all Playwright shards, both report jobs, and the new gate succeeded.
  - Active no-bypass rulesets `protect-develop` (`23566563`) and `protect-release-master` (`23566566`) strictly require `PHP 8.3 | MySQL test on ubuntu-latest`, `PHP 8.3 | PostgreSQL test on ubuntu-latest`, `Check translation files consistency`, and `Playwright E2E Gate` from GitHub Actions integration `15368`.
  - The aggregate gate tests the aggregate shard and report outcomes, avoiding brittle ruleset entries tied to a particular shard count. Individual shard and report contexts remain visible as diagnostics but are not individually required.
  - This record does not claim that every workflow job, code-style check, coverage threshold, or future CI improvement is enforced; those remain separate follow-up decisions.

### O7 — Upstream Synchronization Record (2026-09-18)

- **Evidentiary Tier**: Git-Verified history plus direct GitHub Actions execution evidence.
- **Status**: **Merged into `develop`; synchronization CI verified; release promotion pending**.
- **Recorded Result**:
  - Integrated `upstream/master` target `d7d471894` through synchronization merge `dcd449b96` and Pull Request #10 merge `ddbd24ba4`.
  - Preserved downstream privacy behavior in the installer and excluded the incoming Playwright reporting workflows because they introduced `gh-pages` force-push and pull-request comment writes.
  - GitHub Actions for PR #10 completed successfully on the synchronization commit: Pest against MySQL and PostgreSQL, Playwright's twelve database/shard jobs and two report jobs, and the translation check. The Pest bootstrap covers a fresh schema/install path, exercising the accepted migrations in both database engines.
  - Recorded the accepted range, migration scope, CI evidence, and remaining release boundaries in [`architecture/change-impact.md`](architecture/change-impact.md) and [`verification-matrix.md`](verification-matrix.md).
  - Added R8 post-merge cleanup to the upstream runbook. It requires a read-only candidate audit and explicit approval before removing any merged branch, remote ref, or linked worktree; no cleanup deletion is recorded here.
  - No release promotion or tag creation is claimed by this record. A production-data migration rehearsal and a local rerun remain outside this evidence record.

### O4/O5/O7 Follow-up — Release-Branch Operating Model

- **Evidentiary Tier**: Active branch policy change with direct Git and GitHub inspection.
- **Status**: **Adopted / first release promotion pending**.
- **Recorded Controls**:
  - Reassigned `master` from the upstream synchronization baseline to the stable release branch; `develop` remains the default daily-integration branch.
  - Moved normal upstream synchronization to `develop` through an authorized `chore/upstream-sync-<date>` Pull Request with a Merge Commit; a verified `develop`-to-`master` Pull Request now forms the release boundary.
  - Required downstream version tags to point only to the verified `master` release commit. The existing no-direct-push, no-bypass, solo-maintainer review, deletion, and force-push protections remain in force.
  - Recorded the transition boundary: current protected-branch history is preserved, and the model is operationally complete only after the first verified release promotion. No reset, force-push, upstream execution, tag creation, or PR merge is claimed by this documentation change.

### R6 Follow-up — Entry-Point Updates Confirmed

- **Evidentiary Tier**: Git-Verified history.
- **Status**: **Complete / Merged into `develop`**.
- The entry-point updates tracked as R6 were previously deferred for follow-up, but commit `63f30ce19` from `refactor/ai-knowledge-architecture` entered `develop` through Pull Request #9 (`c2b4ddaa2`). Its documented scope includes `AGENTS.md`, the documentation index and changelog, and the Git/GitHub/upstream/O10 operating records.

### O5 Follow-up — Solo-Maintainer Ruleset Alignment

- **Evidentiary Tier**: Direct GitHub API inspection.
- **Status**: **Complete / Verified**.
- **Recorded Controls**:
  - Reconfigured the active `protect-develop` (`23566563`) and the `master` ruleset (`23566566`, now named `protect-release-master`) for a single maintainer: Pull Requests remain mandatory, but both now require zero approving reviews and do not require approval from someone other than the latest pusher.
  - Retained the protections that preserve controlled integration: no bypass actors, required conversation resolution, stale-review dismissal, and blocks on deletion and non-fast-forward updates.
  - Aligned the Git operating model, GitHub policy, upstream runbook, AI entry point, and operational roadmap to require author self-review and recorded verification; independent review remains required where a higher-risk repository control mandates it.
  - Corrected the repository classification to public where the GitHub-governance documentation described it as private.

### O8 — Change Management & Knowledge Maintenance

- **Evidentiary Tier**: Active branch implementation.
- **Status**: **Complete / Committed**.
- **Primary Focus**: Establishing an event-driven lifecycle for keeping the Living Documentation and AI guidance aligned with authoritative repository evidence.
- **Recorded Controls**:
  - Added [`docs/development/change-management.md`](development/change-management.md) defining responsibilities by change role, impact triggers, evidence standards, quarterly review, and PR recording requirements.
  - Connected the policy to [`AGENTS.md`](../AGENTS.md), the AI reading-order matrix, the canonical documentation index, and the pull-request template.
  - Preserved the boundary between documentation controls and implementation/platform enforcement: updating a policy does not itself execute upstream synchronization, alter CI, or modify GitHub rulesets.

---

### O9 — AI Skills & Developer Automation

- **Evidentiary Tier**: Active branch implementation.
- **Status**: **Complete / Committed**.
- **Primary Focus**: Adding repository-scoped, narrowly triggered AI skills that route work to established canonical controls.
- **Recorded Controls**:
  - Added six instruction-only skills under `.agents/skills/` for plugin, API, schema, test/CI, documentation, and upstream synchronization work.
  - Added [`docs/development/ai-skills.md`](development/ai-skills.md) to define discovery, skill boundaries, maintenance, and the threshold for future deterministic automation.
  - Narrowed `.gitignore` so only the reviewed `.agents/skills/` subtree is versioned; other local `.agents` content remains ignored.
  - Kept skills subordinate to `AGENTS.md` and the source-of-truth hierarchy; no skill adds credentials, scripts, remote mutation, or authority to bypass approvals.

---

### O10 — Knowledge Base & Operational Readiness Audit

- **Evidentiary Tier**: Read-only branch audit.
- **Status**: **Initial audit complete / final revalidation pending**.
- **Primary Focus**: Testing whether realistic agent tasks reach the correct canonical controls and direct repository evidence without granting unapproved authority.
- **Recorded Result**:
  - Added [`docs/development/knowledge-base-readiness-audit.md`](development/knowledge-base-readiness-audit.md) with seven scenario-based routing checks covering plugin, company-scoped schema, API, security, test/CI, documentation, and upstream work.
  - All seven current routing scenarios passed static evidence inspection; the audit does not claim application execution or platform enforcement from that result.
  - Final O10 revalidation remains dependent on an authorized upstream synchronization, review of incoming workflow changes, and the separately tracked O6 decisions.

---

## 2. Phase-by-Phase Documentation Evolution

```
Phase 0 ───► Phases 1–4 ───► Phases 5–6 ───► Phase 7 ───► Phase 8 ───► Phase 9 ───► Phase 10 ───► Phase 11 ───► Phase 12 ───► Phase 13
Audit        Foundation      Plugins         Synthesis    Workflows    Rules        AI Rules      Impact        Index        Audit
(Doc)        (Git)           (Git)           (Git)        (Git)        (Git)        (Git)         (LOCKED)      (Active)     (Pending)
```

### Phase 0 — Repository Audit & Baseline Inventory
- **Evidentiary Tier**: Documented Execution History
- **Primary Focus**: Initial repository exploration, local package discovery, and structural inventory.
- **Key Milestones**:
  - Identified 28 local domain packages residing under `plugins/webkul/`.
  - Discovered application bootstrap wiring in `bootstrap/app.php` and `bootstrap/providers.php`.
  - Established initial framework baseline (Laravel 13, Filament 5, Livewire 4, Pest v4).
  - Identified lack of queue Job classes and discovered custom multi-company isolation patterns.

### Phase 1 — AI Context & Guidelines Baseline
- **Evidentiary Tier**: Git-Verified
- **Git Commits**: `81f428783` (2026-08-25), `19fcbe483` (2026-08-29)
- **Primary Focus**: Established AI developer context, task-oriented reading guidance, and runtime framework baseline.
- **Key Milestones**:
  - Authored [`docs/ai/context.md`](ai/context.md) defining high-level architecture, technology versions, and foundational guidelines.
  - Authored [`docs/ai/reading-order.md`](ai/reading-order.md) introducing task-specific reading orders to prevent AI context overflow.
  - Established evidence-first rule: documentation narrows the search, but active source code decides truth.

### Phase 2 — High-Level Architecture Overview
- **Evidentiary Tier**: Git-Verified
- **Git Commit**: `69cdee902` (2026-08-25)
- **Primary Focus**: Core architectural shape, service provider integration, and package lifecycles.
- **Key Milestones**:
  - Authored [`docs/architecture/overview.md`](architecture/overview.md) detailing provider registration and package architecture.
  - Authored [`docs/architecture/plugin-registry.md`](architecture/plugin-registry.md) explaining `wikimedia/composer-merge-plugin` autoloading, `PackageServiceProvider` lifecycle, and runtime installation checks (`Package::isInstalled()`).

### Phase 3 — Security, Authorization & Tenancy Architecture
- **Evidentiary Tier**: Git-Verified
- **Git Commit**: `4fbdef67c` (2026-08-25)
- **Primary Focus**: Authentication guards, authorization services, multi-company isolation, and threat modeling.
- **Key Milestones**:
  - Authored [`docs/security/authorization.md`](security/authorization.md) establishing `Webkul\Security\Bouncer` as an internal proprietary service rather than the third-party `silber/bouncer` package.
  - Authored [`docs/security/multi-company.md`](security/multi-company.md) documenting session-aware company switching and `CompanyContext`.
  - Authored [`docs/security/ownership-scopes.md`](security/ownership-scopes.md) and [`docs/security/threat-model.md`](security/threat-model.md) defining record-level access filtering and cross-tenant attack surfaces.

### Phase 4 — Database Architecture, Schema Conventions & Domain ERDs
- **Evidentiary Tier**: Git-Verified
- **Git Commits**: `1adc0124f` (2026-08-25), `dec502a36` (2026-08-29), `6d0eced01` (2026-08-29)
- **Primary Focus**: Physical persistence, foreign key relationships, model catalogs, and entity-relationship diagrams.
- **Key Milestones**:
  - Authored [`docs/database/overview.md`](database/overview.md) and [`docs/database/company-isolation.md`](database/company-isolation.md) establishing the single-database shared-table multi-company persistence model.
  - Authored [`docs/database/schema-conventions.md`](database/schema-conventions.md) and [`docs/database/relationships.md`](database/relationships.md) cataloging foreign keys and dynamic relationships (`resolveRelationUsing()`).
  - Authored comprehensive [`docs/database/models-index.md`](database/models-index.md).
  - Authored 3 domain ERD specifications: [`docs/database/erds/core.md`](database/erds/core.md), [`docs/database/erds/finance.md`](database/erds/finance.md), and [`docs/database/erds/operations.md`](database/erds/operations.md).

### Phase 5 — Core Plugin Documentation Specifications
- **Evidentiary Tier**: Git-Verified
- **Git Commit**: `a5abc6a94` (2026-08-31)
- **Primary Focus**: Architectural specifications for the 9 foundational Core plugins.
- **Key Milestones**:
  - Created [`docs/plugins/README.md`](plugins/README.md) containing the plugin registry and classification index.
  - Authored dedicated specifications for all 9 Core plugins: `plugin-manager`, `analytics`, `chatter`, `fields`, `full-calendar`, `partners`, `security`, `support`, and `table-views`.
  - Verified core status against `Package::isCore()` in source code.

### Phase 6 — Optional Plugin Documentation Specifications
- **Evidentiary Tier**: Git-Verified
- **Git Commit**: `6a6387e8d` (2026-09-02)
- **Primary Focus**: Architectural specifications for all 19 installable Optional plugins.
- **Key Milestones**:
  - Authored dedicated specifications for all 19 Optional plugins: `accounting`, `accounts`, `barcode`, `blogs`, `contacts`, `employees`, `inventories`, `invoices`, `maintenance`, `manufacturing`, `payments`, `products`, `projects`, `purchases`, `recruitments`, `sales`, `time-off`, `timesheets`, and `website`.
  - Verified runtime plugin dependencies (`Package::hasDependencies([...])`) and panel participation across all optional plugins.

### Phase 7 — Cross-Cutting Architecture Synthesis
- **Evidentiary Tier**: Git-Verified
- **Git Commit**: `c4a489651` (2026-09-02)
- **Primary Focus**: Cross-cutting presentation, dynamic mutation, and event-driven runtime infrastructure.
- **Key Milestones**:
  - Authored [`docs/architecture/filament-architecture.md`](architecture/filament-architecture.md) detailing Admin (`/admin`) and Customer (`/`) panel architectures, clusters, and resource registration.
  - Authored [`docs/architecture/dynamic-schema.md`](architecture/dynamic-schema.md) documenting runtime DDL table mutation and field injection by the `fields` plugin.
  - Authored [`docs/architecture/events-catalog.md`](architecture/events-catalog.md) mapping all 28 domain events, 6 listeners, 7 model observers, and 53 services.

### Phase 8 — End-to-End Transactional Business Workflows
- **Evidentiary Tier**: Git-Verified
- **Git Commit**: `f56b9f03d` (2026-09-03)
- **Primary Focus**: Multi-step business workflows, actor permissions, transactional state machines, and audit trails.
- **Key Milestones**:
  - Authored 7 comprehensive transactional workflow guides:
    - [`docs/workflows/accounting.md`](workflows/accounting.md) (Fiscal periods, journals, reconciliations)
    - [`docs/workflows/sales.md`](workflows/sales.md) (Quotation to invoice and payment)
    - [`docs/workflows/purchasing.md`](workflows/purchasing.md) (Requisition, PO, receipt, vendor bill)
    - [`docs/workflows/inventory.md`](workflows/inventory.md) (Stock moves, transfers, adjustments, scrapping)
    - [`docs/workflows/manufacturing.md`](workflows/manufacturing.md) (Production orders, BOM consumption, work orders)
    - [`docs/workflows/hr.md`](workflows/hr.md) (Recruitment pipelines, employee lifecycle, leaves)
    - [`docs/workflows/projects.md`](workflows/projects.md) (Project milestones, tasks, timesheet allocation)

### Phase 9 — Domain Business & Calculation Rules
- **Evidentiary Tier**: Git-Verified
- **Git Commits**: `b96de7fcc` (2026-09-03), `522e464ce` (2026-09-03)
- **Primary Focus**: Mathematical calculation formulas, validation rules, financial constraints, and source truth reconciliation.
- **Key Milestones**:
  - Authored 4 calculation rule engines:
    - [`docs/business-rules/accounting.md`](business-rules/accounting.md) (Debit/Credit balancing, tax calculation, multi-currency)
    - [`docs/business-rules/inventory.md`](business-rules/inventory.md) (Initial draft described Standard/AVCO/FIFO valuation methods; subsequently reconciled in `522e464ce` to establish quantitative stock tracking, physical removal strategies FIFO/LIFO, and definitive finding of zero monetary valuation layer)
    - [`docs/business-rules/purchasing.md`](business-rules/purchasing.md) (3-way matching, price lists, approval limits)
    - [`docs/business-rules/sales.md`](business-rules/sales.md) (Pricing tiers, quotation expiry, discount constraints)
  - Conducted full documentation reconciliation commit (`522e464ce`) correcting earlier overclaims against source code reality.

### Phase 10 — Prescriptive AI Rules & Binding Guidelines
- **Evidentiary Tier**: Git-Verified
- **Git Commit**: `d9c13fae0` (2026-09-04)
- **Primary Focus**: Canonical terminology glossary, binding architectural invariants, coding/testing standards, and forbidden patterns for AI agents.
- **Key Milestones**:
  - Authored [`docs/ai/terminology.md`](ai/terminology.md) establishing binding definitions and correcting core misconceptions.
  - Authored domain rulebooks: [`architecture-rules.md`](ai/architecture-rules.md), [`security-rules.md`](ai/security-rules.md), [`database-rules.md`](ai/database-rules.md), [`plugin-rules.md`](ai/plugin-rules.md), [`coding-rules.md`](ai/coding-rules.md), and [`forbidden-patterns.md`](ai/forbidden-patterns.md).
  - Authored [`docs/ai/testing-rules.md`](ai/testing-rules.md) conducting a fresh repository test audit (11 Tested / 17 Untested plugins) and setting Pest v4 standards.

### Phase 11 — Change Impact Analysis & Centralized Verification Matrix
- **Evidentiary Tier**: Git-Verified
- **Git Commit**: `ecc1cb2d4` (2026-09-04)
- **Status**: **COMPLETE / LOCKED**
- **Primary Focus**: Operational risk evaluation, blast radius assessment, and centralized verification tracking.
- **Key Milestones**:
  - Authored [`docs/architecture/change-impact.md`](architecture/change-impact.md) providing a 6-question blast radius evaluation model across presentation, security, plugins, events, persistence, packaging, and testing layers.
  - Authored [`docs/verification-matrix.md`](verification-matrix.md) creating a 12-column ledger tracking architectural, security, and count claims across the repository.
  - Formally locked Phase 11 as an immutable control baseline.

### Phase 12 — Documentation Knowledge-Base Index, Navigation & Documentation Changelog
- **Evidentiary Tier**: Active Execution / Documented History
- **Status**: **COMPLETE**
- **Primary Focus**: Canonical documentation entry point, comprehensive navigation index, and dedicated documentation changelog.
- **Key Milestones**:
  - **Part 1 (Sections 1–17)**: Authored [`docs/README.md`](README.md) as the canonical entry point, verified all 70 documentation files on disk, structured dual navigation paths (AI sequential and human developer domain-based), and mapped the complete 28-plugin ecosystem with test statuses.
  - **Part 2 (Sections 18–26)**: Authored this changelog ([`docs/CHANGELOG.md`](CHANGELOG.md)) documenting the evolution of Phases 0–12, distinguishing Git-verified from documented execution history, recording material corrections, and preserving read-only maintenance discipline.
  - **Part 3 (Sections 27–36)**: Conducted complete automated link verification across all documentation assets, confirmed zero collateral Git changes, verified Phase 11 lock boundary, and closed Phase 12.

### Phase 13 — Read-Only Final Documentation Audit
- **Evidentiary Tier**: Git-Verified
- **Git Commits**: `3ad3da082` (2026-09-05)
- **Status**: **COMPLETE / LOCKED**
- **Primary Focus**: Comprehensive read-only audit across all documentation assets, application foundation layer coverage, and closure gate.
- **Key Milestones**:
  - **Application Foundation Coverage Gate (Sections 5–22)**: Audited all 23 architecturally significant project-level surfaces outside `plugins/webkul/`. Classified each as DOCUMENTED, PARTIALLY DOCUMENTED, NOT DOCUMENTED, or NOT APPLICABLE.
  - Identified 8 significant documentation gaps: `ApplyBrandSettings` middleware, `SetLocale` middleware, customized topbar/sidebar vendor overrides, RTL/Arabic CSS architecture, Scribe/API documentation infrastructure, seeder bootstrap chain, and Playwright E2E test framework.
  - Authored [`docs/application/overview.md`](application/overview.md) as a single consolidated document covering providers, middleware, navigation shell, internationalization/RTL, database foundation, API documentation infrastructure, frontend build pipeline, testing foundation, and project configuration.
  - Updated [`docs/README.md`](README.md) documentation map from 8 to 9 functional domains (70→72 files), adding Application Foundation Domain with renumbered domain sections.
  - **Semantic Regression Gate (Section 27)**: Searched entire documentation tree for semantic violations. Found and corrected 2 regressions in `docs/README.md`: (1) "Valuation models (Standard/AVCO/FIFO)" replaced with "Quantitative stock tracking, physical removal strategies (FIFO/LIFO)" to preserve Physical Removal ≠ Financial Valuation distinction; (2) "credit checks" replaced with "customer credit-limit analysis" to avoid implying enforced credit control.
  - **Machine Path Gate (Section 30)**: Discovered and removed 31 machine-specific absolute paths from `docs/application/overview.md`. All paths converted to repository-relative format.
  - **Broken Link Gate (Section 42)**: Verified all 72 relative documentation links in `docs/README.md` resolve to existing files — 0 broken links.
  - **Document Inventory Gate (Section 38/40)**: Actual canonical file count is **72** (excluding the Phase 13 execution artifact). Corrected README.md claim from 71 to 72.
  - **Closure Gate (Sections 44–60)**: Documentation Quality Gate passed across all 7 dimensions (accuracy, completeness, traceability, consistency, navigability, AI usability, maintenance). Final Coverage Matrix produced for 23 architectural surfaces. Git integrity verified. Final verdict issued: PHASE 13 — COMPLETE / LOCKED.
  - **Final Micro-Reconciliation Gate**: Reconciled known closure inconsistencies:
    1. **CLOSURE-01 (Status Alignment)**: Synchronized `docs/README.md` Phase 13 status from `PENDING` to `COMPLETE / LOCKED`.
    2. **CLOSURE-02 & CLOSURE-13 (`ShouldQueue` Reconciled)**: Reconciled `docs/ai/terminology.md:332-343` to align with `ChatterDatabaseNotification implements ShouldQueue` (`TERM-011`) while firmly preserving the zero traditional PHP queue Job classes architectural fact.
    3. **CLOSURE-15 (Sales Quotation Template Boundary)**: Reconciled `docs/workflows/sales.md:180` to clarify that `QuotationForm` in the active admin UI does not expose a template selector, resolving the internal contradiction with line 46.
    4. **CLOSURE-12 (Framework Versions)**: Re-verified versions against `composer.lock` (Laravel 13.21.1, Filament 5.7.6, Livewire 4.3.3). Retained `AGENTS.md` Livewire v3 claim as `[KNOWN STALE PROJECT ARTIFACT]` outside documentation closure scope.
    5. **CLOSURE-08 (Inventory Valuation Semantic Boundary)**: Reconciled historical Phase 9 milestone entry in `docs/CHANGELOG.md:131` to explicitly distinguish the initial draft description from the subsequent reconciliation in `522e464ce`, confirming purely quantitative stock tracking, physical removal strategies (FIFO/LIFO), and zero monetary valuation layer.

### Phase 1 — Final Documentation Baseline Validation & Closure (Micro-Reconciliation)
- **Evidentiary Tier**: Git-Verified Diff
- **Status**: **COMPLETE / READY FOR INTEGRATION**
- **Primary Focus**: Micro-reconciliation of audited repository discrepancies (REC-001 through REC-014) across the living documentation baseline before integration into `develop`.
- **Scope & Architectural Boundaries**:
  - Strictly limited to documentation baseline reconciliation; this milestone involves **zero application feature development**, zero source-code modifications, zero test changes, zero database migration modifications, and zero Composer/configuration alterations.
  - Successfully resolves all findings identified in the read-only empirical audit without altering runtime application behavior or introducing unverified architectural claims.
- **Key Micro-Reconciliations Implemented**:
  - **REC-001 ([`docs/README.md`](README.md))**: Documented `RestrictToAllowedCompanies` as `Webkul\Support\Traits\RestrictToAllowedCompanies` (an Eloquent model trait applying `AllowedCompanyScope` with `CompanyContext` sanitization) rather than HTTP middleware.
  - **REC-002 ([`docs/ai/terminology.md`](ai/terminology.md))**: Reconciled core AI terminology: corrected dual User model architecture (`App\Models\User` as unused Laravel scaffold vs `Webkul\Security\Models\User` as runtime authenticatable bound via `AppServiceProvider::register()` and `config/auth.php`), corrected `InstallCommand` path, updated ownership trait to `HasOwnershipScope`, clarified `PermissionRegistrar` and `Webkul\Security\Bouncer` authorization architecture, and corrected `PartnerCompanyProperty` model ownership to the `accounts` plugin.
  - **REC-003 ([`docs/ai/forbidden-patterns.md`](ai/forbidden-patterns.md))**: Replaced obsolete `InstallPluginCommand.php` reference with `plugins/webkul/plugin-manager/src/Console/Commands/InstallCommand.php`.
  - **REC-004 ([`docs/architecture/change-impact.md`](architecture/change-impact.md))**: Reconciled change impact references and blast-radius evaluation entries for `RestrictToAllowedCompanies`, `HasOwnershipScope`, `InstallCommand`, and `PartnerCompanyProperty`.
  - **REC-005 ([`docs/verification-matrix.md`](verification-matrix.md))**: Updated verification matrix claims `TERM-005`, `SEC-003`, `SEC-007`, and `DEP-002` to match active code reality.
  - **REC-006 ([`docs/architecture/events-catalog.md`](architecture/events-catalog.md))**: Corrected `CompanyContext` path to `plugins/webkul/support/src/Services/CompanyContext.php`.
  - **REC-007 ([`docs/database/schema-conventions.md`](database/schema-conventions.md))**: Replaced stale migration filenames with active repository migration filenames (`2024_11_13_052541_create_custom_fields_table.php`, `2025_01_05_100751_create_products_products_table.php`, `2024_12_10_092657_create_companies_table.php`, `2026_03_31_064247_create_manufacturing_orders_table.php`, `2024_12_11_051916_create_employees_departments_table.php`).
  - **REC-008 ([`docs/database/erds/finance.md`](database/erds/finance.md))**: Reconciled stale finance migration timestamps across `accounts`, `sales`, and `purchases` relationships.
  - **REC-009 ([`docs/workflows/accounting.md`](workflows/accounting.md))**: Corrected Invoice Filament action paths to `InvoiceResource/Actions/` and renamed draft action to `ResetToDraftAction`.
  - **REC-010 ([`docs/workflows/inventory.md`](workflows/inventory.md))**: Reconciled inventory policies to operation-specific policies (`DeliveryPolicy`, `ReceiptPolicy`, `InternalTransferPolicy`), clarifying that `OperationPolicy` does not exist.
  - **REC-011 ([`docs/workflows/sales.md`](workflows/sales.md))**: Reconciled `QuotationTemplateResource` to reflect repository reality (child pages exist under `QuotationTemplateResource/Pages/`, but parent resource class file is absent).
  - **REC-012 ([`docs/plugins/accounts.md`](plugins/accounts.md))**: Removed reference to nonexistent `plugins/webkul/accounts/routes/web.php`, confirming only `routes/api.php` is defined.
  - **REC-013 ([`docs/plugins/purchases.md`](plugins/purchases.md))**: Corrected sequence seeder path to `plugins/webkul/purchases/database/seeders/SequenceSeeder.php:12`.
  - **REC-014 ([`docs/CHANGELOG.md`](CHANGELOG.md))**: Recorded Phase 1 documentation baseline validation & closure milestone.

---

## 3. Material Documentation Corrections History

A critical function of this changelog is recording instances where documentation was corrected to overturn false assumptions, misconceptions, or phantom patterns:

### 1. Rejection of Phantom `HasCompanyScope` Trait
- **Misconception**: Assuming Eloquent models enforce multi-company isolation via a trait named `HasCompanyScope`.
- **Correction**: Verified from source code that `HasCompanyScope` does not exist anywhere in the repository. Tenancy isolation is implemented via `Webkul\Support\Traits\BelongsToCompany`, `BelongsToCompanies`, `Webkul\Support\Models\Scopes\CompanyScope`, `CompaniesScope`, `ChecksCompanyConsistency`, and `CompanyContext`.

### 2. Clarification of Custom `Webkul\Security\Bouncer`
- **Misconception**: Assuming Aureus ERP relies on the open-source `silber/bouncer` package for authorization.
- **Correction**: Verified that neither `composer.json` nor `composer.lock` contains `silber/bouncer`. Bouncer is a custom, proprietary service implemented in `plugins/webkul/security/src/Bouncer.php`.

### 3. Three-Tier Dependency Taxonomy
- **Misconception**: Treating package requirements, plugin dependencies, and class usage as interchangeable.
- **Correction**: Established strict separation between:
  1. *Composer Dependencies* (`composer.json` requirements)
  2. *Runtime Plugin Dependencies* (declared via `Package::hasDependencies([...])` in service providers)
  3. *Code-Level Consumption* (actual symbol imports, service calls, and model relations).

### 4. Rejection of Queued Job Classes
- **Misconception**: Assuming background processing is handled via standard Laravel queued job classes (`app/Jobs/`).
- **Correction**: Verified from source code that the repository contains **zero PHP queue Job classes**. Asynchronous activity uses database notifications or command triggers; phantom queue workers must not be documented.

### 5. Two-Panel Architecture & Separate Customer Guard
- **Misconception**: Treating the application as a single Filament admin panel with unified session authentication.
- **Correction**: Verified that Aureus ERP runs two separate Filament panels: the Admin Panel (`/admin`) and the Customer Portal (`/`), with the Customer panel operating under a completely separate authentication guard.

### 6. Physical Removal Strategies vs Financial Valuation Methods
- **Misconception**: Conflating physical inventory removal rules (FIFO, LIFO) with financial stock valuation (Standard, AVCO, FIFO), or assuming that declared removal strategies imply an active general-ledger valuation engine.
- **Correction**: Verified from source code that stock tracking is purely quantitative (`ProductQuantity` tracks count only) with zero monetary valuation or COGS ledger entries. Clarified FIFO/LIFO as physical quant removal strategies, established that FEFO/Least Packages are non-operational/throwing at runtime, and confirmed that no verified monetary inventory valuation/costing layer exists in Aureus ERP (reconciling initial Phase 9 drafts).

### 7. Declaration vs Runtime Enforcement
- **Misconception**: Assuming that the presence of a database column or Filament form field implies complete business logic enforcement.
- **Correction**: Code verification established that certain UI/schema fields are descriptive or extensible metadata without corresponding automated backend enforcement.

### 8. Conditional Migration Registration vs Migration File Presence
- **Misconception**: Assuming all migration files in plugin directories are automatically executed by Laravel.
- **Correction**: Verified that `PackageServiceProvider::boot()` conditionally loads migrations only for core plugins or optional plugins verified as installed via `Package::isInstalled()`.

### 9. Dynamic Relationship Injection
- **Misconception**: Assuming all Eloquent relationships are statically declared within model classes.
- **Correction**: Documented that cross-plugin relationships (e.g. `accounts` relationships on the `Partner` model) are registered dynamically at runtime via `Partner::resolveRelationUsing()`.

### 10. Dynamic Schema Mutation Mechanism
- **Misconception**: Assuming custom fields are stored as JSON attributes or key-value EAV tables.
- **Correction**: Documented that the `fields` plugin executes real physical DDL `ALTER TABLE` statements and injects fields dynamically into Filament resource forms at runtime.

---

## 4. Documentation Maintenance Discipline

To prevent unintended modifications and maintain absolute repository integrity during documentation maintenance:

1. **Read-Only Inspection**: Agents and developers must conduct audits, investigations, and file explorations in strict read-only mode.
2. **Explicit Bounded Write Scope**: Modifications must strictly target authorized documentation files. Source code files must never be touched during documentation phases.
3. **Repository Cleanliness Verification**: Always verify repository state using:
   ```bash
   git status
   git diff --stat
   ```
   Ensure zero unintended or collateral modifications exist before concluding any phase.
4. **Link & Evidence Verification**: Every referenced path must resolve to a verified repository file. Never fabricate paths, dates, commits, or releases.

---

## 5. Current Documentation Status

| Phase | Title | Evidentiary Status | State |
| :---: | :--- | :---: | :---: |
| **0** | Repository Audit & Baseline Inventory | Documented History | **Complete** |
| **1** | AI Context & Baseline Guidelines | Git-Verified (`81f428783`) | **Complete** |
| **2** | High-Level Architecture Overview | Git-Verified (`69cdee902`) | **Complete** |
| **3** | Security, Authorization & Tenancy | Git-Verified (`4fbdef67c`) | **Complete** |
| **4** | Database Architecture & Domain ERDs | Git-Verified (`dec502a36`, `6d0eced01`) | **Complete** |
| **5** | Core Plugin Documentation | Git-Verified (`a5abc6a94`) | **Complete** |
| **6** | Optional Plugin Documentation | Git-Verified (`6a6387e8d`) | **Complete** |
| **7** | Cross-Cutting Architecture Synthesis | Git-Verified (`c4a489651`) | **Complete** |
| **8** | Transactional Business Workflows | Git-Verified (`f56b9f03d`) | **Complete** |
| **9** | Domain Business & Calculation Rules | Git-Verified (`b96de7fcc`, `522e464ce`) | **Complete** |
| **10** | Prescriptive AI Rules & Guidelines | Git-Verified (`d9c13fae0`) | **Complete** |
| **11** | Change Impact & Verification Matrix | Git-Verified (`ecc1cb2d4`) | **COMPLETE / LOCKED** |
| **12** | Documentation Index & Changelog | Active Execution / Verified | **Complete** |
| **13** | Read-Only Final Documentation Audit | Git-Verified (`3ad3da082`) | **COMPLETE / LOCKED** |
| **Phase 1 Closure** | Documentation Baseline Micro-Reconciliation | Git-Verified Diff | **COMPLETE / READY FOR INTEGRATION** |
