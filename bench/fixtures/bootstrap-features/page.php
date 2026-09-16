<?php declare(strict_types=1);

/**
 * Classic (uncompiled) builder for the features page.
 *
 * Lives under bench/fixtures so benchmarks can compare "build tree + render()"
 * against the compiled shape path; the examples themselves are compiled-only.
 */

require_once __DIR__ . '/components/Section.php';
require_once __DIR__ . '/components/Divider.php';
require_once __DIR__ . '/components/IconColumn.php';
require_once __DIR__ . '/components/HangingIcon.php';
require_once __DIR__ . '/components/CustomCard.php';
require_once __DIR__ . '/components/CellIcon.php';
require_once __DIR__ . '/components/FeatureTitle.php';
require_once __DIR__ . '/components/MainFeature.php';

use Pure\Core\HTML;

use function Pure\HTML\div;
use function Pure\HTML\h1;
use function Pure\HTML\main;

function classicFeaturesPage(
    array $columnsData,
    array $hangingData,
    array $cardsData,
    array $gridData,
    array $featuresData
): HTML {
    return main(
        h1('Features examples')->class('visually-hidden'),
        Section([
            'title' => 'Columns with icons',
            'contents' => array_map(fn (array $data): HTML => IconColumn($data), $columnsData),
            'classList' => 'row g-4 py-5 row-cols-1 row-cols-lg-3',
        ]),
        Divider(),
        Section([
            'title' => 'Hanging icons',
            'contents' => array_map(fn (array $data): HTML => HangingIcon($data), $hangingData),
            'classList' => 'row g-4 py-5 row-cols-1 row-cols-lg-3',
        ]),
        Divider(),
        Section([
            'title' => 'Custom cards',
            'contents' => array_map(fn (array $data): HTML => CustomCard($data), $cardsData),
            'classList' => 'row row-cols-1 row-cols-lg-3 align-items-stretch g-4 py-5',
        ]),
        Divider(),
        Section([
            'title' => 'Icon grid',
            'contents' => array_map(fn (array $data): HTML => CellIcon($data), $gridData),
            'classList' => 'row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 py-5',
        ]),
        Divider(),
        Section([
            'title' => 'Features with title',
            'contents' => [
                MainFeature([
                    'title' => 'Left-aligned title explaining these awesome features',
                    'content' => "Paragraph of text beneath the heading to explain the heading. We'll add onto it with another sentence and probably just keep going until we run out of words.",
                    'link' => '#',
                    'linkText' => 'Primary button',
                ]),
                div(
                    div(
                        ...array_map(fn (array $data): HTML => FeatureTitle($data), $featuresData)
                    )->class('row row-cols-1 row-cols-sm-2 g-4')
                )->class('col'),
            ],
            'classList' => 'row row-cols-1 row-cols-md-2 align-items-md-center g-5 py-5',
        ]),
    );
}
