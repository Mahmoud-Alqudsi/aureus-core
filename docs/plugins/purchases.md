---
status: verified
source_of_truth: source-code
last_verified: 2026-08-31
scope: plugins/webkul/purchases
confidence: high
---

# Plugin: Purchases (`purchases`)

## Status
[VERIFIED]
Active Optional Module. Registered explicitly in `bootstrap/providers.php:56` as `Webkul\Purchase\PurchaseServiceProvider::class`.

## Core/Optional
[VERIFIED]
**Optional Plugin**. Configured as a modular procurement, vendor management, and purchase order workflow plugin extending core architecture without calling `$package->isCore()` (`plugins/webkul/purchases/src/PurchaseServiceProvider.php:41-93`). Execution, Filament resource auto-discovery, cluster mounting, and page registration are gated by runtime installation verification via `Package::isPluginInstalled('purchases')` (`plugins/webkul/purchases/src/PurchasePlugin.php:23`).

## Enabled/Disabled
[VERIFIED]
**Conditionally Enabled (Database-Gated)**. `PurchasePlugin` registers on both the `admin` and `customer` Filament panels only when the plugin record in the database is marked `is_installed = true` (`plugins/webkul/purchases/src/PurchasePlugin.php:23-65`). When uninstalled, all Purchase navigation groups, clusters, API endpoints, and portal resources remain dormant.

## Purpose
[VERIFIED]
The `purchases` plugin delivers end-to-end procurement lifecycle management for Aureus ERP. It orchestrates vendor requisitions, blanket purchase agreements, requests for quotation (RFQs), purchase orders (POs), multi-tier manager approval thresholds, incoming warehouse receipts synchronization, vendor billing, supplier vendor pricelists, and vendor self-service portal interactions:

1. **Requisitions & Purchase Agreements (`Orders` Cluster)**:
   - Manages blanket purchase agreements and purchase templates (`Requisition`) with start/end date validity, committed target quantities, and tender states (`RequisitionState`: `draft`, `confirmed`, `closed`, `canceled`).
   - Enables creation of multiple competing or child RFQs from a single blanket order or template.

2. **Requests for Quotation (RFQs) & Purchase Orders (POs) (`Orders` Cluster)**:
   - Tracks procurement documents (`Order`) transitioning through the complete purchasing lifecycle (`OrderState`: `draft`, `sent`, `to_approve`, `purchase`, `done`, `canceled`).
   - Supports automated PDF document generation (`DocumentGenerator`) for RFQs and POs with print-and-send email dispatch (`VendorPurchaseOrderMail`) to vendors.
   - Provides multi-tier approval governance (`OrderSettings::enable_order_approval` and `order_validation_amount`) requiring manager review for high-value orders.
   - Generates document numbering sequentially via `SequenceService::next('purchases.order')` (`PO/#####`).

3. **Inventory & Warehouse Synchronization (`ReceiptPlanner`)**:
   - Integrates automatically with the `inventories` plugin upon order confirmation (`OrderState::PURCHASE`), creating incoming warehouse receipt operations (`Receipt`) and stock moves (`Move`).
   - Supports multi-step warehouse routing, dropshipping direct to customer destination addresses, and reordering rule validation (`assertReorderingRuleMatchesWarehouse`).
   - Dynamically tracks receipt status (`OrderReceiptStatus`: `no`, `pending`, `partial`, `full`) based on physical warehouse movement completion.

4. **Vendor Invoicing & Accounts Payable Integration (`Biller` & `OrderCalculator`)**:
   - Integrates with the `accounts` and `invoices` plugins to generate vendor bills (`AccountMove` of type `IN_INVOICE` or `IN_REFUND`) linked via pivot table `purchases_order_account_moves`.
   - Computes billable quantities (`qty_to_invoice`) based on product purchasing policy (`purchase_method`: `'purchase'` for ordered quantities vs `'receive'` for goods received quantities).
   - Dynamically tracks billing status (`OrderInvoiceStatus`: `no`, `to_invoiced`, `invoiced`).

5. **Vendor Management & Self-Service Portal (`VendorResource` & Customer Panel)**:
   - Extends the core `Partner` master record with vendor purchasing history, purchase orders tab, billing history, and supplier pricelists.
   - Mounts dedicated vendor quotation and purchase order review screens on the `customer` panel (`Account` cluster), with signed token URLs (`purchases.quotations.respond`) for remote vendor confirmation.

6. **Procurement Configuration & Settings (`Configurations` & `Settings` Clusters)**:
   - Exposes configuration resources for Currencies, Packagings, Product Attributes, Product Categories, UOM Categories, and Vendor Pricelists (`VendorPriceResource`).
   - Provides plugin settings (`ManageOrders` and `ManageProducts`) for configuring approval thresholds, order locking, purchase agreements, variants, units of measure, and packaging.

---

## Service Provider
[VERIFIED]
- **Class**: `Webkul\Purchase\PurchaseServiceProvider` (`plugins/webkul/purchases/src/PurchaseServiceProvider.php:35`)
- **Inheritance**: Extends `Webkul\PluginManager\PackageServiceProvider`
- **Registration & Configuration**:
  - `configureCustomPackage(Package $package)`:
    - Sets package name to `purchases` (`PurchaseServiceProvider::$name = 'purchases'`).
    - Configures view namespace `purchases` (`PurchaseServiceProvider::$viewNamespace = 'purchases'`).
    - Registers web routes (`hasRoute('web')`) and REST API routes (`hasRoute('api')`).
    - Registers translations namespace (`hasTranslations()`).
    - Registers 19 database migrations (`hasMigrations([...])`).
    - Registers settings migrations (`hasSettings([...])`): `2025_01_11_094022_create_purchases_order_settings` and `2025_01_11_094022_create_purchases_product_settings`.
    - Declares runtime plugin dependency on `invoices` (`hasDependencies(['invoices'])`).
    - Registers database seeder: `Webkul\Purchase\Database\Seeders\DatabaseSeeder::class`.
    - Configures install command: runs dependency installation, migrations, and seeders.
    - Configures uninstall command: purges chatter audit logs for `Order` and `Requisition` models via `ChatterCleanupService::purgeForModels()`, and purges sequence codes via `SequenceService::purge(['purchases.order'])`.
    - Configures plugin icon: `purchases`.
  - `packageBooted()`:
    - Registers Livewire components: `order-summary` (`OrderSummary::class`) and `list-products` (`ListProducts::class`).
    - Registers event listeners:
      - `OperationDone`, `OperationBackOrdered` → `ComputePurchaseOrderListener::class`
      - `MoveConfirmed`, `MoveCancelled`, `MoveDrafted`, `MoveReversed` → `ComputePurchaseOrderFromMoveListener::class`
    - Registers product usage tracking in `ProductUsageRegistry::register(OrderLine::class, RequisitionLine::class)`.
    - Registers dynamic macro relation on `Product::resolveRelationUsing('sellers', ...)` to query vendor supplier pricelists across template products and variants.
  - `packageRegistered()`:
    - Registers `PurchasePlugin::make()` with Filament panel builder.
    - Registers alias `purchase_order` for facade `PurchaseOrderFacade::class`.
    - Binds singleton service `purchase_order` to `Webkul\Purchase\PurchaseOrder::class`.

---

