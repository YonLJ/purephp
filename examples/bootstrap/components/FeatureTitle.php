<?php declare(strict_types=1);

require_once __DIR__ . '/Icon.php';

use Pure\Core\Raw;

use function Pure\Component\render;

/**
 * One "features with title" item.
 *
 * @param array{icon: string, title: string, content: string} $item
 */
function FeatureTitle(array $item): Raw
{
    return render(
        __DIR__ . '/FeatureTitle.shape.php',
        icon: (string)Icon('#' . $item['icon']),
        title: $item['title'],
        content: $item['content'],
    );
}
