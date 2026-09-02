---
status: verified
source_of_truth: source-code
last_verified: 2026-09-03
scope: plugins/webkul/purchases, plugins/webkul/products, plugins/webkul/invoices, plugins/webkul/accounts, plugins/webkul/inventories
confidence: high
---

# Procurement & Purchasing Business Rules

## 1. Scope

### What this document covers
This document formalizes the mathematical calculation rules, algorithms, precedence hierarchies, numerical thresholds, validation constraints, precision behaviors, and cross-domain dependencies governing procurement agreements, blanket purchase orders, vendor pricing, manager approval thresholds, billable quantities, warehouse receipt synchronization, three-way matching, and cancellation guardrails in Aureus ERP. The analysis is grounded in `plugins/webkul/purchases` and its collaborating plugins (`products`, `invoices`, `accounts`, `inventories`).

Specifically, this document formalizes:
1. Multi-tier manager approval thresholds, permissions, and escalation behaviors.
2. Vendor bill matching logic under both `purchase` (ordered) and `receive` (received) control policies.
3. Supplier pricelist resolution algorithms and vendor lead-time handling.
4. Blanket agreements, call-off tenders, and dynamic commitment tracking.
5. Warehouse incoming receipt synchronization, return order deductions, and backorder splits.
6. Strict cancellation guardrails preventing order cancellation after physical or financial movement.
7. Administrative state-based locking mechanisms (`OrderState::DONE`).

### What it intentionally does not cover
- **Workflow Sequences**: Step-by-step RFQ creation, vendor emailing, and physical warehouse inspection procedures are documented in `docs/workflows/purchasing.md`.
- **Tax Calculation & Foreign Currency Conversions**: General ledger currency conversions and tax calculation engines are documented in `docs/business-rules/accounting.md`.
- **Warehouse Storage Routing**: Quant reservations, putaway logic, and stock removal strategies are documented in `docs/business-rules/inventory.md`.
- **Database Schema DDL**: Physical database tables and foreign keys are documented in `docs/database/erds/finance.md` and `docs/database/erds/operations.md`.

---

## 2. Calculation Rules

### 2.1 Multi-Tier Approval Thresholds

#### Rule Definition
Evaluates whether a confirmed purchase order requires managerial review based on total monetary value and user authorization scopes.

- **Status**: Structural capability: [VERIFIED] | Single-tier default threshold: [VERIFIED] (5,000) | Multi-tier monetary thresholds: [UNKNOWN]
- **Evidence**: `plugins/webkul/purchases/src/Settings/OrderSettings.php:7-21`, `plugins/webkul/purchases/database/settings/2025_01_11_094022_create_purchases_order_settings.php:9-12`, `plugins/webkul/purchases/src/Services/OrderWorkflow.php:87-130`.

#### Threshold Configuration & Verification
In `OrderSettings` (`purchases_order` group):
- `enable_order_approval` (`bool`): Default `false` (migration default in `2025_01_11_094022_create_purchases_order_settings.php`).
- `order_validation_amount` (`float`): Default `5000` (in company base currency).

#### Approval Evaluation Algorithm (`OrderWorkflow::confirm`)
When a buyer confirms a draft or sent RFQ (`ConfirmAction` in UI or `POST /api/v1/purchases/purchase-orders/{id}/confirm` in API):
$$\text{needsApproval} = \text{settings}->\text{enable\_order\_approval} \land (\text{record}->\text{total\_amount} \ge \text{settings}->\text{order\_validation\_amount})$$
1. **Direct Approval Path**:
   The order is approved directly (`OrderWorkflow::approve()`) if:
   - Approval is disabled (`!needsApproval`), OR
   - The authenticating user possesses managerial permissions:
     $$\text{canApprove}(\text{user}) \iff \text{user}->\text{resource\_permission} \in [\text{PermissionType::GLOBAL}, \text{PermissionType::GROUP}]$$
