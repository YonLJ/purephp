<?php declare(strict_types=1);

use Pure\Core\HTML;
use function Pure\HTML\button;
use function Pure\HTML\div;
use function Pure\HTML\h1;
use function Pure\HTML\h4;
use function Pure\HTML\li;
use function Pure\HTML\small;
use function Pure\HTML\ul;

function Card($props): HTML
{
    [
        'type' => $type,
        'price' => $price,
        'features' => $features,
        'button' => $button
    ] = $props;

    return div(
        div(
            h4(
                $type
            )->class('my-0 font-weight-normal')
        )->class('card-header'),
        div(
            h1(
                "\$$price ",
                small('/ mo')->class('text-muted')
            )->class('card-title pricing-card-title'),
            ul(
                array_map(fn($feature) => li($feature), $features)
            )->class('list-unstyled mt-3 mb-4'),
            button($button['text'])->type('button')->class("btn btn-lg btn-block {$button['class']}")
        )->class('card-body')
    )->class('card mb-4 box-shadow');
}
