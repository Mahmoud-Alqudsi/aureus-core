---
status: verified
source_of_truth: source-code
last_verified: 2026-09-03
scope: plugins/webkul/sales, plugins/webkul/products, plugins/webkul/invoices, plugins/webkul/accounts
confidence: high
---

# Sales Order & Commercial Fulfillment Business Rules

## 1. Scope

### What this document covers
This document formalizes the mathematical calculation rules, algorithms, precedence hierarchies, numerical thresholds, validation constraints, precision behaviors, and cross-domain dependencies governing commercial quotations, sales orders, customer pricing, invoiceable quantities, profitability margins, quotation validity, and credit limit validations in Aureus ERP. The analysis is grounded in `plugins/webkul/sales` and its collaborating plugins (`products`, `invoices`, `accounts`, `inventories`).

Specifically, this document formalizes:
1. Unit price resolution and the relationship between static prices, supplier pricing, and multi-tier price rules.
2. Line-level discount calculation and net unit price derivations.
3. Invoiceable quantity algorithms under both `ORDER` and `DELIVERY` invoicing policies.
4. Stock delivery synchronization, partial fulfillments, and return order netting.
5. Customer invoice synchronization, cancellations, and credit note reversal deductions.
6. Credit limit and customer risk evaluation investigation (definitive verification across the confirmation path).
7. Quotation expiration parameters and enforcement behavior.
8. Line-level and order-level profit margin and markup formulas.
9. Advance payment / down payment invoice mechanics and gaps.

### What it intentionally does not cover
- **Workflow Sequences**: Step-by-step quotation creation, email dispatch, warehouse picking, and invoice payment workflows are documented in `docs/workflows/sales.md`.
- **Tax Calculation & Multi-Currency Rules**: Detailed tax engine mechanics (percentage, division, fixed, group taxes, compounding, repartition lines) and foreign currency exchange rate conversions are documented in `docs/business-rules/accounting.md`.
- **Physical Warehouse Routing**: Quant reservations, putaway rules, and removal strategies are documented in `docs/business-rules/inventory.md`.
- **Database Schema DDL**: Physical tables, indexes, and foreign key definitions are documented in `docs/database/erds/finance.md` and `docs/database/erds/operations.md`.

---

## 2. Calculation Rules

### 2.1 Pricing & Discount Calculation Engine

#### Rule Definition
Calculates the selling price unit (`price_unit`), discounted unit price, line subtotal, tax amount, and total price for each line item on a quotation or sales order.

- **Status**: [VERIFIED]
- **Evidence**: `plugins/webkul/sales/src/Filament/Clusters/Orders/Resources/QuotationResource/Schemas/QuotationForm.php:1160-1275`, `plugins/webkul/sales/src/Services/OrderCalculator.php:52-115`, `plugins/webkul/sales/src/Models/OrderLine.php:30-80`.

#### Unit Price Resolution Algorithm (`QuotationForm::calculateUnitPrice`)
When a product is added to a quotation line in the administrative UI, the default unit price is resolved via the following sequence:
1. **Supplier Pricelist Check**:
   The product's supplier pricelist entries (`ProductSupplierInfo` via `$product->sellers`) are queried and sorted descending by `sort`:
   $$\text{CandidatePrices} = \text{sellers}->\text{sortByDesc}('sort')->\text{filter}(\dots)$$
   A candidate rule matches if:
   - `partner_id` matches the quotation customer (`$record->partner_id`).
   - `currency_id` matches the quotation currency (`$record->currency_id`).
   - `min_qty` is less than or equal to the line ordered quantity (`product_qty \ge min_qty`).
   If a matching vendor price rule exists:
   $$\text{resolvedPrice} = \text{CandidatePrices}->\text{first}()->\text{price}$$
2. **Product Base Price Fallback**:
   If no matching supplier pricelist rule exists:
   $$\text{resolvedPrice} = \text{product}->\text{price} \mathbin{??} \text{product}->\text{cost} \mathbin{??} 0.0$$
3. **Unit of Measure Scaling**:
   The resolved price is scaled from the product's base stocking UOM to the line's transaction UOM:
   $$\text{uomFactor} = \text{lineUOM}->\text{computeQuantity}(1, \text{product}->\text{uom}, \text{precisionRounding} = \text{false})$$
   $$\text{price\_unit} = \text{round}(\text{resolvedPrice} \times \text{uomFactor}, 2)$$

