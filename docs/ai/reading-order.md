---
status: verified
source_of_truth: source-code
last_verified: 2026-08-29
scope: global
confidence: high
---

# AI Agent Reading Order

## Goal

Read the smallest set that can establish the changed behaviour and its immediate integration points. Documentation narrows the search; the current source code decides the result. Begin at `AGENTS.md`, then read `docs/ai/context.md` and this file before choosing the task row below. Do not load unrelated historical-phase documentation merely because it exists.

## Baseline for every code change

1. `AGENTS.md`
2. `docs/ai/context.md`
3. This file
4. The target source file, its nearest tests, and the owning provider/configuration file

Then search for direct references with `rg`. Before naming, creating, or changing a model, trait, service, table, policy, or documentation term, read `docs/ai/terminology.md`. Add a document or source area only when the task or that search result makes it relevant. If a matching repository skill is available under `.agents/skills/`, load it after this baseline; it supplements but never replaces the canonical rules.

## Task-oriented reading sets

| Task | Read next | Inspect in source |
| --- | --- | --- |
| Change a plugin's model, service, controller, resource, page, or migration | `docs/architecture/overview.md`; `docs/architecture/plugin-registry.md` | `plugins/webkul/<plugin>/`, its `*ServiceProvider.php`, relevant migrations/tests, and direct references to the changed symbol |
| Add, remove, install, or alter a plugin | `docs/architecture/plugin-registry.md` | Root `composer.json`, plugin `composer.json`, `bootstrap/providers.php`, `PackageServiceProvider`, `Package`, `InstallCommand`, and a similar existing plugin |
| Change Filament UI or panel configuration | `docs/architecture/overview.md`; `docs/architecture/plugin-registry.md` | The relevant `*Plugin.php`, `AdminPanelProvider`, `CustomerPanelProvider`, and discovered Filament classes |
| Change an application-level provider, middleware, route, or exception handler | `docs/architecture/overview.md` | `bootstrap/app.php`, `bootstrap/providers.php`, `app/`, and the applicable root/plugin route files |
| Add or change an API endpoint | `docs/application/overview.md`; `docs/architecture/overview.md`; applicable security documentation from the security row | The applicable `routes/api.php`, controller or action, request validation, policy or middleware, nearest tests, and direct route references |
| Change a Composer/autoload claim or dependency | `docs/architecture/plugin-registry.md` | `composer.json`, `composer.lock`, applicable plugin `composer.json`, and Composer-generated autoload configuration when needed |
| Add or change documentation, AI guidance, a development runbook, or the PR template | `docs/development/change-management.md`; applicable domain documentation | The authoritative implementation, test, migration, configuration, workflow, or Git evidence supporting the claim; direct links and the documentation changelog when material |
| Audit or execute an approved upstream synchronization | `docs/development/git-workflow.md`; `docs/development/upstream-sync.md`; applicable CI governance | Git remotes, branch divergence, incoming commit range, changed files, migrations, dependency manifests, and affected tests |
| Touch company, authentication, authorization, policy, raw-query, or ownership code | `docs/security/authorization.md`; `docs/security/multi-company.md`; `docs/security/ownership-scopes.md`; `docs/security/threat-model.md` | The target implementation, its tests, related traits/scopes/providers, and direct call sites. Consult the listed security documentation, then confirm against the implementation. |
| Touch a database schema or persistence behaviour | `docs/database/overview.md`; `docs/database/company-isolation.md`; `docs/database/schema-conventions.md`; `docs/database/models-index.md`; `docs/database/relationships.md` | The model, migration, factory, tests, and every directly related plugin provider. Consult the listed database documentation, then confirm against the implementation. |

## Plugin change checklist

Before editing a local plugin, establish these facts from code:

1. Its directory name and PHP namespace from `plugins/webkul/<plugin>/composer.json`.
2. Its provider and explicit root registration in `bootstrap/providers.php`.
3. Its `Package` configuration: name, core status, declared runtime dependencies, routes, and migrations.
4. Its installation behaviour in `Package::isPluginInstalled()` and any local `*Plugin.php` guard.
5. Its panel participation in `*Plugin.php::register(Panel $panel)`.
6. Direct coupling found through references, provider hooks, events, and dynamically registered relationships.

For a focused bug fix, steps 1–5 plus the affected test normally form a sufficient initial set. Expand to coupled plugins only when a direct reference proves they are involved.

## What not to assume

- A plugin folder or Composer `extra.laravel.providers` entry does not, by itself, establish runtime activation; check `bootstrap/providers.php`, the provider, and installation guards.
- `Package::hasDependencies()` does not declare Composer requirements. Inspect the plugin-manager installation command for its actual effect.
- A registered Filament plugin does not imply its resources are available in both panels; inspect its `register()` conditions.
- Existing documentation for Phases 3–13 is not phase-completion evidence and should not replace source verification.
- Do not read every plugin document or the entire repository before a focused task.

## Escalation rule

If a direct source search reaches an event, listener, dynamic relation, provider hook, panel registration, or cross-plugin call, inspect that target before editing. If the repository still does not prove the required behaviour, state `[UNKNOWN]` rather than presenting an assumption as a fact.
