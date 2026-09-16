<?php declare(strict_types=1);

/**
 * Benchmarks the classic renderer against the compiled renderer for this page.
 *
 * Usage (from this directory): php bench.php [iterations]
 */

require_once '../../vendor/autoload.php';
require_once './components/Section.php';
require_once './components/Divider.php';
require_once './components/IconColumn.php';
require_once './components/HangingIcon.php';
require_once './components/CustomCard.php';
require_once './components/CellIcon.php';
require_once './components/FeatureTitle.php';
require_once './components/MainFeature.php';
require_once './shapes.php';
require_once './data.php';

use Pure\Core\HTML;

use function Pure\HTML\div;
use function Pure\HTML\h1;
use function Pure\HTML\main;

$iters = (int)($argv[1] ?? 2000);

function classicPage(
    array $columnsData,
    array $hangingData,
    array $cardsData,
    array $gridData,
    array $featuresData
): HTML {
    return main(
        h1('Features examples')->class('visually-hidden'),
        Section([
            'title' => 'Columns with icons',
            'contents' => array_map(fn (array $data): HTML => IconColumn($data), $columnsData),
            'classList' => 'row g-4 py-5 row-cols-1 row-cols-lg-3',
        ]),
        Divider(),
        Section([
            'title' => 'Hanging icons',
            'contents' => array_map(fn (array $data): HTML => HangingIcon($data), $hangingData),
            'classList' => 'row g-4 py-5 row-cols-1 row-cols-lg-3',
        ]),
        Divider(),
        Section([
            'title' => 'Custom cards',
            'contents' => array_map(fn (array $data): HTML => CustomCard($data), $cardsData),
            'classList' => 'row row-cols-1 row-cols-lg-3 align-items-stretch g-4 py-5',
        ]),
        Divider(),
        Section([
            'title' => 'Icon grid',
            'contents' => array_map(fn (array $data): HTML => CellIcon($data), $gridData),
            'classList' => 'row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 py-5',
        ]),
        Divider(),
        Section([
            'title' => 'Features with title',
            'contents' => [
                MainFeature([
                    'title' => 'Left-aligned title explaining these awesome features',
                    'content' => "Paragraph of text beneath the heading to explain the heading. We'll add onto it with another sentence and probably just keep going until we run out of words.",
                    'link' => '#',
                    'linkText' => 'Primary button',
                ]),
                div(
                    div(
                        ...array_map(fn (array $data): HTML => FeatureTitle($data), $featuresData)
                    )->class('row row-cols-1 row-cols-sm-2 g-4')
                )->class('col'),
            ],
            'classList' => 'row row-cols-1 row-cols-md-2 align-items-md-center g-5 py-5',
        ]),
    );
}

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

$bindings = require __DIR__ . '/bindings.php';
$compiledMode = isset($argv[2]) && $argv[2] === '--compiled';

$classic = fn (): string => classicPage($columnsData, $hangingData, $cardsData, $gridData, $featuresData)->render();

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
