---
name: aureus-plugin-change
description: Plan or implement a change to an Aureus local plugin, including its provider, runtime dependencies, panel participation, and direct cross-plugin coupling. Do not use for root-only application changes or a schema-only task.
---

# Aureus Plugin Change

Use this skill for work in `plugins/webkul/<plugin>/` that changes plugin behavior, structure, registration, UI participation, or plugin-to-plugin integration.

## Read before acting

1. Read [`AGENTS.md`](../../../AGENTS.md), [`docs/ai/context.md`](../../../docs/ai/context.md), and [`docs/ai/reading-order.md`](../../../docs/ai/reading-order.md).
2. Read [`docs/ai/terminology.md`](../../../docs/ai/terminology.md) before changing or documenting a model, trait, service, table, or policy.
3. Read [`docs/ai/plugin-rules.md`](../../../docs/ai/plugin-rules.md), [`docs/architecture/plugin-registry.md`](../../../docs/architecture/plugin-registry.md), and the owning plugin document.
4. Add the security, database, API, or testing reading set only when the change reaches that boundary.

## Establish the plugin boundary

Inspect the plugin `composer.json`, service provider, `Package` configuration, root registration in `bootstrap/providers.php`, nearest tests, and `*Plugin.php` panel registration when present. Search direct references before altering a public symbol, provider hook, event, dynamic relation, or cross-plugin call.

Do not equate Composer requirements, `Package::hasDependencies()`, and PHP-level use of another plugin. They are separate mechanisms.

## Deliver the change safely

Follow the discovery, impact, approval, implementation, and verification gates in [`AGENTS.md`](../../../AGENTS.md). State the affected plugin, direct dependants, installed-plugin assumptions, panel impact, and tests run. If the work touches company-scoped data, schema, authorization, or a shared provider, load the corresponding canonical rules before implementation.

Update knowledge according to [`docs/development/change-management.md`](../../../docs/development/change-management.md). This skill does not authorize a new dependency, migration, provider change, or cross-plugin convention.
