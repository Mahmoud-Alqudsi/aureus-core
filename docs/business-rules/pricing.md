---
status: verified
source_of_truth: source-code
last_verified: 2026-09-21
scope: plugins/webkul/products, plugins/webkul/sales, plugins/webkul/support
confidence: high
---

# Multi-Tier Pricing & Price List Resolution Business Rules

## 1. Scope

### What this document covers
This document formalizes the calculation rules, algorithms, precedence hierarchies, recursion guards, precision behaviors, and cross-domain dependencies governing dynamic price resolution in Aureus ERP. The analysis is grounded in `plugins/webkul/products` (specifically `PriceListResolver`), `plugins/webkul/sales`, and foundational currency/UOM services in `plugins/webkul/support`.

Specifically, this document formalizes:
1. Multi-tier price list structures (`PriceList` and `PriceRuleItem`).
2. Resolution cascade precedence (Variant → Template → Category hierarchy → Global).
3. Rule applicability criteria (minimum quantity breaks, active validity date windows).
4. Price computation types (`FIXED`, `PERCENTAGE`, `FORMULA`).
5. Advanced formula mechanics (markups, surcharges, rounding, and min/max margin limits).
6. Base price resolution and recursion depth guards (`MAX_BASE_DEPTH = 5`).
7. Customer and transactional price list defaulting and dynamic re-evaluation upon switching.
8. Multi-currency price conversion and foreign exchange rate application.
9. Feature flag controls (`enable_price_lists` in `ProductSettings`).

### What it intentionally does not cover
- **Quotation/Order Lifecycle**: State machine transitions (`draft`, `sent`, `sale`, `cancel`) are documented in `docs/business-rules/sales.md` and `docs/workflows/sales.md`.
- **Accounting & Tax Compounding**: Tax calculation engines (`Tax::computeAll()`) are documented in `docs/business-rules/accounting.md`.
- **Supplier Purchasing Pricelists**: Vendor procurement pricing tables (`ProductSupplier`) are documented in `docs/business-rules/purchasing.md`.

---

## 2. Core Pricing Architecture

### 2.1 Feature Flag Control
[VERIFIED]
Multi-tier price list evaluation is governed by the setting:
- **Setting**: `ProductSettings::enable_price_lists` (group: `products_product`, default: `false`).
- **Migration**: `2026_09_15_000300_add_enable_price_lists_to_products_product_settings.php`.
- **UI Gating**: When disabled, the `PriceListResource` navigation item in `Sales` (`Webkul\Sale\Filament\Clusters\Products\Resources\PriceListResource`) is hidden.

### 2.2 Data Model Structure
[VERIFIED]
Multi-tier pricing consists of a header and line-level rule items:
- **`PriceList` (`products_product_price_lists`)**:
  - `name`: Human-readable identifier.
  - `currency_id`: Target currency for all rules in this list.
  - `company_id`: Optional multi-tenant isolation (`BelongsToCompany`).
  - `is_active`: Boolean status flag.
  - `sort`: Sequence display ordering.
- **`PriceRuleItem` (`products_price_rule_items`)**:
  - `price_list_id`: Foreign key referencing parent `PriceList`.
  - `apply_to`: Scope target (`PriceRuleApplyTo`: `all`, `category`, `product`, `variant`).
  - `product_id`: Target product template or specific variant.
  - `category_id`: Target category (matches products in category or its subcategories).
  - `min_quantity`: Quantity threshold required to trigger rule (normalized to product UOM).
  - `starts_at` & `ends_at`: Date validity window.
  - `base`: Base price source (`PriceRuleBase`: `list_price`, `standard_price`, `pricelist`).
  - `base_price_list_id`: Referenced base price list when `base = 'pricelist'`.
  - `type`: Calculation method (`PriceRuleType`: `fixed`, `percentage`, `formula`).
  - `fixed_price`: Static monetary price when `type = 'fixed'`.
  - `percent_price`: Percentage discount when `type = 'percentage'`.
  - Formula modifiers: `price_markup`, `price_surcharge`, `price_discount`, `price_round`, `price_min_margin`, `price_max_margin`.

---

## 3. Resolution Cascade & Rule Matching

### 3.1 Precedence Hierarchy
[VERIFIED]
When `PriceListResolver::resolve()` evaluates a product price, it iterates through rule levels in strict specificity order:

```text
1. Specific Variant Rule    (product_id = variant.id)
       ↓ (if no match)
2. Product Template Rule    (product_id = template.id)
       ↓ (if no match)
3. Category Hierarchy Rule  (category_id IN [category.id, parent_category.id, ...])
       ↓ (if no match)
4. Global Rule              (apply_to = 'all')
       ↓ (if no match)
5. Standard List Price Fallback
```

### 3.2 Filtering & Tie-Breaking Criteria
Within each level of the hierarchy, candidates are filtered and sorted:
1. **Quantity Threshold**: `min_quantity <= quantityInProductUom`.
2. **Date Window**: `(starts_at IS NULL OR starts_at <= evaluation_date) AND (ends_at IS NULL OR ends_at >= evaluation_date)`.
3. **Sort Order**: Sorted by `min_quantity DESC` so that the highest applicable quantity break takes priority.

### 3.3 Quantity Normalization
[VERIFIED]
Requested quantities are converted into the product's primary reference UOM before evaluation:
$$\text{quantityInProductUom} = \text{UOM::computeQuantity}(\text{quantity}, \text{targetUom} \to \text{product.uom})$$

