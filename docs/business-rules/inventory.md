---
status: verified
source_of_truth: source-code
last_verified: 2026-09-03
scope: plugins/webkul/inventories, plugins/webkul/products
confidence: high
---

# Inventory & Warehouse Operations Business Rules

## 1. Scope

### What this document covers
This document formalizes the mathematical calculation rules, algorithms, precedence hierarchies, numerical thresholds, validation constraints, precision behaviors, and cross-domain dependencies governing warehouse operations and inventory management in Aureus ERP. The analysis is grounded in `plugins/webkul/inventories` and its foundational integration with `plugins/webkul/products`.

Specifically, this document formalizes:
1. Stock valuation and costing investigation (definitive verification of whether a monetary valuation layer exists).
2. Stock availability and forecast calculations (on-hand, reserved, free, incoming, outgoing, forecast, backdated balances, and location scoping).
3. Putaway rule precedence and storage capacity routing (specificity scoring, package type prioritization, and sub-location selection).
4. Supply chain route and rule discovery precedence (hierarchical climbing and source priority: packaging $\to$ product $\to$ category $\to$ warehouse).
5. Removal strategies for physical picking (FIFO, LIFO, Closest Location, and FEFO).
6. Negative stock policy and its actual enforcement across UI, API, Service, Model, and Database layers.
7. Tracking integrity constraints (lot/serial traceability, package indivisibility, and serial uniqueness).

### What it intentionally does not cover
- **Workflow Sequences**: Step-by-step warehouse navigation, pick/pack/ship procedural lifecycles, and UI button layouts are documented in `docs/workflows/inventory.md`.
- **Database Schema DDL**: Physical database tables, foreign keys, and indexes are documented in `docs/database/erds/operations.md`.
- **General Ledger Accounting**: Invoicing and payment rules are documented in `docs/business-rules/accounting.md`.
- **Sales and Purchasing Domain Rules**: Commercial quotation pricing and vendor procurement rules are documented in `docs/business-rules/sales.md` and `docs/business-rules/purchasing.md`.

---

## 2. Calculation Rules

### 2.1 Stock Valuation Method — Definitive Finding

#### Rule Definition & Source Investigation
A comprehensive investigation was conducted across `plugins/webkul/inventories`, `plugins/webkul/products`, and `plugins/webkul/accounts` to determine whether Aureus ERP implements an inventory valuation layer (Standard Costing, Moving Average / AVCO, Periodic/Perpetual FIFO, or LIFO).

- **Status**: **No verified inventory valuation/costing layer exists.**
- **Evidence**:
  - `inventories_product_quantities` (`ProductQuantity.php`): Schema contains quantities only (`quantity`, `reserved_quantity`, `counted_quantity`, `difference_quantity`, `inventory_diff_quantity`). It contains **0 monetary, valuation, or cost columns**.
  - `inventories_moves` (`Move.php`): Contains a single monetary column `price_unit` (`decimal(15, 4)` added in migration `2026_04_10_094203_add_price_unit_column_in_inventories_moves_table.php`). This column is populated from source purchase/sale lines and used exclusively by `MoveMerger::offsetAgainstReturn` to calculate weighted unit price when merging returns. It does **not** write to any valuation ledger or general ledger account.
  - `inventories_move_lines` (`MoveLine.php`): Contains **0 cost or monetary columns**.
  - Event Handling: Completion of an inventory transfer (`TransferWorkflow::complete()`) dispatches `OperationDone`. This event is intercepted exclusively by `ComputeSaleOrderListener` (in `sales`) and `ComputePurchaseOrderListener` (in `purchases`) to update delivered/received quantities. **No accounting listeners exist** to create journal entries (`MoveType::ENTRY`) or debit/credit inventory valuation accounts.
  - Product Master Data: `products_products.cost` is a static master data attribute. It is never recalculated dynamically upon Goods Receipt or stock settlement.
  - Database Schema: There are **no stock valuation tables** (such as `stock_valuation_layer`, `inventory_valuations`, or `product_cost_history`).