## Filament Plugin Class
[VERIFIED]
- **Class**: `Webkul\Purchase\PurchasePlugin` (`plugins/webkul/purchases/src/PurchasePlugin.php:9`)
- **Plugin Identifier**: `'purchases'` (`getId(): string`)
- **Panel Registration**: Registers on **BOTH** the **`admin`** and **`customer`** panels (`plugins/webkul/purchases/src/PurchasePlugin.php:27-65`).
- **Auto-Discovery Configuration**:
  - **Admin Panel**:
    - Resources: `plugins/webkul/purchases/src/Filament/Admin/Resources` (`Webkul\Purchase\Filament\Admin\Resources`)
    - Pages: `plugins/webkul/purchases/src/Filament/Admin/Pages` (`Webkul\Purchase\Filament\Admin\Pages`)
    - Clusters: `plugins/webkul/purchases/src/Filament/Admin/Clusters` (`Webkul\Purchase\Filament\Admin\Clusters`)
    - Widgets: `plugins/webkul/purchases/src/Filament/Admin/Widgets` (`Webkul\Purchase\Filament\Admin\Widgets`)
  - **Customer Panel**:
    - Resources: `plugins/webkul/purchases/src/Filament/Customer/Resources` (`Webkul\Purchase\Filament\Customer\Resources`)
    - Pages: `plugins/webkul/purchases/src/Filament/Customer/Pages` (`Webkul\Purchase\Filament\Customer\Pages`)
    - Clusters: `plugins/webkul/purchases/src/Filament/Customer/Clusters` (`Webkul\Purchase\Filament\Customer\Clusters`)
    - Widgets: `plugins/webkul/purchases/src/Filament/Customer/Widgets` (`Webkul\Purchase\Filament\Customer\Widgets`)

---

