<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{a, div, h3, p};

/**
 * The "features with title" main column template.
 */
register('MainFeature', __FILE__, static fn (): Shape => Compile::shape(
    div(
        h3(Slot::text('title'))->class('fw-bold'),
        p(Slot::text('content'))->class('text-muted'),
        a(Slot::text('linkText'))->class('btn btn-primary btn-lg')->href(Slot::attr('link'))
    )->class('col d-flex flex-column align-items-start gap-2')
));

/**
 * The "features with title" main column.
 *
 * @param array{title: string, content: string, link: string, linkText: string} $main
 */
function MainFeature(array $main): Raw
{
    return render(
        'MainFeature',
        title: $main['title'],
        content: $main['content'],
        link: $main['link'],
        linkText: $main['linkText'],
    );
}
