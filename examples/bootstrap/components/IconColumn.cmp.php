<?php declare(strict_types=1);
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{a, div, h3, p};
use function Pure\SVG\{svg, svgUse};

require_once __DIR__ . '/Icon.cmp.php';

/**
 * One "columns with icons" item template.
 */
register('IconColumn', __FILE__, static fn () =>
    div(
        div(
            Slot::raw('icon')
        )->class('feature-icon d-inline-flex align-items-center justify-content-center text-bg-primary bg-gradient fs-2 mb-3'),
        h3(Slot::value('title'))->class('fs-2'),
        p(Slot::value('content')),
        a(
            Slot::value('linkText'),
            svg(svgUse()->href('#chevron-right'))->class('bi')->width('1em')->height('1em')
        )->href(Slot::value('link'))->class('icon-link d-inline-flex align-items-center')
    )->class('feature col')
);

/**
 * One "columns with icons" item.
 */
function IconColumn(string $icon, string $title, string $content, string $link, string $linkText): string
{
    return render(
        'IconColumn',
        icon: Icon('#' . $icon),
        title: $title,
        content: $content,
        link: $link,
        linkText: $linkText,
    );
}
