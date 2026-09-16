---
name: aureus-api-change
description: Plan or implement an Aureus API endpoint or API behavior change with the required route, authorization, validation, company-isolation, and test discovery. Do not use for a Filament-only UI change.
---

# Aureus API Change

Use this skill when adding or changing an HTTP API route, controller/action behavior, request validation, middleware, authentication, or API-facing response contract.

## Read before acting

Start with [`AGENTS.md`](../../../AGENTS.md), [`docs/ai/context.md`](../../../docs/ai/context.md), and [`docs/ai/reading-order.md`](../../../docs/ai/reading-order.md). Then read [`docs/application/overview.md`](../../../docs/application/overview.md), [`docs/architecture/overview.md`](../../../docs/architecture/overview.md), and the applicable security reading set named in the reading-order API row.

Read [`docs/ai/terminology.md`](../../../docs/ai/terminology.md) before changing terms it governs. Read database guidance when persistence behavior changes.

## Establish the execution path

Inspect the applicable route file, controller or action, request validation, middleware, policy or guard, nearest tests, and direct route references. Trace the request beyond a declared route or policy: declarations alone do not prove enforcement.

For company-scoped access, confirm the actual model/query path uses the established isolation mechanisms and that API execution cannot bypass them. Do not infer this from a Filament form or UI policy.

## Deliver the change safely

Use the plan and approval gates in [`AGENTS.md`](../../../AGENTS.md), then run targeted tests and the required security checks. Record API documentation impact through [`docs/development/change-management.md`](../../../docs/development/change-management.md). This skill does not authorize new public exposure, authentication changes, schema changes, or dependency changes without their separate approvals.
