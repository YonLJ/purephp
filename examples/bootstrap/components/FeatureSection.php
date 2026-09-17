<?php declare(strict_types=1);

require_once __DIR__ . '/MainFeature.php';
require_once __DIR__ . '/FeatureTitle.php';

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Slot;

use function Pure\HTML\div;
use function Pure\HTML\h2;

function FeatureSectionShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            h2(Slot::text('title'))->class('pb-2 border-bottom'),
            div(
                Slot::child('main', MainFeatureShape()),
                div(
                    div(Slot::each('features', FeatureTitleShape()))->class('row row-cols-1 row-cols-sm-2 g-4')
                )->class('col')
            )->class('row row-cols-1 row-cols-md-2 align-items-md-center g-5 py-5')
        )->class('container px-4 py-5')
    );
}
