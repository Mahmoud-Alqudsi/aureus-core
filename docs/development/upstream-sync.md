---
status: verified
source_of_truth: repository-configuration
last_verified: 2026-09-17
scope: upstream-synchronization
confidence: high
---

# Upstream Synchronization Runbook

This document defines the canonical operational procedure for synchronizing upstream changes (`upstream` — Webkul open-source repository) into the public development repository (`origin` — Aureus ERP). It operationalizes the governance boundaries established in [`git-workflow.md`](git-workflow.md), [`github-governance.md`](github-governance.md), and [`ci-testing-governance.md`](ci-testing-governance.md).

> [!IMPORTANT]
> **Operational Runbook Authority & Boundaries**:
> 1. This document defines **how an authorized human or supervised agent executes synchronization**.
> 2. Documenting these commands **does not authorize their autonomous execution**.
> 3. Upstream synchronization must **never** be executed as an implicit background task.
> 4. The canonical upstream topology flows through `develop`; release promotion is a separate protected-branch Pull Request:
>    $$\text{upstream/master} \xrightarrow[\text{--no-ff}]{\text{Merge}} \text{chore/upstream-sync-*} \xrightarrow[\text{Merge Commit}]{\text{PR}} \text{develop} \xrightarrow[\text{Merge Commit}]{\text{release PR}} \text{master}$$

---

## 1. Roles & AI Agent Authorization Model

All commands in this runbook are tagged with an authorization class. Autonomous AI agents must adhere strictly to these operational boundaries:

| Class | Operation Type | Permitted Scope | Representative Commands |
|:---:|:---|:---|:---|
| **[A]** | **Read-Only Inspection** | Unrestricted during audit and validation phases | `git status`, `git branch -vv`, `git log`, `git rev-list`, `git diff` |
| **[B]** | **Non-Destructive State-Changing** | Requires explicit authorization in audit-only tasks (modifies `refs/remotes/*`) | `git fetch upstream`, `git fetch origin` |
| **[C]** | **History-Changing / Integration** | Requires explicit human approval and an approved plan | `git merge`, `git cherry-pick`, `git rebase`, `git reset`, `git revert` |
| **[D]** | **Remote Mutation** | Requires explicit human authorization after full validation; pushing a reviewed topic branch is permitted, pushing `master` or `develop` directly is not | `git push origin chore/upstream-sync-<date>` |
| **[E]** | **Destructive / History Rewriting** | **Emergency only**; requires explicit written human authorization and a deliberately temporary ruleset change | `git reset --hard`, `git push --force-with-lease` (never against a protected branch while its ruleset is active) |

---

## 2. Safety Preconditions & Three-Layer Preflight

Before any synchronization procedure begins, the operator must verify three independent layers of repository safety:

```
┌────────────────────────────────────────────────────────────────────────┐
│                     Three-Layer Preflight Safety                       │
├─────────┬─────────────────────────────┬────────────────────────────────┤
│ Layer 1 │ Working-Tree Cleanliness    │ git status --porcelain         │
│ Layer 2 │ Local/Remote Pointer Match  │ git rev-parse comparison       │
│ Layer 3 │ Authoritative Divergence    │ git rev-list --left-right      │
└─────────┴─────────────────────────────┴────────────────────────────────┘
```

### Layer 1: Working-Tree Cleanliness
*Proves no uncommitted or untracked changes exist in the working directory.*
```bash
# [A] Must return completely empty
git status --porcelain
```
> [!WARNING]
> A clean working tree proves only that no tracked/untracked changes exist locally. It does **not** prove branch synchronization, remote currency, or lack of divergence.

### Layer 2: Local/Remote Pointer Equality
*Confirms local and remote tracking pointers match.*
```bash
# [A] Verify master pointer match
git rev-parse master
git rev-parse origin/master

# [A] Verify develop pointer match
git rev-parse develop
git rev-parse origin/develop

# [A] Check unpushed commits (one-directional check)
git log origin/master..master
git log origin/develop..develop
```

### Layer 3: Authoritative Divergence Check
*Authoritative symmetric verification that local and remote tracking branches are identical.*
```bash
# [A] Must return strictly "0 0" for master
git rev-list --left-right --count master...origin/master

# [A] Must return strictly "0 0" for develop
git rev-list --left-right --count develop...origin/develop
```
*Stop Condition*: If either count is non-zero, synchronization must stop immediately until the divergence is investigated and resolved.

---

## 3. Pre-Sync Audit & Repository Baseline

The operator must inspect the active remotes and record the audit baseline before fetching or merging:

