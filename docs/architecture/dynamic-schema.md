---
status: verified
source_of_truth: source-code
last_verified: 2026-09-02
scope: global
confidence: high
---

# Aureus ERP — Dynamic Schema & Runtime Model Architecture

## 1. Overview

Aureus ERP employs a modular, decoupled package architecture where base data models in Core or foundational plugins (such as `partners` and `products`) remain unaware of optional downstream business domains (such as `accounts`, `inventories`, `manufacturing`, and `purchases`). 

To achieve cross-plugin integration without introducing hard compile-time dependencies or modifying base database migrations, the system relies on **three distinct runtime dynamic mechanisms**:

1. **Dynamic Relationship Binding (`Model::resolveRelationUsing`)**:
   Allows optional domain plugins to dynamically attach Eloquent relationships (`belongsTo`, `hasMany`, `belongsToMany`) to external models at application boot time.
2. **Company-Specific Property Resolution (`Webkul\Account\Casts\CompanyProperty`)**:
   A custom Eloquent Attribute Cast that implements multi-tenant property resolution (EAV-style mapping) per active company without polluting core entities with tenant-specific foreign keys.
3. **Runtime Custom Field Rendering (`fields` plugin & `HasCustomFields` traits)**:
   A metadata-driven engine that alters physical database schemas at runtime (`FieldsColumnManager`), merges model fillables and casts on lifecycle hooks, and dynamically injects fields, columns, filters, and infolist entries into Filament UI schemas.

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                   Aureus ERP Dynamic Schema Mechanisms                          │
└─────────────────────────────────┬────────────────────────────────┬───────────────────────────────┘
                                  │                                │
         ┌────────────────────────┴────────┐      ┌────────────────┴──────────────┐
         │                                 │      │                               │
         ▼                                 ▼      ▼                               ▼
┌───────────────────────────────┐ ┌──────────────────────────────┐ ┌──────────────────────────────┐
│  Dynamic Relation Binding     │ │  Company Property Cast       │ │  Runtime Custom Fields       │
│  (resolveRelationUsing)       │ │  (CompanyProperty)           │ │  (fields / HasCustomFields)  │
├───────────────────────────────┤ ├──────────────────────────────┤ ├──────────────────────────────┤
│ • Decouples core & optional   │ │ • Scopes properties to       │ │ • User-defined metadata      │
│   domain models.              │ │   active tenant company.     │ │   extensions.                │
│ • Binds Eloquent relations    │ │ • Attribute cast returning   │ │ • Alters physical database   │
│   in ServiceProvider::boot(). │ │   per-company values.        │ │   tables via Blueprint.      │
│ • No physical schema changes. │ │ • Stores data in tenant-     │ │ • Dynamic Filament form,     │
│ • 22 Verified Usage Sites.    │ │   specific mapping tables.   │ │   table, & infolist inputs.  │
└───────────────────────────────┘ └──────────────────────────────┘ └──────────────────────────────┘
```

[VERIFIED]
Evidence: `plugins/webkul/accounts/src/AccountServiceProvider.php`; `plugins/webkul/accounts/src/Casts/CompanyProperty.php`; `plugins/webkul/fields/src/Traits/HasCustomFields.php`; `plugins/webkul/fields/src/FieldsColumnManager.php`

---

## 2. Dynamic Relationship Binding (`resolveRelationUsing`)

### Mechanism & Lifecycle

Laravel Eloquent provides `Model::resolveRelationUsing($name, Closure $callback)` allowing developers to define relationships on models at runtime. When an undefined method matching `$name` is invoked on the model, Eloquent checks its internal `$relationResolvers` array and executes the callback, passing the model instance.

In Aureus ERP, dynamic relations are registered within plugin service providers during the boot phase (`PackageServiceProvider::boot()` calling `packageBooted()` or explicit contribution methods):
1. The service provider checks if the plugin is installed via `Package::isPluginInstalled(static::$name)`.
2. If installed, it registers dynamic relations on target models (`Product`, `Partner`, `Category`).
3. If uninstalled, the dynamic relations are never registered, ensuring that uninstalled plugins do not pollute base model relationship graphs or trigger database queries against uninstalled tables.

```
Application Boot Phase
       │
       ▼
