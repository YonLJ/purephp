<?php

use function Tiny\div;
use function Tiny\h3;
use function Tiny\p;

require_once __DIR__ . '/Svg.php';

function CellIcon($props)
{
    extract($props);
    return (
        div(
            Svg($icon)->class('bi text-muted flex-shrink-0 me-3')->width('1.75em')->height('1.75em'),
            div(
                h3($title)->class('fw-bold mb-0 fs-4'),
                p($content)
            )
        )->class('col d-flex align-items-start')
    );
}
