<?php declare(strict_types=1);

use Pure\Core\HTML;

use function Pure\Tags\HTML\div;
use function Pure\Tags\HTML\footer;

function PageFooter(...$children): HTML
{
    return footer(
        div(
            ...$children
        )->class('row'),
    )->class('pt-4 my-md-5 pt-md-5 border-top');
}
