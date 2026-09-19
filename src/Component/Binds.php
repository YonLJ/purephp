<?php

declare(strict_types=1);

namespace Pure\Component;

use Attribute;

/**
 * Declares the bindings a prepare() closure or a bindings helper returns.
 *
 * The returned array literal of a prepare() hook is what binds the template,
 * and `pure check` reads its keys when it is one array literal. A hook that
 * builds its bindings in steps — or merges them from a service — leaves the
 * checker nothing to read, so the keys are declared instead:
 *
 *     register('PricingHeader', __FILE__,
 *         factory: static fn () => Compile::shape(...),
 *         prepare: #[Binds('title', 'desc')] static function (): array {
 *             return PricingService::pricing();
 *         }
 *     );
 *
 * The declared keys are what the checker compares against the template: a
 * required slot no declared key covers is an error, as is a declared key the
 * template does not read, and a key the hook returns but did not declare when
 * its literal can be read. The attribute works the same way on a bindings
 * helper function (`#[Binds('title')] function pageBindings(): array`).
 *
 * The attribute is read by `pure check` only and never takes part in
 * rendering; a hook without it behaves exactly as before.
 */
#[Attribute(Attribute::TARGET_FUNCTION)]
final class Binds
{
    /** @var list<string> */
    public readonly array $keys;

    public function __construct(string ...$keys)
    {
        $this->keys = array_values($keys);
    }
}
