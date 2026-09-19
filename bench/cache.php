<?php

declare(strict_types=1);

/**
 * Compares cold (generate + write) and warm (load from disk) compile times for
 * the features page shape. Run each phase in its own process:
 *
 *   php bench/cache.php --clear   # clear cache, then compile and write
 *   php bench/cache.php           # load the cached renderer
 *
 * The shape measured is the page template of
 * examples/bootstrap/views/features.cmp.php, the unit the example precompiles
 * with `pure compile`; the body of the page is composed by the component
 * functions and is not part of this shape.
 */

require __DIR__ . '/../vendor/autoload.php';

use Pure\Compile\Compile;

$dir = sys_get_temp_dir() . '/purephp-cache-bench';
Compile::cachePath($dir);

if (in_array('--clear', $argv, true)) {
    printf("cleared %d cache file(s)\n", Compile::clearCache());
}

$unitFile = __DIR__ . '/../examples/bootstrap/views/features.cmp.php';
require_once $unitFile;
$shape = Compile::toShape((Pure\Component\Registry::unitsFor($unitFile)['Features']['factory'])());
if ($shape === null) {
    throw new RuntimeException('the Features factory must return a tag tree or a Pure\\Compile\\Shape.');
}
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
    strlen($compiled->source)
);