PluginServiceProvider::packageBooted()
       │
       ├── Check: Package::isPluginInstalled('plugin-name')
       │     ├── [FALSE] ──► Return early (No dynamic relations registered)
       │     └── [TRUE]
       │
       ▼
TargetModel::resolveRelationUsing('relationName', function (TargetModel $model) {
    return $model->belongsTo(...) / hasMany(...) / belongsToMany(...);
});
       │
       ▼
Eloquent Model $model->relationName
       └── Resolves dynamically via Model::$relationResolvers['TargetModel']['relationName']
```

[VERIFIED]
Evidence: `plugins/webkul/accounts/src/AccountServiceProvider.php` (lines 251–369); `plugins/webkul/inventories/src/InventoryServiceProvider.php` (lines 255–280); `plugins/webkul/manufacturing/src/ManufacturingServiceProvider.php` (lines 272–280); `plugins/webkul/purchases/src/PurchaseServiceProvider.php` (lines 131–134)

---

## 3. Complete `resolveRelationUsing()` Catalog

At the time of verification, the repository contains exactly **22 occurrences** of `resolveRelationUsing()` across **4 service providers** (`accounts`: 14, `inventories`: 5, `manufacturing`: 2, `purchases`: 1).

Every actual usage site is documented below with its declaring model, target model, relationship type, observed purpose, source evidence, and verified architectural rationale.

### 1. `accounts` Plugin (`plugins/webkul/accounts/src/AccountServiceProvider.php`)

| # | Declaring Model | Relation Name | Target Model | Relationship Type & Foreign Key | Observed Behavior & Architectural Rationale | Verification Label & Evidence |
| :-: | :--- | :--- | :--- | :--- | :--- | :---: |
| 1 | `Webkul\Partner\Models\Partner` | `CompanyProperty::RELATION` (`'companyProperties'`) | `Webkul\Account\Models\PartnerCompanyProperty` | `hasMany(PartnerCompanyProperty::class, 'partner_id')` | **Observed Behavior**: [VERIFIED] Injects dynamic `hasMany` relationship linking `Partner` to `PartnerCompanyProperty` records.<br>**Architectural Rationale**: [INFERRED] Enables multi-tenant accounting isolation without adding company columns to core `partners_partners`. | [VERIFIED]<br>`AccountServiceProvider.php:251` |
| 2 | `Webkul\Partner\Models\Partner` | `'propertyAccountPayable'` | `Webkul\Account\Models\Account` | `belongsTo(Account::class, 'property_account_payable_id')` | **Observed Behavior**: [VERIFIED] Injects dynamic `belongsTo` relationship resolving default payable account.<br>**Architectural Rationale**: [INFERRED] Decouples core `Partner` from optional `accounts` plugin so base model requires no static accounting relations. | [VERIFIED]<br>`AccountServiceProvider.php:258` |
| 3 | `Webkul\Partner\Models\Partner` | `'propertyAccountReceivable'` | `Webkul\Account\Models\Account` | `belongsTo(Account::class, 'property_account_receivable_id')` | **Observed Behavior**: [VERIFIED] Injects dynamic `belongsTo` relationship resolving default receivable account.<br>**Architectural Rationale**: [INFERRED] Decouples core `Partner` from optional `accounts` plugin so base model requires no static accounting relations. | [VERIFIED]<br>`AccountServiceProvider.php:259` |
| 4 | `Webkul\Partner\Models\Partner` | `'propertyAccountPosition'` | `Webkul\Account\Models\FiscalPosition` | `belongsTo(FiscalPosition::class, 'property_account_position_id')` | **Observed Behavior**: [VERIFIED] Injects dynamic `belongsTo` relationship resolving default fiscal position mapping.<br>**Architectural Rationale**: [INFERRED] Decouples core `Partner` from optional `accounts` plugin so base model requires no static accounting relations. | [VERIFIED]<br>`AccountServiceProvider.php:260` |
| 5 | `Webkul\Partner\Models\Partner` | `'propertyPaymentTerm'` | `Webkul\Account\Models\PaymentTerm` | `belongsTo(PaymentTerm::class, 'property_payment_term_id')` | **Observed Behavior**: [VERIFIED] Injects dynamic `belongsTo` relationship resolving default customer payment terms.<br>**Architectural Rationale**: [INFERRED] Decouples core `Partner` from optional `accounts` plugin so base model requires no static accounting relations. | [VERIFIED]<br>`AccountServiceProvider.php:261` |
| 6 | `Webkul\Partner\Models\Partner` | `'propertySupplierPaymentTerm'` | `Webkul\Account\Models\PaymentTerm` | `belongsTo(PaymentTerm::class, 'property_supplier_payment_term_id')` | **Observed Behavior**: [VERIFIED] Injects dynamic `belongsTo` relationship resolving default vendor payment terms.<br>**Architectural Rationale**: [INFERRED] Decouples core `Partner` from optional `accounts` plugin so base model requires no static accounting relations. | [VERIFIED]<br>`AccountServiceProvider.php:262` |
| 7 | `Webkul\Partner\Models\Partner` | `'propertyOutboundPaymentMethodLine'` | `Webkul\Account\Models\PaymentMethodLine` | `belongsTo(PaymentMethodLine::class, 'property_outbound_payment_method_line_id')` | **Observed Behavior**: [VERIFIED] Injects dynamic `belongsTo` relationship resolving default outbound payment method line.<br>**Architectural Rationale**: [INFERRED] Decouples core `Partner` from optional `accounts` plugin so base model requires no static accounting relations. | [VERIFIED]<br>`AccountServiceProvider.php:263` |
| 8 | `Webkul\Partner\Models\Partner` | `'propertyInboundPaymentMethodLine'` | `Webkul\Account\Models\PaymentMethodLine` | `belongsTo(PaymentMethodLine::class, 'property_inbound_payment_method_line_id')` | **Observed Behavior**: [VERIFIED] Injects dynamic `belongsTo` relationship resolving default inbound payment method line.<br>**Architectural Rationale**: [INFERRED] Decouples core `Partner` from optional `accounts` plugin so base model requires no static accounting relations. | [VERIFIED]<br>`AccountServiceProvider.php:264` |
| 9 | `Webkul\Product\Models\Product` | `'productTaxes'` | `Webkul\Account\Models\Tax` | `belongsToMany(Tax::class, 'accounts_product_taxes', 'product_id', 'tax_id')` | **Observed Behavior**: [VERIFIED] Injects dynamic `belongsToMany` relationship associating customer sales taxes.<br>**Architectural Rationale**: [INFERRED] Decouples core `Product` from optional `accounts` plugin. | [VERIFIED]<br>`AccountServiceProvider.php:309` |
| 10 | `Webkul\Product\Models\Product` | `'supplierTaxes'` | `Webkul\Account\Models\Tax` | `belongsToMany(Tax::class, 'accounts_product_supplier_taxes', 'product_id', 'tax_id')` | **Observed Behavior**: [VERIFIED] Injects dynamic `belongsToMany` relationship associating vendor purchase taxes.<br>**Architectural Rationale**: [INFERRED] Decouples core `Product` from optional `accounts` plugin. | [VERIFIED]<br>`AccountServiceProvider.php:316` |
| 11 | `Webkul\Product\Models\Product` | `CompanyProperty::RELATION` (`'companyProperties'`) | `Webkul\Account\Models\ProductCompanyAccount` | `hasMany(ProductCompanyAccount::class, 'product_id')` | **Observed Behavior**: [VERIFIED] Injects dynamic `hasMany` relationship linking product to per-company account rows.<br>**Architectural Rationale**: [INFERRED] Enables multi-tenant accounting isolation per product. | [VERIFIED]<br>`AccountServiceProvider.php:328` |
| 12 | `Webkul\Product\Models\Category` | `CompanyProperty::RELATION` (`'companyProperties'`) | `Webkul\Account\Models\CategoryCompanyAccount` | `hasMany(CategoryCompanyAccount::class, 'category_id')` | **Observed Behavior**: [VERIFIED] Injects dynamic `hasMany` relationship linking product category to per-company account rows.<br>**Architectural Rationale**: [INFERRED] Enables multi-tenant fallback account isolation per category. | [VERIFIED]<br>`AccountServiceProvider.php:339` |
| 13 | `Webkul\Product\Models\Product` | `'propertyAccountIncome'` | `Webkul\Account\Models\Account` | `belongsTo(Account::class, 'property_account_income_id')` | **Observed Behavior**: [VERIFIED] Injects dynamic `belongsTo` relationship resolving income accounts with query filters (`deprecated = false`, valid account types).<br>**Architectural Rationale**: [INFERRED] Decouples `Product` from `Account` and enforces ledger account type validation. | [VERIFIED]<br>`AccountServiceProvider.php:344` |
| 14 | `Webkul\Product\Models\Product` | `'propertyAccountExpense'` | `Webkul\Account\Models\Account` | `belongsTo(Account::class, 'property_account_expense_id')` | **Observed Behavior**: [VERIFIED] Injects dynamic `belongsTo` relationship resolving expense accounts with query filters (`deprecated = false`, valid account types).<br>**Architectural Rationale**: [INFERRED] Decouples `Product` from `Account` and enforces ledger account type validation. | [VERIFIED]<br>`AccountServiceProvider.php:357` |

---

### 2. `inventories` Plugin (`plugins/webkul/inventories/src/InventoryServiceProvider.php`)

| # | Declaring Model | Relation Name | Target Model | Relationship Type & Definition | Observed Behavior & Architectural Rationale | Verification Label & Evidence |
| :-: | :--- | :--- | :--- | :--- | :--- | :---: |
| 15 | `Webkul\Product\Models\Product` | `'routes'` | `Webkul\Inventory\Models\Route` | `belongsToMany(Route::class, 'inventories_product_routes', 'product_id', 'route_id')` | **Observed Behavior**: [VERIFIED] Injects dynamic `belongsToMany` relationship associating inventory logistics routes.<br>**Architectural Rationale**: [INFERRED] Decouples core `Product` from optional `inventories` plugin. | [VERIFIED]<br>`InventoryServiceProvider.php:255` |
| 16 | `Webkul\Product\Models\Product` | `'responsible'` | `Webkul\Security\Models\User` | `belongsTo(User::class, 'responsible_id')` | **Observed Behavior**: [VERIFIED] Injects dynamic `belongsTo` relationship resolving responsible warehouse user.<br>**Architectural Rationale**: [UNKNOWN] The repository demonstrates dynamic binding here, but does not establish an explicit reason why a conventional static relation was not used. | [VERIFIED]<br>`InventoryServiceProvider.php:262` |
| 17 | `Webkul\Product\Models\Product` | `'moveLines'` | `Webkul\Inventory\Models\MoveLine` | Dynamic closure checking `$product->is_configurable`<br>→ `hasMany(MoveLine::class)->orWhereIn('product_id', variants)` | **Observed Behavior**: [VERIFIED] Injects dynamic relationship branching on `$product->is_configurable` to aggregate move lines across variants.<br>**Architectural Rationale**: [INFERRED] Provides transparent stock move line aggregation across variants on configurable products. | [VERIFIED]<br>`InventoryServiceProvider.php:267` |
| 18 | `Webkul\Product\Models\Product` | `'moves'` | `Webkul\Inventory\Models\Move` | Dynamic closure checking `$product->is_configurable`<br>→ `hasMany(Move::class)->orWhereIn('product_id', variants)` | **Observed Behavior**: [VERIFIED] Injects dynamic relationship branching on `$product->is_configurable` to aggregate moves across variants.<br>**Architectural Rationale**: [INFERRED] Provides transparent stock move aggregation across variants on configurable products. | [VERIFIED]<br>`InventoryServiceProvider.php:272` |
| 19 | `Webkul\Product\Models\Product` | `'quantities'` | `Webkul\Inventory\Models\ProductQuantity` | Dynamic closure checking `$product->is_configurable`<br>→ `hasMany(ProductQuantity::class)->orWhereIn('product_id', variants)` | **Observed Behavior**: [VERIFIED] Injects dynamic relationship branching on `$product->is_configurable` to aggregate stock quantities across variants.<br>**Architectural Rationale**: [INFERRED] Provides transparent inventory stock balance aggregation across variants on configurable products. | [VERIFIED]<br>`InventoryServiceProvider.php:277` |

---

### 3. `manufacturing` Plugin (`plugins/webkul/manufacturing/src/ManufacturingServiceProvider.php`)

| # | Declaring Model | Relation Name | Target Model | Relationship Type & Definition | Observed Behavior & Architectural Rationale | Verification Label & Evidence |
| :-: | :--- | :--- | :--- | :--- | :--- | :---: |
| 20 | `Webkul\Product\Models\Product` | `'billsOfMaterials'` | `Webkul\Manufacturing\Models\BillOfMaterial` | `hasMany(BillOfMaterial::class, 'product_id')` | **Observed Behavior**: [VERIFIED] Injects dynamic `hasMany` relationship linking product to its Bills of Materials.<br>**Architectural Rationale**: [INFERRED] Decouples core `Product` from optional `manufacturing` plugin. | [VERIFIED]<br>`ManufacturingServiceProvider.php:272` |
| 21 | `Webkul\Product\Models\Product` | `'billOfMaterialLines'` | `Webkul\Manufacturing\Models\BillOfMaterialLine` | `hasMany(BillOfMaterialLine::class, 'product_id')` | **Observed Behavior**: [VERIFIED] Injects dynamic `hasMany` relationship linking product to BoM component lines.<br>**Architectural Rationale**: [INFERRED] Decouples core `Product` from optional `manufacturing` plugin. | [VERIFIED]<br>`ManufacturingServiceProvider.php:277` |

---

### 4. `purchases` Plugin (`plugins/webkul/purchases/src/PurchaseServiceProvider.php`)

| # | Declaring Model | Relation Name | Target Model | Relationship Type & Definition | Observed Behavior & Architectural Rationale | Verification Label & Evidence |
| :-: | :--- | :--- | :--- | :--- | :--- | :---: |
| 22 | `Webkul\Product\Models\Product` | `'sellers'` | `Webkul\Purchase\Models\ProductSupplier` | Dynamic closure checking `$product->is_configurable`<br>→ `hasMany(ProductSupplier::class)->orWhereIn('product_id', variants)` | **Observed Behavior**: [VERIFIED] Injects dynamic relationship branching on `$product->is_configurable` to aggregate supplier pricelists across variants.<br>**Architectural Rationale**: [INFERRED] Decouples core `Product` from `purchases` and provides transparent supplier aggregation across variants. | [VERIFIED]<br>`PurchaseServiceProvider.php:131` |

---

## 4. Company-Specific Property Resolution (`CompanyProperty` Cast)

### Architectural Purpose

In a multi-company ERP, certain master data entities (`Partner`, `Product`, `Category`) are shared across multiple subsidiary companies, but require **tenant-specific accounting configurations**. For example:
- Partner "Acme Corp" has Account Receivable `10100` in Company A, but Account Receivable `10200` in Company B.
- Product "Office Desk" posts revenue to Income Account `40100` in Company A, but `40200` in Company B.

Rather than duplicating the entire `Partner` or `Product` table per company, Aureus ERP implements the **Company Property Cast** pattern via `Webkul\Account\Casts\CompanyProperty`.

```
                  ┌──────────────────────────────────────────────┐
                  │       Webkul\Partner\Models\Partner          │
                  │       (Shared Global Master Entity)          │
                  └──────────────────────┬───────────────────────┘
                                         │
             ┌───────────────────────────┴───────────────────────────┐
             │                                                       │
             ▼                                                       ▼
