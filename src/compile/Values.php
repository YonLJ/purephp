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
final class Values
{
    /** Coerce a slot value to escaped text content. */
    public static function text(mixed $value, string $path): string
    {
        return Escaper::text(self::stringify($value, $path));
    }

    /** Coerce a slot value to an escaped attribute value. */
    public static function attr(mixed $value, string $path): string
    {
        return Escaper::attr(self::stringify($value, $path));
    }

    /** Coerce a slot value to verbatim output. */
    public static function raw(mixed $value, string $path): string
    {
        return self::stringify($value, $path);
    }

    /**
     * Build one `name="value"` attribute chunk, omitting it for null values.
     *
     * Mirrors Tag::setAttr(), where a null value leaves the attribute unset.
     */
    public static function attrOpen(string $name, mixed $value, string $path): string
    {
        if (is_null($value)) {
            return '';
        }

        return ' ' . $name . '="' . self::attr($value, $path) . '"';
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
