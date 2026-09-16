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
    /** Escape text content. Already-escaped entities are left intact. */
    public static function text(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }

    /** Escape an attribute value. Already-escaped entities are re-encoded. */
    public static function attr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
