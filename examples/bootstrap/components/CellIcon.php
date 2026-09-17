<?php declare(strict_types=1);

require_once __DIR__ . '/Icon.php';

use Pure\Core\Raw;

use function Pure\Component\render;

/**
 * One "icon grid" item.
 *
 * @param array{icon: string, title: string, content: string} $item
 */
function CellIcon(array $item): Raw
{
    return render(
        __DIR__ . '/CellIcon.shape.php',
        icon: (string)Icon('#' . $item['icon'], 'bi text-muted flex-shrink-0 me-3', '1.75em', '1.75em'),
        title: $item['title'],
        content: $item['content'],
    );
}
