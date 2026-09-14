---
status: verified
source_of_truth: repository-configuration
last_verified: 2026-09-15
scope: repository-governance
confidence: high
---

# GitHub Governance

This document defines the canonical GitHub-level repository governance policy, controls, and administrative reconciliation for Aureus ERP. It establishes how development and collaboration are governed on GitHub, reconciling the Phase 4 Git Operating Model with repository reality and verifiable remote settings.

---

## 1. Purpose

The purpose of this document is to define the GitHub governance layer that operates above the repository's Git operating model. While the Git operating model ([`docs/development/git-workflow.md`](git-workflow.md)) defines how human engineers and autonomous AI agents branch, commit, and merge locally, this governance document defines how the GitHub repository environment governs, validates, and controls those interactions.

### Governance Hierarchy

```
+-------------------------------------------------------------------+
|                    GitHub Governance (Phase 5)                    |
|   Default branch, PR controls, rulesets, merge rules, permissions |
+-------------------------------------------------------------------+
                                  │
                                  ▼
+-------------------------------------------------------------------+
|                  Git Operating Model (Phase 4)                    |
|   Branch hierarchy, commit convention, topology, merge semantics  |
+-------------------------------------------------------------------+
                                  │
                                  ▼
+-------------------------------------------------------------------+
|               Continuous Integration & Testing (Phase 6)          |
|      Workflow definitions, test suites, required status checks    |
+-------------------------------------------------------------------+
                                  │
                                  ▼
+-------------------------------------------------------------------+
|                  Upstream Integration (Phase 7)                   |
|     Lineage synchronization, upstream tracking, conflict protocols|
+-------------------------------------------------------------------+
```

---

## 2. Repository Governance Context

### Scope and In-Scope Domains

- **Default Branch Governance**: Verification, role definition, and alignment of GitHub's default branch.
- **Branch Governance**: Reconciling integration, baseline, and topic branch rules with platform controls.
- **Pull Request Governance**: PR submission expectations, base branch targeting, description requirements, and templates.
- **Branch Protection & Rulesets**: Audit of server-side protection mechanisms, policy restrictions on direct pushes, force pushes, and branch deletions.
- **Review Governance**: Peer review expectations, author responsibilities, and code ownership status.
- **Merge Governance**: Reconciliation of Squash Merging for topic branches and Merge Commits for upstream integration.
- **Workflow & Status Check Governance**: Audit of active GitHub Actions workflows and identification of status check gating boundaries.
- **CODEOWNERS**: Audit and classification of repository code ownership mechanisms.
- **Issue Governance**: Audit of active issue templates and forms in `.github/ISSUE_TEMPLATE/`.
- **Governance Findings & Evidence Matrix**: Comprehensive audit ledger mapping controls to verified repository evidence.

### Out-of-Scope (Phase Boundaries)

- **CI / Test Suite Implementation**: Workflow implementation, test matrix design, and automated test fixes belong strictly to **Phase 6 (CI / Testing)**.
- **Upstream Synchronization Execution**: Operational merge procedures, automated sync scripts, and conflict resolution belong strictly to **Phase 7 (Upstream Integration)**.
- **Upstream Knowledge Synchronization**: Reconciling upstream documentation changes into the living knowledge base belongs strictly to **Phase 8 (Upstream -> Knowledge Synchronization)**.
- **Application Code & Infrastructure**: Modifying Laravel/Filament application code, database migrations, package dependencies, or environment configurations is prohibited during governance phases.
- **Unauthorized GitHub Mutations**: Modifying GitHub server-side repository settings, branch protection rules, or rulesets without explicit human authorization is strictly prohibited.

---

## 3. Repository / Remote Topology

Aureus ERP operates a dual-remote topology separating upstream open-source development from downstream private enterprise extensions:

```
+-------------------------------------------------------------+
|                          upstream                           |
|       (git@github.com:Mahmoud-Alqudsi/aureuserp.git)        |
|               Tracks upstream Webkul / Aureus               |
+-------------------------------------------------------------+
                               │
                               │ periodic synchronization (Phase 7)
                               ▼
+-------------------------------------------------------------+
|                           origin                            |
|        (git@github.com:Mahmoud-Alqudsi/aureus-core.git)     |
|               Primary development repository                |
+-------------------------------------------------------------+
```

