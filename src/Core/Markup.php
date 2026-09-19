<?php

declare(strict_types=1);

namespace Pure\Core;

use Stringable;

/**
 * Trusted markup: emitted verbatim in child position instead of being escaped
 * as text, and accepted wherever a raw value is.
 *
 * Raw is the literal implementation. A component call implements it too, so
 * the markup of a component nests like a tag:
 *
 *     div(Card(h2('Title'))->type('Free'))
 *
 * Like a raw slot, this is a trust boundary: only implement it for markup the
 * library itself produced, never for user input.
 */
interface Markup extends Stringable
{
}
