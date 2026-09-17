<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\div;
use function Pure\HTML\img;
use function Pure\HTML\small;

/**
 * The footer logo column template.
 */
return Compile::shape(
    div(
        img()->class('mb-2')->src(Slot::attr('src'))->width(Slot::attr('width'))->height(Slot::attr('height')),
        small(Slot::text('text'))->class('d-block mb-3 text-muted')
    )->class('col-12 col-md')
);
