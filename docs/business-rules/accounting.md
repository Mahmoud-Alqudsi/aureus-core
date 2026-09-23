---
status: verified
source_of_truth: source-code
last_verified: 2026-09-03
scope: plugins/webkul/accounts, plugins/webkul/accounting, plugins/webkul/payments
confidence: high
---

# Accounting Business Rules

## 1. Scope

### What this document covers
This document details the calculation rules, algorithms, precedence orders, numerical thresholds, validation constraints, rounding precision behaviors, and cross-domain dependencies governing the Finance and General Ledger layer in Aureus ERP. It analyzes the underlying domain implementation primarily within `plugins/webkul/accounts`, along with presentation and reporting integrations in `plugins/webkul/accounting` and gateway schema extensions in `plugins/webkul/payments`.

Specifically, this document formalizes:
1. Tax computation engine (base calculation, fixed/percentage/division/code formulas, tax-on-tax cascading, repartition line splitting, and accounting entry generation).
2. Fiscal position remapping (account substitution and the non-enforcement of tax substitution).
3. Payment term schedules and due date algorithms (delay types, day counting, installment splitting, early discount mechanics, and balancing lines).
4. Multi-currency conversion and foreign exchange gain/loss settlement rules.
5. Reconciliation matching logic (pairwise clearing, residual tracking, matching numbers, and candidate discovery).
6. Floating-point precision, cash rounding strategies, and rounding delta absorption.
7. Validation constraints and their actual enforcement across UI, API, Service, Model, and Database layers.

### What it intentionally does not cover
- **Workflow Sequences**: The step-by-step procedural lifecycles (e.g. what UI buttons exist, sequence number assignment mechanics, event listener cascades, and reporting SQL aggregations) are documented in `docs/workflows/accounting.md`.
- **Database Physical Schema & ERD**: Physical table DDL, column types, foreign keys, and indexes are documented in `docs/database/erds/finance.md`.
- **Authorization & Security Policies**: Granular user permissions and Spatie Shield policy implementations are documented in `docs/security/`.
- **Other Functional Domains**: Inventory stock valuation, sales order pricing rules, and purchasing approval thresholds are documented in their respective domain business rules files (`inventory.md`, `sales.md`, `purchasing.md`).

---

## 2. Calculation Rules

### 2.1 Tax Calculation Pipeline

#### Rule Definition
The tax calculation pipeline calculates untaxed subtotal, per-tax monetary amounts, base amounts, and tax-included totals for transaction lines (`MoveLine`). It determines how taxes apply sequentially or compound, how price-inclusive taxes back-calculate the net base, and how computed tax amounts split across ledger accounts via repartition lines.

- **Status**: [VERIFIED]
- **Evidence**: `plugins/webkul/accounts/src/Services/TaxComputer.php:11-237`, `plugins/webkul/accounts/src/Models/Tax.php:143-215`, `plugins/webkul/accounts/src/Services/TaxFormulaEvaluator.php:47-62`, `plugins/webkul/accounts/src/Services/TaxAccountingMapper.php:11-133`, `plugins/webkul/accounts/src/Services/TaxDetailRounder.php:10-183`.

#### Inputs
- `price_unit` (float): Unit selling or purchasing price before line discount.
- `quantity` (float): Billed quantity.
- `discount` (float): Percentage line discount ($0.0 \le \text{discount} \le 100.0$).
- `taxes` (`Collection<Tax>`): Applied taxes attached to the line item.
- `currency` (`Currency`): Document transaction currency with `rounding` factor.
- `company` (`Company`): Transaction tenant with base `currency`.
- `rate` (float): Currency conversion rate between company currency and document currency ($1.0$ if domestic).
- `special_mode` (string|bool): Special evaluation overrides (`false`, `'total_included'`, `'total_excluded'`).
- `manual_tax_amounts` (array|null): Explicit manual override values for base or tax amounts.

#### Mathematical Algorithm & Formula

##### Step 1: Discounted Price Unit & Raw Base
$$\text{discounted\_unit\_price} = \text{price\_unit} \times \left(1 - \frac{\text{discount}}{100}\right)$$
$$\text{raw\_base} = \text{quantity} \times \text{discounted\_unit\_price}$$

If rounding method is `'round_per_line'`:
$$\text{raw\_base} = \text{float\_round}(\text{raw\_base}, \text{precisionRounding})$$

##### Step 1.1: Document Total Discount Calculation
At the document header level (`Move::getTotalDiscountAttribute()`), the total monetary discount is computed across all product-display line items:
$$\text{total\_discount} = \sum_{\text{lines}} \left(\text{price\_unit} \times \text{quantity} \times \frac{\text{discount}}{100}\right)$$
rounded to the document currency's decimal precision.

##### Step 2: Flattening & Ordering
Group taxes (`AmountType::GROUP`) are flattened into their constituent child taxes (`childrenTaxes`). The resulting collection of taxes is sorted by:
1. `sort` (ascending)
2. `id` (ascending)

Taxes are partitioned into evaluation batches (`TaxComputer::batchTaxes`). Taxes share a batch if they have identical `amount_type`, matching `price_include` flag, matching `include_base_amount` flag, and their base is not affected by previous taxes (`!$tax->include_base_amount || !$isBaseAffected`).

##### Step 3: Tax Amount Evaluation by Type
The tax engine resolves amounts in three successive passes:
1. Fixed amount and formula taxes (`evalTaxAmountFixedAmount`) in reverse sort order.
2. Price-included taxes (`evalTaxAmountPriceIncluded`) in reverse sort order.
3. Price-excluded taxes (`evalTaxAmountPriceExcluded`) in forward sort order.

The evaluation formulas per `AmountType` are:

