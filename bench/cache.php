<?php

declare(strict_types=1);

/**
 * Compares cold (generate + write) and warm (load from disk) compile times for
 * one page shape. Run each phase in its own process:
 *
 *   php bench/cache.php --clear   # clear cache, then compile and write
 *   php bench/cache.php           # load the cached renderer
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../examples/bootstrap-features/shapes.php';

use Pure\Compile\Compile;

$dir = sys_get_temp_dir() . '/purephp-cache-bench';
Compile::cachePath($dir);

if (in_array('--clear', $argv, true)) {
    printf("cleared %d cache file(s)\n", Compile::clearCache());
}

$shape = FeaturePageShape();
$file = $dir . '/' . $shape->id() . '.php';
$warm = is_file($file);

$start = hrtime(true);
$compiled = $shape->compile();
$time = (hrtime(true) - $start) / 1000;

printf(
    "%s compile: %.0f us | %d cache file(s) | generated source %d bytes\n",
    $warm ? 'warm (loaded) ' : 'cold (written)',
    $time,
    count(glob($dir . '/*.php') ?: []),
    strlen($compiled->source())
);
