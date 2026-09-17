<?php declare(strict_types=1);

/**
 * Classic (uncompiled) builder for the features page: it assembles the section
 * components from the same content arrays the shapes receive, so benchmarks can
 * compare "build tree + render()" with the compiled path byte for byte.
 *
 * Lives under bench/fixtures because the examples themselves are compiled-only.
 */

require_once __DIR__ . '/components.php';

use Pure\Core\HTML;

use function Pure\HTML\div;
use function Pure\HTML\h1;
use function Pure\HTML\main;

/**
 * @param array{
 *     columns: list<array<string, string>>,
 *     hanging: list<array<string, string>>,
 *     cards: list<array<string, string>>,
 *     grid: list<array<string, string>>,
 *     features: list<array<string, string>>
 * } $content The page content, as the example's PageContent model provides it.
 */
function classicFeaturesPage(array $content): HTML
{
    return main(
        h1('Features examples')->class('visually-hidden'),
        classicSection('Columns with icons', array_map(classicIconColumn(...), $content['columns']), 'row g-4 py-5 row-cols-1 row-cols-lg-3'),
        classicDivider(),
        classicSection('Hanging icons', array_map(classicHangingIcon(...), $content['hanging']), 'row g-4 py-5 row-cols-1 row-cols-lg-3'),
        classicDivider(),
        classicSection('Custom cards', array_map(classicCustomCard(...), $content['cards']), 'row row-cols-1 row-cols-lg-3 align-items-stretch g-4 py-5'),
        classicDivider(),
        classicSection('classicIcon grid', array_map(classicCellIcon(...), $content['grid']), 'row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 py-5'),
        classicDivider(),
        classicSection(
            'Features with title',
            [
                classicMainFeature(
                    'Left-aligned title explaining these awesome features',
                    "Paragraph of text beneath the heading to explain the heading. We'll add onto it with another sentence and probably just keep going until we run out of words.",
                    '#',
                    'Primary button'
                ),
                div(
                    div(...array_map(classicFeatureTitle(...), $content['features']))->class('row row-cols-1 row-cols-sm-2 g-4')
                )->class('col'),
            ],
            'row row-cols-1 row-cols-md-2 align-items-md-center g-5 py-5'
        ),
    );
}
