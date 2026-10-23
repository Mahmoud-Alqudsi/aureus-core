---
status: verified
source_of_truth: repository-configuration
last_verified: 2026-10-25
scope: ci-testing-governance
confidence: high
---

# CI & Testing Governance

This document defines the canonical Continuous Integration (CI) and automated testing governance specification for Aureus ERP. It establishes an evidence-based baseline of active GitHub Actions workflows, test suites, execution commands, runtime and database matrices, dependency handling, security boundaries, and the distinction between workflow execution and GitHub merge enforcement.

---

## 1. Purpose & Ownership Boundary

While [`docs/development/git-workflow.md`](git-workflow.md) defines local branching, commit conventions, and merge strategies, and [`docs/development/github-governance.md`](github-governance.md) defines server-side GitHub repository rulesets (`23566563` and `23566566`), this document exclusively owns the **Continuous Integration and automated verification layer**:

- **GitHub Actions Workflows**: All workflow definitions under [`.github/workflows/`](../../.github/workflows/), their event triggers, path-filtering fast-tracks, concurrency groups, and token permissions.
- **Runtime & Database Matrices**: PHP, Node.js, MySQL 8.0, and PostgreSQL 16 service container configurations.
- **Test Execution Architecture**: Pest v4 backend suites (`phpunit.xml`, `tests/Pest.php`, `TestBootstrapHelper`), Playwright E2E browser sharding and report merging, `translations:check`, and local pre-commit linting (`scripts/kb_lint.py`).
- **Status Check Contexts**: The 18 CI job contexts emitted by the workflows and the 4 stable contexts required for Pull Request merges.

Modifying `.github/workflows/*.yml`, `phpunit.xml`, or GitHub required status check settings requires explicit human approval under [`AGENTS.md`](../../AGENTS.md).

---

## 2. CI Architecture Overview

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
3. **Path Filtering on PR Workflows (Implemented via CI-010)**:
   - Configured path-filtering detection steps across all three PR test workflows (`pest_tests.yml`, `translations_check.yml`, `playwright_tests.yml`).
   - Documentation-only pull requests (changes exclusively within `docs/`, `*.md`, `.github/ISSUE_TEMPLATE/`, or `.github/PULL_REQUEST_TEMPLATE.md`) bypass heavy test executions while immediately reporting successful completion to satisfy required status check gates.

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

### Domain Test Distribution & Architecture Observations

