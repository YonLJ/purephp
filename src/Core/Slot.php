<?php

declare(strict_types=1);

namespace Pure\Core;

use InvalidArgumentException;
use LogicException;

/**
 * Placeholder for data bound at render time by a compiled shape.
 *
 * Slots live inside a shape tree built for Pure\Compile\Compile::shape() and
 * cannot be rendered by Tag::render() directly. Nested shapes are typed against
 * ShapeContract so that Pure\Core does not depend on Pure\Compile: a bare tag
 * tree satisfies the contract (a tag is already a data-free tree), so
 * `Slot::each('items', li(Slot::text('value')))` needs no wrapper, while a
 * memoized Pure\Compile\Shape is still accepted.
 */
final class Slot
{
    /**
     * @param array<array-key, ShapeContract> $variants
     */
    private function __construct(
        public readonly SlotKind $kind,
        public readonly string $name,
        public readonly ?ShapeContract $shape,
        public readonly bool $required,
        public readonly mixed $default,
        public readonly ?ShapeContract $else = null,
        public readonly array $variants = [],
        public readonly ?string $kindKey = null,
    ) {
    }

    /**
     * Text content slot: the value is coerced to string and escaped.
     *
     * The bound value may be a string or any Stringable (including a Raw); it
     * is stringified before escaping, so a Raw is not emitted verbatim here —
     * use raw() for that.
     *
     * @param string $name The slot name.
     * @return self
     */
    public static function text(string $name): self
    {
        return new self(SlotKind::Text, $name, null, true, null);
    }

    /**
     * Attribute value slot: the value is coerced to string and escaped.
     *
     * The attribute name comes from the setter
     * (`->class(Slot::attr('classList'))` sets `class`); $name is the data key
     * holding the value. The bound value may be a string or any Stringable
     * (including a Raw); it is stringified and escaped for the attribute
     * context.
     *
     * @param string $name The data key holding the attribute value.
     * @return self
     */
    public static function attr(string $name): self
    {
        return new self(SlotKind::Attr, $name, null, true, null);
    }

    /**
     * Raw content slot: the value is emitted verbatim, without escaping.
     *
     * The bound value may be a string, any Stringable (including a Raw returned
     * by another component), or an iterable of such values: each is stringified
     * and the results are concatenated in order. Pass a Raw or a rendered list
     * of component markup directly, without casting to string or implode().
     *
     * @param string $name The slot name.
     * @return self
     */
    public static function raw(string $name): self
    {
        return new self(SlotKind::Raw, $name, null, true, null);
    }

    /**
     * Child component slot: the nested data scope is `$data[$name]`.
     *
     * @param string $name The slot name.
     * @param ShapeContract $shape The shape or bare tag tree rendering this slot's content.
     * @return self
     */
    public static function child(string $name, ShapeContract $shape): self
    {
        return new self(SlotKind::Child, $name, $shape, true, null);
    }

    /**
     * List slot: the value is an iterable of arrays, each rendered by $shape
     * and each being the nested scope of its item.
     *
     * @param string $name The slot name.
     * @param ShapeContract $shape The shape or bare tag tree rendering each item.
     * @return self
     */
    public static function each(string $name, ShapeContract $shape): self
    {
        return new self(SlotKind::Each, $name, $shape, true, null);
    }

    /**
     * Conditional slot: renders $then when `$data[$name]` is truthy, otherwise
     * $else (or nothing). A missing key is false and never throws; then/else
     * branches share the current data scope.
     *
     * @param string $name The slot name.
     * @param ShapeContract $then The shape or bare tag tree of the truthy branch.
     * @param ShapeContract|null $else The shape or bare tag tree of the falsy branch.
     * @return self
     */
    public static function if(string $name, ShapeContract $then, ?ShapeContract $else = null): self
    {
        return new self(SlotKind::If, $name, $then, false, false, $else);
    }

    /**
     * Heterogeneous list slot: every item is dispatched on `$item[$kindKey]`
     * to the matching shape; an unknown kind throws an InvalidArgumentException.
     *
     * Kind keys must be non-empty strings; PHP array keys that look numeric are
     * ints at runtime and cannot match the string kinds used by the generated
     * dispatch, so they are rejected here.
     *
     * @param string $name The slot name.
     * @param array<array-key, ShapeContract> $shapes Shapes or bare tag trees, by kind.
     * @param string $kindKey The key used to dispatch items by kind.
     * @return self
     */
    public static function eachKind(string $name, array $shapes, string $kindKey = 'kind'): self
    {
        if ($shapes === []) {
            throw new InvalidArgumentException("slot '{$name}' eachKind requires at least one shape.");
        }

        foreach (array_keys($shapes) as $kind) {
            if (!is_string($kind) || $kind === '') {
                throw new InvalidArgumentException("slot '{$name}' eachKind variants must be non-empty strings.");
            }
        }

        return new self(SlotKind::EachKind, $name, null, true, null, null, $shapes, $kindKey);
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

        return new self($this->kind, $this->name, $this->shape, $required, $this->default, $this->else, $this->variants, $this->kindKey);
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

        return new self($this->kind, $this->name, $this->shape, false, $value, $this->else, $this->variants, $this->kindKey);
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
