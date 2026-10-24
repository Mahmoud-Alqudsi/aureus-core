---
status: policy
source_of_truth: repository-documentation-and-source-evidence
last_verified: 2026-10-23
scope: plugin-documentation-template
confidence: high
---

# Aureus ERP — Plugin Documentation Template

## Purpose

This is the structural template for the 28 plugin specifications under [`docs/plugins/`](../plugins/). It standardizes discoverability and evidence placement without replacing source code, tests, migrations, configuration, Composer metadata, or plugin-specific domain documentation.

The template is a documentation contract. It does not require every plugin to own every capability, and it must not erase architectural exceptions such as headless plugins, presentation proxies, render-hook integrations, or customer-panel extensions.

## Source and evidence rules

Every behavior claim must be checked against the repository source-of-truth hierarchy:

```text
Source code → automated tests → database schema → configuration
→ Composer metadata → verified documentation → inference
```

Use the existing evidence labels precisely:

- `[VERIFIED]`: directly supported by a source file, symbol, configuration key, migration, or test.
- `[PARTIALLY VERIFIED]`: only part of the claim is directly supported.
- `[INFERRED]`: a reasoned conclusion that still needs direct confirmation.
- `[UNKNOWN]`: the inspected repository does not establish the fact.

Do not treat a declaration, UI component, enum, policy, or browser spec as proof of enforcement or complete workflow coverage.

## Frontmatter contract

Every plugin specification uses this frontmatter shape:

```yaml
---
status: verified
source_of_truth: source-code
last_verified: YYYY-MM-DD
scope: plugins/webkul/<plugin-slug>
confidence: high
---
```

The values must describe the current document. `last_verified` changes only when the implementation evidence was actually checked. A working document may use `status: draft` and must not be presented as a verified plugin specification.

## Standard document shape

The following order is the default structure. Domain-specific sections may be inserted after `## Database` and before `## Tests` when they preserve unique implementation knowledge.

```markdown
# Plugin: <Display Name> (`<plugin-slug>`)

## Status
## Core/Optional
## Purpose
## Service Provider
## Filament Integration
## Composer Dependencies
## Install Plugins Dependency
## Directory Structure
## Models
## Database
## Filament Components
## Panels
## Services and Support Classes
## Events
## Listeners
## Observers
## Policies and Authorization
## Routes
## Settings
## Translations
<domain-specific sections, when applicable>
## Tests
## Shared Infrastructure Dependencies
## Cross-Plugin Relationships
## Data Flow and Workflows
## Business Rules
## Extension Points
## Dangerous Areas
## Change Impact
## Evidence
```

### Section requirements

- `Status`, `Core/Optional`, `Purpose`, and `Service Provider` establish plugin identity and lifecycle. `Status` combines operational state (active, archived, or deprecated), runtime activation posture (always enabled for Core plugins, or conditionally enabled via `Package::isPluginInstalled()` / `Package::isInstalled()` for Optional plugins), and panel availability summary; a separate `Enabled/Disabled` section is forbidden.
- `Filament Integration` describes the local `*Plugin.php` class or the verified exception when no such class exists. `Filament Components` describes resources, pages, clusters, widgets, schemas, or tables owned by the plugin.
- `Composer Dependencies` describes Composer metadata only. `Install Plugins Dependency` describes the plugins that must be installed before this plugin, sourced strictly from `Package::hasDependencies()`.
- `Models` and `Database` distinguish model inheritance, physical table ownership, migrations, and zero-table extension behavior.
- `Panels` records actual `admin`/`customer` participation and registration conditions; it must not infer panel availability from directory names.
- `Services and Support Classes`, `Events`, `Listeners`, and `Observers` record executable classes and registration paths. A combined domain-specific section is allowed only when its title makes the combined content explicit.
- `Policies and Authorization` records policy classes, permission names, ownership checks, and verified execution paths. It must not claim universal enforcement without direct evidence.
- `Tests` records direct backend tests, direct browser specs, cross-plugin coverage, and known gaps. The complete Playwright atlas remains owned by [`testing-strategy.md`](testing-strategy.md).
- `Shared Infrastructure Dependencies` records the shared/core plugins and code-level infrastructure consumed by the plugin. It may summarize a declared Install Plugins Dependency again when the integration context requires it; the installation-order declaration itself remains owned by `Install Plugins Dependency` and the plugin registry.
- `Cross-Plugin Relationships`, `Data Flow and Workflows`, and `Business Rules` preserve unique domain behavior and integration boundaries.
- `Dangerous Areas` and `Change Impact` record evidence-backed risks, not speculative failure modes.
- `Evidence` lists the direct implementation, test, schema, configuration, and lockfile sources used for the document.

