<?php declare(strict_types=1);

use Pure\Core\Raw;

use function Pure\Component\render;

/**
 * An icon that references the SVG symbol sheet: `Icon('#home')` renders
 * `<svg class="bi" ...><use href="#home" /></svg>`.
 */
function Icon(string $href, string $class = 'bi', string $width = '1em', string $height = '1em'): Raw
{
    return render(
        __DIR__ . '/Icon.shape.php',
        href: $href,
        class: $class,
        width: $width,
        height: $height,
    );
}
