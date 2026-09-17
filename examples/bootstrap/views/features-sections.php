<?php declare(strict_types=1);

require_once __DIR__ . '/../components/Icon.php';
require_once __DIR__ . '/../components/IconColumn.php';
require_once __DIR__ . '/../components/HangingIcon.php';
require_once __DIR__ . '/../components/CustomCard.php';
require_once __DIR__ . '/../components/CellIcon.php';
require_once __DIR__ . '/../components/MainFeature.php';
require_once __DIR__ . '/../components/FeatureTitle.php';
require_once __DIR__ . '/../components/Section.php';
require_once __DIR__ . '/../components/Divider.php';
require_once __DIR__ . '/../components/FeatureSection.php';

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Slot;

use function Pure\HTML\h1;
use function Pure\HTML\main;

/**
 * The body of the features page, composed from the section components in
 * components/. features.shape.php wraps it in the document.
 */
function FeaturesBodyShape(): Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        main(
            h1('Features examples')->class('visually-hidden'),
            Slot::child('columns', SectionShape(IconColumnShape(), 'row g-4 py-5 row-cols-1 row-cols-lg-3')),
            DividerShape(),
            Slot::child('hanging', SectionShape(HangingIconShape(), 'row g-4 py-5 row-cols-1 row-cols-lg-3')),
            DividerShape(),
            Slot::child('cards', SectionShape(CustomCardShape(), 'row row-cols-1 row-cols-lg-3 align-items-stretch g-4 py-5')),
            DividerShape(),
            Slot::child('grid', SectionShape(CellIconShape(), 'row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 py-5')),
            DividerShape(),
            Slot::child('features', FeatureSectionShape()),
        )
    );
}
