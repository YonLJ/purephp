<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

use InvalidArgumentException;
use Pure\Core\Escaper;
use Stringable;

/**
 * Runtime value coercion used by generated renderers.
 *
 * @internal
 */
final class SlotRuntime
{
    /**
     * Coerce a slot value to escaped text content.
     *
     * Generated renderers inline this call for scalar values (see
     * CodeGenerator::valueExpr()) and only fall back here for null, Stringable
     * and invalid values, so the documented empty-null behaviour and the
     * path-bearing InvalidArgumentException stay in one place. Byte-identity
     * with Escaper::text() is locked by
     * CompileTest::testLiteralAndSlotEscapingStayByteIdentical.
     *
     * @param mixed $value The slot value.
     * @param string $path The slot path for error messages.
     * @return string The escaped text content.
     */
    public static function text(mixed $value, string $path): string
    {
        return htmlspecialchars(self::stringify($value, $path), Escaper::FLAGS, Escaper::ENCODING, false);
    }

    /**
     * Coerce a slot value to verbatim output.
     *
     * @param mixed $value The slot value.
     * @param string $path The slot path for error messages.
     * @return string The verbatim output.
     */
    public static function raw(mixed $value, string $path): string
    {
        return self::stringify($value, $path);
    }

    /**
     * Build one `name="value"` attribute chunk, omitting it for null values.
     *
     * Mirrors Tag::setAttr(): a null value leaves the attribute unset, `false`
     * omits it and `true` renders the name as its own value (`disabled`).
     *
     * The scalar branch is deliberately separate: attribute slots inside
     * `Slot::each()` loops run once per item, and merging it into stringify()
     * costs ~8% of a list-heavy page's render (measured on the 604-element
     * benchmark page).
     *
     * @param string $name The attribute name.
     * @param mixed $value The attribute value.
     * @param string $path The slot path for error messages.
     * @return string The serialized attribute chunk, or empty string if null.
     */
    public static function attrOpen(string $name, mixed $value, string $path): string
    {
        if (is_null($value)) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? Escaper::attribute($name, $name) : '';
        }

        if (is_scalar($value)) {
            return Escaper::attribute($name, (string)$value);
        }

        return Escaper::attribute($name, self::stringify($value, $path));
    }

    /**
     * Ensure a child component value is an array usable as a nested data scope.
     *
     * @param mixed $value The slot value.
     * @param string $path The slot path for error messages.
     * @return array<array-key, mixed>
     * @throws InvalidArgumentException When the value is not an array.
     */
    public static function scope(mixed $value, string $path): array
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException("slot '{$path}' must be an array, " . get_debug_type($value) . ' given.');
        }

        return $value;
    }

    /**
     * Ensure a list slot value is iterable.
     *
     * @param mixed $value The slot value.
     * @param string $path The slot path for error messages.
     * @return iterable<array-key, mixed>
     * @throws InvalidArgumentException When the value is not iterable.
     */
    public static function items(mixed $value, string $path): iterable
    {
        if (!is_iterable($value)) {
            throw new InvalidArgumentException("slot '{$path}' must be iterable, " . get_debug_type($value) . ' given.');
        }

        return $value;
    }

    /**
     * Validate a heterogeneous list item and return its discriminator.
     *
     * @param mixed $item The list item to validate.
     * @param string $kindKey The key used to dispatch items by kind.
     * @param string $path The slot path for error messages.
     * @param array<int, string> $allowed
     * @return string The validated kind discriminator.
     * @throws InvalidArgumentException When the item is invalid.
     */
    public static function kind(mixed $item, string $kindKey, string $path, array $allowed): string
    {
        if (!is_array($item)) {
            throw new InvalidArgumentException("slot '{$path}' must be an array, " . get_debug_type($item) . ' given.');
        }

        if (!array_key_exists($kindKey, $item)) {
            throw new InvalidArgumentException("slot '{$path}.{$kindKey}' is required but was not provided.");
        }

        $kind = $item[$kindKey];
        if (!is_string($kind) || !in_array($kind, $allowed, true)) {
            throw new InvalidArgumentException("slot '{$path}.{$kindKey}' must be one of " . self::listKinds($allowed) . ', ' . get_debug_type($kind) . ' given.');
        }

        return $kind;
    }

    /**
     * Format allowed kind values for error messages.
     *
     * @param array<int, string> $allowed
     * @return string The formatted list string.
     */
    private static function listKinds(array $allowed): string
    {
        return implode(', ', array_map(static fn (string $kind): string => "'{$kind}'", $allowed));
    }

    private static function stringify(mixed $value, string $path): string
    {
        if (is_null($value) || is_scalar($value)) {
            return (string)$value;
        }

        if ($value instanceof Stringable) {
            return (string)$value;
        }

        throw new InvalidArgumentException("slot '{$path}' must be stringable, " . get_debug_type($value) . ' given.');
    }
}
