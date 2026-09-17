<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\div;
use function Pure\HTML\h1;
use function Pure\HTML\p;

/**
 * The pricing page heading template.
 */
return Compile::shape(
    div(
        h1(Slot::text('title'))->class('display-4'),
        p(Slot::text('desc'))->class('lead')
    )->class('pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center')
);
