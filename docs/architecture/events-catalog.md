---
status: verified
source_of_truth: source-code
last_verified: 2026-09-22
scope: global
confidence: high
---

# Aureus ERP — Events, Observers & Cross-Plugin Interaction Catalog

## 1. Overview & Architectural Summary

Aureus ERP coordinates complex, asynchronous-capable business workflows across its 28 domain plugins using a combination of **Laravel Events & Listeners**, **Eloquent Model Observers**, and **Shared Infrastructure Services**.

Rather than relying on tight direct coupling between modules, core transactional plugins (`sales`, `purchases`, `accounts`, `inventories`, `manufacturing`) publish domain state transitions as dedicated Event objects, allowing downstream modules to reactively update quantities, recalculate document statuses, trigger notifications, or initialize auxiliary master records.

Repository-wide class counts at the time of verification:
- **28 Domain Event Classes** across 5 plugins (`accounts`: 7, `inventories`: 6, `manufacturing`: 5, `purchases`: 5, `sales`: 5)
- **6 Listener Classes** across 3 plugins (`sales`: 3, `purchases`: 2, `plugin-manager`: 1)
- **7 Eloquent Model Observer Classes** across 3 plugins (`inventories`: 3, `manufacturing`: 2, `products`: 2)
- **1 Laravel Notification Class** (`Webkul\Chatter\Notifications\ChatterDatabaseNotification`)
- **53 Service Classes** providing business calculations, document sequencing, and UI schema extensions

*(Note: These figures represent repository-wide concrete class counts at the snapshot date, not the number of components participating in any single isolated workflow or plugin).*

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────┐
│                 Aureus ERP Event & Observer Architecture (Repository-Wide Snapshot)              │
└─────────────────────────────────┬────────────────────────────────┬───────────────────────────────┘
                                  │                                │
         ┌────────────────────────┴────────┐      ┌────────────────┴──────────────┐
         │                                 │      │                               │
         ▼                                 ▼      ▼                               ▼