2. **Escalation Path**:
   If `needsApproval` is true and the user lacks `GLOBAL` or `GROUP` permission:
   $$\text{record}->\text{state} = \text{OrderState::TO\_APPROVE}$$
   The order halts in `to_approve` state and is blocked from scheduling warehouse receipts.
3. **Manager Sign-Off**:
   An authorized manager with `GLOBAL` or `GROUP` permission opens the order and clicks "Confirm Order" (`ConfirmAction`), executing `OrderWorkflow::approve()`:
   $$\text{record}->\text{state} = \text{settings}->\text{enable\_lock\_confirmed\_orders} \mathrel{?} \text{OrderState::DONE} : \text{OrderState::PURCHASE}$$
   $$\text{record}->\text{approved\_at} = \text{now}()$$
   Calls `ReceiptPlanner::planForOrder()` to generate incoming warehouse receipts.

#### Structural Multi-Tier Capability Finding
- **Single-Tier Implementation**: The active codebase implements a **single-tier approval gate** controlled by `order_validation_amount`.
- **Absence of Hierarchical Multi-Tier Matrix**: There is no multi-tier matrix (e.g. Tier 1: $1,000 for Lead, Tier 2: $10,000 for Director, Tier 3: $50,000 for CFO), no role-based sequential escalation chain, and no absence delegate logic.
- **Default Finding**:
  - Structural capability: [VERIFIED] (Single-tier gate via `to_approve` state).
  - Single-tier default threshold: [VERIFIED] (`5000`).
  - Multi-tier monetary thresholds: [UNKNOWN] (No multi-level thresholds exist in configuration or database).

---

### 2.2 Vendor Bill Matching Logic (2-Way vs 3-Way Match)

#### Rule Definition
Calculates the quantity eligible for vendor billing on each purchase order line (`qty_to_invoice`), governing whether billing reflects ordered quantities (2-Way Match) or received quantities (3-Way Match).

- **Status**: [VERIFIED]
- **Evidence**: `plugins/webkul/purchases/src/Services/OrderCalculator.php:155-174`, `plugins/webkul/purchases/src/Services/Biller.php:12-57`, `plugins/webkul/accounts/src/Services/MoveWorkflow.php:238-285`.

#### Mathematical Formulas (`OrderCalculator::refreshQtyBilled`)
If the purchase order is not in `OrderState::PURCHASE` or `OrderState::DONE`:
$$\text{qty\_to\_invoice} = 0.0$$

Otherwise, billing policy is determined by the product's control policy (`Product::$purchase_method`):
- **Policy 1: Billed on Ordered Quantities (`purchase_method == 'purchase'`) [2-Way Match]**:
  $$\text{qty\_to\_invoice} = \text{product\_qty} - \text{qty\_invoiced}$$
  - Allows vendor bills to be posted immediately upon order confirmation prior to warehouse receipt.
- **Policy 2: Billed on Received Quantities (`purchase_method == 'receive'`) [3-Way Match]**:
  $$\text{qty\_to\_invoice} = \text{qty\_received} - \text{qty\_invoiced}$$
  - Restricts default billing strictly to quantities confirmed as received by warehouse operations.

#### Over-Billing & Negative Invoiceable Quantities (`Biller::createBill`)
When creating a vendor bill from a purchase order (`CreateBillAction`):
1. If $\text{record}->\text{qty\_to\_invoice} \ge 0$:
   - Generates an `AccountMove` of type **`IN_INVOICE`** (Standard Vendor Bill) with:
     $$\text{billLine}->\text{quantity} = \text{abs}(\text{orderLine}->\text{qty\_to\_invoice})$$
2. If $\text{record}->\text{qty\_to\_invoice} < 0$ (occurs when billed quantities exceed received/ordered goods):
   - The system automatically flips the move type to **`IN_REFUND`** (Vendor Debit Note / Refund):
     $$\text{move\_type} = \text{AccountEnums\MoveType::IN\_REFUND}$$
     $$\text{billLine}->\text{quantity} = \text{abs}(\text{orderLine}->\text{qty\_to\_invoice})$$
   - Credits back the over-billed quantity automatically.

