---
status: verified
source_of_truth: source-code
last_verified: 2026-08-29
scope: global
confidence: high
---

# Aureus ERP — AI Agent Context

## Purpose and source-of-truth rule

Aureus ERP is a Laravel ERP application whose domain functionality is primarily organised as local packages in `plugins/webkul/`. Its user interfaces are Filament panels. The canonical AI entry point is [`AGENTS.md`](../../AGENTS.md) at the repository root; this document is the second stop, providing system context and the source-of-truth verification framework.

[VERIFIED]
Evidence: `composer.json`; `bootstrap/providers.php`; `plugins/webkul/`

For a coding task, use this evidence order: implementation and executable configuration first; then tests that exercise the behaviour; then Composer metadata and lockfile for installed dependency versions; then repository documentation. Existing documentation, comments, and prior analysis are leads to verify, not evidence by themselves. When sources disagree, record the discrepancy in the relevant in-scope work and follow the implementation.

Use these labels precisely:

- `[VERIFIED]`: directly supported by a cited source file and symbol or configuration key.
- `[PARTIALLY VERIFIED]`: source supports only part of the statement.
- `[INFERRED]`: a reasoned conclusion that still needs direct confirmation.
- `[UNKNOWN]`: the repository inspected does not establish the fact.

## Verified platform baseline

| Component | Installed / declared version | Evidence |
| --- | --- | --- |
| PHP | runtime `8.3.29`; project constraint `^8.3` | `php -v`; `composer.json` → `require.php` |
| Laravel | `v13.31.0` | `composer.lock` → `laravel/framework` |
| Filament | `v5.8.1` | `composer.lock` → `filament/filament` |
| Livewire | `v4.4.5` | `composer.lock` → `livewire/livewire` |
| Pest | `v4.7.5` | `composer.lock` → `pestphp/pest` |
| Filament Shield | `4.2.0` | `composer.lock` → `bezhansalleh/filament-shield` |
| Laravel Sanctum | `v4.3.3` | `composer.lock` → `laravel/sanctum` |
| Composer merge plugin | `v2.1.0` | `composer.lock` → `wikimedia/composer-merge-plugin` |

[VERIFIED]
Evidence: `composer.lock` → `livewire/livewire`, `laravel/sanctum`
Note: Always verify exact versions against `composer.lock`. Direct inspection of `composer.lock` (lines 4135–4148) confirms `laravel/sanctum` is installed at `v4.3.3`.

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
5. **Scope boundary.** Security, database design, workflow, business-rule, event-catalog, and change-impact documentation are later-phase concerns. Their presence in the repository is not evidence that those phases are complete. Inspect code directly when a current task touches one of those areas.

[VERIFIED]
Evidence: `app/Providers/Filament/AdminPanelProvider.php` → `AdminPanelProvider::panel()`; `app/Providers/Filament/CustomerPanelProvider.php` → `CustomerPanelProvider::panel()`; `plugins/webkul/plugin-manager/src/PackageServiceProvider.php`; `plugins/webkul/plugin-manager/src/Package.php`

## Terms used in this repository

- **Plugin package:** a local package under `plugins/webkul/<plugin>`, configured by a class extending `Webkul\PluginManager\PackageServiceProvider`.
- **Package:** `Webkul\PluginManager\Package`, the runtime metadata object configured by each plugin provider.
- **Core plugin:** a package whose provider calls `Package::isCore()`. This affects the base provider's route/migration loading gates; it is not a Composer dependency classification.
- **Installed plugin:** a plugin whose database record has `is_installed` truthy as read by `Package::isPluginInstalled()`. The method returns false when the database cannot be reached or the `plugins` table is absent.
- **Filament plugin class:** a local `*Plugin.php` class implementing `Filament\Contracts\Plugin`; its `register()` method contributes discovery/configuration to a `Panel`.
- **Runtime plugin dependency:** a name declared with `Package::hasDependencies()`. It is consumed by the plugin-manager installation command; it is not a Composer `require` relationship.
- **Company trait terminology:** `BelongsToCompany` is an existing trait. Do not invent a `HasCompanyScope` trait.
- **Bouncer terminology:** `Webkul\Security\Bouncer` is a local Aureus class, not the `silber/bouncer` package. Its behaviour is outside this phase's scope.

[VERIFIED]
Evidence: `plugins/webkul/plugin-manager/src/Package.php` → `Package::isCore()`, `Package::isPluginInstalled()`, `Package::hasDependencies()`; `plugins/webkul/support/src/Traits/BelongsToCompany.php`; `plugins/webkul/security/src/Bouncer.php`

## Investigation method

Start with a small task-specific reading set, then expand only when code shows a direct dependency:

1. Read `docs/ai/reading-order.md` and the relevant section of this document.
2. Open the changed class, its immediate tests, and its service provider or panel provider.
3. Search for the changed class, route name, event, migration, configuration key, or package name with `rg`.
4. Follow only direct results: callers, listeners, relations, policies, migrations, and panel registrations that can alter the requested behaviour.
5. Check `composer.lock` for version-sensitive dependency claims and use repository code to validate documentation before relying on it.

Do not read the entire documentation tree or every plugin by default. Conversely, do not treat a document's absence as permission to guess: investigate the code path and mark unresolved conclusions `[UNKNOWN]`.
