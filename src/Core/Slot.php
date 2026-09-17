<?php

declare(strict_types=1);

namespace Pure\Core;

use Closure;
use InvalidArgumentException;
use LogicException;

/**
 * Placeholder for data bound at render time by a compiled shape.
 *
 * Slots live inside a shape tree built for Pure\Compile\Compile::shape() and
 * cannot be rendered by Tag::render() directly. Shape arguments are typed
 * against ShapeContract so that Pure\Core does not depend on Pure\Compile.
 */
final class Slot
{
    /**
     * @param array<string, ShapeContract> $variants
     */
    private function __construct(
        public readonly SlotKind $kind,
        public readonly string $name,
        public readonly ?ShapeContract $shape,
        public readonly bool $required,
        public readonly mixed $default,
        public readonly ?Closure $map,
        public readonly ?ShapeContract $else = null,
        public readonly array $variants = [],
        public readonly ?string $kindKey = null,
    ) {
    }

    /**
     * Text content slot: the value is coerced to string and escaped.
     *
     * @param string $name The slot name.
     * @return self
     */
    public static function text(string $name): self
    {
        return new self(SlotKind::Text, $name, null, true, null, null);
    }

    /**
     * Attribute value slot: the value is coerced to string and escaped.
     *
     * The attribute name comes from the setter
     * (`->class(Slot::attr('classList'))` sets `class`); $name is the data key
     * holding the value.
     *
     * @param string $name The data key holding the attribute value.
     * @return self
     */
    public static function attr(string $name): self
    {
        return new self(SlotKind::Attr, $name, null, true, null, null);
    }

    /**
     * Raw content slot: the value is emitted verbatim, without escaping.
     *
     * @param string $name The slot name.
     * @return self
     */
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
     * @param string $name The slot name.
     * @param ShapeContract $shape The shape for rendering this slot's content.
     * @param Closure(array<string, mixed>): array<string, mixed>|null $map
     * @return self
     */
    public static function sub(string $name, ShapeContract $shape, ?Closure $map = null): self
    {
        return new self(SlotKind::Sub, $name, $shape, true, null, $map);
    }

    /**
     * List slot: the value is an iterable of arrays, each rendered by $shape.
     *
     * With $map the nested scope of each item is `$map($item)`.
     *
     * @param string $name The slot name.
     * @param ShapeContract $shape The shape for rendering each item.
     * @param Closure(mixed): array<string, mixed>|null $map
     * @return self
     */
    public static function each(string $name, ShapeContract $shape, ?Closure $map = null): self
    {
        return new self(SlotKind::Each, $name, $shape, true, null, $map);
    }

    /**
     * Conditional slot: renders $then when `$data[$name]` is truthy, otherwise
     * $else (or nothing). A missing key is false and never throws; then/else
     * branches share the current data scope.
     *
     * @param string $name The slot name.
     * @param ShapeContract $then The shape for the truthy branch.
     * @param ShapeContract|null $else The shape for the falsy branch.
     * @return self
     */
    public static function if(string $name, ShapeContract $then, ?ShapeContract $else = null): self
    {
        return new self(SlotKind::If, $name, $then, false, false, null, $else);
    }

    /**
     * Heterogeneous list slot: every item is dispatched on `$item[$kindKey]`
     * to the matching shape; an unknown kind throws an InvalidArgumentException.
     *
     * @param string $name The slot name.
     * @param array<string, ShapeContract> $shapes
     * @param string $kindKey The key used to dispatch items by kind.
     * @param Closure(mixed): array<string, mixed>|null $map
     * @return self
     */
    public static function eachAny(string $name, array $shapes, string $kindKey = 'kind', ?Closure $map = null): self
    {
        if ($shapes === []) {
            throw new InvalidArgumentException("slot '{$name}' eachAny requires at least one shape.");
        }

        foreach (array_keys($shapes) as $kind) {
            if ($kind === '') {
                throw new InvalidArgumentException("slot '{$name}' eachAny kinds must be non-empty strings.");
            }
        }

        return new self(SlotKind::EachAny, $name, null, true, null, $map, null, $shapes, $kindKey);
    }

    /**
     * Mark the slot as optional.
     *
     * @param bool $required Whether the slot is required (default true).
     * @return self
     */
    public function required(bool $required = true): self
    {
        if ($this->kind === SlotKind::If) {
            throw new LogicException("slot '{$this->name}' is a condition slot; required() does not apply.");
        }

        return new self($this->kind, $this->name, $this->shape, $required, $this->default, $this->map, $this->else, $this->variants, $this->kindKey);
    }

    /**
     * Provide a fallback value, making the slot optional.
     *
     * Defaults are inlined into the compiled renderer, so they must be value
     * types: null, a scalar, or an array of value types.
     *
     * @param mixed $value The default value.
     * @return self
     */
    public function default(mixed $value): self
    {
        if ($this->kind === SlotKind::If) {
            throw new LogicException("slot '{$this->name}' is a condition slot; default() does not apply.");
        }

        if (!self::isValueType($value)) {
            throw new InvalidArgumentException(
                "slot '{$this->name}' default must be null, a scalar or an array of value types, "
                . get_debug_type($value) . ' given.'
            );
        }

        return new self($this->kind, $this->name, $this->shape, false, $value, $this->map, $this->else, $this->variants, $this->kindKey);
    }

    private static function isValueType(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (!self::isValueType($item)) {
                    return false;
                }
            }

            return true;
        }

        return is_scalar($value) || $value === null;
    }
}
