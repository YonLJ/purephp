<?php declare(strict_types=1);

use Pure\Core\HTML;
use function Pure\Tags\HTML\button;
use function Pure\Tags\HTML\div;
use function Pure\Tags\HTML\h1;
use function Pure\Tags\HTML\h4;
use function Pure\Tags\HTML\li;
use function Pure\Tags\HTML\small;
use function Pure\Tags\HTML\ul;

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
                array_map(fn ($feature) => li($feature), $features)
            )->class('list-unstyled mt-3 mb-4'),
            button($button['text'])->type('button')->class("btn btn-lg btn-block {$button['class']}")
        )->class('card-body')
    )->class('card mb-4 box-shadow');
}
