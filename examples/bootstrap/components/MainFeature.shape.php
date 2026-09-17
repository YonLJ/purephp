<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\a;
use function Pure\HTML\div;
use function Pure\HTML\h3;
use function Pure\HTML\p;

/**
 * The "features with title" main column template.
 */
return Compile::shape(
    div(
        h3(Slot::text('title'))->class('fw-bold'),
        p(Slot::text('content'))->class('text-muted'),
        a(Slot::text('linkText'))->class('btn btn-primary btn-lg')->href(Slot::attr('link'))
    )->class('col d-flex flex-column align-items-start gap-2')
);
