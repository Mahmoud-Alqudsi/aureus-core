---
status: verified
source_of_truth: repository-configuration
last_verified: 2026-09-17
scope: development-workflow
confidence: high
---

# Git & Development Operating Model

This document defines the canonical Git operating model and development workflow for Aureus ERP. It establishes deterministic branch management, commit conventions, merge strategies, and upstream synchronization roles for human engineers and autonomous AI coding agents.

---

## 1. Repository Topology

Aureus ERP operates across a dual-remote topology separating upstream open-source lineage from downstream public enterprise development.

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
| `upstream` | `git@github.com:Mahmoud-Alqudsi/aureuserp.git` | **Upstream Lineage Tracking**. Public fork used to track updates, bug fixes, and releases from upstream Aureus/Webkul. Serves as the synchronization source for `develop`. |

---

## 2. Branch Model & Hierarchy

The branch model separates incoming upstream integration from stable downstream releases:

```
upstream
   │
   │ synchronization
   ▼
develop
   │
   ├── feature/*
   ├── fix/*
   ├── refactor/*
   ├── docs/*
   └── chore/*
   │
   │ verified release Pull Request
   ▼
master
```

### Branch Roles

- **`master` — Stable Release Branch**:
  `master` contains only verified releases of Aureus ERP. It is not the default branch and it is never updated directly; its normal source is a verified Pull Request from `develop`. Immutable downstream version tags are created from verified commits on this branch only.
- **`develop` — Primary Integration Branch**:
  `develop` is the default integration branch for daily development. All features, fixes, refactorings, documentation updates, and chores integrate into `develop`.
- **Topic Branches (`feature/*`, `fix/*`, `refactor/*`, `docs/*`, `chore/*`)**:
  Isolated, short-lived development branches dedicated to specific tasks.

### Intentional Branch Omissions

The project intentionally has no dedicated `hotfix/*` or `release/*` branch classes:

- Urgent defect work uses the normal `fix/*` lifecycle from `develop`; urgency does not authorize a direct push or bypass a Pull Request.
- A release is promoted from a verified `develop` commit through a Pull Request to `master`, then identified by an approved immutable version tag. A separately maintained `release/*` branch is not needed.
- `master` is the release branch; it must not be repurposed as an unreviewed development or raw upstream-integration branch.

---

## 3. Work Item, Branch & Pull Request Lifecycle

Aureus uses GitHub Issues as the tracked work-item layer when a change requires planning, discussion, traceability, or explicit follow-up. An Issue is not a substitute for a branch, commit, or Pull Request; each serves a different purpose.

### Canonical Relationship

```text
Issue (why / what)
   │
   ▼
Topic Branch (where the work is isolated)
   │
   ▼
Commits (what changed)
   │
   ▼
Pull Request (request to review and integrate)
   │
   ▼
Review + CI + verification
   │
   ▼
Merge
   │
   ▼
Issue closed when the tracked work is complete
```

### When an Issue Is Required

For the project workflow, create or reuse an Issue before implementation when the work is any of the following:

- a new feature or externally visible behavior change;
- a bug or regression requiring investigation or a regression test;
- a security, authorization, company-isolation, or data-integrity change;
- a schema, dependency, plugin-lifecycle, or cross-plugin change;
- an upstream synchronization, recovery, or other multi-step repository operation;
- a material documentation or governance change that needs planning, discussion, or traceability;
- a task large enough to require multiple commits, contributors, or follow-up work.

A separate Issue is not required for a trivial, self-contained correction such as a spelling/formatting fix or an obvious one-line documentation correction, unless traceability is otherwise required by the maintainer or change risk.

### Issue Responsibilities

An Issue should describe the problem, requested outcome, relevant context, and acceptance criteria where useful. It is the place to track scope and discussion; implementation details belong in the branch and Pull Request.

### Branch and PR Linkage

- Create the topic branch from the latest `develop` after the work item is understood and scoped.
- Use a branch name that reflects the change type and scope, following Section 5.
- Link the Pull Request to the Issue using GitHub closing keywords such as `Closes #123`, `Fixes #123`, or `Resolves #123` when the PR completes that Issue.
- If one PR only partially addresses an Issue, reference the Issue without a closing keyword and leave the Issue open until the remaining work is complete.
- A PR may address multiple Issues when the changes are intentionally part of one coherent review; link each relevant Issue explicitly.

