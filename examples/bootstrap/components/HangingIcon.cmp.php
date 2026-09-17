<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{a, div, h3, p};

require_once __DIR__ . '/Icon.cmp.php';

/**
 * One "hanging icons" item template.
 */
register('HangingIcon', __FILE__, static fn (): Shape => Compile::shape(
    div(
        div(
            Slot::raw('icon')
        )->class('icon-square text-bg-light d-inline-flex align-items-center justify-content-center fs-4 flex-shrink-0 me-3'),
        div(
            h3(Slot::text('title'))->class('fs-2'),
            p(Slot::text('content')),
            a(Slot::text('linkText'))->href(Slot::attr('link'))->class('btn btn-primary')
        )
    )->class('col d-flex align-items-start')
));

/**
 * One "hanging icons" item.
 *
 * @param array{icon: string, title: string, content: string, link: string, linkText: string} $item
 */
function HangingIcon(array $item): Raw
{
    return render(
        'HangingIcon',
        icon: (string)Icon('#' . $item['icon']),
        title: $item['title'],
        content: $item['content'],
        link: $item['link'],
        linkText: $item['linkText'],
    );
}