#### Multi-Tier Price Lists (`PriceList` / `PriceRuleItem`) & Resolver Service
- **Dynamic Resolution Engine**:
  - Multi-tier customer pricing is managed through `PriceList` (`products_product_price_lists`) and line rule items `PriceRuleItem` (`products_price_rule_items`).
  - When enabled via `ProductSettings::$enable_price_lists`, customer price lists are selected or defaulted onto quotations (`sales_orders.price_list_id`).
  - Dynamic line unit pricing is evaluated at quote authoring and order storage via `PriceListResolver::resolve()`.
  - The resolver prioritizes candidate rules across specificity scopes (`PriceRuleApplyTo`: variant -> product -> category -> global), applying formula adjustments, quantity break tiers, date window validation, and currency conversions.
  - See canonical multi-tier pricing specification in [`docs/business-rules/pricing.md`](pricing.md).

#### Line-Level Discount & Net Amounts (`OrderCalculator::applyLineTotals`)
1. **Discount Type**:
   Discounts are strictly **percentage-based** (`OrderLine::$discount`, `decimal(15, 4)` default `0.0`).
2. **Discounted Unit Price**:
   $$\text{discountedUnit} = \begin{cases} \text{price\_unit} \times \left(1 - \frac{\text{discount}}{100}\right) & \text{if } \text{discount} > 0 \\ \text{price\_unit} & \text{otherwise} \end{cases}$$
3. **Line Subtotal ($\text{price\_subtotal}$)**:
   - When no taxes apply:
     $$\text{price\_subtotal} = \text{round}(\text{discountedUnit} \times \text{product\_qty}, 4)$$
     $$\text{price\_tax} = 0.0, \quad \text{price\_total} = \text{price\_subtotal}$$
   - When taxes apply:
     Line taxes are delegated to the accounting tax computation engine (`Tax::computeAll`):
     $$\text{taxResult} = \text{Tax::computeAll}(\text{taxes}, \text{discountedUnit}, \text{currency}, \text{product\_qty}, \text{product}, \text{partner})$$
     $$\text{price\_subtotal} = \text{round}(\text{taxResult}['total\_excluded'], 4)$$
     $$\text{price\_tax} = \text{round}(\text{taxResult}['total\_included'] - \text{taxResult}['total\_excluded'], 4)$$
     $$\text{price\_total} = \text{round}(\text{taxResult}['total\_included'], 4)$$
4. **Derived Line Unit Rates**:
   $$\text{price\_reduce\_taxexcl} = \begin{cases} \text{round}\left(\frac{\text{price\_subtotal}}{\text{product\_uom\_qty}}, 4\right) & \text{if } \text{product\_uom\_qty} > 0 \\ 0.0 & \text{otherwise} \end{cases}$$
   $$\text{price\_reduce\_taxinc} = \begin{cases} \text{round}\left(\frac{\text{price\_total}}{\text{product\_uom\_qty}}, 4\right) & \text{if } \text{product\_uom\_qty} > 0 \\ 0.0 & \text{otherwise} \end{cases}$$

---

### 2.2 Invoiceable Quantity Calculation (InvoicePolicy: ORDER vs DELIVERY)

#### Rule Definition
Calculates the remaining quantity eligible for customer billing on each order line (`qty_to_invoice`), governing whether billing occurs before or after warehouse fulfillment.

- **Status**: [VERIFIED]
- **Evidence**: `plugins/webkul/sales/src/Services/OrderCalculator.php:117-185,247-290`, `plugins/webkul/sales/src/Services/Invoicer.php:62-86`.

#### Invoicing Policy Resolution Hierarchy (`OrderCalculator::invoicePolicyFor`)
The governing policy for an order line resolves in the following fallback sequence:
1. Product Specific Policy: `$line->product->invoice_policy`
2. Parent Variant Product Policy: `$line->product->parent->invoice_policy`
3. System-Wide Fallback Setting: `InvoiceSettings::$invoice_policy` (configured in `sales_invoicing`)

#### Mathematical Formulas (`OrderCalculator::refreshQtyToInvoice`)
If the order is not in `OrderState::SALE` or the line is a display section/note (`display_type != null`):
$$\text{qty\_to\_invoice} = 0.0$$

Otherwise:
- **Policy A: Based on Ordered Quantities (`InvoicePolicy::ORDER`)**:
  $$\text{qty\_to\_invoice} = \text{product\_uom\_qty} - \text{qty\_invoiced}$$
  - Enables immediate invoicing upon order confirmation prior to warehouse picking.
- **Policy B: Based on Delivered Quantities (`InvoicePolicy::DELIVERY`)**:
  $$\text{qty\_to\_invoice} = \text{qty\_delivered} - \text{qty\_invoiced}$$
  - Restricts customer billing strictly to quantities confirmed as shipped by warehouse operations.

