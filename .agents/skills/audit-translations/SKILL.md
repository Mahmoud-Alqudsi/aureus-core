---
name: audit-translations
description: >-
  Audit, detect, and remediate translation gaps, missing labels in Filament tables and infolists,
  and duplicate label calls across Aureus ERP plugins. Use whenever creating or modifying Filament
  resources, schemas, tables, infolists, or language files.
---

# Translation & Localization Audit Runbook

This skill guides Antigravity agents in identifying and eliminating localization gaps, missing component labels, hardcoded English strings, and duplicate label declarations across all Aureus ERP plugins (`plugins/webkul/*`).

---

## Localization Standards & Invariants

1. **Explicit Component Labels**: All entries, columns, filters, and groups in Filament must define explicit namespaced labels via `->label(__('plugin::...'))` or explicitly invoke `->hiddenLabel()`.
2. **Structural Components**: `Section::make()` and `Tab::make()` must take translated titles `__('plugin::...')`.
3. **No Chained Duplicate Labels**: Dead-code chains like `->label(__('Medium'))->label(__('sales::...'))` must be pruned down to the single canonical namespaced call.
4. **5-Locale Parity**: All translation keys must exist across `ar`, `en`, `es`, `fr`, and `pt_BR`.
5. **Arabic Glossary Compliance**: Terminology must follow the platform's unified Arabic glossary (e.g. `Customer` = العميل, `Vendor` = المورد, `Bill` = فاتورة الشراء, `Quotation` = عرض السعر).

---

## Workflow Steps

### Step 1: Run the Localization Audit

To audit a specific plugin (recommended during scoped changes):
```bash
php scripts/audit-translations.php --plugin=<plugin_name> -v
```

To audit multiple plugins or the entire platform:
```bash
php scripts/audit-translations.php --plugin=sales,accounts,crm
php scripts/audit-translations.php --check
```

Review the output metrics:
- **Miss Label**: Entries or columns lacking `->label()` and `hiddenLabel()`.
- **Hardcoded**: Elements or sections using raw English strings instead of `__('...')`.
- **Chained**: Redundant consecutive `->label()` calls.
- **Lang Gaps**: Missing keys across locales or untranslated English strings in Arabic files.

### Step 2: Auto-Fix Chained Duplicate Labels

If the audit reports chained duplicate labels, run the automatic fixer:
```bash
php scripts/audit-translations.php --plugin=<plugin_name> --fix -v
```
The fixer will safely remove the non-namespaced redundant label and retain the namespaced key.

### Step 3: Localize Missing Component & Infolist Labels

1. For each reported missing entry or column in the plugin's `Schemas/` or `Tables/`:
   - Trace the component name (e.g. `TextEntry::make('payment_state')`).
   - Check the plugin's language files: `plugins/webkul/<plugin>/resources/lang/en/filament/...`.
   - Add the appropriate key if missing across all 5 locales (`ar`, `en`, `es`, `fr`, `pt_BR`).
   - Add `->label(__('plugin::...'))` to the component declaration.

2. For sub-tables and repeater entries inside `RepeatableEntry` or `InfolistTableColumn`:
   - Ensure every child entry specifies `->label(__('...'))`.

### Step 4: Localize Hardcoded Sections & Tabs

Replace raw string literals in sections and tabs:
```php
// Before
Section::make('General Information')

// After
Section::make(__('plugin::filament/clusters/.../resource.infolist.sections.general.title'))
```
Add the corresponding titles in all 5 locale dictionaries.

### Step 5: Verify & Format

Confirm that zero issues remain for the affected plugin(s):
```bash
php scripts/audit-translations.php --plugin=<plugin_name> --check
```

Format modified files using Pint:
```bash
vendor/bin/pint --dirty
```

Run affected automated tests:
```bash
php artisan test --filter=<ResourceOrPluginTest>
```
