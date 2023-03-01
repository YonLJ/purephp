<?php

use function Tiny\div;
use function Tiny\h2;

function Section($props)
{
    extract($props);
    return (
        div(
            h2($title)->class('pb-2 border-bottom'),
            div(...$contents)->class($classList)
        )->class('container px-4 py-5')
    );
}
