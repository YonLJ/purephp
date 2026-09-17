<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\div;
use function Pure\HTML\h2;

/**
 * One page section template: a heading and a grid of already rendered items.
 */
return Compile::shape(
    div(
        h2(Slot::text('title'))->class('pb-2 border-bottom'),
        div(Slot::raw('contents'))->class(Slot::attr('class'))
    )->class('container px-4 py-5')
);
