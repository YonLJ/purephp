<?php declare(strict_types=1);

require_once __DIR__ . '/Icon.php';

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Slot;

use function Pure\HTML\div;
use function Pure\HTML\h3;
use function Pure\HTML\p;

function CellIconShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            Slot::child('icon', IconShape('bi text-muted flex-shrink-0 me-3', '1.75em', '1.75em')),
            div(
                h3(Slot::text('title'))->class('fw-bold mb-0 fs-4'),
                p(Slot::text('content'))
            )
        )->class('col d-flex align-items-start')
    );
}
