<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\div;
use function Pure\HTML\h3;
use function Pure\HTML\p;

/**
 * One "icon grid" item template.
 */
return Compile::shape(
    div(
        Slot::raw('icon'),
        div(
            h3(Slot::text('title'))->class('fw-bold mb-0 fs-4'),
            p(Slot::text('content'))
        )
    )->class('col d-flex align-items-start')
);