## Composer Dependencies
[VERIFIED]
- **Declared in `plugins/webkul/purchases/composer.json`**:
  - `webkul/purchases` declares zero package-level Composer `require` dependencies.
  - Autoloads PSR-4 namespaces:
    - `Webkul\Purchase\`: `src/`
    - `Webkul\Purchase\Database\Factories\`: `database/factories/`
    - `Webkul\Purchase\Database\Seeders\`: `database/seeders/`
  - Autoloads dev PSR-4 namespace:
    - `Webkul\Purchase\Tests\`: `tests/`

---

## Runtime Plugin Dependencies
[VERIFIED]
- **`invoices`**: Declared in `PurchaseServiceProvider::configureCustomPackage()` via `->hasDependencies(['invoices'])` (`plugins/webkul/purchases/src/PurchaseServiceProvider.php:75-77`). Through `invoices`, runtime access to `accounts` and `products` is guaranteed.
- **Conditional Inter-Plugin Linkages**:
  - **`inventories`**: When installed, incoming receipts, stock moves, multi-step warehouse transfers, and dropshipping routes are synchronized automatically.
  - **`website`**: When installed, vendor self-service portal features mount under the `Account` customer cluster.

---

## Directory Structure
[VERIFIED]
```text
plugins/webkul/purchases/
├── composer.json
├── config/
│   └── filament-shield.php
├── database/
│   ├── factories/
│   │   ├── OrderFactory.php
│   │   ├── OrderGroupFactory.php
│   │   ├── OrderLineFactory.php
│   │   ├── RequisitionFactory.php
│   │   └── RequisitionLineFactory.php
│   ├── migrations/
│   │   ├── 2025_02_11_101100_create_purchases_order_groups_table.php
│   │   ├── 2025_02_11_101101_create_purchases_requisitions_table.php
│   │   ├── 2025_02_11_101105_create_purchases_requisition_lines_table.php
│   │   ├── 2025_02_11_101110_create_purchases_orders_table.php
│   │   ├── 2025_02_11_101118_create_purchases_order_lines_table.php
│   │   ├── 2025_02_11_135617_create_purchases_order_line_taxes_table.php
│   │   ├── 2025_02_11_142937_create_purchases_order_account_moves_table.php
│   │   ├── 2025_02_11_143351_alter_accounts_account_move_lines_table.php
│   │   ├── 2025_03_17_101755_add_inventories_columns_to_purchases_orders_table_from_purchases.php
│   │   ├── 2025_03_17_101814_add_inventories_columns_to_purchases_order_lines_table_from_purchases.php
│   │   ├── 2025_03_17_111610_add_purchases_columns_to_inventories_moves_table_from_purchases.php
│   │   ├── 2025_03_17_115707_create_purchases_order_operations_table_from_purchases.php
│   │   ├── 2026_03_11_103115_alter_purchases_order_lines_table.php
│   │   ├── 2026_03_13_181105_alter_purchases_orders_table.php
│   │   ├── 2026_04_15_044345_add_destination_address_id_in_purchases_orders_table.php
│   │   ├── 2026_04_22_115707_create_purchases_order_line_moves_table_from_purchases.php
│   │   ├── 2026_04_23_043411_add_procurement_group_id_column_in_purchases_orders_table_from_purchases.php
│   │   ├── 2026_04_23_043412_add_procurement_group_id_column_in_purchases_order_lines_table_from_purchases.php
│   │   └── 2026_08_03_130000_seed_purchases_sequences.php
│   ├── seeders/
│   │   ├── DatabaseSeeder.php
│   │   └── SequenceSeeder.php
│   └── settings/
│       ├── 2025_01_11_094022_create_purchases_order_settings.php
│       └── 2025_01_11_094022_create_purchases_product_settings.php
├── resources/
│   ├── lang/
│   │   ├── ar/
│   │   ├── en/
│   │   ├── es/
│   │   ├── fr/
│   │   └── pt_BR/
│   └── views/
│       ├── emails/
│       │   └── index.blade.php
│       ├── filament/
│       │   ├── admin/
│       │   │   └── clusters/
│       │   │       └── orders/
│       │   │           └── orders/
│       │   │               └── actions/
│       │   │                   ├── print-purchase-order.blade.php
│       │   │                   └── print-quotation.blade.php
│       │   └── customer/
│       │       └── clusters/
│       │           └── account/
│       │               └── resources/
│       │                   └── order/
│       │                       └── pages/
│       │                           └── view-order.blade.php
│       └── livewire/
│           ├── customer/
│           │   └── list-products.blade.php
│           ├── order-summary.blade.php
│           └── respond-quotation.blade.php
├── routes/
│   ├── api.php
│   └── web.php
├── src/
│   ├── Enums/
│   │   ├── OrderInvoiceStatus.php
│   │   ├── OrderReceiptStatus.php
│   │   ├── OrderState.php
│   │   ├── QtyReceivedMethod.php
│   │   ├── RequisitionState.php
│   │   └── RequisitionType.php
│   ├── Events/
│   │   ├── OrderCanceled.php
│   │   ├── OrderConfirmed.php
│   │   ├── OrderDrafted.php
│   │   ├── OrderLocked.php
│   │   └── OrderUnlocked.php
│   ├── Facades/
│   │   └── PurchaseOrder.php
│   ├── Filament/
│   │   ├── Admin/
│   │   │   ├── Clusters/
│   │   │   │   ├── Configurations/
│   │   │   │   │   └── Resources/
│   │   │   │   │       ├── CurrencyResource/
│   │   │   │   │       ├── CurrencyResource.php
│   │   │   │   │       ├── PackagingResource/
│   │   │   │   │       ├── PackagingResource.php
│   │   │   │   │       ├── ProductAttributeResource/
│   │   │   │   │       ├── ProductAttributeResource.php
│   │   │   │   │       ├── ProductCategoryResource/
│   │   │   │   │       ├── ProductCategoryResource.php
│   │   │   │   │       ├── UOMCategoryResource/
│   │   │   │   │       ├── UOMCategoryResource.php
│   │   │   │   │       ├── VendorPriceResource/
│   │   │   │   │       └── VendorPriceResource.php
│   │   │   │   ├── Configurations.php
│   │   │   │   ├── Orders/
│   │   │   │   │   └── Resources/
│   │   │   │   │       ├── OrderResource/
│   │   │   │   │       │   ├── Actions/
│   │   │   │   │       │   │   ├── CancelAction.php
│   │   │   │   │       │   │   ├── ConfirmAction.php
│   │   │   │   │       │   │   ├── ConfirmReceiptDateAction.php
│   │   │   │   │       │   │   ├── CreateBillAction.php
│   │   │   │   │       │   │   ├── DraftAction.php
│   │   │   │   │       │   │   ├── LockAction.php
│   │   │   │   │       │   │   ├── PrintPOAction.php
│   │   │   │   │       │   │   ├── PrintRFQAction.php
│   │   │   │   │       │   │   ├── SendEmailAction.php
│   │   │   │   │       │   │   ├── SendPOEmailAction.php
│   │   │   │   │       │   │   └── UnlockAction.php
│   │   │   │   │       │   ├── Pages/
│   │   │   │   │       │   ├── Schemas/
│   │   │   │   │       │   └── Tables/
│   │   │   │   │       ├── OrderResource.php
│   │   │   │   │       ├── PurchaseAgreementResource/
│   │   │   │   │       ├── PurchaseAgreementResource.php
│   │   │   │   │       ├── PurchaseOrderBillResource/
│   │   │   │   │       ├── PurchaseOrderBillResource.php
│   │   │   │   │       ├── PurchaseOrderReceiptResource/
│   │   │   │   │       ├── PurchaseOrderReceiptResource.php
│   │   │   │   │       ├── PurchaseOrderResource/
│   │   │   │   │       ├── PurchaseOrderResource.php
│   │   │   │   │       ├── QuotationBillResource/
│   │   │   │   │       ├── QuotationBillResource.php
│   │   │   │   │       ├── QuotationReceiptResource/
│   │   │   │   │       ├── QuotationReceiptResource.php
│   │   │   │   │       ├── QuotationResource/
│   │   │   │   │       ├── QuotationResource.php
│   │   │   │   │       ├── VendorResource/
│   │   │   │   │       └── VendorResource.php
│   │   │   │   ├── Orders.php
│   │   │   │   ├── PluginSettings.php
│   │   │   │   ├── Products/
│   │   │   │   │   └── Resources/
│   │   │   │   │       ├── ProductResource/
│   │   │   │   │       └── ProductResource.php
│   │   │   │   ├── Products.php
│   │   │   │   └── Settings/
│   │   │   │       └── Pages/
│   │   │   │           ├── ManageOrders.php
│   │   │   │           └── ManageProducts.php
│   │   │   └── Pages/
│   │   │       └── Settings/
│   │   │           ├── ManageOrders.php
│   │   │           └── ManageProducts.php
│   │   └── Customer/
│   │       └── Clusters/
│   │           └── Account/
│   │               └── Resources/
│   │                   ├── OrderResource/
│   │                   ├── OrderResource.php
│   │                   ├── PurchaseOrderResource/
│   │                   │   └── Pages/
│   │                   │       ├── ListPurchaseOrders.php
│   │                   │       └── ViewPurchaseOrder.php
│   │                   ├── PurchaseOrderResource.php
│   │                   ├── QuotationResource/
│   │                   │   └── Pages/
│   │                   │       ├── ListQuotations.php
│   │                   │       └── ViewQuotation.php
│   │                   └── QuotationResource.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── API/
│   │   │       └── V1/
│   │   │           ├── Controller.php
│   │   │           ├── PurchaseAgreementController.php
│   │   │           ├── PurchaseAgreementLineController.php
│   │   │           ├── PurchaseOrderBillController.php
│   │   │           ├── PurchaseOrderController.php
│   │   │           ├── PurchaseOrderLineController.php
│   │   │           ├── PurchaseOrderReceiptController.php
│   │   │           └── VendorPriceListController.php
│   │   ├── Requests/
│   │   │   ├── PurchaseAgreementLineRequest.php
│   │   │   ├── PurchaseAgreementRequest.php
│   │   │   ├── PurchaseOrderLineRequest.php
│   │   │   ├── PurchaseOrderRequest.php
│   │   │   └── VendorPriceListRequest.php
│   │   └── Resources/
│   │       └── V1/
│   ├── Listeners/
│   │   ├── ComputePurchaseOrderFromMoveListener.php
│   │   └── ComputePurchaseOrderListener.php
│   ├── Livewire/
│   │   ├── Customer/
│   │   │   └── ListProducts.php
│   │   ├── OrderSummary.php
│   │   └── RespondQuotation.php
│   ├── Mail/
│   │   └── VendorPurchaseOrderMail.php
│   ├── Models/
│   │   ├── AccountMove.php
│   │   ├── AccountMoveLine.php
│   │   ├── Attribute.php
│   │   ├── Bill.php
│   │   ├── Category.php
│   │   ├── Currency.php
│   │   ├── CustomerPurchaseOrder.php
│   │   ├── Order.php
│   │   ├── OrderGroup.php
│   │   ├── OrderLine.php
│   │   ├── Packaging.php
│   │   ├── Partner.php
│   │   ├── Product.php
│   │   ├── ProductSupplier.php
│   │   ├── PurchaseOrder.php
│   │   ├── Quotation.php
│   │   ├── Requisition.php
│   │   ├── RequisitionLine.php
│   │   └── UOMCategory.php
│   ├── Policies/
│   │   ├── AttributePolicy.php
│   │   ├── CategoryPolicy.php
│   │   ├── CurrencyPolicy.php
│   │   ├── CustomerPurchaseOrderPolicy.php
│   │   ├── PackagingPolicy.php
│   │   ├── PartnerPolicy.php
│   │   ├── ProductPolicy.php
│   │   ├── ProductSupplierPolicy.php
│   │   ├── PurchaseOrderPolicy.php
│   │   ├── QuotationPolicy.php
│   │   ├── RequisitionPolicy.php
│   │   └── UOMCategoryPolicy.php
│   ├── PurchaseOrder.php
│   ├── PurchasePlugin.php
│   ├── PurchaseServiceProvider.php
│   ├── Services/
│   │   ├── Biller.php
│   │   ├── DocumentGenerator.php
│   │   ├── OrderCalculator.php
│   │   ├── OrderWorkflow.php
│   │   └── ReceiptPlanner.php
│   └── Settings/
│       ├── OrderSettings.php
│       └── ProductSettings.php
└── tests/
    ├── Feature/
    │   ├── API/
    │   │   └── V1/
    │   │       ├── PurchaseAgreementLineTest.php
    │   │       ├── PurchaseAgreementTest.php
    │   │       ├── PurchaseOrderBillTest.php
    │   │       ├── PurchaseOrderLineTest.php
    │   │       ├── PurchaseOrderReceiptTest.php
    │   │       ├── PurchaseOrderTest.php
    │   │       └── VendorPriceListTest.php
    │   ├── Filament/
    │   │   ├── PurchaseOrderResourceTest.php
    │   │   └── ResourceGlobalSearchSmokeTest.php
    │   └── Workflows/
    │       ├── CompanyIsolationTest.php
    │       ├── CompanyScopingInvariantsTest.php
    │       ├── OrderBillingTest.php
    │       ├── PurchaseOrderTest.php
    │       └── TwoStepPurchaseOrderTest.php
    └── Helpers/
        └── PurchaseHelper.php
