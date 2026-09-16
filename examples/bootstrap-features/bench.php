<?php declare(strict_types=1);

/**
 * Benchmarks the classic renderer against the compiled renderer for this page.
 *
 * The classic baseline lives in bench/fixtures (examples are compiled-only).
 *
 * Usage (from this directory): php bench.php [iterations]
 */

require_once '../../vendor/autoload.php';
require_once '../../bench/fixtures/bootstrap-features/page.php';
require_once './data.php';
require_once './shapes.php';

function bench(string $label, int $iters, callable $fn): float
{
    $fn();
    $start = hrtime(true);
    for ($i = 0; $i < $iters; $i++) {
        $fn();
    }
    $per = (hrtime(true) - $start) / $iters / 1000;

    printf("%-34s %8.1f us/op\n", $label, $per);

    return $per;
}

$iters = (int)($argv[1] ?? 2000);
$bindings = require __DIR__ . '/bindings.php';

$classic = fn (): string => classicFeaturesPage($columnsData, $hangingData, $cardsData, $gridData, $featuresData)->render();

$shapeBuildStart = hrtime(true);
$page = FeaturePageShape();
$shapeBuild = (hrtime(true) - $shapeBuildStart) / 1000;

$compileStart = hrtime(true);
$page->compile();
$compileTime = (hrtime(true) - $compileStart) / 1000;

printf(
    "shape build + compile: %.1f us + %.1f us (once per process)\n\n",
    $shapeBuild,
    $compileTime
);

$classicTime = bench('classic build + render', $iters, $classic);
$compiledTime = bench('compiled shape + data', $iters, fn (): string => $page($bindings));

$classicOutput = $classic();
$compiledOutput = $page($bindings);
printf(
    "\noutput identical: %s (%d bytes) | speedup: %.1fx\n",
    $classicOutput === $compiledOutput ? 'yes' : 'NO',
    strlen($classicOutput),
    $classicTime / $compiledTime
);
