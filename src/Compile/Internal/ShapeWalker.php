<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

use Pure\Compile\CompileException;
use Pure\Core\Raw;
use Pure\Core\ShapeContract;
use Pure\Core\Slot;
use Pure\Core\SlotKind;
use Pure\Core\Tag;

/**
 * The single traversal of shape trees.
 *
 * Both the structure fingerprint (ShapeIndex) and the code generator
 * (CodeGenerator) consume this walk, so paths, traversal order and map keys
 * stay aligned by construction.
 *
 * Map keys combine the slot path with the occurrence of that path, because
 * duplicate paths are legal (siblings, if branches) and must keep distinct
 * closures.
 *
 * @internal
 */
final class ShapeWalker
{
    /** @var array<string, int> */
    private array $mapCounts = [];

    public function __construct(private readonly ShapeVisitor $visitor)
    {
    }

    public function walk(Tag $tree): void
    {
        $this->tag($tree, '');
    }

    private function tag(Tag $tag, string $path): void
    {
        $export = $tag->export();

        if (!$this->visitor->tagOpen($tag, $path, $export)) {
            return;
        }

        foreach ($export['attrs'] as $key => $value) {
            $this->visitor->attribute((string)$key, $value, self::slotPath($path, (string)$key));
        }

        if ($export['selfClose']) {
            $this->visitor->tagSelfClose();

            return;
        }

        $this->visitor->contentStart();

        foreach ($export['children'] as $child) {
            if ($child instanceof Tag) {
                $this->tag($child, $path);

                continue;
            }

            if ($child instanceof Raw) {
                $this->visitor->raw($child);

                continue;
            }

            if ($child instanceof Slot) {
                $this->slot($child, $path);

                continue;
            }

            $this->visitor->text((string)$child);
        }

        $this->visitor->tagClose($export['tagName']);
    }

    private function slot(Slot $slot, string $path): void
    {
        $slotPath = self::slotPath($path, $slot->name);
        $mapKey = $this->mapKey($slot, $slotPath);

        $this->visitor->slotEnter($slot, $slotPath, $mapKey);

        switch ($slot->kind) {
            case SlotKind::Sub:
                $this->tag(self::shapeTree($slot->shape, $slotPath), $slotPath);

                break;
            case SlotKind::Each:
                $this->tag(self::shapeTree($slot->shape, $slotPath), $slotPath . '[]');

                break;
            case SlotKind::If:
                $this->visitor->slotBranch(0);
                $this->tag(self::shapeTree($slot->shape, $slotPath), $slotPath);
                if ($slot->else !== null) {
                    $this->visitor->slotBranch(1);
                    $this->tag($slot->else->tree(), $slotPath);
                }

                break;
            case SlotKind::EachAny:
                foreach ($slot->variants as $kind => $variant) {
                    $this->visitor->slotBranch((string)$kind);
                    $this->tag($variant->tree(), $slotPath . '[]');
                }

                break;
            case SlotKind::Text:
            case SlotKind::Attr:
            case SlotKind::Raw:
                break;
        }

        $this->visitor->slotLeave($slot, $slotPath);
    }

    private function mapKey(Slot $slot, string $slotPath): ?string
    {
        if ($slot->map === null) {
            return null;
        }

        $mapPath = $slot->kind === SlotKind::Sub ? $slotPath : $slotPath . '[]';
        $occurrence = $this->mapCounts[$mapPath] = ($this->mapCounts[$mapPath] ?? 0) + 1;

        return $mapPath . '#' . $occurrence;
    }

    private static function shapeTree(?ShapeContract $shape, string $slotPath): Tag
    {
        if ($shape === null) {
            throw CompileException::missingShape($slotPath);
        }

        return $shape->tree();
    }

    private static function slotPath(string $path, string $name): string
    {
        return $path === '' ? $name : $path . '.' . $name;
    }
}