## Not-applicable rule

When a standard section does not apply, keep the section heading and write a short evidence-backed statement:

```markdown
## Observers
[NOT APPLICABLE]
No model observers are defined or registered by this plugin. Confirmed by the plugin source tree and provider registration.
```

Use `[NOT APPLICABLE]` only for a capability that was checked and does not exist. Use `[UNKNOWN]` when the repository inspection is insufficient. Do not use an empty section, a guessed absence, or a copied statement from another plugin.

## Heading normalization rules

The following aliases normalize to the canonical headings without changing section content:

| Existing variant | Canonical heading |
| --- | --- |
| `Filament Plugin Class` / `Filament Plugin class` | `Filament Integration` |
| `Filament Resources, Pages, Widgets & Clusters` and equivalent punctuation/case variants | `Filament Components` |
| `Filament UI Architecture` | `Filament Components` |
| `Services` and `Services & ...` variants | `Services and Support Classes` |
| `Policies`, `Policies & Authorization`, `Policies & Security` | `Policies and Authorization` |
| `Runtime dependencies` / `Runtime Dependencies` (code-level consumption section) | `Shared Infrastructure Dependencies` |
| `Runtime plugin dependencies` / `Runtime Plugin Dependencies` (installation-order section) | `Install Plugins Dependency` |
| `Cross-plugin relationships` | `Cross-Plugin Relationships` |
| `Data Flow` and `Data Flow & ...` variants | `Data Flow and Workflows` |
| `Business rules` and `Business Rules & ...` variants | `Business Rules` |
| `Evidence Index` | `Evidence` |

Combined sections with materially different semantics, such as event/mail/notification or domain-specific lifecycle sections, must be reviewed manually and must not be renamed mechanically if the new heading would misrepresent their contents. A combined section may remain under an explicit domain-specific heading when it is the most accurate representation.

## Forbidden headings

<!-- lint: allow-forbidden-heading -->

The following headings must not appear in any `docs/plugins/<plugin>.md` specification:

| Forbidden heading | Reason | Canonical replacement |
| --- | --- | --- |
| `## Enabled/Disabled` | Separates runtime activation gates from plugin status | Merge activation posture and `Package::isPluginInstalled()` / `Package::isInstalled()` evidence into `## Status` |
| `## Runtime Plugin Dependencies` | Conflates `Package::hasDependencies()` installation ordering with runtime code coupling | `## Install Plugins Dependency` |
| `## Runtime Dependencies` | Ambiguous between installation-order declarations and code-level consumption | `## Install Plugins Dependency` (for `hasDependencies()`) or `## Shared Infrastructure Dependencies` (for code-level consumption) |

## Adoption checklist

For every plugin specification, verify:

- [ ] Frontmatter matches the contract and `last_verified` is evidence-backed.
- [ ] Provider, package name, core state, installation state (merged under `## Status`), and `Install Plugins Dependency` (`Package::hasDependencies()`) are verified from source.
- [ ] No forbidden headings (`## Enabled/Disabled`, `## Runtime Plugin Dependencies`, `## Runtime Dependencies`) appear in the document.
- [ ] Panel participation is verified from the local plugin registration path.
- [ ] Physical table ownership is distinguished from proxy, inherited, or zero-table behavior.
- [ ] Standard headings use the canonical names or an explicitly justified domain-specific heading.
- [ ] Non-applicable capabilities use `[NOT APPLICABLE]`; unresolved facts use `[UNKNOWN]`.
- [ ] Direct tests and browser coverage are identified without claiming exhaustive coverage.
- [ ] Shared rules link to their canonical owners; plugin-specific behavior remains local.
- [ ] Evidence identifies direct source files and symbols for high-risk claims.
- [ ] No content is deleted or relocated without an inbound-reference and destination record.
- [ ] Markdown links, `kb_lint.py --strict`, and `git diff --check` pass.
