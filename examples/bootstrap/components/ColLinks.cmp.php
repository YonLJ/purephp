<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{a, div, h5, li, ul};

/**
 * One footer link column template.
 */
register('ColLinks', __FILE__, static fn (): Shape => Compile::shape(
    div(
        h5(Slot::text('title')),
        ul(
            Slot::each('links', li(a(Slot::text('text'))->class('text-muted')->href(Slot::attr('href'))))
        )->class('list-unstyled text-small')
    )->class('col-6 col-md')
));

/**
 * One footer link column.
 *
 * @param array{title: string, links: list<array{text: string, href: string}>} $column
 */
function ColLinks(array $column): Raw
{
    return render(
        'ColLinks',
        title: $column['title'],
        links: $column['links'],
    );
}
