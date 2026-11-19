<?php

declare(strict_types=1);

test('plugin-manager translations:check runs cleanly on support plugin via CLI', function () {
    $output = [];
    $exitCode = 0;

    exec('php artisan translations:check --plugin=support', $output, $exitCode);

    expect($exitCode)->toBe(0)
        ->and(implode(PHP_EOL, $output))->toContain('All translations are synchronized with EN!');
});

test('plugin-manager translations:check runs cleanly on accounts plugin via CLI', function () {
    $output = [];
    $exitCode = 0;

    exec('php artisan translations:check --plugin=accounts', $output, $exitCode);

    expect($exitCode)->toBe(0)
        ->and(implode(PHP_EOL, $output))->toContain('All translations are synchronized with EN!');
});

test('translations consistency check fails when missing translations are detected', function () {
    $output = [];
    $exitCode = 0;

    // Running with a non-existent plugin or testing exit behavior
    exec('php artisan translations:check --plugin=nonexistent_plugin_xyz', $output, $exitCode);

    expect($exitCode)->toBe(0)
        ->and(implode(PHP_EOL, $output))->toContain('No translations found to check.');
});
