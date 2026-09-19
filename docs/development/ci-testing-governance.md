---
status: verified
source_of_truth: repository-configuration
last_verified: 2026-09-19
scope: ci-testing-governance
confidence: high
---

# CI & Testing Governance

This document defines the canonical Continuous Integration (CI) and automated testing governance specification for Aureus ERP. It establishes an evidence-based baseline of active GitHub Actions workflows, test suites, execution commands, runtime and database matrices, dependency handling, security boundaries, and the distinction between workflow execution and GitHub merge enforcement.

---

## 1. Purpose

The purpose of this document is to define the technical verification and testing layer that validates contributions to Aureus ERP. While the Git Operating Model ([`docs/development/git-workflow.md`](git-workflow.md)) establishes local branching and commit protocols, and GitHub Governance ([`docs/development/github-governance.md`](github-governance.md)) defines repository-level administrative controls, this document governs how automated pipelines inspect, test, and validate changes before integration into `develop`.

### Governance Hierarchy

```
+-------------------------------------------------------------------+
|                    GitHub Governance (Operational Stage O5)       |
|   Default branch, PR controls, rulesets, merge rules, permissions |
+-------------------------------------------------------------------+
                                  │
                                  ▼
+-------------------------------------------------------------------+
|                  Git Operating Model (Operational Stage O4)       |
|   Branch hierarchy, commit convention, topology, merge semantics  |
+-------------------------------------------------------------------+
                                  │
                                  ▼
+-------------------------------------------------------------------+
|               Continuous Integration & Testing (Operational O6)   |
|      Workflow definitions, test suites, required status checks    |
+-------------------------------------------------------------------+
                                  │
                                  ▼
+-------------------------------------------------------------------+
|                  Upstream Integration (Operational Stage O7)      |
|     Lineage synchronization, upstream tracking, conflict protocols|
+-------------------------------------------------------------------+
```

---

## 2. Scope & Boundaries

### In-Scope Domains

- **GitHub Actions Workflows**: Complete audit of all workflows located in [`.github/workflows/`](../../.github/workflows/).
- **Workflow Triggers & Concurrency**: Inspection of push, pull request, merge group, and manual dispatch triggers, path filters, and cancellation rules.
- **PHP / Pest Testing Architecture**: Inspection of Pest v4 configuration, PHPUnit test suites, test directory structure across all plugins, test base classes, and database bootstrapping.
- **Frontend / Playwright Testing**: Audit of end-to-end browser testing, sharding, browser installations, test server lifecycle, and report merging.
- **Translation Parity Testing**: Audit of the custom `translations:check` CLI command, supported locales, and failure semantics.
- **Runtime & Database Matrices**: Verification of PHP versions, Node.js versions, MySQL 8.0, and PostgreSQL 16 container configurations and service healthchecks.
- **Dependency Management & Caching**: Audit of `composer install` invocations, `npm ci`, Composer cache, NPM cache, and Playwright browser caching.
- **Static Analysis & Code Style**: Audit of Pint, PHPStan, ESLint, and architectural test gates.
- **Coverage Governance**: Verification of coverage drivers, thresholds, and reporting services.
- **Permissions & Supply Chain Security**: Audit of GitHub token permissions (`GITHUB_TOKEN`), referenced GitHub Actions, and container image publishing.
- **GitHub Status Check Governance**: Explicit reconciliation distinguishing workflow execution from GitHub server-side required status check gating.
- **Governance Findings & Evidence Matrix**: Comprehensive catalog of evidence-backed findings and control classifications.

### Out-of-Scope (Operational Stage Boundaries)

- **Workflow File Modification**: Modifying YAML workflows in `.github/workflows/` is prohibited during Operational Stage O6 baseline auditing. Remediations are documented as governance findings.
- **Automated Test Code Modification**: Fixing failing tests, refactoring test helpers, or modifying test suites in `tests/` or `plugins/webkul/*/tests/` belongs to future development phases.
- **Application Code & Migrations**: Modifying domain code in `app/`, `plugins/`, `database/`, or `config/` is strictly prohibited.
- **GitHub Platform Mutations**: Modifying repository settings, rulesets, or branch protection rules on GitHub is prohibited without explicit authorization.
- **Upstream Synchronization Execution**: Operational merge procedures and synchronization runbooks are canonically documented in [`docs/development/upstream-sync.md`](upstream-sync.md).

---

## 3. CI Architecture Overview

Aureus ERP automates continuous integration via GitHub Actions. The pipeline consists of 4 distinct workflow files:

```
                                  GitHub Events
                                        │
           ┌────────────────────────────┼───────────────────────────┐
           │                            │                           │
           ▼                            ▼                           ▼
    Pull Request / Push          Pull Request / Push          Push Tag (v*) /
    (develop, master)            (develop, master)            Manual Dispatch
           │                            │                           │
           ▼                            ▼                           ▼
┌──────────────────────┐     ┌──────────────────────┐     ┌──────────────────────┐
│   pest_tests.yml     │     │ playwright_tests.yml │     │  docker_publish.yml  │
│                      │     │                      │     │                      │
│ - PHP 8.3            │     │ - PHP 8.3 / Node 22  │     │ - Multi-Arch Build   │
│ - MySQL 8.0 & PG 16  │     │ - 6 Shards x 2 DBs   │     │ - amd64 + arm64      │
│ - Single process     │     │ - HTML Report Merge  │     │ - Docker Hub Push    │
└──────────────────────┘     └──────────────────────┘     └──────────────────────┘
           │
           ▼
┌──────────────────────┐
│translations_check.yml│
│                      │
│ - PHP 8.3            │
│ - EN Baseline Parity │
│ - All Plugins Checked│
└──────────────────────┘
```

1. **`pest_tests.yml`** (Backend Verification): Executes the Pest v4 feature test suite across dual database engines (MySQL 8.0 and PostgreSQL 16) on PHP 8.3.
2. **`playwright_tests.yml`** (End-to-End Verification): Executes browser tests using Playwright across 6 parallel shards against dual database engines (MySQL 8.0 and PostgreSQL 16), culminating in a consolidated HTML report merge job.
3. **`translations_check.yml`** (Linguistic Parity): Audits translation key and file parity across all plugin language directories against the canonical English (`en`) baseline.
4. **`docker_publish.yml`** (Release Deployment): Builds and publishes production multi-architecture Docker images (`linux/amd64`, `linux/arm64`) to Docker Hub. This is a release deployment workflow, not a PR merge gate.

---

## 4. Workflow Inventory

Inspection of [`.github/workflows/`](../../.github/workflows/) confirms the following 4 active workflow definitions:

