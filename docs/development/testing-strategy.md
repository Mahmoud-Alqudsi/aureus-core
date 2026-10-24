---
status: verified
source_of_truth: source-code-and-test-configuration
last_verified: 2026-10-23
scope: pest-and-playwright-testing-strategy
confidence: high
---

# Aureus ERP — Testing Strategy

This document is the canonical guide to how Aureus ERP tests are structured, discovered, and run locally. It owns the test strategy and the spec-to-page-object atlas. CI runtime matrices, workflow jobs, merge gates, and artifact retention remain owned by [`ci-testing-governance.md`](ci-testing-governance.md).

## Documentation Ownership Boundary
[VERIFIED]

This document owns the shared testing vocabulary, Pest discovery model, Playwright runtime/fixture model, and the complete spec-to-page-object coverage atlas.

- Plugin documents retain only direct plugin test paths, direct browser specs, cross-plugin coverage references, and plugin-specific coverage gaps.
- [`ci-testing-governance.md`](ci-testing-governance.md) owns workflow execution, runtime/database matrices, shards, artifacts, and merge-gate governance.
- A browser spec or page object proves only the journey and assertions implemented by that test; it does not establish complete backend, security, schema, company-isolation, or workflow coverage.
- Source test files and workflow configuration remain authoritative when this atlas or a plugin document becomes stale.

## Source evidence

The strategy is derived from the executable test configuration and current test tree:

- Pest bootstrap: [`tests/Pest.php`](../../tests/Pest.php) and [`tests/TestCase.php`](../../tests/TestCase.php).
- PHPUnit suites: [`phpunit.xml`](../../phpunit.xml).
- Playwright configuration: [`tests/e2e-pw/playwright.config.ts`](../../tests/e2e-pw/playwright.config.ts).
- Playwright scripts and locked frontend dependencies: [`tests/e2e-pw/package.json`](../../tests/e2e-pw/package.json) and [`tests/e2e-pw/package-lock.json`](../../tests/e2e-pw/package-lock.json).
- Shared browser fixtures: [`tests/e2e-pw/setup.ts`](../../tests/e2e-pw/setup.ts), [`tests/e2e-pw/utils/admin.ts`](../../tests/e2e-pw/utils/admin.ts), and [`tests/e2e-pw/locator/erp_locator.ts`](../../tests/e2e-pw/locator/erp_locator.ts).
- CI execution: [`pest_tests.yml`](../../.github/workflows/pest_tests.yml), [`playwright_tests.yml`](../../.github/workflows/playwright_tests.yml), and [`translations_check.yml`](../../.github/workflows/translations_check.yml).

## Pest strategy

### Discovery and isolation

- `tests/Pest.php` binds Pest tests to `Tests\TestCase` and applies Laravel `DatabaseTransactions`.
- The Pest bootstrap discovers root `Feature` tests and plugin feature tests through `../plugins/*/*/tests/Feature`.
- `Tests\TestCase::setUp()` calls `TestBootstrapHelper::ensureERPInstalled()` before the Laravel test setup.
- [`phpunit.xml`](../../phpunit.xml) declares 11 plugin feature suites: accounts, accounting, inventories, sales, purchases, manufacturing, projects, partners, products, support, and employees.
- The current PHPUnit source include is `app/`; it does not claim plugin code coverage.

### Local commands

Run the complete backend suite from the repository root:

```bash
vendor/bin/pest
```

Use Pest's filtering options for a focused test or suite. The relevant plugin test directories and nearest test files remain the authority for behavior coverage; this document does not replace their assertions.

## Playwright strategy

### Runtime and fixture model

- Test directory: `tests/e2e-pw/tests`.
- Browser project: Chromium using Playwright's `Desktop Chrome` device profile.
- Default base URL: `http://127.0.0.1:8000`; override with `BASE_URL`.
- Shared fixture: `adminPage` creates or reuses an authenticated administrator browser context.
- Authentication state is stored under `tests/e2e-pw/.state/admin-auth.json` after the first successful login.
- Shared selectors and modal handling live in `ErpLocators` and `anyDialog`; page objects contain domain navigation and actions.
- Test timeout is 420 seconds, assertion timeout is 50 seconds, and action timeout is 30 seconds.
- Local runs use one worker and no retries. CI enables full parallel scheduling, one retry, and `forbidOnly`.
- Screenshots are captured on failure; video and trace are captured on the first retry.

### Local commands

From `tests/e2e-pw`:

```bash
npm ci
npx playwright install chromium
npm test
```

Available package scripts are `npm test`, `npm run test:headed`, and `npm run test:ui`. These commands require a running Aureus application at `BASE_URL` and a seeded administrator account matching the test environment.

### CI boundary

