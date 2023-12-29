<?php declare(strict_types=1);

use Pure\Core\HTML;

use function Pure\Tags\HTML\div;
use function Pure\Tags\HTML\h3;
use function Pure\Tags\HTML\p;
use function Pure\Tags\HTML\a;

require_once __DIR__ . '/Icon.php';

function HangingIcon(array $props): HTML
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
            )->class('icon-square text-bg-light d-inline-flex align-items-center justify-content-center fs-4 flex-shrink-0 me-3'),
            div(
                h3($title)->class('fs-2'),
                p($content),
                a($linkText)->href($link)->class('btn btn-primary')
            )
        )->class('col d-flex align-items-start')
    );
}
