<?php declare(strict_types=1);

use Tiny\Core\HTML;

use function Tiny\Tags\HTML\div;

function Divider(): HTML
{
    return div()->class('b-example-divider');
}
