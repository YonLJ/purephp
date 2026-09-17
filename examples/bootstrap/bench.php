<?php declare(strict_types=1);

/**
 * Benchmarks the classic renderer and the function components of this page:
 * the page function, the precompiled skeleton artifact with precomputed
 * bindings, and the plain view.
 *
 * The classic baseline lives in bench/fixtures (examples are compiled-only).
 *
 * Usage: php examples/bootstrap/bench.php [iterations]
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../bench/fixtures/features/page.php';
require_once __DIR__ . '/app/controllers/FeaturesController.php';

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
$data = featuresData();

// One-time costs: the page skeleton compiles once per process, the artifact is
// loaded once, and the component functions compile on their first render.
$shapeStart = hrtime(true);
$pageShape = require __DIR__ . '/views/features.shape.php';
$pageShape->compile();
$shapeTime = (hrtime(true) - $shapeStart) / 1000;

$requireStart = hrtime(true);
$renderer = require __DIR__ . '/views/features.pure.php';
$requireTime = (hrtime(true) - $requireStart) / 1000;

$firstStart = hrtime(true);
$first = (string)featuresPage($data);
$firstTime = (hrtime(true) - $firstStart) / 1000;

printf("page shape + compile: %.1f us (once) | artifact require: %.1f us (once)\n", $shapeTime, $requireTime);
printf("page function first render (compiles components): %.1f us\n\n", $firstTime);

// A plain view is an include: age the freshly compiled file so opcache serves
// its cached op_array instead of revalidating a file whose mtime just changed.
touch(__DIR__ . '/views/features.plain.php', time() - 5);

$bindings = featuresBindings($data);
$classic = fn (): string => classicFeaturesPage(featuresContent())->render();

$classicTime = bench('classic build + render', $iters, $classic);
$pageTime = bench('page function (components + artifact)', $iters, fn (): string => (string)featuresPage($data));
$artifactTime = bench('skeleton artifact + bindings', $iters, fn (): string => $renderer->render($bindings));
$plainTime = bench('plain view + bindings', $iters, fn (): string => plain('features', $bindings));

$document = $renderer->header . $renderer->render($bindings);
printf(
    "\npage identical: %s (%d bytes) | page vs classic: %.2fx\n",
    $first === $document ? 'yes' : 'NO',
    strlen($first),
    $classicTime / $pageTime
);
printf(
    "skeleton identical: %s | %d bytes | artifact vs page: %.2fx\n",
    $renderer->render($bindings) === substr($document, strlen($renderer->header)) ? 'yes' : 'NO',
    strlen($document),
    $artifactTime / $pageTime
);
printf(
    "plain identical: %s | plain vs artifact: %.2fx\n",
    plain('features', $bindings) === $document ? 'yes' : 'NO',
    $plainTime / $artifactTime
);
