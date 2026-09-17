<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Slot;

use function Pure\SVG\svg;
use function Pure\SVG\svgUse;

function IconShape(string $class = 'bi', string $width = '1em', string $height = '1em'): Shape
{
    static $shapes = [];
    $key = $class . '|' . $width . '|' . $height;

    return $shapes[$key] ??= Compile::shape(
        svg(svgUse()->href(Slot::attr('href')))->class($class)->width($width)->height($height)
    );
}