#### Summary Finding
Stock tracking in Aureus ERP is **purely quantitative**. Physical inventory movements alter quant balances at rest without computing monetary valuation, cost of goods sold (COGS), or inventory asset ledger entries.

---

### 2.2 Stock Availability & Forecast Level Calculation

#### Rule Definition
Calculates stock balances across five distinct metrics (`onHand`, `free`, `incoming`, `outgoing`, `forecast`) for standalone products, product variants, and configurable parent templates across configurable location and company scopes.

- **Status**: [VERIFIED]
- **Evidence**: `plugins/webkul/inventories/src/Models/Product.php:211-305`, `plugins/webkul/inventories/src/Support/StockLevels.php:5-41`, `plugins/webkul/inventories/src/Models/ProductQuantity.php:454-520`.

#### Inputs
- `product` (`Product`): Target storable product or variant.
- `scope` (`StockScope`): Scoping parameters (target warehouses, locations, companies, lots, packages, date window).
- `uom` (`UOM`): Product base unit of measure with `rounding` factor.

#### Mathematical Formulation & Algorithms (`Product::stockLevels`)

##### 1. Configurable Product Variant Aggregation
If a product is configurable (`is_configurable = true`), its stock levels are the sum of its concrete physical variants:
$$\text{StockLevels}_{\text{parent}} = \sum_{v \in \text{variants}} \text{StockLevels}(v).\text{rounded}(\text{uom}->\text{rounding})$$

##### 2. On Hand Quantity ($\text{onHand}$)
Represents physical inventory residing in designated internal locations:
$$\text{stored} = \sum_{\text{ProductQuantity}} \text{quantity}$$
If historical backdating is active (`scope->isBackdated()`, querying as of historical timestamp $T$):
$$\text{onHand} = \text{stored} - \text{settledIncomingMovesSince}(T) + \text{settledOutgoingMovesSince}(T)$$
Where settled moves are moves in `MoveState::DONE` with `scheduled_at > T`.
$$\text{onHand} = \text{float\_round}(\text{onHand}, \text{precisionRounding} = \text{uom}->\text{rounding})$$

##### 3. Free / Available Quantity ($\text{free}$)
Represents unreserved stock available for immediate picking:
$$\text{reserved} = \sum_{\text{ProductQuantity}} \text{reserved\_quantity}$$
$$\text{free} = \text{float\_round}(\text{onHand} - \text{reserved}, \text{precisionRounding} = \text{uom}->\text{rounding})$$
- **Reservation Subtraction**: Reservation **IS subtracted** from `onHand` to derive `free` stock.

##### 4. Incoming Quantity ($\text{incoming}$)
Represents pending movements transferring stock into the scoped warehouse from outside locations (Vendor $\to$ Internal, Inventory Loss $\to$ Internal):
$$\text{incoming} = \sum \text{product\_qty} \quad \forall \text{ Move} \in \mathcal{M}_{\text{in}}$$
Where $\mathcal{M}_{\text{in}}$ satisfies:
- `product_id = product.id`
- `state` $\in$ `[WAITING, CONFIRMED, ASSIGNED, PARTIALLY_ASSIGNED]`
- `destination_location_id` $\in \text{ScopedLocations}$
- `source_location_id` $\notin \text{ScopedLocations}$
- `scheduled_at` within `[scope->from, scope->until]`

##### 5. Outgoing Quantity ($\text{outgoing}$)
Represents pending movements transferring stock out of the scoped warehouse to external destinations (Internal $\to$ Customer, Internal $\to$ Scrap):
$$\text{outgoing} = \sum \text{product\_qty} \quad \forall \text{ Move} \in \mathcal{M}_{\text{out}}$$
Where $\mathcal{M}_{\text{out}}$ satisfies:
- `product_id = product.id`
- `state` $\in$ `[WAITING, CONFIRMED, ASSIGNED, PARTIALLY_ASSIGNED]`
- `source_location_id` $\in \text{ScopedLocations}$
- `destination_location_id` $\notin \text{ScopedLocations}$
- `scheduled_at` within `[scope->from, scope->until]`

