<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\a;

/**
 * One navigation link; the class comes from the data, so the same template
 * serves the header links and the sign-up button.
 */
return Compile::shape(
    a(Slot::text('text'))->class(Slot::attr('class'))->href(Slot::attr('href'))
);
