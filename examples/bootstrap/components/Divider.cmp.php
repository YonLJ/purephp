<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Raw;

use function Pure\Component\{register, render};
use function Pure\HTML\div;

/**
 * The divider between two page sections: static markup, one compiled template.
 */
register('Divider', __FILE__, static fn (): Shape => Compile::shape(
    div()->class('b-example-divider')
));

/**
 * The divider between two page sections.
 */
function Divider(): Raw
{
    return render('Divider');
}
