<?php declare(strict_types=1);

require_once __DIR__ . '/MainFeature.php';
require_once __DIR__ . '/FeatureTitle.php';

use Pure\Core\Raw;

use function Pure\Component\render;

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
        __DIR__ . '/FeatureSection.shape.php',
        title: $title,
        main: (string)MainFeature($main),
        features: implode('', $items),
    );
}
