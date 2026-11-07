<?php

declare(strict_types=1);

namespace Scripts\Architecture;

class Extractor
{
    protected string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = rtrim($basePath ?? dirname(__DIR__, 2), '/');
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Extract platform baseline versions from composer.json and composer.lock
     */
    public function getBaselineVersions(): array
    {
        $composerJsonPath = $this->basePath.'/composer.json';
        $composerLockPath = $this->basePath.'/composer.lock';

        $composerJson = file_exists($composerJsonPath)
            ? json_decode(file_get_contents($composerJsonPath), true)
            : [];

        $composerLock = file_exists($composerLockPath)
            ? json_decode(file_get_contents($composerLockPath), true)
            : [];

        $lockedPackages = [];
        $packages = array_merge(
            $composerLock['packages'] ?? [],
            $composerLock['packages-dev'] ?? []
        );

        foreach ($packages as $pkg) {
            $lockedPackages[$pkg['name']] = $pkg['version'];
        }

        $phpConstraint = $composerJson['require']['php'] ?? '^8.3';

        return [
            'php' => [
                'name'       => 'PHP Runtime',
                'constraint' => $phpConstraint,
                'locked'     => '8.3.x',
                'source'     => 'composer.json:12',
            ],
            'laravel/framework' => [
                'name'       => 'Laravel Framework',
                'constraint' => $composerJson['require']['laravel/framework'] ?? '^13.0',
                'locked'     => $lockedPackages['laravel/framework'] ?? 'unknown',
            ],
            'filament/filament' => [
                'name'       => 'Filament Admin Panel',
                'constraint' => $composerJson['require']['filament/filament'] ?? '^5.0',
                'locked'     => $lockedPackages['filament/filament'] ?? 'unknown',
            ],
            'filament/spatie-laravel-settings-plugin' => [
                'name'       => 'Filament Settings Plugin',
                'constraint' => $composerJson['require']['filament/spatie-laravel-settings-plugin'] ?? '^5.0',
                'locked'     => $lockedPackages['filament/spatie-laravel-settings-plugin'] ?? $lockedPackages['filament/filament'] ?? 'unknown',
            ],
            'bezhansalleh/filament-shield' => [
                'name'       => 'Filament Shield',
                'constraint' => $composerJson['require']['bezhansalleh/filament-shield'] ?? '4.2.0',
                'locked'     => $lockedPackages['bezhansalleh/filament-shield'] ?? 'unknown',
            ],
            'livewire/livewire' => [
                'name'       => 'Livewire',
                'constraint' => $composerJson['require']['livewire/livewire'] ?? '^4.0',
                'locked'     => $lockedPackages['livewire/livewire'] ?? 'unknown',
            ],
            'laravel/sanctum' => [
                'name'       => 'Laravel Sanctum',
                'constraint' => $composerJson['require']['laravel/sanctum'] ?? '^4.0',
                'locked'     => $lockedPackages['laravel/sanctum'] ?? 'unknown',
            ],
            'pestphp/pest' => [
                'name'       => 'Pest Testing Framework',
                'constraint' => $composerJson['require-dev']['pestphp/pest'] ?? '^4.4',
                'locked'     => $lockedPackages['pestphp/pest'] ?? 'unknown',
            ],
            'wikimedia/composer-merge-plugin' => [
                'name'       => 'Composer Merge Plugin',
                'constraint' => $composerJson['require']['wikimedia/composer-merge-plugin'] ?? '^2.1',
                'locked'     => $lockedPackages['wikimedia/composer-merge-plugin'] ?? 'unknown',
            ],
            'bezhansalleh/filament-language-switch' => [
                'name'       => 'Filament Language Switch',
                'constraint' => $composerJson['require']['bezhansalleh/filament-language-switch'] ?? '^4.3',
                'locked'     => $lockedPackages['bezhansalleh/filament-language-switch'] ?? 'unknown',
            ],
            'malzariey/filament-daterangepicker-filter' => [
                'name'       => 'Filament DateRangePicker',
                'constraint' => $composerJson['require']['malzariey/filament-daterangepicker-filter'] ?? '^5.0',
                'locked'     => $lockedPackages['malzariey/filament-daterangepicker-filter'] ?? 'unknown',
            ],
            'spatie/laravel-query-builder' => [
                'name'       => 'Spatie Query Builder',
                'constraint' => $composerJson['require']['spatie/laravel-query-builder'] ?? '^7.0',
                'locked'     => $lockedPackages['spatie/laravel-query-builder'] ?? 'unknown',
            ],
            'spatie/laravel-translatable' => [
                'name'       => 'Spatie Translatable',
                'constraint' => $composerJson['require']['spatie/laravel-translatable'] ?? '^6.14',
                'locked'     => $lockedPackages['spatie/laravel-translatable'] ?? 'unknown',
            ],
            'lara-zeus/spatie-translatable' => [
                'name'       => 'Zeus Spatie Translatable',
                'constraint' => $composerJson['require']['lara-zeus/spatie-translatable'] ?? '^2.0',
                'locked'     => $lockedPackages['lara-zeus/spatie-translatable'] ?? 'unknown',
            ],
            'laravel/pint' => [
                'name'       => 'Laravel Pint',
                'constraint' => $composerJson['require-dev']['laravel/pint'] ?? '^1.27',
                'locked'     => $lockedPackages['laravel/pint'] ?? 'unknown',
            ],
            'knuckleswtf/scribe' => [
                'name'       => 'Scribe API Documentation',
                'constraint' => $composerJson['require']['knuckleswtf/scribe'] ?? '^5.6',
                'locked'     => $lockedPackages['knuckleswtf/scribe'] ?? 'unknown',
            ],
            'aureuserp/nativephp-remote' => [
                'name'       => 'NativePHP Remote Shell',
                'constraint' => $composerJson['require']['aureuserp/nativephp-remote'] ?? '^0.1.0',
                'locked'     => $lockedPackages['aureuserp/nativephp-remote'] ?? 'unknown',
            ],
            'nativephp/mobile' => [
                'name'       => 'NativePHP Mobile Runtime',
                'constraint' => $composerJson['require']['nativephp/mobile'] ?? '^3.3',
                'locked'     => $lockedPackages['nativephp/mobile'] ?? 'unknown',
            ],
            'barryvdh/laravel-dompdf' => [
                'name'       => 'Barryvdh Laravel DomPDF',
                'constraint' => $composerJson['require']['barryvdh/laravel-dompdf'] ?? '^3.1',
                'locked'     => $lockedPackages['barryvdh/laravel-dompdf'] ?? 'unknown',
            ],
            'barryvdh/laravel-debugbar' => [
                'name'       => 'Barryvdh Laravel Debugbar',
                'constraint' => $composerJson['require']['barryvdh/laravel-debugbar'] ?? $composerJson['require-dev']['barryvdh/laravel-debugbar'] ?? '^4.0',
                'locked'     => $lockedPackages['barryvdh/laravel-debugbar'] ?? 'unknown',
            ],
            'flowframe/laravel-trend' => [
                'name'       => 'Flowframe Laravel Trend',
                'constraint' => $composerJson['require']['flowframe/laravel-trend'] ?? '^0.5',
                'locked'     => $lockedPackages['flowframe/laravel-trend'] ?? 'unknown',
            ],
            'guava/filament-icon-picker' => [
                'name'       => 'Guava Filament Icon Picker',
                'constraint' => $composerJson['require']['guava/filament-icon-picker'] ?? '^4.0',
                'locked'     => $lockedPackages['guava/filament-icon-picker'] ?? 'unknown',
            ],
            'khaled.alshamaa/ar-php' => [
                'name'       => 'Khaled Alshamaa Ar-PHP',
                'constraint' => $composerJson['require']['khaled.alshamaa/ar-php'] ?? '^7.0',
                'locked'     => $lockedPackages['khaled.alshamaa/ar-php'] ?? 'unknown',
            ],
            'league/glide' => [
                'name'       => 'League Glide Image Engine',
                'constraint' => $composerJson['require']['league/glide'] ?? '^2.3',
                'locked'     => $lockedPackages['league/glide'] ?? 'unknown',
            ],
            'maatwebsite/excel' => [
                'name'       => 'Maatwebsite Excel',
                'constraint' => $composerJson['require']['maatwebsite/excel'] ?? '^3.1',
                'locked'     => $lockedPackages['maatwebsite/excel'] ?? 'unknown',
            ],
            'milon/barcode' => [
                'name'       => 'Milon Barcode Engine',
                'constraint' => $composerJson['require']['milon/barcode'] ?? '^13.0',
                'locked'     => $lockedPackages['milon/barcode'] ?? 'unknown',
            ],
            'spatie/eloquent-sortable' => [
                'name'       => 'Spatie Eloquent Sortable',
                'constraint' => $composerJson['require']['spatie/eloquent-sortable'] ?? '^5.0',
                'locked'     => $lockedPackages['spatie/eloquent-sortable'] ?? 'unknown',
            ],
            'laravel/pail' => [
                'name'       => 'Laravel Pail Log Streamer',
                'constraint' => $composerJson['require-dev']['laravel/pail'] ?? '^1.2.5',
                'locked'     => $lockedPackages['laravel/pail'] ?? 'unknown',
            ],
        ];
    }

