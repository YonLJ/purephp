<?php declare(strict_types=1);

require_once __DIR__ . '/../models/FeaturesContent.php';

/**
 * The view data of the features page, keyed by the slots of
 * views/features.shape.php. Both controllers fill their view with it, so the
 * page is the same whether it renders the artifact or the plain view; the
 * content itself comes from app/models/FeaturesContent.php.
 *
 * @return array<string, mixed>
 */
function featuresData(): array
{
    $content = featuresContent();

    return [
        'title' => 'Features · Bootstrap v5.2',
        'content' => [
            'columns' => [
                'title' => 'Columns with icons',
                'contents' => $content['columns'],
            ],
            'hanging' => [
                'title' => 'Hanging icons',
                'contents' => $content['hanging'],
            ],
            'cards' => [
                'title' => 'Custom cards',
                'contents' => array_map(
                    static fn (array $card): array => $card + ['style' => "background-image: url('{$card['bgImg']}');"],
                    $content['cards']
                ),
            ],
            'grid' => [
                'title' => 'Icon grid',
                'contents' => $content['grid'],
            ],
            'features' => [
                'title' => 'Features with title',
                'main' => [
                    'title' => 'Left-aligned title explaining these awesome features',
                    'content' => "Paragraph of text beneath the heading to explain the heading. We'll add onto it with another sentence and probably just keep going until we run out of words.",
                    'link' => '#',
                    'linkText' => 'Primary button',
                ],
                'features' => $content['features'],
            ],
        ],
    ];
}
