---
status: verified
source_of_truth: source-code
last_verified: 2026-08-25
scope: security
confidence: high
---
# Ownership Scopes

### Status
[VERIFIED]

This document details how Aureus ERP restricts data visibility based on user ownership.

## Overview

Unlike `CompanyScope` which filters data based on the business entity (tenant), `OwnershipScope` filters data based on the user's position within the organizational hierarchy (e.g., individual, team, or global).

## HasOwnershipScope Trait
[VERIFIED]
Models opt into ownership filtering by applying the `Webkul\Security\Traits\HasOwnershipScope` trait.
This trait automatically boots the `OwnershipScope` global scope.

By default, the trait looks for the `creator_id` and `user_id` columns as the sources of ownership:
```php
public function ownershipSources(): array
{
    return [
        OwnerSource::column('creator_id'),
        OwnerSource::column('user_id'),
    ];
}
```

**Evidence:**
- `plugins/webkul/security/src/Traits/HasOwnershipScope.php`

## OwnershipScope Global Scope
[VERIFIED]
`Webkul\Security\Models\Scopes\OwnershipScope` applies the actual query filtering based on the authorized user IDs provided by the `Bouncer` service.

If `bouncer()->getAuthorizedUserIds()` returns `null` (e.g., the user has `GLOBAL` permission), the scope returns immediately, allowing full visibility.
If it returns an array of IDs, the scope dynamically builds an `orWhere` query for each defined ownership source.

**Supported Sources:**
1. **Column (`OwnerSource::KIND_COLUMN`)**: Direct foreign key match (e.g., `creator_id IN (userIds)`).
2. **Relation (`OwnerSource::KIND_RELATION`)**: Matches via an Eloquent relationship (`whereHas`).
3. **Pivot (`OwnerSource::KIND_PIVOT`)**: Matches via a custom pivot table query utilizing `whereExists`.
4. **Followers (`OwnerSource::KIND_FOLLOWERS`)**: Matches records where any of the authorized users are listed as followers (via `partner_id`).

**Bypass Conditions:**
- Running in console.
- Unauthenticated users.
- `Gate::allows('bypass_ownership_scope')` is true.

**Evidence:**
- `plugins/webkul/security/src/Models/Scopes/OwnershipScope.php`
- `plugins/webkul/security/src/Support/OwnerSource.php`

## Bouncer Integration
[VERIFIED]
The array of IDs passed into the `OwnershipScope` query is resolved centrally by `Bouncer::getAuthorizedUserIds()`.
- For `GROUP` permission, Bouncer fetches all user IDs attached to any of the current user's teams.
- For `INDIVIDUAL` permission, it returns `[$user->id]`.

**Evidence:**
- `plugins/webkul/security/src/Bouncer.php`