#### Line Invoicing Status Lifecycle (`OrderCalculator::refreshLineInvoiceStatus`)
- **Under `InvoicePolicy::ORDER`**:
  $$\text{invoice\_status} = \begin{cases} \text{INVOICED} & \text{if } \text{qty\_invoiced} \ge \text{product\_uom\_qty} \\ \text{UP\_SELLING} & \text{if } \text{qty\_delivered} > \text{product\_uom\_qty} \\ \text{TO\_INVOICE} & \text{otherwise} \end{cases}$$
- **Under `InvoicePolicy::DELIVERY`**:
  $$\text{invoice\_status} = \begin{cases} \text{INVOICED} & \text{if } \text{qty\_invoiced} \ge \text{product\_uom\_qty} \\ \text{TO\_INVOICE} & \text{if } \text{qty\_to\_invoice} \ne 0 \lor \text{qty\_delivered} == \text{product\_uom\_qty} \\ \text{NO} & \text{otherwise} \end{cases}$$

#### Untaxed Amount Remaining to Invoice (`OrderCalculator::refreshUntaxedAmountToInvoice`)
$$\text{baseQty} = \begin{cases} \text{qty\_delivered} & \text{if } \text{policy} == \text{DELIVERY} \\ \text{product\_uom\_qty} & \text{if } \text{policy} == \text{ORDER} \end{cases}$$
$$\text{priceReduce} = \text{price\_unit} \times \left(1 - \frac{\text{discount} \mathbin{??} 0.0}{100.0}\right)$$
$$\text{untaxed\_amount\_to\_invoice} = (\text{priceReduce} \times \text{baseQty}) - \text{untaxed\_amount\_invoiced}$$

---

### 2.3 Delivered Quantity & Stock Returns Offsetting

#### Rule Definition
Calculates the physical quantity delivered to the customer (`qty_delivered`) by synchronizing with settled warehouse stock moves.

- **Status**: [VERIFIED]
- **Evidence**: `plugins/webkul/sales/src/Services/OrderCalculator.php:143-162`, `plugins/webkul/sales/src/Services/ProcurementRequester.php:185-215`.

#### Calculation Algorithm (`OrderCalculator::refreshQtyDelivered`)
1. **Method Check**:
   If `$line->qty_delivered_method === QtyDeliveredMethod::MANUAL`, the delivered quantity is updated manually by the user (default `0.0`).
   If `$line->qty_delivered_method === QtyDeliveredMethod::STOCK_MOVE` (standard for storable goods):
2. **Move Aggregation**:
   `ProcurementRequester::outgoingAndIncomingMoves($line)` retrieves all stock moves associated with the order line.
   Delivered quantities are summed strictly from moves in state `MoveState::DONE`, converted to the sales line UOM:
   $$\text{deliveredOutgoing} = \sum_{\substack{\text{Move} \in \mathcal{M}_{\text{out}} \\ \text{state} = \text{DONE}}} \text{Move}->\text{uom}->\text{computeQuantity}(\text{Move}->\text{quantity}, \text{line}->\text{uom}, \text{roundingMethod} = \text{'HALF-UP'})$$
   $$\text{deliveredIncoming} = \sum_{\substack{\text{Move} \in \mathcal{M}_{\text{in}} \\ \text{state} = \text{DONE}}} \text{Move}->\text{uom}->\text{computeQuantity}(\text{Move}->\text{quantity}, \text{line}->\text{uom}, \text{roundingMethod} = \text{'HALF-UP'})$$
3. **Net Delivery Formula**:
   $$\text{qty\_delivered} = \text{deliveredOutgoing} - \text{deliveredIncoming}$$
   - **Returns Impact**: Customer returns (incoming return moves from Customer $\to$ Stock) directly decrement `qty_delivered`. Under `InvoicePolicy::DELIVERY`, this reduction immediately reduces `qty_to_invoice` or produces a negative delta if already invoiced.

---

### 2.4 Invoiced Quantity & Credit Note Netting

#### Rule Definition
Synchronizes billed quantities (`qty_invoiced`) and amounts (`untaxed_amount_invoiced`) across all accounting invoices and credit notes linked via `sales_order_line_invoices`.

- **Status**: [VERIFIED]
- **Evidence**: `plugins/webkul/sales/src/Services/OrderCalculator.php:117-141,310-332`, `plugins/webkul/sales/src/Listeners/ComputeSaleOrderFromMoveListener.php:18-65`.

