<?php declare(strict_types=1);

/**
 * Benchmarks the classic renderer, the in-process compiled shape and the
 * precompiled view artifact for this page.
 *
 * The classic baseline lives in bench/fixtures (examples are compiled-only).
 *
 * Usage: php examples/bootstrap/bench.php [iterations]
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../bench/fixtures/features/page.php';
require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/controllers/FeaturesController.php';
require_once __DIR__ . '/views/features.shape.php';

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

$classic = fn (): string => classicFeaturesPage(featuresContent())->render();

$shapeBuildStart = hrtime(true);
$page = FeaturesBodyShape();
$shapeBuild = (hrtime(true) - $shapeBuildStart) / 1000;

$compileStart = hrtime(true);
$page->compile();
$compileTime = (hrtime(true) - $compileStart) / 1000;

$requireStart = hrtime(true);
$renderer = require __DIR__ . '/views/features.pure.php';
$requireTime = (hrtime(true) - $requireStart) / 1000;

$documentStart = hrtime(true);
$document = FeaturesPageShape();
$document->compile();
$documentTime = (hrtime(true) - $documentStart) / 1000;

printf("shape build + compile: %.1f us + %.1f us (once per process)\n", $shapeBuild, $compileTime);
printf("artifact require: %.1f us (once per process) | document build + compile: %.1f us\n\n", $requireTime, $documentTime);

// A plain view is an include: age the freshly compiled file so opcache serves
// its cached op_array instead of revalidating a file whose mtime just changed.
touch(__DIR__ . '/views/features.plain.php', time() - 5);

$classicTime = bench('classic build + render', $iters, $classic);
$compiledTime = bench('compiled shape + data', $iters, fn (): string => $page($data['content']));
$artifactTime = bench('precompiled artifact + data', $iters, fn (): string => $renderer->render($data));
$plainTime = bench('plain view + data', $iters, fn (): string => plain('features', $data));

$classicOutput = $classic();
$compiledOutput = $page($data['content']);
printf(
    "\ncontent identical: %s (%d bytes) | compiled speedup: %.1fx\n",
    $classicOutput === $compiledOutput ? 'yes' : 'NO',
    strlen($compiledOutput),
    $classicTime / $compiledTime
);

$artifactOutput = $renderer->render($data);
printf(
    "artifact identical: %s | id match: %s | %d bytes | artifact vs compiled: %.2fx\n",
    $artifactOutput === $document($data) ? 'yes' : 'NO',
    $renderer->id === $document->id() ? 'yes' : 'NO',
    strlen($artifactOutput),
    $artifactTime / $compiledTime
);
printf(
    "plain identical: %s | %d bytes | plain vs artifact: %.2fx\n",
    plain('features', $data) === $renderer->header . $artifactOutput ? 'yes' : 'NO',
    strlen($artifactOutput) + strlen($renderer->header),
    $plainTime / $artifactTime
);