```bash
# [A] Verify remotes
git remote -v

# [A] Inspect remote-tracking references and current commit hashes
git rev-parse origin/master
git rev-parse origin/develop
git rev-parse upstream/master

# [A] Inspect divergence between origin/develop and upstream/master
git merge-base origin/develop upstream/master
git rev-list --left-right --count origin/develop...upstream/master
```

### Historical Repository Baseline (Pre-Transition Audit Record)
- **`origin`**: `git@github.com:Mahmoud-Alqudsi/aureus-core.git` (Public development repo).
- **`upstream`**: `git@github.com:Mahmoud-Alqudsi/aureuserp.git` (Public fork tracking Webkul).
- **`origin/master` historical baseline**: Commit `49e330b5e` ("Merge remote-tracking branch 'upstream/master'").
- **`upstream/master` baseline**: Commit `9ece7f023` (Webkul v1.6.0 + PR patches #1543–#1559).
- **Historical baseline divergence**: `10 95` (10 downstream-only commits, 95 upstream commits ahead).
- **`backup/pre-upstream-sync` reference**: Pre-existing local branch pointing to `49e330b5e`.
- **`origin/develop`**: Commit `76aa5f9a6` (incorporates `49e330b5e` via merge commit `15a76bf09`).

---

## 4. Checkpoint Creation

To prevent accidental data loss, create an immutable, uniquely identifiable checkpoint before executing any state-changing operations:

```bash
# [A] 1. Check if proposed checkpoint name already exists
git show-ref --verify refs/heads/checkpoint/pre-sync-$(date +%Y%m%d)

# [C] 2. Create an immutable checkpoint branch from current origin/develop
git branch checkpoint/pre-sync-$(date +%Y%m%d)-$(git rev-parse --short origin/develop) origin/develop

# [A] 3. Record the checkpoint commit hash in the execution log
git rev-parse checkpoint/pre-sync-$(date +%Y%m%d)-$(git rev-parse --short origin/develop)
```

> [!CAUTION]
> **No Forced Branch Movement**: Never execute `git branch -f backup/pre-upstream-sync origin/master`. Forcing existing backup references destroys historical recovery points.

---

## 5. Authorized Upstream Fetch

```bash
# [B] Fetch updates from upstream (requires explicit authorization)
git fetch upstream

# [A] Record the freshly resolved target commit
UPSTREAM_TARGET=$(git rev-parse upstream/master)
echo "Target upstream/master commit: ${UPSTREAM_TARGET}"

# [A] Review incoming commits and changed files against the integration branch
git log --oneline --decorate --graph origin/develop..upstream/master
git diff --stat origin/develop..upstream/master
```

---

## 6. Prepare the Upstream Integration Pull Request

Upstream changes are merged on a short-lived synchronization branch, never directly on `develop`. This preserves upstream history while allowing the active `develop` ruleset to require review before integration.

```bash
# [C] 1. Create the approved synchronization branch from the protected integration branch
SYNC_BRANCH="chore/upstream-sync-$(date -u +%Y%m%d)-$(git rev-parse --short origin/develop)"
git switch --create "${SYNC_BRANCH}" origin/develop

# [C] 2. Execute the explicit merge on the synchronization branch
git merge upstream/master --no-ff -m "Merge updates from upstream/master (${UPSTREAM_TARGET:0:9})"
```

> [!IMPORTANT]
> Do not check out `develop` and merge into it locally. GitHub ruleset `protect-develop` requires the resulting change to enter `develop` through an authorized, self-reviewed Pull Request.

---

## 7. Conflict Resolution Protocol

> [!IMPORTANT]
> **Guiding Principle**:
> «Ownership identifies who must review a conflict; ownership does **not** automatically determine the winning change.»

When conflicts occur during `git merge`:
1. **Immediate Halt**: Automatic tool progression halts immediately.
2. **Identification**: Conflicted files are categorized using the 9 analytical criteria:
   - File ownership tier
   - Change intent (upstream feature vs upstream bugfix)
   - Downstream customization purpose
   - Security implications
   - Schema & migration compatibility
   - Dependency compatibility
   - Multi-company isolation (`CompanyScope`, `BelongsToCompany`)
   - Automated test impact
   - UI/Filament customization impact

### File Ownership Tiers

| Tier | Typical Paths | Handling Protocol |
|:---|:---|:---|
| **Downstream-Owned** | `AGENTS.md`, `docs/`, `.github/workflows/`, `docker/`, `.gitignore` | Downstream changes reviewed; upstream additions evaluated; no automatic overwrite |
| **Upstream-Owned** | `plugins/webkul/*` (unmodified downstream core code) | Upstream updates accepted after verifying custom extension compatibility |
| **Shared Critical** | `composer.json`, `composer.lock`, `phpunit.xml` | **Mandatory manual line-by-line review**. Automatic `ours` or `theirs` resolution is strictly prohibited |

```bash
# [A] Inspect conflicted paths
git status --short | grep '^UU\|^AA\|^UD\|^DU'

# [C] Abort merge if unexpected or unresolvable conflicts arise
git merge --abort
```

### Upstream CI and Automation Changes

Treat every incoming `.github/workflows/` change as a security-sensitive downstream-owned change. It must be reviewed separately from application-code conflicts before acceptance:

1. Inspect triggers, token permissions, secrets, artifact handling, and every remote write or deployment action.
2. Do not accept a workflow merely because it originated upstream or because its file name resembles an existing workflow.
3. A workflow using `workflow_run`, `contents: write`, `pull-requests: write`, Pages publication, or a force push requires explicit human review before it enters `develop`.
4. Record accepted CI changes for the post-sync O6 reconciliation; do not alter protected-branch status checks until their new workflow runs have succeeded.

---

## 8. Validate the Synchronization Branch

After the successful merge and before opening a Pull Request, run local validation suites on `${SYNC_BRANCH}`:

```bash
# [A] 1. Validate Composer manifests
composer validate --strict

# [A] 2. Check code style
vendor/bin/pint --test

# [A] 3. Run unit & feature tests (if environment is configured)
php artisan test --compact

# [A] 4. Verify merge commit parent topology
git log -n 1 --format="%H %P" HEAD
```

---

## 9. Review and Merge into `develop`

```bash
# [D] 1. Push only the synchronization branch (requires human authorization)
git push --set-upstream origin "${SYNC_BRANCH}"

# [D] 2. Create a Pull Request to develop (or create the equivalent PR in the GitHub UI)
gh pr create \
  --base develop \
  --head "${SYNC_BRANCH}" \
  --title "chore: synchronize upstream ${UPSTREAM_TARGET:0:9}" \
  --body "Upstream target: ${UPSTREAM_TARGET}\n\nValidation: <record completed validation>"
```

The responsible maintainer must confirm the conflict-resolution record, self-review the changes, record validation evidence, confirm tag safety, and review any incoming workflow changes. Merge this Pull Request with **Merge Commit**, not Squash or Rebase. After the required authorization and applicable CI checks at that time, confirm the result:

```bash
# [B] Refresh the protected integration branch after GitHub merges the Pull Request
git fetch origin develop

# [A] The merged target must be reachable from origin/develop
git merge-base --is-ancestor "${UPSTREAM_TARGET}" origin/develop
git log -n 1 --format="%H %P" origin/develop
```

---

## 10. Promote a Verified `develop` Release into `master`

Following the canonical release flow (`develop` $\to$ `master`), a verified integration state is promoted through a release Pull Request. Do not merge or push directly into `master`.

```bash
# [A] 1. Record the exact verified source before opening the release Pull Request
RELEASE_SOURCE=$(git rev-parse origin/develop)

# [D] 2. Create a release Pull Request with develop as the source branch
gh pr create \
  --base master \
  --head develop \
  --title "chore: release develop to master" \
  --body "Source develop commit: ${RELEASE_SOURCE}\n\nValidation: <record release and local validation evidence>"
```

The responsible maintainer must select **Merge Commit** after self-review, required authorization, and applicable CI checks complete. Then verify protected-branch parity:

```bash
# [B] Refresh protected references
git fetch origin master develop

# [A] The exact verified release source must be reachable from master
git merge-base --is-ancestor "${RELEASE_SOURCE}" origin/master
git rev-list --left-right --count master...origin/master # must return 0 0
```

---

## 11. Rollback & Emergency Recovery

### Normal Rollback (History-Preserving — Preferred)
If an integrated merge on `master` or `develop` proves defective after pushing, revert the merge commit to preserve linear history without rewriting shared branches:

```bash
# [A] 1. Verify parent ordering of the merge commit
git log -n 1 --format="%H parents: %P" <merge-commit-hash>
```

> [!IMPORTANT]
> **Mainline Parent Verification Rule**:
> «The mainline parent number must be verified against the actual merge commit's parent order before executing the revert. Do not blindly assume that parent "1" is correct.»
> In standard merges, Parent 1 is the pre-merge target branch and Parent 2 is the incoming source branch. Verify the actual order via `git log -n 1 --format="%P"`; do not assume the source is always `upstream/master`.

```bash
# [C] 2. Create a recovery branch from the protected target and revert there.
# Set TARGET_BRANCH to master or develop, then set both verified values below.
TARGET_BRANCH=master
MAINLINE_PARENT=
MERGE_COMMIT=
test -n "${MAINLINE_PARENT}" && test -n "${MERGE_COMMIT}" || {
  echo "Set MAINLINE_PARENT and MERGE_COMMIT after verifying parent order." >&2
  exit 1
}
RECOVERY_BRANCH="chore/upstream-sync-rollback-$(date -u +%Y%m%d)-$(git rev-parse --short origin/${TARGET_BRANCH})"
git switch --create "${RECOVERY_BRANCH}" "origin/${TARGET_BRANCH}"
git revert -m "${MAINLINE_PARENT}" "${MERGE_COMMIT}"

# [D] 3. Push the recovery branch, then open an authorized, self-reviewed Pull Request to the target.
git push --set-upstream origin "${RECOVERY_BRANCH}"
gh pr create \
  --base "${TARGET_BRANCH}" \
  --head "${RECOVERY_BRANCH}" \
  --title "revert: rollback upstream synchronization" \
  --body "Reverts: ${MERGE_COMMIT}\n\nReason: <record approved reason>"
```

For a rollback targeting `master`, merge the authorized, self-reviewed recovery Pull Request with **Merge Commit**, then immediately promote the resulting `master` rollback to `develop` through an authorized recovery Pull Request so the release and integration histories do not diverge. For a rollback targeting `develop` only, use the project-approved Merge Commit path for this upstream recovery. No rollback permits a direct push to `master` or `develop`.

> [!NOTE]
> `git revert -m <parent>` accepts one mainline-parent selector. The commit message is supplied by Git's normal editor or with `--no-edit` when the generated message is sufficient; `-m` is not a message option.

### Emergency Recovery (Destructive History-Rewriting — Exceptional Only)
If a critical security breach or catastrophic data corruption occurs before others have based work on the new commits, use the normal recovery Pull Request whenever technically possible. The active rulesets intentionally block direct history rewrites.

> [!CAUTION]
> **Emergency Recovery Authorization**:
> Requires **written human authorization**, an explicit recorded reason, and a deliberately temporary ruleset change approved by a repository administrator. Restore and re-verify the ruleset immediately after recovery. This alters shared branch history and disrupts all collaborating developers.

```bash
# [E] Emergency procedure is not a normal runbook command.
# First: document authorization, temporarily change the affected ruleset,
# and record the exact protected branch and verified checkpoint.
# Only then may the authorized human perform a history rewrite.
```

---

## 12. Tag & Docker Publishing Safety

Verified findings regarding tags and Docker automation in this repository:

1. **Tag Fetch Behavior**:
   - `remote.upstream.tagOpt` is **not set** (defaults to standard Git behavior).
   - Upstream tags must **not** be assumed to propagate automatically to `origin`.
2. **Tag Push Boundary**:
   - Upstream tags must **never** be pushed to `origin` automatically.
   - Do **not** run `git push origin --tags`.
3. **Docker Publishing Workflow Risk**:
   - `.github/workflows/docker_publish.yml` triggers on:
     ```yaml
     on:
       push:
         tags:
           - 'v*'
     ```
   - Pushing any `v*` tag to `origin` automatically initiates Docker image build and publish to `webkul/aureuserp`.
   - Any upstream version tag (e.g., `v1.6.0`) pushed to `origin` would unintentionally trigger a production Docker build.

---

## 13. Stop Conditions (Emergency Circuit Breakers)

The operator or agent must **immediately halt** the procedure and escalate to a senior maintainer if any of the following conditions occur:

1. Working tree is not clean (`git status --porcelain` is non-empty).
2. Divergence check between local and remote branches returns anything other than `0 0`.
3. Expected remotes (`origin` or `upstream`) or tracking branches are missing.
4. Target upstream commit changes unexpectedly during execution.
5. Checkpoint branch cannot be established or collides with an existing ref.
6. Merge conflicts occur in shared critical files (`composer.json`, `composer.lock`, `phpunit.xml`).
7. Incoming upstream changes modify database migrations in ways that violate company isolation.
8. Destructive migrations (table drops, column drops) are detected in upstream commits.
9. Composer dependency constraints conflict or fail strict validation.
10. Automated tests or linting (`pint`) fail on `develop` after upstream integration or on `master` after release promotion.
11. Tag collision occurs between upstream and downstream tags.
12. Docker publishing triggers could be unintentionally activated.
13. Required validation checks cannot be completed due to missing tooling.
14. Incoming CI or automation changes request elevated token permissions, secret access, Pages publication, or a remote write without explicit human review.
15. Any unexpected Git state cannot be confidently explained by repository evidence.

---

## 14. Documentation & Impact Recording

Following successful synchronization or release promotion:
1. Record the upstream sync commit range, date, and resolved target in [`docs/architecture/change-impact.md`](../architecture/change-impact.md).
2. Update verification entries in [`docs/verification-matrix.md`](../verification-matrix.md).
3. If user-facing or schema changes are included in a release promotion, document release impact in root `CHANGELOG.md` before the downstream tag is created.

---

## 15. R8 — Post-Merge Cleanup

This stage prevents completed synchronization branches and their temporary worktrees from accumulating, while preserving recovery evidence. It applies only after the protected-branch Pull Request is merged and its verification record is complete. It does **not** authorize deletion by itself.

### Candidate Audit — Read-Only Required

For every proposed cleanup target, establish all of the following before asking for deletion authorization:

1. The Pull Request is merged, its merge commit is reachable from its protected target, and the remote branch is not the protected target itself.
2. A local branch is fully merged according to `git branch --merged <target>`; do not rely only on a branch name or a closed Pull Request.
3. Any associated worktree is not the active worktree and has an empty `git -C <path> status --porcelain` result.
4. The target is not a `master`, `develop`, `upstream/*`, `origin/*`, `checkpoint/*`, backup, release, recovery, or currently active topic branch.
5. The candidate list and exact paths/refs have been shown to the human maintainer.

### Authorized Cleanup Sequence

Only after explicit human approval of the audited candidate list:

1. Remove a clean, non-current linked worktree with `git worktree remove <exact-path>`; never use `--force`.
2. Delete its fully merged local topic branch with `git branch -d <exact-branch>`.
3. Delete the matching remote topic branch with `git push origin --delete <exact-branch>` only when it is confirmed merged and no longer needed for collaboration.
4. Run `git worktree prune`, then re-check `git worktree list --porcelain`, `git branch -vv`, and `git status --short`.

Keep pre-sync checkpoints and recovery references until a separately authorized retention decision. Do not use `rm -rf`, `git branch -D`, `git worktree remove --force`, `git push --force`, or wildcard deletion commands in this stage. If a candidate has uncommitted changes, unexpected upstream divergence, or an unclear owner, stop and leave it intact.

### Current Post-Synchronization Candidates

As of the 2026-09-18 synchronization, the merged PR #10 branch `chore/upstream-sync-20260918-c2b4ddaa2` and its temporary linked worktree are candidates only after the audit above. The merged PR #9 branch `refactor/ai-knowledge-architecture` is also a branch-cleanup candidate; it contains the R6 entry-point updates now reachable from `develop`. Checkpoint branches and all other worktrees remain explicitly excluded unless separately audited.

---

## 16. Evidence Matrix

| Area | Observed Reality | Classification | Evidence Source |
|:---|:---|:---:|:---|
| **Remote Topology** | `origin` (aureus-core) and `upstream` (aureuserp) | **VERIFIED** | `git remote -v` |
| **Branch Role: `master`** | Stable release branch; first release promotion pending | **POLICY** | `git-workflow.md` Sections 2 and 8 |
| **Branch Role: `develop`** | Active development trunk | **POLICY** | `git-workflow.md` Section 3, commit `15a76bf09` |
| **Historical Merge Strategy** | Merge commit (`--no-ff`) used for upstream integration | **HISTORICAL PRACTICE** | Commit `49e330b5e`, `15a76bf09`, `de51752a5` |
| **Preflight Divergence** | Authoritative check requires `0 0` count | **POLICY** | `git rev-list --left-right --count` |
| **Backup Branch** | `backup/pre-upstream-sync` exists at `49e330b5e` | **VERIFIED** | `git show-ref --verify` |
| **Rollback Strategy** | `git revert -m <verified-parent>` is canonical | **POLICY** | Section 11 of this runbook |
| **Docker Tag Trigger** | Push of `v*` tag triggers `docker_publish.yml` | **VERIFIED** | `.github/workflows/docker_publish.yml` lines 3–6 |
| **Tag Option Setting** | `remote.upstream.tagOpt` is unset | **VERIFIED** | `git config --get remote.upstream.tagOpt` |