```

---

## Models
[VERIFIED]

The `purchases` plugin maintains 5 primary physical domain models and 14 domain extension/proxy models:

| Model Class | Physical Table | Company Scoped | Primary Role & Extends | Traits / Interfaces |
|---|---|---|---|---|
| `Webkul\Purchase\Models\Order` | `purchases_orders` | Yes (`company_id`) | Purchase Order / RFQ header | `BelongsToCompany`, `ChecksCompanyConsistency`, `HasChatter`, `HasCustomFields`, `HasFactory`, `HasLogActivity`, `HasOwnershipScope` |
| `Webkul\Purchase\Models\OrderLine` | `purchases_order_lines` | Yes (`company_id`) | Purchased item line item | `BelongsToCompany`, `ChecksCompanyConsistency`, `HasFactory`, `SortableTrait`, implements `Sortable` |
| `Webkul\Purchase\Models\Requisition` | `purchases_requisitions` | Yes (`company_id`) | Blanket agreement & tender header | `BelongsToCompany`, `HasChatter`, `HasCustomFields`, `HasFactory`, `HasLogActivity`, `SoftDeletes` |
| `Webkul\Purchase\Models\RequisitionLine` | `purchases_requisition_lines` | Yes (`company_id`) | Requisition product line requirement | `BelongsToCompany`, `ChecksCompanyConsistency`, `HasFactory` |
| `Webkul\Purchase\Models\OrderGroup` | `purchases_order_groups` | No (Global Group) | Group container for RFQ tenders | `HasFactory` |
| `Webkul\Purchase\Models\Quotation` | `purchases_orders` | Yes (Via Order) | Quotation proxy model | Extends `Webkul\Purchase\Models\Order` |
| `Webkul\Purchase\Models\PurchaseOrder` | `purchases_orders` | Yes (Via Order) | Confirmed Purchase Order proxy | Extends `Webkul\Purchase\Models\Order` |
| `Webkul\Purchase\Models\CustomerPurchaseOrder` | `purchases_orders` | Yes (Via Order) | Customer/Vendor portal PO proxy | Extends `Webkul\Purchase\Models\Order` |
| `Webkul\Purchase\Models\Bill` | `accounts_account_moves` | Yes (Via Move) | Vendor Bill extension | Extends `Webkul\Invoice\Models\Bill` |
| `Webkul\Purchase\Models\AccountMove` | `accounts_account_moves` | Yes (Via Move) | Account Move extension with PO link | Extends `Webkul\Account\Models\Move` |
| `Webkul\Purchase\Models\AccountMoveLine` | `accounts_account_move_lines` | Yes (Via MoveLine) | Bill line linking to `purchases_order_lines` | Extends `Webkul\Account\Models\MoveLine` |
| `Webkul\Purchase\Models\Partner` | `partners_partners` | Optional (Via Partner) | Partner proxy with purchase orders | Extends `Webkul\Account\Models\Partner` |
| `Webkul\Purchase\Models\Product` | `products_products` | Optional (Via Product) | Product proxy with vendor suppliers | Extends `Webkul\Invoice\Models\Product` |
| `Webkul\Purchase\Models\ProductSupplier` | `products_product_suppliers` | Optional (Via Base) | Supplier pricelist proxy | Extends `Webkul\Product\Models\ProductSupplier` |
| `Webkul\Purchase\Models\Attribute` | `products_attributes` | No (Global Master) | Attribute proxy model | Extends `Webkul\Product\Models\Attribute` |
| `Webkul\Purchase\Models\Category` | `products_categories` | No (Global Master) | Product category proxy model | Extends `Webkul\Product\Models\Category` |
| `Webkul\Purchase\Models\Currency` | `currencies` | No (Global Master) | Currency proxy model | Extends `Webkul\Support\Models\Currency` |
| `Webkul\Purchase\Models\Packaging` | `products_packagings` | Optional (Via Base) | Product packaging proxy model | Extends `Webkul\Product\Models\Packaging` |
| `Webkul\Purchase\Models\UOMCategory` | `unit_of_measure_categories` | No (Global Master) | UOM category proxy model | Extends `Webkul\Support\Models\UOMCategory` |

### Detailed Model Relationships

#### `Order` (`purchases_orders`)
- `requisition()`: `BelongsTo` → `Requisition::class` (`requisition_id`).
- `group()`: `BelongsTo` → `OrderGroup::class` (`purchases_group_id`).
- `partner()`: `BelongsTo` → `Partner::class` (`partner_id`).
- `destinationAddress()`: `BelongsTo` → `Partner::class` (`destination_address_id`).
- `fiscalPosition()`: `BelongsTo` → `FiscalPosition::class` (`fiscal_position_id`).
- `paymentTerm()`: `BelongsTo` → `PaymentTerm::class` (`payment_term_id`).
- `incoterm()`: `BelongsTo` → `Incoterm::class` (`incoterm_id`).
- `currency()`: `BelongsTo` → `Currency::class` (`currency_id`).
- `user()`: `BelongsTo` → `User::class` (`user_id` / Buyer).
- `creator()`: `BelongsTo` → `User::class` (`creator_id`).
- `company()`: `BelongsTo` → `Company::class` (`company_id`).
- `lines()`: `HasMany` → `OrderLine::class` (`order_id`).
- `accountMoves()`: `BelongsToMany` → `AccountMove::class` via pivot `purchases_order_account_moves` (`order_id`, `move_id`).
- `bills()`: `BelongsToMany` → `Bill::class` via pivot `purchases_order_account_moves` (`order_id`, `move_id`).
- `operationType()`: `BelongsTo` → `OperationType::class` (`operation_type_id`).
- `operations()`: `BelongsToMany` → `Receipt::class` via pivot `purchases_order_operations` (`purchase_order_id`, `inventory_operation_id`).
- `procurementGroup()`: `BelongsTo` → `ProcurementGroup::class` (`procurement_group_id`).

#### `OrderLine` (`purchases_order_lines`)
- `order()`: `BelongsTo` → `Order::class` (`order_id`).
- `partner()`: `BelongsTo` → `Partner::class` (`partner_id`).
- `product()`: `BelongsTo` → `Product::class` (`product_id`).
- `productPackaging()`: `BelongsTo` → `Packaging::class` (`product_packaging_id`).
- `uom()`: `BelongsTo` → `UOM::class` (`uom_id`).
- `taxes()`: `BelongsToMany` → `Tax::class` via pivot `purchases_order_line_taxes` (`order_line_id`, `tax_id`).
- `currency()`: `BelongsTo` → `Currency::class` (`currency_id`).
- `user()`: `BelongsTo` → `User::class` (`user_id`).
- `company()`: `BelongsTo` → `Company::class` (`company_id`).
- `creator()`: `BelongsTo` → `User::class` (`creator_id`).
- `accountMoveLines()`: `HasMany` → `AccountMoveLine::class` (`purchase_order_line_id`).
- `inventoryMoves()`: `HasMany` → `InventoryMove::class` (`purchase_order_line_id`).
- `moveDestinations()`: `BelongsToMany` → `InventoryMove::class` via pivot `purchases_order_line_moves` (`purchase_order_line_id`, `inventory_move_id`).
- `finalLocation()`: `BelongsTo` → `Location::class` (`final_location_id`).
- `orderPoint()`: `BelongsTo` → `OrderPoint::class` (`order_point_id`).
- `procurementGroup()`: `BelongsTo` → `ProcurementGroup::class` (`procurement_group_id`).

#### `Requisition` (`purchases_requisitions`)
- `partner()`: `BelongsTo` → `Partner::class` (`partner_id`).
- `currency()`: `BelongsTo` → `Currency::class` (`currency_id`).
- `user()`: `BelongsTo` → `User::class` (`user_id`).
- `company()`: `BelongsTo` → `Company::class` (`company_id`).
- `creator()`: `BelongsTo` → `User::class` (`creator_id`).
- `lines()`: `HasMany` → `RequisitionLine::class` (`requisition_id`).
- `orders()`: `HasMany` → `Order::class` (`requisition_id`).

---

## Database
[VERIFIED]

### Physical Database Tables Owned
1. **`purchases_order_groups`**: Group container for blanket order RFQ tenders.
2. **`purchases_requisitions`**: Blanket agreements and purchase templates with validity windows (`starts_at`, `ends_at`), soft deletes (`deleted_at`), and company scoping.
3. **`purchases_requisition_lines`**: Agreement target line quantities and agreed unit prices.
4. **`purchases_orders`**: Primary purchase order / quotation master document header with state machine, multi-currency rates, approval timestamps, and company isolation.
5. **`purchases_order_lines`**: Line items with tax calculation values, discounts, unit prices, downpayment flags, and inventory tracking.
6. **`purchases_order_line_taxes`**: Pivot linking `purchases_order_lines.id` to `accounts_taxes.id`.
7. **`purchases_order_account_moves`**: Pivot linking `purchases_orders.id` to `accounts_account_moves.id` (vendor bills).
8. **`purchases_order_operations`**: Pivot linking `purchases_orders.id` to `inventories_operations.id` (warehouse receipts).
9. **`purchases_order_line_moves`**: Pivot linking `purchases_order_lines.id` to `inventories_moves.id` (inventory stock moves).

### External Table Alterations
- **`accounts_account_move_lines`**: Adds nullable `purchase_order_line_id` foreign key referencing `purchases_order_lines.id` with `nullOnDelete()`.
- **`inventories_moves`**: Adds nullable `purchase_order_line_id` foreign key referencing `purchases_order_lines.id` with `restrictOnDelete()`.

### Migrations
- `2025_02_11_101100_create_purchases_order_groups_table.php`
- `2025_02_11_101101_create_purchases_requisitions_table.php`
- `2025_02_11_101105_create_purchases_requisition_lines_table.php`
- `2025_02_11_101110_create_purchases_orders_table.php`
- `2025_02_11_101118_create_purchases_order_lines_table.php`
- `2025_02_11_135617_create_purchases_order_line_taxes_table.php`
- `2025_02_11_142937_create_purchases_order_account_moves_table.php`
- `2025_02_11_143351_alter_accounts_account_move_lines_table.php`
- `2025_03_17_101755_add_inventories_columns_to_purchases_orders_table_from_purchases.php`
- `2025_03_17_101814_add_inventories_columns_to_purchases_order_lines_table_from_purchases.php`
- `2025_03_17_111610_add_purchases_columns_to_inventories_moves_table_from_purchases.php`
- `2025_03_17_115707_create_purchases_order_operations_table_from_purchases.php`
- `2026_03_11_103115_alter_purchases_order_lines_table.php`
- `2026_03_13_181105_alter_purchases_orders_table.php`
- `2026_04_15_044345_add_destination_address_id_in_purchases_orders_table.php`
- `2026_04_22_115707_create_purchases_order_line_moves_table_from_purchases.php`
- `2026_04_23_043411_add_procurement_group_id_column_in_purchases_orders_table_from_purchases.php`
- `2026_04_23_043412_add_procurement_group_id_column_in_purchases_order_lines_table_from_purchases.php`
- `2026_08_03_130000_seed_purchases_sequences.php`

### Seeders
- `DatabaseSeeder`: Calls `SequenceSeeder::class`.
- `SequenceSeeder`: Ensures default sequence record exists for code `'purchases.order'` with prefix `'PO/'`.

### Settings Migrations
- `2025_01_11_094022_create_purchases_order_settings.php`: Group `'purchases_order'`, adds `enable_order_approval` (false), `order_validation_amount` (5000), `enable_lock_confirmed_orders` (true), `enable_purchase_agreements` (false).
- `2025_01_11_094022_create_purchases_product_settings.php`: Group `'purchases_product'`, adds `enable_variants` (false), `enable_uom` (false), `enable_packagings` (false).

---

## Filament Resources, Pages, Widgets & Clusters
[VERIFIED]

### Admin Panel

#### 1. `Orders` Cluster (`Webkul\Purchase\Filament\Admin\Clusters\Orders`)
- **`QuotationResource`**:
  - Model: `Quotation` (`purchases_orders`)
  - Navigation: Sort 1, Icon `heroicon-o-document-text`.
  - Sub-navigation pages: `ViewQuotation`, `EditQuotation`, `ManageBills`, `ManageReceipts`.
  - Actions: `DraftAction`, `SendEmailAction`, `PrintRFQAction`, `ConfirmAction`, `CancelAction`, `CreateBillAction`.
- **`PurchaseOrderResource`**:
  - Model: `PurchaseOrder` (`purchases_orders`)
  - Navigation: Sort 2, Icon `heroicon-o-document-check`.
  - Sub-navigation pages: `ViewPurchaseOrder`, `EditPurchaseOrder`, `ManageBills`, `ManageReceipts`.
  - Actions: `SendPOEmailAction`, `PrintPOAction`, `LockAction`, `UnlockAction`, `ConfirmReceiptDateAction`, `CancelAction`, `CreateBillAction`.
- **`PurchaseAgreementResource`**:
  - Model: `Requisition` (`purchases_requisitions`)
  - Navigation: Sort 3, Icon `heroicon-o-document-check`. Gated by `OrderSettings::enable_purchase_agreements` (`isDiscovered()`).
  - Sub-navigation pages: `ViewPurchaseAgreement`, `EditPurchaseAgreement`, `ManageRfqs`.
- **`VendorResource`**:
  - Model: `Partner` (`partners_partners`)
  - Navigation: Sort 4, Icon `heroicon-o-users`.
  - Sub-navigation pages: `ViewVendor`, `EditVendor`, `ManageContacts`, `ManageAddresses`, `ManageBills`, `ManagePurchases`.
- **Sub-Resource Tabs**:
  - `QuotationReceiptResource` / `PurchaseOrderReceiptResource`: Parent resource registration with `QuotationResource` and `PurchaseOrderResource` mapping to `operations`.
  - `QuotationBillResource` / `PurchaseOrderBillResource`: Parent resource registration mapping to `bills`.
- **`OrderResource`**: Abstract base resource providing shared `OrderForm`, `OrdersTable`, and `OrderInfolist` schemas.
  - Multi-Currency & Pricing Conversion: `OrderForm` features dynamic currency selection (`currency_id`), passes `currency` context to the `OrderSummary` view, and converts vendor pricing or product cost to the target order currency via `calculateUnitPrice` and `convertPrice` using `CurrencyRate` and UOM factors.
  - Agreement Currency Propagation: `PurchaseAgreementForm` defaults `currency_id` to current company currency and propagates currency to child orders.

#### 2. `Configurations` Cluster (`Webkul\Purchase\Filament\Admin\Clusters\Configurations`)
- `CurrencyResource`: Currencies master (`Webkul\Support\Models\Currency`).
- `PackagingResource`: Product packaging definitions (`Webkul\Product\Models\Packaging`).
- `ProductAttributeResource`: Product variant attributes (`Webkul\Product\Models\Attribute`).
- `ProductCategoryResource`: Product categories (`Webkul\Product\Models\Category`).
- `UOMCategoryResource`: Units of measure categories (`Webkul\Support\Models\UOMCategory`).
- `VendorPriceResource`: Vendor pricelist rules and supplier terms (`Webkul\Product\Models\ProductSupplier`), supporting multi-currency pricing, minimum quantities, and validity dates with currency filter and infolist display (`VendorPriceInfolist`).

#### 3. `Products` Cluster (`Webkul\Purchase\Filament\Admin\Clusters\Products`)
- `ProductResource`: Product catalog management with purchasing tabs and supplier rules (`Webkul\Invoice\Models\Product`).

#### 4. Settings Pages (`Webkul\Support\Filament\Clusters\Settings`)
- `ManageOrders`: Group `Purchase`, slug `purchase/manage-orders`. Controls `enable_order_approval`, `order_validation_amount`, `enable_lock_confirmed_orders`, and `enable_purchase_agreements`.
- `ManageProducts`: Group `Purchase`, slug `purchase/manage-products`. Controls `enable_variants`, `enable_uom`, and `enable_packagings`.

### Customer Panel
- **Cluster**: `Account` (`Webkul\Website\Filament\Customer\Clusters\Account`).
- **`QuotationResource`**: Pages `ListQuotations`, `ViewQuotation`.
- **`PurchaseOrderResource`**: Pages `ListPurchaseOrders`, `ViewPurchaseOrder`.
- **`OrderResource`**: Shared customer table and infolist schemas.
- **Livewire Components**:
  - `RespondQuotation`: Handles vendor online acceptance/decline via signed token routes.
  - `ListProducts`: Renders product line table in customer portal view.
  - `OrderSummary`: Dynamically computes untaxed amount, tax distributions, and grand total, rendering amounts with currency-specific symbols and formatting.

---

## Panels
[VERIFIED]
- **`admin`**: Registers complete purchasing back-office clusters (`Orders`, `Configurations`, `Products`, `Settings`).
- **`customer`**: Registers vendor self-service document view and acceptance resources under the `Account` cluster.

---

## Services
[VERIFIED]

1. **`Webkul\Purchase\PurchaseOrder` (Facade: `PurchaseOrder`)**:
   - Main purchasing workflow and calculation gateway class registered as singleton `'purchase_order'`.
   - Delegates to specialized service classes for workflows, calculations, receipts, billing, and PDFs.

2. **`Webkul\Purchase\Services\OrderWorkflow`**:
   - `sendRequestForQuotation(Order $record, array $data)`: Generates RFQ PDF, emails vendors, sets state to `OrderState::SENT`, attaches PDF to chatter.
   - `sendOrder(Order $record, array $data)`: Generates PO PDF, emails vendors, attaches PDF to chatter.
   - `confirm(Order $record)`: Checks `enable_order_approval` setting. If total amount exceeds threshold and user lacks global/group permission, sets state to `OrderState::TO_APPROVE`. Otherwise executes `approve()`.
   - `approve(Order $record, OrderSettings $settings)`: Sets state to `OrderState::DONE` (if `enable_lock_confirmed_orders` is true) or `OrderState::PURCHASE`, sets `approved_at`, triggers `ReceiptPlanner::planForOrder()`, refreshes receipt status, and dispatches `OrderConfirmed`.
   - `cancel(Order $record)`: Sets state to `OrderState::CANCELED`, unconfirms reminder flag, calls `ReceiptPlanner::cancelOperations()`, and dispatches `OrderCanceled`.
   - `backToDraft(Order $record)`, `lock(Order $record)`, `unlock(Order $record)`: State transitions dispatching corresponding lifecycle events.

3. **`Webkul\Purchase\Services\OrderCalculator`**:
   - `recompute(Order $record)`: Iterates line items, invokes `recomputeLine()`, aggregates untaxed, tax, total, and company currency totals, refreshes receipt status, and refreshes invoice status.
   - `recomputeLine(OrderLine $line)`: Recalculates billed quantities, received quantities, remaining billable quantity (`qty_to_invoice`), and line totals via `TaxFacade::computeAll()`.
   - `refreshInvoiceStatus(Order $order)`: Updates `invoice_status` to `NO`, `TO_INVOICED`, or `INVOICED`.
   - `refreshReceiptStatus(Order $order)`: Updates `receipt_status` to `NO`, `PENDING`, `PARTIAL`, or `FULL` based on linked warehouse operations.
   - `refreshQtyBilled(OrderLine $line)`: Scans linked `accountMoveLines` from non-cancelled bills, aggregating incoming invoices (`IN_INVOICE`) and subtracting refunds (`IN_REFUND`) with UOM conversion.
   - `refreshQtyReceived(OrderLine $line)`: Analyzes completed `inventoryMoves`, accounting for purchase returns, refunds, and dropship reversals.

4. **`Webkul\Purchase\Services\ReceiptPlanner`**:
   - `planForOrder(Order $record)`: Automatically creates incoming `Receipt` operations and `Move` records for physical goods (`ProductType::GOODS`) upon purchase order confirmation.
   - `syncFromLines($lines)`: Dynamically creates or adjusts inventory moves when line quantities are edited on confirmed orders.
   - `buildOperationAttributes(Order $order)`: Automatically constructs `ProcurementGroup` and assigns warehouse source/destination locations.
   - `destinationLocation(Order $order)`: Resolves customer location for dropship orders or warehouse input location for standard orders.
   - `cancelOperations(Order $record)`: Cancels open warehouse receipts upon order cancellation.

5. **`Webkul\Purchase\Services\Biller`**:
   - `createBill(Order $record)`: Instantiates a new vendor bill (`AccountMove` with `move_type = IN_INVOICE` or `IN_REFUND`), attaches it via `purchases_order_account_moves`, creates line items in `accounts_account_move_lines` linking `purchase_order_line_id`, and triggers `AccountFacade::computeAccountMove()`.

6. **`Webkul\Purchase\Services\DocumentGenerator`**:
   - `requestForQuotationPdf(Order $record)`: Renders PDF using view `purchases::filament.admin.clusters.orders.orders.actions.print-quotation` via DomPDF.
   - `purchaseOrderPdf(Order $record)`: Renders PDF using view `purchases::filament.admin.clusters.orders.orders.actions.print-purchase-order` via DomPDF.

---

## Events
[VERIFIED]
- `Webkul\Purchase\Events\OrderConfirmed`: Dispatched upon purchase order confirmation and approval (`OrderWorkflow::approve()`).
- `Webkul\Purchase\Events\OrderCanceled`: Dispatched when an order is cancelled (`OrderWorkflow::cancel()`).
- `Webkul\Purchase\Events\OrderDrafted`: Dispatched when an order transitions back to draft (`OrderWorkflow::backToDraft()`).
- `Webkul\Purchase\Events\OrderLocked`: Dispatched when an order is locked (`OrderWorkflow::lock()`).
- `Webkul\Purchase\Events\OrderUnlocked`: Dispatched when an order is unlocked (`OrderWorkflow::unlock()`).

---

## Listeners
[VERIFIED]
- **`Webkul\Purchase\Listeners\ComputePurchaseOrderListener`**:
  - Listens for `Webkul\Inventory\Events\OperationDone` and `OperationBackOrdered`.
  - Recomputes receipt status and billable quantities on affected purchase orders.
- **`Webkul\Purchase\Listeners\ComputePurchaseOrderFromMoveListener`**:
  - Listens for `Webkul\Account\Events\MoveConfirmed`, `MoveCancelled`, `MoveDrafted`, and `MoveReversed`.
  - Recomputes billed quantities and invoice status on purchase orders linked to the affected vendor bill.

---

## Observers
[NOT APPLICABLE]
Zero dedicated Eloquent Observer classes exist in `purchases`. Model lifecycle events are handled directly within model `boot()` methods:
- `Order::boot()`: Assigns `creator_id`, defaults `state = DRAFT`, generates sequence code `purchases.order` on creation.
- `OrderLine::boot()`: Assigns `creator_id`, triggers `PurchaseOrderFacade::createOrUpdateInventoryOperation()` on line creation or quantity mutation for confirmed orders.
- `Requisition::boot()`: Assigns `creator_id`, defaults `state = DRAFT`, generates name (`BO/id` or `PT/id`).
- `OrderGroup::boot()`: Assigns `creator_id`.

---

## Policies
[VERIFIED]

The `purchases` plugin defines 12 authorization policies registered under `plugins/webkul/purchases/src/Policies/`:

- **`PurchaseOrderPolicy`**: Enforces `view_any_purchase_purchase::order`, `view_purchase_purchase::order`, `create_purchase_purchase::order`, `update_purchase_purchase::order`, `delete_purchase_purchase::order`, `delete_any_purchase_purchase::order` using `HasScopedPermissions`.
- **`QuotationPolicy`**: Enforces quotation CRUD permissions via `HasScopedPermissions`.
- **`RequisitionPolicy`**: Enforces purchase agreement CRUD, soft-delete restore, and force-delete permissions via `HasScopedPermissions`.
- **`VendorResource` / `PartnerPolicy`**: Enforces vendor profile and sub-tab permissions.
- **`ProductPolicy` / `ProductSupplierPolicy` / `PackagingPolicy` / `CategoryPolicy` / `AttributePolicy` / `CurrencyPolicy` / `UOMCategoryPolicy`**: Manage configuration resource permissions.
- **`CustomerPurchaseOrderPolicy`**: Authorizes vendor portal access on the `customer` panel by asserting that authenticated portal user ID matches `$purchaseOrder->partner_id`.

---

## Routes
[VERIFIED]

### Web Routes (`plugins/webkul/purchases/routes/web.php`)
- `GET purchase/{order}/{action}` (`purchases.quotations.respond`): Signed URL route rendering `RespondQuotation` Livewire component for external vendor response.

### API Routes (`plugins/webkul/purchases/routes/api.php`)
All routes prefixed with `admin/api/v1/purchases` and guarded by `auth:sanctum`:
- `GET|POST admin/api/v1/purchases/vendor-price-lists`: `VendorPriceListController` (CRUD)
- `GET|POST admin/api/v1/purchases/purchase-orders`: `PurchaseOrderController` (CRUD)
- `POST admin/api/v1/purchases/purchase-orders/{id}/confirm`: `PurchaseOrderController::confirm`
- `POST admin/api/v1/purchases/purchase-orders/{id}/cancel`: `PurchaseOrderController::cancel`
- `POST admin/api/v1/purchases/purchase-orders/{id}/draft`: `PurchaseOrderController::draft`
- `POST admin/api/v1/purchases/purchase-orders/{id}/toggle-lock`: `PurchaseOrderController::toggleLock`
- `POST admin/api/v1/purchases/purchase-orders/{id}/confirm-receipt-date`: `PurchaseOrderController::confirmReceiptDate`
- `GET admin/api/v1/purchases/purchase-orders/{id}/lines`: `PurchaseOrderLineController`
- `GET admin/api/v1/purchases/purchase-orders/{id}/receipts`: `PurchaseOrderReceiptController`
- `GET admin/api/v1/purchases/purchase-orders/{id}/bills`: `PurchaseOrderBillController`
- `GET|POST admin/api/v1/purchases/purchase-agreements`: `PurchaseAgreementController` (Soft-deletable CRUD)
- `POST admin/api/v1/purchases/purchase-agreements/{id}/confirm`: `PurchaseAgreementController::confirm`
- `POST admin/api/v1/purchases/purchase-agreements/{id}/close`: `PurchaseAgreementController::close`
- `POST admin/api/v1/purchases/purchase-agreements/{id}/cancel`: `PurchaseAgreementController::cancel`
- `GET admin/api/v1/purchases/purchase-agreements/{id}/lines`: `PurchaseAgreementLineController`

---

## Settings
[VERIFIED]

1. **`Webkul\Purchase\Settings\OrderSettings` (`purchases_order` group)**:
   - `enable_order_approval` (`bool`): Enables manager approval requirement for orders above threshold.
   - `order_validation_amount` (`float`): Minimum currency threshold requiring approval (default: `5000.0`).
   - `enable_lock_confirmed_orders` (`bool`): Automatically transitions confirmed orders directly into locked `done` state.
   - `enable_purchase_agreements` (`bool`): Enables `PurchaseAgreementResource` visibility in navigation.

2. **`Webkul\Purchase\Settings\ProductSettings` (`purchases_product` group)**:
   - `enable_variants` (`bool`): Enables variant attribute selection on purchased items.
   - `enable_uom` (`bool`): Enables unit of measure selection.
   - `enable_packagings` (`bool`): Enables packaging quantity specifications.

---

## Translations
[VERIFIED]
Translation files reside under `resources/lang/` for 5 supported locales: `en`, `ar`, `es`, `fr`, `pt_BR`. Keys include:
- `purchases::enums/order-state.*`: draft, sent, to_approve, purchase, done, canceled.
- `purchases::enums/order-receipt-status.*`: no, pending, partial, full.
- `purchases::enums/order-invoice-status.*`: no, to-invoiced, invoiced.
- `purchases::enums/requisition-state.*`: draft, confirmed, closed, canceled.
- `purchases::enums/requisition-type.*`: blanket-order, purchase-template.
- `purchases::filament/admin/clusters/orders/...`: complete UI translations.

---

## Tests
[VERIFIED]
The `purchases` plugin contains comprehensive automated test coverage with **15 test files and 1 test helper** structured under `plugins/webkul/purchases/tests/`:

1. **API Feature Tests (`tests/Feature/API/V1/`)** (7 files):
   - `PurchaseAgreementLineTest.php`: Tests agreement line listing, retrieval, and company isolation.
   - `PurchaseAgreementTest.php`: Tests requisition CRUD, confirmation, cancellation, close, restore, and force-delete.
   - `PurchaseOrderBillTest.php`: Tests bill listing endpoint for purchase orders.
   - `PurchaseOrderLineTest.php`: Tests purchase line retrieval and subtotal computations.
   - `PurchaseOrderReceiptTest.php`: Tests warehouse receipt listing endpoint for purchase orders.
   - `PurchaseOrderTest.php`: Tests PO CRUD, confirmation, cancellation, draft, locking, and receipt date confirmation.
   - `VendorPriceListTest.php`: Tests vendor supplier pricelist management.

2. **Filament UI Smoke Tests (`tests/Feature/Filament/`)** (2 files):
   - `PurchaseOrderResourceTest.php`: Tests admin panel purchase order table, creation, and view actions.
   - `ResourceGlobalSearchSmokeTest.php`: Tests global search across purchase orders and requisitions.

3. **Workflow & Integration Tests (`tests/Feature/Workflows/`)** (6 files):
   - `PurchaseOrderTest.php`: 570 lines testing line pricing, tax inclusiveness/exclusiveness, currency conversion, approval limits, warehouse receipt generation, and partial receipt logic.
   - `OrderBillingTest.php`: Tests complete order billing cycle, status transitions (`TO_INVOICED` → `INVOICED`), bill cancellations, resets to draft, refund reversals, and return adjustments.
   - `OrderCurrencyConversionTest.php`: Tests purchase order line price calculation, vendor price multi-currency conversion, cost fallbacks, and UOM factor adjustments.
   - `TwoStepPurchaseOrderTest.php`: Tests two-step warehouse receiving routes (Input → Stock).
   - `CompanyIsolationTest.php`: Tests multi-company isolation boundaries.
   - `CompanyScopingInvariantsTest.php`: Asserts foreign key company consistency across fiscal positions, payment terms, and operation types.

4. **Test Helpers (`tests/Helpers/`)** (1 file):
   - `PurchaseHelper.php`: Reusable helper for bootstrapping test products, suppliers, taxes, RFQs, POs, receipts, and vendor bills.

---

## Data Flow & Lifecycle State Machine
[VERIFIED]

The purchasing architecture operates a strict multi-stage lifecycle linking requisitions, purchase orders, warehouse operations, and vendor bills:

```mermaid
stateDiagram-v2
    [*] --> RequisitionDraft: Create Agreement
    RequisitionDraft --> RequisitionConfirmed: Confirm Tender
    RequisitionConfirmed --> RFQDraft: Generate RFQs
    RequisitionConfirmed --> RequisitionClosed: All POs Done/Canceled
    RequisitionConfirmed --> RequisitionCanceled: Cancel Tender

    [*] --> RFQDraft: Direct Creation
    RFQDraft --> RFQSent: Send by Email (PO/PDF)
    RFQSent --> RFQDraft: Edit / Revise
    
    RFQDraft --> ToApprove: Confirm (Amount >= Limit & Not Manager)
    RFQSent --> ToApprove: Confirm (Amount >= Limit & Not Manager)
    
    RFQDraft --> PurchaseOrder: Confirm (Amount < Limit or Manager)
    RFQSent --> PurchaseOrder: Confirm (Amount < Limit or Manager)
    ToApprove --> PurchaseOrder: Manager Approval
    
    PurchaseOrder --> InventoryReceipts: Generate Warehouse Moves
    PurchaseOrder --> LockedDone: Auto-Lock or LockAction
    LockedDone --> PurchaseOrder: UnlockAction
    
    PurchaseOrder --> Canceled: Cancel Order
    RFQDraft --> Canceled: Cancel RFQ
    RFQSent --> Canceled: Cancel RFQ
    
    PurchaseOrder --> VendorBill: Create Bill (Biller::createBill)
    LockedDone --> VendorBill: Create Bill (Biller::createBill)
    
    VendorBill --> PostBill: Validate in Accounting
    PostBill --> InvoicedStatus: InvoiceStatus = INVOICED
