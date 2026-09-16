<?php

declare(strict_types=1);

namespace Pure\Compile;

use Pure\Core\Tag;

final class Compile
{
    /**
     * Wrap a data-free shape tree for compiled rendering.
     *
     * Build shapes once per process (for example with a static variable inside
     * a component function), never on every request.
     */
    public static function shape(Tag $shape): Shape
    {
        return new Shape($shape);
    }
}