1. **Percentage Tax (`AmountType::PERCENT`)**:
   - **Price-Excluded (`price_include = false`)**:
     $$\text{tax\_amount} = \text{evaluation\_base} \times \frac{\text{amount}}{100}$$
   - **Price-Included (`price_include = true`)**:
     Let $\text{totalPercentage} = \frac{\sum_{t \in \text{batch}} t.\text{amount}}{100}$.
     $$\text{toPriceExcludedFactor} = \begin{cases} \frac{1}{1 + \text{totalPercentage}} & \text{if } \text{totalPercentage} \ne -1 \\ 0.0 & \text{if } \text{totalPercentage} = -1 \end{cases}$$
     $$\text{tax\_amount} = \text{evaluation\_base} \times \text{toPriceExcludedFactor} \times \frac{\text{amount}}{100}$$

2. **Division Tax (`AmountType::DIVISION`)**:
   - **Price-Included (`price_include = true`)**:
     $$\text{tax\_amount} = \text{evaluation\_base} \times \frac{\text{amount}}{100}$$
   - **Price-Excluded (`price_include = false`)**:
     Let $\text{totalPercentage} = \frac{\sum_{t \in \text{batch}} t.\text{amount}}{100}$.
     $$\text{inclBaseMultiplicator} = \begin{cases} 1.0 & \text{if } \text{totalPercentage} = 1.0 \\ 1 - \text{totalPercentage} & \text{if } \text{totalPercentage} \ne 1.0 \end{cases}$$
     $$\text{tax\_amount} = \frac{\text{evaluation\_base} \times \frac{\text{amount}}{100}}{\text{inclBaseMultiplicator}}$$

3. **Fixed Amount Tax (`AmountType::FIXED`)**:
   $$\text{sign} = \begin{cases} -1 & \text{if } \text{price\_unit} < 0.0 \\ 1 & \text{otherwise} \end{cases}$$
   $$\text{tax\_amount} = \text{sign} \times \text{quantity} \times \text{amount}$$

4. **Code / Formula Tax (`AmountType::CODE`)**:
   Parsed and executed by `TaxFormulaEvaluator` using an arithmetic recursive-descent parser.
   - Allowed variables: `price_unit`, `quantity`, `price_subtotal`.
   - Allowed arithmetic operators: `+`, `-`, `*`, `/` (division by zero returns `0.0` safely).
   - Allowed functions: `min(...)`, `max(...)`.
   - If formula is blank or throws `InvalidTaxFormulaException`, returns `null`, omitting the tax without halting document processing.

##### Step 4: Base Adjustment & Compounding (Tax-on-Tax)
When a tax has `include_base_amount = true`, its computed `tax_amount` is propagated via `TaxComputer::propagateBaseAdjustments` to adjust the evaluation base of subsequent taxes where `is_base_affected = true`:
$$\text{evaluation\_base}_{\text{next}} = \text{raw\_base} + \sum \text{tax\_amount}_{\text{preceding}}$$

##### Step 5: Tax Repartition Line Distribution
Computed taxes do not post directly as a single ledger entry. Each tax possesses repartition lines (`accounts_tax_repartition_lines`) partitioned by `document_type` (`INVOICE` vs `REFUND`):
- Repartition factor: $\text{factor} = \frac{\text{factor\_percent}}{100.0}$.
- For each repartition line with `repartition_type = 'tax'`:
  $$\text{line\_tax\_amount\_currency} = \text{currency}->\text{round}(\text{tax\_amount\_currency} \times \text{factor} \times \text{sign})$$
  $$\text{line\_tax\_amount} = \text{companyCurrency}->\text{round}(\text{tax\_amount} \times \text{factor} \times \text{sign})$$
- Where $\text{sign} = 1.0$ for positive factors, or $-1.0$ for negative factors (reverse charge).
- Target ledger account: `repartition_line->account_id ?? baseLine['account_id']`.

#### Rounding & Precision
- Rounding mode is determined by `TaxesSettings::$tax_calculation_rounding_method`:
  - `'round_per_line'`: `float_round` is executed on each line's raw base and tax amounts immediately using the document currency's `rounding` factor.
  - `'round_globally'`: Unrounded amounts accumulate across all lines; delta between the rounded document total and sum of rounded lines is distributed to the line with the largest `total_included_currency` via `TaxDetailRounder::distributeTaxRoundingDeltas`.
- Fractional rounding errors across multiple repartition lines within a single tax are absorbed by `TaxAccountingMapper::absorbRoundingErrors`, incrementing entries by $\frac{\text{totalError}}{\text{steps}}$ in descending order of absolute currency amount.

---

### 2.2 Payment Term Due-Date & Installment Calculation

#### Rule Definition
Calculates the maturity dates and split monetary balances for commercial documents (customer invoices and vendor bills) based on configured payment terms (`PaymentTerm`) and installment lines (`PaymentDueTerm`).

- **Status**: [VERIFIED]
- **Evidence**: `plugins/webkul/accounts/src/Models/PaymentTerm.php:75-223`, `plugins/webkul/accounts/src/Models/PaymentDueTerm.php:39-62`, `plugins/webkul/accounts/src/Services/DynamicLineSynchronizer.php:175-330`.

#### Inputs
- `dateRef` (Carbon|string): Baseline accounting date (`invoice_date ?? date ?? now()`).
- `currency` (`Currency`): Transaction document currency.
- `company` (`Company`): Tenant legal entity.
- `totalAmount` (float): Total document amount in company currency ($\text{untaxed} + \text{tax}$).
- `totalAmountCurrency` (float): Total document amount in transaction currency.
- `rate` (float): Document currency rate ($\left|\frac{\text{totalAmountCurrency}}{\text{totalAmount}}\right|$).
- `sign` (int): Directional multiplier ($1$ for sale/inbound, $-1$ for purchase/outbound).
- `cashRounding` (`CashRounding`|null): Optional cash rounding model.

#### Due Date Resolution Algorithm (`PaymentDueTerm::getDueDate`)
For each term line, the maturity date is computed from `dateRef` based on `delay_type`:

| `delay_type` Enum Value | Mathematical / Calendar Logic | Formula |
| :--- | :--- | :--- |
| `days_after` | Fixed day offset | $\text{dateRef} + \text{nb\_days}$ |
| `days_after_end_of_month` | End of current month plus day offset | $\text{endOfMonth}(\text{dateRef}) + \text{nb\_days}$ |
| `days_after_end_of_next_month` | End of following month plus day offset | $\text{endOfMonth}(\text{dateRef} + 1\text{ month}) + \text{nb\_days}$ |
| `days_end_of_month_on_the` | Specific day of following month | If $\text{days\_next\_month} > 0$:<br>$(\text{dateRef} + \text{nb\_days} + 1\text{ month})\text{ with day}=\text{days\_next\_month}$<br>If $\text{days\_next\_month} = 0$:<br>$\text{endOfMonth}(\text{dateRef} + \text{nb\_days})$ |

*Note on Month Overflow*: Calculations utilize `addMonthNoOverflow(1)` / `addMonthsNoOverflow(1)` to prevent 31-day month rollovers (e.g. Jan 31 + 1 month yields Feb 28/29, not March 2/3).

#### Installment Allocation & Residual Balancing
The lines are evaluated sequentially in sort order. A running residual tracks unallocated amounts:
$$\text{residualAmount} = \text{totalAmount}, \quad \text{residualAmountCurrency} = \text{totalAmountCurrency}$$

For each term line index $i \in [0, N-1]$:
1. **Intermediate Installments ($i < N-1$)**:
   - If `value === 'fixed'`:
     $$\text{foreign\_amount} = \text{sign} \times \text{currency}->\text{round}(\text{value\_amount})$$
     $$\text{company\_amount} = \text{rate} \ne 0 ? \text{sign} \times \text{companyCurrency}->\text{round}\left(\frac{\text{value\_amount}}{\text{rate}}\right) : 0.0$$
   - If `value === 'percent'`:
     $$\text{share} = \frac{\text{value\_amount}}{100.0}$$
     $$\text{foreign\_amount} = \text{currency}->\text{round}(\text{totalAmountCurrency} \times \text{share})$$
     $$\text{company\_amount} = \text{companyCurrency}->\text{round}(\text{totalAmount} \times \text{share})$$
   - Cash rounding adjustments: If cash rounding is active, the rounding difference is added to `foreign_amount` and converted into `company_amount`.
   - Running residuals update:
     $$\text{residualAmount} \leftarrow \text{residualAmount} - \text{company\_amount}$$
     $$\text{residualAmountCurrency} \leftarrow \text{residualAmountCurrency} - \text{foreign\_amount}$$
2. **Balancing Installment ($i = N-1$, Last Line)**:
   The final line is designated the **Balancing Line**:
   $$\text{company\_amount} = \text{residualAmount}$$
   $$\text{foreign\_amount} = \text{residualAmountCurrency}$$
   This eliminates cumulative fractional rounding errors across multi-installment schedules.

#### Early Payment Discount
- Declared fields: `early_discount` (boolean), `discount_percentage` (decimal), `discount_days` (integer), `early_pay_discount` (enum: `included`, `excluded`, `mixed`).
- Execution status: `PaymentTerm::getEarlyDiscountAttribute()` is hardcoded to return `false` in `plugins/webkul/accounts/src/Models/PaymentTerm.php:70-73`.
- Inactive by default: The underlying calculation logic exists in `PaymentTerm::earlyDiscount()`, but because the accessor returns `false`, early payment discounts are not computed unless programmatic intervention overrides the model attribute.

---

### 2.3 Multi-Currency Conversion & Exchange Gain/Loss Calculation

#### Rule Definition
Calculates the operational exchange rates for foreign currency documents, converts document currency amounts into company base currency for the general ledger, and detects/posts realized currency exchange gains or losses upon reconciliation.

- **Status**: [VERIFIED]
- **Evidence**: `plugins/webkul/support/src/Models/Currency.php:51-100`, `plugins/webkul/accounts/src/Models/Move.php:561-577`, `plugins/webkul/accounts/src/Services/ExchangeDifferenceRecorder.php:12-101`, `plugins/webkul/accounts/src/Services/Reconciler.php:326-407`.

#### Rate Resolution Algorithm (`Currency::resolveRate`)
To determine the conversion rate between currency $A$ and currency $B$ for tenant company $C$ on date $D$:
1. If $A.\text{id} == B.\text{id}$, conversion rate is exactly $1.0$.
2. For each currency, resolve rate $R$ from physical table `currency_rates`:
   - Query: `currency_rates.currency_id = currency.id`
   - Company filter: `(company_id = C.id OR company_id IS NULL)`
   - Date filter: `whereDate('name', '<=', D)` (Note: In the `currency_rates` schema, the rate effective date is stored in column `name`).
   - Order: `orderByDesc('name')` (latest applicable date).
   - If found and $R > 0$, use $R$; otherwise fallback to $1.0$.
3. Resulting Cross-Rate:
   $$\text{ConversionRate}(A \to B) = \frac{R_B}{R_A}$$

#### Document Stamped Currency Rate
On customer invoices and vendor bills (`isInvoice(true)`), `Move::computeInvoiceCurrencyRate()` executes upon document saving:
$$\text{invoice\_currency\_rate} = \text{Currency::getConversionRate}(\text{companyCurrency}, \text{documentCurrency}, \text{company}, \text{invoice\_date} \text{ ?? } \text{now}())$$
- Transaction date priority: Evaluated against `invoice_date`. It does NOT evaluate against `posted_at` or posting execution timestamps.