*Note on Internal Movements*: Internal transfers between two bins within the same scoped warehouse ($\text{source} \in \text{ScopedLocations} \land \text{destination} \in \text{ScopedLocations}$) are excluded from both $\text{incoming}$ and $\text{outgoing}$ because their net warehouse delta is zero.

##### 6. Forecasted Quantity ($\text{forecast}$)
Represents projected stock balance after all pending commitments are fulfilled:
$$\text{forecast} = \text{float\_round}(\text{onHand} + \text{incoming} - \text{outgoing}, \text{precisionRounding} = \text{uom}->\text{rounding})$$
- **Reservation Interaction**: `forecast` does **NOT** subtract `reserved` directly because `reserved` quantities are already captured within pending `outgoing` moves.

---

### 2.3 Physical Stock Removal Strategy

#### Rule Definition
Determines the physical ordering of quant rows (`ProductQuantity`) when reserving or picking stock for an outgoing or internal movement.

- **Status**: [VERIFIED]
- **Evidence**: `plugins/webkul/inventories/src/Models/ProductQuantity.php:410-452`, `plugins/webkul/products/src/Enums/ProductRemoval.php:7-29`.

#### Algorithms & Ordering
The removal ordering is resolved via `ProductQuantity::removalOrdering($strategy)`:

| Strategy (`ProductRemoval`) | Database Order Clause | Operational Mechanics |
| :--- | :--- | :--- |
| **FIFO** (`fifo`) | `incoming_at ASC, id ASC` | Oldest received stock quants are reserved and picked first. |
| **LIFO** (`lifo`) | `incoming_at DESC, id DESC` | Most recently received stock quants are reserved and picked first. |
| **Closest Location** (`closest`) | `null` (No DB order) | Preserves natural retrieval order; physical proximity sorting is not implemented at query level. |
| **FEFO** (`fefo`) | *Throws Exception* | `ProductRemoval::FEFO` is declared in enum but throws `RuntimeException('Removal strategy fefo is not implemented')`. |
| **Least Packages** (`least_packages`) | *Throws Exception* | Declared in enum but throws `RuntimeException`. |

*Lot Priority Tie-Breaker*: Regardless of removal strategy, `ProductQuantity::matching()` post-sorts results so that quants with an assigned lot (`lot_id != null`) are prioritized ahead of untracked quants (`lot_id == null`):
$$\text{sortBy}(fn(\text{stock}) \implies \text{stock}->\text{lot\_id} ? 0 : 1)$$

---

### 2.4 Replenishment Threshold Parameters & Lead Times

#### Rule Definition
Defines the parameters governing minimum and maximum inventory thresholds (`OrderPoint`) and supplier lead-time date offsetting.

- **Status**: [VERIFIED]
- **Evidence**: `plugins/webkul/inventories/src/Models/OrderPoint.php:14-55`, `plugins/webkul/inventories/src/Models/Product.php:198-210`, `plugins/webkul/inventories/src/Services/ProcurementRunner.php:194-197`.

#### Order Point Parameters (`inventories_order_points`)
- `product_min_qty` (decimal): Safety stock floor. If forecast stock drops below this threshold, reordering is warranted.
- `product_max_qty` (decimal): Target inventory ceiling.
- `qty_multiple` (decimal): Procurement batch size multiplier. Order quantity must round up to the nearest multiple of this value.
- `qty_to_order_manual` (decimal): Manual quantity override.
- `trigger` (`OrderPointTrigger`): `'manual'` vs `'automatic'`.
  - **Passivity Finding**: In active source code, order points are **strictly passive data records**. There is no background scheduler, cron job, or daemon that queries `product_min_qty` and generates automated Purchase Orders or Manufacturing Orders.

