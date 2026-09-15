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
| 6 | Documentation (`docs/`) | Verified synthesis |
| 7 | AI Inference | Non-authoritative |

## Knowledge Map

### AI Governance Rules — `docs/ai/`

Prescriptive rules that AI agents **must** follow. Start with:

1. [`docs/ai/context.md`](docs/ai/context.md) — System context and architecture baseline
2. [`docs/ai/reading-order.md`](docs/ai/reading-order.md) — Task-specific reading sets (read only what is relevant)
3. [`docs/ai/terminology.md`](docs/ai/terminology.md) — Canonical vocabulary and common misconceptions

Then load domain-specific rules as needed:

- [`docs/ai/architecture-rules.md`](docs/ai/architecture-rules.md) — Structural invariants
- [`docs/ai/security-rules.md`](docs/ai/security-rules.md) — Auth, multi-company isolation
- [`docs/ai/database-rules.md`](docs/ai/database-rules.md) — Schema and persistence conventions
- [`docs/ai/plugin-rules.md`](docs/ai/plugin-rules.md) — Plugin lifecycle and dependencies
- [`docs/ai/coding-rules.md`](docs/ai/coding-rules.md) — PHP 8.3, Laravel, Filament conventions
- [`docs/ai/testing-rules.md`](docs/ai/testing-rules.md) — Pest v4 test standards
- [`docs/ai/forbidden-patterns.md`](docs/ai/forbidden-patterns.md) — Prohibited antipatterns

### System Knowledge — `docs/`

Descriptive documentation of how the system works. Full index at [`docs/README.md`](docs/README.md).

Key domains: `docs/architecture/`, `docs/database/`, `docs/security/`, `docs/plugins/`, `docs/workflows/`, `docs/business-rules/`.

### Change Control

- [`docs/architecture/change-impact.md`](docs/architecture/change-impact.md) — Blast radius assessment
- [`docs/verification-matrix.md`](docs/verification-matrix.md) — Verification state tracking

## Operating Principles

1. **Discovery before action.** Never modify code without first understanding the affected component, its boundaries, and its integration points.
2. **Read the minimum relevant set.** Use `docs/ai/reading-order.md` to load only what the task requires. Do not read the entire documentation tree.
3. **Source code decides.** Documentation narrows the search; the current implementation is the truth. When they disagree, follow the code and note the discrepancy.
4. **Plan before implementation.** For non-trivial changes: assess impact, identify affected components, write a plan, and obtain human approval before editing.
5. **No phantom architecture.** Do not reference, create, or assume classes, traits, methods, or conventions that do not exist in the repository. Verify with `rg` or file inspection.
6. **Company isolation is mandatory.** Every database change touching company-scoped data must use the established isolation suite. See `docs/ai/security-rules.md`.

## Discovery Workflow

For any non-trivial code change, follow this sequence. Do not skip steps.

```
Request → Understand → Discover → Assess → Plan → Approve → Implement → Verify
```

| Step | Action | Gate to proceed |
|:---:|:---|:---|
| **Understand** | Parse the request. Identify what is being asked and what domain it touches. | Can articulate scope in one sentence |
| **Discover** | Read `docs/ai/reading-order.md`, select the task row, load the minimum docs. Inspect the target source, its tests, its provider. Search with `rg` for direct references. | Affected files, symbols, and boundaries identified |
| **Assess** | Evaluate blast radius using `docs/architecture/change-impact.md`. Identify cross-plugin coupling, schema implications, security implications. | Impact is bounded and understood |
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

These constraints are absolute. No task, instruction, or optimization justifies violating them.

1. **Do not delete tests** without explicit human approval.
2. **Do not modify database schema** (migrations, columns, foreign keys, indexes) without an approved plan.
3. **Do not change `composer.json` dependencies** without explicit human approval.
4. **Do not modify `bootstrap/providers.php`** without understanding the full dependency chain of the affected provider.
5. **Do not create new architectural conventions** (new traits, base classes, service patterns) that do not already exist in the repository.
6. **Do not bypass company isolation.** Every query on company-scoped data must go through the established isolation suite (`BelongsToCompany`, `CompanyScope`, `CompanyContext`). See `docs/ai/security-rules.md`.
7. **Do not assume a workflow is functional** because an enum, schema, or UI element represents it. Trace the execution path. See `docs/ai/forbidden-patterns.md`.
8. **Do not modify files outside the task scope**, even to fix unrelated issues. Note them and move on.
9. **Do not hardcode versions.** Always verify against `composer.lock`, not documentation or cached values.
10. **Do not use `DB::` for domain queries.** Use `Model::query()` and Eloquent relationships. See `docs/ai/coding-rules.md`.

## Verification Expectations

After every implementation, verify and report:

| Change Type | Required Verification |
|:---|:---|
| Code logic | Run affected tests (`php artisan test --filter=...`). Report pass/fail. |
| Code style | Run `vendor/bin/pint --dirty`. Report if changes were made. |
| Schema/migration | Verify column names follow `docs/database/schema-conventions.md`. Verify company isolation if applicable. |
| Plugin structure | Verify registration in `bootstrap/providers.php`. Verify `Package` configuration in the service provider. |
| Security-sensitive | Verify policy exists and is registered. Verify company scope is applied. Verify no raw queries bypass scoping. |
| Cross-plugin | Verify no circular dependencies. Verify affected plugins' tests still pass. |

**Always report:**
- What was tested and the result.
- What was **not** tested and why (e.g., "full test suite not run per user request").
- Any unintended side effects observed.

## Technology Baseline

Verify exact versions against `composer.lock` — do not trust cached values:

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

The canonical detailed Git operating model is documented in [`docs/development/git-workflow.md`](docs/development/git-workflow.md), the GitHub repository governance policy is documented in [`docs/development/github-governance.md`](docs/development/github-governance.md), and the CI & testing governance baseline is documented in [`docs/development/ci-testing-governance.md`](docs/development/ci-testing-governance.md).

### Branch Model

All normal development targets `develop`. Direct pushes to `master` and `develop` are prohibited by project policy.

| Branch Pattern | Purpose |
|:---|:---|
| `feature/*` | New functionality |
| `fix/*` | Bug fixes |
| `refactor/*` | Code restructuring without behavior change |
| `docs/*` | Documentation changes |
| `chore/*` | Maintenance, dependencies, CI |

### Commit Convention

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <description>

[optional body]
```

Types: `feat`, `fix`, `refactor`, `docs`, `chore`, `test`, `style`.

Scope: plugin name, `app`, `config`, `ci`, or omit for cross-cutting changes.

### Branch Lifecycle

```
develop → create branch → commits → PR → review → merge → develop
```