#### Reality Check: Three-Way Match Enforcement on Posting
- **No Hard Blocking on Posting**: While `Biller` defaults draft bill lines to `qty_to_invoice`, if an accounting user manually edits a draft Vendor Bill to exceed `qty_received` or `product_qty`, `MoveWorkflow::post()` **does not block posting**.
- **No Price Variance Split**: If the vendor bill unit price differs from the purchase order line unit price, no price difference ledger account (`Price Variance`) is generated. The entire billed amount posts directly to the expense account.
- **Summary**: Matching in Aureus ERP is **informative and status-driven**, not a hard blocking posting validator.

---

### 2.3 Supplier Price List Resolution

#### Rule Definition
Resolves the default purchase unit price (`price_unit`) for a product on an RFQ or purchase order line based on vendor pricelists, quantities, and currencies.

- **Status**: [VERIFIED]
- **Evidence**: `plugins/webkul/purchases/src/Filament/Admin/Clusters/Orders/Resources/OrderResource/Schemas/OrderForm.php:849-874,1090-1117`.

#### Precedence Hierarchy (`OrderForm::calculateUnitPrice`)
When a product is selected on a purchase order line:
1. **Vendor Pricelist Lookup (`ProductSupplierInfo` via `$product->sellers`)**:
   Pricelist entries are evaluated and sorted descending by priority (`sortByDesc('sort')`):
   $$\text{Candidates} = \text{product}->\text{sellers}->\text{filter}(\dots)$$
   A candidate rule matches if:
   - `partner_id` matches the PO vendor (`$order->partner_id`).
   - `currency_id` matches the PO currency (`$order->currency_id`).
   - `min_qty` $\le$ line quantity (`$get('product_qty') \ge \text{min\_qty}$).
   If matching rules exist:
   $$\text{vendorPrice} = \text{Candidates}->\text{first}()->\text{price}$$
2. **Product Cost Fallback**:
   If no matching vendor pricelist rule exists:
   $$\text{vendorPrice} = \text{product}->\text{cost} \mathbin{?:} \text{product}->\text{price} \mathbin{?:} 0.0$$
   *(Note: Purchases prefers static `cost` before sales `price`, the exact inverse of Sales pricing).*
3. **Unit of Measure Scaling**:
   $$\text{uomQty} = \text{lineUOM}->\text{computeQuantity}(1, \text{product}->\text{uom}, \text{precisionRounding} = \text{false})$$
   $$\text{price\_unit} = \text{round}(\text{vendorPrice} \times \text{uomQty}, 2)$$

#### Vendor Lead Time Handling
- `ProductSupplierInfo::$delay` captures delivery lead time in days.
- In active source code, `delay` is **descriptive/informative** in the UI. `OrderLine::$planned_at` defaults to `now()` (or is manually selected), and is passed directly to the warehouse receipt move (`$line->planned_at ?? $order->planned_at`).

---

### 2.4 Blanket Order & Call for Tenders (Purchase Agreements)

#### Rule Definition
Governs long-term supplier contracts (`Requisition`), target call-off quantities, and agreement lifecycle management.

- **Status**: [VERIFIED]
- **Evidence**: `plugins/webkul/purchases/src/Models/Requisition.php:23-137`, `plugins/webkul/purchases/src/Models/RequisitionLine.php:23-100`, `plugins/webkul/purchases/src/Filament/Admin/Clusters/Orders/Resources/OrderResource/Schemas/OrderForm.php:1120-1165`.

#### Supported Agreement Types (`RequisitionType`)
- **`BLANKET_ORDER` (`blanket_order`)**: Long-term contract with a single supplier defining target quantities and fixed unit prices for repeat call-offs.
- **`PURCHASE_TEMPLATE` (`purchase_template`)**: Preset line template for recurring procurement orders.

#### Call-Off Quantity Deduction Algorithm (`RequisitionLine::$ordered_qty`)
When child purchase orders are confirmed against an agreement (`order.requisition_id = requisition.id`), the quantity is tracked dynamically:
$$\text{ordered\_qty} = \sum_{\substack{\text{OrderLine} \in \text{lines} \\ \text{order}->\text{requisition\_id} = \text{requisition}->\text{id} \\ \text{order}->\text{state} \in [\text{PURCHASE}, \text{DONE}]}} \text{OrderLine}->\text{product\_qty}$$
$$\text{available\_qty} = \text{requisitionLine}->\text{qty} - \text{ordered\_qty}$$

#### Exceeded Commitment Warning (`OrderForm::checkBlanketOrderQtyLimit`)
If a buyer enters a line quantity exceeding remaining agreement capacity:
$$\text{product\_qty} > \text{available\_qty}$$
- The UI triggers a **warning notification** (`Notification::make()->warning()`).
- **Non-Blocking Finding**: The system does **not block** confirmation. The buyer can exceed blanket quantities.

#### Agreement Expiration Filtering (`OrderForm.php:113-130`)
When selecting an agreement on an RFQ or purchase order:
$$\text{starts\_at} \le \text{now}() \quad \land \quad (\text{ends\_at} \ge \text{now}() \lor \text{ends\_at} \text{ is null}) \quad \land \quad \text{state} = \text{RequisitionState::CONFIRMED}$$
- Expired agreements are excluded from selection in the UI.

---

### 2.5 Purchase-to-Stock Receipt Synchronization

#### Rule Definition
Synchronizes physical goods intake from warehouse operations into purchase order lines, updating received quantities and receipt statuses.

- **Status**: [VERIFIED]
- **Evidence**: `plugins/webkul/purchases/src/Services/OrderCalculator.php:176-225`, `plugins/webkul/purchases/src/Listeners/ComputePurchaseOrderListener.php:10-22`.

#### Synchronization Algorithm (`OrderCalculator::refreshQtyReceived`)
Triggered automatically when `OperationDone` or `OperationBackOrdered` fires:
1. **Method Check**:
   If `$line->qty_received_method === QtyReceivedMethod::MANUAL`, uses `$line->qty_received_manual`.
   If `$line->qty_received_method === QtyReceivedMethod::STOCK_MOVE`:
2. **Move Evaluation**:
   Iterates over all `$line->inventoryMoves` in state `MoveState::DONE`:
   $$\text{inLineUom} = \text{move}->\text{uom}->\text{computeQuantity}(\text{move}->\text{quantity}, \text{line}->\text{uom}, \text{precisionRounding} = \text{true}, \text{roundingMethod} = \text{'HALF-UP'})$$
   - **Regular Receipt**: $\text{total} \mathrel{+}= \text{inLineUom}$.
   - **Purchase Return to Vendor (`isPurchaseReturn()`)**: $\text{total} \mathrel{-}= \text{inLineUom}$.
   $$\text{qty\_received} = \text{total}$$

#### Receipt Status Lifecycle (`OrderCalculator::refreshReceiptStatus`)
- **`NO`**: No stock operations exist, or all operations are `CANCELED`.
- **`FULL`**: All linked inventory operations are settled (`DONE` or `CANCELED`).
- **`PARTIAL`**: At least one linked receipt is `DONE`, but not all operations are settled.
- **`PENDING`**: Operations exist but none have reached `DONE`.

---

## 3. Precedence & Matching Rules

### 3.1 Unit Price Fallback Precedence

```
1. Matching Vendor Pricelist (ProductSupplierInfo):
   Matching partner_id, currency_id, and min_qty <= product_qty (sorted by sort DESC)
   │ (if none found)
   ▼
2. Product Static Cost:
   Product::$cost
   │ (if null or zero)
   ▼
3. Product Base Price:
   Product::$price
   │ (if null or zero)
   ▼
4. Zero Fallback:
   0.00
   │
   ▼
Applied UOM Scale:
   price_unit = resolvedPrice * UOM::computeQuantity(1, product->uom)
```

### 3.2 Bill Matching Policy Precedence

```
1. Product Specific Purchase Method:
   Product::$purchase_method ('purchase' vs 'receive')
   │ (governs formula)
   ▼
   • 'purchase' => qty_to_invoice = product_qty - qty_invoiced (2-Way Match)
   • 'receive'  => qty_to_invoice = qty_received - qty_invoiced (3-Way Match)
```

### 3.3 Overall Order Billing Status Precedence (`OrderCalculator::refreshInvoiceStatus`)

If order is not in `OrderState::PURCHASE` or `OrderState::DONE`: $\implies \text{OrderInvoiceStatus::NO}$. Otherwise:
1. If any line has $\text{qty\_to\_invoice} \ne 0$: $\implies \text{OrderInvoiceStatus::TO\_INVOICED}$
2. If all lines have $\text{qty\_to\_invoice} == 0$ and at least one linked bill exists: $\implies \text{OrderInvoiceStatus::INVOICED}$
3. Default: $\implies \text{OrderInvoiceStatus::NO}$

---

## 4. Thresholds & Limits

The following numerical thresholds and validation barriers are enforced in the purchasing domain:

| Parameter / Field | Source Location | Exact Value / Formula | Behavior When Violated | Status |
| :--- | :--- | :--- | :--- | :--- |
| **Manager Approval Validation Floor** | `OrderSettings.php:11` | Default `5000` (`order_validation_amount`) | If `total_amount >= 5000` and user lacks `GLOBAL`/`GROUP` permission, state halts at `TO_APPROVE`. | [VERIFIED] |
| **Receipt Cancellation Barrier** | `CancelAction.php:29`<br>`PurchaseOrderController.php:226` | `qty_received > 0` | Rejects order cancellation: *"The order cannot be canceled since they have receipts that are already done."* | [VERIFIED] |
| **Vendor Bill Cancellation Barrier** | `CancelAction.php:39`<br>`PurchaseOrderController.php:232` | Any linked bill $\ne$ `CANCEL` | Rejects order cancellation: *"The order cannot be canceled. You must first cancel their related vendor bills."* | [VERIFIED] |
| **Ordered vs Received Reduction Barrier** | `ReceiptPlanner.php:67-72` | `product_qty < qty_received` | Throws `Exception`: Cannot reduce ordered quantity below already received stock. | [VERIFIED] |
| **Reordering Warehouse Consistency** | `ReceiptPlanner.php:307-319` | Warehouse mismatch with `OrderPoint` | Throws `Exception`: Receipt warehouse must match reordering rule location hierarchy. | [VERIFIED] |
| **Minimum Purchase Price** | `PurchaseOrderRequest.php` | `price_unit >= 0` | Validation error HTTP 422: Price unit must be at least 0. | [VERIFIED] |
| **Maximum Purchase Price** | `PurchaseOrderRequest.php` | `price_unit <= 99999999999` | Validation error HTTP 422: Price unit exceeds numerical bounds. | [VERIFIED] |
| **Minimum Order Quantity** | `PurchaseOrderRequest.php` | `product_qty >= 0.0001` | Validation error HTTP 422: Order quantity must be greater than zero. | [VERIFIED] |

---

## 5. Validation Constraints & Enforcement Matrix

| Business Rule | Declared | UI (Filament) | API (FormRequest) | Service Layer | Model / Event | Database Schema | Overall Enforcement |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Single-Tier Manager Approval Gate** | [VERIFIED] | [VERIFIED] | [VERIFIED] | [VERIFIED] (`OrderWorkflow:91`) | [NOT ENFORCED] | [NOT ENFORCED] | [VERIFIED] |
| **Multi-Tier Hierarchical Approval** | [DECLARED] | [NOT IMPLEMENTED] | [NOT IMPLEMENTED] | [NOT IMPLEMENTED] | [NOT IMPLEMENTED] | [NOT IMPLEMENTED] | **Structural Only / Unenforced** |
| **Receipt Cancellation Barrier** | [VERIFIED] | [VERIFIED] (`CancelAction:29`) | [VERIFIED] (`Controller:226`) | [VERIFIED] | [NOT ENFORCED] | [NOT ENFORCED] | [VERIFIED] |
| **Vendor Bill Cancellation Barrier** | [VERIFIED] | [VERIFIED] (`CancelAction:39`) | [VERIFIED] (`Controller:232`) | [VERIFIED] | [NOT ENFORCED] | [NOT ENFORCED] | [VERIFIED] |
| **Ordered vs Received Quantity Floor** | [VERIFIED] | [VERIFIED] | [VERIFIED] | [VERIFIED] (`ReceiptPlanner:67`) | [NOT ENFORCED] | [NOT ENFORCED] | [VERIFIED] |
| **3-Way Match Posting Barrier** | [DECLARED] | [NOT ENFORCED] | [NOT ENFORCED] | [NOT ENFORCED] | [NOT ENFORCED] | [NOT ENFORCED] | **Informational / Unenforced** |
| **Price Variance Tolerance Barrier** | [NOT IMPLEMENTED] | [NOT IMPLEMENTED] | [NOT IMPLEMENTED] | [NOT IMPLEMENTED] | [NOT IMPLEMENTED] | [NOT IMPLEMENTED] | **Non-Existent (0%)** |
| **Blanket Order Over-Commitment** | [DECLARED] | [WARNING ONLY] | [NOT ENFORCED] | [NOT ENFORCED] | [NOT ENFORCED] | [NOT ENFORCED] | **Non-Blocking Warning** |
| **Expired Blanket Agreement Blocking** | [VERIFIED] | [VERIFIED] (Filtered) | [NOT ENFORCED] | [NOT ENFORCED] | [NOT ENFORCED] | [NOT ENFORCED] | **UI Filter Only** |
| **Administrative Locking (`DONE`)** | [VERIFIED] | [VERIFIED] (`disabled()`) | [VERIFIED] (`toggleLock`) | [VERIFIED] (`OrderWorkflow`) | [VERIFIED] | [VERIFIED] (`state = done`) | [VERIFIED] |

---

## 6. Rounding & Precision Rules

1. **Storage Precision**:
   Monetary amounts (`price_unit`, `discount`, `price_subtotal`, `price_total`, `total_amount`, `untaxed_amount`, `total_cc_amount`) and quantities (`product_qty`, `qty_received`, `qty_invoiced`, `qty_to_invoice`) are persisted as `DECIMAL(15, 4)`.
2. **Intermediate Calculation Rounding**:
   - Line subtotal, tax amount, and total: Rounded to **4 decimal places** (`round(..., 4)`) in `OrderCalculator::applyLineTotals()` and `OrderForm::calculateLineTotals()`.
   - Unit price resolution: Rounded to **2 decimal places** (`round($vendorPrice, 2)`).
   - Converted UOM quantities: Evaluated with precision rounding via `UOM::computeQuantity(..., roundingMethod: 'HALF-UP')`.
3. **Multi-Currency Rounding**:
   Company currency grand total (`total_cc_amount`) is derived using `round($record->total_amount / $record->currency_rate, 4)`.

---

## 7. Cross-Domain Rule Dependencies

```
┌────────────────────────────────────────────────────────────────────────────────┐
│                         Purchasing Cross-Domain Matrix                         │
└────────────────────────────────────────────────────────────────────────────────┘
                                  │
         ┌────────────────────────┼────────────────────────┐
         ▼                        ▼                        ▼
┌──────────────────┐    ┌──────────────────┐    ┌──────────────────┐
│  products plugin │    │inventories plugin│    │ accounts plugin  │
├──────────────────┤    ├──────────────────┤    ├──────────────────┤
│ - Master Catalog │    │ - ReceiptPlanner │    │ - AccountMove    │
│ - ProductSupplier│    │   creates receipt│    │   (IN_INVOICE /  │
│   (sellers)      │    │   operations     │    │    IN_REFUND)    │
│ - Storable Flag  │    │ - OperationDone  │    │ - MoveConfirmed  │
│ - Base Cost/Price│    │   updates        │    │   updates        │
│ - purchase_method│    │   qty_received   │    │   qty_invoiced   │
│   (purchase vs   │    │ - OperationBack- │    │ - MoveReversed   │
│    receive)      │    │   Ordered split  │    │   reduces        │
│                  │    │ - Returns reduce │    │   qty_invoiced   │
│                  │    │   qty_received   │    │                  │
└──────────────────┘    └──────────────────┘    └──────────────────┘
         ▲                        ▲                        ▲
         │                        │                        │
         └────────────────────────┼────────────────────────┘
                                  │
                        ┌──────────────────┐
                        │ purchases plugin │
                        │  (OrderWorkflow) │
                        └──────────────────┘
                                  │
         ┌────────────────────────┴────────────────────────┐
         ▼                                                 ▼
┌──────────────────┐                             ┌──────────────────┐
│  invoices plugin │                             │ partners plugin  │
├──────────────────┤                             ├──────────────────┤
│ - UI Resource    │                             │ - Vendor profile │
│   presentation   │                             │ - Supplier rank  │
│   for bills      │                             │ - Bank accounts  │
└──────────────────┘                             └──────────────────┘
```

### Key Integration Mechanics
1. **`purchases` $\to$ `inventories` (Intake & Traceability)**:
   - Approving an order schedules warehouse receipts via `ReceiptPlanner::planForOrder()`.
   - `OperationDone` and `OperationBackOrdered` trigger `ComputePurchaseOrderListener`, executing `OrderCalculator::refreshQtyReceived()`.
   - Validating returns to vendor decrements `qty_received`.
2. **`purchases` $\to$ `accounts` & `invoices` (Billing)**:
   - `Biller::createBill()` instantiates an `AccountMove` (`IN_INVOICE` or `IN_REFUND`) bound via `purchases_order_account_moves`.
   - When the bill is posted (`MoveConfirmed`), `ComputePurchaseOrderFromMoveListener` executes `OrderCalculator::refreshQtyBilled()`.
   - When a debit note is posted (`MoveReversed`), `ComputePurchaseOrderFromMoveListener` deducts from `qty_invoiced`.
3. **`purchases` $\to$ `products` (Pricelists & Control)**:
   - Consumes `ProductSupplierInfo` (`sellers`) for price resolution.
   - Evaluates `Product::$purchase_method` to switch between 2-Way and 3-Way matching.

---

## 8. Edge Cases & Known Gaps

1. **Informational 3-Way Matching**:
   - Accounts users can post vendor bills exceeding received goods without receiving system validation errors or tolerance warnings.
2. **Absence of Price Variance Accounts**:
   - Price differences between PO lines and vendor bill lines are absorbed directly into the expense ledger without generating separate price variance postings.
3. **Absence of Hierarchical Multi-Tier Matrix**:
   - Approval thresholds operate on a single monetary floor (`order_validation_amount = 5000`) rather than multi-tiered escalation levels.
4. **Non-Blocking Blanket Agreement Limits**:
   - Call-off purchase orders that exceed blanket order commitments trigger UI warnings but are not blocked from confirmation.
5. **Passive Reordering Rules**:
   - While `ReceiptPlanner` enforces warehouse consistency on triggering order points, automated PO generation from replenishment rules is not driven by an active daemon.

---

## 9. Unknowns / Inferences

### [UNKNOWN]
1. **Multi-Tier Threshold Roadmap**: It is [UNKNOWN] whether multi-tier hierarchical approvals (distinct thresholds for different managerial roles) are planned for a future release or omitted intentionally.

### [INFERRED]
1. **Automatic Debit Note Creation on Over-Billing**: The design decision to automatically generate an `IN_REFUND` when `qty_to_invoice < 0` is inferred to be an automated reconciliation helper ensuring negative billing deltas are self-correcting.

---

## 10. Evidence References

| Area | Relative File Path | Key Symbols / Methods |
| :--- | :--- | :--- |
| **Order Workflow Service** | `plugins/webkul/purchases/src/Services/OrderWorkflow.php` | `OrderWorkflow::confirm()`, `approve()`, `canApprove()`, `cancel()`, `lock()`, `unlock()` |
| **Order Calculator Service** | `plugins/webkul/purchases/src/Services/OrderCalculator.php` | `recompute()`, `refreshReceiptStatus()`, `refreshInvoiceStatus()`, `refreshQtyReceived()`, `refreshQtyBilled()` |
| **Receipt Planner Service** | `plugins/webkul/purchases/src/Services/ReceiptPlanner.php` | `planForOrder()`, `syncFromLines()`, `cancelOperations()`, `assertOrderedCoversReceived()`, `assertReorderingRuleMatchesWarehouse()` |
| **Vendor Billing Service** | `plugins/webkul/purchases/src/Services/Biller.php` | `createBill()`, `createBillLine()` |
| **Quotation / PO UI Form** | `plugins/webkul/purchases/src/Filament/Admin/Clusters/Orders/Resources/OrderResource/Schemas/OrderForm.php` | `calculateUnitPrice()`, `calculateLineTotals()`, `checkBlanketOrderQtyLimit()` |
| **Filament Cancel Action** | `plugins/webkul/purchases/src/Filament/Admin/Clusters/Orders/Resources/OrderResource/Actions/CancelAction.php` | `CancelAction::setUp()`, receipt and bill guardrail checks |
| **API Purchase Order Controller** | `plugins/webkul/purchases/src/Http/Controllers/API/V1/PurchaseOrderController.php` | `confirm()`, `cancel()`, `toggleLock()`, `confirmReceiptDate()` |
| **Receipt Done Listener** | `plugins/webkul/purchases/src/Listeners/ComputePurchaseOrderListener.php` | `ComputePurchaseOrderListener::handle()` |
| **Bill Confirmed Listener** | `plugins/webkul/purchases/src/Listeners/ComputePurchaseOrderFromMoveListener.php` | `ComputePurchaseOrderFromMoveListener::handle()` |
| **Blanket Agreement Line Model** | `plugins/webkul/purchases/src/Models/RequisitionLine.php` | `RequisitionLine::getOrderedQtyAttribute()` |
| **Order Settings Schema** | `plugins/webkul/purchases/src/Settings/OrderSettings.php`<br>`plugins/webkul/purchases/database/settings/2025_01_11_094022_create_purchases_order_settings.php` | `enable_order_approval`, `order_validation_amount = 5000`, `enable_lock_confirmed_orders` |

---

## 11. Mandatory Final Answer

> **Question:**
> *"Are the multi-tier approval thresholds actual configured monetary amounts, or only a structural capability with no verified default values?"*

### Explicit Answer:
- **Structural capability: [VERIFIED]**
- **Default monetary threshold: [UNKNOWN]** (for multi-tier hierarchical levels)

### Detailed Clarification:
1. **Single-Tier Approval Gate (Verified)**:
   The codebase implements a **single-tier manager approval gate** controlled by `OrderSettings::$order_validation_amount`. The default configured monetary amount in settings migration (`2025_01_11_094022_create_purchases_order_settings.php:10`) is **`5000`** (disabled by default via `enable_order_approval = false`). When total order value reaches or exceeds this floor, buyers lacking `GLOBAL` or `GROUP` resource permissions have their orders halted in state `OrderState::TO_APPROVE`.
2. **Multi-Tier Hierarchical Approval (Structural Only / No Multi-Tier Config)**:
   There is **no multi-tier approval matrix or multi-level monetary threshold table** in the codebase. The system possesses the structural capability to hold orders in `to_approve` state until sign-off, but multi-tier monetary amounts (e.g. Tier 1 / Tier 2 / Tier 3 limits) do **not exist**. Therefore, multi-tier monetary thresholds are **[UNKNOWN]** / non-existent.
