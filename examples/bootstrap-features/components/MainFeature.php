<?php declare(strict_types=1);

use function Tiny\Html\a;
use function Tiny\Html\div;
use function Tiny\Html\h3;
use function Tiny\Html\p;

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
