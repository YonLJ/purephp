<?php declare(strict_types=1);

use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h3, p};

require_once __DIR__ . '/Icon.cmp.php';

/**
 * One "icon grid" item template.
 */
register('CellIcon', __FILE__, static fn () =>
    div(
        Slot::raw('icon'),
        div(
            h3(Slot::value('title'))->class('fw-bold mb-0 fs-4'),
            p(Slot::value('content'))
        )
    )->class('col d-flex align-items-start')
);

/**
 * One "icon grid" item.
 *
 * @param array{icon: string, title: string, content: string} $item
 */
function CellIcon(array $item): string
{
    return render(
        'CellIcon',
        icon: Icon('#' . $item['icon'], 'bi text-muted flex-shrink-0 me-3', '1.75em', '1.75em'),
        title: $item['title'],
        content: $item['content'],
    );
}
