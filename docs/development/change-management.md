---
status: policy
source_of_truth: repository-change-evidence
last_verified: 2026-09-17
scope: change-management-and-knowledge-maintenance
confidence: high
---

# Change Management & Knowledge Maintenance

## Purpose

This policy keeps the Aureus ERP Living Documentation and AI guidance aligned with the repository as it changes. It governs documentation maintenance; it does not replace source-code review, automated tests, database safeguards, GitHub rulesets, or the Git operating model.

The source-of-truth hierarchy remains binding:

```text
Source code → automated tests → database schema → configuration
→ Composer manifests → documentation → AI inference
```

When documentation and an executable source disagree, the executable source governs. The discrepancy must be corrected or explicitly recorded; it must never be hidden by treating documentation as proof.

## Scope and Boundaries

This policy applies to changes in `docs/`, `AGENTS.md`, `.github/PULL_REQUEST_TEMPLATE.md`, and other agent-facing repository guidance. It does not authorize changes to application code, tests, migrations, dependencies, workflows, repository rulesets, branches, or deployments.

Use the applicable domain rules for a source change. In particular:

- assess implementation blast radius through [`docs/architecture/change-impact.md`](../architecture/change-impact.md) before altering code or configuration;
- follow [`docs/ai/reading-order.md`](../ai/reading-order.md) and the relevant domain rules before documenting implementation behavior;
- use [`git-workflow.md`](git-workflow.md), [`github-governance.md`](github-governance.md), and [`upstream-sync.md`](upstream-sync.md) for repository operations;
- use [`ci-testing-governance.md`](ci-testing-governance.md) for CI and quality-gate changes.

## Roles and Accountability

The repository does not establish named documentation owners or an organizational rota. This policy therefore assigns responsibilities by change role, without inventing people or teams.

| Role | Required responsibility |
| --- | --- |
| Change author | Identify documentation impact, update in-scope material, provide evidence, and state what is intentionally unchanged. |
| Reviewer | Verify that claims match the authoritative repository evidence, required links resolve, and the declared scope is respected. |
| Approving maintainer | Accept or reject the PR under the protected-branch rules and resolve ownership or policy questions that the repository does not answer. |
| AI agent | Follow `AGENTS.md`, inspect authoritative sources before asserting behavior, preserve uncertainty labels, and never expand scope without authorization. |

The author may also be the reviewer only where the active GitHub governance policy permits it; this document does not weaken required independent review.

## Required Change Lifecycle

Every non-trivial change follows this lifecycle. The implementation workflow in `AGENTS.md` remains the controlling procedure for AI agents.

```text
Request → Issue (when required) → discover → assess impact → plan → approve
→ implement → verify → Pull Request → review/CI → merge → record/close
```