#### Realized Exchange Difference on Settlement
When a foreign-currency receivable or payable line is matched against a payment in `Reconciler::matchSinglePair()`:
1. The transaction currency amounts settle: $\text{amount\_residual\_currency} \to 0.0$.
2. Due to differing historical exchange rates between invoice posting date $D_1$ and payment date $D_2$, the company currency residual balance $\text{amount\_residual}$ on the ledger line may remain non-zero ($\text{companyCurrency}->\text{isZero}(\text{residual}) === \text{false}$).
3. When both foreign amounts are fully matched, `ExchangeDifferenceRecorder::buildExchangeMove()` is triggered:
   - Verifies configuration from `DefaultAccountSettings`:
     - `currency_exchange_journal_id`
     - `income_currency_exchange_account_id`
     - `expense_currency_exchange_account_id`
     - Throws `Exception('Exchange difference journal and accounts must be configured')` if missing.
   - Generates balancing entry (`MoveType::ENTRY`):
     - Date: Current date (`now()->format('Y-m-d')`).
     - Line 1 (Rebalancing entry on original receivable/payable account):
       $$\text{debit} = \text{residual} < 0 ? |\text{residual}| : 0, \quad \text{credit} = \text{residual} > 0 ? \text{residual} : 0, \quad \text{amount\_currency} = 0$$
     - Line 2 (Exchange Gain/Loss counterpart entry):
       $$\text{target\_account} = \text{residual} < 0 ? \text{income\_account\_id} : \text{expense\_account\_id}$$
       $$\text{debit} = \text{residual} > 0 ? \text{residual} : 0, \quad \text{credit} = \text{residual} < 0 ? |\text{residual}| : 0, \quad \text{amount\_currency} = 0$$
   - The exchange entry is posted immediately and auto-reconciled with the original line, clearing $\text{amount\_residual}$ to $0.0$.

---

### 2.4 Cash Rounding Calculation

#### Rule Definition
Calculates fractional rounding differences for cash transactions to conform to currency denomination constraints (e.g. rounding to nearest 0.05).

- **Status**: [VERIFIED]
- **Evidence**: `plugins/webkul/accounts/src/Models/CashRounding.php:44-56`, `plugins/webkul/accounts/src/Services/DynamicLineSynchronizer.php:72-173`.

#### Inputs
- `amount` (float): Total document currency amount.
- `rounding` (float): Configured precision factor (e.g. `0.05`, `0.10`, `1.00`).
- `rounding_method` (string): Rounding algorithm (`HALF-UP`, `HALF-DOWN`, `HALF-EVEN`, `UP`, `DOWN`).
- `strategy` (string): Accounting distribution strategy (`biggest_tax`, `add_invoice_line`).

#### Calculation & Strategies
1. **Difference Formula**:
   $$\text{rounded\_amount} = \text{float\_round}(\text{amount}, \text{precisionRounding}=\text{rounding}, \text{roundingMethod}=\text{rounding\_method})$$
   $$\text{difference} = \text{currency}->\text{round}(\text{rounded\_amount} - \text{amount})$$
2. **Distribution Strategy**:
   - **`biggest_tax`**: Modifies the move line having the largest absolute tax balance (`DisplayType::TAX`), tagging it as `Tax Rounding (<tax_name>)` and absorbing the delta into that tax line.
   - **`add_invoice_line`**: Creates a distinct line (`DisplayType::ROUNDING`):
     - If $\text{difference} > 0$ (loss for company / concession): posts to `loss_account_id`.
     - If $\text{difference} < 0$ (profit for company / gain): posts to `profit_account_id`.

---

## 3. Precedence & Matching Rules

### 3.1 Fiscal Position Remapping

#### Candidate Selection & Discovery
- **Status**: [PARTIALLY VERIFIED]
- **Evidence**: `plugins/webkul/accounts/src/Models/FiscalPosition.php:75-84`, `plugins/webkul/accounts/src/Models/MoveLine.php:425-427`, `plugins/webkul/accounts/src/Models/Product.php:116-129`, `plugins/webkul/accounts/src/Filament/Resources/InvoiceResource/Schemas/InvoiceForm.php:339-349`.

In Aureus ERP, fiscal position selection operates as follows:
1. **Document-Level Assignment**:
   - On manual invoice/bill creation in Filament UI (`InvoiceForm`), `fiscal_position_id` is a manual select field.
   - When a partner is selected, `fiscal_position_id` is **NOT** automatically set from `Partner::$property_account_position_id`.
   - When invoices or bills are generated from upstream Sales Orders (`Webkul\Sale\Services\Invoicer`) or Purchase Orders (`Webkul\Purchase\Services\Biller`), `fiscal_position_id` is explicitly copied from the source order.
2. **Jurisdiction Matching Precedence**:
   - Although the `accounts_fiscal_positions` schema contains regional matching fields (`country_id`, `country_group_id`, `zip_from`, `zip_to`, `vat_required`, `auto_reply`), **NO automatic matching engine exists in the codebase** to evaluate partner country/zip/VAT and select a fiscal position automatically.
   - Discovery status: Selection is entirely manual or passed from upstream documents. Automatic jurisdiction matching is [NOT IMPLEMENTED].

#### Account Substitution Rule
- **Enforcement**: [VERIFIED]
- **Mechanism**: `FiscalPosition::mapAccount(Account $account)`:
  1. Searches `accounts_fiscal_position_accounts` where `fiscal_position_id = $this->id` and `account_source_id = $account->id`.
  2. If a mapping row exists and `account_destination_id` is not null:
     $$\text{Mapped Account} = \text{Account::find}(\text{mapping}->\text{account\_destination\_id})$$
  3. Fallback / Tie-Breaker: If no mapping exists or destination is null, returns the original `$account` unchanged.
- **Enforced Sites**:
  - `MoveLine` Payment Term lines: Remaps `property_account_receivable_id` or `property_account_payable_id`.
  - `Product` Income/Expense lines: Remaps product/category income and expense accounts via `Product::getAccountsFromFiscalPosition()`.

#### Tax Substitution Rule
- **Enforcement**: [NOT IMPLEMENTED / UNENFORCED]
- **Critical Architectural Finding**:
  - Physical table `accounts_fiscal_position_taxes` defines mappings between `tax_source_id` and `tax_destination_id`.
  - Filament UI provides complete CRUD administration under `FiscalPositionResource`.
  - API provides endpoints under `FiscalPositionController`.
  - **However, NO method (`mapTax`) exists on `FiscalPosition`, and NO invocation of `FiscalPositionTax` occurs in `TaxComputer`, `TaxManager`, `MoveCalculator`, or `MoveLine`.**
  - Taxes are never substituted by fiscal positions during invoice or bill processing. Tax substitution is Declared in schema/UI but Completely Unenforced in application business logic.