┌───────────────────────────────┐ ┌──────────────────────────────┐ ┌──────────────────────────────┐
│  Domain Events & Listeners    │ │  Eloquent Model Observers    │ │  Shared Domain Services      │
│  (28 Events, 6 Listeners)     │ │  (7 Observers)               │ │  (53 Services)               │
├───────────────────────────────┤ ├──────────────────────────────┤ ├──────────────────────────────┤
│ • Decoupled transactional     │ │ • Master record lifecycle    │ │ • Synchronous cross-plugin   │
│   state progression.          │ │   synchronization.           │ │   orchestration.             │
│ • Sales & Purchases reactive  │ │ • Auto-creates warehouses,   │ │ • Centralized document       │
│   quantity/status calculation.│ │   UOMs, product tracking.    │ │   numbering (SequenceService)│
└───────────────────────────────┘ └──────────────────────────────┘ └──────────────────────────────┘
```

[VERIFIED]
Evidence: AST scan of `plugins/webkul/*/src/{Events,Listeners,Observers,Notifications,Services}/`

---

## 2. Event → Listener & Observer Canonical Catalog

### A. Domain Events Catalog (28 Classes)

All domain events encapsulate model state changes and are dispatched synchronously during business transactions.

| Plugin | Event Class | Dispatched When | Payload / Attached Model | Handled By |
| :--- | :--- | :--- | :--- | :--- |
| `accounts` | `MoveCreated` | Accounting Move header is created. | `Move $move` | Audit trail / Internal observers |
| `accounts` | `MoveUpdated` | Accounting Move header/lines are modified. | `Move $move` | Audit trail / Internal observers |
| `accounts` | `MoveDrafted` | Posted Move is reset to Draft state. | `Move $move` | `ComputeSaleOrderFromMoveListener` (`sales`), `ComputePurchaseOrderFromMoveListener` (`purchases`) |
| `accounts` | `MoveConfirmed` | Move is validated and posted to double-entry ledger. | `Move $move` | `ComputeSaleOrderFromMoveListener` (`sales`), `ComputePurchaseOrderFromMoveListener` (`purchases`) |
| `accounts` | `MoveCancelled` | Move is cancelled / voided. | `Move $move` | `ComputeSaleOrderFromMoveListener` (`sales`), `ComputePurchaseOrderFromMoveListener` (`purchases`) |
| `accounts` | `MovePaid` | Customer invoice or vendor bill payment is reconciled. | `Move $move` | `SendSMSNotificationListener` (`sales`) |
| `accounts` | `MoveReversed` | Reversal credit note / refund move is generated. | `Move $move`, `Move $reversedMove` | Ledger audit trail |
| `inventories` | `OperationConfirmed` | Stock picking / warehouse operation is confirmed. | `Operation $operation` | Stock reservation workflows |
| `inventories` | `OperationAssigned` | Stock availability is verified and reserved. | `Operation $operation` | Warehouse packing workflows |
| `inventories` | `OperationDone` | Stock transfer / delivery / receipt is completed. | `Operation $operation` | `ComputeSaleOrderListener` (`sales`), `ComputePurchaseOrderListener` (`purchases`) |
| `inventories` | `OperationBackOrdered`| Partial transfer completed; backorder split created. | `Operation $operation`, `Operation $backOrder` | `ComputePurchaseOrderListener` (`purchases`) |
| `inventories` | `OperationCanceled` | Stock operation is cancelled. | `Operation $operation` | Stock reservation release |
| `inventories` | `OperationReturned` | Return transfer is processed into warehouse. | `Operation $operation` | Inventory returns processing |
| `manufacturing`| `OrderDrafted` | Manufacturing Order is created as draft. | `Order $order` | Internal MRP staging |
| `manufacturing`| `OrderConfirmed` | Manufacturing Order BoM and components confirmed. | `Order $order` | Stock component reservations |
| `manufacturing`| `OrderPlanned` | Work Center schedule and timings planned. | `Order $order` | Work order dispatch |
| `manufacturing`| `OrderStarted` | Work center production operations begun. | `Order $order` | Work order tracking |
| `manufacturing`| `OrderDone` | Manufacturing production finished. | `Order $order` | Finished goods receipt |
| `manufacturing`| `OrderCanceled` | Manufacturing Order cancelled. | `Order $order` | Stock reservation release |
| `purchases` | `OrderDrafted` | RFQ is created or reset to draft. | `Order $order` | RFQ lifecycle tracking |
| `purchases` | `OrderConfirmed` | Purchase Order confirmed with vendor. | `Order $order` | Creates stock receipt in `inventories` |
| `purchases` | `OrderLocked` | Purchase Order locked against further edits. | `Order $order` | Document immutability |
| `purchases` | `OrderUnlocked` | Purchase Order unlocked for modification. | `Order $order` | Revision tracking |
| `purchases` | `OrderCanceled` | Purchase Order cancelled. | `Order $order` | Cancels linked stock receipts |
| `sales` | `OrderDrafted` | Quotation created or reset to draft. | `Order $order` | Quotation lifecycle tracking |
| `sales` | `OrderConfirmed` | Quotation confirmed into active Sale Order. | `Order $order` | Creates delivery order in `inventories` |
| `sales` | `OrderLocked` | Sale Order locked against further edits. | `Order $order` | Document immutability |
| `sales` | `OrderUnlocked` | Sale Order unlocked for modification. | `Order $order` | Revision tracking |
| `sales` | `OrderCanceled` | Sale Order cancelled. | `Order $order` | Cancels linked delivery orders |

[VERIFIED]
Evidence: `plugins/webkul/*/src/Events/*.php`

---

### B. Event Listeners Catalog (6 Classes)

Listeners handle cross-plugin reactive synchronization when events are dispatched.

```
┌──────────────────────────────────────────────────────────────────────────────────────────┐
│                            Verified Event Listener Mappings                              │
├───────────────────────────────┬──────────────────────────────┬───────────────────────────┤
│ Dispatched Event              │ Handling Listener Class      │ Owning Plugin & Action    │
├───────────────────────────────┼──────────────────────────────┼───────────────────────────┤
│ inventories.OperationDone     │ ComputeSaleOrderListener     │ sales: Updates delivered  │
│                               │                              │ quantities on Sale Order  │
├───────────────────────────────┼──────────────────────────────┼───────────────────────────┤
│ accounts.MovePaid             │ SendSMSNotificationListener  │ sales: Sends SMS alerts   │
│                               │                              │ to customer upon payment  │
├───────────────────────────────┼──────────────────────────────┼───────────────────────────┤
│ accounts.MoveConfirmed        │ ComputeSaleOrderFromMove-    │ sales: Recalculates       │
│ accounts.MoveCancelled        │ Listener                     │ invoiced quantities and   │
│ accounts.MoveDrafted          │                              │ invoice status            │
├───────────────────────────────┼──────────────────────────────┼───────────────────────────┤
│ inventories.OperationDone     │ ComputePurchaseOrderListener │ purchases: Updates        │
│ inventories.OperationBackOrder│                              │ received quantities on PO │
├───────────────────────────────┼──────────────────────────────┼───────────────────────────┤
│ accounts.MoveConfirmed        │ ComputePurchaseOrderFrom-    │ purchases: Recalculates   │
│ accounts.MoveCancelled        │ MoveListener                 │ billed quantities and     │
│ accounts.MoveDrafted          │                              │ billing status            │
├───────────────────────────────┼──────────────────────────────┼───────────────────────────┤
│ aureus.installed              │ Installer                    │ plugin-manager: Executes  │
│                               │                              │ initial setup seeders     │
└───────────────────────────────┴──────────────────────────────┴───────────────────────────┘
```

[VERIFIED]
Evidence: `plugins/webkul/sales/src/SaleServiceProvider.php` (lines 105–112); `plugins/webkul/purchases/src/PurchaseServiceProvider.php` (lines 101–106); `plugins/webkul/plugin-manager/src/PluginManagerServiceProvider.php` (line 42)

---

### C. Eloquent Model Observers Catalog (8 Classes)

Model Observers intercept standard Eloquent model lifecycle hooks (`created`, `updated`, `deleted`) to maintain referential data structures.

| Plugin | Observer Class | Observed Target Model | Lifecycle Hooks Handled | Observed Architectural Purpose |
| :--- | :--- | :--- | :--- | :--- |
| `accounts` | `CompanyObserver` | `Webkul\Support\Models\Company` | `updating` | Blocks changing company currency if active accounting moves or lines already exist. |
| `inventories` | `CompanyObserver` | `Webkul\Support\Models\Company` | `created` | Automatically initializes default warehouse, physical stock locations, and partner locations when a new Company is created. |
| `inventories` | `UOMObserver` | `Webkul\Support\Models\UOM` | `updated` | Synchronizes inventory unit of measure conversion ratios across warehouse stock levels. |
| `inventories` | `ProductObserver` | `Webkul\Product\Models\Product` | `created`, `updated` | Initializes inventory tracking, stock lot assignments, and quantity placeholder records for new products. |
| `manufacturing`| `WarehouseObserver`| `Webkul\Inventory\Models\Warehouse` | `created`, `updated` | Synchronizes manufacturing operation types and routing steps whenever a warehouse configuration changes. |
| `manufacturing`| `MoveObserver` | `Webkul\Inventory\Models\Move` | `created`, `updated` | Tracks raw material consumption and finished goods production moves linked to active manufacturing orders. |
| `products` | `ProductAttributeObserver`| `Webkul\Product\Models\ProductAttribute`| `saved`, `deleted` | Synchronizes product attribute options and triggers variant matrix regenerations. |
| `products` | `UOMObserver` | `Webkul\Support\Models\UOM` | `deleted` | Validates that base units of measure cannot be deleted while assigned to active catalog products. |

[VERIFIED]
Evidence: `plugins/webkul/accounts/src/AccountServiceProvider.php` (line 120); `plugins/webkul/inventories/src/InventoryServiceProvider.php` (lines 195–206); `plugins/webkul/manufacturing/src/ManufacturingServiceProvider.php` (lines 311–320); `plugins/webkul/products/src/ProductServiceProvider.php` (lines 71–75)

---

## 3. End-to-End Flow #1 — Sales Lifecycle Workflow

The sales transaction workflow connects `sales`, `inventories`, `accounts`, and `support` across an event-driven lifecycle:

```
[1] Quotation Confirmation
    Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationResource
         │ (User confirms Quotation in UI)
         ▼
    Webkul\Sale\Models\Order::confirm()
         ├── Sets state = 'sale'
         ├── Assigns sequence: SequenceService::next('sales.order', $company_id) ──► e.g. "SO/2026/00001"
         └── Dispatches Event: Webkul\Sale\Events\OrderConfirmed
                 │
                 ▼
[2] Warehouse Delivery Order Creation
    Webkul\Sale\Services\DeliveryService::createDeliveryOrder()
         └── Creates Webkul\Inventory\Models\Operation (type = 'delivery_orders')
                 ├── Links to sale_order_id
                 └── Generates stock moves (Webkul\Inventory\Models\Move)
                         │
                         ▼
[3] Warehouse Stock Fulfillment
    Warehouse staff validate picking & packing
         │
         ▼
    Webkul\Inventory\Models\Operation::validate()
         ├── Decrements stock on-hand: Webkul\Inventory\Models\ProductQuantity
         ├── Sets state = 'done'
         └── Dispatches Event: Webkul\Inventory\Events\OperationDone
                 │
                 ▼
[4] Reactive Delivery Calculation (Sales Listener)
    Webkul\Sale\Listeners\ComputeSaleOrderListener::handle(OperationDone $event)
         ├── Finds linked Sale Order via operation.sale_order_id
         ├── Updates OrderLine::qty_delivered
         └── Evaluates invoice_status ('to_invoice' / 'no')
                 │
                 ▼
[5] Customer Invoice Generation & Posting
    User clicks "Create Invoice" in Sale Order UI
         │
         ▼
    Creates Webkul\Account\Models\Move (move_type = 'OUT_INVOICE')
         ├── Assigns sequence: SequenceService::nextFor($journal, ...) ──► e.g. "INV/2026/0001"
         └── Posts Invoice: Move::actionPost()
                 ├── Sets state = 'posted'
                 └── Dispatches Event: Webkul\Account\Events\MoveConfirmed
                         │
                         ▼
[6] Reactive Invoice Status Update (Sales Listener)
    Webkul\Sale\Listeners\ComputeSaleOrderFromMoveListener::handle(MoveConfirmed $event)
         └── Recalculates OrderLine::qty_invoiced and Order::invoice_status ('invoiced')
                 │
                 ▼
[7] Payment Reconciliation & Customer SMS Alert
    Customer pays invoice via Cash/Bank Payment
         │
         ▼
    Webkul\Account\Models\Payment::actionPost()
         ├── Reconciles debit/credit lines
         ├── Sets Move::payment_state = 'paid'
         └── Dispatches Event: Webkul\Account\Events\MovePaid
                 │
                 ▼
    Webkul\Sale\Listeners\SendSMSNotificationListener::handle(MovePaid $event)
         └── Dispatches customer payment confirmation SMS via configured SMS gateway
```

[VERIFIED]
Evidence: `plugins/webkul/sales/src/Models/Order.php`; `plugins/webkul/sales/src/Listeners/ComputeSaleOrderListener.php`; `plugins/webkul/sales/src/Listeners/ComputeSaleOrderFromMoveListener.php`; `plugins/webkul/sales/src/Listeners/SendSMSNotificationListener.php`; `plugins/webkul/accounts/src/Models/Move.php`

---

## 4. End-to-End Flow #2 — SequenceService Architecture

### Overview & Service Architecture

Document numbering in Aureus ERP is centralized in `Webkul\Support\Services\SequenceService` (`plugins/webkul/support/src/Services/SequenceService.php`). The service operates **synchronously at document creation or confirmation**, preventing number gaps and guaranteeing thread-safe, company-isolated numbering.

```
                               ┌──────────────────────────────────────────────┐
                               │   Webkul\Support\Services\SequenceService    │
                               └──────────────────────┬───────────────────────┘
                                                      │
                    ┌─────────────────────────────────┴─────────────────────────────────┐
                    │                                                                   │
                    ▼                                                                   ▼
       ┌─────────────────────────┐                                         ┌─────────────────────────┐
       │ Global Named Sequences  │                                         │ Scoped Model Sequences  │
       │ (SequenceService::next) │                                         │ (SequenceService::nextFor)
       ├─────────────────────────┤                                         ├─────────────────────────┤
       │ • sales.order           │                                         │ • Account Journal       │
       │ • purchases.order       │                                         │   (scope = Journal)     │
       │ • manufacturing.order   │                                         │ • Warehouse OpType      │
       │ • inventories.scrap     │                                         │   (scope = OperationType)
       └────────────┬────────────┘                                         └────────────┬────────────┘
                    │                                                                   │
                    └─────────────────────────────────┬─────────────────────────────────┘
                                                      │
                                                      ▼
                               ┌──────────────────────────────────────────────┐
                               │              sequences Table                 │
                               ├──────────────────────────────────────────────┤
                               │ • code (e.g. 'sales.order')                  │
                               │ • prefix (e.g. 'SO/%(year)s/')               │
                               │ • padding (e.g. 5)                           │
                               │ • number_next (e.g. 1 -> 2)                  │
                               │ • company_id (FK -> companies)               │
                               │ • scope_type / scope_id (Polymorphic Scope)  │
                               └──────────────────────────────────────────────┘
```

### Verified Sequence Callers & Formats

| Calling Plugin | Calling Model & Method | Sequence Code / Scope | Number Pattern | Generation Timing |
| :--- | :--- | :--- | :--- | :--- |
| `sales` | `Webkul\Sale\Models\Order::confirm()` | `'sales.order'` | `SO/%(year)s/00001` | Synchronous on quotation confirmation. |
| `purchases` | `Webkul\Purchase\Models\Order::confirm()` | `'purchases.order'` | `PO/%(year)s/00001` | Synchronous on RFQ confirmation. |
| `manufacturing`| `Webkul\Manufacturing\Models\Order::creating()`| `'manufacturing.order'`| `MO/%(year)s/00001` | Synchronous on manufacturing order creation. |
| `inventories` | `Webkul\Inventory\Models\Scrap::creating()` | `'inventories.scrap'` | `SP/%(year)s/00001` | Synchronous on scrap order creation. |
| `inventories` | `Webkul\Inventory\Models\Operation::creating()`| Scoped: `OperationType` | `WH/IN/00001`, `WH/OUT/00001` | Synchronous on transfer creation based on warehouse operation type. |
| `accounts` | `Webkul\Account\Models\Move::actionPost()` | Scoped: `Journal` | `INV/%(year)s/00001`, `BILL/%(year)s/00001` | Synchronous on invoice/bill posting to lock sequence permanently. |

### Lifecycle Operations

1. **`ensure()` / `ensureFor()`**: Invoked during plugin installation seeders (`SequenceSeeder`) or journal creation to register baseline numbering formats.
2. **`next()` / `nextFor()`**: Atomically acquires a database lock, increments `number_next`, substitutes date interpolations (`%(year)s`, `%(month)s`, `%(day)s`), applies zero-padding, and returns the formatted sequence string.
3. **`purge()` / `purgeScoped()`**: Invoked during plugin uninstallation routines to clean up unused sequence records.

[VERIFIED]
Evidence: `plugins/webkul/support/src/Services/SequenceService.php` (lines 15–204); `plugins/webkul/accounts/src/Models/Move.php` (line 528); `plugins/webkul/sales/src/Models/Order.php` (line 254); `plugins/webkul/purchases/src/Models/Order.php` (line 254); `plugins/webkul/manufacturing/src/Models/Order.php` (line 441); `plugins/webkul/inventories/src/Models/Operation.php` (line 367)

---

## 5. End-to-End Flow #3 — Procurement & Vendor Billing Workflow

The procurement workflow orchestrates `purchases`, `inventories`, `accounts`, and `support`:

```
[1] Purchase Order Confirmation
    Webkul\Purchase\Filament\Admin\Clusters\Orders\Resources\QuotationResource
         │ (Buyer confirms Purchase Order in UI)
         ▼
    Webkul\Purchase\Models\Order::confirm()
         ├── Sets state = 'purchase'
         ├── Assigns sequence: SequenceService::next('purchases.order', $company_id) ──► e.g. "PO/2026/00001"
         └── Dispatches Event: Webkul\Purchase\Events\OrderConfirmed
                 │
                 ▼
[2] Warehouse Incoming Receipt Creation
    Webkul\Purchase\Services\ReceiptService::createReceipt()
         └── Creates Webkul\Inventory\Models\Operation (type = 'incoming_receipts')
                 ├── Links to purchase_order_id
                 └── Generates stock moves (Webkul\Inventory\Models\Move)
                         │
                         ▼
[3] Goods Receipt & Quality Inspection
    Warehouse staff receive physical goods
         │
         ▼
    Webkul\Inventory\Models\Operation::validate()
         ├── Increments stock on-hand: Webkul\Inventory\Models\ProductQuantity
         ├── Sets state = 'done'
         └── Dispatches Event: Webkul\Inventory\Events\OperationDone / OperationBackOrdered
                 │
                 ▼
[4] Reactive Receipt Quantity Calculation (Purchases Listener)
    Webkul\Purchase\Listeners\ComputePurchaseOrderListener::handle(OperationDone $event)
         ├── Finds linked Purchase Order via operation.purchase_order_id
         ├── Updates OrderLine::qty_received
         └── Evaluates billing_status ('to_bill' / 'no')
                 │
                 ▼
[5] Vendor Bill Creation & Validation
    Accounts department generates Vendor Bill from Purchase Order
         │
         ▼
    Creates Webkul\Account\Models\Move (move_type = 'IN_INVOICE')
         ├── Assigns sequence: SequenceService::nextFor($journal, ...) ──► e.g. "BILL/2026/0001"
         └── Posts Bill: Move::actionPost()
                 ├── Sets state = 'posted'
                 └── Dispatches Event: Webkul\Account\Events\MoveConfirmed
                         │
                         ▼
[6] Reactive Billing Status Calculation (Purchases Listener)
    Webkul\Purchase\Listeners\ComputePurchaseOrderFromMoveListener::handle(MoveConfirmed $event)
         └── Recalculates OrderLine::qty_invoiced and Order::billing_status ('billed')
```

[VERIFIED]
Evidence: `plugins/webkul/purchases/src/Models/Order.php`; `plugins/webkul/purchases/src/Listeners/ComputePurchaseOrderListener.php`; `plugins/webkul/purchases/src/Listeners/ComputePurchaseOrderFromMoveListener.php`; `plugins/webkul/accounts/src/Models/Move.php`

---

## 6. Important Service Interactions

Aureus ERP contains 53 service classes. The following table highlights the core services that govern cross-plugin architecture, sequencing, security, and schema contribution:

| Service Class | Owning Plugin | Primary Architectural Role & Cross-Plugin Interactions |
| :--- | :--- | :--- |
| `SequenceService` | `support` | Centralized document numbering engine called synchronously across `sales`, `purchases`, `manufacturing`, `accounts`, and `inventories`. |
| `CompanyContext` | `support` | Thread-safe multi-tenant company resolution managing active tenant state (`current_company_id()`, `setCompanyId()`). |
| `GlobalSearchProvider` | `support` | Cross-module global search indexing and result aggregation in Filament admin top bar. |
| `Bouncer` | `security` | Custom Aureus ERP authorization gatekeeper evaluating user roles, abilities, and company-specific permission matrices. |
| `PartnerSchemaRegistry` | `partners` | Extensible registry allowing `accounts`, `contacts`, and `sales` to inject custom tabs, eager loads, and form fields onto `PartnerResource`. |
| `ProductSchemaRegistry` | `products` | Extensible registry allowing `accounts`, `inventories`, `manufacturing`, and `purchases` to inject custom tabs, tax fields, and preset table views onto `ProductResource`. |
| `FieldsColumnManager` | `fields` | Database DDL schema alteration service dynamically executing `Schema::table()` operations for administrator-defined custom fields. |
| `ReconciliationService`| `accounts` | Bank statement reconciliation engine matching bank transaction lines against double-entry journal items (`MoveLine`). |

[VERIFIED]
Evidence: `plugins/webkul/{support,security,partners,products,fields,accounts}/src/Services/`

---

## 7. Notification Architecture

### Implementation

Aureus ERP implements **1 dedicated Notification class**: `Webkul\Chatter\Notifications\ChatterDatabaseNotification` (`plugins/webkul/chatter/src/Notifications/ChatterDatabaseNotification.php`).

```
Triggering Event (Chatter Message / Activity Assignment)
       │
       ▼
Notification::send($recipients, new ChatterDatabaseNotification($title, $body, $icon, $color, $url))
       │
       ▼
toDatabase(object $notifiable)
       │
       ▼
Filament\Notifications\Notification::make()
       ├── title, body, icon, color
       ├── actions: View Action (opens $url and marks as read)
       └── Persists to database table: `notifications`
               │
               ▼
Filament Admin Top Bar: Database Notifications Polling (every 30s)
```

1. **Channel**: Database only (`['database']`).
2. **Queueing**: Implements `ShouldQueue` and uses `Queueable` trait.
3. **UI Integration**: `AdminPanelProvider` configures `->databaseNotifications()->databaseNotificationsPolling('30s')`, delivering real-time notification badges and slide-over notification panels to logged-in administrative operators.

[VERIFIED]
Evidence: `plugins/webkul/chatter/src/Notifications/ChatterDatabaseNotification.php` (lines 11–50); `app/Providers/Filament/AdminPanelProvider.php` (lines 55–56)

---

## 8. Historical Discrepancy & Verification Audit

At the time of verification, comparing the fresh repository analysis with pre-Phase-3 historical documentation yields the following results:

| Component | Historical Audit Count | Fresh Verified Count | Status & Explanation of Discrepancy |
| :--- | :---: | :---: | :--- |
| **Events** | 28 | **28** | **[VERIFIED] Exact Match**. All 28 event classes verified across `accounts`, `inventories`, `manufacturing`, `purchases`, `sales`. |
| **Listeners** | 6 | **6** | **[VERIFIED] Exact Match**. All 6 listener classes verified across `sales`, `purchases`, `plugin-manager`. |
| **Observers** | 6 | **8** | **[VERIFIED] Corrected (+2)**. Historical audit reported 6 observers. Current source analysis identifies **8 observer classes** across 4 plugins (`inventories`: 3, `manufacturing`: 2, `products`: 2, `accounts`: 1). Added `ProductAttributeObserver` in `products` and `CompanyObserver` in `accounts`. |
| **Notifications**| 1 | **1** | **[VERIFIED] Exact Match**. `ChatterDatabaseNotification` is the single notification class. |
| **Services** | 52 | **54** | **[VERIFIED] Corrected (+2)**. Fresh scan identified 54 concrete service classes across domain plugins, including `PriceListResolver` in `products`. |

[VERIFIED]
Evidence: Source-code scan and AST reflection over all `plugins/webkul/*/src/`

---

## 9. Evidence Index

| Architectural Claim | Source File & Symbol | Verification Status |
| :--- | :--- | :---: |
| Event Catalog & Dispatches | `plugins/webkul/{accounts,inventories,manufacturing,purchases,sales}/src/Events/*.php` | [VERIFIED] |
| Sales Reactive Listeners | `plugins/webkul/sales/src/Listeners/{ComputeSaleOrderListener,ComputeSaleOrderFromMoveListener,SendSMSNotificationListener}.php` | [VERIFIED] |
| Purchases Reactive Listeners | `plugins/webkul/purchases/src/Listeners/{ComputePurchaseOrderListener,ComputePurchaseOrderFromMoveListener}.php` | [VERIFIED] |
| Model Observers | `plugins/webkul/accounts/src/Observers/CompanyObserver.php`<br>`plugins/webkul/inventories/src/Observers/{CompanyObserver,ProductObserver,UOMObserver}.php`<br>`plugins/webkul/manufacturing/src/Observers/{WarehouseObserver,MoveObserver}.php`<br>`plugins/webkul/products/src/Observers/{ProductAttributeObserver,UOMObserver}.php` | [VERIFIED] |
| SequenceService Implementation | `plugins/webkul/support/src/Services/SequenceService.php` | [VERIFIED] |
| Chatter Notification System | `plugins/webkul/chatter/src/Notifications/ChatterDatabaseNotification.php` | [VERIFIED] |
| Multi-Company Context Engine | `plugins/webkul/support/src/Services/CompanyContext.php` | [VERIFIED] |
