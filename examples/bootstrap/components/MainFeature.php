<?php declare(strict_types=1);

use Pure\Core\Raw;

use function Pure\Component\render;

/**
 * The "features with title" main column.
 *
 * @param array{title: string, content: string, link: string, linkText: string} $main
 */
function MainFeature(array $main): Raw
{
    return render(
        __DIR__ . '/MainFeature.shape.php',
        title: $main['title'],
        content: $main['content'],
        link: $main['link'],
        linkText: $main['linkText'],
    );
}