#### Calculation Algorithm (`OrderCalculator::refreshQtyInvoiced`)
Iterates over all linked `accountMoveLines`:
1. **State Gate**: Moves in state `MoveState::CANCEL` are ignored (unless payment state is legacy `INVOICING_LEGACY`).
2. **Quantity Conversion & Netting**:
   $$\text{convertedQty} = \text{accountMoveLine}->\text{uom}->\text{computeQuantity}(\text{accountMoveLine}->\text{quantity}, \text{line}->\text{uom})$$
   $$\text{qty\_invoiced} = \sum_{\text{OUT\_INVOICE}} \text{convertedQty} - \sum_{\text{OUT\_REFUND}} \text{convertedQty}$$
   - **Credit Note Netting**: Posting a customer credit note (`MoveType::OUT_REFUND`) linked to the sales order **directly subtracts** from `qty_invoiced`, reopening invoiceable capacity (`qty_to_invoice`) on the sales order line.

---

### 2.5 Margin & Profitability Calculation

#### Rule Definition
Calculates line-level and order-level profit margins and margin percentages to monitor commercial profitability.

- **Status**: [VERIFIED]
- **Evidence**: `plugins/webkul/sales/src/Filament/Clusters/Orders/Resources/QuotationResource/Schemas/QuotationForm.php:1238-1344`, `plugins/webkul/sales/src/Models/OrderLine.php:40-50`.

#### Line-Level Margin Formulas (`QuotationForm::calculateMargin`)
1. **Inputs**:
   - $\text{sellingPrice} = \text{OrderLine}->\text{price\_unit}$
   - $\text{costPrice} = \text{OrderLine}->\text{purchase\_price}$ (Derived from `Product::$cost`, scaled by UOM factor)
   - $\text{quantity} = \text{OrderLine}->\text{product\_uom\_qty}$
   - $\text{discount} = \text{OrderLine}->\text{discount}$ (Percentage)
2. **Formulas**:
   $$\text{discountedPrice} = \text{sellingPrice} \times \left(1 - \frac{\text{discount}}{100}\right)$$
   $$\text{marginPerUnit} = \text{discountedPrice} - \text{costPrice}$$
   $$\text{margin} = \text{round}(\text{marginPerUnit} \times \text{quantity}, 4)$$
   $$\text{margin\_percent} = \begin{cases} \text{round}\left(\left(\frac{\text{marginPerUnit}}{\text{discountedPrice}}\right) \times 100, 4\right) & \text{if } \text{marginPerUnit} \ne 0 \land \text{discountedPrice} \ne 0 \\ 0.0 & \text{otherwise} \end{cases}$$

#### Order-Level Margin Totals (`QuotationForm::calculateQuotationTotals`)
$$\text{orderSubtotal} = \sum_{\text{lines}} \text{price\_subtotal}$$
$$\text{orderMargin} = \sum_{\text{lines}} \text{margin}$$
$$\text{orderMarginPercentage} = \begin{cases} \text{round}\left(\left(\frac{\text{orderMargin}}{\text{orderSubtotal}}\right) \times 100, 2\right) & \text{if } \text{orderSubtotal} > 0 \\ 0.0 & \text{otherwise} \end{cases}$$

- **Cost Source Finding**: The cost basis (`purchase_price`) is populated exclusively from static product master data (`Product::$cost`). It does **not** query warehouse stock moves, landed costs, or valuation layers.

---

### 2.6 Advance Payment & Down Payment Invoicing

#### Rule Definition
Governs the creation and tracking of down payment invoices prior to final delivery.

- **Status**: [PARTIALLY VERIFIED]
- **Evidence**: `plugins/webkul/sales/src/Services/Invoicer.php:20-38`, `plugins/webkul/sales/src/Filament/Clusters/Orders/Resources/QuotationResource/Actions/CreateInvoiceAction.php:45-53`, `plugins/webkul/sales/src/Models/AdvancedPaymentInvoice.php`.

#### Operational Behavior
1. **Enum Definition**: `AdvancedPayment` defines `DELIVERED`, `PERCENTAGE`, and `FIXED`.
2. **UI Gate**: `CreateInvoiceAction::setUp()` explicitly restricts the user modal selection to `DELIVERED` only:
   `Arr::only($options, [AdvancedPayment::DELIVERED->value])`. Down payment options (`PERCENTAGE` and `FIXED`) cannot be triggered via standard UI.
3. **Execution Gaps**:
   - When invoked programmatically with `PERCENTAGE` or `FIXED`, `Invoicer::invoiceOrder()` persists an `AdvancedPaymentInvoice` record and attaches the order to `sales_advance_payment_invoice_order_sales`, but does **not create an `AccountMove`**.
   - While `AdvancedPaymentInvoice` persists `'deduct_down_payments' => true`, `Invoicer::createInvoiceLine()` contains **no deduction algorithm** to subtract previous down payments from regular delivery invoices.

