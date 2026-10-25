---
status: verified
source_of_truth: repository-configuration
last_verified: 2026-10-25
scope: repository-governance
confidence: high
---

# GitHub Governance

This document defines the canonical GitHub-level repository governance policy, server-side rulesets, Pull Request controls, and issue-template conventions for Aureus ERP.

---

## 1. Purpose & Ownership Boundary

While [`docs/development/git-workflow.md`](git-workflow.md) owns local branch hierarchy, naming conventions, commit formatting, and merge/release lifecycles, and [`docs/development/ci-testing-governance.md`](ci-testing-governance.md) owns GitHub Actions workflows and test matrices, this document owns the **GitHub platform administration layer**:

- **Default Branch Configuration**: Server-side default branch (`develop`) vs. release branch (`master`).
- **Repository Rulesets & Branch Protection**: Server-side rulesets (`23566563` and `23566566`), direct-push/force-push/deletion blocks, and required status check bindings.
- **Repository Merge Button Settings**: Allowed merge methods on GitHub (`Squash Merge` and `Merge Commits` enabled; `Rebase Merge` disabled).
- **Pull Request & Review Governance**: Solo-maintainer review rules, conversation-resolution requirements, and [`.github/PULL_REQUEST_TEMPLATE.md`](../../.github/PULL_REQUEST_TEMPLATE.md).
- **Issue Templates & `CODEOWNERS`**: Structured forms in [`.github/ISSUE_TEMPLATE/`](../../.github/ISSUE_TEMPLATE/) and repository code-ownership configuration.

Modifying GitHub server-side repository settings, branch protection rules, or rulesets without explicit human authorization is strictly prohibited.

---

## 2. Default Branch & Remote Governance

