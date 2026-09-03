---
status: verified
source_of_truth: source-code
last_verified: 2026-09-03
scope: global
confidence: high
---

# Aureus ERP — Security Rules

## 1. Overview & High-Stakes Principles

This document defines the binding security, authorization, multi-company isolation, and query safety rules for Aureus ERP. Security is the highest-stakes domain in the ERP architecture: improper isolation can lead to cross-tenant data leakage, unauthorized financial operations, or privilege escalation.

Every rule in this document is prescriptive. Developers and AI agents MUST strictly comply with these rules.

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                 Aureus ERP Security Tenets                                       │
├──────────────────────────────┬───────────────────────────────────────────────────────────────────┤
│ Declaration ≠ Enforcement    │ Policy/UI declarations MUST NOT be assumed to enforce backend DB  │
│ Company Isolation Integrity  │ Raw queries MUST explicitly mirror Eloquent CompanyScope filtering│
│ Mandatory Security Review    │ Changes to Bouncer, scopes, or company logic REQUIRE human review │
│ OwnerSource Precision        │ Ownership resolution MUST match actual column/relation/pivot kind │
│ Multi-Panel Isolation        │ Admin (User) and Customer (Partner) guards MUST remain separated  │
└──────────────────────────────┴───────────────────────────────────────────────────────────────────┘
```

---

## 2. Company-Scoped Query Rule (Raw SQL & Query Builder)

### Mandatory Standards
> **MANDATORY RULES FOR QUERIES:**
> 1. **Eloquent/scoped mechanisms SHOULD be preferred because they inherit established isolation behavior.**
> 2. **If raw SQL or query builder (`DB::table()`, `DB::select()`, `DB::raw()`) is necessary, the implementation MUST explicitly preserve the same company-isolation guarantees provided by the corresponding Eloquent scope.**
> 3. **A raw query MUST NOT merely add a company condition to one table while leaving another relevant company-scoped relation exposed.**
> 4. **If intentionally bypassing an established company scope, the reason MUST be documented in code and the isolation logic MUST be reviewed explicitly.**

### The Reality of Current Practice vs. Future Standard
- **Audit Finding**: Approximately **143 files** in the repository perform raw database operations (`DB::table`, `DB::raw`, `DB::select`, `DB::statement`).
- **Honest Assessment**: The raw-SQL company-isolation rule is **NOT an already-established repository practice**; Phase 10 is introducing it as a **mandatory future rule because current practice across existing plugins is inconsistent**.
- Historically, some reporting widgets, installation commands, and analytical calculations utilized raw SQL without universally filtering by `company_id` across every joined table, relying informally on surrounding context.
- Moving forward, raw SQL is classified as a **critical review zone**.

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────┐
│                               Raw SQL Isolation Verification Checklist                           │
├───────────────────┬──────────────────────────────────────────────────────────────────────────────┤
│ Primary Table     │ Does the FROM clause table include WHERE company_id IN (...) OR IS NULL?    │
│ Joined Tables     │ Does EVERY joined company-scoped table apply the matching tenant condition?  │
│ Subqueries        │ Do subqueries, derived tables, and CTEs isolate by active company IDs?       │
│ Pivots            │ Are many-to-many pivot rows constrained to tenant-authorized entities?       │
│ Aggregates        │ Do SUM, COUNT, and AVG aggregations filter out foreign tenant transactions?  │
│ Result Processing │ Are records verified against active CompanyContext before returning?        │
└───────────────────┴──────────────────────────────────────────────────────────────────────────────┘
```

### Prescriptive Rules: Queries
- Developers MUST NOT use `DB::table()` merely for convenience when an Eloquent model with `BelongsToCompany` or `BelongsToCompanies` is available.
- When raw SQL or Query Builder is required for complex reporting or bulk performance, the query MUST explicitly append tenant conditions matching `CompanyScope`:
  ```sql
  WHERE (target_table.company_id IN (:activeCompanyIds) OR target_table.company_id IS NULL)
  ```
- In multi-table `JOIN` statements, every joined table that possesses a `company_id` column MUST have its company condition explicitly evaluated in the `ON` or `WHERE` clause. Joining an isolated table to an un-isolated table is STRICTLY FORBIDDEN.

---

## 3. Mandatory Security Review Rule

Because `Webkul\Security` currently has **ZERO automated test coverage** (`plugins/webkul/security/tests/` does not exist), modifications to core security primitives represent extreme regression risks.

### Mandatory Standards
> **MANDATORY REVIEW REQUIREMENT:**
> **Any proposed pull request, code edit, or architectural refactoring that touches any of the following components MUST receive explicit human and security-specialist review before being merged:**
> - `Webkul\Security\Bouncer`
> - `Webkul\Security\Models\Scopes\OwnershipScope`
> - `Webkul\Security\Traits\HasScopedPermissions`
> - `Webkul\Security\Enums\PermissionType`
> - Company isolation logic (`CompanyContext`, `CompanyScope`, `CompaniesScope`, `ChecksCompanyConsistency`)
> - Authorization boundaries, route middleware, or token authentication (`auth:sanctum`)

