---
name: aureus-test-and-ci-change
description: Plan or implement Aureus Pest, Playwright, translation-check, or GitHub Actions validation changes while preserving O6 boundaries and proportionate verification. Do not use for ordinary application feature work without a testing or CI change.
---

# Aureus Test and CI Change

Use this skill when changing a test, test helper, test bootstrap, test command, quality gate, or `.github/workflows/` validation behavior.

## Read before acting

Read [`AGENTS.md`](../../../AGENTS.md), [`docs/ai/context.md`](../../../docs/ai/context.md), [`docs/ai/reading-order.md`](../../../docs/ai/reading-order.md), and [`docs/ai/testing-rules.md`](../../../docs/ai/testing-rules.md). For CI work, read [`docs/development/ci-testing-governance.md`](../../../docs/development/ci-testing-governance.md). For a workflow arriving from upstream, also read [`docs/development/upstream-sync.md`](../../../docs/development/upstream-sync.md).

## Establish the smallest valid verification scope

Identify the changed behavior, nearest test, shared helpers/bootstrap, command invocation, workflow trigger, job permissions, artifacts, and status-check implications. A green or failed historical run is evidence only when its environment and failure cause are relevant to the proposed decision.

Do not infer that a workflow is a required GitHub gate merely because its YAML exists. GitHub enforcement is a separate O5/O6 concern.

## Deliver the change safely

Keep test remediation separate from unrelated governance or application refactors. Do not delete tests without explicit human approval. For workflow changes, use a separately approved scope and preserve least privilege. Review incoming upstream automation as security-sensitive; elevated token permissions, secrets, remote writes, Pages publication, or force pushes require human review before acceptance.

Run the targeted checks required by [`AGENTS.md`](../../../AGENTS.md), report unrun checks, and update CI/testing documentation under [`docs/development/change-management.md`](../../../docs/development/change-management.md) when the operating behavior changes.
