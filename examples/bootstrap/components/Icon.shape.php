<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\SVG\svg;
use function Pure\SVG\svgUse;

/**
 * The icon template: a `<use>` reference into the SVG symbol sheet. The class
 * and the size come from the data, so one template serves every icon.
 */
return Compile::shape(
    svg(svgUse()->href(Slot::attr('href')))
        ->class(Slot::attr('class'))
        ->width(Slot::attr('width'))
        ->height(Slot::attr('height'))
);
