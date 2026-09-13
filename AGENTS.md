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

> This section will be expanded in Stage 3 (AI Operating Protocol) and Stage 4 (Git Operating Model).

For now: all development targets the `develop` branch. Feature work uses topic branches (`feature/*`, `fix/*`, `refactor/*`, `docs/*`).