```

### Exact State Machine Enums & Values

#### 1. Requisition Lifecycle (`RequisitionState`)
- **`draft`**: Tender agreement in drafting phase.
- **`confirmed`**: Active agreement open for RFQ generation (`canBeConfirmed`: requires at least 1 line).
- **`closed`**: Completed agreement (`canBeClosed`: all child orders settled in `done` or `canceled`).
- **`canceled`**: Revoked agreement.

#### 2. Order Lifecycle (`OrderState`)
- **`draft`**: Request for Quotation initialized with sequence number `PO/#####`.
- **`sent`**: Quotation PDF generated and emailed to vendor.
- **`to_approve`**: High-value purchase order waiting for manager approval (`total_amount >= order_validation_amount`).
- **`purchase`**: Confirmed purchase order. Incoming receipts and inventory moves generated in `inventories`.
- **`done`**: Locked purchase order. Cannot be modified without unlocking.
- **`canceled`**: Canceled order. Cancels linked open warehouse receipts.

#### 3. Receipt Status (`OrderReceiptStatus`)
- **`no`**: Non-stock goods, unconfirmed order, or `inventories` plugin not installed.
- **`pending`**: Receipt transfer generated but not yet processed in warehouse.
- **`partial`**: Part of ordered goods received into warehouse.
- **`full`**: All incoming stock moves marked `done` or canceled.

