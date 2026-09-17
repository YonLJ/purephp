<?php declare(strict_types=1);

require_once __DIR__ . '/Icon.php';

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Slot;

use function Pure\HTML\a;
use function Pure\HTML\div;
use function Pure\HTML\h3;
use function Pure\HTML\p;
use function Pure\SVG\svg;
use function Pure\SVG\svgUse;

function IconColumnShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            div(
                Slot::child('icon', IconShape())
            )->class('feature-icon d-inline-flex align-items-center justify-content-center text-bg-primary bg-gradient fs-2 mb-3'),
            h3(Slot::text('title'))->class('fs-2'),
            p(Slot::text('content')),
            a(
                Slot::text('linkText'),
                svg(svgUse()->href('#chevron-right'))->class('bi')->width('1em')->height('1em')
            )->href(Slot::attr('link'))->class('icon-link d-inline-flex align-items-center')
        )->class('feature col')
    );
}
