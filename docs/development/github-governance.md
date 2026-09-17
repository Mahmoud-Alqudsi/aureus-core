---
status: verified
source_of_truth: repository-configuration
last_verified: 2026-09-17
scope: repository-governance
confidence: high
---

# GitHub Governance

This document defines the canonical GitHub-level repository governance policy, controls, and administrative reconciliation for Aureus ERP. It establishes how development and collaboration are governed on GitHub, reconciling the Operational Stage O4 Git Operating Model with repository reality and verifiable remote settings.

---

## 1. Purpose

The purpose of this document is to define the GitHub governance layer that operates above the repository's Git operating model. While the Git operating model ([`docs/development/git-workflow.md`](git-workflow.md)) defines how human engineers and autonomous AI agents branch, commit, and merge locally, this governance document defines how the GitHub repository environment governs, validates, and controls those interactions.

### Governance Hierarchy

```
+-------------------------------------------------------------------+
|                    GitHub Governance (Operational Stage O5)       |
|   Default branch, PR controls, rulesets, merge rules, permissions |
+-------------------------------------------------------------------+
                                  │
                                  ▼
+-------------------------------------------------------------------+
|                  Git Operating Model (Operational Stage O4)       |
|   Branch hierarchy, commit convention, topology, merge semantics  |
+-------------------------------------------------------------------+
                                  │
                                  ▼
+-------------------------------------------------------------------+
|               Continuous Integration & Testing (Operational O6)   |
|      Workflow definitions, test suites, required status checks    |
+-------------------------------------------------------------------+
                                  │
                                  ▼
+-------------------------------------------------------------------+
|                  Upstream Integration (Operational Stage O7)      |
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

### Out-of-Scope (Operational Stage Boundaries)

- **CI / Test Suite Implementation**: Workflow implementation, test matrix design, and automated test fixes belong strictly to **Operational Stage O6 (CI / Testing)**.
- **Upstream Synchronization Execution**: Operational merge procedures, automated sync scripts, and conflict resolution belong strictly to **Operational Stage O7 (Upstream Integration)**.
- **Upstream Knowledge Synchronization**: Reconciling upstream documentation changes into the living knowledge base belongs strictly to **Operational Stage O8 (Change Management & Knowledge Maintenance)**.
- **Application Code & Infrastructure**: Modifying Laravel/Filament application code, database migrations, package dependencies, or environment configurations is prohibited during governance phases.
- **Unauthorized GitHub Mutations**: Modifying GitHub server-side repository settings, branch protection rules, or rulesets without explicit human authorization is strictly prohibited.

---

## 3. Repository / Remote Topology

Aureus ERP operates a dual-remote topology separating upstream open-source development from downstream public enterprise development:

```
+-------------------------------------------------------------+
|                          upstream                           |
|       (git@github.com:Mahmoud-Alqudsi/aureuserp.git)        |
|               Tracks upstream Webkul / Aureus               |
+-------------------------------------------------------------+
                               │
                               │ periodic synchronization (Operational Stage O7)
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
   - Synchronized periodically into `origin/develop` through the O7 runbook.
2. **`origin` (`Mahmoud-Alqudsi/aureus-core`)**:
   - Represents the public development and integration repository.
   - Hosts all daily development, feature branches, pull requests, and automated testing pipelines.
3. **`master` Branch Role**:
   - **Stable Release Branch**.
   - Receives verified release promotions from `develop` and is the sole source of downstream `v*` release tags.
   - `master` is **NOT** the default branch.
   - Direct pushes and general topic branch merges into `master` are prohibited by project policy.
4. **`develop` Branch Role**:
   - **Primary Development / Integration Branch**.
   - Serves as the GitHub Default Branch and target base for all internal topic branch Pull Requests (`feature/*`, `fix/*`, `refactor/*`, `docs/*`, `chore/*`).
   - Direct pushes to `develop` are prohibited by project policy.

> [!NOTE]
> For comprehensive topology, branch naming, and branch lifetime rules, refer directly to [`docs/development/git-workflow.md`](git-workflow.md).

---

## 4. Default Branch

### Governance Decision and Verification

A formal governance decision has been enacted establishing `develop` as the canonical GitHub default branch of the public `origin` repository:

