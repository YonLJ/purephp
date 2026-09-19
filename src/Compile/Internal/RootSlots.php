<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

use Pure\Core\ShapeContract;
use Pure\Core\Slot;
use Pure\Core\SlotKind;
use Pure\Core\Tag;

/**
 * The data keys of the root scope of a shape tree.
 *
 * The manifest is what a compiled renderer knows about its bindings: the
 * development guard reports data keys outside it, and `pure check` compares it
 * against the component function. Only the root scope is collected — a child or
 * list slot introduces a nested scope whose keys belong to the parent
 * component — while `Slot::if` branches stay in the current scope and are
 * walked.
 *
 * @internal
 */
final class RootSlots
{
    private function __construct()
    {
    }

    /**
     * The root slot names, in first-seen order.
     *
     * @param Tag $tree The shape tree.
     * @return list<string>
     */
    public static function of(Tag $tree): array
    {
        return array_keys(self::manifest($tree));
    }

    /**
     * The root slot contract: per slot name, whether any occurrence is required
     * and which kinds use it.
     *
     * @param Tag $tree The shape tree.
     * @return array<string, array{required: bool, kinds: array<string, true>}>
     */
    public static function manifest(Tag $tree): array
    {
        $slots = [];
        self::collect($tree, $slots);

        return $slots;
    }

    /**
     * @param array<string, array{required: bool, kinds: array<string, true>}> $slots
     */
    private static function collect(Tag $tag, array &$slots): void
    {
        $export = $tag->export();

        foreach ($export['attrs'] as $value) {
            if ($value instanceof Slot) {
                self::add($slots, $value);
            }
        }

        if ($export['selfClose']) {
            return;
        }

        foreach ($export['children'] as $child) {
            if ($child instanceof Tag) {
                self::collect($child, $slots);

                continue;
            }

            if (!$child instanceof Slot) {
                continue;
            }

            self::add($slots, $child);

            if ($child->kind === SlotKind::Child || $child->kind === SlotKind::Each) {
                continue;
            }

            if ($child->kind === SlotKind::If) {
                if ($child->shape !== null) {
                    self::collect($child->shape->tree(), $slots);
                }

                if ($child->else !== null) {
                    self::collect($child->else->tree(), $slots);
                }
            }
        }
    }

    /**
     * The merged manifest of the item shapes of a list slot: the keys each
     * item scope reads, across every `Slot::each($slot, ...)` in the tree.
     *
     * An empty result means no list slot of that name holds a shape that reads
     * keys of its own, so `pure check` can tell a text slot from a list slot
     * whose items are static markup.
     *
     * @param Tag $tree The shape tree.
     * @param string $slot The list slot name.
     * @return array<string, array{required: bool, kinds: array<string, true>}>
     */
    public static function itemSlots(Tag $tree, string $slot): array
    {
        $items = [];

        foreach (self::itemShapes($tree, $slot) as $shape) {
            foreach (self::manifest($shape->tree()) as $name => $info) {
                $entry = $items[$name] ?? ['required' => false, 'kinds' => []];
                $entry['required'] = $entry['required'] || $info['required'];

                foreach (array_keys($info['kinds']) as $kind) {
                    $entry['kinds'][$kind] = true;
                }

                $items[$name] = $entry;
            }
        }

        return $items;
    }

    /**
     * The item shapes of a list slot, in tree order.
     *
     * @param Tag $tree The shape tree.
     * @param string $slot The list slot name.
     * @return list<ShapeContract>
     */
    private static function itemShapes(Tag $tree, string $slot): array
    {
        $shapes = [];
        $export = $tree->export();

        if ($export['selfClose']) {
            return $shapes;
        }

        foreach ($export['children'] as $child) {
            if ($child instanceof Tag) {
                foreach (self::itemShapes($child, $slot) as $shape) {
                    $shapes[] = $shape;
                }

                continue;
            }

            if ($child instanceof Slot && $child->kind === SlotKind::Each && $child->name === $slot && $child->shape !== null) {
                $shapes[] = $child->shape;
            }
        }

        return $shapes;
    }

    /**
     * @param array<string, array{required: bool, kinds: array<string, true>}> $slots
     */
    private static function add(array &$slots, Slot $slot): void
    {
        $entry = $slots[$slot->name] ?? ['required' => false, 'kinds' => []];
        $entry['required'] = $entry['required'] || $slot->required;
        $entry['kinds'][$slot->kind->name] = true;

        $slots[$slot->name] = $entry;
    }
}
