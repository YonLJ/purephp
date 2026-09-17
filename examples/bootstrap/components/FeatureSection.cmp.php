<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\{div, h2};

require_once __DIR__ . '/MainFeature.cmp.php';
require_once __DIR__ . '/FeatureTitle.cmp.php';

/**
 * The "features with title" section template: the main column and the feature
 * items are rendered by MainFeature() and FeatureTitle() and injected as raw
 * markup.
 */
register('FeatureSection', __FILE__, static fn (): Shape => Compile::shape(
    div(
        h2(Slot::text('title'))->class('pb-2 border-bottom'),
        div(
            Slot::raw('main'),
            div(
                div(Slot::raw('features'))->class('row row-cols-1 row-cols-sm-2 g-4')
            )->class('col')
        )->class('row row-cols-1 row-cols-md-2 align-items-md-center g-5 py-5')
    )->class('container px-4 py-5')
));

/**
 * The "features with title" section.
 *
 * @param array{title: string, content: string, link: string, linkText: string} $main
 * @param list<array{icon: string, title: string, content: string}> $features
 */
function FeatureSection(string $title, array $main, array $features): Raw
{
    $items = [];

    foreach ($features as $feature) {
        $items[] = (string)FeatureTitle($feature);
    }

    return render(
        'FeatureSection',
        title: $title,
        main: (string)MainFeature($main),
        features: implode('', $items),
    );
}
