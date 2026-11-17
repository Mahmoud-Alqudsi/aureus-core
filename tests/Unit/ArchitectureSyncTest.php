<?php

declare(strict_types=1);

use Scripts\Architecture\Extractor;
use Scripts\Architecture\Synchronizer;

require_once dirname(__DIR__, 2).'/scripts/architecture/Extractor.php';
require_once dirname(__DIR__, 2).'/scripts/architecture/Synchronizer.php';

test('architecture specification is synchronized with codebase metrics without drift or contradiction', function () {
    $basePath = dirname(__DIR__, 2);
    $extractor = new Extractor($basePath);
    $synchronizer = new Synchronizer($extractor, $basePath.'/ARCHITECTURE.md');

    $drift = $synchronizer->checkDrift();

    expect($drift)->toBeEmpty(
        'ARCHITECTURE.md has drifted from codebase reality:'.PHP_EOL.implode(PHP_EOL, $drift)
    );
});

test('architecture synchronization CLI check returns exit code 0', function () {
    $script = dirname(__DIR__, 2).'/scripts/sync-architecture.php';
    $command = sprintf('php %s --check', escapeshellarg($script));
    $output = [];
    $exitCode = 0;

    exec($command, $output, $exitCode);

    expect($exitCode)->toBe(0, implode(PHP_EOL, $output));
});

test('architecture synchronization CLI diff and dry-run execute cleanly', function () {
    $script = dirname(__DIR__, 2).'/scripts/sync-architecture.php';

    // Test --diff
    $diffOutput = [];
    $diffExit = 0;
    exec(sprintf('php %s --diff', escapeshellarg($script)), $diffOutput, $diffExit);
    expect($diffExit)->toBe(0)
        ->and(implode(PHP_EOL, $diffOutput))->toContain('synchronized');

    // Test --dry-run
    $dryOutput = [];
    $dryExit = 0;
    exec(sprintf('php %s --dry-run', escapeshellarg($script)), $dryOutput, $dryExit);
    expect($dryExit)->toBe(0)
        ->and(implode(PHP_EOL, $dryOutput))->toContain('[DRY-RUN]');
});

test('architecture synchronizer pre-flight safety assertions block corrupt or truncated content', function () {
    $basePath = dirname(__DIR__, 2);
    $extractor = new Extractor($basePath);
    $synchronizer = new Synchronizer($extractor, $basePath.'/ARCHITECTURE.md');
    $plugins = $extractor->getPluginCatalog();

    $original = file_get_contents($basePath.'/ARCHITECTURE.md');

    // 1. Shrunken content must throw RuntimeException
    $truncated = substr($original, 0, (int) (strlen($original) * 0.8));
    expect(fn () => $synchronizer->validateIntegrity($original, $truncated, $plugins))
        ->toThrow(RuntimeException::class, 'abnormally smaller');

    // 2. Headings deletion must throw RuntimeException
    $withoutHeadings = preg_replace('/^###\s+.*$/m', '', $original);
    expect(fn () => $synchronizer->validateIntegrity($original, $withoutHeadings, $plugins))
        ->toThrow(RuntimeException::class, 'Section heading count decreased');
});