#### 4. Invoice / Billing Status (`OrderInvoiceStatus`)
- **`no`**: Order in draft/sent/canceled state or zero billable quantity.
- **`to_invoiced`**: Billable quantity remains (`qty_to_invoice != 0`). Calculated based on product purchasing policy:
  - Ordered quantities policy (`purchase_method = 'purchase'`): `product_qty - qty_invoiced`.
  - Received quantities policy (`purchase_method = 'receive'`): `qty_received - qty_invoiced`.
- **`invoiced`**: All line items completely billed (`qty_to_invoice == 0`) and matching `AccountMove` exists.

---

## Business Rules & Logic
[VERIFIED]

1. **Company Scoping & Consistency**:
   - `Order` and `OrderLine` enforce strict company tenancy via `BelongsToCompany` and `ChecksCompanyConsistency`.
   - `Order::companyConsistentFields()` validates that `fiscal_position_id`, `payment_term_id`, `operation_type_id`, and `requisition_id` belong to the same company as the purchase order.
   - `OrderLine::companyConsistentFields()` asserts product company consistency.

2. **Sequential Numbering (`SequenceService`)**:
   - Purchase orders utilize sequence code `'purchases.order'` prefixed with `'PO/'`.
   - Requisitions generate document identifiers formatted as `'BO/' . $id` (for blanket orders) or `'PT/' . $id` (for purchase templates).

