<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Slot;

use function Pure\HTML\a;
use function Pure\HTML\div;
use function Pure\HTML\h5;
use function Pure\HTML\li;
use function Pure\HTML\ul;

function FooterLinkShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        li(a(Slot::text('text'))->class('text-muted')->href(Slot::attr('href')))
    );
}

function ColLinksShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            h5(Slot::text('title')),
            ul(Slot::each('links', FooterLinkShape()))->class('list-unstyled text-small')
        )->class('col-6 col-md')
    );
}
