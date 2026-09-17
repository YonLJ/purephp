<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Slot;

use function Pure\HTML\a;
use function Pure\HTML\div;
use function Pure\HTML\h5;
use function Pure\HTML\nav;

/**
 * Nav / sign-up link shape: bindings provide text, href and class.
 */
function NavLinkShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        a(Slot::text('text'))->class(Slot::attr('class'))->href(Slot::attr('href'))
    );
}

function PageHeaderShape(string $companyName): Shape
{
    static $shapes = [];

    return $shapes[$companyName] ??= Compile::shape(
        div(
            h5($companyName)->class('my-0 mr-md-auto font-weight-normal'),
            nav(Slot::each('navs', NavLinkShape()))->class('my-2 my-md-0 mr-md-3'),
            Slot::child('signUp', NavLinkShape())
        )->class('d-flex flex-column flex-md-row align-items-center p-3 px-md-4 mb-3 bg-white border-bottom box-shadow')
    );
}