3. **Tax Computation & Multi-Currency**:
   - Tax amounts are computed per line via `TaxFacade::computeAll()`, correctly respecting tax inclusion/exclusion flags, customer/vendor fiscal position mappings, and currency decimal precision (`decimal:4`).
   - Line subtotal and tax amounts convert into company currency (`total_cc_amount`) using the exchange rate at order date.

4. **Automated Inventory Dropshipping**:
   - When an order specifies a `destination_address_id` and an operation type of type `DROPSHIP`, `ReceiptPlanner` automatically routes destination locations directly to the customer location (`LocationType::CUSTOMER`) without moving goods through internal stock.

5. **Chatter & Audit Trail**:
   - `Order` and `Requisition` implement `HasChatter` and `HasLogActivity`, logging state changes, partner revisions, buyer assignments, and untaxed amounts to the chatter feed.

---

## Extension Points
[VERIFIED]
- **`ProductUsageRegistry`**: Registers `OrderLine` and `RequisitionLine` so core product deletion checks prevent deleting products referenced in purchase orders.
- **Dynamic Relations**: `Product::resolveRelationUsing('sellers', ...)` adds supplier relationship across single and configurable variant products.
- **Custom Fields (`HasCustomFields`)**: Dynamic custom fields can be attached to `Order`, `OrderLine`, and `Requisition` via the `fields` plugin.
- **Activity Planning**: Configured with `ACTIVITY_PLAN_PLUGIN = 'purchases'` for scheduling chatter activities.

