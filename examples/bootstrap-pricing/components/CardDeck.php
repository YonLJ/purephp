<?php declare(strict_types=1);

require_once __DIR__ . '/Card.php';

use Pure\Core\HTML;
use function Pure\Tags\HTML\div;

function CardDeck($cards): HTML
{
    return div(
        array_map(fn($data) => Card($data), $cards)
    )->class('card-deck mb-3 text-center');
}