#### Scheduled Date Offsetting Formula
When a stock move is generated via procurement routes, its target scheduled date and deadline are offset by rule delay:
$$\text{scheduled\_at} = \text{plannedDate} - \text{rule}->\text{delay}$$
$$\text{deadline} = \text{deadlineDate} - \text{rule}->\text{delay}$$

---

## 3. Precedence & Matching Rules

### 3.1 Putaway Rule Precedence & Location Assignment

#### Rule Definition
When incoming goods arrive at a destination or intermediate view location (`LocationType::VIEW`), the putaway engine evaluates candidate rules (`PutawayRule`) to route items into specific physical storage bins or shelves.

- **Status**: [VERIFIED]
- **Evidence**: `plugins/webkul/inventories/src/Models/Location.php:290-360,440-515`.

#### Candidate Selection & Filtering
Candidates are filtered from `this->putawayRules` (where `in_location_id = this.id`):
1. **Product Match**: `(!rule->product_id || rule->product_id == product.id)`
2. **Category Match**: `(!rule->category_id || categoryAncestry->contains(rule->category_id))`
3. **Package Type Match**: `(rule->packageTypes->isEmpty() || rule->packageTypes->contains(packageType.id))`

#### Specificity Precedence Hierarchy (`Location::applicablePutawayRules`)
Filtered rules are ranked using an array-based descending sort. The first rule that satisfies storage capacity is selected:

```
[Rank 1] Package Type Match:
         rule->packageTypes->isNotEmpty() (1 vs 0)
         └── Rules requiring specific packaging types take highest priority.

[Rank 2] Product Identity Match:
         rule->product_id != null (1 vs 0)
         └── Rules matching the exact product ID take precedence over category rules.

[Rank 3] Immediate Category Match:
         rule->category_id === categoryAncestry->first() (1 vs 0)
         └── Rules matching the product's immediate category take precedence over parent categories.

[Rank 4] Category Ancestry Match:
         rule->category_id != null (1 vs 0)
         └── Rules matching an ancestor category take precedence over general catch-all rules.

[Rank 5] Manual Sequence Tie-Breaker:
         rule->sort ASC, rule->id ASC
         └── If specificity scores are identical, user-defined sort order breaks the tie.
```

#### Sub-Location & Capacity Routing
Once the highest-ranked rule is selected:
- If `sub_location === SubLocation::LAST_USED`: The system queries `MoveLine` history (`MoveState::DONE`) for the most recent movement of that product/package into that location hierarchy and routes to that exact shelf (`$lastUsed ?? $target`).
- If `storage_category_id` is defined: The target location's internal descendants are filtered. Descendants that already stock the product/package type are evaluated first, followed by empty shelves, ensuring storage capacity (`acceptsStorageOf`) is not exceeded.

---

### 3.2 Supply Chain Route & Rule Discovery Precedence

#### Rule Definition
When an order or movement demands stock at a given destination location, `RuleResolver` determines which replenishment rule (`Rule`) applies by evaluating multi-level route assignments and climbing the location hierarchy.

- **Status**: [VERIFIED]
- **Evidence**: `plugins/webkul/inventories/src/Services/RuleResolver.php:15-84`.

#### Source Precedence Hierarchy (`RuleResolver::matchRule`)
When resolving applicable routes, candidate sources are searched in strict order:

```
1. Packaging Routes:    $packaging->routes
   │ (if null or empty)
   ▼
2. Product Routes:      $product->routes
   │ (if null or empty)
   ▼
3. Category Routes:     $product->category->routes
   │ (if null or empty)
   ▼
4. Warehouse Routes:    $warehouse->routes
```

#### Location Hierarchy Climbing (`RuleResolver::climbFrom`)
1. The resolver executes `matchRule` matching `destination_location_id = location.id` (for pull rules) or `source_location_id = location.id` (for push rules).
2. Within the matching routes, rules are ordered by:
   $$\text{Order} = [\text{route\_sort ASC}, \quad \text{sort ASC}]$$
   The first matching rule is returned.