### Lifecycle Summary

```text
Request
  ↓
Issue (when tracked work is required)
  ↓
Understand / Discover / Assess / Plan
  ↓
Create topic branch
  ↓
Implement + test + commit
  ↓
Open Pull Request
  ↓
Self-review + CI + verification
  ↓
Review / changes if needed
  ↓
Merge into protected target
  ↓
Close completed Issue / record follow-up work
```

This lifecycle complements the implementation procedure in `AGENTS.md`; it does not replace the repository's source-of-truth or authorization rules.

---

## 4. Branch Source and Target Rules

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
3. **Upstream Synchronization Exception**: An upstream synchronization starts from the latest `develop` in a short-lived `chore/upstream-sync-<date>` branch and targets `develop`. It preserves upstream history with a Merge Commit. A history-preserving upstream recovery uses `chore/upstream-sync-rollback-<date>` from the affected protected target.
4. **Release Promotion Exception**: A verified release is promoted by a Pull Request with `develop` as its source and `master` as its target. No general topic branch may target `master`.
5. **Prohibition of Direct Pushes**:
   - Direct pushes to `develop` are **strictly prohibited by project policy**.
   - Direct pushes to `master` are **strictly prohibited by project policy**.
   - All code, documentation, configuration, and upstream synchronization changes must enter protected branches through self-reviewed Pull Requests with recorded verification.
6. **Urgent Fixes**: An urgent production or customer-impacting defect uses `fix/<description>` from the latest `develop` and targets `develop` through the same self-reviewed Pull Request lifecycle. If it must be released immediately, its verified `develop` commit is then promoted to `master` through the release Pull Request. A priority label or expedited review never authorizes a direct push or bypass.

> [!NOTE]
> The active solo-maintainer rulesets require a Pull Request but zero approving reviews. Self-review and recorded verification are therefore mandatory for every Pull Request; independent review remains mandatory when another repository control or the change risk requires it.

> [!NOTE]
> Detailed GitHub-level branch protection rule enforcement and CI status check requirements are defined in Operational Stage O5. Upstream synchronization exceptions and operational runbooks are documented in [`docs/development/upstream-sync.md`](upstream-sync.md).

---

## 5. Branch Naming Convention

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