┌─────────────────────────┐                             ┌─────────────────────────┐
│  Company A Context      │                             │  Company B Context      │
│  (current_company_id=1) │                             │  (current_company_id=2) │
├─────────────────────────┤                             ├─────────────────────────┤
│ property_account_payable│                             │ property_account_payable│
│   → Account #10100      │                             │   → Account #20100      │
└────────────┬────────────┘                             └────────────┬────────────┘
             │                                                       │
             └───────────────────────────┬───────────────────────────┘
                                         │
                                         ▼
                  ┌──────────────────────────────────────────────┐
                  │   partners_partner_company_properties        │
                  ├──────────────────────────────────────────────┤
                  │ • id                                         │
                  │ • partner_id (FK -> partners_partners)       │
                  │ • company_id (FK -> companies)               │
                  │ • property_account_payable_id                │
                  │ • property_account_receivable_id             │
                  │ • property_account_position_id               │
                  │ • property_payment_term_id                   │
                  │ • property_supplier_payment_term_id          │
                  └──────────────────────────────────────────────┘
```

### Technical Implementation

1. **Cast Declaration**:
   `AccountServiceProvider::boot()` registers casts dynamically:
   ```php
   Partner::contributeCasts([
       'property_account_payable_id' => CompanyProperty::class.':'.PartnerCompanyProperty::class.',partner_id',
       // ...
   ]);
   ```
2. **Getter Resolution (`get()`)**:
   - Determines the active tenant company via `current_company_id()` (from `CompanyContext`).
   - Checks in-memory pending changes via `WeakMap`.
   - Eager loads the `companyProperties` relation and retrieves the record matching `'company_id' == $companyId`.
   - Returns the property value, or falls back to `$model->getAttributes()[$field]`.
3. **Setter Buffering (`set()`)**:
   - Intercepts writes and buffers pending attribute changes in a static `WeakMap` associated with the model instance without mutating the parent table immediately.
4. **Flush & Persistence (`flush()`)**:
   - Upon model save/update, writes the buffered attributes into the tenant table (`PartnerCompanyProperty::updateOrCreate(['company_id' => $companyId, 'partner_id' => $partner->id], $pending)`).

[VERIFIED]
Evidence: `plugins/webkul/accounts/src/Casts/CompanyProperty.php` (lines 9–144); `plugins/webkul/accounts/src/AccountServiceProvider.php` (lines 241–254)

---

## 5. Runtime Custom Fields (`fields` Plugin & `HasCustomFields`)

### Architectural Purpose

The `fields` plugin (`plugins/webkul/fields`) provides user-driven, metadata-configured custom attribute extension across customizable models in Aureus ERP. Unlike the fixed developer-defined relations or company property casts, custom fields can be created dynamically by system administrators at runtime via `FieldResource`.

```
1. Administrator creates Custom Field via UI (FieldResource)
   └── Field record saved in `custom_fields` table
       ↓
