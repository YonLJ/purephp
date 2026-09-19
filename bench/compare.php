<?php

declare(strict_types=1);

/**
 * Compiled renderer benchmark.
 *
 * Usage: php bench/compare.php [--rows=200] [--iters=3000]
 */

require __DIR__ . '/../vendor/autoload.php';

use Pure\Compile\Compile;
use Pure\Core\HTML;
use Pure\Core\Slot;
use Pure\Compile\Shape;

use function Pure\HTML\div;
use function Pure\HTML\p;
use function Pure\HTML\span;

$options = getopt('', ['rows::', 'iters::']);
$rows = (int)($options['rows'] ?? 200);
$iters = (int)($options['iters'] ?? 3000);

/** @return array<int, array{id: int, title: string, text: string}> */
function makeData(int $rows): array
{
    $data = [];
    for ($i = 1; $i <= $rows; $i++) {
        $data[] = ['id' => $i, 'title' => 'Item ' . $i, 'text' => 'Some text & more < ' . $i];
    }

    return $data;
}

/** @param array<int, array{id: int, title: string, text: string}> $rows */
function buildPage(array $rows): HTML
{
    $items = [];
    foreach ($rows as $row) {
        $items[] = div(
            span($row['title'])->class('title'),
            p($row['text'])->class('text')
        )->class('row')->id('row-' . $row['id'])->data_index((string)$row['id']);
    }

    return div(
        div('Header')->class('head'),
        div(...$items)->class('body'),
        div('Footer')->class('foot')
    )->class('page');
}

function pageShape(): Shape
{
    $item = Compile::shape(
        div(
            span(Slot::value('title'))->class('title'),
            p(Slot::value('text'))->class('text')
        )->class('row')->id(Slot::value('id'))->data_index(Slot::value('index'))
    );

    return Compile::shape(
        div(
            div('Header')->class('head'),
            div(Slot::each('rows', $item))->class('body'),
            div('Footer')->class('foot')
        )->class('page')
    );
}

function bench(string $label, int $iters, callable $fn): float
{
    $fn();
    $start = hrtime(true);
    for ($i = 0; $i < $iters; $i++) {
        $fn();
    }
    $perOp = (hrtime(true) - $start) / $iters;

    printf("%-40s %9.1f us/op\n", $label, $perOp / 1000);

    return $perOp;
}

$data = makeData($rows);
$bindings = ['rows' => array_map(
    static fn (array $row): array => ['title' => $row['title'], 'text' => $row['text'], 'id' => 'row-' . $row['id'], 'index' => (string)$row['id']],
    $data
)];

$shape = pageShape();
$staticTree = buildPage($data);
$staticCompiled = Compile::shape($staticTree);

$baseline = bench("build + render (current)", $iters, fn (): string => buildPage($data)->render());
$shaped = bench("compiled shape + data (new)", $iters, fn (): string => $shape($bindings));
$staticRender = bench("render only (same tree reused)", $iters, fn (): string => $staticTree->render());
$staticCompiledTime = bench("compiled static tree (literal)", $iters, fn (): string => $staticCompiled([]));

$shapedOutput = $shape($bindings);
$baselineOutput = $staticTree->render();
printf(
    "\nrows=%d elements~%d output=%d bytes  identical=%s\n",
    $rows,
    $rows * 3 + 4,
    strlen($baselineOutput),
    $shapedOutput === $baselineOutput ? 'yes' : 'NO'
);
printf(
    "speedup: end-to-end %.1fx | vs render-only %.1fx | static compiled %.1fx\n",
    $baseline / $shaped,
    $staticRender / $shaped,
    $baseline / $staticCompiledTime
);
