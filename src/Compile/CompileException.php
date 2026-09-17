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

    public static function attributeSlotInChildPosition(string $slotPath): self
    {
        return new self("attribute slots are not allowed in child position: '{$slotPath}'.");
    }

    public static function slotInAttributePosition(SlotKind $kind, string $slotPath): self
    {
        return new self("only attribute slots are allowed in attribute position, got '{$kind->name}' for '{$slotPath}'.");
    }

    /**
     * A map closure depends on runtime context that an artifact cannot carry.
     *
     * @param string $slotPath The path of the slot carrying the map.
     * @param string $reason Why the closure cannot be copied.
     */
    public static function mapInArtifact(string $slotPath, string $reason): self
    {
        return new self("the map on slot '{$slotPath}' cannot be copied into an artifact: {$reason}.");
    }
}
