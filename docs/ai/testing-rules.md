---
status: verified
source_of_truth: source-code
last_verified: 2026-09-03
scope: global
confidence: high
---

# Aureus ERP — Testing Rules

## 1. Overview & Core Philosophy

This document defines the testing architecture, conventions, and future standards for Aureus ERP. Testing in this repository is built on **Pest v4** (`v4.7.5`) and **PHPUnit v12**.

Because the repository has evolved with significant variation in automated test coverage across its 28 plugins, this document establishes a clear separation between **historical practice** and **future mandatory standards**.

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                Aureus ERP Testing Architecture                                   │
├──────────────────────────────┬───────────────────────────────────────────────────────────────────┤
│ Testing Framework            │ Pest v4 (PHP 8.3+) with Feature/Unit separation                   │
│ Shared Test Helpers          │ plugins/webkul/support/tests/Helpers/ (TestBootstrap, Filament)  │
│ Historical Baseline          │ 11 Tested Plugins / 17 Untested Plugins (Fresh Audit)             │
│ Future Rule Standard         │ Historical Practice ≠ Future Standard (Mandatory minimums)       │
│ Priority Review Areas        │ security, payments, invoices, plugin-manager                      │
└──────────────────────────────┴───────────────────────────────────────────────────────────────────┘
```

---

## 2. Fresh Repository Test Coverage Audit

A fresh audit of automated test files (`*Test.php`) across `plugins/webkul/*/tests/` confirms the following repository baseline:

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────┐
│                             Fresh Plugin Test Coverage Breakdown                                 │
├────────────────────────────┬─────────────────────────────┬───────────────────────────────────────┤
│ Classification             │ Total Plugins               │ Test Coverage Status                  │
├────────────────────────────┼─────────────────────────────┼───────────────────────────────────────┤
│ Core Plugins               │ 9 plugins                   │ 2 Tested / 7 Untested (77.8% zero)    │
│ Optional Plugins           │ 19 plugins                  │ 9 Tested / 10 Untested (52.6% zero)   │
├────────────────────────────┼─────────────────────────────┼───────────────────────────────────────┤
│ Entire Repository          │ 28 plugins                  │ 11 Tested / 17 Untested (60.7% zero)  │
└────────────────────────────┴─────────────────────────────┴───────────────────────────────────────┘
```

### Complete Classification List

#### 1. Tested Plugins (11 Total)
- **Core Tested (2)**:
  - `partners` (9 test files)
  - `support` (17 test files)
- **Optional Tested (9)**:
  - `accounting` (9 test files)
  - `accounts` (42 test files)
  - `employees` (5 test files)
  - `inventories` (41 test files)
  - `manufacturing` (8 test files)
  - `products` (16 test files)
  - `projects` (8 test files)
  - `purchases` (15 test files)
  - `sales` (17 test files)

#### 2. Untested Plugins (17 Total with Zero Automated Tests)
- **Core Untested (7)**:
  - `analytics` (`tests/` does not exist)
  - `chatter` (`tests/` does not exist)
  - `fields` (`tests/` does not exist)
  - `full-calendar` (`tests/` does not exist)
  - `plugin-manager` (`tests/` does not exist)
  - `security` (`tests/` does not exist)
  - `table-views` (`tests/` does not exist)
- **Optional Untested (10)**:
  - `barcode` (`tests/` does not exist)
  - `blogs` (`tests/` does not exist)
  - `contacts` (`tests/` does not exist)
  - `invoices` (`tests/` does not exist)
  - `maintenance` (`tests/` does not exist)
  - `payments` (`tests/` does not exist)
  - `recruitments` (`tests/` does not exist)
  - `time-off` (`tests/` does not exist)
  - `timesheets` (`tests/` does not exist)
  - `website` (`tests/` does not exist)

---

## 3. Actual Pest Testing Conventions

Observed across the 11 tested plugins, tests adhere to these standard conventions:

### 1. Test Directory Structure
Tests reside exclusively inside each plugin's `tests/` directory:
```text
plugins/webkul/<plugin>/tests/
├── Feature/
│   ├── Filament/          # Filament Resource, Page, Action, and Table tests
│   ├── Workflows/         # Multi-step transactional workflows and state machines
│   └── API/V1/            # REST API endpoints and JSON response validation
├── Unit/                  # Isolated calculation and helper unit tests
└── Helpers/               # Domain-specific testing factories and fixtures
```

### 2. Test File Naming
- Feature and workflow test files MUST end with `Test.php` in PascalCase:
  - `InvoiceResourceTest.php`, `MoveLifecycleTest.php`, `CompanyIsolationTest.php`.

### 3. Bootstrap & Database Setup Pattern
Every Pest test file bootstraps plugin installation and active tenancy within `beforeEach()`:
```php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Webkul\PluginManager\Models\Plugin;
use Webkul\PluginManager\Package;

require_once __DIR__.'/../../../../support/tests/Helpers/TestBootstrapHelper.php';
require_once __DIR__.'/../../../../support/tests/Helpers/FilamentHelper.php';

beforeEach(function () {
    TestBootstrapHelper::ensurePluginInstalled('accounts');

    DB::table('plugins')->updateOrInsert(
        ['name' => 'accounts'],
        ['is_installed' => true, 'is_active' => true, 'updated_at' => now()],
    );

    Package::$plugins = Plugin::all()->keyBy('name');
    URL::resolveMissingNamedRoutesUsing(fn () => '#');
});
```

### 4. Authentication & Authorization Setup
- Administrative tests simulate authentication and Filament Shield permissions via `FilamentHelper`:
  ```php
  // Forbid access without permission
  FilamentHelper::actingAs([]);
  Livewire::test(ListInvoices::class)->assertForbidden();

  // Allow access with granular permissions
  FilamentHelper::actingAs(['view_any_account_invoice', 'create_account_invoice']);
  Livewire::test(ListInvoices::class)->assertOk();
  ```

### 5. Filament UI Testing Patterns (Livewire)
- Resource listing tables:
  ```php
  Livewire::test(ListInvoices::class)
      ->assertOk()
      ->assertCanRenderTableColumn('name')
      ->assertCanRenderTableColumn('state');
  ```
- Form filling and creation actions:
  ```php
  Livewire::test(CreateInvoice::class)
      ->fillForm([
          'partner_id' => $partner->id,
          'invoice_date' => now()->toDateString(),
      ])
      ->call('create')
      ->assertHasNoFormErrors();
  ```
- Header & Table action execution:
  ```php
  Livewire::test(EditInvoice::class, ['record' => $invoice->id])
      ->callAction(ConfirmAction::class)
      ->assertHasNoActionErrors();
  ```

### 6. Multi-Company Invariant Assertion Patterns
- Tested plugins utilize `CompanyScopeHelper` to assert that models enforce tenancy:
  ```php
  CompanyScopeHelper::assertCompanyScopeApplied(Order::class);
  ```

---

## 4. Prioritized List of High-Risk Untested Areas

Because 17 plugins currently lack automated tests, future testing and refactoring efforts MUST prioritize modules according to risk profile:

| Priority | Plugin | Status | Primary Risk Factors & Architectural Justification |
| :-: | :--- | :--- | :--- |
| **P1** | **`security`** | Core | **HIGHEST ARCHITECTURAL RISK**: Governs authentication, `Bouncer`, `OwnershipScope`, `PermissionType`, and user authorization repo-wide. An untested authorization bug compromises all company and tenant boundaries. |
| **P2** | **`payments`** | Optional | **FINANCIAL RISK**: Manages gateway transaction logging, payment method configurations, and credit card tokenization. Flaws can cause duplicate charges or ledger discrepancies. |
| **P3** | **`invoices`** | Optional | **OPERATIONAL BILLING RISK**: Core commercial invoicing, credit notes, and payment registration. Lacks dedicated tests despite being a primary operational entry point. |
| **P4** | **`plugin-manager`** | Core | **INFRASTRUCTURE RISK**: Manages plugin installation, dependency resolution, migration running, and uninstallation cleanup. Installation failures can corrupt database schemas. |
| **P5** | **`time-off`** | Optional | **BUSINESS RULE COMPLEXITY**: Complex accrual plan calculations, holiday calendars, and leave allocation balances. |
| **P6** | **`maintenance`** | Optional | **EQUIPMENT WORKFLOW RISK**: Equipment requests, maintenance teams, and FullCalendar integration. |
| **P7** | **`barcode`** | Optional | **WAREHOUSE EXECUTION RISK**: Live scanner interface performing stock movements, package updates, and cycle counts directly on inventory tables. |
| **P8** | **Core UI Primitives** (`chatter`, `fields`, `full-calendar`, `table-views`, `analytics`) | Core | **FOUNDATIONAL UI RISK**: Provide cross-cutting capabilities (audit logging, custom schema columns, saved views) across all domain plugins. |
| **P9** | **Domain Modules** (`contacts`, `timesheets`, `recruitments`, `blogs`, `website`) | Optional | **MODULAR RISK**: Specialized front-end or sub-domain workflows with localized impact. |

---

## 5. Historical Practice ≠ Future Mandatory Standard

### The Principle of Asymmetric Standards
> **CRITICAL ARCHITECTURAL PRINCIPLE:**
> **The fact that 17 existing plugins lack automated tests MUST NOT become the future testing standard.**
> **Historical absence of tests is a legacy artifact, NOT an architectural endorsement.**

A future testing rule may deliberately be stricter than historical practice when justified by:
1. **Security Risk**: Preventing unauthorized data access across tenant or ownership boundaries.
2. **Business-Rule Complexity**: Validating complex state machines and calculations (e.g. tax formulas, currency rate conversions).
3. **Financial Integrity**: Ensuring double-entry ledgers remain balanced.
4. **Company Isolation**: Guaranteeing queries do not leak cross-company records.
5. **Regression Risk**: Protecting core primitives that have dozens of downstream dependents.

Universal 100% test coverage is NOT prescribed; rather, high-risk operational paths MUST be protected by targeted Pest feature tests.

---

## 6. New Plugin Testing Rule

Because repository history does not establish a uniform testing standard across older modules, Phase 10 introduces the following **binding future rule**:

### Mandatory Testing Standard for New Plugins
> **MANDATORY RULE FOR ALL NEW PLUGINS:**
> **Every newly created plugin in `plugins/webkul/<plugin>/` MUST include an automated Pest feature test suite covering its core lifecycles before being merged.**

### Required Minimum Test Matrix for New Plugins:
1. **Plugin Bootstrap Test**: Verifies that the plugin service provider boots, registers with `Package::$plugins`, and gates UI properly on `Package::isPluginInstalled()`.
2. **Multi-Company Scoping Test**: If the plugin introduces company-scoped models, it MUST include a test asserting that `BelongsToCompany` or `BelongsToCompanies` filters records by active company.
3. **Authorization & Policy Test**: Verifies that unauthenticated or unauthorized users receive `assertForbidden()`, and authorized roles can access resource pages.
4. **Primary Workflow Test**: Exercises at least one end-to-end transactional lifecycle (e.g. create draft, confirm, post/complete).

### Prescriptive Rule: Test Preservation
- Developers and AI agents MUST NOT delete, disable, or skip existing test cases without explicit user approval.
