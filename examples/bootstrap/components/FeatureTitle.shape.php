<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\div;
use function Pure\HTML\h4;
use function Pure\HTML\p;

/**
 * One "features with title" item template.
 */
return Compile::shape(
    div(
        div(
            Slot::raw('icon')
        )->class('feature-icon-small d-inline-flex align-items-center justify-content-center text-bg-primary bg-gradient fs-4 rounded-3'),
        h4(Slot::text('title'))->class('fw-semibold mb-0'),
        p(Slot::text('content'))->class('text-muted')
    )->class('col d-flex flex-column gap-2')
);