---

## 3. Precedence & Matching Rules

### 3.1 Unit Price Fallback Precedence

```
1. Customer & Currency Supplier Pricelist:
   ProductSupplierInfo matching partner_id, currency_id, and min_qty <= product_qty
   │ (if none found)
   ▼
2. Product Base List Price:
   Product::$price
   │ (if null or zero)
   ▼
3. Product Static Cost:
   Product::$cost
   │ (if null or zero)
   ▼
4. Zero Fallback:
   0.00
   │
   ▼
Applied UOM Scale:
   price_unit = resolvedPrice * UOM::computeQuantity(1, product->uom)
```

### 3.2 Invoicing Policy Precedence

```
1. Specific Product Policy:
   Product::$invoice_policy ('order' or 'delivery')
   │ (if null)
   ▼
2. Parent Product Variant Policy:
   Product::parent::$invoice_policy
   │ (if null)
   ▼
3. System-Wide Default Setting:
   InvoiceSettings::$invoice_policy (sales_invoicing group)
```

### 3.3 Overall Order Delivery Status Precedence (`OrderCalculator::refreshDeliveryStatus`)

Evaluates all linked inventory operations (`$order->operations`):
1. If operations list is empty or all operations are `CANCELED`: $\implies \text{OrderDeliveryStatus::NO}$
2. If every operation is settled (`DONE` or `CANCELED`): $\implies \text{OrderDeliveryStatus::FULL}$
3. If at least one operation is `DONE` and at least one line has `qty_delivered > 0`: $\implies \text{OrderDeliveryStatus::PARTIAL}$
4. If at least one operation is `DONE` but delivered quantities remain zero: $\implies \text{OrderDeliveryStatus::STARTED}$
5. Default (operations exist in draft/waiting/confirmed/assigned): $\implies \text{OrderDeliveryStatus::PENDING}$

### 3.4 Overall Order Invoicing Status Precedence (`OrderCalculator::refreshInvoiceStatus`)

If order is not in `OrderState::SALE`: $\implies \text{InvoiceStatus::NO}$. Otherwise:
1. If any line has status `TO_INVOICE`: $\implies \text{InvoiceStatus::TO_INVOICE}$
2. If any line has status `INVOICED` and none are `TO_INVOICE`: $\implies \text{InvoiceStatus::INVOICED}$
3. If any line has status `UP_SELLING`: $\implies \text{InvoiceStatus::UP_SELLING}$
4. Default: $\implies \text{InvoiceStatus::NO}$

---

## 4. Thresholds & Limits

| Parameter / Field | Source Location | Exact Value / Formula | Behavior When Violated | Status |
| :--- | :--- | :--- | :--- | :--- |
| **Minimum Unit Price** | `OrderRequest.php:52` | `price_unit >= 0` | Validation error HTTP 422: `"The price unit must be at least 0."` | [VERIFIED] |
| **Maximum Unit Price** | `OrderRequest.php:52` | `price_unit <= 99999999999` | Validation error HTTP 422: Value exceeds numerical capacity. | [VERIFIED] |
| **Minimum Quantity** | `OrderRequest.php:50` | `product_uom_qty >= 0.0001` | Validation error HTTP 422: Quantity cannot be zero or negative. | [VERIFIED] |
| **Discount Percentage Range** | `OrderRequest.php:53` | `discount >= 0 && discount <= 100` | Validation error HTTP 422: Discount must be between 0 and 100%. | [VERIFIED] |
| **Quotation Expiration Validity** | `QuotationAndOrderSettings.php:9` | Default days added to `now()` | Descriptive only. Does **not** block quotation confirmation even if date is past. | [VERIFIED] |
| **Zero Invoiceable Quantity Barrier** | `CreateInvoiceAction.php:71-79` | `qty_to_invoice == 0` on all lines | Halts execution with warning notification; prevents creation of blank invoices. | [VERIFIED] |
| **Administrative Lock Barrier** | `QuotationForm.php:106,567` | `Order::$locked === true` | UI fields and line items become disabled; prevents unauthorized edits. | [VERIFIED] |
| **Database Decimal Precision** | Migrations | `DECIMAL(15, 4)` | Supports up to 4 decimal places across all sales amounts and quantities. | [VERIFIED] |

---

## 5. Validation Constraints & Enforcement Matrix

