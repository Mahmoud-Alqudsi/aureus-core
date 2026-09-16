---
status: verified
source_of_truth: repository-configuration
last_verified: 2026-09-15
scope: development-workflow
confidence: high
---

# Git & Development Operating Model

This document defines the canonical Git operating model and development workflow for Aureus ERP. It establishes deterministic branch management, commit conventions, merge strategies, and upstream synchronization roles for human engineers and autonomous AI coding agents.

---

## 1. Repository Topology

Aureus ERP operates across a dual-remote topology separating upstream open-source lineage from downstream private enterprise development.

```
+-------------------------------------------------------------+
|                          upstream                           |
|       (git@github.com:Mahmoud-Alqudsi/aureuserp.git)        |
|               Tracks upstream Webkul / Aureus               |
+-------------------------------------------------------------+
                              |
                              | periodic synchronization
                              v
+-------------------------------------------------------------+
|                           origin                            |
|        (git@github.com:Mahmoud-Alqudsi/aureus-core.git)     |
|               Primary development repository                |
+-------------------------------------------------------------+
```

### Remote Roles

| Remote | Repository URL | Role & Authority |
| :--- | :--- | :--- |
| `origin` | `git@github.com:Mahmoud-Alqudsi/aureus-core.git` | **Primary Development Repository**. All active development, feature branches, pull requests, and CI pipelines target `origin`. Daily engineering work is performed exclusively against this remote. |
| `upstream` | `git@github.com:Mahmoud-Alqudsi/aureuserp.git` | **Upstream Lineage Tracking**. Public fork used to track updates, bug fixes, and releases from upstream Aureus/Webkul. Serves as the synchronization source for `master`. |

---

## 2. Branch Model & Hierarchy

The branch model enforces strict hierarchical propagation from upstream baseline down to isolated topic branches:

```
upstream
   │
   │ synchronization
   ▼
master
   │
   │ integration
   ▼
develop
   │
   ├── feature/*
   ├── fix/*
   ├── refactor/*
   ├── docs/*
   └── chore/*
```

### Branch Roles

- **`master` — Upstream Synchronization Baseline**:
  `master` is **NOT** a production or deployment release branch in this repository. Its canonical role is the baseline tracking upstream synchronization and serving as the reference point for upstream-related integration and diff comparison.
- **`develop` — Primary Integration Branch**:
  `develop` is the default integration branch for daily development. All features, fixes, refactorings, documentation updates, and chores integrate into `develop`.
- **Topic Branches (`feature/*`, `fix/*`, `refactor/*`, `docs/*`, `chore/*`)**:
  Isolated, short-lived development branches dedicated to specific tasks.

### Intentional Branch Omissions

The project intentionally has no dedicated `hotfix/*` or `release/*` branch classes:

- Urgent defect work uses the normal `fix/*` lifecycle from `develop`; urgency does not authorize a direct push or bypass a Pull Request.
- A release is a verified commit already integrated into `develop`, identified by an approved version tag. It is not a separately maintained release branch.
- `master` remains exclusively the upstream synchronization baseline and must not be repurposed as a hotfix or release branch.

---

## 3. Branch Source and Target Rules

All normal development work follows a strict branch-and-PR lifecycle centered on `develop`:

```
develop
   │
   ├── feature/*
   ├── fix/*
   ├── refactor/*
   ├── docs/*
   └── chore/*
        │
        ▼
   Pull Request
        │
        ▼
     develop
```

### Rules of Engagement

1. **Source**: Normal topic branches must always branch off the latest `develop`.
2. **Target**: Normal topic branches must target `develop` as their pull request base.
3. **Prohibition of Direct Pushes**:
   - Direct pushes to `develop` are **strictly prohibited by project policy**.
   - Direct pushes to `master` are **strictly prohibited by project policy**.
   - All code changes must enter `develop` through reviewed Pull Requests.
4. **Urgent Fixes**: An urgent production or customer-impacting defect uses `fix/<description>` from the latest `develop` and targets `develop` through the same reviewed Pull Request lifecycle. A priority label or expedited review may change response time, but it does not change the branch topology or bypass verification.

