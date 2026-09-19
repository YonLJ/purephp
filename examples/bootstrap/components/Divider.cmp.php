<?php declare(strict_types=1);

use function Pure\Component\{register, render};
use function Pure\HTML\div;

/**
 * The divider between two page sections: static markup, one compiled template.
 */
register('Divider', __FILE__, static fn () =>
    div()->class('b-example-divider')
);

/**
 * The divider between two page sections.
 */
function Divider(): string
{
    return render('Divider');
}