| Workflow File | Workflow Name | Event Triggers | Concurrency Group | Permissions | Runner | Matrix Dimensions | Total Jobs |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| [`pest_tests.yml`](../../.github/workflows/pest_tests.yml) | `Pest Tests` | `push`, `pull_request` (branches: `master`, `develop`) | `${{ github.workflow }}-${{ github.ref }}` (`cancel-in-progress: true`) | `contents: read` | `ubuntu-latest` | OS: `ubuntu-latest`<br>PHP: `8.3`<br>DB: `mysql`, `pgsql` | 2 matrix jobs |
| [`playwright_tests.yml`](../../.github/workflows/playwright_tests.yml) | `Playwright Tests` | `push`, `pull_request` (branches: `master`, `develop`), `merge_group`, `workflow_dispatch` | `${{ github.workflow }}-${{ github.ref }}` (`cancel-in-progress: true`) | `contents: read` | `ubuntu-latest` | PHP: `8.3`<br>Node: `22.13.1`<br>Shards: `1..6` of `6`<br>DB: `mysql`, `pgsql` | 14 jobs (12 test shards + 2 report merges) |
| [`translations_check.yml`](../../.github/workflows/translations_check.yml) | `Translations Check` | `push`, `pull_request` (branches: `master`, `develop`) | `${{ github.workflow }}-${{ github.ref }}` (`cancel-in-progress: true`) | `contents: read` | `ubuntu-latest` | None (single job) | 1 job |
| [`docker_publish.yml`](../../.github/workflows/docker_publish.yml) | `Publish Docker Image` | `push` (tags: `v*`), `workflow_dispatch` (inputs: `app_ref`, `image_tag`) | None | `contents: read` | `ubuntu-latest` | Platforms: `linux/amd64`, `linux/arm64` | 1 job |

---

## 5. Workflow Trigger Model

### Audit of `on:` Triggers

```
+──────────────────────────┬───────────┬──────────────┬─────────────┬───────────────────┬───────────┬───────────────+
| Workflow                 | push      | pull_request | merge_group | workflow_dispatch | schedule  | Path Filters  |
+──────────────────────────┼───────────┼──────────────┼─────────────┼───────────────────┼───────────┼───────────────+
| pest_tests.yml           | YES [1]   | YES [1]      | NO          | NO                | NO        | NONE (Runs all)|
| playwright_tests.yml     | YES [1]   | YES [1]      | YES         | YES               | NO        | NONE (Runs all)|
| translations_check.yml   | YES [1]   | YES [1]      | NO          | NO                | NO        | NONE (Runs all)|
| docker_publish.yml       | YES [2]   | NO           | NO          | YES               | NO        | NONE (Tags only|
+──────────────────────────┴───────────┴──────────────┴─────────────┴───────────────────┴───────────┴───────────────+
[1] Target branches: master, develop
[2] Target tags: v*
```

### Trigger Observations

1. **Trigger Asymmetry on `merge_group`**:
   - `playwright_tests.yml` declares `merge_group:` to support GitHub Merge Queues.
   - However, neither `pest_tests.yml` nor `translations_check.yml` declare `merge_group:`.
   - **Implication**: The current trigger configuration is asymmetric. If GitHub Merge Queue is enabled for protected branches, Pest and translation validation would not execute through `merge_group` unless explicitly configured.
2. **Missing `workflow_dispatch` on Core Test Workflows**:
   - Neither `pest_tests.yml` nor `translations_check.yml` can be manually triggered via the GitHub Actions UI or API.
   - Engineers cannot re-run backend tests on demand without pushing a commit or triggering a full PR synchronization event.
3. **Absence of Path Filtering**:
   - None of the PR workflows configure `paths` or `paths-ignore`.
   - Documentation-only pull requests (e.g., changes exclusively within `docs/` or `*.md`) trigger the full test matrix: 2 Pest test jobs, 12 Playwright shards, 2 report merge jobs, and 1 translation check job (totaling 17 jobs per commit).

---

## 6. Test Architecture

### Frameworks and Versions

Inspection of [`composer.json`](../../composer.json) and [`composer.lock`](../../composer.lock) reveals the exact test framework dependencies:

- **Pest Framework**: `pestphp/pest` version `v4.7.5` (defined as `^4.4` in `require-dev`).
- **PHPUnit Engine**: `phpunit/phpunit` version `12.5.30` (underlying execution engine for Pest v4).
- **Parallel Testing Engine**: `brianium/paratest` version `v7.20.0` (present in lockfile).
- **Collision Error Handler**: `nunomaduro/collision` version `v8.9.5`.
- **E2E Browser Automation**: `@playwright/test` version `^1.57.0` (defined in `tests/e2e-pw/package.json`).

### PHP Test Suites & Configuration

The root configuration file [`phpunit.xml`](../../phpunit.xml) defines 11 discrete test suites corresponding to 11 domain plugin packages:

```xml
<testsuites>
    <testsuite name="AccountFeature"><directory>plugins/webkul/accounts/tests/Feature</directory></testsuite>
    <testsuite name="AccountingFeature"><directory>plugins/webkul/accounting/tests/Feature</directory></testsuite>
    <testsuite name="InventoryFeature"><directory>plugins/webkul/inventories/tests/Feature</directory></testsuite>
    <testsuite name="SaleFeature"><directory>plugins/webkul/sales/tests/Feature</directory></testsuite>
    <testsuite name="PurchaseFeature"><directory>plugins/webkul/purchases/tests/Feature</directory></testsuite>
    <testsuite name="ManufacturingFeature"><directory>plugins/webkul/manufacturing/tests/Feature</directory></testsuite>
    <testsuite name="ProjectFeature"><directory>plugins/webkul/projects/tests/Feature</directory></testsuite>
    <testsuite name="PartnerFeature"><directory>plugins/webkul/partners/tests/Feature</directory></testsuite>
    <testsuite name="ProductFeature"><directory>plugins/webkul/products/tests/Feature</directory></testsuite>
    <testsuite name="SupportFeature"><directory>plugins/webkul/support/tests/Feature</directory></testsuite>
    <testsuite name="EmployeeFeature"><directory>plugins/webkul/employees/tests/Feature</directory></testsuite>
</testsuites>
```

### Domain Test Distribution

A comprehensive audit of `plugins/webkul/` reveals **175 test files** (`*Test.php`), distributed across the 11 suites:

```
Test Files Distribution:
  41  accounts       (Refund, Invoice, Bill, Tax, CashRounding, CompanyIsolation, Scoping, Currency)
  40  inventories    (Operations, Moves, Quantities, Warehouses, Locations, Scrap)
  16  sales          (Quotations, Orders, Customers, Invoicing, Pricing)
  16  products       (Categories, Variants, Attributes, Barcodes, Inventory)
  14  purchases      (Requisitions, Purchase Orders, Vendor Agreements, Approvals)
  12  support        (Test helpers, system utilities, settings, base components)
   9  partners       (Contacts, Addresses, Customers, Vendors)
   8  projects       (Tasks, Stages, Milestones, Project Management)
   8  accounting     (Journal Entries, Fiscal Years, Ledgers, Reports)
   7  manufacturing  (Work Orders, Bills of Materials, Production, Routings)
   4  employees      (Departments, Positions, Employees, Contracts)
 ---
 175  Total PHP Feature Test Files
```

### Critical Architecture Observations

1. **Feature Test Concentration**:
   - An exhaustive filesystem search confirms that **zero `Unit/` test directories exist** in the repository.
   - All 175 test files reside under `tests/Feature` within plugin directories.
2. **Zero Pest Architecture Tests**:
   - A codebase-wide grep confirms that Pest architecture testing (`arch()`) is **not configured**. Architectural boundaries and layering invariants are not evaluated via automated tests.