3. **Climbing Fallback**: If no rule matches at `location.id`, the resolver walks up to the parent location:
   $$\text{location} \leftarrow \text{location}->\text{parent}$$
   The search repeats at each ancestral level until a rule is found or the root of the location tree is reached.

---

### 3.3 Stock Removal Strategy Precedence

#### Precedence Hierarchy (`ProductQuantity::resolveRemovalStrategy`)
When determining whether to pick stock via FIFO or LIFO:
1. **Category Strategy**: Checks `product->category->removal_strategy`. If populated, this strategy governs.
2. **Location Hierarchy Traversal**: If category strategy is null, traverses from the storage location upwards through its parents:
   $$\text{for } (\text{current} = \text{location}; \text{ current}; \text{ current} = \text{current}->\text{parent}) \implies \text{if (current->removal\_strategy) return strategy}$$
3. **Default Fallback**: If neither category nor any location ancestor specifies a strategy, falls back to `ProductRemoval::FIFO` (`'fifo'`).

---

### 3.4 Stock Reservation & Competing Line Release Precedence

#### Reservation Release Priority (`MoveLine::releasePriority`)
When a high-priority movement requires stock reserved by other pending moves, `MoveLine::releaseCompetingReservations` releases competing lines in the following order:
1. Moves belonging to a different `operation_id` are released before moves in the same operation (`$candidate->move->operation_id !== $this->move->operation_id ? 1 : 0`).
2. Moves with later scheduled dates are released before earlier scheduled dates (`-$scheduledAt->timestamp`).
3. Later created lines are released before earlier created lines (`-$candidate->id`).

---

## 4. Thresholds & Limits

The following numerical boundaries and validation limits are enforced in the inventory domain:

| Parameter / Field | Source Location | Exact Value / Formula | Behavior When Violated | Status |
| :--- | :--- | :--- | :--- | :--- |
| **Minimum Move Line Quantity** | `MoveCompleter.php:89` | `float_compare(line->qty, 0, rounding) < 0` | Throws `Exception('Quantity cannot be negative')`. Line quantity must be $\ge 0$. | [VERIFIED] |
| **Serial Number Stock Limit** | `Move.php:1247-1258` | $\text{Quantity} \le 1.0$ per serial | Throws `Exception`: Serial-tracked products cannot have more than 1 unit per serial number. | [VERIFIED] |
| **UOM Rounding Precision Alignment** | `MoveCompleter.php:148-156` | `float_compare(roundUom, roundDigits, 2) !== 0` | Throws `Exception`: Move line quantity cannot violate UOM fractional rounding precision. | [VERIFIED] |
| **Price Precision** | `MoveMerger.php:21` | `PRICE_PRECISION = 2` | Unit prices during move merges and offsets are rounded to 2 decimal places. | [VERIFIED] |
| **Database Quantity Storage** | Migrations | `DECIMAL(15, 4)` | Quantities support up to 4 decimal places across all stock ledgers. | [VERIFIED] |
| **Package Wholeness** | `MoveCompleter.php:258-278` | Single destination location per package | Throws `Exception`: A package container cannot be partially split across multiple locations. | [VERIFIED] |
| **Done Move Cancellation Barrier** | `MoveCanceller.php:14` | `state === MoveState::DONE` | Throws `Exception`: Completed stock moves cannot be cancelled; requires a formal return. | [VERIFIED] |

---

## 5. Validation Constraints & Enforcement Matrix

