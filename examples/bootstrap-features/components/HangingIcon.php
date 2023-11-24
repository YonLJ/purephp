<?php declare(strict_types=1);

use function Tiny\Html\div;
use function Tiny\Html\h3;
use function Tiny\Html\p;
use function Tiny\Html\a;

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