### Remote and Branch Roles

1. **`upstream` (`Mahmoud-Alqudsi/aureuserp`)**:
   - Represents the public upstream fork tracking vendor updates.
   - Synchronized periodically into `origin/master`.
2. **`origin` (`Mahmoud-Alqudsi/aureus-core`)**:
   - Represents the private development and integration repository.
   - Hosts all daily development, feature branches, pull requests, and automated testing pipelines.
3. **`master` Branch Role**:
   - **Upstream Synchronization Baseline**.
   - Tracks clean upstream lineage and serves as the baseline comparison reference.
   - `master` is **NOT** the default branch and is **NOT** an internal release branch.
   - Direct pushes and topic branch merges into `master` are prohibited by project policy.
4. **`develop` Branch Role**:
   - **Primary Development / Integration Branch**.
   - Serves as the GitHub Default Branch and target base for all internal topic branch Pull Requests (`feature/*`, `fix/*`, `refactor/*`, `docs/*`, `chore/*`).
   - Direct pushes to `develop` are prohibited by project policy.

> [!NOTE]
> For comprehensive topology, branch naming, and branch lifetime rules, refer directly to [`docs/development/git-workflow.md`](git-workflow.md).

---

## 4. Default Branch

### Governance Decision and Verification

A formal governance decision has been enacted establishing `develop` as the canonical GitHub default branch of the private `origin` repository:

```
Canonical Default Branch: develop
Upstream Baseline Branch: master
```

### Direct Evidence

The default branch configuration was directly audited and verified across both GitHub remote metadata and local tracking references:

| Inspection Level | Verified State | Direct Evidence | Status |
| :--- | :--- | :--- | :--- |
| **GitHub Server-Side Default Branch** | `develop` | `git ls-remote --symref origin HEAD` output: `ref: refs/heads/develop HEAD` (`76aa5f9a644390e2f6a87fea3e841169ae036cfd`) | **VERIFIED** |
| **Local Remote Tracking HEAD** | `refs/remotes/origin/develop` | `git symbolic-ref refs/remotes/origin/HEAD` points to `refs/remotes/origin/develop` (synchronized via `git remote set-head origin -a`) | **VERIFIED** |
| **Active Working Branch** | `refactor/ai-knowledge-architecture` | `git branch --show-current` | **VERIFIED** |

### Distinction Between Remote and Local States

- **GitHub Default Branch**: The server-side default branch on `origin` is confirmed as `develop`. All new Pull Requests created on GitHub without an explicit base parameter automatically default to `develop`.
- **Upstream Synchronization Baseline**: `master` remains strictly dedicated as the upstream vendor synchronization baseline.
- **Classification**: **`VERIFIED`**.

---

## 5. Branch Governance

### Reconciliation with Phase 4 Operating Model

The repository's branch governance directly reconciles the Phase 4 Git operating model with platform-level controls:

```
develop (GitHub Default & Primary Integration Branch)
   │
   ├── feature/*  ──► Target: develop (Squash Merge)
   ├── fix/*      ──► Target: develop (Squash Merge)
   ├── refactor/* ──► Target: develop (Squash Merge)
   ├── docs/*     ──► Target: develop (Squash Merge)
   └── chore/*    ──► Target: develop (Squash Merge)
```

### Branch Rules and Constraints

1. **Branch Source**: All normal topic branches must branch from the latest `develop`.
2. **Branch Target**: All normal topic branches must target `develop` as their Pull Request base.
3. **Branch Naming**: Must adhere to `<type>/<description>` using kebab-case and lowercase characters. Allowed types: `feature`, `fix`, `refactor`, `docs`, `chore`. Generic branch names (`temp`, `test`, `wip`, `patch`) are forbidden.
4. **Direct Push Prohibition**:
   - Direct pushes to `develop` are prohibited by project policy (`POLICY`).
   - Direct pushes to `master` are prohibited by project policy (`POLICY`).
   - All code, documentation, and configuration changes must arrive via reviewed Pull Request.
5. **Enforcement Distinction**: While direct push prohibition is strict project policy, technical server-side push rejection via GitHub ruleset is **`NOT VERIFIED`** due to lack of API credentials in the environment.

---

## 6. Pull Request Governance

### PR Expectations and Workflow

