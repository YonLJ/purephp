<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, img, small};

/**
 * The footer logo column template.
 */
register('ColLogo', __FILE__, static fn (): Shape => Compile::shape(
    div(
        img()->class('mb-2')->src(Slot::attr('src'))->width(Slot::attr('width'))->height(Slot::attr('height')),
        small(Slot::text('text'))->class('d-block mb-3 text-muted')
    )->class('col-12 col-md')
));

/**
 * The footer logo column.
 *
 * @param array{src: string, width: string, height: string, text: string} $logo
 */
function ColLogo(array $logo): Raw
{
    return render(
        'ColLogo',
        src: $logo['src'],
        width: $logo['width'],
        height: $logo['height'],
        text: $logo['text'],
    );
}
