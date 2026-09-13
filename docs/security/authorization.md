---
status: verified
source_of_truth: source-code
last_verified: 2026-08-25
scope: security
confidence: high
---
# Authorization Architecture

### Status
[VERIFIED]

This document outlines the authorization architecture in Aureus ERP.

## Overview

Aureus ERP employs a hybrid authorization model that combines standard Laravel/Filament role-based access control with custom scoping logic for data isolation.

Authentication vs. Authorization vs. Isolation:
- **Authentication**: The `web` and `customer` guards use sessions. API route groups use `auth:sanctum`; Sanctum is configured to check the `web` guard before bearer tokens.
- **Authorization**: Validates if the authenticated user has permission to perform an action (handled by Policies and Filament Shield).
- **Ownership filtering**: Limits visibility to records owned by the user or their group (handled by `OwnershipScope`).
- **Company isolation**: Limits visibility to records within the user's active company context (handled by `CompanyScope` and `CompaniesScope`).

### Security Mechanisms Are Not a Universal Request Pipeline
[VERIFIED]
The source does not implement one fixed sequence containing authentication, Shield, policies, Bouncer, and scopes for every request. Panel access is evaluated by the relevant Filament user implementation; API groups apply `auth:sanctum`; policies run when a caller invokes Laravel/Filament authorization; and company/ownership scopes apply when a model that opts into them builds an Eloquent query. `Bouncer` is consumed by `OwnershipScope`, while policies using `HasScopedPermissions` independently compare `PermissionType` with an owner relation. These mechanisms can combine for a particular endpoint, but their presence does not guarantee isolated retrieval for every query path.

**Evidence:**
- `config/auth.php`; `config/sanctum.php`
- `app/Providers/Filament/AdminPanelProvider.php`; `app/Providers/Filament/CustomerPanelProvider.php`
- `plugins/webkul/security/src/Models/Scopes/OwnershipScope.php`
- `plugins/webkul/security/src/Traits/HasScopedPermissions.php`
- `plugins/webkul/support/src/Models/Scopes/CompanyScope.php`

## Role-Based Access Control (RBAC)

### Filament Shield
[VERIFIED]
Filament Shield (`bezhansalleh/filament-shield`) is used for generating and managing roles and permissions. It integrates directly into the Filament Admin Panel, mapping permissions to resources, pages, and widgets.

**Evidence:**
- `composer.json` (`bezhansalleh/filament-shield`)
- `app/Providers/Filament/AdminPanelProvider.php` (Registers `FilamentShieldPlugin`)
- `config('filament-shield.super_admin.name')` referenced in `SecurityServiceProvider.php`

## Policies and Custom Authorization Logic

### Policy Registration
[VERIFIED]
Policies are standard Laravel policy classes distributed across plugins. `SupportServiceProvider` explicitly registers the security `RolePolicy` for `Webkul\Security\Models\Role`; other policy availability must be verified at the consuming resource/controller rather than inferred from that single registration.

### Policy Structure
[VERIFIED]
Some policies combine basic capability checks (via `$user->can()`) with record-level ownership checks implemented by custom traits; other policies only check a named capability.
For example, `UserPolicy` uses `Webkul\Security\Traits\HasScopedPermissions`. 

**Evidence:**
- `plugins/webkul/security/src/Policies/UserPolicy.php`

### HasScopedPermissions Trait
[VERIFIED]
This trait resolves whether a user has structural access to a record based on their `resource_permission` property (enum `PermissionType`). It implements the `hasAccess` check.

The system recognizes three main levels of access:
1. `PermissionType::GLOBAL`: The user can access any resource (`hasGlobalAccess`).
2. `PermissionType::GROUP`: The user can access resources owned by themselves or members of any team they belong to (`hasGroupAccess`).
3. `PermissionType::INDIVIDUAL`: The user can only access resources they directly own (`hasIndividualAccess`).

**Evidence:**
- `plugins/webkul/security/src/Traits/HasScopedPermissions.php`

## Bouncer Service
[VERIFIED]
The `Webkul\Security\Bouncer` service resolves the user-ID set used by `OwnershipScope`. The reviewed production caller of `bouncer()->getAuthorizedUserIds()` is `OwnershipScope`; the class is not called by `HasScopedPermissions` policies.

- If the user has `GLOBAL` permission, `Bouncer::getAuthorizedUserIds()` returns `null` (implying no restriction).
- If `GROUP` permission, it returns an array of user IDs belonging to the user's teams.
- If `INDIVIDUAL` (self) permission, it returns an array containing only the user's own ID.

**Evidence:**
- `plugins/webkul/security/src/Bouncer.php`
- `plugins/webkul/security/src/Facades/Bouncer.php`
- `plugins/webkul/security/src/Models/Scopes/OwnershipScope.php`

## Panel Security Boundaries
[VERIFIED]
- **Admin Panel**: Authorized via standard Filament mechanisms and the `web` guard (`User` model). Requires users to be authenticated via the `Authenticate::class` middleware and supports Multi-Factor Authentication via `AppAuthentication`.
- **Customer Panel**: Uses the `customer` guard (`Partner` model).

**Evidence:**
- `app/Providers/Filament/AdminPanelProvider.php`
- `config/auth.php`

## Dynamic Relationships
[UNKNOWN]
Aureus ERP heavily utilizes `resolveRelationUsing()` for dynamic relationships across plugins. While there is no direct evidence that these bypass authorization, they require standard authorization policies to be applied explicitly on the endpoints or actions that interact with them.
