# Architecture Specification Synchronization Rule

## Authority & Canonical Status

`ARCHITECTURE.md` is the canonical root architectural specification of Aureus ERP. It reflects Level 1 Source Code Authority and must never diverge from the codebase.

## Architectural Watchlist

Any task or commit touching the following paths is considered an **Architectural Mutation**:
- `composer.json` or `composer.lock`
- `database/migrations/*.php`
- `plugins/webkul/*/database/migrations/*.php`
- `plugins/webkul/*/composer.json`
- `plugins/webkul/*` (directory addition, renaming, or removal)
- `bootstrap/providers.php`
- `plugins/webkul/support/src/Enums/NavigationGroup.php`
- Core tenancy and isolation primitives in `plugins/webkul/support/`

## Mandatory Invariants

1. **Drift-Free Verification**: Prior to completing any task that modifies any file in the Architectural Watchlist, execute:
   ```bash
   php scripts/sync-architecture.php --check
   ```
   If drift is reported, run:
   ```bash
   php scripts/sync-architecture.php --sync
   ```
2. **Qualitative Documentation**: If a new plugin is introduced, its business domain and architectural role must be documented in Section 4.1 of `ARCHITECTURE.md`.
3. **No Phantom Conventions**: Do not document features, traits, or classes in `ARCHITECTURE.md` that do not exist in the source code.