1. **Canonical Per-Plugin Test Coverage Audit**:
   - Automated PHP test coverage exists in **11 of the 28** plugin packages under `plugins/webkul/`, while **17 plugins** currently have no `tests/` directory (some, such as `website`, are partially exercised by Playwright E2E specs).
   - The authoritative per-plugin test file breakdown is maintained solely in [`docs/ai/testing-rules.md`](../ai/testing-rules.md#2-fresh-repository-test-coverage-audit). Do not maintain a duplicate per-plugin count list in this document.
2. **Feature Test Concentration**:
   - **Zero `Unit/` test directories exist** in the repository; all plugin tests reside under `tests/Feature` (with shared helpers/fakes under `tests/Helpers` or `tests/Fakes`).
3. **Zero Pest Architecture Tests**:
   - Pest architecture testing (`arch()`) is **not configured**. Architectural boundaries and layering invariants are enforced via code review and `scripts/kb_lint.py` rather than `arch()` test suites.

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

### Playwright Test Inventory

The complete spec-to-functional-scope, page-object, and coverage-boundary atlas is maintained in [`testing-strategy.md`](testing-strategy.md). The current source tree contains 19 specs across 7 functional modules. This CI document owns how those specs are installed, sharded, executed, reported, and gated; it does not duplicate the per-spec inventory.

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
| **Knowledge Base Linter**| Custom Python script ([`scripts/kb_lint.py`](../../scripts/kb_lint.py)) | **NO** | **PRESENT AND EXECUTED LOCALLY (PRE-COMMIT)** | Versioned Git hook [`scripts/hooks/pre-commit`](../../scripts/hooks/pre-commit) |

### Code Quality Observation

Laravel Pint is configured for local development via [`pint.json`](../../pint.json) and referenced in [`AGENTS.md`](../../AGENTS.md), but is not currently executed as part of CI validation. Adding a CI style-check step is recommended if project governance requires automated style enforcement.

### Knowledge Base Linting & Git Pre-Commit Hook

A dedicated static consistency checker for the documentation tree and AI guidance is provided in [`scripts/kb_lint.py`](../../scripts/kb_lint.py). It validates Markdown links, front matter, banned terminology, citation targets, numeric consistency, and skill definitions without modifying files.

To prevent documentation regressions locally before they are committed, the repository ships a versioned Git pre-commit hook at [`scripts/hooks/pre-commit`](../../scripts/hooks/pre-commit). The hook inspects staged changes via `git diff --cached --name-only` and runs:

```bash
python3 scripts/kb_lint.py --root . --strict
```

whenever a commit touches `docs/**`, `AGENTS.md`, or `agents/**` (including `.agents/**`), blocking the commit on a non-zero exit code.

#### One-Time Developer Setup

Git does not execute repository-tracked hooks automatically. Developers must configure Git to use the versioned hook directory right after cloning:

```bash
git config core.hooksPath scripts/hooks
```

> [!NOTE]
> In the current solo-maintainer setup, running `kb_lint.py` locally via the versioned pre-commit hook provides immediate feedback and catches documentation regressions before commit. If external contributors join the project in the future, the same check can be promoted to a GitHub Actions workflow under `.github/workflows/`.

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

A central architectural invariant is the strict separation between workflow definitions and server-side merge gating:

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

1. **Workflow Execution**: Governed by `.github/workflows/*.yml` and GitHub Actions runner dispatch.
2. **GitHub Merge Enforcement**: Governed by server-side GitHub Repository Rulesets `protect-develop` (`23566563`) and `protect-release-master` (`23566566`), documented in [`docs/development/github-governance.md`](github-governance.md#4-active-github-rulesets--merge-settings).
3. **Current Verified State (`[VERIFIED]`)**:
   - Workflows execute on Pull Requests targeting `develop` and `master`.
   - Active GitHub rulesets `23566563` and `23566566` strictly require the four contexts listed below from GitHub Actions integration `15368`, with no bypass actors.
   - Documentation-only Pull Requests (`docs/**`, `*.md`, `.github/ISSUE_TEMPLATE/*`, `.github/PULL_REQUEST_TEMPLATE.md`) use path-filtering detection steps across `pest_tests.yml`, `translations_check.yml`, and `playwright_tests.yml` to bypass heavy matrix execution while still reporting passing results to all four required status check contexts.

### Evaluated Status Check Contexts

The workflow configuration emits 18 distinct CI check contexts across all matrix dimensions and shards. Only the 4 stable contexts marked below are required by the active GitHub rulesets; the remaining 14 shard and report contexts execute as diagnostic detail:

| Status Check Context | Workflow File | Server-Side Ruleset Status | Purpose |
| :--- | :--- | :--- | :--- |
| `PHP 8.3 \| MySQL test on ubuntu-latest` | `pest_tests.yml` | **Required (`23566563`, `23566566`)** | Core backend Pest verification against MySQL 8.0. |
| `PHP 8.3 \| PostgreSQL test on ubuntu-latest` | `pest_tests.yml` | **Required (`23566563`, `23566566`)** | Backend Pest verification against PostgreSQL 16. |
| `Check translation files consistency` | `translations_check.yml` | **Required (`23566563`, `23566566`)** | Fast dictionary parity check against the `en` baseline. |
| `Playwright E2E Gate` | `playwright_tests.yml` | **Required (`23566563`, `23566566`)** | Stable aggregate gate (`playwright_e2e_gate`, lines 257–272); succeeds only when all 12 browser shards (`MySQL/PostgreSQL \| Shard 1..6 of 6`) and both `report (mysql/pgsql)` jobs succeed. |
| `MySQL \| Shard 1..6 of 6`, `PostgreSQL \| Shard 1..6 of 6`, `report (mysql/pgsql)` | `playwright_tests.yml` | Diagnostic only (covered by `Playwright E2E Gate`) | Individual shard and report jobs; not bound directly in rulesets to avoid shard-count fragility. |
| `docker_publish.yml` job | `docker_publish.yml` | Not a PR check | Release deployment workflow triggered only on `v*` tags or manual dispatch. |

---

## 19. Active Controls vs. Current Technical Constraints & Optional Future Enhancements

The table below summarizes active CI controls alongside current technical constraints and optional future enhancements:

| Area | Current Active Reality (`[VERIFIED]`) | Optional Future Enhancement |
| :--- | :--- | :--- |
| **Required PR Status Checks (`CI-001`, `CI-008`)** | 4 contexts (`MySQL Pest`, `PostgreSQL Pest`, `Translations`, `Playwright E2E Gate`) are strictly enforced on `develop` and `master`. | Add a dedicated `Laravel Pint` or `kb_lint.py` CI job before binding any additional required check context. |
| **Documentation Fast-Track (`CI-010`)** | Path filtering in `pest_tests.yml`, `translations_check.yml`, and `playwright_tests.yml` skips heavy test runs on docs-only PRs while satisfying required status checks. | Keep path-filter patterns synchronized if new documentation-only root files are introduced. |
| **Merge Queue (`merge_group`, `CI-002`)** | `playwright_tests.yml` declares `merge_group:`; `pest_tests.yml` and `translations_check.yml` omit `merge_group:` and `workflow_dispatch:`. | Add `merge_group:` and `workflow_dispatch:` to `pest_tests.yml` and `translations_check.yml` before enabling GitHub Merge Queue. |
| **Composer Caching (`CI-003`)** | `playwright_tests.yml` and `translations_check.yml` use `actions/cache@v4`; `pest_tests.yml` runs bare `composer install` without cache. | Standardize `composer install --no-interaction --prefer-dist` and `actions/cache@v4` in `pest_tests.yml`. |
| **Code Style (`laravel/pint`, `CI-004`)** | Configured via `pint.json` and run locally; not executed in `.github/workflows/`. | Add a `vendor/bin/pint --test` step or workflow in CI if server-side style enforcement is desired. |
| **Code Coverage (`CI-005`)** | No coverage driver (`pcov`/`xdebug`) in CI; `phpunit.xml` `<source>` includes only `app/` and omits `plugins/webkul/*/src`. | Expand `phpunit.xml` `<source>` to include `plugins/webkul/*/src` and configure `pcov` if coverage thresholds are adopted. |
| **Pest Failure Artifacts (`CI-006`)** | `playwright_tests.yml` uploads screenshots, traces, videos, and `laravel.log` on failure; `pest_tests.yml` uploads no artifacts. | Upload `storage/logs/laravel.log` and JUnit XML reports on failure in `pest_tests.yml`. |
| **PHP Runtime Alignment (`CI-007`)** | CI workflows test on PHP `8.3` (`composer.json` constraint `^8.3`), while `docker/production/Dockerfile` (`line 4`) defaults to `ARG PHP_VERSION=8.4`. | Align `docker/production/Dockerfile` with PHP `8.3` or add PHP `8.4` to the CI test matrix. |
| **GitHub Action Pinning (`CI-009`)** | All 10 referenced GitHub Actions use major version tags (`@v4`, `@v2`, `@v3`, `@v6`) with `contents: read` permissions and no `pull_request_target`. | Pin third-party actions (such as `shivammathur/setup-php`) to full commit SHAs with Dependabot updates. |
