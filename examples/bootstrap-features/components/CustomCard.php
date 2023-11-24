<?php declare(strict_types=1);

use function Tiny\Html\div;
use function Tiny\Html\h3;
use function Tiny\Html\img;
use function Tiny\Html\li;
use function Tiny\Html\small;
use function Tiny\Html\ul;

require_once __DIR__ . '/Svg.php';

function CustomCard($props)
{
    extract($props);
    return (
        div(
            div(
                div(
                    h3($title)->class('pt-5 mt-5 mb-4 display-6 lh-1 fw-bold'),
                    ul(
                        li(
                            img()->src($icon)->alt('Bootstrap')->width('32')->height('32')->class('rounded-circle border border-white')
                        )->class('me-auto'),
                        li(
                            Svg('geo-fill')->class('bi me-2')->width('1em')->height('1em'),
                            small($location)
                        )->class('d-flex align-items-center me-3'),
                        li(
                            Svg('calendar3')->class('bi me-2')->width('1em')->height('1em'),
                            small($date)
                        )->class('d-flex align-items-center'),
                    )->class('d-flex list-unstyled mt-auto')
                )->class('d-flex flex-column h-100 p-5 pb-3 text-white text-shadow-1')
            )->class('card card-cover h-100 overflow-hidden text-bg-dark rounded-4 shadow-lg')->style("background-image: url('$bgImg');")
        )->class('col')
    );
}