The dual-remote topology (`origin` at `Mahmoud-Alqudsi/aureus-core` and `upstream` at `Mahmoud-Alqudsi/aureuserp`) and branch roles (`develop`, `master`, and topic branches) are canonically defined in [`docs/development/git-workflow.md`](git-workflow.md#1-repository-topology).

On the `origin` GitHub repository:

```
Canonical Default Branch: develop
Stable Release Branch:    master
```

| Inspection Level | Verified State | Direct Evidence | Status |
| :--- | :--- | :--- | :--- |
| **GitHub Server-Side Default Branch** | `develop` | `git ls-remote --symref origin HEAD` → `ref: refs/heads/develop HEAD` | `[VERIFIED]` |
| **Local Remote Tracking HEAD** | `refs/remotes/origin/develop` | `git symbolic-ref refs/remotes/origin/HEAD` → `refs/remotes/origin/develop` | `[VERIFIED]` |

All new Pull Requests created on GitHub without an explicit base parameter automatically target `develop`. `master` receives only verified release promotions from `develop`.

---

## 3. Pull Request & Review Governance

### Pull Request Entry & Targeting

- **Mandatory PR Entry**: Every change entering `develop` or `master` must arrive via Pull Request. Direct pushes to `develop` or `master` are blocked by active server-side rulesets (`[VERIFIED]`).
- **Target Branch Rules**: Normal topic branches and `chore/upstream-sync-<date>` synchronization branches target `develop`. Verified release promotions target `master` from `develop`. Full branch source/target rules and merge semantics are defined in [`docs/development/git-workflow.md`](git-workflow.md#4-branch-source-and-target-rules).

### Pull Request Template

Every Pull Request must populate all sections of [`.github/PULL_REQUEST_TEMPLATE.md`](../../.github/PULL_REQUEST_TEMPLATE.md):
- **Description**: Clear explanation of change context and purpose.
- **Related Issue**: Traceability link (`Closes #123`, `Fixes #123`, or `Resolves #123` when completing an Issue, or a non-closing reference for partial work, per [`git-workflow.md`](git-workflow.md#3-work-item-branch--pull-request-lifecycle)).
- **Type of Change**: Categorization (`Bug fix`, `New feature`, `Breaking change`, `Documentation update`, `Refactor`, `Tests`).
- **Self-Review & Pre-Merge Checklist**: Coding standards, self-review, documentation updates, and local test execution.
- **Testing Details & Documentation Impact**: Empirical verification evidence and explicit list of updated documents (or justification when no maintained document is affected).

### Solo-Maintainer Review Policy

- **Author Self-Review**: The Pull Request author must complete the template self-review and record proportionate verification before merging into `develop` or `master`.
- **Server-Side Review Configuration (`[VERIFIED]`)**: Both active rulesets require a Pull Request and conversation resolution (`required_review_thread_resolution`), set required approving reviews to **zero** (`required_approving_review_count: 0`), and disable `require_last_push_approval`. This allows the solo maintainer to merge verified Pull Requests without a second GitHub account while still blocking direct pushes, force pushes, branch deletion, and unresolved review threads. Independent review remains mandatory whenever another repository control or change risk requires it.

---

## 4. Active GitHub Rulesets & Merge Settings

Inspection of `.github/` confirms that rulesets are configured server-side on GitHub rather than via declarative files in the repository. Both active rulesets have **zero bypass actors**:

| Ruleset Name | Ruleset ID | Target Ref | Enforcement | Enforced Server-Side Controls (`[VERIFIED]`) |
| :--- | :--- | :--- | :--- | :--- |
| `protect-develop` | `23566563` | `refs/heads/develop` | `active` | Require Pull Request; `0` required approvals; dismiss stale reviews; require conversation resolution; block branch deletion (`deletion`); block force pushes (`non_fast_forward`); no bypass actors; strictly require the 4 CI status checks below. |
| `protect-release-master` | `23566566` | `refs/heads/master` | `active` | Require Pull Request; `0` required approvals; dismiss stale reviews; require conversation resolution; block branch deletion (`deletion`); block force pushes (`non_fast_forward`); no bypass actors; strictly require the 4 CI status checks below. |

### Required Status Checks Bound in Both Rulesets

Both rulesets (`23566563` and `23566566`) strictly require the following 4 status check contexts from GitHub Actions integration `15368`:

1. `PHP 8.3 | MySQL test on ubuntu-latest`
2. `PHP 8.3 | PostgreSQL test on ubuntu-latest`
3. `Check translation files consistency`
4. `Playwright E2E Gate`

Detailed workflow specifications, trigger matrices, and status-check architecture are maintained exclusively in [`docs/development/ci-testing-governance.md`](ci-testing-governance.md#18-github-status-check-governance).

### Repository Merge Button Settings

- **Enabled on GitHub (`[VERIFIED]`)**: **Squash Merge** and **Merge Commits**.
- **Disabled on GitHub (`[VERIFIED]`)**: **Rebase Merge**.
- **Maintainer Selection Rule**: Because GitHub repository settings apply per repository rather than per source-branch pattern, the maintainer selects **Squash Merge** for normal topic branches (`feature/*`, `fix/*`, `refactor/*`, `docs/*`, `chore/*`) and **Merge Commit** for `chore/upstream-sync-*` and `develop`-to-`master` release promotions, as specified in [`docs/development/git-workflow.md`](git-workflow.md#8-merge-strategy).

---

## 5. Issue Templates & `CODEOWNERS`

### Audited Issue Templates

Inspection of [`.github/ISSUE_TEMPLATE/`](../../.github/ISSUE_TEMPLATE/) confirms 3 template files:

| Template File | Format | Issue Type | Governance Role |
| :--- | :--- | :--- | :--- |
| [`bug.yml`](../../.github/ISSUE_TEMPLATE/bug.yml) | GitHub Issue Form (YAML) | Bug Report (`labels: ["Bug"]`) | Canonical structured defect form (environment, preconditions, reproduction steps, expected vs. actual behavior). Required for AI agents per `AGENTS.md`. |
| [`feature_request.yml`](../../.github/ISSUE_TEMPLATE/feature_request.yml) | GitHub Issue Form (YAML) | Feature Request (`labels: ['Feature Request']`) | Canonical structured proposal form (problem statement, proposed solution, alternatives considered, context). Required for AI agents per `AGENTS.md`. |
| [`bug_report.md`](../../.github/ISSUE_TEMPLATE/bug_report.md) | Legacy Markdown Template | Bug Report (`labels: bug`) | Legacy freeform markdown template retained in `.github/ISSUE_TEMPLATE/`. |

### `CODEOWNERS` Status

- Inspected `.github/CODEOWNERS`, `CODEOWNERS`, and `docs/CODEOWNERS`: **Absent** (`[VERIFIED]`).
- Under the current solo-maintainer operating model, path-based `CODEOWNERS` review gates are intentionally not configured.

---

## 6. Active Controls vs. Optional Future Governance Enhancements

To distinguish between controls that are active and enforced today versus optional enhancements that may be adopted if the contributor model scales:

| Governance Area | Current Active State (`[VERIFIED]`) | Optional Future Enhancement |
| :--- | :--- | :--- |
| **Protected Branches (`develop`, `master`)** | Enforced via active rulesets `23566563` and `23566566` (PR required, deletion/force-push blocked, no bypass actors). | Require `>=1` independent approving review if the project transitions from a solo maintainer to a multi-maintainer team. |
| **Required CI Status Checks** | 4 status checks (`MySQL Pest`, `PostgreSQL Pest`, `Translations`, `Playwright E2E Gate`) strictly enforced on `develop` and `master`. | Add a dedicated `Laravel Pint` or `kb_lint.py` status check context if server-side style/documentation gating is desired in CI. |
| **Merge Queue** | Disabled (`merge_group` is present only in `playwright_tests.yml`). | Enable GitHub Merge Queue only after adding `merge_group` triggers to `pest_tests.yml` and `translations_check.yml`. |
| **Code Ownership (`CODEOWNERS`)** | Not configured; all PRs are reviewed by the solo maintainer. | Introduce `.github/CODEOWNERS` mapping domain plugins (e.g. `accounts`, `security`) to domain owners if team size expands. |
| **Issue Templates** | `bug.yml`, `feature_request.yml`, and legacy `bug_report.md` present; `AGENTS.md` mandates `bug.yml` and `feature_request.yml`. | Optionally retire `bug_report.md` or add dedicated `docs.yml` / `refactor.yml` issue forms in a future template cleanup. |
| **AI Agent Identity** | AI agents operate strictly under the human maintainer's credentials and the constraints in `AGENTS.md` (not a native GitHub role). | If dedicated bot accounts or GitHub Apps are introduced, scope their token permissions to least privilege without ruleset bypass rights. |
