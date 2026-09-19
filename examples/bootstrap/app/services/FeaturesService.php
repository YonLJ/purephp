<?php declare(strict_types=1);

require_once __DIR__ . '/../dao/FeaturesDao.php';

/**
 * The domain layer of the features page: it turns the records of FeaturesDao
 * into the props of one component, so a component fetches its own slice here
 * instead of receiving it through the page.
 *
 * A component only needs the method that matches it: Section() asks for one
 * section, FeatureSection() asks for the section with its main column, and the
 * page asks for the title.
 */
final class FeaturesService
{
    /**
     * The section titles by data key; the records themselves come from the DAO.
     */
    private const TITLES = [
        'columns' => 'Columns with icons',
        'hanging' => 'Hanging icons',
        'cards' => 'Custom cards',
        'grid' => 'Icon grid',
    ];

    /**
     * The title of the features page.
     */
    public static function pageTitle(): string
    {
        return 'Features · Bootstrap v5.2';
    }

    /**
     * One section of the page: its title and the item records the section
     * component renders.
     *
     * @param string $key The section key, one of the TITLES keys.
     * @return array{title: string, items: list<array<string, string>>}
     */
    public static function section(string $key): array
    {
        if (!isset(self::TITLES[$key])) {
            throw new RuntimeException(
                "unknown features section '{$key}'; known sections: " . implode(', ', array_keys(self::TITLES)) . '.'
            );
        }

        /** @var list<array<string, string>> $items */
        $items = FeaturesDao::content()[$key];

        return ['title' => self::TITLES[$key], 'items' => $items];
    }

    /**
     * The "features with title" section: the heading, the main column and the
     * feature records.
     *
     * @return array{
     *     title: string,
     *     main: array{title: string, content: string, link: string, linkText: string},
     *     features: list<array{icon: string, title: string, content: string}>
     * }
     */
    public static function featureSection(): array
    {
        /** @var list<array{icon: string, title: string, content: string}> $features */
        $features = FeaturesDao::content()['features'];

        return [
            'title' => 'Features with title',
            'main' => [
                'title' => 'Left-aligned title explaining these awesome features',
                'content' => "Paragraph of text beneath the heading to explain the heading. We'll add onto it with another sentence and probably just keep going until we run out of words.",
                'link' => '#',
                'linkText' => 'Primary button',
            ],
            'features' => $features,
        ];
    }
}
