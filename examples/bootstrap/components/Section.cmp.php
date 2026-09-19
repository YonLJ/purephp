<?php declare(strict_types=1);
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h2};

/**
 * One page section template: a heading and a grid of already rendered items.
 */
register('Section', __FILE__, static fn () =>
    div(
        h2(Slot::value('title'))->class('pb-2 border-bottom'),
        div(Slot::raw('contents'))->class(Slot::value('class'))
    )->class('container px-4 py-5')
);

/**
 * One page section: the heading, the grid class list and the rendered items.
 */
function Section(string $title, iterable|string $contents, string $class): string
{
    return render(
        'Section',
        title: $title,
        contents: $contents,
        class: $class,
    );
}
