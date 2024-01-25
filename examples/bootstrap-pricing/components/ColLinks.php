<?php declare(strict_types=1);

use Pure\Core\HTML;

use function Pure\HTML\a;
use function Pure\HTML\div;
use function Pure\HTML\h5;
use function Pure\HTML\li;
use function Pure\HTML\ul;

function ColLinks(array $props): HTML
{
    [
        'title' => $title,
        'links' => $links
    ] = $props;

    return div(
        h5($title),
        ul(
            array_map(fn($link) => li(a($link['text'])->class('text-muted')->href($link['href'])), $links)
        )->class('list-unstyled text-small')
    )->class('col-6 col-md');
}
