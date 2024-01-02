<?php declare(strict_types=1);

use Pure\Core\HTML;

use function Pure\Tags\HTML\img;
use function Pure\Tags\HTML\small;
use function Pure\Tags\HTML\div;

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
