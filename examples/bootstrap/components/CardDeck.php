<?php declare(strict_types=1);

require_once __DIR__ . '/Card.php';

use Pure\Core\Raw;

use function Pure\Component\render;

/**
 * The pricing card deck.
 *
 * @param list<array{type: string, price: string, features: list<array{value: string}>, text: string, class: string}> $cards
 */
function CardDeck(array $cards): Raw
{
    $items = [];

    foreach ($cards as $card) {
        $items[] = (string)Card(
            $card['type'],
            $card['price'],
            array_map(static fn (array $feature): string => $feature['value'], $card['features']),
            $card['text'],
            $card['class'],
        );
    }

    return render(
        __DIR__ . '/CardDeck.shape.php',
        cards: implode('', $items),
    );
}
