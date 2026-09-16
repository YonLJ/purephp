<?php

declare(strict_types=1);

namespace Pure\Core;

use Closure;
use Pure\Compile\Shape;

/**
 * Placeholder for data bound at render time by a compiled shape.
 *
 * Slots live inside a shape tree built for Pure\Compile\Compile::shape() and
 * cannot be rendered by Tag::render() directly.
 */
final class Slot
{
    private function __construct(
        public readonly SlotKind $kind,
        public readonly string $name,
        public readonly ?Shape $shape,
        public readonly bool $required,
        public readonly mixed $default,
        public readonly ?Closure $map,
    ) {
    }

    /** Text content slot: the value is coerced to string and escaped. */
    public static function text(string $name): self
    {
        return new self(SlotKind::Text, $name, null, true, null, null);
    }

    /** Attribute value slot: the value is coerced to string and escaped. */
    public static function attr(string $name): self
    {
        return new self(SlotKind::Attr, $name, null, true, null, null);
    }

    /** Raw content slot: the value is emitted verbatim, without escaping. */
    public static function raw(string $name): self
    {
        return new self(SlotKind::Raw, $name, null, true, null, null);
    }

    /**
     * Sub-template slot.
     *
     * Without $map the nested data scope is `$data[$name]`; with $map it is
     * `$map($data)`, which is how a component derives its children props.
     *
     * @param Closure(array<string, mixed>): array<string, mixed>|null $map
     */
    public static function sub(string $name, Shape $shape, ?Closure $map = null): self
    {
        return new self(SlotKind::Sub, $name, $shape, true, null, $map);
    }

    /**
     * List slot: the value is an iterable of arrays, each rendered by $shape.
     *
     * With $map the nested scope of each item is `$map($item)`.
     *
     * @param Closure(mixed): array<string, mixed>|null $map
     */
    public static function each(string $name, Shape $shape, ?Closure $map = null): self
    {
        return new self(SlotKind::Each, $name, $shape, true, null, $map);
    }

    /** Mark the slot as optional. */
    public function required(bool $required = true): self
    {
        return new self($this->kind, $this->name, $this->shape, $required, $this->default, $this->map);
    }

    /** Provide a fallback value, making the slot optional. */
    public function default(mixed $value): self
    {
        return new self($this->kind, $this->name, $this->shape, false, $value, $this->map);
    }
}
