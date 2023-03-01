<?php

use function Tiny\div;
use function Tiny\h4;
use function Tiny\p;

require_once __DIR__ . '/Svg.php';

function FeatureTitle($props)
{
    extract($props);
    return (
        div(
            div(
                Svg($icon)->class('bi')->width('1em')->height('1em')
            )->class('feature-icon-small d-inline-flex align-items-center justify-content-center text-bg-primary bg-gradient fs-4 rounded-3'),
            h4($title)->class('fw-semibold mb-0'),
            p($content)->class('text-muted')
        )->class('col d-flex flex-column gap-2')
    );
}
