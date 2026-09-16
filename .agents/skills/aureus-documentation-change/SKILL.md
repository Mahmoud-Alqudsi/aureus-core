---
name: aureus-documentation-change
description: Create, correct, or reorganize Aureus documentation and AI guidance using repository evidence, documentation-impact controls, link validation, and changelog boundaries. Do not use for a code change whose documentation impact is incidental.
---

# Aureus Documentation Change

Use this skill for a material change to `docs/`, `AGENTS.md`, a repository runbook, or the pull-request template.

## Read before acting

Read [`AGENTS.md`](../../../AGENTS.md), [`docs/ai/context.md`](../../../docs/ai/context.md), [`docs/ai/reading-order.md`](../../../docs/ai/reading-order.md), [`docs/ai/terminology.md`](../../../docs/ai/terminology.md), and [`docs/development/change-management.md`](../../../docs/development/change-management.md). Load the direct domain document and its authoritative implementation evidence before changing a behavior claim.

## Preserve evidence boundaries

Documentation narrows search; source code, tests, schema, configuration, and lockfiles remain authoritative in that order. Use `[VERIFIED]`, `[PARTIALLY VERIFIED]`, `[INFERRED]`, and `[UNKNOWN]` accurately. Do not invent owners, classes, conventions, dates, commits, releases, or runtime behavior.

Keep the root `CHANGELOG.md` for application releases and `docs/CHANGELOG.md` for material knowledge-base evolution. Do not rewrite locked historical phase narratives to make current work appear historical.

## Deliver the change safely

State the source evidence, files intentionally in scope, documentation-impact decision, and verification performed. Confirm introduced paths exist and run the documentation checks required by the change-management policy. This skill does not authorize code, workflow, GitHub, branch, or deployment changes merely because their documentation is updated.