| Business Rule | Declared | UI (Filament) | API (FormRequest) | Service Layer | Model / Event | Database Schema | Overall Enforcement |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Customer Credit Limit Check** | [DECLARED] (`Partner::$credit_limit`) | [NOT ENFORCED] | [NOT ENFORCED] | [NOT ENFORCED] | [NOT ENFORCED] | [NOT ENFORCED] (String column) | **Non-Existent (0%)** |
| **Customer Receivables Balance Check** | [NOT IMPLEMENTED] | [NOT ENFORCED] | [NOT ENFORCED] | [NOT ENFORCED] | [NOT ENFORCED] | [NOT ENFORCED] | **Non-Existent (0%)** |
| **Quotation Expiration Blocking** | [DECLARED] (`validity_date`) | [NOT ENFORCED] | [NOT ENFORCED] | [NOT ENFORCED] | [NOT ENFORCED] | [NOT ENFORCED] | **Descriptive Only** |
| **Multi-Tier Price Lists Engine** | [ENFORCED] (`PriceList`, `PriceRuleItem`) | [ENFORCED] (`PriceListForm`, `QuotationForm`) | [ENFORCED] (`PriceListResolver`) | [ENFORCED] (`OrderPriceListTest`, `PriceListResolverTest`) | [ENFORCED] (`/api/v1/products/price-lists`) | [ENFORCED] (`products_product_price_lists`) | **Operational via `PriceListResolver`** |
| **Advance Payment Line Deduction** | [DECLARED] (`deduct_down_payments`) | [NOT ENFORCED] (Option hidden) | [NOT ENFORCED] | [NOT IMPLEMENTED] | [NOT IMPLEMENTED] | [DECLARED] (Column exists) | **Unenforced / Code Stub** |
| **Non-Negative Price and Quantities** | [VERIFIED] | [VERIFIED] (`minValue(0)`) | [VERIFIED] (`min:0`) | [VERIFIED] | [VERIFIED] | [NOT ENFORCED] | [VERIFIED] |
| **Discount Range (0% to 100%)** | [VERIFIED] | [VERIFIED] (`minValue(0)`, `maxValue(100)`) | [VERIFIED] (`between:0,100`) | [VERIFIED] | [VERIFIED] | [NOT ENFORCED] | [VERIFIED] |
| **Duplicate Confirmation Prevention** | [VERIFIED] | [VERIFIED] (`hidden()`) | [VERIFIED] (Checks `state`) | [VERIFIED] | [VERIFIED] | [NOT ENFORCED] | [VERIFIED] |
| **Confirmed Order Lock Protection** | [VERIFIED] | [VERIFIED] (`disabled()`) | [VERIFIED] (`toggleLock`) | [VERIFIED] (`OrderWorkflow`) | [VERIFIED] | [VERIFIED] (`locked` column) | [VERIFIED] |
| **Credit Note Reversal Offset** | [VERIFIED] | [VERIFIED] | [VERIFIED] | [VERIFIED] (`OrderCalculator`) | [VERIFIED] (`ComputeSaleOrderFromMoveListener`) | [VERIFIED] (Pivot table) | [VERIFIED] |
| **Stock Delivery Return Offset** | [VERIFIED] | [VERIFIED] | [VERIFIED] | [VERIFIED] (`OrderCalculator`) | [VERIFIED] (`ComputeSaleOrderListener`) | [VERIFIED] (Moves link) | [VERIFIED] |

---

## 6. Rounding & Precision Rules

1. **Storage Precision**:
   All monetary amounts (`price_unit`, `discount`, `price_subtotal`, `price_total`, `purchase_price`, `margin`, `margin_percent`) and quantities (`product_uom_qty`, `product_qty`, `qty_delivered`, `qty_invoiced`, `qty_to_invoice`) are stored as `DECIMAL(15, 4)` in MySQL/PostgreSQL.
2. **Intermediate Calculation Rounding**:
   - Line subtotal, tax amount, and total: Rounded to **4 decimal places** (`round(..., 4)`) in `OrderCalculator::applyLineTotals()` and `QuotationForm::calculateLineTotals()`.
   - Reduced unit prices (`price_reduce_taxexcl`, `price_reduce_taxinc`): Rounded to **4 decimal places**.
   - Margin and margin percentage: Rounded to **4 decimal places** on line level (`round($margin, 4)`).
3. **Quotation Header Display Rounding**:
   In `QuotationForm::calculateQuotationTotals()`, aggregated order amounts (`subtotal`, `totalTax`, `grandTotal`, `margin`, `marginPercentage`) are rounded to **2 decimal places** (`round(..., 2)`).
4. **Tax Computation Delegation**:
   Taxes are computed via `Tax::computeAll()`, adhering to the rounding and repartition rules documented in `docs/business-rules/accounting.md`.

---

## 7. Cross-Domain Rule Dependencies

