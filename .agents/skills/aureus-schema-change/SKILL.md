---
name: aureus-schema-change
description: Plan or implement an Aureus schema or persistence change with migration ownership, company-isolation, relationship, rollback, and verification discovery. Do not use for a documentation-only data-model correction.
---

# Aureus Schema Change

Use this skill for migrations, columns, indexes, foreign keys, persistence behavior, model/table ownership, or data backfill work.

## Read before acting

Read [`AGENTS.md`](../../../AGENTS.md), [`docs/ai/context.md`](../../../docs/ai/context.md), [`docs/ai/reading-order.md`](../../../docs/ai/reading-order.md), and [`docs/ai/terminology.md`](../../../docs/ai/terminology.md). Then read the database reading set in [`docs/ai/reading-order.md`](../../../docs/ai/reading-order.md): overview, company isolation, schema conventions, models index, and relationships.

Also assess the relevant rows of [`docs/architecture/change-impact.md`](../../../docs/architecture/change-impact.md) before proposing an edit.

## Establish ownership and risk

Locate the owning migration, model, factory, tests, provider registration, and direct relationships. Confirm whether another plugin extends the table or dynamically registers a relationship. Identify company scope, foreign-key deletion behavior, existing data, rollback safety, and all callers that depend on the persisted shape.

## Deliver the change safely

Schema work requires an approved plan under [`AGENTS.md`](../../../AGENTS.md). Do not modify a migration, column, foreign key, or index before that approval. Keep domain queries in established Eloquent paths and preserve company isolation.

Verify naming, migrations, targeted tests, and any required cross-plugin effects. Update only directly affected knowledge under [`docs/development/change-management.md`](../../../docs/development/change-management.md). This skill does not authorize data-destructive operations, dependency changes, or bypassing the isolation suite.
