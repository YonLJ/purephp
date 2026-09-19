<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

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