---

### 3.2 Product General Ledger Account Resolution Precedence

When determining the default income or expense account for a product line on an invoice or vendor bill, `Product::resolveAccount` executes a strict 5-tier fallback hierarchy:

```
[1] Product Company Account (products_product_company_accounts for current company_id)
     │ (if null)
     ▼
[2] Parent Product Company Account (if variant has parent_id)
     │ (if null)
     ▼
[3] Category Company Account Hierarchy (traverses up category parent tree)
     │ (if null)
     ▼
[4] Default Account Settings (DefaultAccountSettings::income_account_id / expense_account_id)
     │ (if resolved)
     ▼
[5] Fiscal Position Account Mapping (FiscalPosition::mapAccount($account))
     │ (returns destination account or original account)
     ▼
Final Ledger Account ID
```

- **Status**: [VERIFIED]
- **Evidence**: `plugins/webkul/accounts/src/Models/Product.php:94-129`.

---

### 3.3 Reconciliation Matching Algorithm

#### Candidate Selection & Pairing Logic
- **Status**: [VERIFIED]
- **Evidence**: `plugins/webkul/accounts/src/Services/Reconciler.php:104-308`.

When `Reconciler::reconcile(Collection $lines)` executes:
1. **Pre-Condition Validation (`assertReconcilable`)**:
   - All lines must have `parent_state = 'posted'`.
   - No lines may already have `reconciled = true`.
   - All lines must share identical `account_id`, `partner_id`, and `company_id`.
   - Account must permit reconciliation (`account->reconcile == true` or `account_type` in `ASSET_CASH`, `LIABILITY_CREDIT_CARD`).
2. **Plan Sorting Precedence**:
   Lines are sorted strictly by:
   $$\text{Order} = [\text{date\_maturity ?? date} \text{ (ASC)}, \quad \text{currency\_id}, \quad \text{amount\_currency}, \quad \text{balance}]$$
   If multi-currency lines exist, lines are partitioned into currency sub-nodes before cross-currency reconciliation.
3. **Debit-Credit Pairing Loop (`matchDebitsToCredits`)**:
   - Partitioned into `debits` ($\text{balance} > 0$ or $\text{amount\_currency} > 0$) and `credits` ($\text{balance} < 0$ or $\text{amount\_currency} < 0$).
   - Loops through debit index and credit index sequentially:
     $$\text{matchable} = \min(\text{availableDebit}, \text{availableCredit})$$
   - Generates `PartialReconcile` record linking `debit_move_id` and `credit_move_id`.
   - Decrements `amount_residual` and `amount_residual_currency`.
   - Advance condition: The side that was fully cleared advances to the next index in the array.
4. **Full Reconciliation Finalization**:
   - When all lines in a reconciliation group reach `amount_residual == 0` (evaluated using `Currency::isZero`), a `FullReconcile` record is created.
   - Matching numbers are stamped on lines:
     - Fully reconciled lines: Integer ID of `FullReconcile` (e.g. `'12'`).
     - Partially reconciled lines: `'P'` followed by the group index (e.g. `'P1'`).

---

## 4. Thresholds & Limits

Every numerical boundary and tolerance threshold enforced by source code in the Finance domain is documented below:

| Parameter / Field | Source Location | Exact Value / Formula | Behavior When Violated | Status |
| :--- | :--- | :--- | :--- | :--- |
| **Minimum Document Amount** | `MoveWorkflow.php:254` | `float_compare(amount_total, 0, rounding) < 0` | Throws `Exception('The total amount of the document cannot be negative.')`. Zero total is permitted; negative total is strictly blocked. | [VERIFIED] |
| **Manual Journal Balancing Threshold** | `JournalEntryForm.php:610` | `abs(round(totalDebit - totalCredit, 2)) < 0.01` | If difference $\ge 0.01$, UI automatically injects an "Automatic Balancing" line to the journal's suspense account. | [VERIFIED] |
| **Tax Repartition Positive Sum** | `TaxPartition.php:128` | `bccomp((string) $positive, '100', 2) === 0` | Throws `ValidationException`: Total positive factors must equal exactly 100.00%. | [VERIFIED] |
| **Tax Repartition Negative Sum** | `TaxPartition.php:134` | `bccomp((string) $negative, '-100', 2) === 0` | Throws `ValidationException`: Total negative factors must equal exactly -100.00%. | [VERIFIED] |
| **Tax Repartition Base Line Count** | `TaxPartition.php:92` | Exactly 1 line per document type | Throws `ValidationException` if `repartition_type = BASE` count $\ne 1$ for invoice or refund. | [VERIFIED] |
| **Tax Repartition Tax Line Count** | `TaxPartition.php:102` | $\ge 1$ line per document type | Throws `ValidationException` if `repartition_type = TAX` count $< 1$. | [VERIFIED] |
| **Floating-Point Zero Epsilon** | `Currency.php:119-121` | $\text{epsilon} = \text{rounding} \text{ (default } 0.01 \text{ or } 10^{-\text{decimal\_places}})$ | Values where $|x| < \text{epsilon}$ evaluate to zero (`floatIsZero = true`). | [VERIFIED] |
| **Currency Conversion Fallback** | `Currency.php:99` | Rate $> 0$ fallback to `1.0` | If currency rate is missing, non-positive, or unconfigured, system silently defaults rate to 1.0. | [VERIFIED] |
| **Material Tax Line Balance** | `TaxAccountingMapper.php:251` | `!$company->currency->isZero(balance)` | Zero-balance tax lines are discarded before saving unless document currency amount is non-zero. | [VERIFIED] |

---

## 5. Validation Constraints & Enforcement Matrix

The following matrix distinguishes between rules that are merely declared (in UI schemas, form requests, or database columns) versus those actively enforced by the backend across different entry points:

