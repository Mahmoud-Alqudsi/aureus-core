# Aureus ERP — AI Agent Entry Point

## Project Identity

Aureus ERP is a multi-company Laravel ERP application built on Filament panels. Domain functionality is organized as local plugin packages under `plugins/webkul/`. This file is the canonical entry point for AI coding agents.

## Source-of-Truth Hierarchy

When sources disagree, follow this precedence — higher levels override lower:

| Level | Source | Authority |
|:---:|:---|:---|
| 1 | Source Code (`plugins/`, `app/`, `bootstrap/`) | Absolute |
| 2 | Automated Tests (`tests/`, plugin `tests/`) | Intended behavior |
| 3 | Database Schema (migrations) | Persistence truth |
| 4 | Configuration (`config/`, plugin `config/`) | Runtime settings |
| 5 | Composer Manifests (`composer.json`, `composer.lock`) | Dependencies & versions |
| 6 | AI Inference | Non-authoritative |

## Operating Principles

1. **Discovery before action.** Never modify code without first understanding the affected component, its boundaries, and its integration points.
2. **Source code decides.** The current implementation in code and tests is the truth.
3. **Plan before implementation.** For non-trivial changes: assess impact, identify affected components, write a plan, and obtain human approval before editing.
4. **No phantom architecture.** Do not reference, create, or assume classes, traits, methods, or conventions that do not exist in the repository. Verify with search or file inspection.
5. **Company isolation is mandatory.** Every database change touching company-scoped data must use the established isolation suite (`BelongsToCompany`, `CompanyScope`, `CompanyContext`).
6. **Architecture specification must stay synchronized.** The canonical reference `ARCHITECTURE.md` must accurately reflect Level 1 source code reality. When changes touch plugins, migrations, dependencies, or architectural primitives, verify and synchronize via `php scripts/sync-architecture.php --check` (or `--sync`).

## Discovery Workflow

For any non-trivial code change, follow this sequence:

```
Request → Understand → Discover → Assess → Plan → Approve → Implement → Verify
```

| Step | Action | Gate to proceed |
|:---:|:---|:---|
| **Understand** | Parse the request. Identify what is being asked and what domain it touches. | Can articulate scope in one sentence |
| **Discover** | Inspect the target source, its tests, and its service provider. Search for direct references. | Affected files, symbols, and boundaries identified |
| **Assess** | Evaluate blast radius. Identify cross-plugin coupling, schema implications, security implications. | Impact is bounded and understood |
| **Plan** | Write an implementation plan listing files to change, what changes, and why. Include verification steps. | Plan exists |
| **Approve** | Present the plan to the human for approval. **Do not implement without approval** for: schema changes, dependency changes, cross-plugin changes, security-sensitive changes, or any change touching >3 files. | Human says proceed |
| **Implement** | Execute the plan. Stay within approved scope. | Code written |
| **Verify** | Run affected tests. Check Pint. Confirm no unintended file changes with `git status`. Report what was tested and what was not. | Verification reported |

**Exceptions that skip to Implement directly:**
- Fixing an obvious syntax error in a single file
- Updating a comment or docblock
- Running an existing test or command
- Answering an exploratory question (no code change)

## Critical Constraints

These constraints are absolute:

1. **Do not delete tests** without explicit human approval.
2. **Do not modify database schema** (migrations, columns, foreign keys, indexes) without an approved plan.
3. **Do not change `composer.json` dependencies** without explicit human approval.
4. **Do not modify `bootstrap/providers.php`** without understanding the full dependency chain of the affected provider.
5. **Do not create new architectural conventions** (new traits, base classes, service patterns) that do not already exist in the repository.
6. **Do not bypass company isolation.** Every query on company-scoped data must go through the established isolation suite (`BelongsToCompany`, `CompanyScope`, `CompanyContext`).
7. **Do not assume a workflow is functional** because an enum, schema, or UI element represents it. Trace the execution path.
8. **Do not modify files outside the task scope**, even to fix unrelated issues. Note them and move on.
9. **Do not hardcode versions.** Always verify against `composer.lock`, not cached values.
10. **Do not use `DB::` for domain queries.** Use `Model::query()` and Eloquent relationships.

## Verification Expectations

After every implementation, verify and report:

| Change Type | Required Verification |
|:---|:---|
| Code logic | Run affected tests (`php artisan test --filter=...`). Report pass/fail. |
| Code style | Run `vendor/bin/pint --dirty`. Report if changes were made. |
| Schema/migration | Verify company isolation if applicable. |
| Plugin structure | Verify registration in `bootstrap/providers.php`. Verify `Package` configuration in the service provider. |
| Security-sensitive | Verify policy exists and is registered. Verify company scope is applied. Verify no raw queries bypass scoping. |
| Cross-plugin | Verify no circular dependencies. Verify affected plugins' tests still pass. |
| Architecture | Run `php scripts/sync-architecture.php --check`. If drifted, run `--sync` and verify qualitative impact in `ARCHITECTURE.md`. |

**Always report:**
- What was tested and the result.
- What was **not** tested and why.
- Any unintended side effects observed.

## Technology Baseline

Verify exact versions against `composer.lock`:

| Component | Expected Range |
|:---|:---|
| PHP | ^8.3 |
| Laravel | v13.x |
| Filament | v5.x |
| Livewire | v4.x |
| Pest | v4.x |
| Filament Shield | v4.x |
| Laravel Sanctum | v4.x |

## Development Workflow

### Branch Model

All normal development targets `develop`. Direct pushes to `master` and `develop` are prohibited by project policy. In the active solo-maintainer configuration, a Pull Request remains mandatory but requires zero approving reviews; the author must self-review and record verification.

Urgent defects use `fix/*` from `develop`. Releases enter `master` only through a verified `develop`-to-`master` Pull Request.

| Branch Pattern | Purpose |
|:---|:---|
| `feature/*` | New functionality |
| `fix/*` | Bug fixes |
| `refactor/*` | Code restructuring without behavior change |
| `chore/*` | Maintenance, dependencies, CI |

### Commit Convention

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <description>

[optional body]
```

Types: `feat`, `fix`, `refactor`, `chore`, `test`, `style`.

Scope: plugin name, `app`, `config`, `ci`, or omit for cross-cutting changes.

### Branch Lifecycle

```
develop → create branch → commits → PR → review → merge → develop
```

### Pull Request & Issue Governance

1. **Pull Request Template**: When drafting, opening, or updating a Pull Request, follow [`.github/PULL_REQUEST_TEMPLATE.md`](.github/PULL_REQUEST_TEMPLATE.md):
   - Description and Problem/Solution context
   - Related Issue reference (`Closes #...` or reason for unlinked PR)
   - Type of change classification
   - Pre-merge Checklist (coding standards, self-review, test coverage)
   - Testing steps and local verification evidence

2. **Issue Templates**: When drafting, proposing, or opening GitHub Issues, adhere to the structured forms in [`.github/ISSUE_TEMPLATE/`]:
   - Defect reports: use the structure in `bug.yml`.
   - Proposals & feature requests: use the structure in `feature_request.yml`.