3. **Plugin Test Coverage Distribution**:
   - Automated PHP test coverage is currently concentrated in 11 of the 28 plugin packages located in `plugins/webkul/`.
   - 17 plugins do not currently have corresponding PHP feature test suites: `analytics`, `barcode`, `blogs`, `chatter`, `contacts`, `fields`, `full-calendar`, `invoices`, `maintenance`, `payments`, `plugin-manager`, `recruitments`, `security`, `table-views`, `time-off`, `timesheets`, `website`. (Some, such as `website`, are partially covered by Playwright).

### Test Bootstrapping & Database Isolation

- **Pest Configuration ([`tests/Pest.php`](../../tests/Pest.php))**:
  - Binds tests to [`Tests\TestCase`](../../tests/TestCase.php).
  - Uses `Illuminate\Foundation\Testing\DatabaseTransactions` to wrap each test execution in a rollback transaction.
  - Applies to `'Feature'` and `'../plugins/*/*/tests/Feature'`.
- **Test Setup ([`tests/TestCase.php`](../../tests/TestCase.php))**:
  - Every test invokes `\TestBootstrapHelper::ensureERPInstalled()`.
- **Test Bootstrap Helper ([`plugins/webkul/support/tests/Helpers/TestBootstrapHelper.php`](../../plugins/webkul/support/tests/Helpers/TestBootstrapHelper.php))**:
  - Executes `migrate:fresh --force` and `erp:install --force` once during suite initialization.
  - Dynamically runs plugin seeders (`projects`, `sales`, `inventories`, `accounts`, `products`, `manufacturing`).
  - Contains full support for parallel testing via `TEST_TOKEN` (automatically provisioning isolated worker databases `aureuserp_p{token}` for MySQL and PostgreSQL and separate compiled view paths).

---

## 7. Test Execution Model

### 1. Pest Test Execution (Backend)

- **CI Command**: `vendor/bin/pest --colors=always` (configured in `pest_tests.yml` line 100).
- **Parallelization**: **Single-process execution**. Although `brianium/paratest` is present in `composer.lock` and `TestBootstrapHelper` provides worker database provisioning for `TEST_TOKEN`, the workflow runs Pest sequentially without `--parallel`.
- **Database Initialization**:
  ```bash
  php artisan erp:install \
    --force \
    --admin-name="Example" \
    --admin-email="admin@example.com" \
    --admin-password="admin123"
  ```
- **Environment Injections**:
  ```bash
  sed -i "s|^\(APP_ENV=\s*\).*$|\1testing|" .env
  sed -i "s|^\(DB_CONNECTION=\s*\).*$|\1${{ matrix.db }}|" .env
  sed -i "s|^#\?\s*DB_HOST=.*|DB_HOST=127.0.0.1|" .env
  sed -i "s|^#\?\s*DB_PORT=.*|DB_PORT=${{ matrix.db-port }}|" .env
  sed -i "s|^#\?\s*DB_DATABASE=.*|DB_DATABASE=aureuserp|" .env
  sed -i "s|^#\?\s*DB_USERNAME=.*|DB_USERNAME=${{ matrix.db-user }}|" .env
  sed -i "s|^#\?\s*DB_PASSWORD=.*|DB_PASSWORD=${{ matrix.db-password }}|" .env
  ```
- **Process Isolation**: Handled via `DatabaseTransactions` on a pre-migrated schema.

### 2. Playwright Test Execution (E2E Browser)

- **CI Command**:
  ```bash
  npx playwright test --config=playwright.config.ts --shard=${{ matrix.shard_index }}/${{ matrix.shard_total }}
  ```
- **Working Directory**: `tests/e2e-pw`
- **Application Server Lifecycle**:
  - Web server started via `php artisan serve --host=0.0.0.0 --port=8000 > server.log 2>&1 &`.
  - Healthcheck polling waits up to 30 seconds for HTTP 200 response on `http://127.0.0.1:8000`.
- **Database Readiness Verification**:
  - Shell poll loop (up to 30 iterations, 3-second sleep) verifies both CLI ping (`mysqladmin ping` / `pg_isready`) AND active PHP PDO database connection before running tests.
- **Reporting & Report Merging**:
  - Test shards output blob reports to `tests/e2e-pw/blob-report`.
  - Shard artifacts uploaded via `actions/upload-artifact@v4` with name pattern `blob-report-${{ matrix.db }}-${{ matrix.shard_index }}`.
  - Dependent job `merge_playwright_reports` runs with condition `if: ${{ always() && !cancelled() }}`, downloads all shard blobs, merges them via `npx playwright merge-reports --reporter html ./all-blob-reports`, and uploads the final HTML report.

### 3. Translation Parity Execution

- **CI Command**: `php artisan translations:check --details`
- **Working Directory**: Root repository directory.
- **Database Dependency**: None (database is neither started nor migrated; uses `.env.example` with generated `APP_KEY`).
- **Execution Mechanism**: Iterates over all plugins in `plugins/webkul/`, discovers language files in `/src/Resources/lang` and `/resources/lang`, compares dictionary keys against the `en` baseline, and returns exit code `1` (`Command::FAILURE`) if discrepancies exist.

---

## 8. Runtime Matrix

### PHP Matrix Audit

| Component | Declared Requirement | CI Tested Versions | Production Docker Version | Status / Alignment |
| :--- | :--- | :--- | :--- | :--- |
| **`composer.json`** | `"php": "^8.3"` | N/A | N/A | High-level dependency contract |
| **`pest_tests.yml`** | N/A | `8.3` | N/A | **Single version testing** |
| **`playwright_tests.yml`** | N/A | `8.3` | N/A | **Single version testing** |
| **`translations_check.yml`** | N/A | `8.3` | N/A | **Single version testing** |
| **`docker/production/Dockerfile`** | N/A | N/A | `8.4` (`ARG PHP_VERSION=8.4`) | **Runtime Discrepancy (Finding CI-007)** |

#### Discrepancy Analysis

