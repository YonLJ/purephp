<?php

declare(strict_types=1);

namespace Pure\Compile;

use Attribute;

/**
 * Marks a template builder: the function that returns the tag tree or
 * `Pure\Compile\Shape` a unit factory is built from, next to the component
 * it belongs to:
 *
 *     #[Template]
 *     function XmlPageShape(): Shape
 *     {
 *         static $shape;
 *
 *         return $shape ??= Compile::shape(...);
 *     }
 *
 * `pure compile --list` prints the marked builders of a unit file next to
 * the component, `pure check` verifies a declared return type against Shape
 * or a tag, and the call-function lookup skips a marked function so a
 * builder that shares the component's name is never mistaken for the call
 * function.
 */
#[Attribute(Attribute::TARGET_FUNCTION)]
final class Template
{
}
