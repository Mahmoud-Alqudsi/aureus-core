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

    public function getExtractor(): Extractor
    {
        return $this->extractor;
    }

    /**
     * Check if ARCHITECTURE.md has drifted from actual source code metrics.
     * Returns an array of drift messages. If empty, the document is 100% in sync without contradictions.
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
        $providerCount = count($providers);

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

        // 2. Check Table 2.1 Locked Dependency Baseline
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

        // 3. Check Table of Contents links
        $expectedToc41 = "[4.1 Comprehensive Catalog of All {$plugins['total_plugins']} Local Plugins](#41-comprehensive-catalog-of-all-{$plugins['total_plugins']}-local-plugins)";
        if (! str_contains($content, $expectedToc41)) {
            $drift[] = "Table of Contents Section 4.1 link mismatch. Expected: '{$expectedToc41}'";
        }

        $expectedToc81 = "[8.1 Schema Architecture Across {$migrations['total_count']} Migrations](#81-schema-architecture-across-{$migrations['total_count']}-migrations)";
        if (! str_contains($content, $expectedToc81)) {
            $drift[] = "Table of Contents Section 8.1 link mismatch. Expected: '{$expectedToc81}'";
        }

        // 4. Check Section Headings
        $expectedHeading41 = "### 4.1 Comprehensive Catalog of All {$plugins['total_plugins']} Local Plugins";
        if (! str_contains($content, $expectedHeading41)) {
            $drift[] = "Heading 4.1 mismatch. Expected: '{$expectedHeading41}'";
        }

        $expectedHeading81 = "### 8.1 Schema Architecture Across {$migrations['total_count']} Migrations";
        if (! str_contains($content, $expectedHeading81)) {
            $drift[] = "Heading 8.1 mismatch. Expected: '{$expectedHeading81}'";
        }

        // 5. Check Section 1.1 Monorepo File Tree Metrics
        $expectedProviderTreeStr = "providers.php            # Static registration of all {$providerCount} service providers";
        if ($providerCount > 0 && ! str_contains($content, $expectedProviderTreeStr)) {
            $drift[] = "Section 1.1 file tree provider count mismatch. Expected: '{$expectedProviderTreeStr}'";
        }

        $expectedMigrationTreeStr = "Root migrations ({$migrations['core_count']} system migrations)";
        if (! str_contains($content, $expectedMigrationTreeStr)) {
            $drift[] = "Section 1.1 file tree system migrations count mismatch. Expected to contain: '{$expectedMigrationTreeStr}'";
        }

        $expectedPluginTreeStr = "{$plugins['total_plugins']} Local Composer packages";
        if (! str_contains($content, $expectedPluginTreeStr)) {
            $drift[] = "Section 1.1 file tree local plugins count mismatch. Expected to contain: '{$expectedPluginTreeStr}'";
        }

        // 6. Check Registered Service Providers count in Section 3.4
        $expectedProviderStr = "**{$providerCount} service providers**";
        if ($providerCount > 0 && ! str_contains($content, $expectedProviderStr)) {
            $drift[] = "Service provider count mismatch in Section 3.4. Expected: '{$expectedProviderStr}'";
        }

        // 7. Check Section 4.1 Plugin Count Header
        $expectedPluginCountStr = "Exactly {$plugins['total_plugins']} plugins are present";
        if (! str_contains($content, $expectedPluginCountStr)) {
            $drift[] = "Plugin count mismatch in Section 4.1. Expected: '{$expectedPluginCountStr}'";
        }

        // 8. Check Section 4.1 Archetype counts and package lists
        $expectedCoreStr = "{$plugins['core_plugins']} Core Infrastructure Packages";
        if (! str_contains($content, $expectedCoreStr)) {
            $drift[] = "Core plugin count mismatch in Section 4.1. Expected: '{$expectedCoreStr}'";
        }

        $coreListStr = implode(', ', array_map(fn ($p) => "`{$p}`", $plugins['core_plugin_names']));
        if (! empty($coreListStr) && ! str_contains($content, $coreListStr)) {
            $drift[] = "Core Infrastructure packages list mismatch in Section 4.1. Expected: '{$coreListStr}'";
        }

        $expectedOptionalStr = "{$plugins['optional_plugins']} Optional Domain Packages";
        if (! str_contains($content, $expectedOptionalStr)) {
            $drift[] = "Optional plugin count mismatch in Section 4.1. Expected: '{$expectedOptionalStr}'";
        }

        $expectedZeroTableStr = "{$plugins['zero_table_plugins']} Zero-Table Extension Packages";
        if (! str_contains($content, $expectedZeroTableStr)) {
            $drift[] = "Zero-table plugin count mismatch in Section 4.1. Expected: '{$expectedZeroTableStr}'";
        }

        $zeroTableListStr = implode(', ', array_map(fn ($p) => "`{$p}`", $plugins['zero_table_plugin_names']));
        if (! empty($zeroTableListStr) && ! str_contains($content, $zeroTableListStr)) {
            $drift[] = "Zero-Table packages list mismatch in Section 4.1. Expected: '{$zeroTableListStr}'";
        }

        // 9. Check Section 8.1 Migration counts
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

        // 10. Check Navigation groups in Section 7.4
        $expectedNavString = 'Cases: '.implode(', ', array_map(fn ($c) => "`{$c}`", $navGroups)).'.';
        if (! str_contains($content, $expectedNavString)) {
            $drift[] = "Navigation groups mismatch in Section 7.4. Expected: '{$expectedNavString}'";
        }

        // 11. Check individual plugin rows in Section 4.1 table
        foreach ($plugins['plugins'] as $name => $meta) {
            if (! preg_match('/\|\s*\d+\s*\|\s*`'.preg_quote($name, '/').'`\s*\|/m', $content)) {
                $drift[] = "Plugin '{$name}' is missing from the catalog table in Section 4.1.";

                continue;
            }

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
     * Generate updated content for ARCHITECTURE.md with all metrics synchronized.
     */
    public function generateSynchronizedContent(): string
    {
        if (! file_exists($this->docPath)) {
            throw new \RuntimeException('ARCHITECTURE.md not found at '.$this->docPath);
        }

        $content = file_get_contents($this->docPath);

        $baseline = $this->extractor->getBaselineVersions();
        $migrations = $this->extractor->getMigrationMetrics();
        $plugins = $this->extractor->getPluginCatalog();
        $navGroups = $this->extractor->getNavigationGroups();
        $providers = $this->extractor->getRegisteredProviders();
        $providerCount = count($providers);

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

        // 2. Update Table of Contents entries
        $content = preg_replace(
            '/\[4\.1 Comprehensive Catalog of All \d+ Local Plugins\]\(#41-comprehensive-catalog-of-all-\d+-local-plugins\)/m',
            "[4.1 Comprehensive Catalog of All {$plugins['total_plugins']} Local Plugins](#41-comprehensive-catalog-of-all-{$plugins['total_plugins']}-local-plugins)",
            $content
        );
        $content = preg_replace(
            '/\[8\.1 Schema Architecture Across \d+ Migrations\]\(#81-schema-architecture-across-\d+-migrations\)/m',
            "[8.1 Schema Architecture Across {$migrations['total_count']} Migrations](#81-schema-architecture-across-{$migrations['total_count']}-migrations)",
            $content
        );

        // 3. Update Headings
        $content = preg_replace(
            '/^###\s*4\.1\s+Comprehensive Catalog of All \d+ Local Plugins/m',
            "### 4.1 Comprehensive Catalog of All {$plugins['total_plugins']} Local Plugins",
            $content
        );
        $content = preg_replace(
            '/^###\s*8\.1\s+Schema Architecture Across \d+ Migrations/m',
            "### 8.1 Schema Architecture Across {$migrations['total_count']} Migrations",
            $content
        );

        // 4. Update Table 2.1 Locked Dependency Baseline
        foreach ($baseline as $key => $info) {
            $nameEscaped = preg_quote($info['name'], '/');
            $pattern = '/(\|\s*\*\*'.$nameEscaped.'\*\*\s*\|\s*`)[^`]+(`[^|]*\|\s*`)[^`]+(`\s*\|)/m';
            $replacement = '${1}'.$info['constraint'].'${2}'.$info['locked'].'${3}';
            $content = preg_replace($pattern, $replacement, $content);
        }

        // 5. Update Section 1.1 Monorepo File Trees & Prose Metrics
        $content = preg_replace(
            '/An autonomous suite of \d+ local Composer packages/m',
            "An autonomous suite of {$plugins['total_plugins']} local Composer packages",
            $content
        );
        $content = preg_replace(
            '/(providers\.php\s+# Static registration of all )\d+( service providers)/m',
            '${1}'.$providerCount.'${2}',
            $content
        );
        $content = preg_replace(
            '/(Root migrations \()\d+( system migrations\))/m',
            '${1}'.$migrations['core_count'].'${2}',
            $content
        );
        $content = preg_replace(
            '/(├── plugins\/webkul\/\s+# )\d+( Local Composer packages)/m',
            '${1}'.$plugins['total_plugins'].'${2}',
            $content
        );
        $content = preg_replace(
            '/(\.\.\. \()\d+( other plugins\))/m',
            '${1}'.$plugins['other_plugins_count'].'${2}',
            $content
        );

        // 6. Update Section 2.3, 4.3, 4.4, 7.1, 8.3 References
        $content = preg_replace(
            '/(of all )\d+( plugins under `plugins\/webkul\/\*`)/m',
            '${1}'.$plugins['total_plugins'].'${2}',
            $content
        );
        $content = preg_replace(
            '/(All )\d+( plugin service providers inherit from)/m',
            '${1}'.$plugins['total_plugins'].'${2}',
            $content
        );
        $content = preg_replace(
            '/(\(all )\d+( plugins\))/m',
            '${1}'.$plugins['total_plugins'].'${2}',
            $content
        );
        $content = preg_replace(
            '/(checks across all )\d+( plugins execute)/m',
            '${1}'.$plugins['total_plugins'].'${2}',
            $content
        );
        $content = preg_replace(
            '/(from )\d+( Plugin Service Providers)/m',
            '${1}'.$plugins['total_plugins'].'${2}',
            $content
        );
        $content = preg_replace(
            '/(foreign key deletion rules across )\d+( migrations)/m',
            '${1}'.$migrations['total_count'].'${2}',
            $content
        );

        // 7. Update Section 3.4 Registered Service Providers Count
        if ($providerCount > 0) {
            $content = preg_replace(
                '/explicitly returns an immutable array of \*\*\d+ service providers\*\*:/m',
                "explicitly returns an immutable array of **{$providerCount} service providers**:",
                $content
            );
        }

        // 8. Update Section 4.1 Plugin Count Header
        $content = preg_replace(
            '/Every business feature in Aureus ERP is encapsulated in `plugins\/webkul\/\*`\. Exactly \d+ plugins are present:/m',
            "Every business feature in Aureus ERP is encapsulated in `plugins/webkul/*`. Exactly {$plugins['total_plugins']} plugins are present:",
            $content
        );

        // 9. Update Section 4.1 Plugin Catalog Table Rows (Non-Destructive)
        $tablePattern = '/(\| # \| Directory \|.*?\n\|:---:\|.*?\n)((?:\|.*?\n)+)(?=\n#### Architectural Plugin Archetypes)/s';
        if (preg_match($tablePattern, $content, $matches)) {
            $headerBlock = $matches[1];
            $bodyBlock = $matches[2];

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

        // 10. Update Section 4.1 Archetypes (Counts & Package Lists)
        $coreListStr = implode(', ', array_map(fn ($p) => "`{$p}`", $plugins['core_plugin_names']));
        $zeroTableListStr = implode(', ', array_map(fn ($p) => "`{$p}`", $plugins['zero_table_plugin_names']));

        $content = preg_replace(
            '/-\s*\*\*\d+\s*Core Infrastructure Packages\s*\(`\$package->isCore\(\)`\)\*\*:\s*[`A-Za-z0-9_,\s-]+\.\s*Loaded unconditionally\./m',
            "- **{$plugins['core_plugins']} Core Infrastructure Packages (`\$package->isCore()`)**: {$coreListStr}. Loaded unconditionally.",
            $content
        );

        $content = preg_replace(
            '/-\s*\*\*\d+\s*Optional Domain Packages\*\*:\s*Gated by runtime installation/m',
            "- **{$plugins['optional_plugins']} Optional Domain Packages**: Gated by runtime installation",
            $content
        );

        $content = preg_replace(
            '/-\s*\*\*\d+\s*Zero-Table Extension Packages\*\*:\s*[`A-Za-z0-9_,\s-]+\.\s*Own \*\*zero migrations\*\*/m',
            "- **{$plugins['zero_table_plugins']} Zero-Table Extension Packages**: {$zeroTableListStr}. Own **zero migrations**",
            $content
        );

        // 11. Update Section 7.4 Navigation Groups Cases
        $navCasesStr = 'Cases: '.implode(', ', array_map(fn ($c) => "`{$c}`", $navGroups)).'.';
        $content = preg_replace(
            '/Cases:\s*[`A-Za-z0-9_,\s]+\./m',
            $navCasesStr,
            $content
        );

        // 12. Update Section 8.1 Database Migration Counts
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

        return $content;
    }

    /**
     * Validate the structural integrity of generated content before writing.
     * Prevents data loss, abnormal shrinking, or heading deletions.
     *
     * @throws \RuntimeException
     */
    public function validateIntegrity(string $original, string $newContent, array $plugins): void
    {
        $origLen = strlen($original);
        $newLen = strlen($newContent);

        // 1. File size sanity check (never shrink by > 5%)
        if ($newLen < (int) ($origLen * 0.95)) {
            throw new \RuntimeException(sprintf(
                'Safety assertion failed: Updated content is abnormally smaller (%d bytes vs %d bytes). Aborting write to prevent data loss.',
                $newLen,
                $origLen
            ));
        }

        // 2. Heading count preservation
        preg_match_all('/^(#{1,3}\s+[^\n]+)/m', $original, $origHeadings);
        preg_match_all('/^(#{1,3}\s+[^\n]+)/m', $newContent, $newHeadings);

        $origCount = count($origHeadings[0]);
        $newCount = count($newHeadings[0]);

        if ($newCount < $origCount) {
            throw new \RuntimeException(sprintf(
                'Safety assertion failed: Section heading count decreased (%d vs %d). Aborting write to prevent data loss.',
                $newCount,
                $origCount
            ));
        }

        // 3. Plugin catalog table rows count check
        $tablePattern = '/(\| # \| Directory \|.*?\n\|:---:\|.*?\n)((?:\|.*?\n)+)(?=\n#### Architectural Plugin Archetypes)/s';
        if (preg_match($tablePattern, $newContent, $matches)) {
            $rowLines = array_filter(explode("\n", trim($matches[2])), fn ($l) => str_contains($l, '|'));
            $expectedCount = $plugins['total_plugins'];
            $actualCount = count($rowLines);

            if ($actualCount !== $expectedCount) {
                throw new \RuntimeException(sprintf(
                    'Safety assertion failed: Plugin catalog row count mismatch (%d rows vs %d expected). Aborting.',
                    $actualCount,
                    $expectedCount
                ));
            }
        }
    }

    /**
     * Synchronize ARCHITECTURE.md with current codebase metrics.
     * Returns true if changes were written, false if already in sync.
     */
    public function sync(bool $dryRun = false): bool
    {
        if (! file_exists($this->docPath)) {
            throw new \RuntimeException('ARCHITECTURE.md not found at '.$this->docPath);
        }

        $original = file_get_contents($this->docPath);
        $newContent = $this->generateSynchronizedContent();
        $plugins = $this->extractor->getPluginCatalog();

        // Run pre-flight safety validations
        $this->validateIntegrity($original, $newContent, $plugins);

        if ($newContent !== $original) {
            if (! $dryRun) {
                file_put_contents($this->docPath, $newContent);
            }

            return true;
        }

        return false;
    }

    /**
     * Produce a unified line diff between original and new content.
     */
    public function diff(): string
    {
        if (! file_exists($this->docPath)) {
            return 'ARCHITECTURE.md not found at '.$this->docPath;
        }

        $original = file_get_contents($this->docPath);
        $newContent = $this->generateSynchronizedContent();

        if ($original === $newContent) {
            return 'No differences. ARCHITECTURE.md is already synchronized with codebase metrics.';
        }

        $origLines = explode("\n", $original);
        $newLines = explode("\n", $newContent);

        $diff = [];
        $max = max(count($origLines), count($newLines));

        for ($i = 0; $i < $max; $i++) {
            $origLine = $origLines[$i] ?? null;
            $newLine = $newLines[$i] ?? null;

            if ($origLine !== $newLine) {
                $lineNum = $i + 1;
                if ($origLine !== null) {
                    $diff[] = "- L{$lineNum}: {$origLine}";
                }
                if ($newLine !== null) {
                    $diff[] = "+ L{$lineNum}: {$newLine}";
                }
            }
        }

        return implode(PHP_EOL, $diff);
    }
}
