<?php

declare(strict_types=1);

namespace Pure\Core;

/**
 * Trusted markup emitted verbatim instead of being escaped.
 */
final class Raw
{
    private function __construct(public readonly string $value)
    {
    }

    /**
     * Wrap trusted markup so it is emitted verbatim instead of being escaped.
     */
    public static function of(string $value): self
    {
        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
