<?php declare(strict_types=1);

require_once __DIR__ . '/Icon.php';

use Pure\Core\Raw;

use function Pure\Component\render;

/**
 * One "hanging icons" item.
 *
 * @param array{icon: string, title: string, content: string, link: string, linkText: string} $item
 */
function HangingIcon(array $item): Raw
{
    return render(
        __DIR__ . '/HangingIcon.shape.php',
        icon: (string)Icon('#' . $item['icon']),
        title: $item['title'],
        content: $item['content'],
        link: $item['link'],
        linkText: $item['linkText'],
    );
}
