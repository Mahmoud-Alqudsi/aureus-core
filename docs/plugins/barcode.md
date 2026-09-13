---
status: verified
source_of_truth: source-code
last_verified: 2026-08-31
scope: plugins/webkul/barcode
confidence: high
---

# Plugin: Barcode (`barcode`)

## Status
[VERIFIED]
Active Optional Module. Registered in `bootstrap/providers.php:42` as `Webkul\Barcode\BarcodeServiceProvider::class`.

## Core/Optional
[VERIFIED]
**Optional Plugin**. Configured as an optional domain module without declaring `$package->isCore()` (`plugins/webkul/barcode/src/BarcodeServiceProvider.php:25-39`). Execution and Filament UI registration are gated at runtime via `Package::isPluginInstalled('barcode')` (`plugins/webkul/barcode/src/BarcodePlugin.php:23`).

## Enabled/Disabled
[VERIFIED]
**Conditionally Enabled (Database-Gated)**. `BarcodeServiceProvider` registers the package and binds `BarcodePlugin` to Filament panels via `Panel::configureUsing()`, but Filament pages are discovered and registered only when `Package::isPluginInstalled('barcode')` returns `true` (`plugins/webkul/barcode/src/BarcodePlugin.php:23-33`). Standalone web and mobile routes defined in `plugins/webkul/barcode/routes/web.php` are registered at boot.

## Purpose
[VERIFIED]
The `barcode` module functions as a mobile-first, handheld camera/scanner workflow UI and execution layer for warehouse operations in Aureus ERP. It operates as a specialized UI/Livewire orchestration wrapper over the `inventories` domain and `products` master data:

1. **Mobile-First Handheld Warehouse Scanning Interface**:
   - Provides a dedicated, responsive full-screen web and mobile user experience tailored for smartphone cameras and handheld barcode scanning terminals.
   - Embeds client-side camera scanning using the bundled `html5-qrcode` JavaScript engine (`plugins/webkul/barcode/resources/dist/html5-qrcode.min.js`) with automatic autofocus, back-camera targeting (`facingMode: 'environment'`), and viewport framing.

2. **Dual Execution Architecture (Web & NativePHP Mobile Shell)**:
   - Operates both as a web application (`/admin/barcode`) and as an embedded NativePHP mobile shell (iOS and Android).
   - Integrates with `Webkul\NativephpRemote` middleware (`PersistNativeShell`, `RenderHostedNativeUi`) to dynamically render native top bars, sidebar navigations, hardware haptic vibration feedback, and native toast notifications.
   - Ships a native manifest (`plugins/webkul/barcode/nativephp.json`) declaring Android `android.permission.CAMERA` and iOS `NSCameraUsageDescription`.

3. **Zero-Schema UI/Proxy Architecture**:
   - Owns **0 database tables and 0 migrations**.
   - Resolves scanned barcodes directly against existing physical barcode, reference, and name columns on foreign tables:
     - `products_products.barcode` & `reference`
     - `products_packagings.barcode`
     - `inventories_locations.barcode`, `name`, & `full_name`
     - `inventories_lots.name` & `reference`
     - `inventories_packages.name`
     - `inventories_operations.name`, `origin`, & `id`

4. **Comprehensive Warehouse Transfer Execution Flow**:
   - **Operational Dashboard (`Dashboard`)**: Summarizes warehouse operations grouped by operation type and warehouse, displaying real-time badges of waiting/pending transfers.
   - **Transfer Discovery & Search (`Transfers`)**: Enables barcode-scanning or textual searching for specific transfers by transfer number, source document origin, customer/vendor partner, product, packaging, lot/serial, or package.
   - **Interactive Transfer Execution (`Operation`)**: Scans items into transfer move lines, adjusts counted/picked quantities, modifies source/destination locations and destination packages, assigns/creates lots or serial numbers dynamically, calculates backorders (`CreateBackorder::ASK`), and validates transfers via `Inventory::completeTransfer()` or cancels via `Inventory::cancelTransfer()`.

5. **Physical Inventory Adjustments & Cycle Counting Flow (`Adjustments`)**:
   - Provides rapid physical stock counting by location, product, or lot/serial number.
   - Supports quick-count matching, step increments/decrements (+1, -1, full on-hand count), direct numeric count editing, count clearing, and direct application to physical stock balances in `ProductQuantity`.

6. **Filament Admin Panel Launcher Integration (`LaunchBarcode`)**:
   - Registers a launcher page in the Filament Admin panel under navigation group `NavigationGroup::Barcode` (`barcode`), which immediately redirects authenticated users into the full-screen barcode application.

