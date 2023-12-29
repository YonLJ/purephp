<?php declare(strict_types=1);

use Pure\Core\HTML;

use function Pure\Tags\HTML\a;
use function Pure\Tags\HTML\div;
use function Pure\Tags\HTML\h3;
use function Pure\Tags\HTML\p;

require_once __DIR__ . '/Icon.php';

function IconColumn(array $props): HTML
{
    [
        'icon' => $icon,
        'title' => $title,
        'content' => $content,
        'linkText' => $linkText,
        'link' => $link
    ] = $props;

    return (
        div(
            div(
                Icon($icon)->class('bi')->width('1em')->height('1em')
            )->class('feature-icon d-inline-flex align-items-center justify-content-center text-bg-primary bg-gradient fs-2 mb-3'),
            h3($title)->class('fs-2'),
            p($content),
            a(
                $linkText,
                Icon('chevron-right')->class('bi')->width('1em')->height('1em'),
            )->href($link)->class('icon-link d-inline-flex align-items-center')
        )->class('feature col')
    );
}
