<?php declare(strict_types=1);

require_once __DIR__ . '/Icon.php';

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Slot;

use function Pure\HTML\div;
use function Pure\HTML\h4;
use function Pure\HTML\p;

function FeatureTitleShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            div(
                Slot::child('icon', IconShape())
            )->class('feature-icon-small d-inline-flex align-items-center justify-content-center text-bg-primary bg-gradient fs-4 rounded-3'),
            h4(Slot::text('title'))->class('fw-semibold mb-0'),
            p(Slot::text('content'))->class('text-muted')
        )->class('col d-flex flex-column gap-2')
    );
}
