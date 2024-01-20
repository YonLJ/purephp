<?php declare(strict_types=1);

use Pure\Core\HTML;

use function Pure\HTML\div;

function Divider(): HTML
{
    return div()->class('b-example-divider');
}
