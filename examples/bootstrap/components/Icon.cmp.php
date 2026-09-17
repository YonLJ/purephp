<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\SVG\{svg, svgUse};

/**
 * The icon template: a `<use>` reference into the SVG symbol sheet. The class
 * and the size come from the data, so one template serves every icon.
 */
register('Icon', __FILE__, static fn (): Shape => Compile::shape(
    svg(svgUse()->href(Slot::attr('href')))
        ->class(Slot::attr('class'))
        ->width(Slot::attr('width'))
        ->height(Slot::attr('height'))
));

/**
 * An icon that references the SVG symbol sheet: `Icon('#home')` renders
 * `<svg class="bi" ...><use href="#home" /></svg>`.
 */
function Icon(string $href, string $class = 'bi', string $width = '1em', string $height = '1em'): Raw
{
    return render(
        'Icon',
        href: $href,
        class: $class,
        width: $width,
        height: $height,
    );
}