2. Physical Database Column Creation (FieldsColumnManager)
   └── FieldsColumnManager::createColumn($field)
       └── Schema::table($modelTable, fn (Blueprint $table) => $table->$type($field->code)->nullable())
       ↓
3. Eloquent Model Auto-Hydration (Model HasCustomFields Trait)
   └── Webkul\Field\Traits\HasCustomFields
       ├── On retrieved/creating/updating: loads Field::forCustomizable(static::class)
       ├── mergeFillable([$field->code, ...])
       └── mergeCasts([$field->code => 'array'|'boolean'|'string'])
       ↓
4. Runtime Filament UI Generation (Filament HasCustomFields Trait)
   └── Webkul\Field\Filament\Traits\HasCustomFields
       ├── CustomFields::make(static::class)->getSchema() ──► Form Inputs
       ├── CustomColumns::make(static::class)->getColumns() ─► Table Columns
       ├── CustomFilters::make(static::class)->getFilters() ─► Table Filters
       └── CustomEntries::make(static::class)->getSchema() ──► Infolist Entries
```

### Core Components

1. **`FieldsColumnManager` (`plugins/webkul/fields/src/FieldsColumnManager.php`)**:
   Directly executes physical schema alterations using Laravel's `Schema::table()` and `Blueprint` methods (`createColumn`, `updateColumn`, `deleteColumn`). Maps custom field types (`text`, `select`, `checkbox`, `datetime`, `color`, `radio`) to database column types (`string`, `text`, `integer`, `decimal`, `boolean`, `json`, `datetime`).
2. **Model Trait `Webkul\Field\Traits\HasCustomFields` (`plugins/webkul/fields/src/Traits/HasCustomFields.php`)**:
   Listens to Eloquent lifecycle events (`retrieved`, `creating`, `updating`) to merge active custom field codes into `$fillable` and map appropriate casts (`array` for multiselect/checkbox lists, `boolean` for toggles/checkboxes, `string` for text).
3. **Filament Trait `Webkul\Field\Filament\Traits\HasCustomFields` (`plugins/webkul/fields/src/Filament/Traits/HasCustomFields.php`)**:
   Provides helper methods (`getCustomFormFields()`, `getCustomTableColumns()`, `getCustomTableFilters()`, `getCustomInfolistEntries()`) called inside Filament Resource `form()`, `table()`, and `infolist()` schema definitions.

[VERIFIED]
Evidence: `plugins/webkul/fields/src/FieldsColumnManager.php`; `plugins/webkul/fields/src/Traits/HasCustomFields.php`; `plugins/webkul/fields/src/Filament/Traits/HasCustomFields.php`

---

## 6. Comparison of the Three Dynamic Mechanisms

The three mechanisms solve fundamentally different architectural challenges, operate at different execution layers, and produce distinct database and UI outcomes. They must not be conflated.

| Comparison Dimension | Dynamic Relationship Binding (`resolveRelationUsing`) | Company-Specific Property Resolution (`CompanyProperty`) | Runtime Custom Field Rendering (`fields` / `HasCustomFields`) |
| :--- | :--- | :--- | :--- |
| **Primary Architectural Problem Solved** | Cross-plugin model decoupling and optional domain extension. | Multi-tenant property isolation for shared master records. | User-defined administrative attribute customization. |
| **Defined By** | Developers in PHP ServiceProvider source code. | Developers via custom Eloquent Attribute Cast in PHP. | System Administrators via Filament UI at runtime. |
| **Execution Layer** | Eloquent Relationship Resolution Layer (`Model::$relationResolvers`). | Eloquent Attribute Casting Layer (`CastsAttributes::get/set/flush`). | Database Schema (`Schema::table`) + Eloquent Lifecycle Hooks + Filament Schemas. |
| **Physical Database Schema Impact** | **None**. Foreign keys and pivot tables are predefined in migrations; the ORM relation is bound at runtime. | **None to parent table**. Data stored in predefined relational tables (`*_company_properties`). | **Direct & Physical**. Dynamically executes `ALTER TABLE ADD/DROP COLUMN` on model table. |
| **Eloquent Relationship Impact** | **High**. Injects callable Eloquent relations (`belongsTo`, `hasMany`, `belongsToMany`). | **Moderate**. Uses `companyProperties` relation to query/eager-load property rows. | **Low / None**. Injects attributes and scalar/JSON casts, not Eloquent relationships. |
| **Filament UI Schema Impact** | **Indirect**. Enables relation managers, relational columns, and select options to query the relation. | **Transparent**. Fields bind to attribute name (`property_account_payable_id`) normally. | **Direct & Comprehensive**. Dynamically generates form inputs, table columns, filters, and infolist entries. |
| **Multi-Tenancy Interaction** | Respects company scope on target models (e.g. `Tax::class`). | Directly driven by active tenant (`current_company_id()`). | Shared across all companies on the physical table schema. |
| **Verified Repository Occurrences** | Exactly **22 usage sites** across 4 plugins. | Configured on `Partner`, `Product`, and `Category` models. | Applied via `HasCustomFields` across customizable ERP models. |

[VERIFIED]
Evidence: Comparative analysis synthesized from `plugins/webkul/accounts/`, `plugins/webkul/inventories/`, `plugins/webkul/manufacturing/`, `plugins/webkul/purchases/`, and `plugins/webkul/fields/`

---

## 7. Static Analysis Limitations

A central architectural characteristic of Aureus ERP is that **conventional static analysis tools and IDEs cannot determine the complete schema or relationship graph from model source files alone**.

### Source-Proven Limitations

1. **Invisible Eloquent Relationships**:
   - Opening `Webkul\Product\Models\Product` reveals no methods named `sellers()`, `billsOfMaterials()`, `billOfMaterialLines()`, `routes()`, `moveLines()`, `moves()`, `quantities()`, `productTaxes()`, `supplierTaxes()`, `propertyAccountIncome()`, or `propertyAccountExpense()`.
   - All 14 dynamic product relations exist exclusively in `Model::$relationResolvers` at runtime when their parent plugins are booted.
   - IDE "Find Usages" or PHPStan running without a dedicated Laravel plugin will report these relationships as missing methods or dynamic property violations.
2. **Polymorphic Variant Aggregations**:
   - In `inventories` and `purchases`, relationships like `moveLines`, `moves`, `quantities`, and `sellers` dynamically evaluate `$product->is_configurable` at execution time. If true, they execute an `orWhereIn('product_id', $product->variants()->pluck('id'))` query. Static analysis cannot determine the SQL query structure without knowing runtime model state.
3. **Transparent Company Property Redirection**:
   - Accessing `$partner->property_account_payable_id` does not read a column on `partners_partners`.
   - Static analysis inspecting the database migration for `partners_partners` will find no such column.
   - The property is intercepted by `CompanyProperty`, which executes queries against `partners_partner_company_properties` scoped to `current_company_id()`.
4. **Runtime Database Columns and Fillables**:
   - `Field::forCustomizable(static::class)` injects `$fillable` attributes and `$casts` dynamically during Eloquent lifecycle events (`retrieved`, `creating`, `updating`).
   - Static inspection of `$model->getFillable()` or `$model->getCasts()` in model class definitions reflects only baseline hardcoded fields.
5. **Phase 4 Verification Alignment**:
   - This analysis validates and resolves the caveat documented in `docs/database/relationships.md`:
     > *«Dynamic Relationship State: [UNKNOWN] — Which dynamic relationships actually exist at runtime is fundamentally unknown from static analysis alone. It depends entirely on which plugins are installed, active, and successfully booted in the current environment.»*

[VERIFIED]
Evidence: `docs/database/relationships.md` (lines 89–98, 128–129); `plugins/webkul/products/src/Models/Product.php`; `plugins/webkul/accounts/src/Casts/CompanyProperty.php`

---

## 8. Known Unknowns

1. **Runtime Plugin State Dependency**:
   - The exact set of active dynamic relationships in any specific deployed instance of Aureus ERP is determined by the `plugins` table (`is_installed = true`). In an environment where `manufacturing` is uninstalled, `Product::resolveRelationUsing('billsOfMaterials', ...)` is not executed.
   - **Status**: `[UNKNOWN]` without querying the runtime database `plugins` table for a specific deployment.

---

## 9. Evidence Index

| Entity / Concept | Source File & Symbol | Documentation Cross-Reference | Verification Status |
| :--- | :--- | :--- | :---: |
| Dynamic Relation Binding Engine | `plugins/webkul/accounts/src/AccountServiceProvider.php:251-369`<br>`plugins/webkul/inventories/src/InventoryServiceProvider.php:255-280`<br>`plugins/webkul/manufacturing/src/ManufacturingServiceProvider.php:272-280`<br>`plugins/webkul/purchases/src/PurchaseServiceProvider.php:131-134` | `docs/database/relationships.md`<br>`docs/plugins/accounts.md`<br>`docs/plugins/inventories.md`<br>`docs/plugins/manufacturing.md`<br>`docs/plugins/purchases.md` | [VERIFIED] |
| CompanyProperty Cast Implementation | `plugins/webkul/accounts/src/Casts/CompanyProperty.php:9-144` | `docs/database/erds/finance.md`<br>`docs/plugins/accounts.md` | [VERIFIED] |
| Custom Fields Column Manager | `plugins/webkul/fields/src/FieldsColumnManager.php:9-145` | `docs/plugins/fields.md` | [VERIFIED] |
| Model Custom Fields Trait | `plugins/webkul/fields/src/Traits/HasCustomFields.php:8-89` | `docs/plugins/fields.md` | [VERIFIED] |
| Filament Custom Fields Trait | `plugins/webkul/fields/src/Filament/Traits/HasCustomFields.php:10-76` | `docs/plugins/fields.md` | [VERIFIED] |
| Static Analysis Limitations | `docs/database/relationships.md:89-98` | `docs/architecture/overview.md` | [VERIFIED] |