The CI workflow runs Playwright against PHP 8.3, Node.js 22.13.1, MySQL 8.0, and PostgreSQL 16. Each database runs six shards, and the aggregate `Playwright E2E Gate` is the merge-required status context. The exact setup, service lifecycle, shard command, report merge, and artifact retention are documented in [`ci-testing-governance.md`](ci-testing-governance.md) and implemented in [`playwright_tests.yml`](../../.github/workflows/playwright_tests.yml).

## Playwright spec atlas

This table is the stable discovery map. It records the spec's functional intent, the page object or shared fixture it uses, and the boundary that must not be inferred from browser coverage alone.

| Spec | Functional scope | Page object / shared support | Coverage boundary |
| --- | --- | --- | --- |
| [`01_plugins/01_plugins.spec.ts`](../../tests/e2e-pw/tests/01_plugins/01_plugins.spec.ts) | Plugin listing, tabs, search, installation, and uninstallation | [`01_pluginManagement.ts`](../../tests/e2e-pw/pages/01_pluginManagement.ts); [`setup.ts`](../../tests/e2e-pw/setup.ts) | Does not prove plugin provider internals, migrations, CLI behavior, or every dependency path. |
| [`02_companies/01_companies.spec.ts`](../../tests/e2e-pw/tests/02_companies/01_companies.spec.ts) | Company listing, CRUD, status, and bulk deletion | [`02_companyManagement.ts`](../../tests/e2e-pw/pages/02_companyManagement.ts) | Does not replace company-isolation, schema, policy, or API tests. |
| [`03_users/01_users.spec.ts`](../../tests/e2e-pw/tests/03_users/01_users.spec.ts) | User CRUD, validation, roles, companies, password reset, and invitation settings | [`02_companyManagement.ts`](../../tests/e2e-pw/pages/02_companyManagement.ts); [`03_userManagement.ts`](../../tests/e2e-pw/pages/03_userManagement.ts) | Does not prove all authorization, Sanctum, invitation, MFA, or ownership paths. |
| [`04_sales/01_salesCustomers.spec.ts`](../../tests/e2e-pw/tests/04_sales/01_salesCustomers.spec.ts) | Sales customer listing and CRUD | [`04_salesManagement.ts`](../../tests/e2e-pw/pages/04_salesManagement.ts) | Does not provide complete partner API or customer-panel coverage. |
| [`04_sales/02_salesProducts.spec.ts`](../../tests/e2e-pw/tests/04_sales/02_salesProducts.spec.ts) | Sales product listing and CRUD | [`04_salesManagement.ts`](../../tests/e2e-pw/pages/04_salesManagement.ts) | Does not cover the complete product model, pricing, or inventory API surface. |
| [`04_sales/03_salesQuotations.spec.ts`](../../tests/e2e-pw/tests/04_sales/03_salesQuotations.spec.ts) | Quotation listing, creation, validation, editing, and draft deletion | [`04_salesManagement.ts`](../../tests/e2e-pw/pages/04_salesManagement.ts) | Does not prove all quotation policies, accounting posting, or customer portal behavior. |
| [`04_sales/04_salesFlow.spec.ts`](../../tests/e2e-pw/tests/04_sales/04_salesFlow.spec.ts) | Quotation-to-order, invoicing, delivery, warehouse integration, returns, amounts, and confirmed-order changes | [`04_salesManagement.ts`](../../tests/e2e-pw/pages/04_salesManagement.ts); [`06_inventoriesManagement.ts`](../../tests/e2e-pw/pages/06_inventoriesManagement.ts) | Does not replace backend workflow, accounting, company-isolation, or concurrency tests. |
| [`05_inventories/01_inventorySettings.spec.ts`](../../tests/e2e-pw/tests/05_inventories/01_inventorySettings.spec.ts) | Inventory setting toggles for warehouses, products, operations, traceability, and dropshipping | [`06_inventoriesManagement.ts`](../../tests/e2e-pw/pages/06_inventoriesManagement.ts) | Does not cover every configuration combination or direct persistence/API behavior. |
| [`05_inventories/03_inventoryProducts.spec.ts`](../../tests/e2e-pw/tests/05_inventories/03_inventoryProducts.spec.ts) | Storable, lot-tracked, and serial-tracked products, quantities, moves, and packages | [`06_inventoriesManagement.ts`](../../tests/e2e-pw/pages/06_inventoriesManagement.ts) | Does not cover the full product master or all stock valuation paths. |
| [`05_inventories/04_inventoryOperations.spec.ts`](../../tests/e2e-pw/tests/05_inventories/04_inventoryOperations.spec.ts) | Receipts, deliveries, transfers, scraps, returns, packages, adjustments, and backorders | [`06_inventoriesManagement.ts`](../../tests/e2e-pw/pages/06_inventoriesManagement.ts) | Does not prove all API, authorization, race-condition, or database-engine edge cases. |
| [`05_inventories/05_inventoryFlow.spec.ts`](../../tests/e2e-pw/tests/05_inventories/05_inventoryFlow.spec.ts) | One-, two-, and three-step warehouse flows from receipt through internal movement and delivery | [`06_inventoriesManagement.ts`](../../tests/e2e-pw/pages/06_inventoriesManagement.ts) | Does not cover every warehouse route or all replenishment integrations. |
| [`05_inventories/06_inventoryConfigurations.spec.ts`](../../tests/e2e-pw/tests/05_inventories/06_inventoryConfigurations.spec.ts) | Warehouses, locations, operation types, tracking, reservation, backorders, and putaway rules | [`06_inventoriesManagement.ts`](../../tests/e2e-pw/pages/06_inventoriesManagement.ts) | Does not imply exhaustive coverage of configuration permutations or APIs. |
| [`05_purchases/01_purchaseVendors.spec.ts`](../../tests/e2e-pw/tests/05_purchases/01_purchaseVendors.spec.ts) | Purchase vendor listing and CRUD | [`05_purchaseManagement.ts`](../../tests/e2e-pw/pages/05_purchaseManagement.ts) | Does not replace partner API or vendor-company isolation tests. |
| [`05_purchases/02_purchaseProducts.spec.ts`](../../tests/e2e-pw/tests/05_purchases/02_purchaseProducts.spec.ts) | Purchase product listing and CRUD | [`05_purchaseManagement.ts`](../../tests/e2e-pw/pages/05_purchaseManagement.ts) | Does not cover all product, pricing, or inventory behavior. |
| [`05_purchases/03_purchaseREQ.spec.ts`](../../tests/e2e-pw/tests/05_purchases/03_purchaseREQ.spec.ts) | RFQ listing, creation, validation, editing, and draft deletion | [`05_purchaseManagement.ts`](../../tests/e2e-pw/pages/05_purchaseManagement.ts) | Does not prove every approval, API, billing, or supplier-portal path. |
| [`05_purchases/04_purchaseAgreement.spec.ts`](../../tests/e2e-pw/tests/05_purchases/04_purchaseAgreement.spec.ts) | Purchase agreement settings, CRUD, validation, editing, deletion, and confirmation | [`05_purchaseManagement.ts`](../../tests/e2e-pw/pages/05_purchaseManagement.ts) | Does not cover all agreement lifecycle and authorization combinations. |
| [`05_purchases/05_purchaseFlow.spec.ts`](../../tests/e2e-pw/tests/05_purchases/05_purchaseFlow.spec.ts) | Purchase order flow, receiving, billing, inventory integration, multi-step reception, amounts, and confirmed-order changes | [`05_purchaseManagement.ts`](../../tests/e2e-pw/pages/05_purchaseManagement.ts); [`06_inventoriesManagement.ts`](../../tests/e2e-pw/pages/06_inventoriesManagement.ts) | Does not replace backend purchase, accounting, company-isolation, or concurrency tests. |
| [`06_website/01_websitePages.spec.ts`](../../tests/e2e-pw/tests/06_website/01_websitePages.spec.ts) | Website page listing, creation, editing, deletion, and visibility controls | [`06_websiteManagement.ts`](../../tests/e2e-pw/pages/06_websiteManagement.ts) | Does not cover customer authentication, all frontend routes, or deployment asset behavior. |
| [`06_website/02_websiteBlogs.spec.ts`](../../tests/e2e-pw/tests/06_website/02_websiteBlogs.spec.ts) | Blog categories and posts, publishing, frontend visibility, and drafting | [`06_websiteManagement.ts`](../../tests/e2e-pw/pages/06_websiteManagement.ts) | Does not cover all translation, SEO, customer-panel, or blog API edge cases. |

## Coverage interpretation rules

- A spec proves only the user journey and assertions implemented in that file; its page object is a test support layer, not an application API.
- A shared page object imported by multiple specs does not mean that every method in the page object is covered by every importing spec.
- Browser coverage does not replace Pest, API, schema, security, company-isolation, or workflow tests.
- A plugin with no row in this atlas has no direct Playwright coverage claim. Supporting entities may appear in a cross-plugin flow without receiving standalone coverage.
- The atlas is reviewed when a spec, page object, test directory, or workflow execution contract changes. It is not a second source of test implementation details.

## Maintenance checklist

When adding or changing a test:

1. Confirm the source file and nearest fixture/page object.
2. Update this atlas if the functional scope, page-object mapping, or coverage boundary changes.
3. Update the affected plugin document only for its direct coverage statement.
4. Keep CI execution and merge-gate details in [`ci-testing-governance.md`](ci-testing-governance.md).
5. Run the documentation checks required by [`change-management.md`](change-management.md) for documentation changes.
