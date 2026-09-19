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
 * development guard reports data keys outside it. Only the root scope is
 * collected — a child or list slot introduces a nested scope whose keys belong
 * to the parent component — while `Slot::if` branches stay in the current
 * scope and are walked.
 *
 * @internal
 */
final class RootSlots
{
    private function __construct()
    {
    }

    /**
     * @return list<string> The root slot names, in first-seen order.
     */
    public static function of(Tag $tree): array
    {
        $slots = [];
        self::collect($tree, $slots);

        return array_keys($slots);
    }

    /**
     * @param array<string, true> $slots
     */
    private static function collect(Tag $tag, array &$slots): void
    {
        $export = $tag->export();

        foreach ($export['attrs'] as $value) {
            if ($value instanceof Slot) {
                $slots[$value->name] = true;
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

            $slots[$child->name] = true;

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
}
