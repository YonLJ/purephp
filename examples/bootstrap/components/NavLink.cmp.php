<?php declare(strict_types=1);
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\a;

/**
 * One navigation link; the class comes from the data, so the same template
 * serves the header links and the sign-up button.
 */
register('NavLink', __FILE__, static fn () =>
    a(Slot::value('text'))->class(Slot::value('class'))->href(Slot::value('href'))
);

/**
 * One navigation link with its own class list.
 */
function NavLink(string $text, string $href, string $class = ''): string
{
    return render(
        'NavLink',
        text: $text,
        href: $href,
        class: $class,
    );
}