### Prescriptive Rules: Security Changes
- AI agents MUST NOT independently modify Bouncer logic, ownership evaluation methods, or company scoping algorithms without flagging the change for mandatory human sign-off.
- Any change to `PermissionType` or `Bouncer::getAuthorizedUserIds()` MUST be accompanied by new automated feature tests proving that existing `GLOBAL`, `GROUP`, and `INDIVIDUAL` authorization boundaries remain intact.

---

## 4. OwnerSource Selection Rule

Record-level access control via `Webkul\Security\Traits\HasOwnershipScope` dynamically resolves authorized users through `OwnerSource` definitions.

### Mandatory Standard
> **MANDATORY RULE:**
> **Future ownership-scoped models MUST select the correct `OwnerSource` kind based on the actual ownership representation in the database schema. A neighboring model's ownership configuration MUST NOT be copied blindly.**

```
                                  How is record ownership
                                represented on the entity?
                                             │
         ┌───────────────────┬───────────────┴───────────────┬───────────────────┐
         ▼                   ▼                               ▼                   ▼
    Direct Column    Eloquent Relation                  Pivot Table           Chatter Followers
         │                   │                               │                   │
         ▼                   ▼                               ▼                   ▼
OwnerSource::column() OwnerSource::relation()        OwnerSource::pivot() OwnerSource::followers()
         │                   │                               │                   │
• creator_id         • creator() relation            • team_members pivot • chatter_followers
• user_id            • assignedUser() relation       • user_assignments   • dynamic followers
• assigned_to        • author() relation             • project_members    • partner subscriptions
```

### Established OwnerSource Kinds
1. **`column` (`OwnerSource::column($columnName)`)**:
   - Use when the model table has a direct foreign key pointing to `users.id` (e.g., `creator_id`, `user_id`, `assigned_to`).
2. **`relation` (`OwnerSource::relation($relationName)`)**:
   - Use when ownership is resolved through an existing Eloquent relationship method returning a user or user collection.
3. **`pivot` (`OwnerSource::pivot($table, $firstKey, $secondKey)`)**:
   - Use when ownership is mediated by an intermediate join/pivot table linking records to users.
4. **`followers` (`OwnerSource::followers()`)**:
   - Use when records use the `chatter` infrastructure and grant visibility to subscribed followers.

### Prescriptive Rules: OwnerSource
- Models using `HasOwnershipScope` MUST implement `ownershipSources(): array` returning one or more valid `OwnerSource` instances.
- Developers MUST NOT assume that a column named `user_id` exists on every model.

---

## 5. User Model Distinction Rule (`Security\User` vs `App\User`)

Cross-reference: See Section 1.5 of `docs/ai/terminology.md`.

### Mandatory Standards
- `Webkul\Security\Models\User` is the domain package model containing ERP-specific traits (`HasRoles`, `BelongsToCompanies`, `HasApiTokens`), status attributes, and company associations.
- `App\Models\User` is the application root model extending `Webkul\Security\Models\User`.
- **Plugin Rule**: Local plugin models, policies, and service classes MUST type-hint `Webkul\Security\Models\User` or resolve dynamically via `config('auth.providers.users.model')`.
- **Application Rule**: Root service providers and global guard configurations MUST reference `App\Models\User`.
- Plugins MUST NOT introduce duplicate user models or create parallel inheritance trees.

---

## 6. The Security Declaration Rule (`Declaration ≠ Enforcement`)

### The Foundational Architectural Truth
> **CRITICAL SECURITY PRINCIPLE:**
> **A Policy, Filament Shield Permission, Role, Bouncer check, or UI restriction MUST NOT be treated as proof of complete authorization coverage.**
> **Developers and AI agents MUST verify the actual execution paths for every operation.**

### Why Declaration Is Not Enforcement
1. **Policies Run Only When Called**: A Laravel Policy does not execute automatically on Eloquent queries or database updates. It executes ONLY when an authorizer explicitly calls `$user->can()`, `Gate::authorize()`, or Filament's authorization hooks. Raw queries, background commands, and un-gated controllers bypass policies completely.
2. **UI Hiding Is Not Security**: Hiding a Filament table action button or disabling a form field prevents accidental clicks in the browser, but provides ZERO protection against direct API calls, Form Request manipulation, or CLI execution.
3. **Shield Roles Are Declarative**: Generating permissions via Filament Shield defines permissions in the database; it does NOT guarantee that every API endpoint or internal service validates those permissions before executing business actions.

### Prescriptive Rules: Authorization Verification
- Security-critical actions MUST be verified at the service layer or Form Request layer, not solely in Filament resource view schemas.
- When creating API endpoints under `routes/api.php`, developers MUST apply `auth:sanctum` middleware and explicitly invoke policy authorization (`$this->authorize(...)`).
- Multi-company validation between parent and child models (e.g., verifying that a selected warehouse belongs to the same company as the sales order) MUST be enforced using `ChecksCompanyConsistency` or dedicated service guards, NOT left to UI dropdown filtering.
