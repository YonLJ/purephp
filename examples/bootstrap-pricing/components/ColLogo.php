<?php declare(strict_types=1);

use Pure\Core\HTML;

use function Pure\HTML\img;
use function Pure\HTML\small;
use function Pure\HTML\div;

function ColLogo(array $props): HTML
{
    [
        'img' => $img,
        'text' => $text
    ] = $props;

    return div(
        img()->class('mb-2')->src($img['src'])->width($img['width'])->height($img['height']),
        small($text)->class('d-block mb-3 text-muted')
    )->class('col-12 col-md');
}