## Service Provider
[VERIFIED]
- **Class**: `Webkul\Barcode\BarcodeServiceProvider` (`plugins/webkul/barcode/src/BarcodeServiceProvider.php:19`)
- **Inheritance**: Extends `Webkul\PluginManager\PackageServiceProvider`
- **Registration & Configuration**:
  - `configureCustomPackage(Package $package)`:
    - Sets package name: `barcode` (`BarcodeServiceProvider::$name = 'barcode'`).
    - Sets view namespace: `barcode` (`BarcodeServiceProvider::$viewNamespace = 'barcode'`).
    - Registers Blade views: `hasViews()`.
    - Registers translations: `hasTranslations()`.
    - Registers web routes: `hasRoute('web')`.
    - Declares runtime plugin dependency on `inventories`: `hasDependencies(['inventories'])` (`plugins/webkul/barcode/src/BarcodeServiceProvider.php:31-33`).
    - Configures CLI installer: `hasInstallCommand(fn (InstallCommand $command) => $command->installDependencies())`.
    - Configures CLI uninstaller: `hasUninstallCommand(fn (UninstallCommand $command) => null)`.
    - Sets package icon: `icon('barcode')`.
    - Does **not** declare `$package->isCore()` (confirming optional plugin status).
    - Does **not** register database migrations or seeders.
  - `packageBooted()`:
    - Registers 4 Livewire components:
      - `barcode-dashboard` => `Webkul\Barcode\Livewire\Dashboard::class`
      - `barcode-adjustments` => `Webkul\Barcode\Livewire\Adjustments::class`
      - `barcode-transfers` => `Webkul\Barcode\Livewire\Transfers::class`
      - `barcode-operation` => `Webkul\Barcode\Livewire\Operation::class`
    - Registers Filament assets under the `barcode` package identifier:
      - `Css::make('barcode', __DIR__.'/../resources/dist/barcode.css')`
      - `Js::make('barcode', __DIR__.'/../resources/dist/barcode.js')`
      - `Js::make('html5-qrcode', __DIR__.'/../resources/dist/html5-qrcode.min.js')`
  - `packageRegistered()`:
    - Binds `BarcodePlugin::make()` to Filament panels via `Panel::configureUsing()` (`plugins/webkul/barcode/src/BarcodeServiceProvider.php:57-59`).

## Filament Plugin Class
[VERIFIED]
- **Class**: `Webkul\Barcode\BarcodePlugin` (`plugins/webkul/barcode/src/BarcodePlugin.php:9`)
- **Implements**: `Filament\Contracts\Plugin`
- **Plugin ID**: `barcode` (`getId(): string`)
- **Singleton Factory**: `BarcodePlugin::make()` resolves `app(static::class)`.
- **Panel Registration Logic**:
  - Checks installation state: `Package::isPluginInstalled($this->getId())`; returns early if uninstalled (`plugins/webkul/barcode/src/BarcodePlugin.php:23-25`).
  - Restricts registration exclusively to the `admin` panel (`$panel->when($panel->getId() == 'admin', ...)`):
    - Discovers Pages in `plugins/webkul/barcode/src/Filament/Pages` (`Webkul\Barcode\Filament\Pages`).
- **Boot**: Empty method stub (`boot(Panel $panel): void`).

## Composer Dependencies
[VERIFIED]
Defined in `plugins/webkul/barcode/composer.json`:
- **Package Name**: `webkul/barcode`
- **Description**: `Barcode operations app for inventory and manufacturing`
- **Package Type**: `nativephp-plugin`
- **Autoload PSR-4**:
  - `Webkul\Barcode\`: `src/`
- **Autoload-dev PSR-4**:
  - `Webkul\Barcode\Tests\`: `tests/`
- **Extra Config**:
  - `nativephp.manifest`: `nativephp.json`
  - `laravel.providers`: `Webkul\Barcode\BarcodeServiceProvider`
- **Require Dependencies**: None declared in `composer.json` (relies on root workspace Composer packages and required plugin modules).

## Runtime Plugin Dependencies
[VERIFIED]
- **Declared Runtime Dependencies (`Package::hasDependencies([...])`)**:
  - `inventories` (`plugins/webkul/barcode/src/BarcodeServiceProvider.php:31-33`): Required for core warehouse models (`Operation`, `OperationType`, `Move`, `MoveLine`, `Location`, `Lot`, `Package`, `Packaging`, `ProductQuantity`), enums (`OperationState`, `LocationType`, `ProductTracking`, `CreateBackorder`), and operational execution service (`Inventory` facade).
- **Implicit Core Plugin Dependencies**:
  - `products`: Product master catalog (`Webkul\Product\Models\Product`), barcodes, internal references, stock scoping methods (`resolveStockScopes()`), and units of measure.
  - `security`: Authentication check (`Filament::auth()`), user authorization (`$user->canAccessPanel()`), and session management.
  - `support`: Navigation enum `NavigationGroup::Barcode`, cross-database SQL dialect helper `db_dialect()` (`caseInsensitiveEquals()`), and layout helpers.
  - `plugin-manager`: Package service provider infrastructure and `Package::isPluginInstalled()`.
- **External Package Dependencies**:
  - `nativephp-remote` / `Webkul\NativephpRemote`: Provides middleware `PersistNativeShell`, `RenderHostedNativeUi`, and base class `NativeRemote` for native mobile shell integration.

## Directory Structure
[VERIFIED]
```text
plugins/webkul/barcode/
├── composer.json
├── nativephp.json
├── README.md
├── config/
│   └── filament-shield.php
├── resources/
│   ├── dist/
│   │   ├── barcode.css
│   │   ├── barcode.js
│   │   └── html5-qrcode.min.js
│   ├── lang/
│   │   ├── ar/
│   │   │   └── app.php
│   │   ├── en/
│   │   │   └── app.php
│   │   ├── es/
│   │   │   └── app.php
│   │   ├── fr/
│   │   │   └── app.php
│   │   └── pt_BR/
│   │       └── app.php
│   └── views/
│       ├── components/
│       │   ├── header/
│       │   │   ├── native.blade.php
│       │   │   └── web.blade.php
│       │   └── sidebar/
│       │       ├── native.blade.php
│       │       └── web.blade.php
│       ├── layouts/
│       │   └── app.blade.php
│       └── livewire/
│           ├── adjustments.blade.php
│           ├── dashboard.blade.php
│           ├── operation.blade.php
│           └── transfers.blade.php
├── routes/
│   └── web.php
└── src/
    ├── BarcodePlugin.php
    ├── BarcodeServiceProvider.php
    ├── Filament/
    │   └── Pages/
    │       └── LaunchBarcode.php
    ├── Http/
    │   ├── Middleware/
    │   │   └── Authenticate.php
    │   └── Responses/
    │       └── LoginResponse.php
    ├── Livewire/
    │   ├── Adjustments.php
    │   ├── Auth/
    │   │   └── Login.php
    │   ├── Dashboard.php
    │   ├── Operation.php
    │   └── Transfers.php
    └── Support/
        ├── Navigation.php
        └── NativeApp.php
