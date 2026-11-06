<?php

declare(strict_types=1);

use Scripts\Architecture\Extractor;
use Scripts\Architecture\Synchronizer;

require_once dirname(__DIR__, 2).'/scripts/architecture/Extractor.php';
require_once dirname(__DIR__, 2).'/scripts/architecture/Synchronizer.php';

test('architecture specification is synchronized with codebase metrics', function () {
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
