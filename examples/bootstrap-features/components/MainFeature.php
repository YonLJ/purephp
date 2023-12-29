<?php declare(strict_types=1);

use Tiny\Core\HTML;

use function Tiny\Tags\HTML\a;
use function Tiny\Tags\HTML\div;
use function Tiny\Tags\HTML\h3;
use function Tiny\Tags\HTML\p;

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
