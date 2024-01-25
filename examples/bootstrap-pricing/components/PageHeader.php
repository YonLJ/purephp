<?php declare(strict_types=1);

use Pure\Core\HTML;
use function Pure\HTML\div;
use function Pure\HTML\h5;
use function Pure\HTML\nav;
use function Pure\HTML\a;

function PageHeader($props): HTML
{
    [
        'companyName' => $companyName,
        'navs' => $navs,
        'signUp' => $signUp
    ] = $props;

    return div(
        h5($companyName)->class('my-0 mr-md-auto font-weight-normal'),
        nav(
            array_map(fn($nav) => a($nav['text'])->class('p-2 text-dark')->href($nav['href']), $navs),
        )->class('my-2 my-md-0 mr-md-3'),
        a($signUp['text'])->class('btn btn-outline-primary')->href($signUp['href'])
    )->class('d-flex flex-column flex-md-row align-items-center p-3 px-md-4 mb-3 bg-white border-bottom box-shadow');
}
