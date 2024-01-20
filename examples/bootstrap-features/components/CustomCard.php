<?php declare(strict_types=1);

use Pure\Core\HTML;

use function Pure\HTML\div;
use function Pure\HTML\h3;
use function Pure\HTML\img;
use function Pure\HTML\li;
use function Pure\HTML\small;
use function Pure\HTML\ul;

require_once __DIR__ . '/Icon.php';

function CustomCard(array $props): HTML
{
    [
        'title' => $title,
        'icon' => $icon,
        'location' => $location,
        'date' => $date,
        'bgImg' => $bgImg
    ] = $props;

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
                            Icon('geo-fill')->class('bi me-2')->width('1em')->height('1em'),
                            small($location)
                        )->class('d-flex align-items-center me-3'),
                        li(
                            Icon('calendar3')->class('bi me-2')->width('1em')->height('1em'),
                            small($date)
                        )->class('d-flex align-items-center'),
                    )->class('d-flex list-unstyled mt-auto')
                )->class('d-flex flex-column h-100 p-5 pb-3 text-white text-shadow-1')
            )->class('card card-cover h-100 overflow-hidden text-bg-dark rounded-4 shadow-lg')->style("background-image: url('$bgImg');")
        )->class('col')
    );
}
