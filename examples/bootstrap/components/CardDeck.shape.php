<?php declare(strict_types=1);

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\div;

/**
 * The card deck template: the cards are rendered by Card() and injected as raw
 * markup.
 */
return Compile::shape(
    div(Slot::raw('cards'))->class('card-deck mb-3 text-center')
);
