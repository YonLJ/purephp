<?php

declare(strict_types=1);

namespace Pure\Component;

use Attribute;

/**
 * Marks the call function of a unit file: the `function Card(...): Call`
 * companion of `register('Card', __FILE__, ...)` that hands the component
 * name to `component()`.
 *
 * Without the attribute the checker falls back to matching the function's
 * short name against the registered component name. The attribute makes the
 * link explicit so the function may be renamed and `pure check` can verify
 * that the marked name and the registered name agree:
 *
 *     #[Component]
 *     function Card(mixed ...$children): Call
 *     {
 *         return component('Card', ...$children);
 *     }
 *
 * A nameless `#[Component]` claims the function for whichever component its
 * unit file registers; `#[Component('Card')]` additionally pins the name and
 * `pure check` reports a mismatch against `register()`.
 */
#[Attribute(Attribute::TARGET_FUNCTION)]
final class Component
{
    /**
     * @param string|null $name The registered component name, or null to accept the file's registration.
     */
    public function __construct(public ?string $name = null)
    {
    }
}
