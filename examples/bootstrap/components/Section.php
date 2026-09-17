<?php declare(strict_types=1);

use Pure\Core\Raw;

use function Pure\Component\render;

/**
 * One page section: the heading, the grid class list and the rendered items.
 */
function Section(string $title, Raw $contents, string $class): Raw
{
    return render(
        __DIR__ . '/Section.shape.php',
        title: $title,
        contents: $contents,
        class: $class,
    );
}