> [!NOTE]
> Detailed GitHub-level branch protection rule enforcement and CI status check requirements are defined in Operational Stage O5. Upstream synchronization exceptions are defined in Operational Stage O7.

---

## 4. Branch Naming Convention

All topic branches must strictly adhere to the standardized prefix naming format.

### Canonical Format

```
<type>/<description>
```

### Allowed Types

| Type | Purpose | Example |
| :--- | :--- | :--- |
| `feature` | Introducing new functionality or capabilities | `feature/accounting-reports` |
| `fix` | Correcting bugs, errors, or unintended behaviors | `fix/inventory-calculation` |
| `refactor` | Code restructuring without altering external behavior | `refactor/plugin-service-providers` |
| `docs` | Documentation additions, corrections, or updates | `docs/api-documentation` |
| `chore` | Maintenance tasks, dependency updates, tooling adjustments | `chore/update-dependencies` |

`hotfix/*` and `release/*` are intentionally not allowed branch types. See [Protected-Branch Policy](#8-protected-branch-policy) for urgent-fix and release handling.

### Naming Constraints

- **Lowercase only**: All characters must be lowercase.
- **Kebab-case descriptions**: Separate multiple words in `<description>` with hyphens (`-`).
- **No spaces or special characters**: Spaces, underscores, and punctuation marks (other than the single forward slash `/` separator and hyphens) are prohibited.
- **Meaningful and descriptive**: The description must clearly communicate the scope of work.
- **No generic names**: Names such as `temp`, `test`, `wip`, `patch`, or `dev` are forbidden.
- **Prefix mandatory**: No branch may exist without an approved type prefix.

---

## 5. Branch Lifetime Guidance

Branch lifetimes should remain minimal to facilitate continuous integration, minimize merge conflicts, and keep review scopes manageable.

| Branch Type | Expected Lifetime Guidance | Typical Objective |
| :--- | :--- | :--- |
| `feature/*` | Days to weeks | Scoped feature implementation with unit/feature tests |
| `fix/*` | Hours to days | Targeted bug resolution with regression test |
| `refactor/*` | Days | Internal code reorganization and cleanup |
| `docs/*` | Days | Documentation updates and architectural guides |
| `chore/*` | Hours to days | Dependency updates, tooling, and routine maintenance |

> [!TIP]
> This guidance represents operational best practice rather than an automated SLA. Long-lived topic branches should be broken down into smaller, incremental changes.

---

## 6. Commit Convention

Aureus ERP follows the [Conventional Commits](https://www.conventionalcommits.org/) standard across all contributions.

### Canonical Format

```
<type>(<scope>): <description>

[optional body]

[optional footer(s)]
```

### Allowed Types

| Type | Description |
| :--- | :--- |
| `feat` | New user-facing or domain feature |
| `fix` | Bug fix or behavioral defect correction |
| `refactor` | Code change that neither fixes a bug nor adds a feature |
| `docs` | Documentation changes only |
| `chore` | Changes to build process, auxiliary tools, dependencies, or repository hygiene |
| `test` | Adding missing tests or correcting existing tests |
| `style` | Formatting, whitespace, or code style adjustments without logic changes |

### Scope Conventions

The scope identifies the affected repository component or domain:
- **Plugin slug**: e.g., `accounts`, `inventories`, `purchases`, `support`
- **Application core**: `app`
- **Configuration**: `config`
- **CI / Workflows**: `ci`
- **Omitted**: Permitted for cross-cutting changes spanning multiple domains

### Description Rules

- **Imperative mood**: Use imperative/present-oriented phrasing (`add`, `fix`, `update`, not `added`, `fixes`, `updating`).
- **Lowercase**: Begin the description with a lowercase letter.
- **Concise**: Succinctly summarize the change.
- **No trailing punctuation**: Do not end the description with a period (`.`).

### Footers

Optional footers may be supplied for tracking or breaking change declarations:
```
Closes #123
BREAKING CHANGE: The `CompanyContext` service constructor now requires a tenant resolver instance.
```

---

## 7. Merge Strategy

The repository applies distinct merge strategies depending on whether changes originate from normal internal development or upstream synchronization.

### Normal Topic Branches to `develop`

- **Strategy**: **Squash Merge**
- **Applies to**: All normal topic branches (`feature/*`, `fix/*`, `refactor/*`, `docs/*`, `chore/*`).
- **Objective**: Maintain a clean, linear history on `develop` where each pull request corresponds to exactly one atomic, descriptive commit.
- **No Commit Thresholds**: Squash Merge is used regardless of commit count or branch size. Commit counts (such as "≤5 commits") do not alter this strategy.

### Upstream Integration

- **Strategy**: **Merge Commits** (`--no-ff`)
- **Applies to**: Synchronizing changes from `upstream/master` into `master`, and integrating `master` into `develop`.
- **Objective**: Preserve upstream lineage and commit history.

### Shared Branch Protection

- **Do Not Rebase Shared Branches**: Rebasing or rewriting history on shared branches (`master`, `develop`, or collaborative integration branches) is strictly prohibited.
- History rewriting is restricted to local, unpushed topic branches before submitting for review.

---

## 8. Protected-Branch Policy

The repository operating model defines baseline protections for core integration branches:

```
master   ───► Protected by Policy: Direct push prohibited.
develop  ───► Protected by Policy: Direct push prohibited.
```

1. **`master` Protection**:
   - Direct pushes to `master` are prohibited.
   - Updates occur solely via upstream synchronization procedures.
2. **`develop` Protection**:
   - Direct pushes to `develop` are prohibited.
   - All code, documentation, and configuration changes must arrive via Pull Request.
   - Changes to `develop` require a reviewed Pull Request.

> [!IMPORTANT]
> This section outlines the normative project policy. GitHub-level enforcement mechanisms (such as branch protection rules, required reviews, and automated CI gates) belong to Operational Stage O5.

### Urgent-Fix Handling

1. Create `fix/<description>` from the latest `develop`.
2. Keep the change narrowly scoped, including a regression test when the defect is testable.
3. Open a Pull Request to `develop`, identifying urgency, affected components, verification performed, and any customer or security impact.
4. Use the normal squash-merge policy after the required review. No emergency path permits direct pushes, rebases of shared branches, or a merge to `master`.

### Release and Version-Tag Policy

1. A release candidate must already be a verified commit reachable from `develop`; a release branch is not created.
2. The release Pull Request must include the applicable project changelog update and record verification results before a tag is considered.
3. Stable releases use immutable Semantic Version tags in the form `v<major>.<minor>.<patch>`. Pre-releases append a hyphenated label, for example `v1.6.0-rc.1`.
4. Creating or pushing a `v*` tag to `origin` requires explicit human authorization. The `docker_publish.yml` workflow publishes a Docker image for every pushed `v*` tag; a stable tag on the default branch may also update the `latest` image tag.
5. Do not move, delete, or reuse published release tags. Correct a released defect with a new `fix/*` Pull Request and a new version tag.
6. Upstream version tags are not release candidates for `origin` and must not be pushed automatically. Their synchronization is governed by Operational Stage O7.

---

## 9. Upstream Relationship & Synchronization Boundary

The repository maintains an active relationship with the upstream open-source Aureus/Webkul codebase:

1. Upstream updates from `upstream` are synchronized into `master`.
2. Once validated on `master`, updates are merged into `develop`.
3. Topic branches incorporate upstream changes via `develop`.

### Scope Boundary Notice

This document establishes the high-level topological roles and branch relationships. Detailed operational workflows for upstream synchronization—including merge procedures, conflict resolution protocols, and automation scripts—belong strictly to **Operational Stage O7 (Upstream Synchronization & Merge Strategy)**. No upstream synchronization actions or procedures should be executed or assumed outside Operational Stage O7.
