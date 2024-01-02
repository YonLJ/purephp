<?php declare(strict_types=1);

use Pure\Core\HTML;
use function Pure\Tags\HTML\div;
use function Pure\Tags\HTML\h1;
use function Pure\Tags\HTML\p;

function PricingHeader($props): HTML
{
    [
        'title' => $title,
        'desc' => $desc
    ] = $props;

    return div(
        h1($title)->class('display-4'),
        p($desc)->class('lead')
    )->class('pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center');
}
