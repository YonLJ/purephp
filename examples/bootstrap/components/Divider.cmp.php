<?php declare(strict_types=1);

use Pure\Component\Call;
use Pure\Component\Component;

use function Pure\Component\{component, register};
use function Pure\HTML\div;

/**
 * The divider between two page sections: `Divider()`.
 */
#[Component]
function Divider(mixed ...$children): Call
{
    return component(__FUNCTION__, ...$children);
}

/**
 * The divider between two page sections: static markup, one compiled template.
 */
register(Divider(...), static fn () =>
    div()->class('b-example-divider')
);