```
┌────────────────────────────────────────────────────────────────────────────────┐
│                           Sales Cross-Domain Matrix                            │
└────────────────────────────────────────────────────────────────────────────────┘
                                  │
         ┌────────────────────────┼────────────────────────┐
         ▼                        ▼                        ▼
┌──────────────────┐    ┌──────────────────┐    ┌──────────────────┐
│  products plugin │    │inventories plugin│    │ accounts plugin  │
├──────────────────┤    ├──────────────────┤    ├──────────────────┤
│ - Master Catalog │    │ - ProcurementReq │    │ - AccountMove    │
│ - Supplier Info  │    │   creates Moves  │    │   (OUT_INVOICE)  │
│ - Storable Flag  │    │ - OperationDone  │    │ - MoveConfirmed  │
│ - Base UOM & Cost│    │   updates        │    │   updates        │
│ - PriceList/Rule │    │   qty_delivered  │    │   qty_invoiced   │
│    Unused)       │    │ - Returns reduce │    │ - MoveReversed   │
│                  │    │   qty_delivered  │    │   reduces        │
│                  │    │                  │    │   qty_invoiced   │
└──────────────────┘    └──────────────────┘    └──────────────────┘
         ▲                        ▲                        ▲
         │                        │                        │
         └────────────────────────┼────────────────────────┘
                                  │
                        ┌──────────────────┐
                        │   sales plugin   │
                        │  (OrderWorkflow) │
                        └──────────────────┘
                                  │
         ┌────────────────────────┴────────────────────────┐
         ▼                                                 ▼
┌──────────────────┐                             ┌──────────────────┐
│  invoices plugin │                             │ partners plugin  │
├──────────────────┤                             ├──────────────────┤
│ - UI Resource    │                             │ - Customer data  │
│   presentation   │                             │ - Credit limit   │
│   for billing    │                             │   (Decorative/   │
│                  │                             │    Unchecked)    │
└──────────────────┘                             └──────────────────┘
```

### Key Integration Mechanics
1. **`sales` $\to$ `inventories` (Fulfillment)**:
   - When an order is confirmed, `OrderWorkflow::confirm()` calls `ProcurementRequester::requestForLines()`, generating delivery transfers.
   - When warehouse transfers complete, `OperationDone` is intercepted by `ComputeSaleOrderListener`, executing `OrderCalculator::refreshQtyDelivered()`.
2. **`sales` $\to$ `accounts` & `invoices` (Billing)**:
   - `Invoicer::createInvoice()` instantiates an `AccountMove` (`move_type = OUT_INVOICE`) and binds order lines via `sales_order_line_invoices`.
   - When the invoice is posted (`MoveConfirmed`), `ComputeSaleOrderFromMoveListener` executes `OrderCalculator::refreshQtyInvoiced()`.
   - When a credit note is posted (`MoveReversed`), `ComputeSaleOrderFromMoveListener` links credit lines and subtracts from `qty_invoiced`.
3. **`sales` $\to$ `products` (Pricing & Packaging)**:
   - Line items consume product base prices, supplier pricelists, and packaging divisors (`Packaging::where('product_id', ...)->orderByDesc('qty')`).

---

## 8. Edge Cases & Known Gaps

1. **Complete Absence of Credit Limit Enforcement**:
   - Customers can place orders of any monetary amount regardless of creditworthiness, overdue balances, or values defined in `Partner::$credit_limit`.
2. **Unenforced Quotation Expiration**:
   - `validity_date` is purely informational. Expired quotations can be confirmed at any time without warnings or managerial approvals.
3. **Multi-Tier Price List Resolution**:
   - Resolved via `PriceListResolver::resolve()` integrated into `QuotationForm` and `OrderController` with recursion depth guards (`MAX_BASE_DEPTH = 5`).
4. **Advance Payment Down Payment Incomplete**:
   - Down payment creation is restricted in the UI to delivered quantities only. Programmatic down payment creation produces no accounting lines, and final invoice down payment deductions are not implemented.
5. **Static Margin Cost Basis**:
   - Profit margins reflect static product catalog cost (`Product::$cost`), ignoring real procurement costs or inventory purchase receipt prices.

---

## 9. Unknowns / Inferences

### [UNKNOWN]
1. **Down Payment Accounting Roadmap**: It is [UNKNOWN] when multi-step down payment accounting lines (clearing account crediting and deduction from subsequent invoices) will be implemented to support `AdvancedPayment::PERCENTAGE` and `FIXED`.
2. **Price Rule Integration Plan**: [RESOLVED] Multi-tier customer pricing is fully operational using `PriceList` and `PriceRuleItem` with `PriceListResolver`.