```
Canonical Default Branch: develop
Stable Release Branch: master
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
- **Stable Release Branch**: `master` receives only verified release promotions from `develop`; upstream integration occurs first on `develop`.
- **Classification**: **`VERIFIED`**.

---

## 5. Branch Governance

### Reconciliation with Operational Stage O4 Git Operating Model

The repository's branch governance directly reconciles the Operational Stage O4 Git operating model with platform-level controls:

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
4. **Upstream Synchronization Pull Request**: `chore/upstream-sync-<date>` is created from the latest `develop` and opened as a Pull Request to `develop` with a Merge Commit to preserve upstream lineage. A history-preserving recovery uses `chore/upstream-sync-rollback-<date>` from its protected target.
5. **Release Pull Request**: The sole normal source permitted to target `master` is `develop`, through an authorized, self-reviewed release Pull Request using a Merge Commit. Emergency upstream security fixes and history-preserving recoveries are exceptional and must be promoted back to `develop` after merging.
6. **Direct Push Prohibition**:
   - Direct pushes to `develop` are prohibited by project policy (`POLICY`).
   - Direct pushes to `master` are prohibited by project policy (`POLICY`).
   - All code, documentation, configuration, and upstream synchronization changes must arrive via a self-reviewed Pull Request with recorded verification.
6. **Enforcement Distinction**: Server-side protection is **`VERIFIED`**: active repository rulesets require Pull Requests for both protected branches and block deletion and force pushes. Required status checks remain deliberately deferred to O6.

---

## 6. Pull Request Governance

### PR Expectations and Workflow

- **Mandatory PR Entry**: Every change entering `develop` or `master` must arrive via Pull Request.
- **Target Branch**: Normal topic and upstream-synchronization Pull Requests target `develop`. A verified release Pull Request targets `master` from `develop`. Only explicitly authorized emergency upstream security fixes or history-preserving recoveries may use another source for `master`.

### Pull Request Template

The repository provides a standardized Pull Request template located at [`.github/PULL_REQUEST_TEMPLATE.md`](../../.github/PULL_REQUEST_TEMPLATE.md) (42 lines):
- **Description**: Clear explanation of change context and purpose.
- **Related Issue**: Traceability link (`Closes #`).
- **Type of Change**: Categorization (`Bug fix`, `New feature`, `Breaking change`, `Documentation update`, `Refactor`, `Tests`).
- **Self-Review Checklist**: Code standards, comments, documentation, test coverage, local test execution.
- **Testing Details & Screenshots**: Empirical proof of correctness.

### PR Review Governance

- **Solo-Maintainer Review Policy**: The Pull Request author must complete the template self-review and record proportionate verification before merging into `develop` or `master`. The active rulesets require **zero** approving reviews, so an independent approval is not a merge prerequisite. Independent review remains required whenever another repository control or the change risk requires it.
- **GitHub Server-Side Review Enforcement**: **`VERIFIED`** by GitHub API: both protected branches require a Pull Request and conversation resolution, require zero approvals, and do not require approval from someone other than the latest pusher. Stale-review dismissal remains configured; with zero required approvals it is not a merge gate.
- **CI Workflow Execution vs. Merge Gating**:
  - Automated CI workflows (`pest_tests.yml`, `playwright_tests.yml`, `translations_check.yml`) execute on Pull Requests targeting `develop` and `master` (**`VERIFIED`**).
  - The execution of these workflows does **NOT** prove that GitHub blocks merging when checks fail; required status check gating on GitHub is **`NOT VERIFIED`**.
  - Formal CI gating, test strategy, and required status check rulesets belong strictly to **Operational Stage O6 (CI / Testing)**.
