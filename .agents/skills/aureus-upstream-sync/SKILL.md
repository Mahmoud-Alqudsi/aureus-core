---
name: aureus-upstream-sync
description: Audit or execute an explicitly approved Aureus upstream synchronization through the protected-branch PR workflow, including divergence, conflict, automation, validation, and rollback safeguards. Do not use for ordinary feature branching or a routine Git operation.
---

# Aureus Upstream Synchronization

Use this skill only for an upstream synchronization audit or for execution after explicit human authorization.

## Read before acting

Read [`AGENTS.md`](../../../AGENTS.md), [`docs/ai/reading-order.md`](../../../docs/ai/reading-order.md), [`docs/development/git-workflow.md`](../../../docs/development/git-workflow.md), [`docs/development/github-governance.md`](../../../docs/development/github-governance.md), and [`docs/development/upstream-sync.md`](../../../docs/development/upstream-sync.md) in full.

## Authorization boundary

The upstream runbook is authoritative for its command classes. Read-only inspection may proceed within task scope. Fetching, integration, pushing a synchronization branch, creating Pull Requests, reverting, changing rulesets, or history rewriting each require the explicit authorization stated there. Never push directly to `master` or `develop`.

## Deliver the work safely

Follow the runbook’s preflight, conflict classification, CI-automation review, knowledge-base reconciliation, validation, PR, and stop-condition steps. Upstream synchronization integrates exclusively into `develop`; release promotion to `master` is decoupled and governed independently by `git-workflow.md`. Treat incoming workflows as downstream-owned security-sensitive changes. Do not accept elevated permissions, secrets, remote writes, Pages publication, or force pushing without explicit human review.

Reconcile and commit all living documentation and knowledge base updates directly onto the synchronization branch before opening the Pull Request. Format the Pull Request strictly adhering to [`.github/PULL_REQUEST_TEMPLATE.md`](../../../.github/PULL_REQUEST_TEMPLATE.md), documenting the accepted commit range, evidence, documentation impact, and any deferred follow-up work as prescribed by the runbook and [`docs/development/change-management.md`](../../../docs/development/change-management.md). This skill does not grant permission to run the synchronization.
