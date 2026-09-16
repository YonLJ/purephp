<?php

declare(strict_types=1);

namespace Pure\Core;

/**
 * Minimal contract the slot layer needs from a compiled shape.
 *
 * Implemented by Pure\Compile\Shape. It exists so that Pure\Core stays
 * independent of Pure\Compile: the compiler depends on core, not the other way
 * around.
 */
interface ShapeContract
{
    /**
     * The data-free tag tree of the shape.
     *
     * @internal
     */
    public function tree(): Tag;
}