```

## Models
[NOT APPLICABLE]
- [VERIFIED] The `barcode` plugin defines **zero Eloquent models** of its own (no `src/Models/` directory exists).
- It consumes and orchestrates existing models from upstream plugins:
  - `Webkul\Inventory\Models\Operation`: Transfer header and state lifecycle.
  - `Webkul\Inventory\Models\OperationType`: Warehouse operation types (receipts, delivery orders, internal transfers).
  - `Webkul\Inventory\Models\Move`: Stock move demand records.
  - `Webkul\Inventory\Models\MoveLine`: Individual stock move lines, lot tracking, picked quantities, and location routing.
  - `Webkul\Inventory\Models\Location`: Internal and scrap warehouse locations with barcodes.
  - `Webkul\Inventory\Models\Lot`: Lot and serial tracking master records.
  - `Webkul\Inventory\Models\Package`: Physical container packages.
  - `Webkul\Inventory\Models\Packaging`: Product packagings with distinct barcodes.
  - `Webkul\Inventory\Models\ProductQuantity`: Physical stock on hand per location, lot, and package.
  - `Webkul\Product\Models\Product`: Product definitions, tracking types, references, barcodes, and stock scope resolvers.

## Database
[NOT APPLICABLE]
- [VERIFIED] Verified from source code and migrations: contains **0 database tables, 0 migrations, 0 seeders, and 0 factories** (no `database/` directory exists).
- Cross-references verified in `docs/database/erds/operations.md:40, 505-517, 1124`.
- Barcode lookups operate entirely against physical columns owned by other modules:
  - `products_products.barcode` & `products_products.reference` (owned by `products`)
  - `products_packagings.barcode` (owned by `products`)
  - `inventories_locations.barcode`, `inventories_locations.name`, `inventories_locations.full_name` (owned by `inventories`)
  - `inventories_lots.name` & `inventories_lots.reference` (owned by `inventories`)
  - `inventories_packages.name` (owned by `inventories`)
  - `inventories_operations.name`, `inventories_operations.origin`, `inventories_operations.id` (owned by `inventories`)

## Filament Resources / Pages / Widgets / Clusters
[VERIFIED]
- **Filament Resources**: None (`0`).
- **Filament Clusters**: None (`0`).
- **Filament Widgets**: None (`0`).
- **Filament Pages**: 1 page:
  - `Webkul\Barcode\Filament\Pages\LaunchBarcode` (`plugins/webkul/barcode/src/Filament/Pages/LaunchBarcode.php:9`):
    - **Namespace**: `Webkul\Barcode\Filament\Pages`
    - **Route Slug**: `barcode-app`
    - **Navigation Icon**: `heroicon-o-qr-code`
    - **Navigation Group**: `NavigationGroup::Barcode` (`barcode`)
    - **Navigation Label**: `__('barcode::app.filament.navigation.label')` ("Barcode App")
    - **Traits**: `BezhanSalleh\FilamentShield\Traits\HasPageShield`
    - **Behavior**: Acts as a bridge launcher in the standard Filament sidebar. Upon navigation `mount()`, immediately executes `$this->redirect(route('barcode.dashboard'), navigate: true);` to switch into the dedicated Livewire Barcode mobile web application.

## Panels
[VERIFIED]
- **Admin Panel (`admin`)**:
  - Participates via `LaunchBarcode` page registration in the sidebar.
  - Hosts dedicated barcode routes under `/admin/barcode/*` bound to the admin panel context via `SetUpPanel::class.':admin'`.
- **Customer Panel (`customer`)**:
  - `[NOT APPLICABLE]`. Zero customer portal participation.

## Services & Support Classes
[VERIFIED]
1. **`Webkul\Barcode\Support\Navigation`** (`plugins/webkul/barcode/src/Support/Navigation.php:5`):
   - Generates structured navigation items for both web and native sidebars (`Navigation::items()`):
     - `inventory-operations`: Links to `barcode.dashboard`, active on dashboard, transfers, and operation screens (icon `heroicon-m-arrows-right-left` / native `swap_horiz`).
     - `manufacturing-orders`: Future extension placeholder marked as `'disabled' => true` (icon `heroicon-m-wrench-screwdriver` / native `build`).
     - `inventory-adjustments`: Links to `barcode.adjustments`, active on adjustments screen (icon `heroicon-m-clipboard-document-list` / native `inventory_2`).

2. **`Webkul\Barcode\Support\NativeApp`** (`plugins/webkul/barcode/src/Support/NativeApp.php:7`):
   - Extends `Webkul\NativephpRemote\Support\NativeRemote`.
   - Manages native mobile wrapper integration:
     - `startUrl()`: Returns `/admin/barcode?nativephp=1`.
     - `headerTitle()` / `headerSubtitle()`: Resolves dynamic titles and subtitles based on the current named route and route parameters (`operationType`, `operation`).
     - `shouldShowScanAction()`: Returns `true` on adjustments, transfers, and operation views.
     - `scanActionUrl()`: Generates `#scan-barcode` deep-link action URLs for native top-bar triggers.

3. **`Webkul\Barcode\Http\Responses\LoginResponse`** (`plugins/webkul/barcode/src/Http/Responses/LoginResponse.php:9`):
   - Implements `Filament\Auth\Http\Responses\Contracts\LoginResponse`.
   - Directs successful logins to `redirect()->intended(route('barcode.dashboard'))`.

4. **`Webkul\Barcode\Http\Middleware\Authenticate`** (`plugins/webkul/barcode/src/Http/Middleware/Authenticate.php:11`):
   - Intercepts requests to `/admin/barcode/*`.
   - Checks `Filament::auth()->check()`.
   - Verifies panel authorization via `$user->canAccessPanel(Filament::getCurrentOrDefaultPanel())`.
   - Invalidates session and redirects unauthenticated or unauthorized users to `route('barcode.login')`.

## Livewire Components (Scanner Architecture)
[VERIFIED]
The barcode plugin's runtime execution is structured across 5 Livewire components:

### 1. `Webkul\Barcode\Livewire\Dashboard` (`plugins/webkul/barcode/src/Livewire/Dashboard.php:12`)
- **Route**: `GET /admin/barcode` (`barcode.dashboard`)
- **View**: `barcode::livewire.dashboard`
- **Layout**: `barcode::layouts.app`
- **Functionality**:
  - Queries all `OperationType` records with their associated `warehouse:id,name`.
  - Executes a subquery on `inventories_operations` calculating `waiting_count` of active transfers (excluding `DRAFT`, `DONE`, `CANCELED`).
  - Groups operation types by name, type, and warehouse to present consolidated operational cards.
  - Clicking any operation type card navigates to `route('barcode.transfers', $operationType)`.

### 2. `Webkul\Barcode\Livewire\Transfers` (`plugins/webkul/barcode/src/Livewire/Transfers.php:18`)
- **Route**: `GET /admin/barcode/operations/{operationType}` (`barcode.transfers`)
- **View**: `barcode::livewire.transfers`
- **Layout**: `barcode::layouts.app`
- **Functionality**:
  - Displays pending/waiting transfers for the specified `OperationType`.
  - Provides a real-time search and barcode scanner input (`search`).
  - **Scanning Resolution Logic (`findMatchingOperations()`)**:
    1. Direct match: Exact match on transfer `name`, `origin`, or primary key `id`.
    2. Product match: Finds product by barcode/reference and locates parent operations via `Move::where('product_id', $product->id)`.
    3. Packaging match: Finds packaging by barcode and locates parent operations via `Move::where('product_packaging_id', $packaging->id)`.
    4. Lot match: Finds lot by name/reference and locates parent operations via `MoveLine::where('lot_id', $lot->id)`.
    5. Package match: Finds package by name and locates parent operations via `MoveLine::where('package_id', $package->id)->orWhere('result_package_id', $package->id)`.
  - If a single matching transfer is identified, automatically redirects to `barcode.operation` with the scanned barcode passed in the query string (`?scan=...`).

### 3. `Webkul\Barcode\Livewire\Operation` (`plugins/webkul/barcode/src/Livewire/Operation.php:26`)
- **Route**: `GET /admin/barcode/operations/{operationType}/transfers/{operation}` (`barcode.operation`)
- **View**: `barcode::livewire.operation`
- **Layout**: `barcode::layouts.app`
- **Functionality**:
  - Validates route integrity (`abort_unless($operation->operation_type_id === $operationType->id, 404)`).
  - **Scan Resolution Engine (`resolveScan()`)**:
    - Scanned barcode matching transfer name confirms the operation header.
    - Scanned product barcode locates matching `MoveLine`, marks demand as counted, and dispatches `barcode-move-line-located` to scroll and highlight the line.
    - Scanned packaging barcode matches product packaging.
    - Scanned lot/serial barcode matches lot on move lines.
    - Scanned package barcode matches source or destination packages.
  - **Counted Quantity Management**:
    - Supports numeric adjustment (`adjustMoveLineQuantity`, `setMoveLineQuantity`, `updateMoveLineQuantity`).
    - Caps counted quantity between `0` and line demand (`qty`).
  - **Move Line Modal Configuration (`editMoveLine`, `confirmMoveLineEdit`)**:
    - Allows changing source stock location from available `ProductQuantity` stock scopes.
    - Allows changing destination location (`Location::withTrashed()`).
    - Allows setting destination result package (`Package::query()`).
    - Allows dynamic lot assignment/creation (`assignEditingMoveLineLot()` creates new `Lot` if non-existent).
  - **Validation & Backorder Engine**:
    - Calculates backorders for incomplete lines (`backorderMoveLines()`).
    - Checks operation type configuration (`shouldAskBackorder()` via `create_backorder === CreateBackorder::ASK`).
    - In `executeAction('validate')`, wraps execution in `DB::transaction()`:
      1. Syncs counted quantities to `MoveLine::$qty`.
      2. Calls `Inventory::completeTransfer($operation->fresh(), $cancelBackOrder)`.
      3. Automatically redirects back to `barcode.transfers` upon successful completion.
    - `executeAction('cancel')` calls `Inventory::cancelTransfer($operation)`.

### 4. `Webkul\Barcode\Livewire\Adjustments` (`plugins/webkul/barcode/src/Livewire/Adjustments.php:15`)
- **Route**: `GET /admin/barcode/inventory-adjustments` (`barcode.adjustments`)
- **View**: `barcode::livewire.adjustments`
- **Layout**: `barcode::layouts.app`
- **Functionality**:
  - Implements physical cycle counting for internal warehouse stock (`LocationType::INTERNAL`, `is_scrap = false`).
  - **Barcode Scanning Filter Resolution (`scan()`)**:
    - Scanned location barcode filters stock to `selectedLocationId`.
    - Scanned product barcode filters stock to `selectedProductId` and auto-locates quantity record.
    - Scanned lot barcode filters stock to `selectedLotId` and auto-locates quantity record.
  - **Count Modification Actions**:
    - `quickCountQuantity(int $quantityId)`: Instantly sets counted quantity equal to current on-hand quantity.
    - `adjustQuantityCount(int $quantityId, float $amount)`: Increments/decrements counted quantity.
    - `editQuantity(int $quantityId)` / `confirmQuantityEdit()`: Full-screen modal with custom keypad buttons (0, -1, +1, +remaining).
    - `clearQuantityCount(int $quantityId)`: Resets `counted_quantity = 0`, `inventory_quantity_set = false`, `inventory_diff_quantity = 0`.
    - `applyQuantityCount(int $quantityId)`: Directly applies count to database:
      ```php
      $quantity->update([
          'quantity'               => (float) $quantity->counted_quantity,
          'counted_quantity'       => 0,
          'inventory_quantity_set' => false,
      ]);
      ```

### 5. `Webkul\Barcode\Livewire\Auth\Login` (`plugins/webkul/barcode/src/Livewire/Auth/Login.php:10`)
- **Route**: `GET /admin/barcode/login` (`barcode.login`)
- **Functionality**: Extends `Filament\Auth\Pages\Login`. Mount checks `Filament::auth()->check()` and redirects to `barcode.dashboard`. Dispatches authentication through `Webkul\Barcode\Http\Responses\LoginResponse`.

## Events
[VERIFIED]
- **PHP Event Classes**: None (`0`).
- **Browser / Livewire Events**:
  - Dispatches `barcode-native-feedback`: Dispatches message, vibration request (`vibrate: true`), and toast duration to the native shell or web layout.
  - Dispatches `barcode-record-located`: Dispatched on quantity match to smoothly scroll the browser viewport to the matched quantity DOM container and auto-focus its input.
  - Dispatches `barcode-move-line-located`: Dispatched on transfer move line match to highlight and focus the specific move line card.
  - Listens for `barcode-native-scan-request`: Triggered when URL hash `#scan-barcode` is detected, activating camera capture.

## Listeners
[NOT APPLICABLE]
- [VERIFIED] 0 PHP event listener classes defined.

## Observers
[NOT APPLICABLE]
- [VERIFIED] 0 Eloquent observer classes defined.

## Policies
[NOT APPLICABLE]
- [VERIFIED] 0 model policy classes defined (0 models).
- Page access control on `LaunchBarcode` is managed via `BezhanSalleh\FilamentShield\Traits\HasPageShield`.
- Livewire routes are protected via `Webkul\Barcode\Http\Middleware\Authenticate`.

## Routes
[VERIFIED]
Defined in `plugins/webkul/barcode/routes/web.php`:

| Method | URI | Name | Middleware | Controller / Component |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/barcode` | `—` | `web`, `PersistNativeShell`, `RenderHostedNativeUi` | Closure (redirects to `barcode.dashboard` if auth, else `barcode.login`) |
| `GET` | `/admin/barcode/login` | `barcode.login` | `web`, `PersistNativeShell`, `RenderHostedNativeUi`, `SetUpPanel:admin` | `Webkul\Barcode\Livewire\Auth\Login` |
| `GET` | `/admin/barcode` | `barcode.dashboard` | `web`, `PersistNativeShell`, `RenderHostedNativeUi`, `SetUpPanel:admin`, `Authenticate` | `Webkul\Barcode\Livewire\Dashboard` |
| `GET` | `/admin/barcode/inventory-adjustments` | `barcode.adjustments` | `web`, `PersistNativeShell`, `RenderHostedNativeUi`, `SetUpPanel:admin`, `Authenticate` | `Webkul\Barcode\Livewire\Adjustments` |
| `GET` | `/admin/barcode/operations/{operationType}` | `barcode.transfers` | `web`, `PersistNativeShell`, `RenderHostedNativeUi`, `SetUpPanel:admin`, `Authenticate` | `Webkul\Barcode\Livewire\Transfers` |
| `GET` | `/admin/barcode/operations/{operationType}/transfers/{operation}` | `barcode.operation` | `web`, `PersistNativeShell`, `RenderHostedNativeUi`, `SetUpPanel:admin`, `Authenticate` | `Webkul\Barcode\Livewire\Operation` |

## Settings
[NOT APPLICABLE]
- [VERIFIED] 0 custom settings classes defined.

## Translations
[VERIFIED]
- **Namespace**: `barcode` (`resources/lang/`)
- **Supported Locales**: English (`en`), Arabic (`ar`), Spanish (`es`), French (`fr`), Brazilian Portuguese (`pt_BR`).
- **Translation File**: `app.php` providing localized strings for:
  - `title`, `navigation`, `auth`, `filament`, `dashboard`, `operation-search`, `transfers`, `adjustments`, `operation`, `scan`, `actions`.

## Tests
[NOT APPLICABLE]
- [VERIFIED] **Zero test files exist** for the `barcode` plugin.
- The directory `plugins/webkul/barcode/tests/` does not exist, and no feature or unit test cases reference `Webkul\Barcode` in the root `tests/` directory.

## Cross-Plugin Relationships
[VERIFIED]
- **`inventories`**:
  - Primary foundation. Barcode reads `Operation`, `OperationType`, `Move`, `MoveLine`, `Location`, `Lot`, `Package`, `Packaging`, and `ProductQuantity`.
  - Executes state mutations directly through `Webkul\Inventory\Facades\Inventory` (`completeTransfer()`, `cancelTransfer()`).
- **`products`**:
  - Resolves product barcodes, references, tracking modes (`ProductTracking::LOT`, `ProductTracking::SERIAL`, `ProductTracking::QTY`), and stock scoping rules (`$product->resolveStockScopes()`).
- **`security`**:
  - Manages warehouse operator authentication via `Filament::auth()`, session validation, and panel accessibility checks.
- **`support`**:
  - Consumes navigation icon mappings from `NavigationGroup::Barcode` (`icon-barcode`).
  - Uses `db_dialect()->caseInsensitiveEquals(...)` for cross-database barcode matching.
- **`plugin-manager`**:
  - Manages package lifecycle and installation checks (`Package::isPluginInstalled('barcode')`).
- **`nativephp-remote`**:
  - Provides native app shell integration and bridge scripts for iOS and Android hardware interaction.

## Data Flow
[VERIFIED]

```text
[ Warehouse Operator / Scanner Terminal ]
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│                    Route / Panel Entry                     │
│  /admin/barcode  ──►  Authenticate Middleware  ──► Panel    │
└──────────────────────────────┬──────────────────────────────┘
                               │
               ┌───────────────┴───────────────┐
               ▼                               ▼
┌──────────────────────────────┐ ┌─────────────────────────────┐
│    Transfers / Operations    │ │    Inventory Adjustments    │
│  (Livewire\Dashboard/        │ │  (Livewire\Adjustments)     │
│   Transfers/Operation)       │ │                             │
└──────────────┬───────────────┘ └─────────────┬───────────────┘
               │                               │
               ▼                               ▼
┌──────────────────────────────┐ ┌─────────────────────────────┐
│   Hardware / Camera Scan     │ │   Hardware / Camera Scan    │
│   (html5-qrcode.min.js)      │ │   (html5-qrcode.min.js)     │
│   Barcode Input:             │ │   Barcode Input:            │
│   - Product barcode/ref      │ │   - Location barcode        │
│   - Lot/Serial name/ref      │ │   - Product barcode/ref     │
│   - Package/Packaging name   │ │   - Lot/Serial name/ref     │
│   - Transfer name/origin/id  │ │                             │
└──────────────┬───────────────┘ └─────────────┬───────────────┘
               │                               │
               ▼                               ▼
┌──────────────────────────────┐ ┌─────────────────────────────┐
│   Quantity / Line Editing    │ │   Count Editing & Step Key  │
│   - Set picked quantity      │ │   - 0 / -1 / +1 / +Full     │
│   - Assign/Create Lot        │ │   - Direct numeric count    │
│   - Set source/dest location │ │   - Clear count             │
│   - Set destination package  │ │                             │
└──────────────┬───────────────┘ └─────────────┬───────────────┘
               │                               │
               ▼                               ▼
┌──────────────────────────────┐ ┌─────────────────────────────┐
│  Validation & Backorders     │ │   Commit Count to Stock     │
│  - Sync MoveLine::$qty       │ │   ProductQuantity::update([ │
│  - Prompt Backorder (ASK)    │ │     quantity = counted,     │
│  - Inventory::completeTransfer││     counted_quantity = 0,   │
│  - Inventory::cancelTransfer │ │     inventory_quantity_set=0│
│                              │ │   ])                        │
└──────────────────────────────┘ └─────────────────────────────┘
```

## Business Rules
[VERIFIED]
1. **Transfer State Restrictions**:
   - Only transfers in active execution states (excluding `DRAFT`, `DONE`, `CANCELED`) are listed on the dashboard and transfer selection screens (`plugins/webkul/barcode/src/Livewire/Dashboard.php:32-36`, `plugins/webkul/barcode/src/Livewire/Transfers.php:110-114`).
   - Transfer validation action buttons are disabled once a transfer is in `DONE` or `CANCELED` state (`plugins/webkul/barcode/src/Livewire/Operation.php:381-383`).
2. **Move Line Quantity Capping**:
   - Counted quantity on a move line is constrained between `0` and the required move line quantity (`min((float) $moveLine->qty, max(0, $counted))`). Over-picking beyond the move line requirement is prevented in the UI (`plugins/webkul/barcode/src/Livewire/Operation.php:106, 119, 132`).
3. **Dynamic Lot Creation & Company Scoping**:
   - When editing a tracked product's move line (`ProductTracking::LOT` or `ProductTracking::SERIAL`), if the entered lot name does not exist in the database, `assignEditingMoveLineLot()` dynamically creates a new `Lot` record scoped to the move line's `company_id` (`plugins/webkul/barcode/src/Livewire/Operation.php:464-470`).
4. **Backorder Creation Protocol**:
   - If any move line is validated with a counted quantity less than required (`counted < required`), and the operation type's `create_backorder` policy is set to `CreateBackorder::ASK`, the system prompts the operator whether to create a backorder or cancel remaining quantities before calling `Inventory::completeTransfer()` (`plugins/webkul/barcode/src/Livewire/Operation.php:337-364, 402-417`).
5. **Direct Physical Count Application**:
   - In `Adjustments::applyQuantityCount()`, applying a counted inventory adjustment immediately overwrites `ProductQuantity::$quantity` with `counted_quantity` and clears the temporary count flags (`counted_quantity = 0`, `inventory_quantity_set = false`) (`plugins/webkul/barcode/src/Livewire/Adjustments.php:193-210`).
6. **Internal Stock Scoping**:
   - Inventory adjustments and move line source picking are restricted exclusively to internal warehouse locations (`LocationType::INTERNAL`) that are not marked as scrap (`is_scrap = false`) (`plugins/webkul/barcode/src/Livewire/Adjustments.php:238-241, 325-326`).

## Extension Points
[VERIFIED]
1. **Manufacturing Orders Mobile Navigation**:
   - `Navigation::items()` includes a predefined entry for `manufacturing-orders` (currently marked `disabled => true`). Downstream modules can enable this route to support mobile manufacturing work order execution.
2. **Blade Component Theming**:
   - Header, sidebar, and layout views under `resources/views/` can be overridden by publishing plugin views.
3. **NativePHP Mobile Packaging**:
   - Mobile manifests (`nativephp.json`) and native bridge components (`<x-nativephp-remote::bridge-scripts />`) allow wrapping the hosted web application into standalone native mobile binaries with hardware camera and haptics access.

## Dangerous Areas
[VERIFIED]
1. **Zero Test Coverage**:
   - The plugin contains **0 automated unit or feature tests**. Any regression in Livewire component methods, scanning resolution logic, or transfer completion transactions can only be caught through manual UI testing.
2. **Direct Database Overwrite in Inventory Adjustments**:
   - `Adjustments::applyQuantityCount()` directly mutates `ProductQuantity::$quantity` without creating formal audit adjustments or inventory move lines if used outside standard stock valuation flows.
3. **Move Line Quantity Truncation on Partial Validation**:
   - In `Operation::syncCountedMoveLineQuantitiesForValidation()`, `MoveLine::$qty` is directly overwritten with the validated count before `completeTransfer()` is called. If backorders are not created or cancelled improperly, original demand records could be lost.
4. **Camera Hardware & Permissions in Non-HTTPS Environments**:
   - The `html5-qrcode` scanner relies on browser `navigator.mediaDevices.getUserMedia()`, which modern browsers strictly block on insecure (HTTP) origins. Operating in production without SSL or valid mobile permissions will cause the scanner to fail.

## Change Impact
[VERIFIED]
- **Warehouse Operations**: Modifying scanning logic or Livewire event dispatchers directly impacts warehouse floor speed, scanning terminal reliability, and transfer processing time.
- **Inventory Balances**: Altering `Adjustments::applyQuantityCount()` or `Operation::validateOperation()` affects on-hand stock quantities across all internal locations.

## Evidence
[VERIFIED]

| Evidence Code | File Path | Symbol / Citation | Description |
| :--- | :--- | :--- | :--- |
| `E-BAR-001` | `plugins/webkul/barcode/src/BarcodeServiceProvider.php` | `BarcodeServiceProvider` | Service provider registration, Livewire component binding, Filament asset registration |
| `E-BAR-002` | `plugins/webkul/barcode/src/BarcodePlugin.php` | `BarcodePlugin` | Filament plugin registration and panel gating (`admin` only) |
| `E-BAR-003` | `plugins/webkul/barcode/composer.json` | `webkul/barcode` | Composer manifest, namespace autoloading, nativephp manifest pointer |
| `E-BAR-004` | `plugins/webkul/barcode/nativephp.json` | `nativephp.json` | Android camera permissions and iOS camera usage descriptions |
| `E-BAR-005` | `plugins/webkul/barcode/routes/web.php` | `routes/web.php` | Web routes for barcode login, dashboard, adjustments, transfers, and operations |
| `E-BAR-006` | `plugins/webkul/barcode/src/Filament/Pages/LaunchBarcode.php` | `LaunchBarcode` | Filament admin launcher page redirecting to `/admin/barcode` |
| `E-BAR-007` | `plugins/webkul/barcode/src/Livewire/Dashboard.php` | `Dashboard` | Livewire operational dashboard summarizing waiting transfer counts |
| `E-BAR-008` | `plugins/webkul/barcode/src/Livewire/Transfers.php` | `Transfers` | Livewire transfer listing and barcode search resolution |
| `E-BAR-009` | `plugins/webkul/barcode/src/Livewire/Operation.php` | `Operation` | Livewire transfer execution, barcode scanning engine, backorder handling, and validation |
| `E-BAR-010` | `plugins/webkul/barcode/src/Livewire/Adjustments.php` | `Adjustments` | Livewire physical stock adjustment and cycle counting engine |
| `E-BAR-011` | `plugins/webkul/barcode/src/Livewire/Auth/Login.php` | `Login` | Dedicated barcode mobile login page extending Filament base login |
| `E-BAR-012` | `plugins/webkul/barcode/src/Http/Middleware/Authenticate.php` | `Authenticate` | Barcode panel authentication and authorization middleware |
| `E-BAR-013` | `plugins/webkul/barcode/src/Http/Responses/LoginResponse.php` | `LoginResponse` | Custom login redirect handler targeting `barcode.dashboard` |
| `E-BAR-014` | `plugins/webkul/barcode/src/Support/Navigation.php` | `Navigation` | Sidebar navigation item definitions |
| `E-BAR-015` | `plugins/webkul/barcode/src/Support/NativeApp.php` | `NativeApp` | Native mobile shell bridge, dynamic titles, and deep-link generation |
| `E-BAR-016` | `plugins/webkul/barcode/resources/dist/barcode.js` | `barcode.js` | Client-side Alpine.js barcodeScanner component and event handlers |
| `E-BAR-017` | `plugins/webkul/barcode/resources/dist/html5-qrcode.min.js` | `html5-qrcode.min.js` | Bundled local QR/barcode camera scanning JavaScript library |
| `E-BAR-018` | `docs/database/erds/operations.md` | `operations.md:40, 505-517, 1124` | Phase 4 ERD verification of 0 database tables and 0 migrations |
