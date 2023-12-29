<?php

declare(strict_types=1);

use Pure\Core\SVG;

use function Pure\Tags\SVG\svg;
use function Pure\Tags\SVG\svgUse;

function Icon(string $icon): SVG
{
    return svg(svgUse()->href("#$icon"));
}
