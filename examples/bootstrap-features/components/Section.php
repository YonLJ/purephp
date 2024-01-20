<?php declare(strict_types=1);

use Pure\Core\HTML;

use function Pure\HTML\div;
use function Pure\HTML\h2;

function Section(array $props): HTML
{
    [
        'title' => $title,
        'contents' => $contents,
        'classList' => $classList
    ] = $props;

    return (
        div(
            h2($title)->class('pb-2 border-bottom'),
            div(...$contents)->class($classList)
        )->class('container px-4 py-5')
    );
}