1. **Track the work** — create or reuse a GitHub Issue when the change falls under the tracked-work criteria in [`git-workflow.md`](git-workflow.md#3-work-item-branch--pull-request-lifecycle). Trivial corrections may proceed without a separate Issue.
2. **Discover** — identify the affected implementation, tests, configuration, workflows, and documentation. Use repository-relative paths and inspect direct references with `rg`.
3. **Assess impact** — classify the change against the source-of-truth hierarchy and [`change-impact.md`](../architecture/change-impact.md). Identify whether security, tenancy, schema, dependency, CI, upstream, or release controls apply.
4. **Plan and approve** — state files, intent, evidence, verification, and any authority needed before making a non-trivial change. Obtain the approval required by `AGENTS.md`.
5. **Implement** — create the appropriate topic branch and keep edits within the approved scope. Do not turn a documentation update into an unreviewed code or platform change.
6. **Verify** — run the proportionate checks: affected tests for behavior changes, documentation link and diff checks for documentation changes, and the required domain checks for higher-risk changes.
7. **Pull Request and review** — open the PR against the correct protected target, link the Issue when one exists, complete the required self-review/verification, and satisfy CI and conversation-resolution controls. Reviewers validate evidence and must not accept claims merely because they appear in existing documentation.
8. **Merge and record** — merge only through the protected-branch process. Close the Issue when the tracked work is complete; update [`docs/CHANGELOG.md`](../CHANGELOG.md) for a material addition, correction, policy change, or verification result. Use the project-root [`CHANGELOG.md`](../../CHANGELOG.md) only for software release history.

## Documentation Impact Triggers

The following events require an explicit documentation-impact decision in the PR. “Not applicable” is valid only when the PR explains why no maintained document describes the changed behavior.

| Change event | Required documentation action |
| --- | --- |
| Behavior, API, workflow, UI, policy, or business-rule change | Update the direct domain document and any directly affected workflow or architecture document. Cite the implementation, test, migration, or configuration that supports the claim. |
| Schema, relationship, model, plugin lifecycle, or dependency change | Update the relevant database, architecture, plugin, or terminology documentation after confirming the repository evidence. Do not alter canonical terminology without source support. |
| Security, authorization, company isolation, or ownership change | Update the applicable security documentation and AI rules when the implementation changes a mandatory invariant. Treat the impact as security-sensitive. |
| Test strategy, CI workflow, quality gate, or developer-tooling change | Update CI/testing governance and the PR verification guidance. Do not claim a gate is enforced until its successful execution and GitHub enforcement are verified separately. |
| Git, GitHub, release, or upstream procedure change | Update the corresponding development runbook and `AGENTS.md` when agent operating behavior changes. Review incoming upstream workflows under the O7 procedure. |
| Documentation correction, navigation change, or AI-guidance change | Update the index or reading route when discoverability changes; preserve the source-of-truth hierarchy and record a material correction in `docs/CHANGELOG.md`. |
| Documentation-only formatting or spelling fix | Update no additional document unless links, meanings, evidence labels, or navigation change. A changelog entry is optional when the change is non-material. |

## Evidence and Claim Discipline

1. Write documentation claims only after inspecting the supporting source, test, schema, configuration, or lockfile.
2. Use the existing evidence labels precisely: `[VERIFIED]`, `[PARTIALLY VERIFIED]`, `[INFERRED]`, and `[UNKNOWN]`.
3. Cite repository-relative file paths and, when useful, a class, method, configuration key, migration, test, or workflow job.
4. Do not create architectural terms, ownership assignments, implementation claims, dates, commit hashes, or release facts that cannot be established from repository evidence.
5. Treat a declaration as distinct from runtime enforcement. A UI element, enum, policy, or workflow file is not proof that the behavior is enforced on every execution path.
6. If the available evidence is insufficient, record `[UNKNOWN]`, state the missing verification, and do not convert the uncertainty into a policy requirement.

## Review Cadence and Staleness

Maintenance is event-driven first: every qualifying PR must make the documentation-impact decision described above. In addition, the approving maintainer should schedule a **quarterly** review of these cross-cutting documents:

- `AGENTS.md` and `docs/ai/reading-order.md`;
- `docs/README.md` and `docs/CHANGELOG.md`;
- the development runbooks in `docs/development/`;
- any document directly affected by changes since the previous review.

The review records its evidence and outcome in `docs/CHANGELOG.md`. A missed cadence does not make documentation false by itself; it creates a maintenance task. A discovered conflict with executable evidence is a correction task and must be prioritized according to its technical and security impact.

## Pull Request Requirements

Every PR must use [`.github/PULL_REQUEST_TEMPLATE.md`](../../.github/PULL_REQUEST_TEMPLATE.md). For a change with documentation impact, the author must declare:

1. the documents updated, or why no maintained document is affected;
2. the authoritative evidence checked;
3. the verification performed and its outcome;
4. any remaining `[UNKNOWN]`, deferred item, or follow-up owner.

This is a review record, not an automated enforcement mechanism. Required status checks remain governed by O5/O6 and are not implied by a checked template box.

The PR template records traceability and verification; it does not replace the Issue. An Issue describes the tracked work, while the PR records the implementation proposed for integration.

## Maintenance Boundaries

- Keep `docs/CHANGELOG.md` for knowledge-base evolution; keep the root `CHANGELOG.md` for application releases.
- Do not update every document merely because one term appears in it. Follow direct implementation and navigation impact.
- Do not edit locked historical phase narratives to make a new policy appear historical. Record current operational work separately.
- Do not execute upstream synchronization, alter GitHub enforcement, publish tags, or change CI merely because their documentation is updated. Each action retains its own authorization boundary.

## Verification for This Policy

When changing this policy or an associated navigation/control document:

```bash
git diff --check
rg --files docs AGENTS.md .github/PULL_REQUEST_TEMPLATE.md
git status --short
```

Also confirm every Markdown link introduced by the change resolves to an existing repository file and that the PR template still renders as a checklist.

## Completion Boundary

O8 is complete on this branch when the lifecycle, responsibilities, triggers, evidence rules, review cadence, PR record, and documentation-change boundaries are documented and connected to the entry points used by developers and AI agents. It does not prove that every future PR will follow the policy; compliance is verified per change and through the quarterly review.
