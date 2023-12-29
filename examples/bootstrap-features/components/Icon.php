<?php

declare(strict_types=1);

use Tiny\Core\SVG;

use function Tiny\Tags\SVG\svg;
use function Tiny\Tags\SVG\svgUse;

function Icon(string $icon): SVG
{
    return svg(svgUse()->href("#$icon"));
}
