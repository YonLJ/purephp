<?php declare(strict_types=1);
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\SVG\{svg, svgUse};

/**
 * The icon template: a `<use>` reference into the SVG symbol sheet. The class
 * and the size come from the data, so one template serves every icon.
 */
register('Icon', __FILE__, static fn () =>
    svg(svgUse()->href(Slot::value('href')))
        ->class(Slot::value('class'))
        ->width(Slot::value('width'))
        ->height(Slot::value('height'))
);

/**
 * An icon that references the SVG symbol sheet: `Icon('#home')` renders
 * `<svg class="bi" ...><use href="#home" /></svg>`.
 */
function Icon(string $href, string $class = 'bi', string $width = '1em', string $height = '1em'): string
{
    return render(
        'Icon',
        href: $href,
        class: $class,
        width: $width,
        height: $height,
    );
}