---

## Dangerous Areas
[VERIFIED]

> [!WARNING]
> **Inventory Moves Quantity Mismatch & Return Validation**:
> `ReceiptPlanner::assertOrderedCoversReceived()` throws an explicit exception if a user attempts to reduce an order line's ordered quantity below the already received quantity (`product_qty < qty_received`). Users must generate a formal stock return in `inventories` before adjusting order quantities.

> [!WARNING]
> **Reordering Rule Warehouse Consistency**:
> `ReceiptPlanner::assertReorderingRuleMatchesWarehouse()` verifies that the warehouse associated with an order's `operation_type_id` matches the storage location specified on automated replenishment rules (`OrderPoint`). Mismatches will abort receipt planning.

> [!NOTE]
> **Automated Test Coverage**:
> Automated tests exist and are actively maintained (`plugins/webkul/purchases/tests/` contains 14 feature test files covering workflows, API endpoints, Filament UI smoke tests, multi-company boundaries, and billing calculations).

---

## Change Impact
[VERIFIED]
- **Upstream Dependencies**: Depends on `invoices` (and indirectly `accounts`, `products`, `partners`, `support`, `security`, `chatter`).
- **Downstream Dependents**: Optional plugins in later phases may integrate with purchasing (e.g. `manufacturing` for component replenishment, `sales` for automated dropshipping / back-to-back PO generation).

---

## Evidence
[VERIFIED]
- Service Provider: `plugins/webkul/purchases/src/PurchaseServiceProvider.php:35`
- Filament Plugin: `plugins/webkul/purchases/src/PurchasePlugin.php:9`
- Order Model: `plugins/webkul/purchases/src/Models/Order.php:36`
- Order Line Model: `plugins/webkul/purchases/src/Models/OrderLine.php:33`
- Requisition Model: `plugins/webkul/purchases/src/Models/Requisition.php:23`
- Order State Enum: `plugins/webkul/purchases/src/Enums/OrderState.php:8`
- Receipt Status Enum: `plugins/webkul/purchases/src/Enums/OrderReceiptStatus.php:8`
- Invoice Status Enum: `plugins/webkul/purchases/src/Enums/OrderInvoiceStatus.php:8`
- Workflow Service: `plugins/webkul/purchases/src/Services/OrderWorkflow.php:20`
- Calculator Service: `plugins/webkul/purchases/src/Services/OrderCalculator.php:13`
- Receipt Planner Service: `plugins/webkul/purchases/src/Services/ReceiptPlanner.php:19`
- Biller Service: `plugins/webkul/purchases/src/Services/Biller.php:10`
- Document Generator: `plugins/webkul/purchases/src/Services/DocumentGenerator.php:8`
- Sequence Service Usage: `plugins/webkul/purchases/src/Models/Order.php:254`, `PurchaseServiceProvider.php:89`, `plugins/webkul/purchases/database/seeders/SequenceSeeder.php:12`
- Routes: `plugins/webkul/purchases/routes/api.php:1-35`, `plugins/webkul/purchases/routes/web.php:1-11`
- Settings: `plugins/webkul/purchases/src/Settings/OrderSettings.php:7`, `plugins/webkul/purchases/src/Settings/ProductSettings.php:7`
- Tests: `plugins/webkul/purchases/tests/Feature/Workflows/PurchaseOrderTest.php:1`, `plugins/webkul/purchases/tests/Feature/Workflows/OrderBillingTest.php:1`
