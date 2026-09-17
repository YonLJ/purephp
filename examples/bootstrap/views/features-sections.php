<?php declare(strict_types=1);

require_once __DIR__ . '/../components/IconColumn.php';
require_once __DIR__ . '/../components/HangingIcon.php';
require_once __DIR__ . '/../components/CustomCard.php';
require_once __DIR__ . '/../components/CellIcon.php';
require_once __DIR__ . '/../components/MainFeature.php';
require_once __DIR__ . '/../components/FeatureTitle.php';
require_once __DIR__ . '/../components/Section.php';
require_once __DIR__ . '/../components/Divider.php';
require_once __DIR__ . '/../components/FeatureSection.php';

use Pure\Core\Raw;

use function Pure\HTML\h1;
use function Pure\HTML\main;

/**
 * The body of the features page: the sections are composed from the component
 * functions in components/, each section item rendered by its own function.
 *
 * @param array<string, mixed> $content The page content.
 */
function FeaturesBody(array $content): Raw
{
    return Raw::of(main(
        h1('Features examples')->class('visually-hidden'),
        Section($content['columns']['title'], Raw::of(renderItems($content['columns']['contents'], IconColumn(...))), 'row g-4 py-5 row-cols-1 row-cols-lg-3'),
        Divider(),
        Section($content['hanging']['title'], Raw::of(renderItems($content['hanging']['contents'], HangingIcon(...))), 'row g-4 py-5 row-cols-1 row-cols-lg-3'),
        Divider(),
        Section($content['cards']['title'], Raw::of(renderItems($content['cards']['contents'], CustomCard(...))), 'row row-cols-1 row-cols-lg-3 align-items-stretch g-4 py-5'),
        Divider(),
        Section($content['grid']['title'], Raw::of(renderItems($content['grid']['contents'], CellIcon(...))), 'row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 py-5'),
        Divider(),
        FeatureSection($content['features']['title'], $content['features']['main'], $content['features']['features']),
    )->render());
}

/**
 * Render one list of section items with its component function.
 *
 * @param list<array<string, string>> $items
 * @param callable(array<string, string>): Raw $component
 */
function renderItems(array $items, callable $component): string
{
    $html = '';

    foreach ($items as $item) {
        $html .= (string)$component($item);
    }

    return $html;
}
