# Localization & Translation Standards Rule

## Authority & Compliance

All Filament panels, resources, schemas, tables, infolists, actions, filters, and language dictionaries in Aureus ERP must maintain 100% localization compliance. English hardcoded strings or missing labels in UI components are strictly prohibited.

## Mandatory Invariants

### 1. Explicit Labels on All UI Components
Every Filament component must have an explicit namespaced translation label via `->label(__('plugin::...'))` or explicitly call `->hiddenLabel()` if visually concealed:
- **Infolists**: `TextEntry`, `IconEntry`, `ImageEntry`, `ColorEntry`, `RepeatableEntry`, `InfolistTableColumn`.
- **Tables**: `TextColumn`, `IconColumn`, `ImageColumn`, `ColorColumn`, `ToggleColumn`, `SelectColumn`, `CheckboxColumn`.
- **Filters**: `SelectFilter`, `Filter`, `TernaryFilter`.
- **Groups**: `Tables\Grouping\Group`.

Never rely on Filament's default headline title-casing of attribute names (e.g. `TextEntry::make('qty_delivered')` without a label), as it will render un-translated English strings in non-English locales.

### 2. No Hardcoded Strings in Structural Components
- **Sections & Tabs**: Use `Section::make(__('plugin::...'))` and `Tab::make(__('plugin::...'))`. Never use literal English strings like `Section::make('General Information')` or `Section::make('Sales')`.
- **Placeholders & Tooltips**: Always localize user-visible tooltips and placeholders where appropriate.

### 3. No Chained Duplicate Labels
Never chain multiple `->label()` calls on the same component (e.g. `->label(__('Medium'))->label(__('sales::...'))`). Remove non-namespaced redundant calls and keep only the canonical namespaced key.

### 4. 5-Locale Parity & No Untranslated English in Arabic
- Any new or updated translation key must be present in all 5 supported locales:
  - Arabic (`ar`)
  - English (`en`)
  - Spanish (`es`)
  - French (`fr`)
  - Brazilian Portuguese (`pt_BR`)
- In Arabic files (`resources/lang/ar/`), never leave raw English words or phrases as the translated value. Technical acronyms (e.g. `ID`, `SKU`, `PDF`, `POS`) and template placeholders (`:record_name`) are permitted.

### 5. Unified Arabic Business Glossary
To maintain consistency across all 30 local plugins, adhere strictly to the platform's canonical terminology:
- **Customer**: العميل (not "الزبون")
- **Vendor / Supplier**: المورد (not "البائع")
- **Vendor Bill**: فاتورة الشراء / فاتورة المورد
- **Customer Invoice**: الفاتورة / فاتورة البيع
- **Quotation**: عرض السعر (not "عرض أسعار" or "اقتباس")
- **Responsible**: المسؤول
- **Untaxed Amount**: المبلغ قبل الضريبة
- **Tax / Taxes**: الضريبة / الضرائب
- **Status / State**: الحالة
- **Delivery / Shipping**: التسليم / الشحن

## Verification & Testing Scope

### Tier 1: Routine Translation Modifications (Plugin Manager Consistency Engine)
Whenever any translation file is created or modified (`resources/lang/**` or `lang/**`):
Execute the translation parity and consistency check from `plugin-manager`:
```bash
php artisan translations:check --plugin=<plugin_name> --details
```
This ensures 5-locale parity (`ar`, `en`, `es`, `fr`, `pt_BR`), structural consistency, matching placeholders, and key ordering against English.

### Tier 2: New Plugin Authoring & Upstream Synchronization (Full UI Component Audit)
The comprehensive UI localization auditor (`scripts/audit-translations.php`) scans every Filament schema, table, infolist, and action for missing labels and hardcoded strings. To avoid developer friction, this full audit is strictly reserved for:
1. **Authoring a new plugin package** under `plugins/webkul/<new-plugin>`.
2. **Synchronizing with upstream** whenever a new plugin package is merged or introduced.

When either of those two events occurs, run:
```bash
php scripts/audit-translations.php --plugin=<new_plugin> -v
```
To verify the entire platform during full release audits:
```bash
php scripts/audit-translations.php --check
```
