<?php declare(strict_types=1);
use Pure\Component\Call;
use Pure\Core\Slot;

use function Pure\Component\{component, register};
use function Pure\HTML\a;

/**
 * One navigation link; the class comes from the data — with a default, so the
 * same template serves the header links and the sign-up button.
 */
register('NavLink', __FILE__, static fn () =>
    a(Slot::value('text'))->class(Slot::value('class')->default(''))->href(Slot::value('href'))
);

/**
 * One navigation link with its own class list: `NavLink()->text('Home')->href('/')`.
 */
function NavLink(mixed ...$children): Call
{
    return component('NavLink', ...$children);
}