- The repository dependency constraint `"php": "^8.3"` allows execution on PHP 8.3 and PHP 8.4.
- However, all 3 CI workflows test strictly on PHP 8.3.
- The production container image ([`docker/production/Dockerfile`](../../docker/production/Dockerfile#L4)) defaults to PHP 8.4.
- The current CI runtime matrix validates PHP 8.3, while the production Dockerfile builds PHP 8.4. PHP 8.4 production behavior is therefore not validated by the current CI runtime matrix.

### PHP Extensions Configured in CI

The setup step `shivammathur/setup-php@v2` installs the following extensions:
- Core: `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `zip`
- Database-specific: `${{ matrix.php-db-ext }}` (`pdo_mysql` or `pdo_pgsql`)
- Tools: `composer:v2`
- PHP INI: `error_reporting=E_ALL`

### Node.js Runtime

- **Playwright CI (`playwright_tests.yml`)**: Pinned to Node.js `22.13.1` (`node-version: ["22.13.1"]`).
- **Production Container (`docker/production/Dockerfile`)**: Targets Node.js `22` via Nodesource `setup_22.x`.

---

## 9. Database Matrix

The CI configuration currently runs automated test validation against both supported database engines across all core test workflows:

```
+──────────────────────────┬─────────────────────────────┬─────────────────────────────+
| Dimension                | MySQL Matrix Node           | PostgreSQL Matrix Node      |
+──────────────────────────┼─────────────────────────────┼─────────────────────────────+
| Database Identifier      | mysql                       | pgsql                       |
| Engine Label             | MySQL                       | PostgreSQL                  |
| Container Image          | mysql:8.0                   | postgres:16                 |
| Default Port             | 3306                        | 5432                        |
| Credentials              | root / root                 | postgres / postgres         |
| Database Name            | aureuserp                   | aureuserp                   |
| Service Healthcheck      | mysqladmin ping             | pg_isready                  |
| Health Interval / Timeout| 10s / 5s                    | 10s / 5s                    |
| Health Retries           | 5 (Pest) / 10 (Playwright)  | 5 (Pest) / 10 (Playwright)  |
| PHP Extension            | pdo_mysql                   | pdo_pgsql                   |
+──────────────────────────┴─────────────────────────────┴─────────────────────────────+
```

### Full Matrix Verification

- In `pest_tests.yml`, both `mysql` and `pgsql` execute the entire 11-suite Pest test run.
- In `playwright_tests.yml`, both `mysql` and `pgsql` execute all 6 browser shards.
- Matrix failure isolation is configured via `strategy: fail-fast: false`, ensuring a failure in MySQL does not cancel the PostgreSQL verification run, providing complete comparative diagnostics.

---

## 10. Dependency Installation Audit

### Inconsistent `composer install` Invocations

The audit revealed variations in how Composer dependencies are installed and cached across workflows:

```
+──────────────────────────┬─────────────────────────────────────────────────┬────────────────────────────────+
| Workflow                 | Composer Command Line                           | Cache Strategy                 |
+──────────────────────────┼─────────────────────────────────────────────────┼────────────────────────────────+
| pest_tests.yml           | composer install                                | NONE (No cache configured)     |
| playwright_tests.yml     | composer install --no-scripts --no-interaction  | ~/.composer/cache AND vendor   |
|                          | --prefer-dist --optimize-autoloader             | (actions/cache@v4)             |
| translations_check.yml   | composer install --no-interaction --prefer-dist | vendor (actions/cache@v4)      |
+──────────────────────────┴─────────────────────────────────────────────────┴────────────────────────────────+
```

### Dependency Observations

1. **Composer Strategy Variations**:
   - Different Composer installation strategies were observed across workflows. `pest_tests.yml` invokes bare `composer install` without `--no-interaction`, `--prefer-dist`, or `actions/cache`.
   - In contrast, `playwright_tests.yml` caches `~/.composer/cache` and `vendor`, running `composer install --no-scripts --no-interaction --prefer-dist --optimize-autoloader`.
   - Standardizing dependency installation and caching across all workflows is recommended to improve consistency and CI performance.
2. **`playwright_tests.yml` Script Suppression**:
   - Passes `--no-scripts`, suppressing post-autoload dumps (`package:discover`, `filament:upgrade`).
   - Relies on subsequent manual artisan commands.
3. **Frontend Assets in Root `package.json`**:
   - Root `package.json` specifies Vite, Tailwind CSS v4, and Axios.
   - **Root frontend assets are not installed or built in CI PR workflows**. Neither `npm install` nor `npm run build` is invoked in `pest_tests.yml` or `playwright_tests.yml`.
   - Browser tests execute against Filament panel assets bundled with packages, while production asset building occurs in `docker/production/Dockerfile`.

---

## 11. Frontend / Browser Testing

### Playwright Architecture ([`tests/e2e-pw/playwright.config.ts`](../../tests/e2e-pw/playwright.config.ts))

- **Test Directory**: `./tests` inside `tests/e2e-pw`
- **Output Directory**: `./test-results`
- **Browser Target**: Desktop Chromium (`devices["Desktop Chrome"]`). WebKit and Firefox are **not configured**.
- **Timeouts**:
  - Test Timeout: `420,000 ms` (7 minutes per test).
  - Assertion Timeout (`expect`): `50,000 ms` (50 seconds).
  - Action Timeout: `30,000 ms` (30 seconds).
- **Execution Concurrency**:
  - `workers: 1`: Tests run serially within each shard to avoid session, database, and state collision.
  - `fullyParallel: true`: Enabled in CI to allow test file scheduling across shards.
  - `retries`: `1` retry enabled in CI (`process.env.CI ? 1 : 0`).
  - `forbidOnly`: `true` in CI (prevents accidental commit of `test.only`).

### Playwright Test Inventory (19 Specs across 7 Modules)

```
tests/e2e-pw/tests/
  ├── 01_plugins/       01_plugins.spec.ts
  ├── 02_companies/     01_companies.spec.ts
  ├── 03_users/         01_users.spec.ts
  ├── 04_sales/         01_salesCustomers.spec.ts, 02_salesProducts.spec.ts,
  │                     03_salesQuotations.spec.ts, 04_salesFlow.spec.ts
  ├── 05_inventories/   01_inventorySettings.spec.ts, 03_inventoryProducts.spec.ts,
  │                     04_inventoryOperations.spec.ts, 05_inventoryFlow.spec.ts,
  │                     06_inventoryConfigurations.spec.ts
  ├── 05_purchases/     01_purchaseVendors.spec.ts, 02_purchaseProducts.spec.ts,
  │                     03_purchaseREQ.spec.ts, 04_purchaseAgreement.spec.ts,
  │                     05_purchaseFlow.spec.ts
  └── 06_website/       01_websitePages.spec.ts, 02_websiteBlogs.spec.ts
```

### Artifact & Failure Diagnostics

- **Screenshots**: `only-on-failure` (full page captures).
- **Traces**: `on-first-retry` (Playwright trace recording for post-mortem debugging).
- **Videos**: `on-first-retry` (session video recording).
- **CI Failure Upload**: Uploads `tests/e2e-pw/test-results` and `storage/logs/laravel.log` on failure with 1-day retention.

---

## 12. Translation Testing

### Audit of `translations:check` Command

The translation verification pipeline is implemented in [`plugins/webkul/plugin-manager/src/Console/Commands/FindMissingTranslations.php`](../../plugins/webkul/plugin-manager/src/Console/Commands/FindMissingTranslations.php):

- **Canonical Locale**: `en` (English is the source of truth).
- **Supported Locales**: Discovered dynamically via `config('app.supported_locales')`.
- **Target Directories**:
  - Root: `lang/`
  - All plugins: `plugins/webkul/*/{src/Resources/lang,resources/lang}`
- **Verification Criteria**:
  1. Existence of locale subdirectories.
  2. Parity of language files against the English baseline.
  3. Parity of nested translation keys inside PHP array return files.
- **Failure Semantics**:
  - Any missing file, directory, or key sets `$this->hasError = true`.
  - Command returns `self::FAILURE` (code 1).
- **Workflow Failure Behavior**: A translation inconsistency causes the `translations_check` job to fail with exit code 1.
- **Merge Blocking**: `Check translation files consistency` is strictly required by the active `develop` and `master` rulesets (**`VERIFIED`**, GitHub API inspection on 2026-09-19).

---

## 13. Static Analysis & Quality Checks

A systematic audit of quality tools, static analyzers, and linters yielded the following classifications:

| Tool | Presence in Repository | Executed in CI | Classification | Evidence Source |
| :--- | :--- | :--- | :--- | :--- |
| **Laravel Pint** | Yes (`laravel/pint: ^1.27` in `require-dev`, [`pint.json`](../../pint.json) present) | **NO** | **PRESENT BUT NOT EXECUTED IN CI** | Present in root; omitted from all `.github/workflows/*.yml` |
| **PHPStan** | Transitive (`phpstan/phpstan: 2.2.5` via `filament/upgrade` $\to$ `rector`) | **NO** | **PRESENT BUT NOT EXECUTED IN CI** | No `phpstan.neon`; not invoked in CI |
| **Larastan** | Absent | **NO** | **NOT CONFIGURED** | Not present in `composer.json` or `composer.lock` |
| **Psalm** | Absent | **NO** | **NOT CONFIGURED** | Not present in repository |
| **PHP CS Fixer** | Wrapped via Pint | **NO** | **NOT CONFIGURED** | Internal engine of Pint |
| **ESLint** | Absent | **NO** | **NOT CONFIGURED** | Not present in `package.json` |
| **Prettier** | Absent | **NO** | **NOT CONFIGURED** | Not present in `package.json` |
| **Rector** | Transitive (`rector/rector` via `filament/upgrade`) | **NO** | **NOT CONFIGURED** | No standalone configuration |
| **Deptrac** | Absent | **NO** | **NOT CONFIGURED** | Not present in repository |
| **Pest Arch Tests**| Absent | **NO** | **NOT CONFIGURED** | Zero `arch()` references in test codebase |
| **Translations Check**| Custom Artisan command | **YES** | **PRESENT AND EXECUTED IN CI** | Executed in `translations_check.yml` |

### Code Quality Observation

Laravel Pint is configured for local development via [`pint.json`](../../pint.json) and referenced in [`AGENTS.md`](../../AGENTS.md), but is not currently executed as part of CI validation. Adding a CI style-check step is recommended if project governance requires automated style enforcement.

---

## 14. Coverage Governance

### Audit Findings

1. **Coverage Driver**: **Absent / Not Configured**.
   - Neither `pcov` nor `xdebug` is included in the `extensions:` list of `shivammathur/setup-php@v2` across any workflow.
2. **Test Runner Coverage Flags**:
   - `pest_tests.yml` executes `vendor/bin/pest --colors=always`. No coverage parameters (`--coverage`, `--coverage-clover`, `--min=...`) are passed.
3. **`phpunit.xml` Coverage Source Scope**:
   - The `<source>` section of `phpunit.xml` configures:
     ```xml
     <source>
         <include>
             <directory>app</directory>
         </include>
     </source>
     ```
   - **Scope Discrepancy**: It includes **only `app`**, omitting `plugins/webkul/`. `plugins/webkul/` contains substantial domain and business logic that is excluded from the configured PHPUnit coverage source scope.
4. **External Services & Badges**:
   - No coverage services (Codecov, Coveralls, Scrutinizer) are integrated.
   - No minimum coverage thresholds are established or enforced.
5. **Classification**: **`NOT CONFIGURED`**.

---

## 15. Permissions & Security

### Workflow Permissions Audit

Every active workflow explicitly defines top-level permissions adhering to the principle of least privilege:

```yaml
permissions:
  contents: read
```

- **`pest_tests.yml`**: `contents: read`
- **`playwright_tests.yml`**: `contents: read`
- **`translations_check.yml`**: `contents: read`
- **`docker_publish.yml`**: `contents: read` (top-level and job-level)

### Supply Chain & Secrets Audit

1. **Secrets Handling on PRs**:
   - None of the PR-facing validation workflows (`pest_tests`, `playwright_tests`, `translations_check`) reference repository secrets.
   - Secrets are utilized exclusively within `docker_publish.yml` (`DOCKERHUB_USERNAME`, `DOCKERHUB_TOKEN`), which triggers only on version tags (`v*`) or authorized manual dispatch.
2. **`pull_request_target` Trigger Audit**:
   - No workflow uses `pull_request_target`. This reduces exposure to the specific security risks associated with executing untrusted pull-request workflows in the elevated `pull_request_target` context.
3. **Referenced GitHub Actions Dependencies**:
   All 10 referenced GitHub Actions utilized across workflows are pinned via major version tags, not immutable commit SHAs:

```
+─────────────────────────────────┬──────────┬─────────────────────────┬─────────────────────────────┬───────────────────+
| Action                          | Version  | Maintainer / Origin     | Purpose                     | Pinned by SHA?    |
+─────────────────────────────────┼──────────┼─────────────────────────┼─────────────────────────────┼───────────────────+
| actions/checkout                | v4       | GitHub                  | Repository checkout         | NO (Major Tag)    |
| shivammathur/setup-php          | v2       | Community               | PHP runtime setup           | NO (Major Tag)    |
| actions/cache                   | v4       | GitHub                  | Dependency / browser cache  | NO (Major Tag)    |
| actions/setup-node              | v4       | GitHub                  | Node.js runtime setup       | NO (Major Tag)    |
| actions/upload-artifact         | v4       | GitHub                  | Artifact preservation       | NO (Major Tag)    |
| actions/download-artifact       | v4       | GitHub                  | Artifact consolidation      | NO (Major Tag)    |
| docker/setup-qemu-action        | v3       | Docker                  | QEMU multi-arch emulation   | NO (Major Tag)    |
| docker/setup-buildx-action      | v3       | Docker                  | Docker Buildx builder setup | NO (Major Tag)    |
| docker/login-action             | v3       | Docker                  | Docker Hub authentication   | NO (Major Tag)    |
| docker/build-push-action        | v6       | Docker                  | Container build & push      | NO (Major Tag)    |
+─────────────────────────────────┴──────────┴─────────────────────────┴─────────────────────────────┴───────────────────+
```

---

## 16. Concurrency & Failure Semantics

### Concurrency Controls

All 3 PR-facing validation workflows configure identical concurrency controls:

```yaml
concurrency:
  group: ${{ github.workflow }}-${{ github.ref }}
  cancel-in-progress: true
```

- **Behavior on Pull Requests**: When a developer pushes a new commit to an active PR, running workflows on the previous commit are cancelled, conserving runner capacity.
- **Behavior on Branch Pushes**: Successive pushes to `develop` cancel prior in-progress runs.
- **Release Isolation**: `docker_publish.yml` omits concurrency groups, preventing concurrent tag builds from terminating each other prematurely.

### Failure Semantics & Error Handling

1. **Step-Level Failure**:
   - No workflow uses `continue-on-error: true` on test or validation steps.
   - Any non-zero exit code immediately halts the executing job and marks it as failed.
2. **Matrix Isolation**:
   - Both `pest_tests.yml` and `playwright_tests.yml` declare `strategy: fail-fast: false`.
   - If MySQL fails on Shard 1, PostgreSQL shards and remaining MySQL shards continue running to completion.
3. **Report Merging Resilience**:
   - In `playwright_tests.yml`, `merge_playwright_reports` declares `if: ${{ always() && !cancelled() }}`.
   - The HTML report is compiled even when individual browser shards fail, ensuring diagnostics are available for troubleshooting.
4. **Execution Timeouts**:
   - No workflow explicitly declares `timeout-minutes:`.
   - Workflows fall back to GitHub Actions default job timeout of **360 minutes (6 hours)**.

---

## 17. Artifacts & Diagnostics

```
+──────────────────────────┬──────────────────────┬──────────────────────┬───────────────────────┬───────────+
| Workflow                 | Artifact Name        | Upload Condition     | Contents              | Retention |
+──────────────────────────┼──────────────────────┼──────────────────────┼───────────────────────┼───────────+
| pest_tests.yml           | NONE                 | N/A                  | None (Console output) | N/A       |
| playwright_tests.yml     | blob-report-*        | !cancelled()         | Shard test blobs      | 1 day     |
| playwright_tests.yml     | playwright-failures-*| failure()            | test-results, logs    | 1 day     |
| playwright_tests.yml     | playwright-report-*  | always() && !canc... | Merged HTML report    | 1 day     |
| translations_check.yml   | NONE                 | N/A                  | None (Console output) | N/A       |
+──────────────────────────┴──────────────────────┴──────────────────────┴───────────────────────┴───────────+
```

### Diagnostic Observations

- **Backend Pest Diagnostics**: Backend CI currently does not publish dedicated failure diagnostics such as JUnit reports or Laravel logs; engineers inspect console logs for test failure output. Uploading such artifacts on failure could improve troubleshooting.
- **Playwright Diagnostics**: `playwright_tests.yml` preserves screenshots, trace archives (`trace.zip`), video recordings, and Laravel application logs on failure, enabling visual post-mortem debugging.

---

## 18. GitHub Status Check Governance

### The Fundamental Governance Distinction

A central mandate of Operational Stage O6 is to strictly maintain the architectural boundary defined in Operational Stage O5:

```
Workflow Exists in Repository (.github/workflows/)
                     ≠
Workflow Triggers on Pull Request Event
                     ≠
Workflow Job Executes Successfully on Runner
                     ≠
GitHub Ruleset Requires Check Before Merge
                     ≠
GitHub Blocks PR Merge When Check Fails
```

1. **Workflow Execution**: Proven by YAML configuration and GitHub Actions runner dispatch.
2. **GitHub Merge Enforcement**: Governed strictly by GitHub server-side Branch Protection Rules or Repository Rulesets targeting `develop`.
3. **Current Repository Reality**:
   - Workflows execute on Pull Requests targeting `develop` and `master` (**`VERIFIED`**).
   - Active GitHub rulesets `protect-develop` (`23566563`) and `protect-release-master` (`23566566`) require the four contexts listed below, use strict required-status-check policy, and have no bypass actors (**`VERIFIED`**, GitHub API inspection on 2026-09-19).
   - Pull Request #12 validated the aggregate Playwright gate and entered `develop` as merge commit `439950402663107c42ffd7d7d3570c3d2e4ccc07` after all required checks succeeded (**`VERIFIED`**).

### Evaluated Status Check Contexts

The current workflow configuration produces 18 distinct CI check contexts when evaluated across all matrix dimensions and shards. GitHub rulesets require the two Pest contexts, the translation context, and the stable Playwright aggregate context; the remaining contexts execute as diagnostic detail and are not individually required:

```
Pest Tests Workflow (2 checks):
  - PHP 8.3 | MySQL test on ubuntu-latest
  - PHP 8.3 | PostgreSQL test on ubuntu-latest

Playwright Tests Workflow (15 checks):
  - MySQL | Shard 1 of 6
  - MySQL | Shard 2 of 6
  - MySQL | Shard 3 of 6
  - MySQL | Shard 4 of 6
  - MySQL | Shard 5 of 6
  - MySQL | Shard 6 of 6
  - PostgreSQL | Shard 1 of 6
  - PostgreSQL | Shard 2 of 6
  - PostgreSQL | Shard 3 of 6
  - PostgreSQL | Shard 4 of 6
  - PostgreSQL | Shard 5 of 6
  - PostgreSQL | Shard 6 of 6
  - report (mysql)
  - report (pgsql)
  - Playwright E2E Gate

Translations Check Workflow (1 check):
  - Check translation files consistency
```

### Shard Fragility in GitHub Rulesets

- Direct enforcement of individual shard check names (e.g., `MySQL | Shard 1 of 6`) creates ruleset brittleness.
- If the shard total is adjusted (e.g., from 6 to 8 shards), GitHub rulesets configured with old names would block PRs.
- **Implemented Solution**: `playwright_e2e_gate` exposes the stable `Playwright E2E Gate` context. It runs after the matrix test and report jobs and fails unless both aggregate `needs` results are `success` (`.github/workflows/playwright_tests.yml`, lines 257–272). GitHub rulesets require this context rather than individual shards (**`VERIFIED`**).

---

## 19. Required Check Candidates

Based on workflow reliability, execution cost, and regression impact, candidate status checks for `develop` are classified as follows:

| Status Check Name | Workflow Origin | Classification | Governance Rationale |
| :--- | :--- | :--- | :--- |
| `PHP 8.3 \| MySQL test on ubuntu-latest` | `pest_tests.yml` | **REQUIRED / VERIFIED** | Required by both active rulesets; core backend verification against the default database engine. |
| `PHP 8.3 \| PostgreSQL test on ubuntu-latest`| `pest_tests.yml` | **REQUIRED / VERIFIED** | Required by both active rulesets; backend verification against the supported enterprise database engine. |
| `Check translation files consistency` | `translations_check.yml` | **REQUIRED / VERIFIED** | Required by both active rulesets; fast parity check preventing multilingual dictionary corruption. |
| `Playwright E2E Gate` (Aggregate Job) | `playwright_tests.yml` | **REQUIRED / VERIFIED** | Required by both active rulesets; succeeds only after the aggregate test-shard and report results both succeed. |
| Individual Shard Checks (`Shard 1..6`) | `playwright_tests.yml` | **NOT RECOMMENDED** | Highly brittle in branch protection rulesets upon shard scaling. |
| `Laravel Pint Code Style` | New Workflow | **CANDIDATE** | Fast style linter to enforce `pint.json` formatting before merge. |
| `docker_publish.yml` Jobs | `docker_publish.yml` | **NOT REQUIRED** | Release deployment workflow; does not execute on PRs. |

---

## 20. Merge Queue / `merge_group`

### Analysis of Merge Queue Compatibility

GitHub Merge Queues allow automated, serialized merging of pull requests. A merge queue triggers the `merge_group` event instead of standard `pull_request` events.

- **Current State**:
  - `playwright_tests.yml` includes `merge_group:`.
  - `pest_tests.yml` **omits** `merge_group:`.
  - `translations_check.yml` **omits** `merge_group:`.
- **Governance Implication**:
  - The current trigger configuration is asymmetric. If GitHub Merge Queue is enabled for protected branches, Pest and translation validation would not execute through `merge_group` unless explicitly configured.
  - If GitHub rulesets were to require Pest tests to pass before merge in a merge queue, the merge queue could not proceed because the required check would not trigger.
  - **Remediation**: Before enabling GitHub Merge Queues, all PR gating workflows should align triggers to include `merge_group:` if queue validation is adopted.

---

## 21. Docker CI Boundary

[`docker_publish.yml`](../../.github/workflows/docker_publish.yml) operates strictly outside the continuous integration testing boundary:

- **Trigger Scope**:
  - `push` on tags matching `v*` (versioned release tags).
  - Manual execution via `workflow_dispatch` with input parameters `app_ref` and `image_tag`.
- **Function**: Builds production container images for `linux/amd64` and `linux/arm64` using QEMU and Buildx, publishing to Docker Hub (`webkul/aureuserp`).
- **Separation of Concerns**: Docker publishing is release and deployment automation. It performs no automated software testing and does not participate in PR gating.

---

## 22. Governance Classification

Every governance control and observation in this document is classified according to the canonical project taxonomy:

- **`VERIFIED`**: Direct repository configuration or executed workflow independently confirms the state.
- **`POLICY`**: Explicitly adopted in project documentation, but technical enforcement is unverified.
- **`HISTORICAL PRACTICE`**: Observable in commit history or existing workflow structure.
- **`RECOMMENDED`**: A verified technical improvement identified by audit.
- **`PENDING DECISION`**: A technical question requiring maintainer resolution.
- **`PENDING AUTHORIZATION`**: A desired configuration requiring administrative authorization prior to change.
- **`DEFERRED`**: Technical responsibility formally assigned to a subsequent roadmap phase.
- **`NOT CONFIGURED`**: The mechanism was audited and does not exist in the repository.
- **`NOT VERIFIED`**: Remote GitHub platform configuration could not be independently inspected via API.
- **`GAP`**: A material discrepancy between an authoritative requirement or intended architecture and executable configuration.

---

## 23. CI Governance Findings

The audit identified the following evidence-based CI governance findings:

### CI-001: Server-Side Required Status Check Enforcement
- **Classification**: **`VERIFIED`** / **`IMPLEMENTED`**
- **Evidence**: GitHub API inspection on 2026-09-19 confirms that active rulesets `23566563` (`develop`) and `23566566` (`master`) strictly require the two Pest contexts, the translation context, and `Playwright E2E Gate`, each bound to GitHub Actions integration `15368`. PR #12 ran all four successfully before its merge commit `439950402663107c42ffd7d7d3570c3d2e4ccc07`.
- **Impact**: The selected CI contexts are now GitHub merge requirements on both protected branches.
- **Residual Boundary**: This records configured required-check enforcement. It does not claim that every possible workflow, code-style check, coverage threshold, or future workflow context is required.
- **Owning Operational Stage**: O5 / O6 (**complete for the selected gate set**).

### CI-002: `merge_group` Trigger Asymmetry Across Core Workflows
- **Classification**: **`RECOMMENDED`** (Conditional on Merge Queue adoption)
- **Evidence**: `playwright_tests.yml` line 8 includes `merge_group:`, while `pest_tests.yml` and `translations_check.yml` omit it.
- **Impact**: The current trigger configuration is asymmetric. If GitHub Merge Queue is enabled for protected branches, Pest and translation validation would not execute through `merge_group` unless explicitly configured.
- **Recommendation**: Align `merge_group:` triggers across `pest_tests.yml` and `translations_check.yml` if GitHub Merge Queue functionality is adopted.
- **Owning Operational Stage**: O6 (`RECOMMENDED`).

### CI-003: Inconsistent Composer Installation Invocations and Caching
- **Classification**: **`RECOMMENDED`**
- **Evidence**: `pest_tests.yml` line 73 invokes bare `composer install` without caching, while `playwright_tests.yml` caches `~/.composer/cache` and `vendor` and uses `--no-scripts --no-interaction --prefer-dist --optimize-autoloader`.
- **Impact**: Different Composer installation strategies were observed across workflows. Standardizing dependency installation and caching may improve consistency and CI performance.
- **Recommendation**: Standardize Composer installation and caching across all workflows using `actions/cache@v4`.
- **Owning Operational Stage**: O6 (`RECOMMENDED`).

### CI-004: Code Style & Static Analysis (`laravel/pint`) Configured Locally But Omitted in CI
- **Classification**: **`RECOMMENDED`**
- **Evidence**: `laravel/pint` is in `require-dev` and [`pint.json`](../../pint.json) exists, but no workflow executes `pint --test`.
- **Impact**: Laravel Pint is configured for local development but is not currently executed as part of CI validation. Adding a CI style-check step is recommended if project governance requires automated style enforcement.
- **Recommendation**: Introduce a dedicated, fast code-quality workflow running `vendor/bin/pint --test` on pull requests.
- **Owning Operational Stage**: O6 (`RECOMMENDED`).

### CI-005: Code Coverage Untracked and Coverage Scope Misconfigured
- **Classification**: **`NOT CONFIGURED`**
- **Evidence**: No coverage driver (`pcov`/`xdebug`) is installed in CI; `phpunit.xml` line 44 restricts `<source>` exclusively to `app`, excluding `plugins/webkul/`.
- **Impact**: Complete lack of visibility into test coverage metrics across the domain plugin codebase.
- **Recommendation**: Update `phpunit.xml` `<source>` to include `plugins/webkul/*/src` and establish coverage reporting in a future phase.
- **Owning Operational Stage**: O6 (`DEFERRED`).

### CI-006: Missing Diagnostic Artifacts in Backend Pest Workflow
- **Classification**: **`RECOMMENDED`**
- **Evidence**: `pest_tests.yml` configures no `actions/upload-artifact@v4` steps.
- **Impact**: Backend CI currently does not publish dedicated failure diagnostics such as JUnit reports or Laravel logs. Uploading such artifacts on failure could improve troubleshooting.
- **Recommendation**: Add artifact upload steps for `storage/logs/laravel.log` and JUnit test reports on failure.
- **Owning Operational Stage**: O6 (`RECOMMENDED`).

### CI-007: PHP Runtime Discrepancy Between CI Matrix and Production Container
- **Classification**: **`GAP`** / **`PENDING DECISION`**
- **Evidence**: `pest_tests.yml` and `playwright_tests.yml` test exclusively on PHP `8.3`, while [`docker/production/Dockerfile`](../../docker/production/Dockerfile#L4) builds on PHP `8.4`.
- **Impact**: The current CI runtime matrix validates PHP 8.3, while the production Dockerfile builds PHP 8.4. PHP 8.4 production behavior is therefore not validated by the current CI runtime matrix.
- **Recommendation**: Align the production Dockerfile with the CI-validated version (PHP 8.3), or add PHP 8.4 to the CI test matrix.
- **Owning Operational Stage**: O6 (`PENDING DECISION`).

### CI-008: Stable Playwright Aggregate Status Check
- **Classification**: **`VERIFIED`** / **`IMPLEMENTED`**
- **Evidence**: `playwright_e2e_gate` in `.github/workflows/playwright_tests.yml` depends on the aggregate Playwright test and report jobs and exposes `Playwright E2E Gate`. PR #12 executed it successfully, and both rulesets require that context.
- **Impact**: Changes to the shard count do not require changing a ruleset context, provided the aggregate job name remains stable.
- **Residual Boundary**: Pest remains represented by its two stable matrix contexts; no broader synthetic `ci-gate` is configured.
- **Owning Operational Stage**: O6 (**complete for Playwright aggregate gating**).

### CI-009: Floating Action Version References vs. Immutable SHA Pinning
- **Classification**: **`RECOMMENDED`**
- **Evidence**: All 10 referenced GitHub Actions use major version tags (`@v4`, `@v2`, `@v3`, `@v6`) rather than full commit SHAs.
- **Impact**: Theoretical vulnerability to modified upstream action tags.
- **Recommendation**: Evaluate pinning referenced GitHub Actions to immutable commit SHAs with automated Dependabot updates.
- **Owning Operational Stage**: O6 (`RECOMMENDED`).

### CI-010: Absence of Path Filtering on Heavy Validation Workflows
- **Classification**: **`RECOMMENDED`**
- **Evidence**: No workflow configures `paths` or `paths-ignore`.
- **Impact**: Documentation-only changes trigger 17 resource-intensive CI jobs including full browser sharding.
- **Recommendation**: Add path filtering to bypass Playwright and database testing on documentation-only pull requests.
- **Owning Operational Stage**: O6 (`RECOMMENDED`).

### CI-011: Concentration of PHP Feature Test Coverage Across Domain Plugins
- **Classification**: **`HISTORICAL PRACTICE`** / **`RECOMMENDED`**
- **Evidence**: 11 of 28 plugins under `plugins/webkul/` have automated test suites (175 test files); 17 plugins contain no PHP feature tests.
- **Impact**: Automated PHP test coverage is currently concentrated in a subset of domain plugins. Progressive expansion of automated coverage is recommended as part of future testing maturity.
- **Recommendation**: Progressively expand automated test coverage across additional domain plugin packages.
- **Owning Phase**: Quality & Testing Engineering (`DEFERRED`).

---

## 24. Deferred Areas

To maintain strict boundaries across roadmap phases, the following testing and CI areas are formally deferred:

| Deferred Control Area | Owning Operational Stage / Domain | Scope and Governance Responsibilities |
| :--- | :--- | :--- |
| **Workflow YAML Modifications** | **Future Implementation — Requires Explicit Authorization** | Modifying `.github/workflows/*.yml` to implement findings (caching, `merge_group`, synthetic gates). |
| **Pest Test Suite Fixes / Refactoring** | **Domain Testing Maintenance** | Correcting flaky tests, expanding assertions, or refactoring plugin tests. |
| **Untested Plugin Test Creation** | **Domain Testing Maintenance** | Writing new feature tests for the 17 untested plugins in `plugins/webkul/`. |
| **Coverage Driver & Thresholds** | **Quality Engineering** | Configuring PCOV, Codecov, and minimum coverage thresholds. |
| **Expansion of Required Checks** | **O5 / O6 follow-up** | Evaluating additional stable contexts such as Pint only after a workflow exists and succeeds; the current four required contexts are already enforced. |
| **Upstream CI Alignment** | **O7 — Upstream Integration** | Aligning CI test workflows with upstream Webkul updates and tracking changes. |

---

## 25. Evidence Matrix

| Area | Expected State | Actual State | Classification | Evidence Source | Operational Stage |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Workflow Inventory** | 4 active workflows | 4 active workflows (`pest`, `playwright`, `translations`, `docker`) | **VERIFIED** | Directory inspection `.github/workflows/` | O6 |
| **PR Validation Triggers** | `develop`, `master` | `develop`, `master` in all 3 test workflows | **VERIFIED** | Workflows line inspection | O6 |
| **Merge Queue Compatibility** | All PR tests run on `merge_group` | Present in `playwright_tests.yml`; absent in `pest` & `translations` | **RECOMMENDED** | Inspection of `on:` in workflow files | O6 |
| **PHP Runtime (CI)** | PHP 8.3 tested | Tested exclusively on PHP 8.3 | **VERIFIED** | `pest_tests.yml`, `playwright_tests.yml` | O6 |
| **PHP Runtime (Docker)** | Matches CI runtime | Builds on PHP 8.4 (`ARG PHP_VERSION=8.4`) | **GAP** | `docker/production/Dockerfile` line 4 | O6 |
| **Database Engines** | Dual MySQL & PostgreSQL | MySQL 8.0 & PostgreSQL 16 tested in Pest & Playwright | **VERIFIED** | Services configuration in workflows | O6 |
| **Pest Framework Version** | Pest v4 | Pest `v4.7.5` on PHPUnit `12.5.30` | **VERIFIED** | `composer.lock` package inspection | O6 |
| **Unit Tests** | Present in unit directory | Zero Unit test directories; 100% Feature tests | **HISTORICAL PRACTICE** | Filesystem inspection across `plugins/` | O6 |
| **Architecture Tests** | Pest `arch()` tests active | Zero architecture tests present | **NOT CONFIGURED** | Codebase grep `arch()` | O6 |
| **Plugin PHP Test Coverage** | All plugins tested | 11 of 28 plugins tested (175 test files) | **HISTORICAL PRACTICE / RECOMMENDED** | Filesystem inspection `plugins/webkul/` | O6 |
| **Playwright Sharding** | Parallel execution | 6 shards per DB engine (12 matrix jobs total) | **VERIFIED** | `playwright_tests.yml` matrix config | O6 |
| **Playwright Report Merge** | Consolidated HTML report | `merge_playwright_reports` merges blob reports | **VERIFIED** | `playwright_tests.yml` lines 211-256 | O6 |
| **Translation Parity Check** | Automated dictionary audit | `php artisan translations:check --details` | **VERIFIED** | `translations_check.yml`, command code | O6 |
| **Code Style in CI (Pint)** | Enforced via CI | `pint.json` exists; omitted from CI workflows | **RECOMMENDED** | Root `pint.json`, workflows audit | O6 |
| **Code Coverage Driver** | Active coverage tracking | Driver absent; `<source>` omits `plugins/webkul/` | **NOT CONFIGURED** | `phpunit.xml`, `pest_tests.yml` | O6 |
| **Workflow Permissions** | Least privilege | `contents: read` explicitly declared on all workflows | **VERIFIED** | Top-level permissions in YAML | O6 |
| **Referenced Actions Pinning**| Immutable SHA pinning | Floating major version tags (`@v4`, `@v2`, etc.) | **RECOMMENDED** | `uses:` lines across workflows | O6 |
| **Concurrency Controls** | Cancel obsolete runs | `${{ github.workflow }}-${{ github.ref }}` with `cancel-in-progress: true` | **VERIFIED** | Concurrency blocks in test workflows | O6 |
| **Pest Failure Diagnostics** | Logs and artifacts saved | Zero artifacts uploaded on Pest failure | **RECOMMENDED** | `pest_tests.yml` step audit | O6 |
| **GitHub Status Check Gating**| Server-side enforcement | Both active rulesets strictly require two Pest contexts, translation consistency, and `Playwright E2E Gate` | **VERIFIED / IMPLEMENTED** | GitHub rulesets `23566563`, `23566566`; GitHub API inspection 2026-09-19; PR #12 | O5 / O6 |
| **Docker Workflow Role** | CI test gate | Release deployment automation only (tags `v*`) | **VERIFIED** | `docker_publish.yml` triggers | O6 |

---

## 26. Verification Metadata

This Continuous Integration and Testing governance specification is verified against active workflow YAML definitions, test suite sources, Composer manifests, and runtime environments.

- **Status**: `verified`
- **Source of Truth**: `repository-configuration`
- **Last Verified**: `2026-09-15`
- **Scope**: `ci-testing-governance`
- **Confidence**: `high`
