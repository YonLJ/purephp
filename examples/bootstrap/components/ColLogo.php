<?php declare(strict_types=1);

use Pure\Core\Raw;

use function Pure\Component\render;

/**
 * The footer logo column.
 *
 * @param array{src: string, width: string, height: string, text: string} $logo
 */
function ColLogo(array $logo): Raw
{
    return render(
        __DIR__ . '/ColLogo.shape.php',
        src: $logo['src'],
        width: $logo['width'],
        height: $logo['height'],
        text: $logo['text'],
    );
}