| Business Rule | Declared | UI (Filament) | API (FormRequest) | Service Layer | Model / Event | Database Schema | Overall Enforcement |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Negative Stock Prohibition** | [DECLARED] | [WARNING ONLY] (Tooltip icon) | [NOT ENFORCED] | [NOT ENFORCED] (`allowNegative: true`) | [NOT ENFORCED] | [NOT ENFORCED] (Signed decimal) | **Allowed Silently** |
| **Non-Negative Move Quantities** | [VERIFIED] | [VERIFIED] (`minValue(0)`) | [VERIFIED] (`min:0`) | [VERIFIED] (`MoveCompleter:89`) | [VERIFIED] (`MoveLine:313`) | [NOT ENFORCED] | [VERIFIED] |
| **Mandatory Lot/Serial on Tracked Products** | [VERIFIED] | [VERIFIED] | [NOT ENFORCED] | [VERIFIED] (`TransferValidator:28`) | [NOT ENFORCED] | [NOT ENFORCED] | [VERIFIED] |
| **Serial Number Quantity $\le 1$** | [VERIFIED] | [VERIFIED] | [NOT ENFORCED] | [VERIFIED] (`MoveCompleter:71`) | [VERIFIED] (`assertSerialUniqueness`) | [NOT ENFORCED] | [VERIFIED] |
| **Package Container Indivisibility** | [VERIFIED] | [VERIFIED] | [NOT ENFORCED] | [VERIFIED] (`assertPackagesStayWhole`) | [NOT ENFORCED] | [NOT ENFORCED] | [VERIFIED] |
| **Immutable Completed Moves (`DONE`)** | [VERIFIED] | [VERIFIED] (`disabled()`) | [NOT ENFORCED] | [VERIFIED] (`MoveCanceller:14`) | [NOT ENFORCED] | [NOT ENFORCED] | [VERIFIED] |
| **Double-Entry Location Requirement** | [VERIFIED] | [VERIFIED] (`required()`) | [VERIFIED] | [VERIFIED] (`MoveConfirmer`) | [VERIFIED] | [VERIFIED] (`restrictOnDelete`) | [VERIFIED] |
| **Tenant Company Isolation** | [VERIFIED] | [VERIFIED] (`owned_by_company`) | [VERIFIED] | [VERIFIED] (`CrossCompanyTransferGuard`) | [VERIFIED] (`BelongsToCompany`) | [VERIFIED] (`company_id` FK) | [VERIFIED] |
| **Automated Order-Point Reordering** | [DECLARED] | [DECLARED] (`trigger`) | [NOT ENFORCED] | [NOT IMPLEMENTED] (Empty stubs) | [NOT IMPLEMENTED] | [NOT ENFORCED] | **Unenforced / Passive** |
| **Inventory Monetary Valuation** | [NOT IMPLEMENTED] | [NOT IMPLEMENTED] | [NOT IMPLEMENTED] | [NOT IMPLEMENTED] | [NOT IMPLEMENTED] | [NOT IMPLEMENTED] | **Non-Existent** |

---

## 6. Rounding & Precision Rules

### 6.1 Unit of Measure Conversion & Rounding
Stock movements support differing transaction units of measure versus the product's base stocking unit:
- Conversion is executed via `UOM::computeQuantity($qty, $toUom, $roundingMethod)`:
  $$\text{baseQty} = \frac{\text{qty} \times \text{fromUom}->\text{factor}}{\text{toUom}->\text{factor}}$$
  $$\text{result} = \text{float\_round}(\text{baseQty}, \text{precisionRounding} = \text{toUom}->\text{rounding}, \text{roundingMethod} = \text{roundingMethod})$$
- Default rounding method across reservations and completers: `'HALF-UP'`.

### 6.2 Floating-Point Comparison
All quantity equality checks evaluate through `float_is_zero($qty, precisionRounding: $uom->rounding)` or `float_compare($q1, $q2, precisionRounding: $uom->rounding)`. Direct PHP floating-point comparisons (`==` or `===`) are forbidden on stock quantities to prevent precision leakage.

---

## 7. Cross-Domain Rule Dependencies

