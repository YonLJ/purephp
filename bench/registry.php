<?php

declare(strict_types=1);

/**
 * Component loading cost: what a process pays to require the unit artifacts of
 * the examples, cold and with a warm opcache. Run with and without opcache:
 *
 *   php bench/registry.php
 *   php -d opcache.enable_cli=1 bench/registry.php
 */

require __DIR__ . '/../vendor/autoload.php';

$root = dirname(__DIR__);
$artifacts = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . '/examples', RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file instanceof SplFileInfo && $file->isFile() && str_ends_with($file->getPathname(), '.pure.php')) {
        $artifacts[] = $file->getPathname();
    }
}

sort($artifacts);

if ($artifacts === []) {
    fwrite(STDERR, "run: php bin/pure compile --plain examples\n");

    exit(1);
}

$load = static function () use ($artifacts): float {
    $start = hrtime(true);

    foreach ($artifacts as $artifact) {
        require $artifact;
    }

    return (hrtime(true) - $start) / 1000;
};

// Cold: the first require compiles every file.
$cold = $load();

// Warm: the second require is served from opcache when it is enabled.
$warm = $load();

printf("opcache cli: %s\n\n", ini_get('opcache.enable_cli') ? 'on' : 'off');

printf(
    "%d artifact requires: %.1f us cold, %.1f us warm (%.2f us each warm)\n",
    count($artifacts),
    $cold,
    $warm,
    $warm / count($artifacts)
);
