<?php declare(strict_types=1);
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{a, div, h3, p};

require_once __DIR__ . '/Icon.cmp.php';

/**
 * One "hanging icons" item template.
 */
register('HangingIcon', __FILE__, static fn () =>
    div(
        div(
            Slot::raw('icon')
        )->class('icon-square text-bg-light d-inline-flex align-items-center justify-content-center fs-4 flex-shrink-0 me-3'),
        div(
            h3(Slot::value('title'))->class('fs-2'),
            p(Slot::value('content')),
            a(Slot::value('linkText'))->href(Slot::value('link'))->class('btn btn-primary')
        )
    )->class('col d-flex align-items-start')
);

/**
 * One "hanging icons" item.
 */
function HangingIcon(string $icon, string $title, string $content, string $link, string $linkText): string
{
    return render(
        'HangingIcon',
        icon: Icon('#' . $icon),
        title: $title,
        content: $content,
        link: $link,
        linkText: $linkText,
    );
}
