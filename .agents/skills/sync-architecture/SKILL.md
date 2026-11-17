---
name: sync-architecture
description: >-
  Inspect, verify, and synchronize the canonical ARCHITECTURE.md specification against
  Level 1 source code reality. Use whenever database migrations, local plugins, Composer
  dependencies, tenancy scopes, or Filament panel configurations are modified or created.
---

# Architecture Synchronization Runbook

This skill guides Antigravity agents in maintaining absolute alignment between the canonical platform specification ([`ARCHITECTURE.md`](file:///home/mahmoud/projects/aureuserp/ARCHITECTURE.md)) and the Level 1 Source Code reality of Aureus ERP.

## Architectural Watchlist

Whenever work touches any of these directories or files, architecture synchronization is triggered:
- `composer.json` or `composer.lock`
- `database/migrations/*.php`
- `plugins/webkul/*/database/migrations/*.php`
- `plugins/webkul/*/composer.json`
- `plugins/webkul/*` (new package directories or removed packages)
- `bootstrap/providers.php`
- `plugins/webkul/support/src/Enums/NavigationGroup.php`
- Core tenancy and security files in `plugins/webkul/support/src/` or `plugins/webkul/security/src/`

---

## Workflow Steps

### Step 1: Detect Architectural Drift & Preview Changes

Run the architecture verification command:
```bash
php scripts/sync-architecture.php --check
```
- If the exit code is `0`, `ARCHITECTURE.md` is fully in sync with the codebase without drift or contradictions.
- If the exit code is `1`, review the reported drift items (e.g. migration counts, package versions, TOC, headings, or uncataloged plugins).

To preview line-by-line differences before synchronizing:
```bash
php scripts/sync-architecture.php --diff
php scripts/sync-architecture.php --dry-run
```

### Step 2: Automatic Quantitative Synchronization (Zero-Contradiction & Zero-Loss)

Run the synchronization engine:
```bash
php scripts/sync-architecture.php --sync
```
This automatically updates with safety assertions preventing data loss:
1. Header baseline string: `Last Verified Baseline`.
2. Table of Contents & Headings: Section 4.1 (`All N Local Plugins`) and Section 8.1 (`Across N Migrations`).
3. Section 1.1 monorepo file trees: service providers count, system migrations, local plugins count, other plugins count.
4. Section 2.1 table: `Locked Dependency Baseline`.
5. Section 4.1 count and catalog table: `Comprehensive Catalog of All Local Plugins` (preserving existing descriptions).
6. Section 4.1 archetypes breakdown: Core, Optional, and Zero-Table counts **AND package names arrays**.
7. Prose & diagram references: Sections 1.1, 2.3, 4.3, 4.4, 7.1, 8.3.
8. Section 7.4 cases: `Navigation Groups Enum`.
9. Section 8.1 counts: Total, Core, and Plugin database migration counts.

### Step 3: Qualitative Impact Review & Narrative Update

If the change involves more than numerical metrics (such as a new plugin or architectural paradigm), perform qualitative documentation:

1. **New Local Plugin Added**:
   - Inspect `plugins/webkul/<new-plugin>/composer.json` and service provider.
   - In Section 4.1 of `ARCHITECTURE.md`, locate the new plugin row and replace the placeholder text with a clear, accurate description of its **Business Domain & Architectural Role**.
   - In Section 7.3, update the **Panel Participation Breakdown** if the plugin participates in `customer` panel or is universal.
2. **Tenancy or Security Adjustments**:
   - If introducing models with company isolation, ensure compliance with `BelongsToCompany`, `CompanyScope`, or `ChecksCompanyConsistency` (documented in §5).
   - If adding protected system roles or permissions, update §6.3.
3. **Database Schema & Foreign Keys**:
   - Verify that new foreign keys adhere to the three semantic tiers (§8.3): `nullOnDelete()`, `cascadeOnDelete()`, or `restrictOnDelete()`.

### Step 4: Verification

Confirm that drift is eliminated and tests pass:
```bash
php scripts/sync-architecture.php --check
vendor/bin/pest tests/Unit/ArchitectureSyncTest.php
```
Ensure code styling is intact:
```bash
vendor/bin/pint --dirty
```