```
┌────────────────────────────────────────────────────────────────────────────────┐
│                        Inventory Cross-Domain Architecture                     │
└────────────────────────────────────────────────────────────────────────────────┘
                                  │
         ┌────────────────────────┼────────────────────────┐
         ▼                        ▼                        ▼
┌──────────────────┐    ┌──────────────────┐    ┌──────────────────┐
│  products plugin │    │ purchases plugin │    │   sales plugin   │
├──────────────────┤    ├──────────────────┤    ├──────────────────┤
│ - Master Catalog │    │ - Goods Receipt  │    │ - Outward Orders │
│ - Storable Flag  │    │   (ReceiptPlanner)│   │   (DeliveryPlan) │
│ - Tracking (Lot) │    │ - Compute PO     │    │ - Compute SO     │
│ - Base UOM       │    │   Listener       │    │   Listener       │
└──────────────────┘    └──────────────────┘    └──────────────────┘
         ▲                        ▲                        ▲
         │                        │                        │
         └────────────────────────┼────────────────────────┘
                                  │
                        ┌──────────────────┐
                        │inventories plugin│
                        │  (Quant Ledger)  │
                        └──────────────────┘
                                  │
         ┌────────────────────────┴────────────────────────┐
         ▼                                                 ▼
┌──────────────────┐                             ┌──────────────────┐
│manufacturing plg │                             │ accounts plugin  │
├──────────────────┤                             ├──────────────────┤
│ - Component Pick │                             │   DISCONNECTED   │
│ - FG Production  │                             │ (No GL entries,  │
│ - BOM Moves      │                             │  no stock value) │
└──────────────────┘                             └──────────────────┘
```

### Dependency Analysis
1. **`inventories` $\to$ `products` (Core Master Dependency)**:
   - `inventories` declares runtime dependency on `products` (`hasDependencies(['products'])`).
   - Gated behavior: Only products with `type = ProductType::GOODS` and `is_storable = true` generate stock moves or quant records.
2. **`sales` $\to$ `inventories` (Fulfillment)**:
   - `sales` listens to `OperationDone` via `ComputeSaleOrderListener`. When delivery validation completes, `qty_delivered` is updated on sales order lines.
3. **`purchases` $\to$ `inventories` (Intake)**:
   - `purchases` listens to `OperationDone` via `ComputePurchaseOrderListener`. When receipt validation completes, `qty_received` is updated on purchase order lines.
4. **`inventories` $\to$ `accounts` (Complete Disconnection)**:
   - **Architectural Disconnection**: Validating warehouse receipts, internal transfers, or deliveries does **not** trigger any financial ledger entries. General ledger inventory accounts (`Stock Interim`, `Stock Valuation`, `COGS`) are not updated by inventory movements. Financial cost of sales is recognized solely upon customer invoice validation.

---

## 8. Edge Cases & Known Gaps

1. **Absence of Monetary Valuation Layer**:
   - Stock quantities are tracked with zero inventory monetary value. The system does not maintain balance-sheet inventory asset value.
2. **Empty Procurement Action Stubs**:
   - `ProcurementRunner::runBuyRules()` and `ProcurementRunner::runManufactureRules()` are empty method stubs (`{}`). Automated replenishment rules calling "Buy" or "Manufacture" do not spawn upstream documents.
3. **Passive Order Points**:
   - Reordering points (`OrderPoint`) store thresholds but lack an automated monitoring daemon or console command.
4. **Negative Stock Allowed Silently**:
   - Stock quantities can drop below zero at the database and service levels. Filament UI only displays a warning icon when forecast availability is $\le 0$.
5. **FEFO Removal Strategy Exception**:
   - `ProductRemoval::FEFO` is declared in enum but throws a `RuntimeException` when evaluated, rendering expiry-based picking unusable without custom code extension.

---

## 9. Unknowns / Inferences

### [UNKNOWN]
1. **Intended Valuation Architecture**: It is [UNKNOWN] whether inventory valuation was planned as a separate future plugin (e.g. `stock-account`) or omitted by design to keep warehouse tracking purely quantity-based.

