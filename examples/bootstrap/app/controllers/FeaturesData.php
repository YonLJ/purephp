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

    // The components read the icon of an item as the nested data of their
    // IconShape child, so the controller nests it here.
    $withIcon = static fn (array $item): array => array_merge($item, ['icon' => ['href' => '#' . $item['icon']]]);

    return [
        'title' => 'Features · Bootstrap v5.2',
        'content' => [
            'columns' => [
                'title' => 'Columns with icons',
                'contents' => array_map($withIcon, $content['columns']),
            ],
            'hanging' => [
                'title' => 'Hanging icons',
                'contents' => array_map($withIcon, $content['hanging']),
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
                'contents' => array_map($withIcon, $content['grid']),
            ],
            'features' => [
                'title' => 'Features with title',
                'main' => [
                    'title' => 'Left-aligned title explaining these awesome features',
                    'content' => "Paragraph of text beneath the heading to explain the heading. We'll add onto it with another sentence and probably just keep going until we run out of words.",
                    'link' => '#',
                    'linkText' => 'Primary button',
                ],
                'features' => array_map($withIcon, $content['features']),
            ],
        ],
    ];
}