---

## 4. Calculation Rules by Type

Once a rule is matched, the price is computed according to `PriceRuleType`:

### 4.1 Fixed Price (`PriceRuleType::FIXED`)
[VERIFIED]
The unit price is replaced directly with the static amount:
$$\text{Price} = \text{rule.fixed\_price}$$
If the currency of the price list differs from the target transaction currency, currency conversion is applied.

### 4.2 Percentage Discount (`PriceRuleType::PERCENTAGE`)
[VERIFIED]
A percentage discount is deducted from the resolved base price:
$$\text{Price} = \text{basePrice} \times \left(1 - \frac{\text{rule.percent\_price}}{100}\right)$$

### 4.3 Advanced Formula (`PriceRuleType::FORMULA`)
[VERIFIED]
Formula computation supports comprehensive commercial margins, discounts, surcharges, and rounding:

1. **Calculate Margin / Discount Factor**:
   $$\text{WorkingPrice} = \text{basePrice} \times \left(1 - \frac{\text{rule.price\_discount}}{100}\right)$$

2. **Apply Surcharge**:
   $$\text{WorkingPrice} = \text{WorkingPrice} + \text{rule.price\_surcharge}$$

3. **Apply Rounding (`price_round`)**:
   If `price_round > 0`:
   $$\text{WorkingPrice} = \text{round}\left(\frac{\text{WorkingPrice}}{\text{rule.price\_round}}\right) \times \text{rule.price\_round}$$

4. **Enforce Minimum Margin (`price_min_margin`)**:
   If `price_min_margin > 0` and $(\text{WorkingPrice} - \text{basePrice}) < \text{price_min_margin}$:
   $$\text{WorkingPrice} = \text{basePrice} + \text{rule.price\_min\_margin}$$

5. **Enforce Maximum Margin (`price_max_margin`)**:
   If `price_max_margin > 0` and $(\text{WorkingPrice} - \text{basePrice}) > \text{price_max_margin}$:
   $$\text{WorkingPrice} = \text{basePrice} + \text{rule.price\_max\_margin}$$

$$\text{FinalPrice} = \max(0, \text{WorkingPrice})$$

---

## 5. Base Price & Recursion Architecture

### 5.1 Base Price Sources (`PriceRuleBase`)
[VERIFIED]
- **`LIST_PRICE`**: Uses the standard selling price from the product catalogue (`product.price`).
- **`STANDARD_PRICE`**: Uses the product cost price (`product.cost` or supplier purchase baseline).
- **`PRICELIST`**: Recursively resolves the price against `rule.basePriceList`.

### 5.2 Recursion Depth & Cycle Guards
[VERIFIED]
To prevent infinite recursion when price lists reference each other:
- **Maximum Depth**: Capped at `MAX_BASE_DEPTH = 5`. If depth exceeds 5, `PriceListResolver` terminates recursion and falls back to standard `product.price`.
- **Visited List**: Tracks visited price list IDs in `array $visited`. If `in_array($basePriceList->id, $visited)`, recursion terminates immediately.

---

## 6. Sales Integration & Transactional Invariants

### 6.1 Customer Defaulting
[VERIFIED]
- Selecting a customer (`partner_id`) on a quotation or sales order automatically defaults `price_list_id` to `$partner->price_list_id`.
- If the customer has no assigned price list, `price_list_id` remains `null`, falling back to standard list pricing.

### 6.2 Dynamic Line Re-Evaluation
[VERIFIED]
- When `price_list_id` is changed on a draft quotation, all existing order lines are re-evaluated through `PriceListResolver`.
- The order's `currency_id` is synchronized with the selected price list currency.

### 6.3 Fallback Invariant
[VERIFIED]
- If `price_list_id` is null or if no rule in the price list matches the item/quantity/date criteria, the price defaults to standard product sales price converted to the order currency.

---

## 7. Evidence Index

| ID | File | Description |
| :--- | :--- | :--- |
| E-PRC-001 | `plugins/webkul/products/src/Services/PriceListResolver.php` | Resolution engine, cascade logic, formula calculations, recursion limits |
| E-PRC-002 | `plugins/webkul/products/src/Models/PriceList.php` | Price list header model and relationships |
| E-PRC-003 | `plugins/webkul/products/src/Models/PriceRuleItem.php` | Price rule line item model, enums, casts, fillable attributes |
| E-PRC-004 | `plugins/webkul/products/src/Support/ResolvedPrice.php` | Resolved price DTO (`price`, `rule`, `basePrice`) |
| E-PRC-005 | `plugins/webkul/products/database/migrations/2026_09_15_000000_consolidate_products_price_rules_into_price_lists_table.php` | Table drop and price list consolidation schema |
| E-PRC-006 | `plugins/webkul/sales/src/Filament/Clusters/Orders/Resources/QuotationResource/Schemas/QuotationForm.php` | Customer defaulting, price list selection, dynamic line item recalculation |
| E-PRC-007 | `plugins/webkul/sales/tests/Feature/Workflows/OrderPriceListTest.php` | Workflow test coverage for price list defaulting and evaluation |
| E-PRC-008 | `plugins/webkul/products/tests/Feature/Workflows/PriceListResolverTest.php` | Unit and workflow tests for PriceListResolver |
