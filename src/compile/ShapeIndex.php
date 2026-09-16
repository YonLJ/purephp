<?php

declare(strict_types=1);

namespace Pure\Compile;

use Closure;
use Pure\Core\Raw;
use Pure\Core\Slot;
use Pure\Core\SlotKind;
use Pure\Core\Tag;
use ReflectionFunction;

/**
 * Canonical structural fingerprint of a shape tree.
 *
 * Consumes the shared traversal (ShapeWalker) and records the same paths,
 * ordering and map keys the code generator sees, so cache keys and generated
 * code stay aligned.
 *
 * @internal
 */
final class ShapeIndex implements ShapeVisitor
{
    /** @var string[] */
    private array $parts = [];

    /** @var array<string, Closure> */
    private array $maps = [];

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

    /** @return array<string, Closure> */
    public function maps(): array
    {
        return $this->maps;
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
        $this->parts[] = $value instanceof Slot
            ? $this->describeSlot($value, $slotPath)
            : 'attr:' . $key . '=' . $value;
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

    public function slotEnter(Slot $slot, string $slotPath, ?string $mapKey): void
    {
        $this->parts[] = $this->describeSlot($slot, $slotPath);

        if ($slot->kind === SlotKind::EachAny) {
            $this->parts[] = 'kindKey:' . ($slot->kindKey ?? 'kind');
        }

        if ($mapKey !== null && $slot->map !== null) {
            $this->maps[$mapKey] = $slot->map;
            $this->parts[] = 'map:' . $mapKey . ':' . self::closureFingerprint($slot->map);
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
        return 'slot:' . $slot->kind->name . ':' . $slotPath
            . ':required:' . ($slot->required ? '1' : '0')
            . ':default:' . serialize($slot->default);
    }

    private static function closureFingerprint(Closure $closure): string
    {
        $ref = new ReflectionFunction($closure);

        return ($ref->getFileName() ?: '?') . ':' . $ref->getStartLine() . ':' . $ref->getEndLine();
    }
}
