<?php declare(strict_types=1);

use Pure\Core\Raw;

use function Pure\HTML\div;

/**
 * The divider between two page sections: static markup, rendered once.
 */
function Divider(): Raw
{
    static $divider;

    return $divider ??= Raw::of(div()->class('b-example-divider')->render());
}
