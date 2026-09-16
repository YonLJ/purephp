<?php

declare(strict_types=1);

namespace Pure\Core;

/**
 * Single source of truth for output escaping.
 *
 * Used by both the string renderer (Tag::render()) and the compiled renderer
 * (Pure\Compile\*), so both paths produce byte-identical output.
 *
 * @internal
 */
final class Escaper
{
    /** Flags shared with the compiled renderer's value coercion. */
    public const FLAGS = ENT_QUOTES | ENT_SUBSTITUTE;

    /** Encoding shared with the compiled renderer's value coercion. */
    public const ENCODING = 'UTF-8';

    /** Escape text content. Already-escaped entities are left intact. */
    public static function text(string $value): string
    {
        return htmlspecialchars($value, self::FLAGS, self::ENCODING, false);
    }

    /** Escape an attribute value. Already-escaped entities are re-encoded. */
    public static function attr(string $value): string
    {
        return htmlspecialchars($value, self::FLAGS, self::ENCODING);
    }
}
