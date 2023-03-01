<?php

use function Tiny\div;
use function Tiny\h3;
use function Tiny\p;
use function Tiny\a;

require_once __DIR__ . '/Svg.php';

function HangingIcon($props)
{
    extract($props);
    return (
        div(
            div(
                Svg($icon)->class('bi')->width('1em')->height('1em')
            )->class('icon-square text-bg-light d-inline-flex align-items-center justify-content-center fs-4 flex-shrink-0 me-3'),
            div(
                h3($title)->class('fs-2'),
                p($content),
                a($linkText)->href($link)->class('btn btn-primary')
            )
        )->class('col d-flex align-items-start')
    );
}