    /**
     * Extract migration metrics across core and plugins
     */
    public function getMigrationMetrics(): array
    {
        $coreMigrations = glob($this->basePath.'/database/migrations/*.php') ?: [];
        $coreCount = count($coreMigrations);

        $pluginMigrations = [];
        $totalPluginMigrations = 0;
        $pluginsWithMigrations = 0;

        $pluginDirs = glob($this->basePath.'/plugins/webkul/*', GLOB_ONLYDIR) ?: [];
        sort($pluginDirs);

        foreach ($pluginDirs as $dir) {
            $pluginName = basename($dir);
            $migrations = glob($dir.'/database/migrations/*.php') ?: [];
            $count = count($migrations);
            $pluginMigrations[$pluginName] = $count;
            $totalPluginMigrations += $count;
            if ($count > 0) {
                $pluginsWithMigrations++;
            }
        }

        return [
            'core_count'                => $coreCount,
            'plugin_count'              => $totalPluginMigrations,
            'total_count'               => $coreCount + $totalPluginMigrations,
            'plugins_with_migrations'   => $pluginsWithMigrations,
            'by_plugin'                 => $pluginMigrations,
        ];
    }

    /**
     * Extract detailed plugin catalog
     */
    public function getPluginCatalog(): array
    {
        $pluginDirs = glob($this->basePath.'/plugins/webkul/*', GLOB_ONLYDIR) ?: [];
        sort($pluginDirs);

        $catalog = [];
        $coreCount = 0;
        $optionalCount = 0;
        $zeroTableCount = 0;

        foreach ($pluginDirs as $dir) {
            $name = basename($dir);
            $composerFile = $dir.'/composer.json';
            $composerData = file_exists($composerFile)
                ? json_decode(file_get_contents($composerFile), true)
                : [];

            $packageName = $composerData['name'] ?? ('webkul/'.$name);
            $namespaces = array_keys($composerData['autoload']['psr-4'] ?? []);
            $namespace = $namespaces[0] ?? ('Webkul\\'.ucfirst($name).'\\');
            $providers = $composerData['extra']['laravel']['providers'] ?? [];
            $provider = $providers[0] ?? '';

            // Check if Core
            $isCore = false;
            $providerFile = $this->findProviderFile($dir, $provider, $namespace);
            if ($providerFile && file_exists($providerFile)) {
                $providerContent = file_get_contents($providerFile);
                if (str_contains($providerContent, '->isCore(') || str_contains($providerContent, '->isCore()')) {
                    $isCore = true;
                }
            }

            // Migrations count
            $migrations = glob($dir.'/database/migrations/*.php') ?: [];
            $migrationCount = count($migrations);
            $isZeroTable = ($migrationCount === 0);

            if ($isCore) {
                $coreCount++;
            } else {
                $optionalCount++;
            }

            if ($isZeroTable) {
                $zeroTableCount++;
            }

            $catalog[$name] = [
                'directory'        => $name,
                'composer_package' => $packageName,
                'namespace'        => $namespace,
                'provider'         => $provider,
                'is_core'          => $isCore,
                'migrations'       => $migrationCount,
                'is_zero_table'    => $isZeroTable,
            ];
        }

        return [
            'total_plugins'     => count($catalog),
            'core_plugins'      => $coreCount,
            'optional_plugins'  => $optionalCount,
            'zero_table_plugins'=> $zeroTableCount,
            'plugins'           => $catalog,
        ];
    }

