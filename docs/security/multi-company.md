---
status: verified
source_of_truth: source-code
last_verified: 2026-09-23
scope: security
confidence: high
---
# Multi-Company Security

### Status
[VERIFIED]

This document details the multi-company isolation architecture in Aureus ERP.

## Overview

Aureus ERP provides opt-in, session-aware company filtering for Eloquent models that use the relevant traits. `CompanyScope` and `CompaniesScope` are global Eloquent scopes once attached to a model; they do not apply to models that do not opt in, raw/query-builder access, or explicit Eloquent scope removal.

## CompanyContext Service
[VERIFIED]
The `Webkul\Support\Services\CompanyContext` class is the source of truth for the current company session.
It determines which companies the user is allowed to access and which ones are currently "active" in their session.

- **Allowed Companies**: Determined by the `seesAllCompanies()` bypass or the user's explicit relationships (`$user->allowedCompanies()`). Accessible globally via helper functions:
  - `allowed_companies(): Collection` — Resolves the collection of allowed `Company` models.
  - `allowed_company_ids(): array` — Returns integer IDs of allowed companies.
- **Active Companies**: Stored in the session under the key `active_company_ids` (`CompanyContext::SESSION_KEY`). If the session is empty, it selects the user's `default_company_id` when allowed, otherwise the first allowed company. Accessible globally via:
  - `active_company_ids(): array` — Returns active company IDs in session.
  - `current_company_id(): ?int` — Returns the current primary active company ID.

**Evidence:**
- `plugins/webkul/support/src/Services/CompanyContext.php`
- `plugins/webkul/support/src/helpers.php:456-475`

## CompanyScope
[VERIFIED]
`Webkul\Support\Models\Scopes\CompanyScope` is a global Eloquent scope attached by `BelongsToCompany`.

When applied, it modifies Eloquent queries for the model to append:
`WHERE (table.company_id IN (activeIds) OR table.company_id IS NULL)`

For models using the trait, the scope filters their normal Eloquent queries by the active company IDs and also includes rows where `company_id` is `NULL`. This is a query mechanism, not a repository-wide isolation guarantee.

**Bypass Conditions:**
The scope automatically disables itself if:
- Running in console (except during unit tests).
- There is no internal user authenticated.
- The user has been explicitly granted bypass rights (`Gate::allows('bypass_company_scope')`).

**Evidence:**
- `plugins/webkul/support/src/Models/Scopes/CompanyScope.php`

## Traits Enforcing Isolation
[VERIFIED]
Models opt-in to company isolation using traits.

- `BelongsToCompany`: Automatically applies `CompanyScope` and assigns the current active `company_id` when the model is created.
- `BelongsToCompanies`: Applies `CompaniesScope`, which filters through the configured company relation against active company IDs. It also includes models with no related company because the scope uses `orWhereDoesntHave($relation)`.
- `RestrictToAllowedCompanies`: Applies `AllowedCompanyScope` (a variant for restricting to all allowed companies rather than just active ones).

**Evidence:**
- `plugins/webkul/support/src/Traits/BelongsToCompany.php`
- `plugins/webkul/support/src/Traits/BelongsToCompanies.php`
- `plugins/webkul/support/src/Models/Scopes/CompaniesScope.php`
- `plugins/webkul/support/src/Traits/RestrictToAllowedCompanies.php`

## Cross-Company Guards
[VERIFIED]
Explicit inventory logic checks cross-company transfers. `ChecksCrossCompanyTransfer` invokes `CrossCompanyTransferGuard::assert()` on creates and updates of `Operation` and `Scrap` when the location fields are dirty; request validation also calls `detect()`. The guard throws only when both resolved locations have non-null, different `company_id` values.

**Evidence:**
- `plugins/webkul/inventories/src/Support/CrossCompanyTransferGuard.php`
- `plugins/webkul/inventories/src/Models/Concerns/ChecksCrossCompanyTransfer.php`
- `plugins/webkul/inventories/src/Models/Operation.php`
- `plugins/webkul/inventories/src/Models/Scrap.php`

## Cross-Company Form Selection Pattern
[VERIFIED]
When a Filament form schema needs to present selectable records belonging to an explicitly chosen tenant (for example, choosing an `Account` for a specific company in `AccountProductSchema::accountOptions($companyId)`), global scoping like `CompaniesScope` would otherwise restrict choices to the session's active companies.

To safely allow cross-tenant record selection while strictly preventing unauthorized tenant access:
1. The requested `$companyId` is verified against `allowed_company_ids()`:
   ```php
   if (filled($companyId) && in_array((int) $companyId, allowed_company_ids(), true)) {
       $query->withoutGlobalScope(CompaniesScope::class);
   }
   ```
2. If the user is not authorized for that company, `withoutGlobalScope` is not called, preserving isolation.

**Evidence:**
- `plugins/webkul/accounts/src/Filament/Resources/ProductResource/Schemas/AccountProductSchema.php:194-202`
- `plugins/webkul/support/src/helpers.php:470-475`
