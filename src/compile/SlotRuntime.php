<?php

declare(strict_types=1);

namespace Pure\Compile;

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
     * htmlspecialchars is called inline with the Escaper flags/encoding: the
     * extra userland hop costs ~10% of a compiled render. Byte-identity with
     * Escaper::text() is locked by CompileTest::testLiteralAndSlotEscapingStayByteIdentical.
     * Scalars and null take the inline branch, skipping the stringify() call;
     * other values are validated there.
     */
    public static function text(mixed $value, string $path): string
    {
        if (is_scalar($value) || $value === null) {
            return htmlspecialchars((string)$value, Escaper::FLAGS, Escaper::ENCODING, false);
        }

        return htmlspecialchars(self::stringify($value, $path), Escaper::FLAGS, Escaper::ENCODING, false);
    }

    /** Coerce a slot value to an escaped attribute value (see text() on the inlined call). */
    public static function attr(mixed $value, string $path): string
    {
        if (is_scalar($value) || $value === null) {
            return htmlspecialchars((string)$value, Escaper::FLAGS, Escaper::ENCODING);
        }

        return htmlspecialchars(self::stringify($value, $path), Escaper::FLAGS, Escaper::ENCODING);
    }

    /** Coerce a slot value to verbatim output. */
    public static function raw(mixed $value, string $path): string
    {
        if (is_scalar($value) || $value === null) {
            return (string)$value;
        }

        return self::stringify($value, $path);
    }

    /**
     * Build one `name="value"` attribute chunk, omitting it for null values.
     *
     * Mirrors Tag::setAttr(): a null value leaves the attribute unset, `false`
     * omits it and `true` renders the name as its own value (`disabled`);
     * scalars take the inline branch.
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
     * Ensure a sub-template value is an array usable as a nested data scope.
     *
     * @return array<array-key, mixed>
     */
    public static function sub(mixed $value, string $path): array
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException("slot '{$path}' must be an array, " . get_debug_type($value) . ' given.');
        }

        return $value;
    }

    /**
     * Ensure a list slot value is iterable.
     *
     * @return iterable<array-key, mixed>
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
     * @param array<int, string> $allowed
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

    /** @param array<int, string> $allowed */
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
