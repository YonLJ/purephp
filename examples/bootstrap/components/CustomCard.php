<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Slot;

use function Pure\HTML\div;
use function Pure\HTML\h3;
use function Pure\HTML\img;
use function Pure\HTML\li;
use function Pure\HTML\small;
use function Pure\HTML\ul;
use function Pure\SVG\svg;
use function Pure\SVG\svgUse;

function CustomCardShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            div(
                div(
                    h3(Slot::text('title'))->class('pt-5 mt-5 mb-4 display-6 lh-1 fw-bold'),
                    ul(
                        li(
                            img()->src(Slot::attr('icon'))->alt('Bootstrap')->width('32')->height('32')->class('rounded-circle border border-white')
                        )->class('me-auto'),
                        li(
                            svg(svgUse()->href('#geo-fill'))->class('bi me-2')->width('1em')->height('1em'),
                            small(Slot::text('location'))
                        )->class('d-flex align-items-center me-3'),
                        li(
                            svg(svgUse()->href('#calendar3'))->class('bi me-2')->width('1em')->height('1em'),
                            small(Slot::text('date'))
                        )->class('d-flex align-items-center'),
                    )->class('d-flex list-unstyled mt-auto')
                )->class('d-flex flex-column h-100 p-5 pb-3 text-white text-shadow-1')
            )->class('card card-cover h-100 overflow-hidden text-bg-dark rounded-4 shadow-lg')->style(Slot::attr('style'))
        )->class('col')
    );
}
