<?php declare(strict_types=1);

/**
 * Data bindings for the compiled page shape: plain data arrays keyed by the
 * slot names of shapes.php.
 */

require_once __DIR__ . '/data.php';

return [
    'columns' => [
        'title' => 'Columns with icons',
        'contents' => $columnsData,
    ],
    'hanging' => [
        'title' => 'Hanging icons',
        'contents' => $hangingData,
    ],
    'cards' => [
        'title' => 'Custom cards',
        'contents' => array_map(
            static fn (array $card): array => $card + ['style' => "background-image: url('{$card['bgImg']}');"],
            $cardsData
        ),
    ],
    'grid' => [
        'title' => 'Icon grid',
        'contents' => $gridData,
    ],
    'features' => [
        'title' => 'Features with title',
        'main' => [
            'title' => 'Left-aligned title explaining these awesome features',
            'content' => "Paragraph of text beneath the heading to explain the heading. We'll add onto it with another sentence and probably just keep going until we run out of words.",
            'link' => '#',
            'linkText' => 'Primary button',
        ],
        'features' => $featuresData,
    ],
];
