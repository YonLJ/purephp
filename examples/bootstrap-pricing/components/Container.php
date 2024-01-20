<?php declare(strict_types=1);

use Pure\Core\HTML;

use function Pure\HTML\div;

function Container(...$children): HTML
{
    return div(...$children)->class('container');
}