| Business Rule | Declared | UI (Filament) | API (FormRequest) | Service Layer | Model / Event | Database Schema | Overall Enforcement |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Double-Entry Balance (Debit == Credit)** | [VERIFIED] | [VERIFIED] (Auto-injects suspense line) | [NOT ENFORCED] | [NOT ENFORCED] | [NOT ENFORCED] | [NOT ENFORCED] | [PARTIALLY VERIFIED] |
| **Non-Negative Total Amount** | [VERIFIED] | [VERIFIED] | [NOT ENFORCED] | [VERIFIED] (`MoveWorkflow::assertPostable`) | [NOT ENFORCED] | [NOT ENFORCED] | [PARTIALLY VERIFIED] |
| **Partner Presence on Invoices/Bills** | [VERIFIED] | [VERIFIED] (`required()`) | [VERIFIED] (`InvoiceRequest`) | [VERIFIED] (`assertPostable`) | [NOT ENFORCED] | [VERIFIED] (`restrictOnDelete`) | [VERIFIED] |
| **Non-Deprecated Accounts on Lines** | [VERIFIED] | [VERIFIED] (Filtered in query) | [NOT ENFORCED] | [VERIFIED] (`assertPostable`) | [NOT ENFORCED] | [NOT ENFORCED] | [PARTIALLY VERIFIED] |
| **Archived Bank Account Blocking** | [VERIFIED] | [VERIFIED] | [NOT ENFORCED] | [VERIFIED] (`assertPostable`) | [NOT ENFORCED] | [NOT ENFORCED] | [PARTIALLY VERIFIED] |
| **Posted Entry Immutability (No Direct Line Edits)** | [VERIFIED] | [VERIFIED] (`disabled()`) | [NOT ENFORCED] | [VERIFIED] (`MoveState` checks) | [NOT ENFORCED] | [NOT ENFORCED] | [PARTIALLY VERIFIED] |
| **Exchange Difference Entry Reset-to-Draft Block** | [VERIFIED] | [VERIFIED] | [NOT ENFORCED] | [VERIFIED] (`assertNotExchangeDifference`) | [NOT ENFORCED] | [NOT ENFORCED] | [PARTIALLY VERIFIED] |
| **Reconciliation Account Type Guard** | [VERIFIED] | [VERIFIED] | [NOT APPLICABLE] | [VERIFIED] (`assertReconcilable`) | [NOT ENFORCED] | [NOT ENFORCED] | [VERIFIED] |
| **Single-Partner Reconciliation Guard** | [VERIFIED] | [VERIFIED] | [NOT APPLICABLE] | [VERIFIED] (`assertReconcilable`) | [NOT ENFORCED] | [NOT ENFORCED] | [VERIFIED] |
| **Company Isolation on Moves & Lines** | [VERIFIED] | [VERIFIED] (`owned_by_company`) | [VERIFIED] | [VERIFIED] (`assertReconcilable`) | [VERIFIED] (`BelongsToCompany`) | [VERIFIED] (`company_id` FK) | [VERIFIED] |
| **Tax Repartition Parity (Invoice == Refund)** | [VERIFIED] | [VERIFIED] | [NOT ENFORCED] | [NOT APPLICABLE] | [VERIFIED] (`TaxPartition::validate`) | [NOT ENFORCED] | [VERIFIED] |
| **Fiscal Position Tax Remapping** | [VERIFIED] | [VERIFIED] (Form exists) | [VERIFIED] (CRUD API exists) | [NOT IMPLEMENTED] | [NOT IMPLEMENTED] | [VERIFIED] (FK table exists) | [NOT ENFORCED] |

### Critical Enforcement Gap: Manual Journal Entry Balancing on API / Service Paths
While Filament UI (`JournalEntryForm`) actively prevents unbalanced manual journal entries by computing differences and injecting a balancing line into the suspense account, neither `MoveWorkflow::assertPostable()` nor `MoveCalculator::recompute()` validates that total debits equal total credits. As a result, programmatically constructed or API-created journal entries can technically be posted with unequal debit and credit totals.

---

## 6. Rounding & Precision Rules