- **Mandatory PR Entry**: All non-upstream changes entering `develop` must arrive via Pull Request.
- **Target Branch**: Must be `develop`. PRs targeting `master` are prohibited except during Phase 7 upstream synchronization procedures.

### Pull Request Template

The repository provides a standardized Pull Request template located at [`.github/PULL_REQUEST_TEMPLATE.md`](file:///home/mahmoud/projects/aureuserp/.github/PULL_REQUEST_TEMPLATE.md) (42 lines):
- **Description**: Clear explanation of change context and purpose.
- **Related Issue**: Traceability link (`Closes #`).
- **Type of Change**: Categorization (`Bug fix`, `New feature`, `Breaking change`, `Documentation update`, `Refactor`, `Tests`).
- **Self-Review Checklist**: Code standards, comments, documentation, test coverage, local test execution.
- **Testing Details & Screenshots**: Empirical proof of correctness.

### PR Review Governance

- **Project Review Expectations**: Pull Requests are expected to undergo review prior to merging into `develop` as established by project policy (`POLICY`).
- **GitHub Server-Side Review Enforcement**: Server-side mandatory approving reviews and dismissal settings on GitHub are **`NOT VERIFIED`** via API.
- **CI Workflow Execution vs. Merge Gating**:
  - Automated CI workflows (`pest_tests.yml`, `playwright_tests.yml`, `translations_check.yml`) execute on Pull Requests targeting `develop` and `master` (**`VERIFIED`**).
  - The execution of these workflows does **NOT** prove that GitHub blocks merging when checks fail; required status check gating on GitHub is **`NOT VERIFIED`**.
  - Formal CI gating, test strategy, and required status check rulesets belong strictly to **Phase 6 (CI / Testing)**.
- **Template and Operational Reality**:
  - The existence of [`.github/PULL_REQUEST_TEMPLATE.md`](file:///home/mahmoud/projects/aureuserp/.github/PULL_REQUEST_TEMPLATE.md) is **`VERIFIED`**.
  - Historical Pull Request usage is documented in Git history (e.g., PR #8 squash merge commit `76aa5f9a6`), but historical PR usage does not prove active GitHub server-side PR enforcement.

---

## 7. Branch Protection / Rulesets

### Audited Configuration

| Mechanism | Target Branches | Documented Policy | GitHub-Side Enforcement |
| :--- | :--- | :--- | :--- |
| **Direct Push Block** | `develop`, `master` | Prohibited by project policy | **NOT VERIFIED** |
| **Force-Push Restriction** | `develop`, `master` | Prohibited on shared branches | **NOT VERIFIED** |
| **Branch Deletion Restriction** | `develop`, `master` | Prohibited on shared branches | **NOT VERIFIED** |
| **Required Approving Reviews** | `develop` | Policy requires review prior to merge | **NOT VERIFIED** |
| **Required Status Checks** | `develop`, `master` | CI checks run; ruleset gating deferred | **DEFERRED (Phase 6)** |
| **Conversation Resolution** | `develop` | Recommended policy | **NOT VERIFIED** |
| **Linear History Requirement** | `develop` | Maintained via Squash Merge policy | **NOT VERIFIED** |

### Declarative Rulesets

Inspection of `.github/` confirms that **no declarative ruleset files exist** in the repository. Server-side GitHub branch protection rules or repository rulesets could not be programmatically verified due to private repository access constraints.

### Recommended Platform Configuration

When GitHub configuration changes are authorized, the project recommends establishing a GitHub Repository Ruleset targeting `develop` and `master`:
- Enforce deletion restrictions and force-push blocks.
- Require Pull Requests before merging with conversation resolution.
- Require passing CI status checks (codified under Phase 6).
- Status: **`PENDING AUTHORIZATION`** (will not be applied without explicit authorization).

---

## 8. Merge Governance

### Canonical Merge Strategies

| Operation / Path | Canonical Strategy | Policy Authority | Historical Practice | GitHub Setting Enforcement |
| :--- | :--- | :--- | :--- | :--- |
| **Topic Branches $\to$ `develop`** | **Squash Merge** | Project Policy (`docs/development/git-workflow.md` Section 7) | Observed in PR #8 commit `76aa5f9a6` | **NOT VERIFIED** |
| **`upstream/master` $\to$ `master`** | **Merge Commit (`--no-ff`)** | Project Policy (`docs/development/git-workflow.md` Section 7 & 9) | Observed in commit `49e330b5e` | **NOT VERIFIED** |
| **`master` $\to$ `develop`** | **Merge Commit (`--no-ff`)** | Project Policy (`docs/development/git-workflow.md` Section 7) | Observed in commit `15a76bf09` | **NOT VERIFIED** |

### Strategic Principles and Operational Boundaries

1. **Normal Pull Requests entering `develop`**:
   - Canonical strategy: **Squash Merge**.
   - Maintains an atomic, linear commit history on `develop`.
   - Each Pull Request condenses into exactly one descriptive Conventional Commit.
   - **No Commit Thresholds**: Squash merging applies universally, regardless of topic branch commit count.
2. **Upstream Synchronization**:
   - Upstream synchronization is a separate Git operation governed by Phase 7.
   - Preserves complete upstream vendor commit lineage and author attribution via Merge Commits (`--no-ff`).
   - Detailed operational merge procedures, conflict handling, and validation scripts belong strictly to **Phase 7 (Upstream Integration)**.
3. **GitHub Repository Merge Settings Consideration**:
   - Repository settings in GitHub govern PR merges via the GitHub UI.
   - Any recommended configuration of GitHub PR merge options (such as defaulting to Squash Merge for topic branches) must **not** restrict or conflict with upstream synchronization procedures that require Merge Commits (`--no-ff`), noting that upstream synchronization procedures may execute via command line or separate integration workflows outside general UI PR restrictions.
4. **No Rebase on Shared Branches**:
   - Rebasing, resetting, or history rewriting on `develop` or `master` is strictly prohibited by policy.

---

## 9. CODEOWNERS

### Audit Finding

A comprehensive search across the repository confirms that **no CODEOWNERS file exists**:
- Inspected `.github/CODEOWNERS`: Absent.
- Inspected `CODEOWNERS` (root): Absent.
- Inspected `docs/CODEOWNERS`: Absent.

### Assessment

- **Classification**: **`NOT CONFIGURED`**.
- **Assessment**: No CODEOWNERS mechanism is currently configured. The project does not currently define path-based ownership through CODEOWNERS. At current contributor scale, introducing mandatory path-based CODEOWNERS approvals would create unnecessary administrative overhead.
- **Roadmap**: Introducing modular code ownership (e.g., mapping `plugins/webkul/accounts` or `plugins/webkul/security` to dedicated maintainers) is classified as **`RECOMMENDED`** for future scaling phases.

---

## 10. Issue Templates

### Audited Issue Templates

Inspection of [`.github/ISSUE_TEMPLATE/`](file:///home/mahmoud/projects/aureuserp/.github/ISSUE_TEMPLATE/) confirms the presence of 3 templates:

| Template File | Format | Issue Type | Governance Attributes |
| :--- | :--- | :--- | :--- |
| [`bug.yml`](file:///home/mahmoud/projects/aureuserp/.github/ISSUE_TEMPLATE/bug.yml) | GitHub Issue Form (YAML) | Bug Report (`labels: ["Bug"]`) | Prerequisites validation, affected version, environment preconditions (PHP, MySQL, OS, browser), reproduction steps, actual vs expected behavior, screenshots, context |
| [`bug_report.md`](file:///home/mahmoud/projects/aureuserp/.github/ISSUE_TEMPLATE/bug_report.md) | Legacy Markdown Template | Bug Report (`labels: bug`) | Freeform markdown bug report covering similar fields |
| [`feature_request.yml`](file:///home/mahmoud/projects/aureuserp/.github/ISSUE_TEMPLATE/feature_request.yml) | GitHub Issue Form (YAML) | Feature Request (`labels: ['Feature Request']`) | Problem description, proposed solution, alternatives considered, additional context |

### Observations and Recommendations

1. **Template Redundancy**: Having both `bug.yml` (modern form) and `bug_report.md` (legacy template) active causes duplicate bug reporting options in GitHub's issue chooser UI.
2. **Missing Structured Categories**: Structured issue forms do not yet exist for documentation updates, refactorings, or upstream synchronization tasks.
3. **Recommendation**: In a future governance maintenance phase, consolidate bug reporting by deprecating `bug_report.md` in favor of `bug.yml`, and consider structured forms for `docs.yml` and `refactor.yml`.
4. **Implementation Boundary**: This finding is a **governance recommendation only** (**`RECOMMENDED`**). No files in `.github/ISSUE_TEMPLATE/` are created, renamed, or deleted during Phase 5.

---

## 11. GitHub Actions Governance

### Verified Workflow Files

Inspection of [`.github/workflows/`](file:///home/mahmoud/projects/aureuserp/.github/workflows/) confirms 4 active workflows:

| Workflow File | Triggers (`on:`) | Target Branches | Permissions | Purpose |
| :--- | :--- | :--- | :--- | :--- |
| [`pest_tests.yml`](file:///home/mahmoud/projects/aureuserp/.github/workflows/pest_tests.yml) | `push`, `pull_request` | `master`, `develop` | `contents: read` | Automated Pest v4 unit/feature tests across MySQL 8.0 & PostgreSQL 16 on PHP 8.3 |
| [`playwright_tests.yml`](file:///home/mahmoud/projects/aureuserp/.github/workflows/playwright_tests.yml) | `push`, `pull_request`, `merge_group`, `workflow_dispatch` | `master`, `develop` | `contents: read` | End-to-end browser testing with Playwright (6 shards) on MySQL & PostgreSQL |
| [`translations_check.yml`](file:///home/mahmoud/projects/aureuserp/.github/workflows/translations_check.yml) | `push`, `pull_request` | `master`, `develop` | `contents: read` | Translation consistency checks (`php artisan translations:check --details`) |
| [`docker_publish.yml`](file:///home/mahmoud/projects/aureuserp/.github/workflows/docker_publish.yml) | `push` (tags `v*`), `workflow_dispatch` | Tags matching `v*` | `contents: read` | Multi-architecture production Docker image build and publish to Docker Hub |

### Governance Analysis and CI Boundary

- **Concurrency Controls**: `pest_tests.yml`, `playwright_tests.yml`, and `translations_check.yml` configure `${{ github.workflow }}-${{ github.ref }}` concurrency groups with `cancel-in-progress: true` to conserve CI runner resources.
- **Least-Privilege Permissions**: All 4 workflows explicitly declare `contents: read`. This least-privilege permission is verified for these specific workflow files; it does not constitute an audit of the overall repository token settings.
- **Required Status Checks**: While workflows trigger on Pull Requests targeting `develop` and `master`, whether they are enforced as mandatory blocking status checks in GitHub rulesets is **`NOT VERIFIED`**.
- **Phase Boundary Notice**: Formal CI gating, status check ruleset requirements, test matrix reliability, and test runner strategy belong strictly to **Phase 6 (CI / Testing)**.

---

## 12. Governance Classification

Every governance control and observation in this document is classified according to the following taxonomy:

- **`VERIFIED`**: Direct repository or GitHub evidence independently confirms the state.
- **`POLICY`**: The project has explicitly adopted the rule in canonical documentation, but technical server-side enforcement is unverified.
- **`HISTORICAL PRACTICE`**: Observable in Git commit history or past contributions; indicates historical execution, but does not by itself prove platform enforcement or future policy.
- **`RECOMMENDED`**: A reasonable governance improvement identified by audit, not yet adopted.
- **`PENDING DECISION`**: A governance question requiring project stakeholder resolution.
- **`PENDING AUTHORIZATION`**: A desired control is identified but requires explicit administrative authorization prior to mutation.
- **`DEFERRED`**: Governance responsibility formally assigned to a subsequent roadmap phase.
- **`NOT CONFIGURED`**: The relevant governance mechanism was audited and does not exist.
- **`NOT VERIFIED`**: The remote configuration could not be independently inspected.

### Special Constraints

1. **Autonomous AI Agents**: "Autonomous AI Agent" is **NOT** a native GitHub permission role. AI coding agents operate strictly through the identity, credentials, SSH keys, or tokens under which they are executed. AI operating rules belong to [`AGENTS.md`](file:///home/mahmoud/projects/aureuserp/AGENTS.md) and [`docs/ai/`](file:///home/mahmoud/projects/aureuserp/docs/ai/).
2. **Hotfix Governance**: No formal hotfix governance policy was established in the audited sources. Standard `fix/*` topic branch lifecycles apply to all bug resolution. Dedicated emergency hotfix workflows remain **`NOT CONFIGURED`**.

---

## 13. Governance Findings

The audit identified the following genuine, evidence-based governance findings:

### GOV-001: Direct Push Restriction on `develop` and `master`
- **Classification**: **`POLICY`** (GitHub Enforcement: **`NOT VERIFIED`**)
- **Evidence**: `docs/development/git-workflow.md` Section 3 establishes project policy prohibiting direct pushes to `develop` and `master`. Server-side push rejection on GitHub has not been independently verified via API.
- **Impact**: Direct pushes to `develop` or `master` are prohibited by project policy, but GitHub-side technical enforcement has not been independently verified.
- **Recommended Action**: Configure a GitHub Repository Ruleset blocking direct pushes to `develop` and `master`.
- **Owning Phase**: Phase 5 (`PENDING AUTHORIZATION`).

### GOV-002: Mandatory PR Review Enforcement
- **Classification**: **`POLICY`** (GitHub Enforcement: **`NOT VERIFIED`**)
- **Evidence**: `docs/development/git-workflow.md` requires PR reviews prior to integration into `develop`. GitHub server-side approval requirements are unverified via API.
- **Impact**: Unreviewed changes could theoretically be merged if platform rules do not block them.
- **Recommended Action**: Configure required approvals in GitHub rulesets when team scaling warrants.
- **Owning Phase**: Phase 5 (`PENDING AUTHORIZATION`).

### GOV-003: GitHub Repository Merge Button Configuration
- **Classification**: **`POLICY`** (GitHub Enforcement: **`NOT VERIFIED`**)
- **Evidence**: Project policy specifies Squash Merge for normal topic branches and Merge Commits for upstream synchronization (`docs/development/git-workflow.md` Section 7). GitHub repository UI merge button restrictions are unverified via API.
- **Impact**: Non-squash merges could inadvertently be selected in the GitHub UI for topic PRs if settings allow all merge types.
- **Recommended Action**: If GitHub PR merge options are configured, ensure Squash Merge is the designated method for normal topic Pull Requests targeting `develop`, while preserving capability for Merge Commits (`--no-ff`) where required for upstream synchronization, or noting that local upstream synchronization procedures execute outside GitHub UI PR restrictions.
- **Owning Phase**: Phase 5 (`PENDING AUTHORIZATION`).

### GOV-004: Issue Template Redundancy
- **Classification**: **`RECOMMENDED`**
- **Evidence**: `.github/ISSUE_TEMPLATE/` contains both `bug.yml` (YAML form) and `bug_report.md` (legacy Markdown).
- **Impact**: Causes duplicate bug report options in the GitHub issue chooser UI.
- **Recommended Action**: In a future governance maintenance phase, deprecate and remove legacy `bug_report.md` in favor of `bug.yml`. This remains a recommendation only; no template files are modified in Phase 5.
- **Owning Phase**: Phase 5 (`PENDING AUTHORIZATION`).

### GOV-005: CODEOWNERS Configuration
- **Classification**: **`NOT CONFIGURED`**
- **Evidence**: No `CODEOWNERS` file exists in `.github/`, root, or `docs/`.
- **Impact**: Reviewers must be manually assigned on all Pull Requests.
- **Recommended Action**: Retain current unconfigured status for centralized maintainer model; plan modular ownership if team expands.
- **Owning Phase**: Phase 5 (`RECOMMENDED`).

### GOV-006: Automated CI Status Checks Gating
- **Classification**: **`DEFERRED`**
- **Evidence**: `.github/workflows/pest_tests.yml`, `playwright_tests.yml`, and `translations_check.yml` run on PRs, but ruleset gating is unverified.
- **Impact**: PRs could be merged even if automated test suites fail.
- **Recommended Action**: Formally codify required status checks during Phase 6 CI governance.
- **Owning Phase**: Phase 6 (CI / Testing).

---

## 14. Deferred Governance

To preserve strict architectural boundaries across roadmap phases, the following governance areas are formally deferred:

| Deferred Control Area | Owning Phase | Scope and Governance Responsibilities |
| :--- | :--- | :--- |
| **CI Gating & Required Status Checks** | **Phase 6 — CI / Testing** | Codifying required status checks in GitHub rulesets; establishing test matrix reliability; configuring failure notifications and coverage thresholds. |
| **Upstream Synchronization Procedures** | **Phase 7 — Upstream Integration** | Defining operational sync commands; vendor conflict resolution protocols; verification procedures for vendor alignment; tag synchronization. |
| **Living Knowledge Synchronization** | **Phase 8 — Upstream -> Knowledge Synchronization** | Reconciling upstream schema and feature modifications against the living documentation suite (`docs/`); updating architecture specs and ERDs. |

---

## 15. Evidence Matrix

| Area | Expected State | Actual State | Classification | Evidence Source | Phase |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Default Branch** | `develop` | `develop` | **VERIFIED** | `git ls-remote --symref origin HEAD`, `git symbolic-ref refs/remotes/origin/HEAD` | Phase 5 |
| **Branch Role: develop** | Primary integration branch | Primary integration branch | **VERIFIED** | `git ls-remote --symref origin HEAD`, `docs/development/git-workflow.md` | Phase 5 |
| **Branch Role: master** | Upstream synchronization baseline | Upstream synchronization baseline | **POLICY** | `docs/development/git-workflow.md`, commit history | Phase 5 |
| **Direct Push Restriction on develop & master** | Prohibited on `develop` & `master` | Prohibited by policy; server-side block unverified | **POLICY** | `docs/development/git-workflow.md` Section 3 (GitHub enforcement: `NOT VERIFIED`) | Phase 5 |
| **Pull Request Template** | Present | Present at `.github/PULL_REQUEST_TEMPLATE.md` | **VERIFIED** | File inspection `.github/PULL_REQUEST_TEMPLATE.md` | Phase 5 |
| **Pull Request Requirement for develop** | Mandatory for `develop` | Mandatory by policy; practiced in history | **POLICY** | `docs/development/git-workflow.md`, PR #8 commit `76aa5f9a6` (GitHub enforcement: `NOT VERIFIED`) | Phase 5 |
| **Pull Request Review Requirement** | Review prior to merge | Required by policy; server-side approval unverified | **POLICY** | `docs/development/git-workflow.md` (GitHub enforcement: `NOT VERIFIED`) | Phase 5 |
| **Topic Branch Merge Strategy** | Squash Merge | Established by policy; observed in PR #8 | **POLICY** | `docs/development/git-workflow.md` Section 7, commit `76aa5f9a6` (GitHub enforcement: `NOT VERIFIED`) | Phase 5 |
| **Upstream Integration Merge Strategy** | Merge Commit (`--no-ff`) | Established by policy; observed in history | **POLICY** | `docs/development/git-workflow.md` Section 7 & 9, commits `15a76bf09`, `49e330b5e` | Phase 5 / Phase 7 |
| **Branch Protection / Rulesets** | Configured on GitHub | Server-side protection unverified via API | **NOT VERIFIED** | Repository file inspection; GitHub API unverified | Phase 5 |
| **CODEOWNERS** | Configured if needed | Absent across repository | **NOT CONFIGURED** | Inspected `.github/CODEOWNERS`, `CODEOWNERS`, `docs/CODEOWNERS` | Phase 5 |
| **Issue Templates** | Form-based templates | `bug.yml`, `bug_report.md`, `feature_request.yml` present | **VERIFIED** | Inspected `.github/ISSUE_TEMPLATE/` directory | Phase 5 |
| **GitHub Actions Workflows** | Active on PRs | 4 workflows active; permissions `contents: read` | **VERIFIED** | Inspected `.github/workflows/` directory | Phase 5 |
| **Required Status Checks Gating** | Enforced gating | Workflows execute; gating deferred | **DEFERRED** | Inspected `.github/workflows/` directory | Phase 6 |
| **Upstream Sync Procedure** | Documented procedure | Dual-remote topology active; execution deferred | **DEFERRED** | Phase 4 docs, git remotes | Phase 7 |

---

## 16. Verification Metadata

This governance specification is verified against active repository configuration, commit history, and remote tracking state.

- **Status**: `verified`
- **Source of Truth**: `repository-configuration`
- **Last Verified**: `2026-09-15`
- **Scope**: `repository-governance`
- **Confidence**: `high`
