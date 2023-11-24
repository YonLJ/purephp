<?php declare(strict_types=1);

use function Tiny\Html\a;
use function Tiny\Html\div;
use function Tiny\Html\h3;
use function Tiny\Html\p;

require_once __DIR__ . '/Svg.php';

function IconColumn($props)
{
    extract($props);
    return (
        div(
            div(
                Svg($icon)->class('bi')->width('1em')->height('1em')
            )->class('feature-icon d-inline-flex align-items-center justify-content-center text-bg-primary bg-gradient fs-2 mb-3'),
            h3($title)->class('fs-2'),
            p($content),
            a(
                $linkText,
                Svg('chevron-right')->class('bi')->width('1em')->height('1em'),
            )->href($link)->class('icon-link d-inline-flex align-items-center')
        )->class('feature col')
    );
}