### [INFERRED]
1. **Reason for Signed Decimal on Quantities**: `inventories_product_quantities.quantity` is declared as a signed decimal to allow warehouses to continue physical dispatch operations when shipments arrive before purchase receipts are entered into the system.

---

## 10. Evidence References

| Business Rule Area | Relative File Path | Key Symbols / Methods |
| :--- | :--- | :--- |
| **Stock Levels Calculation** | `plugins/webkul/inventories/src/Models/Product.php` | `Product::stockLevels()`, `storedQuantities()`, `pendingMoveQuantity()`, `composeStockScopes()` |
| **Stock Quant Model** | `plugins/webkul/inventories/src/Models/ProductQuantity.php` | `ProductQuantity::adjustStock()`, `availableFor()`, `planReservation()`, `resolveRemovalStrategy()` |
| **Removal Strategies** | `plugins/webkul/inventories/src/Models/ProductQuantity.php`<br>`plugins/webkul/products/src/Enums/ProductRemoval.php` | `ProductQuantity::removalOrdering()`, `ProductRemoval` enum |
| **Putaway Rule Precedence** | `plugins/webkul/inventories/src/Models/Location.php` | `Location::applicablePutawayRules()`, `categoryAncestry()`, `firstAvailablePutawayLocation()` |
| **Putaway Planner** | `plugins/webkul/inventories/src/Services/PutawayPlanner.php` | `PutawayPlanner::assignLocations()`, `relocateWholePackage()` |
| **Route & Rule Discovery** | `plugins/webkul/inventories/src/Services/RuleResolver.php` | `RuleResolver::matchRule()`, `findRule()`, `findPushRule()`, `climbFrom()` |
| **Procurement Runner** | `plugins/webkul/inventories/src/Services/ProcurementRunner.php` | `ProcurementRunner::run()`, `runPullRules()`, `runBuyRules()`, `runManufactureRules()` |
| **Move Completion Engine** | `plugins/webkul/inventories/src/Services/MoveCompleter.php` | `MoveCompleter::complete()`, `completeLines()`, `settleStock()`, `assertPackagesStayWhole()` |
| **Move Reservation Engine** | `plugins/webkul/inventories/src/Services/MoveReserver.php` | `MoveReserver::reserve()`, `reserveFromLocation()`, `releaseCompetingReservations()` |
| **Transfer Validation** | `plugins/webkul/inventories/src/Services/TransferValidator.php` | `TransferValidator::assertReadyToComplete()`, `linesMissingTracking()` |
| **Order Point Model** | `plugins/webkul/inventories/src/Models/OrderPoint.php` | `OrderPoint` schema, `trigger`, `product_min_qty`, `product_max_qty` |
| **Quantities Table UI** | `plugins/webkul/inventories/src/Filament/Clusters/Operations/Resources/QuantityResource/Tables/QuantitiesTable.php` | `QuantitiesTable::configure()`, column definitions |

---

## 11. Mandatory Final Answer

> **Question:**
> *"Does this codebase implement any inventory valuation/costing layer at all, or is stock purely quantity-tracked with no verified monetary valuation layer?"*

### Explicit Answer:
**No verified inventory valuation/costing layer exists.**

Stock tracking in Aureus ERP is **purely quantity-tracked** across the double-entry quant ledger (`inventories_product_quantities` and `inventories_moves`). There is:
1. **No inventory valuation table or model** (no stock valuation layers, no cost history tables).
2. **No dynamic cost calculation method** (neither FIFO, LIFO, Standard Cost, nor Moving Average / AVCO costing is implemented for inventory valuation; the `ProductRemoval` FIFO/LIFO enum represents physical picking removal order only).
3. **No financial accounting integration upon stock movement** (completing receipts, internal transfers, or deliveries generates zero journal entries and touches no General Ledger accounts).
4. **No monetary balance on stock quants** (`ProductQuantity` tracks `quantity`, `reserved_quantity`, and `counted_quantity` with zero cost or valuation fields).
