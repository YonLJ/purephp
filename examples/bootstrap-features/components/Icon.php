<?php

declare(strict_types=1);

use Pure\Core\SVG;

use function Pure\SVG\svg;
use function Pure\SVG\svgUse;

function Icon(string $icon): SVG
{
    return svg(svgUse()->href("#$icon"));
}
