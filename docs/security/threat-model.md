---
status: verified
source_of_truth: source-code
last_verified: 2026-08-25
scope: security
confidence: high
---
# Threat Model

### Status
[VERIFIED]

This document analyzes the security architecture and outlines factual, source-code verified threats and mitigations within Aureus ERP.

## Verified Architecture Mitigations

### 1. Cross-Company Data Leakage
- **Threat:** User from Company A views or modifies records from Company B.
- **Mitigation:** `BelongsToCompany` attaches `CompanyScope` to models that opt into it; the scope filters normal Eloquent queries by active company IDs and includes `NULL` company rows.
- **Residual Risk:** `DB::table()` and query-builder paths rooted outside an Eloquent model do not receive Eloquent global scopes automatically. Raw expressions used inside an Eloquent builder do not by themselves remove its scopes. Scope-bypass-capable paths occur in installation code and also in operational/reporting/model/service code, including accounting and account modules. Each path needs its own filtering review; this audit does not establish an exploitable leak.
- **Classification:** [VERIFIED VULNERABILITY] - None established by source review. [POTENTIAL RISK] - A scope-bypass path can expose cross-company data if it queries company-scoped data without equivalent filtering.

### 2. Ownership / Privilege Escalation
- **Threat:** A standard user modifies records they do not own.
- **Mitigation:** Handled by `OwnershipScope` (restricting visibility) and Policy `hasAccess()` (restricting modification).
- **Residual Risk:** Ownership filtering applies only to models using `HasOwnershipScope`; record-level policy checks apply only where the policy and caller invoke them. The source does not establish a universal fallback from omitted traits to Shield access.
- **Classification:** [ARCHITECTURAL CONCERN] - New models and endpoints require an explicit decision about query scoping and record authorization.

### 3. API Unauthorized Access
- **Threat:** Unauthenticated access to API routes.
- **Mitigation:** The reviewed resource API route groups use `auth:sanctum`. The security plugin deliberately exposes `POST admin/api/v1/login` without that middleware and protects logout with it.
- **Classification:** [VERIFIED VULNERABILITY] - None established by route-middleware review.

### 4. Super-Admin Bypass
- **Threat:** Super admins can bypass the company and ownership scopes.
- **Mitigation:** `Gate::before` is utilized in `SecurityServiceProvider` and `SupportServiceProvider`. 
  - `bypass_ownership_scope` and `bypass_company_scope` are explicitly granted to users with the `super_admin` role.
- **Classification:** [INTENDED BEHAVIOR] - Documented bypass mechanism. 

### 5. Cross-Company Transfers
- **Threat:** Inventory transfers move stock between locations belonging to different companies.
- **Mitigation:** `ChecksCrossCompanyTransfer` invokes `CrossCompanyTransferGuard::assert()` for dirty source/destination locations on inventory `Operation` and `Scrap` models.
- **Classification:** [PARTIALLY VERIFIED] - The guard rejects differing non-null company IDs; it does not reject a pair when either resolved location has a null company ID.

### 6. Stored Cross-Site Scripting (XSS)
- **Threat:** Malicious JavaScript injected via user-submitted formatted text or notes displayed in infolists, tables, or public documents.
- **Mitigation:**
  - `PaymentTerm`: Input sanitized across multiple layers: `PaymentTermRequest::prepareForValidation()` strips unsafe HTML via `str()->sanitizeHtml()`, `PaymentTerm::setNoteAttribute()` mutator enforces sanitization upon persistence, and `PaymentTermInfolist` sanitizes rendered output.
  - `Chatter`: Escapes HTML entities in change summaries.
- **Classification:** [VERIFIED MITIGATION] - Source code verifies multi-layer HTML sanitization on rich-text and note fields.

## Test Coverage
[VERIFIED]
Structural company-scoping invariant tests exist in nine plugins: `accounts`, `inventories`, `manufacturing`, `partners`, `products`, `projects`, `purchases`, `sales`, and `support`. These tests verify trait/column expectations through `CompanyScopeHelper`; they do not by themselves prove every runtime query is isolated.

## Residual Unknowns
[UNKNOWN]
- **Mass Assignment:** While Laravel's `$fillable` is standard, dynamic schemas and custom Filament forms may override standard protections.
- **Dynamic Relationships:** The `resolveRelationUsing()` pattern is heavily used. Its direct impact on IDOR (Insecure Direct Object Reference) depends entirely on whether the calling controller applies the correct authorization policy.
