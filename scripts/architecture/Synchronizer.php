<?php

declare(strict_types=1);

namespace Scripts\Architecture;

class Synchronizer
{
    protected Extractor $extractor;

    protected string $docPath;

    public function __construct(?Extractor $extractor = null, ?string $docPath = null)
    {
        $this->extractor = $extractor ?? new Extractor;
        $this->docPath = $docPath ?? ($this->extractor->getBasePath().'/ARCHITECTURE.md');
    }

    public function getDocPath(): string
    {
        return $this->docPath;
    }

    /**
     * Check if ARCHITECTURE.md has drifted from the actual source code metrics
     * Returns an array of drift messages. If empty, the document is in sync.
     */
    public function checkDrift(): array
    {
        if (! file_exists($this->docPath)) {
            return ['ARCHITECTURE.md does not exist at '.$this->docPath];
        }

        $content = file_get_contents($this->docPath);
        $drift = [];

        $baseline = $this->extractor->getBaselineVersions();
        $migrations = $this->extractor->getMigrationMetrics();
        $plugins = $this->extractor->getPluginCatalog();
        $navGroups = $this->extractor->getNavigationGroups();
        $providers = $this->extractor->getRegisteredProviders();

        // 1. Check Header Baseline
        $expectedHeader = sprintf(
            'PHP %s | Laravel %s | Filament %s | Livewire %s | Filament Shield %s',
            $baseline['php']['constraint'],
            $baseline['laravel/framework']['locked'],
            $baseline['filament/filament']['locked'],
            $baseline['livewire/livewire']['locked'],
            $baseline['bezhansalleh/filament-shield']['locked']
        );

        if (! str_contains($content, $expectedHeader)) {
            $drift[] = "Header baseline mismatch. Expected to contain: '{$expectedHeader}'";
        }

        // 1b. Check Table 2.1 Locked Dependency Baseline
        foreach ($baseline as $key => $info) {
            $nameEscaped = preg_quote($info['name'], '/');
            $pattern = '/\|\s*\*\*'.$nameEscaped.'\*\*\s*\|\s*`([^`]+)`[^|]*\|\s*`([^`]+)`\s*\|/m';

            if (! preg_match($pattern, $content, $m)) {
                $drift[] = "Dependency '{$info['name']}' is missing from Section 2.1 table.";

                continue;
            }

            if ($m[1] !== $info['constraint'] || $m[2] !== $info['locked']) {
                $drift[] = "Dependency '{$info['name']}' mismatch in Section 2.1: documented ({$m[1]} / {$m[2]}), actual ({$info['constraint']} / {$info['locked']}).";
            }
        }

        // 1c. Check Registered Service Providers count in Section 3.4
        $providerCount = count($providers);
        $expectedProviderStr = "**{$providerCount} service providers**";
        if ($providerCount > 0 && ! str_contains($content, $expectedProviderStr)) {
            $drift[] = "Service provider count mismatch in Section 3.4. Expected: '{$expectedProviderStr}'";
        }

        // 2. Check Plugin count
        $expectedPluginCountStr = "Exactly {$plugins['total_plugins']} plugins are present";
        if (! str_contains($content, $expectedPluginCountStr)) {
            $drift[] = "Plugin count mismatch in Section 4.1. Expected: '{$expectedPluginCountStr}'";
        }

        // 3. Check Archetype counts
        $expectedCoreStr = "{$plugins['core_plugins']} Core Infrastructure Packages";
        if (! str_contains($content, $expectedCoreStr)) {
            $drift[] = "Core plugin count mismatch in Section 4.1. Expected: '{$expectedCoreStr}'";
        }

        $expectedOptionalStr = "{$plugins['optional_plugins']} Optional Domain Packages";
        if (! str_contains($content, $expectedOptionalStr)) {
            $drift[] = "Optional plugin count mismatch in Section 4.1. Expected: '{$expectedOptionalStr}'";
        }

        $expectedZeroTableStr = "{$plugins['zero_table_plugins']} Zero-Table Extension Packages";
        if (! str_contains($content, $expectedZeroTableStr)) {
            $drift[] = "Zero-table plugin count mismatch in Section 4.1. Expected: '{$expectedZeroTableStr}'";
        }

        // 4. Check Migration counts in Section 8.1
        $expectedTotalMigrations = "Aureus ERP contains **{$migrations['total_count']} database migrations**";
        if (! str_contains($content, $expectedTotalMigrations)) {
            $drift[] = "Total migrations count mismatch in Section 8.1. Expected: '{$expectedTotalMigrations}'";
        }

        $expectedCoreMigrations = "Core application: `database/migrations/` ({$migrations['core_count']} migrations)";
        if (! str_contains($content, $expectedCoreMigrations)) {
            $drift[] = "Core migrations count mismatch in Section 8.1. Expected: '{$expectedCoreMigrations}'";
        }

        $expectedPluginMigrations = "Local plugins: `plugins/webkul/*/database/migrations/` ({$migrations['plugin_count']} migrations across {$migrations['plugins_with_migrations']} plugins)";
        if (! str_contains($content, $expectedPluginMigrations)) {
            $drift[] = "Plugin migrations count mismatch in Section 8.1. Expected: '{$expectedPluginMigrations}'";
        }

        // 5. Check Navigation groups
        $expectedNavString = 'Cases: '.implode(', ', array_map(fn ($c) => "`{$c}`", $navGroups)).'.';
        if (! str_contains($content, $expectedNavString)) {
            $drift[] = "Navigation groups mismatch in Section 7.4. Expected: '{$expectedNavString}'";
        }

        // 6. Check individual plugin rows in Section 4.1 table
        foreach ($plugins['plugins'] as $name => $meta) {
            // Check if plugin row exists
            if (! preg_match('/\|\s*\d+\s*\|\s*`'.preg_quote($name, '/').'`\s*\|/m', $content)) {
                $drift[] = "Plugin '{$name}' is missing from the catalog table in Section 4.1.";

                continue;
            }

            // Check if migrations count matches for this plugin
            $expectedRowPattern = '/\|\s*`'.preg_quote($name, '/').'`\s*\|[^|]+\|[^|]+\|[^|]+\|[^|]+\|\s*(\d+)\s*\|\s*([^|]+)\s*\|/m';
            if (preg_match($expectedRowPattern, $content, $m)) {
                $recordedMigrations = (int) trim($m[1]);
                $recordedZeroTable = trim($m[2]);

                if ($recordedMigrations !== $meta['migrations']) {
                    $drift[] = "Plugin '{$name}' migration count mismatch: documented {$recordedMigrations}, actual {$meta['migrations']}.";
                }

                $expectedZeroTableText = $meta['is_zero_table'] ? '**Yes**' : 'No';
                if ($recordedZeroTable !== $expectedZeroTableText) {
                    $drift[] = "Plugin '{$name}' zero-table flag mismatch: documented {$recordedZeroTable}, actual {$expectedZeroTableText}.";
                }
            }
        }

        return $drift;
    }

