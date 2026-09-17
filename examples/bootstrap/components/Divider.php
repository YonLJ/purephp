<?php declare(strict_types=1);

use Pure\Core\HTML;

use function Pure\HTML\div;

/**
 * The divider between two page sections.
 */
function DividerShape(): HTML
{
    return div()->class('b-example-divider');
}
