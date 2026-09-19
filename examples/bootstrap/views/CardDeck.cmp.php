<?php declare(strict_types=1);
use Pure\Core\Slot;

use function Pure\Component\{register, render};
use function Pure\HTML\div;

require_once __DIR__ . '/../components/Card.cmp.php';
require_once __DIR__ . '/../app/services/PricingService.php';

/**
 * The card deck template: the cards are rendered by Card() and injected as raw
 * markup.
 */
register('CardDeck', __FILE__, static fn () =>
    div(Slot::raw('cards'))->class('card-deck mb-3 text-center')
);

/**
 * The pricing card deck: it fetches the card records from the service and
 * renders its own children.
 */
function CardDeck(): string
{
    return render(
        'CardDeck',
        cards: array_map(
            static fn (array $card): string => Card(
                type: $card['type'],
                price: $card['price'],
                features: array_map(static fn (array $feature): string => $feature['value'], $card['features']),
                text: $card['text'],
                class: $card['class'],
            ),
            PricingService::deck()
        ),
    );
}
