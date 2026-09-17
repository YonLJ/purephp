<?php declare(strict_types=1);

require_once __DIR__ . '/ColLogo.php';
require_once __DIR__ . '/ColLinks.php';

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Slot;

use function Pure\HTML\div;
use function Pure\HTML\footer;

function PageFooterShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        footer(
            div(
                Slot::child('logo', ColLogoShape()),
                Slot::each('links', ColLinksShape())
            )->class('row'),
        )->class('pt-4 my-md-5 pt-md-5 border-top')
    );
}
