<?php declare(strict_types=1);

require_once __DIR__ . '/Card.php';

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Slot;

use function Pure\HTML\div;

function CardDeckShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(Slot::each('cards', CardShape()))->class('card-deck mb-3 text-center')
    );
}