    /**
     * Synchronize ARCHITECTURE.md with current codebase metrics
     * Returns true if changes were written, false if already in sync.
     */
    public function sync(): bool
    {
        if (! file_exists($this->docPath)) {
            throw new \RuntimeException('ARCHITECTURE.md not found at '.$this->docPath);
        }

        $content = file_get_contents($this->docPath);
        $original = $content;

        $baseline = $this->extractor->getBaselineVersions();
        $migrations = $this->extractor->getMigrationMetrics();
        $plugins = $this->extractor->getPluginCatalog();
        $navGroups = $this->extractor->getNavigationGroups();
        $providers = $this->extractor->getRegisteredProviders();

        // 1. Update Header Baseline
        $newHeader = sprintf(
            '**Last Verified Baseline:** PHP %s | Laravel %s | Filament %s | Livewire %s | Filament Shield %s  ',
            $baseline['php']['constraint'],
            $baseline['laravel/framework']['locked'],
            $baseline['filament/filament']['locked'],
            $baseline['livewire/livewire']['locked'],
            $baseline['bezhansalleh/filament-shield']['locked']
        );
        $content = preg_replace(
            '/\*\*Last Verified Baseline:\*\*.*$/m',
            $newHeader,
            $content
        );

        // 2. Update Table 2.1 Locked Dependency Baseline
        foreach ($baseline as $key => $info) {
            $nameEscaped = preg_quote($info['name'], '/');
            $pattern = '/(\|\s*\*\*'.$nameEscaped.'\*\*\s*\|\s*`)[^`]+(`[^|]*\|\s*`)[^`]+(`\s*\|)/m';
            $replacement = '${1}'.$info['constraint'].'${2}'.$info['locked'].'${3}';
            $content = preg_replace($pattern, $replacement, $content);
        }

        // 2b. Update Section 3.4 Registered Service Providers Count
        $providerCount = count($providers);
        if ($providerCount > 0) {
            $content = preg_replace(
                '/explicitly returns an immutable array of \*\*\d+ service providers\*\*:/m',
                "explicitly returns an immutable array of **{$providerCount} service providers**:",
                $content
            );
        }

        // 3. Update Section 4.1 Plugin Count Header
        $content = preg_replace(
            '/Every business feature in Aureus ERP is encapsulated in `plugins\/webkul\/\*`\. Exactly \d+ plugins are present:/m',
            "Every business feature in Aureus ERP is encapsulated in `plugins/webkul/*`. Exactly {$plugins['total_plugins']} plugins are present:",
            $content
        );

        // 4. Update Section 4.1 Plugin Catalog Table Rows
        $tablePattern = '/(\| # \| Directory \|.*?\n\|:---:\|.*?\n)((?:\|.*?\n)+)(?=\n#### Architectural Plugin Archetypes)/s';
        if (preg_match($tablePattern, $content, $matches)) {
            $headerBlock = $matches[1];
            $bodyBlock = $matches[2];

            // Parse existing rows into [name => full_row_data]
            $existingRows = [];
            $rowLines = explode("\n", trim($bodyBlock));
            foreach ($rowLines as $line) {
                if (! str_contains($line, '|')) {
                    continue;
                }
                $cols = array_map('trim', explode('|', trim($line, '|')));
                if (count($cols) >= 9) {
                    $dir = trim($cols[1], '` ');
                    $existingRows[$dir] = [
                        'num'         => $cols[0],
                        'package'     => $cols[2],
                        'namespace'   => $cols[3],
                        'provider'    => $cols[4],
                        'core_text'   => $cols[5],
                        'migrations'  => $cols[6],
                        'zero_table'  => $cols[7],
                        'description' => $cols[8],
                    ];
                }
            }

            // Reconstruct rows preserving descriptions
            $newRowLines = [];
            $idx = 1;
            foreach ($plugins['plugins'] as $name => $meta) {
                $desc = $existingRows[$name]['description'] ?? 'Local plugin package.';
                $coreText = $meta['is_core'] ? '**Core**' : 'Optional';
                $zeroText = $meta['is_zero_table'] ? '**Yes**' : 'No';

                $newRowLines[] = sprintf(
                    '| %d | `%s` | `%s` | `%s` | `%s` | %s | %d | %s | %s |',
                    $idx++,
                    $name,
                    $meta['composer_package'],
                    $meta['namespace'],
                    $meta['provider'],
                    $coreText,
                    $meta['migrations'],
                    $zeroText,
                    $desc
                );
            }

            $newBody = implode("\n", $newRowLines)."\n";
            $content = str_replace($matches[0], $headerBlock.$newBody, $content);
        }

        // 5. Update Section 4.1 Archetypes
        $content = preg_replace(
            '/-\s*\*\*\d+\s*Core Infrastructure Packages/m',
            "- **{$plugins['core_plugins']} Core Infrastructure Packages",
            $content
        );
        $content = preg_replace(
            '/-\s*\*\*\d+\s*Optional Domain Packages\*\*/m',
            "- **{$plugins['optional_plugins']} Optional Domain Packages**",
            $content
        );
        $content = preg_replace(
            '/-\s*\*\*\d+\s*Zero-Table Extension Packages\*\*/m',
            "- **{$plugins['zero_table_plugins']} Zero-Table Extension Packages**",
            $content
        );

        // 6. Update Section 7.4 Navigation Groups Cases
        $navCasesStr = 'Cases: '.implode(', ', array_map(fn ($c) => "`{$c}`", $navGroups)).'.';
        $content = preg_replace(
            '/Cases:\s*[`A-Za-z0-9_,\s]+\./m',
            $navCasesStr,
            $content
        );

        // 7. Update Section 8.1 Database Migration Counts
        $content = preg_replace(
            '/Aureus ERP contains \*\*\d+ database migrations\*\*:/m',
            "Aureus ERP contains **{$migrations['total_count']} database migrations**:",
            $content
        );
        $content = preg_replace(
            '/-\s*Core application:\s*`database\/migrations\/`\s*\(\d+\s*migrations\)\./m',
            "- Core application: `database/migrations/` ({$migrations['core_count']} migrations).",
            $content
        );
        $content = preg_replace(
            '/-\s*Local plugins:\s*`plugins\/webkul\/\*\/database\/migrations\/`\s*\(\d+\s*migrations across \d+\s*plugins\)\./m',
            "- Local plugins: `plugins/webkul/*/database/migrations/` ({$migrations['plugin_count']} migrations across {$migrations['plugins_with_migrations']} plugins).",
            $content
        );

        if ($content !== $original) {
            file_put_contents($this->docPath, $content);

            return true;
        }

        return false;
    }
}
