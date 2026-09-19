<?php declare(strict_types=1);
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{a, div, h3, p};

/**
 * The "features with title" main column template.
 */
register('MainFeature', __FILE__, static fn () =>
    div(
        h3(Slot::value('title'))->class('fw-bold'),
        p(Slot::value('content'))->class('text-muted'),
        a(Slot::value('linkText'))->class('btn btn-primary btn-lg')->href(Slot::value('link'))
    )->class('col d-flex flex-column align-items-start gap-2')
);

/**
 * The "features with title" main column.
 */
function MainFeature(string $title, string $content, string $link, string $linkText): string
{
    return render(
        'MainFeature',
        title: $title,
        content: $content,
        link: $link,
        linkText: $linkText,
    );
}
