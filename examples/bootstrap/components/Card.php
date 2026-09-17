<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Slot;

use function Pure\HTML\button;
use function Pure\HTML\div;
use function Pure\HTML\h1;
use function Pure\HTML\h4;
use function Pure\HTML\li;
use function Pure\HTML\small;
use function Pure\HTML\ul;

function FeatureItemShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(li(Slot::text('value')));
}

function CardShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            div(
                h4(Slot::text('type'))->class('my-0 font-weight-normal')
            )->class('card-header'),
            div(
                h1('$', Slot::text('price'), ' ', small('/ mo')->class('text-muted'))->class('card-title pricing-card-title'),
                ul(Slot::each('features', FeatureItemShape()))->class('list-unstyled mt-3 mb-4'),
                button(Slot::text('text'))->type('button')->class(Slot::attr('class'))
            )->class('card-body')
        )->class('card mb-4 box-shadow')
    );
}
