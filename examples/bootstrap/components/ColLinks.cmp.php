<?php declare(strict_types=1);
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{a, div, h5, li, ul};

/**
 * One footer link column template.
 */
register('ColLinks', __FILE__, static fn () =>
    div(
        h5(Slot::value('title')),
        ul(
            Slot::each('links', li(a(Slot::value('text'))->class('text-muted')->href(Slot::value('href'))))
        )->class('list-unstyled text-small')
    )->class('col-6 col-md')
);

/**
 * One footer link column.
 *
 * @param array{title: string, links: list<array{text: string, href: string}>} $column
 */
function ColLinks(array $column): string
{
    return render(
        'ColLinks',
        title: $column['title'],
        links: $column['links'],
    );
}
