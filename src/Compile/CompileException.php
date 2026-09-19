<?php

declare(strict_types=1);

namespace Pure\Compile;

use LogicException;
use Pure\Core\SlotKind;

/**
 * Compile-time contract violations reachable from user-authored shapes, with
 * one named constructor per case so messages stay consistent and tests can
 * assert on the type.
 *
 * Internal protocol invariants are reported as plain LogicException instead.
 */
final class CompileException extends LogicException
{
    public static function missingShape(string $slotPath): self
    {
        return new self("slot '{$slotPath}' is missing a shape.");
    }

    public static function slotInAttributePosition(SlotKind $kind, string $slotPath): self
    {
        return new self("only value slots are allowed in attribute position, got '{$kind->name}' for '{$slotPath}'.");
    }

    public static function markupInShape(string $class): self
    {
        return new self(
            "a component call ('{$class}') cannot be part of a data-free shape; "
            . 'render it into a raw slot instead, e.g. Slot::raw(\'children\').'
        );
    }
}
