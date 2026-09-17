<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h3, p};

require_once __DIR__ . '/Icon.cmp.php';

/**
 * One "icon grid" item template.
 */
register('CellIcon', __FILE__, static fn (): Shape => Compile::shape(
    div(
        Slot::raw('icon'),
        div(
            h3(Slot::text('title'))->class('fw-bold mb-0 fs-4'),
            p(Slot::text('content'))
        )
    )->class('col d-flex align-items-start')
));

/**
 * One "icon grid" item.
 *
 * @param array{icon: string, title: string, content: string} $item
 */
function CellIcon(array $item): Raw
{
    return render(
        'CellIcon',
        icon: (string)Icon('#' . $item['icon'], 'bi text-muted flex-shrink-0 me-3', '1.75em', '1.75em'),
        title: $item['title'],
        content: $item['content'],
    );
}
