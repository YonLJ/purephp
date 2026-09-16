<?php

declare(strict_types=1);

namespace Pure\Compile;

use Pure\Core\Raw;
use Pure\Core\Slot;
use Pure\Core\Tag;

/**
 * Consumer of the single shape-tree traversal (see ShapeWalker).
 *
 * Events arrive in traversal order. tagOpen() returning false skips the
 * subtree, which the code generator uses to fold slot-free markup into a
 * single literal.
 *
 * @internal
 */
interface ShapeVisitor
{
    /**
     * @param array{tagName: string, selfClose: bool, attrs: array<string, string|Slot>, children: array<int, mixed>} $export
     *
     * @return bool false to skip this tag's attributes and children
     */
    public function tagOpen(Tag $tag, string $path, array $export): bool;

    public function attribute(string $key, string|Slot $value, string $slotPath): void;

    /** Attributes are complete and the tag is self-closing. */
    public function tagSelfClose(): void;

    /** Attributes are complete and the tag has children. */
    public function contentStart(): void;

    public function tagClose(string $tagName): void;

    public function text(string $text): void;

    public function raw(Raw $raw): void;

    /** A slot in child position; its subtree events follow, then slotLeave(). */
    public function slotEnter(Slot $slot, string $slotPath, ?string $mapKey): void;

    /** Branch boundary: 0 then / 1 else for Slot::if, the kind string for Slot::eachAny. */
    public function slotBranch(string|int $label): void;

    public function slotLeave(Slot $slot, string $slotPath): void;
}
