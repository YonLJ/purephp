<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Slot;

use function Pure\HTML\div;
use function Pure\HTML\h2;

function SectionShape(Shape $item, string $classList): Shape
{
    static $shapes = [];
    $key = $classList . '|' . $item->id();

    return $shapes[$key] ??= Compile::shape(
        div(
            h2(Slot::text('title'))->class('pb-2 border-bottom'),
            div(Slot::each('contents', $item))->class($classList)
        )->class('container px-4 py-5')
    );
}
