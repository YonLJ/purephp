<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\a;
use function Pure\HTML\div;
use function Pure\HTML\h3;
use function Pure\HTML\p;

/**
 * One "hanging icons" item template.
 */
return Compile::shape(
    div(
        div(
            Slot::raw('icon')
        )->class('icon-square text-bg-light d-inline-flex align-items-center justify-content-center fs-4 flex-shrink-0 me-3'),
        div(
            h3(Slot::text('title'))->class('fs-2'),
            p(Slot::text('content')),
            a(Slot::text('linkText'))->href(Slot::attr('link'))->class('btn btn-primary')
        )
    )->class('col d-flex align-items-start')
);
