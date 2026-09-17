<?php declare(strict_types=1);

use Pure\Core\Raw;

use function Pure\Component\render;

/**
 * One footer link column.
 *
 * @param array{title: string, links: list<array{text: string, href: string}>} $column
 */
function ColLinks(array $column): Raw
{
    return render(
        __DIR__ . '/ColLinks.shape.php',
        title: $column['title'],
        links: $column['links'],
    );
}
