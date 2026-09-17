<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\a;
use function Pure\HTML\div;
use function Pure\HTML\h3;
use function Pure\HTML\p;
use function Pure\SVG\svg;
use function Pure\SVG\svgUse;

/**
 * One "columns with icons" item template.
 */
return Compile::shape(
    div(
        div(
            Slot::raw('icon')
        )->class('feature-icon d-inline-flex align-items-center justify-content-center text-bg-primary bg-gradient fs-2 mb-3'),
        h3(Slot::text('title'))->class('fs-2'),
        p(Slot::text('content')),
        a(
            Slot::text('linkText'),
            svg(svgUse()->href('#chevron-right'))->class('bi')->width('1em')->height('1em')
        )->href(Slot::attr('link'))->class('icon-link d-inline-flex align-items-center')
    )->class('feature col')
);
