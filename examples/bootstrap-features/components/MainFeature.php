<?php declare(strict_types=1);

use Pure\Core\HTML;

use function Pure\Tags\HTML\a;
use function Pure\Tags\HTML\div;
use function Pure\Tags\HTML\h3;
use function Pure\Tags\HTML\p;

function MainFeature(array $props): HTML
{
    [
        'title' => $title,
        'content' => $content,
        'linkText' => $linkText,
        'link' => $link
    ] = $props;

    return (
        div(
            h3($title)->class('fw-bold'),
            p($content)->class('text-muted'),
            a($linkText)->class('btn btn-primary btn-lg')->href($link)
        )->class('col d-flex flex-column align-items-start gap-2')
    );
}