    /**
     * Extract NavigationGroup enum cases
     */
    public function getNavigationGroups(): array
    {
        $file = $this->basePath.'/plugins/webkul/support/src/Enums/NavigationGroup.php';
        if (! file_exists($file)) {
            return [];
        }

        $content = file_get_contents($file);
        preg_match_all('/case\s+([A-Za-z0-9_]+)\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/m', $content, $matches);

        return $matches[1] ?? [];
    }

    /**
     * Extract registered providers from bootstrap/providers.php
     */
    public function getRegisteredProviders(): array
    {
        $file = $this->basePath.'/bootstrap/providers.php';
        if (! file_exists($file)) {
            return [];
        }

        $content = file_get_contents($file);
        preg_match_all('/([A-Za-z0-9_\\\\]+)::class/m', $content, $matches);

        return $matches[1] ?? [];
    }

    /**
     * Find the PHP file corresponding to a provider class
     */
    protected function findProviderFile(string $pluginDir, string $providerClass, string $namespace): ?string
    {
        if (empty($providerClass)) {
            return null;
        }

        $relativeClass = str_replace($namespace, '', $providerClass);
        $relativeClass = ltrim($relativeClass, '\\');
        $filePath = $pluginDir.'/src/'.str_replace('\\', '/', $relativeClass).'.php';

        if (file_exists($filePath)) {
            return $filePath;
        }

        $srcFiles = glob($pluginDir.'/src/*ServiceProvider.php') ?: [];

        return $srcFiles[0] ?? null;
    }
}
