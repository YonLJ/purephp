<?php declare(strict_types=1);

/**
 * Data bindings for the compiled page shape: plain data arrays keyed by the
 * slot names of shapes.php.
 */

$data = require __DIR__ . '/data.php';

return [
    'columns' => [
        'title' => 'Columns with icons',
        'contents' => $data['columns'],
    ],
    'hanging' => [
        'title' => 'Hanging icons',
        'contents' => $data['hanging'],
    ],
    'cards' => [
        'title' => 'Custom cards',
        'contents' => array_map(
            static fn (array $card): array => $card + ['style' => "background-image: url('{$card['bgImg']}');"],
            $data['cards']
        ),
    ],
    'grid' => [
        'title' => 'Icon grid',
        'contents' => $data['grid'],
    ],
    'features' => [
        'title' => 'Features with title',
        'main' => [
            'title' => 'Left-aligned title explaining these awesome features',
            'content' => "Paragraph of text beneath the heading to explain the heading. We'll add onto it with another sentence and probably just keep going until we run out of words.",
            'link' => '#',
            'linkText' => 'Primary button',
        ],
        'features' => $data['features'],
    ],
];