`hotfix/*` and `release/*` are intentionally not allowed branch types. See [Protected-Branch Policy](#9-protected-branch-policy) for urgent-fix and release handling.

`chore/upstream-sync-<date>` is a narrowly scoped exception: it branches from and targets `develop`, and exists only for an authorized upstream synchronization. `chore/upstream-sync-rollback-<date>` branches from the affected protected target and exists only for history-preserving recovery. Neither convention authorizes a general chore to target `master`.

### Naming Constraints

- **Lowercase only**: All characters must be lowercase.
- **Kebab-case descriptions**: Separate multiple words in `<description>` with hyphens (`-`).
- **No spaces or special characters**: Spaces, underscores, and punctuation marks (other than the single forward slash `/` separator and hyphens) are prohibited.
- **Meaningful and descriptive**: The description must clearly communicate the scope of work.
- **No generic names**: Names such as `temp`, `test`, `wip`, `patch`, or `dev` are forbidden.
- **Prefix mandatory**: No branch may exist without an approved type prefix.

---

## 6. Branch Lifetime Guidance

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

## 7. Commit Convention

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

## 8. Merge Strategy

The repository applies distinct merge strategies depending on whether changes originate from normal internal development or upstream synchronization.

### Normal Topic Branches to `develop`

- **Strategy**: **Squash Merge**
- **Applies to**: All normal topic branches (`feature/*`, `fix/*`, `refactor/*`, `docs/*`, `chore/*`).
- **Objective**: Maintain a clean, linear history on `develop` where each pull request corresponds to exactly one atomic, descriptive commit.
- **No Commit Thresholds**: Squash Merge is used regardless of commit count or branch size. Commit counts (such as "≤5 commits") do not alter this strategy.

### Upstream Integration and Release Promotion

- **Strategy**: **Merge Commits** (`--no-ff`)
- **Applies to**: Synchronizing `upstream/master` into `develop`, and promoting verified `develop` releases into `master`.
- **Objective**: Preserve upstream lineage and make every release boundary explicit in history.

### Shared Branch Protection

- **Do Not Rebase Shared Branches**: Rebasing or rewriting history on shared branches (`master`, `develop`, or collaborative integration branches) is strictly prohibited.
- History rewriting is restricted to local, unpushed topic branches before submitting for review.

---

## 9. Protected-Branch Policy

The repository operating model defines baseline protections for core integration branches:

```
master   ───► Protected by Policy: Direct push prohibited.
develop  ───► Protected by Policy: Direct push prohibited.
```

1. **`master` Protection**:
   - Direct pushes to `master` are prohibited.
   - Normal updates occur only through an authorized, self-reviewed release Pull Request from `develop` using a Merge Commit.
   - A history-preserving rollback or explicitly authorized emergency upstream security fix may target `master`; either must be promoted back to `develop` immediately after merge.
2. **`develop` Protection**:
   - Direct pushes to `develop` are prohibited.
   - All code, documentation, and configuration changes must arrive via Pull Request.
   - Changes to `develop` require a self-reviewed Pull Request with recorded verification.
   - An upstream synchronization enters through an authorized, self-reviewed `chore/upstream-sync-<date>` Pull Request and uses a Merge Commit.

> [!IMPORTANT]
> This section outlines the normative project policy. GitHub-level enforcement mechanisms (such as branch protection rules, required reviews, and automated CI gates) belong to Operational Stage O5.

### Urgent-Fix Handling

1. Create `fix/<description>` from the latest `develop`.
2. Keep the change narrowly scoped, including a regression test when the defect is testable.
3. Open a Pull Request to `develop`, identifying urgency, affected components, verification performed, and any customer or security impact.
4. Use the normal squash-merge policy after self-review and required verification. No emergency path permits direct pushes, rebases of shared branches, or a merge to `master`.

### Release and Version-Tag Policy

1. A release candidate must already be a verified commit reachable from `develop`; a release branch is not created.
2. The release Pull Request must have `develop` as its source and `master` as its target, include the applicable project changelog update, and record verification results before a tag is considered.
3. Stable releases use immutable Semantic Version tags in the form `v<major>.<minor>.<patch>`. Pre-releases append a hyphenated label, for example `v1.6.0-rc.1`.
4. Creating or pushing a `v*` tag from the verified release commit on `master` requires explicit human authorization. The `docker_publish.yml` workflow publishes a Docker image for every pushed `v*` tag; a stable tag on the default branch may also update the `latest` image tag.
5. Do not move, delete, or reuse published release tags. Correct a released defect with a new `fix/*` Pull Request and a new version tag.
6. Upstream version tags are not release candidates for `origin` and must not be pushed automatically. The upstream synchronization safety procedure is defined in [`upstream-sync.md`](upstream-sync.md).

---

## 10. Upstream Relationship & Synchronization Boundary

The repository maintains an active relationship with the upstream open-source Aureus/Webkul codebase:

1. Upstream updates from `upstream` are synchronized into `develop`.
2. After validation, a selected verified `develop` state is promoted to `master` as a release.
3. Topic branches incorporate upstream changes via `develop`.

### Transition State

This policy applies to all future integrations. The transition becomes operationally complete only when the first verified `develop`-to-`master` release Pull Request is merged and tagged. Existing `master` history is preserved: it must not be reset, force-pushed, or treated as a release merely because this policy changed. Any local or remote divergence must be audited and resolved through protected-branch Pull Requests before that first promotion.

### Scope Boundary Notice

This document establishes the high-level topological roles and branch relationships. Detailed operational workflows for upstream synchronization—including step-by-step merge procedures, safety checkpoints, conflict resolution protocols, and rollback strategies—are canonically defined in [`docs/development/upstream-sync.md`](upstream-sync.md). No upstream synchronization actions or procedures should be executed or assumed outside this documented runbook.
