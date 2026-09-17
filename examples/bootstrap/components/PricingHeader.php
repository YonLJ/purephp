<?php declare(strict_types=1);

use Pure\Core\Raw;

use function Pure\Component\render;

/**
 * The pricing page heading.
 */
function PricingHeader(string $title, string $desc): Raw
{
    return render(
        __DIR__ . '/PricingHeader.shape.php',
        title: $title,
        desc: $desc,
    );
}
