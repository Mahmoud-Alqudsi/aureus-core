---
status: verified
source_of_truth: source-code
last_verified: 2026-10-25
scope: global
confidence: high
---

# Aureus ERP — AI Agent Context

## Purpose and source-of-truth rule

Aureus ERP is a Laravel ERP application whose domain functionality is primarily organised as local packages in `plugins/webkul/`. Its user interfaces are Filament panels. The canonical AI entry point is [`AGENTS.md`](../../AGENTS.md) at the repository root; this document is the second stop, providing system context and the source-of-truth verification framework.

[VERIFIED]
Evidence: `composer.json`; `bootstrap/providers.php`; `plugins/webkul/`

Use the source-of-truth hierarchy in the repository's canonical AI entry point, [`AGENTS.md`](../../AGENTS.md#source-of-truth-hierarchy), when sources disagree. Repository documentation, comments, and prior analysis are leads to verify, not evidence by themselves. Record discrepancies in the relevant in-scope work and follow the governing source.

For documentation ownership and canonical destinations, use [`knowledge-ownership.md`](knowledge-ownership.md). Other documents may provide task-specific summaries, but must link to the owner rather than restate its authoritative content.

Use these labels precisely:

- `[VERIFIED]`: directly supported by a cited source file and symbol or configuration key.
- `[PARTIALLY VERIFIED]`: source supports only part of the statement.
- `[INFERRED]`: a reasoned conclusion that still needs direct confirmation.
- `[UNKNOWN]`: the repository inspected does not establish the fact.

## Verified platform baseline

Expected major/minor ranges for the platform stack (PHP `^8.3`, Laravel `v13.x`, Filament `v5.x`, Livewire `v4.x`, Pest `v4.x`, Filament Shield `v4.x`, Laravel Sanctum `v4.x`, and `wikimedia/composer-merge-plugin` `v2.x`) are defined in [`AGENTS.md` (Technology Baseline)](../../AGENTS.md#technology-baseline). Do not maintain a duplicate pinned version table here; always verify exact installed package versions directly against [`composer.lock`](../../composer.lock) and [`composer.json`](../../composer.json).

[VERIFIED]
Evidence: `composer.json`; `composer.lock`

## Repository map

- `app/` contains application-level providers, middleware, controllers, and models; the two panel providers live in `app/Providers/Filament/`.
- `bootstrap/app.php` configures routing, application middleware, and exception handling. `bootstrap/providers.php` explicitly lists the application, panel, and plugin service providers.
- `plugins/webkul/<plugin>/` contains local plugin packages. Typical plugin roots contain `src/`, `database/`, `resources/`, `routes/`, `tests/`, and `composer.json`; individual plugins may omit directories they do not need.
- `routes/`, `config/`, `database/`, and `tests/` contain application-level code alongside their plugin counterparts.
- `docs/` is supporting documentation. Only documentation that a task has independently verified should be relied on for implementation decisions.

[VERIFIED]
Evidence: `bootstrap/app.php`; `bootstrap/providers.php`; `app/Providers/Filament/`; `plugins/webkul/accounts/`

## Architectural boundaries to establish before editing

1. **Application versus plugin boundary.** Locate the relevant service provider in `bootstrap/providers.php` and the owning plugin directory before changing domain code. A plugin's Composer file supplies its PHP namespace/autoload metadata; the root provider list is also an explicit registration path.
2. **Plugin lifecycle boundary.** A plugin service provider configures a `Webkul\PluginManager\Package`. Core status, runtime installation state, migrations/routes, dependencies, and UI registration are related but distinct. Read `docs/architecture/plugin-registry.md` and the affected provider before changing any of them.
3. **Panel boundary.** There are two configured Filament panels: `admin` at `admin` and `customer` at `/`. A Filament plugin may register different discovery paths for each panel, so inspect its `register(Panel $panel)` method rather than assuming an admin-only feature.
4. **Cross-plugin boundary.** Search the affected plugin and its dependencies for service-provider hooks, events/listeners, model relation extensions, and references to the changed symbol. Do not infer ownership from a class's directory alone.
5. **Scope boundary.** Security, database design, workflow, business-rule, event-catalog, and change-impact documentation live in their respective domain directories under `docs/`. Their presence in the repository does not replace source verification; inspect code directly when a task touches one of those areas.

[VERIFIED]
Evidence: `app/Providers/Filament/AdminPanelProvider.php` → `AdminPanelProvider::panel()`; `app/Providers/Filament/CustomerPanelProvider.php` → `CustomerPanelProvider::panel()`; `plugins/webkul/plugin-manager/src/PackageServiceProvider.php`; `plugins/webkul/plugin-manager/src/Package.php`

## Terms used in this repository

Canonical project terminology, class mappings, and misconception corrections (including multi-company isolation traits, `Webkul\Security\Bouncer`, plugin lifecycle states, dependency taxonomy, and Filament panels) are defined solely in [`docs/ai/terminology.md`](terminology.md). Read [`docs/ai/terminology.md`](terminology.md) before naming, creating, or changing a model, trait, service, table, policy, or documentation term.

## Investigation method

Start with a small task-specific reading set, then expand only when code shows a direct dependency:

1. Read `docs/ai/reading-order.md` and the relevant section of this document.
2. Open the changed class, its immediate tests, and its service provider or panel provider.
3. Search for the changed class, route name, event, migration, configuration key, or package name with `rg`.
4. Follow only direct results: callers, listeners, relations, policies, migrations, and panel registrations that can alter the requested behaviour.
5. Check `composer.lock` for version-sensitive dependency claims and use repository code to validate documentation before relying on it.

Do not read the entire documentation tree or every plugin by default. Conversely, do not treat a document's absence as permission to guess: investigate the code path and mark unresolved conclusions `[UNKNOWN]`.