- **Template and Operational Reality**:
  - The existence of [`.github/PULL_REQUEST_TEMPLATE.md`](../../.github/PULL_REQUEST_TEMPLATE.md) is **`VERIFIED`**.
  - Historical Pull Request usage is documented in Git history (e.g., PR #8 squash merge commit `76aa5f9a6`), but historical PR usage does not prove active GitHub server-side PR enforcement.

---

## 7. Branch Protection / Rulesets

### Audited Configuration

| Mechanism | Target Branches | Documented Policy | GitHub-Side Enforcement |
| :--- | :--- | :--- | :--- |
| **Direct Push Block** | `develop`, `master` | Prohibited by project policy | **VERIFIED** (`pull_request` rules) |
| **Force-Push Restriction** | `develop`, `master` | Prohibited on shared branches | **VERIFIED** (`non_fast_forward` rules) |
| **Branch Deletion Restriction** | `develop`, `master` | Prohibited on shared branches | **VERIFIED** (`deletion` rules) |
| **Required Approving Reviews** | `develop`, `master` | Self-review and recorded verification required; no independent approval gate | **VERIFIED** (zero required approvals; latest-push approval disabled) |
| **Required Status Checks** | `develop`, `master` | CI checks run; ruleset gating deferred | **DEFERRED (Operational Stage O6)** |
| **Conversation Resolution** | `develop`, `master` | Required before merge | **VERIFIED** |
| **Merge Strategy Controls** | Repository-wide | Squash and merge commits enabled; rebase disabled | **VERIFIED** (source-specific choice remains review-governed) |

### Declarative Rulesets

Inspection of `.github/` confirms that **no declarative ruleset files exist** in the repository; rulesets are GitHub server-side configuration. Their active state and effective branch rules were verified through the GitHub API on 2026-09-17.

### Approved Platform Configuration Contract

The following rulesets are the approved O5 enforcement target. They are deliberately split so that daily integration and stable releases can be audited independently.

| Ruleset | Target | Required controls | Explicit exclusions / rationale |
| :--- | :--- | :--- | :--- |
| `protect-develop` | `refs/heads/develop` | Require a Pull Request; require zero approving reviews; dismiss stale approvals after new commits; require conversation resolution; block force pushes and deletions. | Normal topics use Squash Merge; authorized upstream synchronization uses a Merge Commit. Required CI checks are deferred to O6. |
| `protect-release-master` | `refs/heads/master` | Require a Pull Request; require zero approving reviews; dismiss stale approvals after new commits; require conversation resolution; block force pushes and deletions. | The normal source is the verified `develop` release promotion using a Merge Commit. Required CI checks are deferred to O6. |

Both rulesets must have no standing bypass actor. An exceptional change requires explicit human authorization, a recorded reason, and a deliberate temporary ruleset change; it must not be disguised as a normal direct push.

### Repository Merge Settings Contract

Repository merge settings must enable **Squash Merge** and **Merge Commits**, and disable **Rebase Merge**. GitHub cannot distinguish normal topic Pull Requests from upstream-synchronization Pull Requests when both target `develop`; the maintainer therefore enforces Squash Merge for normal topics and Merge Commits for upstream synchronization and release promotions. Merge Queue remains disabled until O6 makes every required validation workflow compatible with the `merge_group` event.

### Application and Verification Procedure

The approved configuration was applied and verified on 2026-09-17 by direct GitHub API inspection:

| Ruleset | Identifier | Target | Enforcement | Verified controls |
| :--- | :--- | :--- | :--- | :--- |
| `protect-develop` | `23566563` | `refs/heads/develop` | `active` | Pull Request, zero required approvals, stale-review dismissal, conversation resolution, deletion block, force-push block, no bypass actors |
| `protect-release-master` | `23566566` | `refs/heads/master` | `active` | Pull Request, zero required approvals, stale-review dismissal, conversation resolution, deletion block, force-push block, no bypass actors |

The effective-rules endpoint confirms that each target receives `pull_request`, `deletion`, and `non_fast_forward` rules. Repository merge settings are also verified as Squash Merge enabled, Merge Commits enabled, and Rebase Merge disabled. Required CI status checks were intentionally not added and remain O6 work.

---

## 8. Merge Governance

### Canonical Merge Strategies

| Operation / Path | Canonical Strategy | Policy Authority | Historical Practice | GitHub Setting Enforcement |
| :--- | :--- | :--- | :--- | :--- |
| **Topic Branches $\to$ `develop`** | **Squash Merge** | Project Policy (`docs/development/git-workflow.md` Section 7) | Observed in PR #8 commit `76aa5f9a6` | **NOT VERIFIED** |
| **`upstream/master` $\to$ `develop`** | **Merge Commit (`--no-ff`)** | Project Policy (`docs/development/git-workflow.md` Section 7 & 9) | Historical upstream merge in commit `49e330b5e` used the previous topology | **POLICY** |
| **`develop` $\to$ `master` release** | **Merge Commit (`--no-ff`)** | Project Policy (`docs/development/git-workflow.md` Section 7 & 8) | First promotion pending | **POLICY** |

### Strategic Principles and Operational Boundaries

1. **Normal Pull Requests entering `develop`**:
   - Canonical strategy: **Squash Merge**.
   - Maintains an atomic, linear commit history on `develop`.
   - Each Pull Request condenses into exactly one descriptive Conventional Commit.
   - **No Commit Thresholds**: Squash merging applies universally, regardless of topic branch commit count.
2. **Upstream Synchronization**:
   - Upstream synchronization is a separate Git operation canonically documented in [`docs/development/upstream-sync.md`](upstream-sync.md).
   - Preserves complete upstream vendor commit lineage and author attribution via Merge Commits (`--no-ff`).
   - Detailed operational merge procedures, conflict handling, safety checkpoints, and validation steps belong to that runbook.
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

Inspection of [`.github/ISSUE_TEMPLATE/`](../../.github/ISSUE_TEMPLATE/) confirms the presence of 3 templates:

| Template File | Format | Issue Type | Governance Attributes |
| :--- | :--- | :--- | :--- |
| [`bug.yml`](../../.github/ISSUE_TEMPLATE/bug.yml) | GitHub Issue Form (YAML) | Bug Report (`labels: ["Bug"]`) | Prerequisites validation, affected version, environment preconditions (PHP, MySQL, OS, browser), reproduction steps, actual vs expected behavior, screenshots, context |
| [`bug_report.md`](../../.github/ISSUE_TEMPLATE/bug_report.md) | Legacy Markdown Template | Bug Report (`labels: bug`) | Freeform markdown bug report covering similar fields |
| [`feature_request.yml`](../../.github/ISSUE_TEMPLATE/feature_request.yml) | GitHub Issue Form (YAML) | Feature Request (`labels: ['Feature Request']`) | Problem description, proposed solution, alternatives considered, additional context |

### Observations and Recommendations

1. **Template Redundancy**: Having both `bug.yml` (modern form) and `bug_report.md` (legacy template) active causes duplicate bug reporting options in GitHub's issue chooser UI.
2. **Missing Structured Categories**: Structured issue forms do not yet exist for documentation updates, refactorings, or upstream synchronization tasks.
3. **Recommendation**: In a future governance maintenance phase, consolidate bug reporting by deprecating `bug_report.md` in favor of `bug.yml`, and consider structured forms for `docs.yml` and `refactor.yml`.
4. **Implementation Boundary**: This finding is a **governance recommendation only** (**`RECOMMENDED`**). No files in `.github/ISSUE_TEMPLATE/` are created, renamed, or deleted during Operational Stage O5.

---

## 11. GitHub Actions Governance

### Verified Workflow Files

Inspection of [`.github/workflows/`](../../.github/workflows/) confirms 4 active workflows:

| Workflow File | Triggers (`on:`) | Target Branches | Permissions | Purpose |
| :--- | :--- | :--- | :--- | :--- |
| [`pest_tests.yml`](../../.github/workflows/pest_tests.yml) | `push`, `pull_request` | `master`, `develop` | `contents: read` | Automated Pest v4 unit/feature tests across MySQL 8.0 & PostgreSQL 16 on PHP 8.3 |
| [`playwright_tests.yml`](../../.github/workflows/playwright_tests.yml) | `push`, `pull_request`, `merge_group`, `workflow_dispatch` | `master`, `develop` | `contents: read` | End-to-end browser testing with Playwright (6 shards) on MySQL & PostgreSQL |
| [`translations_check.yml`](../../.github/workflows/translations_check.yml) | `push`, `pull_request` | `master`, `develop` | `contents: read` | Translation consistency checks (`php artisan translations:check --details`) |
| [`docker_publish.yml`](../../.github/workflows/docker_publish.yml) | `push` (tags `v*`), `workflow_dispatch` | Tags matching `v*` | `contents: read` | Multi-architecture production Docker image build and publish to Docker Hub |

### Governance Analysis and CI Boundary

- **Concurrency Controls**: `pest_tests.yml`, `playwright_tests.yml`, and `translations_check.yml` configure `${{ github.workflow }}-${{ github.ref }}` concurrency groups with `cancel-in-progress: true` to conserve CI runner resources.
- **Least-Privilege Permissions**: All 4 workflows explicitly declare `contents: read`. This least-privilege permission is verified for these specific workflow files; it does not constitute an audit of the overall repository token settings.
- **Required Status Checks**: While workflows trigger on Pull Requests targeting `develop` and `master`, whether they are enforced as mandatory blocking status checks in GitHub rulesets is **`NOT VERIFIED`**.
- **Operational Stage Boundary Notice**: Formal CI gating, status check ruleset requirements, test matrix reliability, and test runner strategy belong strictly to **Operational Stage O6 (CI / Testing)**.

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

1. **Autonomous AI Agents**: "Autonomous AI Agent" is **NOT** a native GitHub permission role. AI coding agents operate strictly through the identity, credentials, SSH keys, or tokens under which they are executed. AI operating rules belong to [`AGENTS.md`](../../AGENTS.md) and [`docs/ai/`](../ai/).
2. **Urgent Fix & Release Branches**: The project intentionally has no `hotfix/*` or `release/*` branches. Urgent defects use the normal reviewed `fix/*` lifecycle from `develop`; releases are promoted to `master` from verified `develop` commits and tagged there. See [`git-workflow.md`](git-workflow.md#8-protected-branch-policy).

---

## 13. Governance Findings

The audit identified the following genuine, evidence-based governance findings:

### GOV-001: Direct Push Restriction on `develop` and `master`
- **Classification**: **`VERIFIED`**
- **Evidence**: Active GitHub rulesets `23566563` and `23566566` apply `pull_request` rules to `develop` and `master`; the effective-rules endpoint confirms both applications.
- **Impact**: Direct updates to either protected branch must use a Pull Request.
- **Recommended Action**: Re-verify ruleset state after any repository-administration change.
- **Owning Operational Stage**: O5 (`COMPLETE`).

### GOV-002: Solo-Maintainer PR Integrity Enforcement
- **Classification**: **`VERIFIED`**
- **Evidence**: Both active rulesets require a Pull Request and resolution of review threads, set required approving reviews to zero, disable latest-pusher approval, retain stale-review dismissal, and have no bypass actors.
- **Impact**: A solo maintainer can merge a self-reviewed, verified Pull Request without a second account, while direct pushes, branch deletion, force pushes, and unresolved conversations remain blocked.
- **Recommended Action**: Complete the template self-review and record verification for every Pull Request; obtain independent review when a higher-risk repository control requires it.
- **Owning Operational Stage**: O5 (`COMPLETE`).

### GOV-003: GitHub Repository Merge Button Configuration
- **Classification**: **`VERIFIED`** (repository settings) / **`POLICY`** (source-specific method selection)
- **Evidence**: GitHub API confirms Squash Merge and Merge Commits enabled, with Rebase Merge disabled. The distinction between a normal topic Pull Request and upstream synchronization remains a maintainer-enforced policy because both target `develop`.
- **Impact**: Rebase Merge cannot be selected; maintainers must select Squash Merge for normal topics and Merge Commits for upstream synchronization and release promotion.
- **Recommended Action**: Re-verify merge settings after repository-administration changes.
- **Owning Operational Stage**: O5 (`COMPLETE`).

### GOV-004: Issue Template Redundancy
- **Classification**: **`RECOMMENDED`**
- **Evidence**: `.github/ISSUE_TEMPLATE/` contains both `bug.yml` (YAML form) and `bug_report.md` (legacy Markdown).
- **Impact**: Causes duplicate bug report options in the GitHub issue chooser UI.
- **Recommended Action**: In a future governance maintenance stage, deprecate and remove legacy `bug_report.md` in favor of `bug.yml`. This remains a recommendation only; no template files are modified in Operational Stage O5.
- **Owning Operational Stage**: O5 (`PENDING AUTHORIZATION`).

### GOV-005: CODEOWNERS Configuration
- **Classification**: **`NOT CONFIGURED`**
- **Evidence**: No `CODEOWNERS` file exists in `.github/`, root, or `docs/`.
- **Impact**: Reviewers must be manually assigned on all Pull Requests.
- **Recommended Action**: Retain current unconfigured status for centralized maintainer model; plan modular ownership if team expands.
- **Owning Operational Stage**: O5 (`RECOMMENDED`).

### GOV-006: Automated CI Status Checks Gating
- **Classification**: **`DEFERRED`**
- **Evidence**: `.github/workflows/pest_tests.yml`, `playwright_tests.yml`, and `translations_check.yml` run on PRs, but ruleset gating is unverified.
- **Impact**: PRs could be merged even if automated test suites fail.
- **Recommended Action**: Formally codify required status checks during Operational Stage O6 CI governance.
- **Owning Operational Stage**: O6 (CI / Testing).

---

## 14. Deferred Governance

To preserve strict architectural boundaries across roadmap phases, the following governance areas are formally deferred:

| Deferred Control Area | Owning Operational Stage | Scope and Governance Responsibilities |
| :--- | :--- | :--- |
| **CI Gating & Required Status Checks** | **O6 — CI / Testing** | Codifying required status checks in GitHub rulesets; establishing test matrix reliability; configuring failure notifications and coverage thresholds. |
| **Upstream Synchronization Procedures** | **O7 — Upstream Integration** | Defining operational sync commands; vendor conflict resolution protocols; verification procedures for vendor alignment; tag synchronization. |
| **Living Knowledge Synchronization** | **O8 — Change Management & Knowledge Maintenance** | Reconciling upstream schema and feature modifications against the living documentation suite (`docs/`); updating architecture specs and ERDs. |

---

## 15. Evidence Matrix

| Area | Expected State | Actual State | Classification | Evidence Source | Operational Stage |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Default Branch** | `develop` | `develop` | **VERIFIED** | `git ls-remote --symref origin HEAD`, `git symbolic-ref refs/remotes/origin/HEAD` | O5 |
| **Branch Role: develop** | Primary integration branch | Primary integration branch | **VERIFIED** | `git ls-remote --symref origin HEAD`, `docs/development/git-workflow.md` | O5 |
| **Branch Role: master** | Stable release branch | Stable release branch; first release promotion pending | **POLICY** | `docs/development/git-workflow.md`, transition record | O4 / O5 |
| **Direct Push Restriction on develop & master** | Prohibited on `develop` & `master` | Active Pull Request rules block direct updates | **VERIFIED** | GitHub rulesets `23566563`, `23566566`; effective-rules API | O5 |
| **Pull Request Template** | Present | Present at `.github/PULL_REQUEST_TEMPLATE.md` | **VERIFIED** | File inspection `.github/PULL_REQUEST_TEMPLATE.md` | O5 |
| **Pull Request Requirement for protected branches** | Mandatory for `develop` and `master` | Active rulesets require Pull Requests | **VERIFIED** | GitHub rulesets `23566563`, `23566566`; effective-rules API | O5 |
| **Pull Request Review Requirement** | Self-review and recorded verification; no independent approval gate | Active rulesets require zero approvals, disable latest-push approval, and require conversation resolution | **VERIFIED** | GitHub rulesets `23566563`, `23566566` | O5 |
| **Topic Branch Merge Strategy** | Squash Merge | Established by policy; Squash Merge enabled and Rebase Merge disabled | **POLICY** | `docs/development/git-workflow.md`; GitHub repository settings | O5 |
| **Upstream Integration Merge Strategy** | Merge Commit (`--no-ff`) | Established by policy; observed in history | **POLICY** | `docs/development/git-workflow.md` Section 7 & 9, commits `15a76bf09`, `49e330b5e` | O5 / O7 |
| **Branch Protection / Rulesets** | Configured on GitHub | Two active repository rulesets protect `develop` and `master` | **VERIFIED** | GitHub rulesets `23566563`, `23566566`; effective-rules API | O5 |
| **CODEOWNERS** | Configured if needed | Absent across repository | **NOT CONFIGURED** | Inspected `.github/CODEOWNERS`, `CODEOWNERS`, `docs/CODEOWNERS` | O5 |
| **Issue Templates** | Form-based templates | `bug.yml`, `bug_report.md`, `feature_request.yml` present | **VERIFIED** | Inspected `.github/ISSUE_TEMPLATE/` directory | O5 |
| **GitHub Actions Workflows** | Active on PRs | 4 workflows active; permissions `contents: read` | **VERIFIED** | Inspected `.github/workflows/` directory | O5 |
| **Required Status Checks Gating** | Enforced gating | Workflows execute; gating deferred | **DEFERRED** | Inspected `.github/workflows/` directory | O6 |
| **Upstream Sync Procedure** | Documented procedure | Dual-remote topology active; execution deferred | **DEFERRED** | O4 docs, git remotes | O7 |

---

## 16. Verification Metadata

This governance specification is verified against active repository configuration, commit history, and remote tracking state.

- **Status**: `verified`
- **Source of Truth**: `repository-configuration`
- **Last Verified**: `2026-09-17`
- **Scope**: `repository-governance`
- **Confidence**: `high`