### [INFERRED]
1. **Supplier Pricelist in QuotationForm**: The presence of supplier pricelist lookups (`$product->sellers`) in `QuotationForm::calculateUnitPrice` is inferred to be an intentional feature allowing customer-specific purchase contract pricing or dropship quote proposals.

---

## 10. Evidence References

| Area | Relative File Path | Key Symbols / Methods |
| :--- | :--- | :--- |
| **Quotation Form UI & Margin Engine** | `plugins/webkul/sales/src/Filament/Clusters/Orders/Resources/QuotationResource/Schemas/QuotationForm.php` | `calculateUnitPrice()`, `calculateLineTotals()`, `calculateMargin()`, `calculateQuotationTotals()` |
| **Order Calculator Service** | `plugins/webkul/sales/src/Services/OrderCalculator.php` | `recompute()`, `recomputeLine()`, `refreshQtyInvoiced()`, `refreshQtyDelivered()`, `refreshQtyToInvoice()`, `refreshDeliveryStatus()`, `refreshInvoiceStatus()` |
| **Order Workflow Service** | `plugins/webkul/sales/src/Services/OrderWorkflow.php` | `confirm()`, `cancel()`, `toggleLock()`, `backToQuotation()` |
| **Invoicing Service** | `plugins/webkul/sales/src/Services/Invoicer.php` | `invoiceOrder()`, `createInvoice()`, `createInvoiceLine()` |
| **Procurement Requester** | `plugins/webkul/sales/src/Services/ProcurementRequester.php` | `requestForLines()`, `outgoingAndIncomingMoves()`, `cancelOperations()` |
| **Filament Confirm Action** | `plugins/webkul/sales/src/Filament/Clusters/Orders/Resources/QuotationResource/Actions/ConfirmAction.php` | `ConfirmAction::setUp()` |
| **API Order Controller** | `plugins/webkul/sales/src/Http/Controllers/API/V1/OrderController.php` | `confirm()`, `cancel()`, `toggleLock()`, `store()` |
| **Delivery Done Listener** | `plugins/webkul/sales/src/Listeners/ComputeSaleOrderListener.php` | `ComputeSaleOrderListener::handle()` |
| **Invoice Move Listener** | `plugins/webkul/sales/src/Listeners/ComputeSaleOrderFromMoveListener.php` | `ComputeSaleOrderFromMoveListener::handle()` |
| **Partner Model in Accounts** | `plugins/webkul/accounts/src/Models/Partner.php` | `Partner::$credit_limit`, `Partner::$debit_limit`, `Partner::$sale_warn` |
| **Price List Models & Resolver** | `plugins/webkul/products/src/Models/PriceList.php`<br>`plugins/webkul/products/src/Models/PriceRuleItem.php`<br>`plugins/webkul/products/src/Services/PriceListResolver.php` | `PriceList`, `PriceRuleItem`, `PriceListResolver`, `ResolvedPrice`, `PriceRuleType`, `PriceRuleBase` |

---

## 11. Mandatory Final Answer

> **Question:**
> *"Does any credit-limit or customer-balance check exist anywhere in the order-confirmation path?"*

### Explicit Answer:
**No credit-limit or customer-balance check exists anywhere in the order-confirmation path.**

### Evidence & Verification Details:
1. **Execution Path Inspection**:
   - `OrderWorkflow::confirm()` (`plugins/webkul/sales/src/Services/OrderWorkflow.php:43-59`) only transitions `state` to `OrderState::SALE`, sets `invoice_status = TO_INVOICE`, requests warehouse procurements via `ProcurementRequester`, updates the `locked` status, recomputes totals, and dispatches `OrderConfirmed`. It contains zero checks against customer balances or credit limits.
   - `ConfirmAction` in UI (`plugins/webkul/sales/src/Filament/Clusters/Orders/Resources/QuotationResource/Actions/ConfirmAction.php`) checks only that the order is not already in `SALE` or `CANCEL` state.
   - `OrderController::confirm()` in REST API (`plugins/webkul/sales/src/Http/Controllers/API/V1/OrderController.php:195-212`) checks only that `state` is `DRAFT` or `SENT`.
2. **Customer Model Inspection**:
   - `credit_limit` is defined as a `string` column on `partners_partners` (added via migration `2025_02_24_123300_add_additional_columns_to_partners_partners_table.php` in `accounts`).
   - The sales plugin never queries, reads, or evaluates `credit_limit` or `sale_warn` during quotation creation, editing, confirmation, or invoicing.
3. **Behavior When Limit Exceeded**:
   - Because no verification logic exists, any quotation can be confirmed regardless of the customer's outstanding balance, overdue invoices, or creditworthiness.
