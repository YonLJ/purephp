<?php declare(strict_types=1);
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\div;

require_once __DIR__ . '/Card.cmp.php';

/**
 * The card deck template: the cards are rendered by Card() and injected as raw
 * markup.
 */
register('CardDeck', __FILE__, static fn () =>
    div(Slot::raw('cards'))->class('card-deck mb-3 text-center')
);

/**
 * The pricing card deck.
 *
 * @param list<array{type: string, price: string, features: list<array{value: string}>, text: string, class: string}> $cards
 */
function CardDeck(array $cards): string
{
    $items = [];

    foreach ($cards as $card) {
        $items[] = Card(
            $card['type'],
            $card['price'],
            array_map(static fn (array $feature): string => $feature['value'], $card['features']),
            $card['text'],
            $card['class'],
        );
    }

    return render(
        'CardDeck',
        cards: $items,
    );
}
