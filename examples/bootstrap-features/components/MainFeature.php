<?php

use function Tiny\a;
use function Tiny\div;
use function Tiny\h3;
use function Tiny\p;

function MainFeature($props)
{
    extract($props);
    return (
        div(
            h3($title)->class('fw-bold'),
            p($content)->class('text-muted'),
            a($linkText)->class('btn btn-primary btn-lg')->href($link)
        )->class('col d-flex flex-column align-items-start gap-2')
    );
}
