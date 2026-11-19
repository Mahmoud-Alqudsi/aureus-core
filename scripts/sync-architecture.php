#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__.'/architecture/Extractor.php';
require_once __DIR__.'/architecture/Synchronizer.php';
require_once __DIR__.'/architecture/HookHandler.php';

use Scripts\Architecture\Extractor;
use Scripts\Architecture\HookHandler;
use Scripts\Architecture\Synchronizer;

$options = getopt('', ['check', 'sync', 'json', 'hook:', 'dry-run', 'diff', 'help']);

if (isset($options['help'])) {
    echo <<<'HELP'
Aureus ERP — Architecture Synchronization CLI

Usage:
  php scripts/sync-architecture.php [options]

Options:
  --check        Check for architectural drift against ARCHITECTURE.md (exit 0: sync, exit 1: drift)
  --sync         Automatically synchronize ARCHITECTURE.md with current codebase metrics
  --dry-run      Simulate synchronization without modifying ARCHITECTURE.md
  --diff         Display unified line diff of pending synchronization changes
  --json         Output current extracted architecture metrics as JSON
  --hook=<type>  Execute Antigravity lifecycle hook handler (e.g. --hook=stop)
  --help         Show this help message

HELP;
    exit(0);
}

// 1. Hook Handler Mode
if (isset($options['hook'])) {
    $handler = new HookHandler;
    $handler->handle((string) $options['hook']);
    exit(0);
}

$extractor = new Extractor;
$synchronizer = new Synchronizer($extractor);

// 2. Diff Mode
if (isset($options['diff'])) {
    echo $synchronizer->diff().PHP_EOL;
    exit(0);
}

// 3. Dry-Run Mode (Standalone)
if (isset($options['dry-run']) && ! isset($options['sync'])) {
    echo 'Simulating synchronization with ARCHITECTURE.md...'.PHP_EOL;
    try {
        $wouldUpdate = $synchronizer->sync(true);
        if ($wouldUpdate) {
            echo ' [DRY-RUN] ARCHITECTURE.md would be updated. Run with --diff to see changes or --sync to apply.'.PHP_EOL;
        } else {
            echo ' [DRY-RUN] ARCHITECTURE.md is already up to date.'.PHP_EOL;
        }
        exit(0);
    } catch (Throwable $e) {
        fwrite(STDERR, ' [ERROR] Dry-run validation failed: '.$e->getMessage().PHP_EOL);
        exit(1);
    }
}

// 4. JSON Mode
if (isset($options['json'])) {
    $data = [
        'baseline'   => $extractor->getBaselineVersions(),
        'migrations' => $extractor->getMigrationMetrics(),
        'plugins'    => $extractor->getPluginCatalog(),
        'navigation' => $extractor->getNavigationGroups(),
        'providers'  => $extractor->getRegisteredProviders(),
    ];
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit(0);
}

// 5. Sync Mode
if (isset($options['sync'])) {
    $isDry = isset($options['dry-run']);
    echo ($isDry ? 'Simulating synchronization of' : 'Synchronizing').' ARCHITECTURE.md with codebase metrics...'.PHP_EOL;
    try {
        $updated = $synchronizer->sync($isDry);
        if ($updated) {
            echo ' [OK] ARCHITECTURE.md '.($isDry ? 'would be updated (dry-run).' : 'successfully updated and synchronized.').PHP_EOL;
        } else {
            echo ' [OK] ARCHITECTURE.md is already up to date with codebase metrics.'.PHP_EOL;
        }
        exit(0);
    } catch (Throwable $e) {
        fwrite(STDERR, ' [ERROR] Failed to synchronize: '.$e->getMessage().PHP_EOL);
        exit(1);
    }
}

// 6. Check Mode (Default)
$drift = $synchronizer->checkDrift();

if (empty($drift)) {
    echo ' [PASS] ARCHITECTURE.md is in sync with codebase metrics.'.PHP_EOL;
    $plugins = $extractor->getPluginCatalog();
    $migrations = $extractor->getMigrationMetrics();
    echo "        Total Plugins: {$plugins['total_plugins']} ({$plugins['core_plugins']} core, {$plugins['optional_plugins']} optional, {$plugins['zero_table_plugins']} zero-table)".PHP_EOL;
    echo "        Total Migrations: {$migrations['total_count']} ({$migrations['core_count']} core, {$migrations['plugin_count']} plugins across {$migrations['plugins_with_migrations']} plugins)".PHP_EOL;
    exit(0);
} else {
    fwrite(STDERR, ' [FAIL] Architectural drift detected between codebase and ARCHITECTURE.md:'.PHP_EOL);
    foreach ($drift as $item) {
        fwrite(STDERR, "        - {$item}".PHP_EOL);
    }
    fwrite(STDERR, PHP_EOL."Run 'php scripts/sync-architecture.php --sync' to update ARCHITECTURE.md.".PHP_EOL);
    exit(1);
}
