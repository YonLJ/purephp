<?php declare(strict_types=1);

use Pure\Core\Raw;

use function Pure\Component\render;

/**
 * One navigation link with its own class list.
 */
function NavLink(string $text, string $href, string $class = ''): Raw
{
    return render(
        __DIR__ . '/NavLink.shape.php',
        text: $text,
        href: $href,
        class: $class,
    );
}
