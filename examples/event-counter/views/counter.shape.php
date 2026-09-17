<?php declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use Pure\Compile\Compile;
use Pure\Core\Slot;

use function Pure\HTML\button;
use function Pure\HTML\div;
use function Pure\HTML\h1;
use function Pure\HTML\span;

/**
 * The counter page as a shape file: `pure compile` precompiles it into
 * views/counter.pure.php, and app/controllers render the same shape without
 * precompiling.
 *
 * `initial` is a slot so the controller can provide a server-random start.
 */
function CounterPageShape(): \Pure\Compile\Shape
{
    static $shape;

    return $shape ??= Compile::shape(
        div(
            h1('JavaScript Counter App'),
            div(
                button('+')->id('add')->onclick('handleAdd()'),
                span(Slot::text('initial'))->id('output'),
                button('-')->id('subtract')
            )->class('counter-container')
        )
    );
}

return CounterPageShape();
