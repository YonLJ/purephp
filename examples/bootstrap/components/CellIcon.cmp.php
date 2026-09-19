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
 */
function CellIcon(string $icon, string $title, string $content): string
{
    return render(
        'CellIcon',
        icon: Icon(
            href: '#' . $icon,
            class: 'bi text-muted flex-shrink-0 me-3',
            width: '1.75em',
            height: '1.75em'
        ),
        title: $title,
        content: $content,
    );
}
