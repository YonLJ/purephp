<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

use Pure\Compile\Compile;
use Pure\Core\Raw;
use Pure\Core\Slot;
use Pure\Core\SlotKind;
use Pure\Core\Tag;

/**
 * Canonical structural fingerprint of a shape tree.
 *
 * Consumes the shared traversal (ShapeWalker) and records the same paths and
 * ordering the code generator sees, so cache keys and generated code stay
 * aligned.
 *
 * @internal
 */
final class ShapeIndex implements ShapeVisitor
{
    /** @var string[] */
    private array $parts = [];

    private string $id = '';

    private function __construct()
    {
    }

    public static function of(Tag $tree): self
    {
        $index = new self();
        (new ShapeWalker($index))->walk($tree);

        $salt = Compile::CACHE_VERSION . "\x00" . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $index->id = sha1(implode("\x00", $index->parts) . "\x00" . $salt);

        return $index;
    }

    public function id(): string
    {
        return $this->id;
    }

    /**
     * @param array{tagName: string, selfClose: bool, attrs: array<string, string|Slot>, children: array<int, mixed>} $export
     */
    public function tagOpen(Tag $tag, string $path, array $export): bool
    {
        $this->parts[] = '<' . $export['tagName'];
        $this->parts[] = 'selfClose:' . ($export['selfClose'] ? '1' : '0');

        return true;
    }

    public function attribute(string $key, string|Slot $value, string $slotPath): void
    {
        // The attribute name belongs to the identity: its slot path carries the
        // slot name only, so `->class(Slot::attr('x'))` and `->id(Slot::attr('x'))`
        // would otherwise share a fingerprint and share a cached renderer.
        $this->parts[] = 'attr:' . $key . '=' . ($value instanceof Slot
            ? $this->describeSlot($value, $slotPath)
            : $value);
    }

    public function tagSelfClose(): void
    {
    }

    public function contentStart(): void
    {
    }

    public function tagClose(string $tagName): void
    {
        $this->parts[] = '</' . $tagName;
    }

    public function text(string $text): void
    {
        $this->parts[] = 'text:' . $text;
    }

    public function raw(Raw $raw): void
    {
        $this->parts[] = 'raw:' . (string)$raw;
    }

    public function slotEnter(Slot $slot, string $slotPath): void
    {
        $this->parts[] = $this->describeSlot($slot, $slotPath);

        if ($slot->kind === SlotKind::EachKind) {
            $this->parts[] = 'kindKey:' . ($slot->kindKey ?? 'kind');
        }
    }

    public function slotBranch(string|int $label): void
    {
        $this->parts[] = 'branch:' . $label;
    }

    public function slotLeave(Slot $slot, string $slotPath): void
    {
    }

    private function describeSlot(Slot $slot, string $slotPath): string
    {
        // A null default is the common case (every required slot) and encodes
        // as 'N' without the serialize() call.
        $default = $slot->default === null ? 'N' : serialize($slot->default);

        return 'slot:' . $slot->kind->name . ':' . $slotPath
            . ':required:' . ($slot->required ? '1' : '0')
            . ':default:' . $default;
    }
}
