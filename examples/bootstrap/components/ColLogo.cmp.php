<?php declare(strict_types=1);
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, img, small};

/**
 * The footer logo column template.
 */
register('ColLogo', __FILE__, static fn () =>
    div(
        img()->class('mb-2')->src(Slot::value('src'))->width(Slot::value('width'))->height(Slot::value('height')),
        small(Slot::value('text'))->class('d-block mb-3 text-muted')
    )->class('col-12 col-md')
);

/**
 * The footer logo column.
 *
 * @param array{src: string, width: string, height: string, text: string} $logo
 */
function ColLogo(array $logo): string
{
    return render(
        'ColLogo',
        src: $logo['src'],
        width: $logo['width'],
        height: $logo['height'],
        text: $logo['text'],
    );
}