### Floating-Point Helper Architecture (`plugins/webkul/support/src/helpers.php`)
All financial calculations rely on centralized floating-point helpers:
- `float_round($value, $precisionDigits = null, $precisionRounding = null, $roundingMethod = 'HALF-UP')`:
  - Determines rounding factor: $\text{factor} = \text{precisionRounding} \text{ ?? } 10^{-\text{precisionDigits}}$.
  - Scales: $\text{scaled} = \frac{\text{value}}{\text{factor}}$.
  - Evaluates rounding method:
    - `'HALF-UP'`: `($scaled > 0) ? floor($scaled + 0.5) : ceil($scaled - 0.5)`
    - `'HALF-DOWN'`: `($scaled > 0) ? ceil($scaled - 0.5) : floor($scaled + 0.5)`
    - `'HALF-EVEN'` (Banker's Rounding): Rounds to the nearest even integer when the fraction is exactly $0.5$.
    - `'UP'`: Rounds away from zero (`ceil` if positive, `floor` if negative).
    - `'DOWN'`: Rounds towards zero (`floor` if positive, `ceil` if negative).
  - Returns $\text{rounded} \times \text{factor}$.
- `float_compare($val1, $val2, $precisionDigits = null, $precisionRounding = null)`:
  - Compares two floats within the rounding factor tolerance. If $\text{float\_is\_zero}(\text{val1} - \text{val2})$, returns `0`; otherwise returns $-1$ or $1$.

### Currency & Quantity Precision
- **Currencies**: Stored in `currencies.decimal_places` (typically `2`) and `currencies.rounding` (typically `0.01`).
- **Database Decimal Storage**: Ledger monetary balances (`debit`, `credit`, `balance`, `amount_currency`, `amount_residual`, `amount_total`) are stored as `DECIMAL(15, 4)` in database migrations.
- **Quantities**: Stored as `DECIMAL(15, 4)` in `accounts_account_move_lines.quantity`.

### Tax Rounding Strategies (`TaxDetailRounder`)
When `tax_calculation_rounding_method` is configured:
1. `round_per_line`: Each line item's base amount and tax amounts are rounded to currency precision immediately.
2. `round_globally`: Tax amounts accumulate unrounded; after summing across the entire document, the net difference between global rounded tax and the sum of line rounded taxes is added to the line holding the largest `total_included_currency`.

---

## 7. Cross-Domain Rule Dependencies

```
┌────────────────────────────────────────────────────────────────────────────────┐
│                         Finance Cross-Domain Architecture                       │
└────────────────────────────────────────────────────────────────────────────────┘
                                  │
         ┌────────────────────────┼────────────────────────┐
         ▼                        ▼                        ▼
┌──────────────────┐    ┌──────────────────┐    ┌──────────────────┐
│  support plugin  │    │ products plugin  │    │ partners plugin  │
│  (Foundational)  │    │   (Runtime Dep)  │    │   (Core Plugin)  │
├──────────────────┤    ├──────────────────┤    ├──────────────────┤
│ - Currency Rates │    │ - Income/Expense │    │ - Partner Ledger │
│ - Sequences      │    │   Company Accts  │    │ - Customer/Vendor│
│ - Float Helpers  │    │ - Taxes Junction │    │   Properties     │
│ - Company Scope  │    │ - Usage Guard    │    │ - Bank Accounts  │
└──────────────────┘    └──────────────────┘    └──────────────────┘
         ▲                        ▲                        ▲
         │                        │                        │
         └────────────────────────┼────────────────────────┘
                                  │
                        ┌──────────────────┐
                        │ accounts plugin  │
                        │  (Core Engine)   │
                        └──────────────────┘
                                  │
         ┌────────────────────────┴────────────────────────┐
         ▼                                                 ▼
┌──────────────────┐                             ┌──────────────────┐
│  sales plugin    │                             │ purchases plugin │
├──────────────────┤                             ├──────────────────┤
│ - SO Invoicing   │                             │ - PO Billing     │
│ - Fiscal Pos CP  │                             │ - Fiscal Pos CP  │
│ - Move Listeners │                             │ - Move Listeners │
└──────────────────┘                             └──────────────────┘
```

### Dependency Classification
1. **Runtime Plugin Dependency (`Package::hasDependencies`)**:
   - `accounts` declares runtime dependency on `products` (`AccountServiceProvider::configureCustomPackage`). Required for tax association tables (`accounts_product_taxes`), category account resolution, and `ProductUsageRegistry`.
   - `accounting` declares runtime dependency on `accounts` (`AccountingServiceProvider::configureCustomPackage`). Required for reporting and navigation.
   - `payments` declares runtime dependency on `accounts`.
2. **Code-Level Dependency**:
   - `accounts` directly consumes models and traits from `support` (`Company`, `Currency`, `CurrencyRate`, `Sequence`, `BelongsToCompany`).
   - `accounts` dynamically injects relationships into `Partner` (`partners_partner_company_properties`) and `Product` (`products_product_company_accounts`) via `resolveRelationUsing` during `packageBooted()`.
3. **Business-Rule Dependency**:
   - **Sales Invoicing**: `Webkul\Sale\Services\Invoicer` creates draft moves from sales orders and copies `fiscal_position_id`, `payment_term_id`, and `partner_id`. Upstream listeners (`ComputeSaleOrderFromMoveListener`) observe `MoveConfirmed`, `MoveDrafted`, `MoveCancelled`, and `MoveReversed` to recalculate sale order billing status.
   - **Purchasing Billing**: `Webkul\Purchase\Services\Biller` creates draft vendor bills from purchase orders and copies `fiscal_position_id`, `payment_term_id`, and `partner_id`. Upstream listeners (`ComputePurchaseOrderFromMoveListener`) observe move lifecycle events to recalculate purchase order billing status.

---

## 8. Edge Cases & Known Gaps

1. **Unbalanced Manual Entries on Backend Paths**:
   - As established in Section 5, while Filament UI injects an automatic balancing line to the suspense account, no service-layer validator (`MoveWorkflow::assertPostable`) rejects unbalanced manual journal entries (`MoveType::ENTRY`).
2. **Fiscal Position Tax Substitution Non-Enforcement**:
   - `accounts_fiscal_position_taxes` and `FiscalPositionTax` are present in schema, models, UI, and API, but tax remapping logic is absent from `TaxComputer` and `MoveCalculator`. Taxes are never remapped by fiscal positions.
3. **Early Payment Discount Inactivity**:
   - `PaymentTerm::getEarlyDiscountAttribute()` is hardcoded to return `false`, rendering early payment discount terms inactive in standard workflows.
4. **Bank Statement Automation UI Absence**:
   - Database tables `accounts_bank_statements` and `accounts_bank_statement_lines` exist with Eloquent models, but no Filament UI, import parser, or automatic bank rule matching engine exists in active code.
5. **Fiscal Period Closing Routine Absence**:
   - Aureus ERP possesses no year-end rollover routines, closing voucher generators, or lock-date validation rules. Transactions can be posted to arbitrary historical or future dates without closing restrictions.
6. **Zero Currency Rate Fallback**:
   - If an operational currency has no rate recorded in `currency_rates` matching the transaction date, `Currency::resolveRate` silently returns `1.0`, potentially computing domestic parity for foreign currency documents without throwing a warning.

---

## 9. Unknowns / Inferences

### [UNKNOWN]
1. **Planned Fiscal Position Tax Engine**: It is [UNKNOWN] whether tax remapping in `FiscalPosition` was intentionally omitted during refactoring or left incomplete, as the schema and admin UI are fully built out but completely decoupled from calculation services.
2. **Default Cash Rounding Behavior when Strategy Fails**: In `DynamicLineSynchronizer::applyCashRounding`, if strategy is `biggest_tax` but no move line contains a `tax_repartition_line_id`, the method returns `null` silently without applying rounding to the document balance.

### [INFERRED]
1. **Intent of Suspense Account Balancing in UI**: The presence of `calculateBalancingLine` in `JournalEntryForm` indicates that the original UI design intended for accountants to balance entries against a suspense account on-the-fly rather than blocking form submission.

### [PARTIALLY VERIFIED]
1. **Manual Entry Balancing Enforcement**: Verified in UI via `JournalEntryForm`; verified as NOT ENFORCED in backend service (`assertPostable`) and model (`MoveCalculator`).

---

## 10. Evidence References

| Business Rule Area | Relative File Path | Symbols / Methods |
| :--- | :--- | :--- |
| **Tax Computation Engine** | `plugins/webkul/accounts/src/Services/TaxComputer.php` | `TaxComputer::computeTaxes()`, `flattenTaxGroups()`, `batchTaxes()`, `propagateBaseAdjustments()` |
| **Tax Rate Evaluation** | `plugins/webkul/accounts/src/Models/Tax.php` | `Tax::evalTaxAmountFixedAmount()`, `evalTaxAmountPriceIncluded()`, `evalTaxAmountPriceExcluded()` |
| **Tax Repartition Lines** | `plugins/webkul/accounts/src/Models/TaxPartition.php` | `TaxPartition::validateRepartitionLines()`, `getFactorAttribute()` |
| **Tax Formula Parser** | `plugins/webkul/accounts/src/Services/TaxFormulaEvaluator.php` | `TaxFormulaEvaluator::evaluate()`, `parseExpression()`, `parseTerm()`, `parseFactor()` |
| **Tax Accounting Mapping** | `plugins/webkul/accounts/src/Services/TaxAccountingMapper.php` | `TaxAccountingMapper::withAccountingData()`, `buildRepartitionData()`, `absorbRoundingErrors()` |
| **Tax Rounding Strategies** | `plugins/webkul/accounts/src/Services/TaxDetailRounder.php` | `TaxDetailRounder::roundTaxDetails()`, `distributeTaxRoundingDeltas()`, `distributeBaseRoundingDeltas()` |
| **Payment Terms & Due Dates** | `plugins/webkul/accounts/src/Models/PaymentTerm.php`<br>`plugins/webkul/accounts/src/Models/PaymentDueTerm.php` | `PaymentTerm::computeTerms()`, `dueLines()`, `PaymentDueTerm::getDueDate()` |
| **Dynamic Line Synchronization**| `plugins/webkul/accounts/src/Services/DynamicLineSynchronizer.php` | `DynamicLineSynchronizer::syncTaxLines()`, `syncRoundingLines()`, `syncPaymentTermLines()` |
| **Currency Conversion** | `plugins/webkul/support/src/Models/Currency.php` | `Currency::convert()`, `getConversionRate()`, `resolveRate()`, `round()` |
| **Exchange Gain/Loss Generation**| `plugins/webkul/accounts/src/Services/ExchangeDifferenceRecorder.php` | `ExchangeDifferenceRecorder::differenceFor()`, `buildExchangeMove()`, `postDifferences()` |
| **Reconciliation Matching** | `plugins/webkul/accounts/src/Services/Reconciler.php` | `Reconciler::reconcile()`, `matchDebitsToCredits()`, `matchSinglePair()`, `assertReconcilable()` |
| **Reconciliation UI Component** | `plugins/webkul/accounts/src/Livewire/InvoiceSummary.php` | `InvoiceSummary::reconcileAction()`, `unReconcileAction()`, `render()` |
| **Candidate Discovery** | `plugins/webkul/accounts/src/Models/Move.php` | `Move::getReconcilablePayments()`, `getAllReconciledInvoicePartials()`, `computeInvoiceCurrencyRate()` |
| **Cash Rounding Logic** | `plugins/webkul/accounts/src/Models/CashRounding.php` | `CashRounding::computeDifference()`, `round()` |
| **Document Validation** | `plugins/webkul/accounts/src/Services/MoveWorkflow.php` | `MoveWorkflow::assertPostable()`, `assertNotExchangeDifference()`, `post()`, `resetToDraft()` |
| **UI Suspense Balancing** | `plugins/webkul/accounting/src/Filament/Clusters/Accounting/Resources/JournalEntryResource/Schemas/JournalEntryForm.php` | `JournalEntryForm::calculateBalancingLine()` |
| **Floating-Point Helpers** | `plugins/webkul/support/src/helpers.php` | `float_round()`, `float_compare()`, `float_check_precision()` |

---

## 11. Mandatory Final Answer

> **Question:**
> *"Is there any automatic match-suggestion logic at all, or is reconciliation entirely user-selected with no system assistance?"*

### Explicit Answer:
**Aureus ERP provides candidate match discovery (assistance) in the user interface, but reconciliation is NEVER executed automatically by a heuristic background engine without explicit user action.**

Specifically:
1. **System Assistance / Candidate Discovery (`Move::getReconcilablePayments()`)**:
   When viewing a posted, open invoice or vendor bill in Filament UI (`InvoiceSummary`), the system queries all unreconciled move lines that share the invoice's `account_id` and commercial `partner_id` with opposing balance sign ($\text{balance} < 0$ for customer invoices, $\text{balance} > 0$ for vendor bills). These discovered open lines are displayed in the invoice summary panel under "Outstanding credits" or "Outstanding debits".
2. **User Selection Requirement**:
   The system does **NOT** automatically bind, match, or reconcile these suggested candidates on its own. The user must explicitly click the **"Add / Reconcile"** button on a specific candidate line (`InvoiceSummary::reconcileAction()`). Only then is `Reconciler::reconcile()` invoked.
3. **Automated Match Exceptions**:
   System-driven automatic reconciliation occurs in only two specific deterministic scenarios:
   - **Direct Payment Registration**: When a payment is recorded directly against an invoice via `PaymentRegister`, `PaymentRegistrar::reconcilePayments()` deterministically reconciles the created payment against that specific invoice line.
   - **Document Reversal**: When `MoveWorkflow::reverse()` is invoked, the original move and its inverted counterpart entry are automatically reconciled via `Reconciler::reconcileReversals()`.
4. **No Fuzzy / Heuristic Matcher**:
   There is **NO** background cron, AI, fuzzy text matching, bank statement rule parser, or automated clearing engine that matches and settles transactions across the general ledger without direct user selection.
