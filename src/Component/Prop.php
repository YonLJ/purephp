<?php

declare(strict_types=1);

namespace Pure\Component;

use Attribute;

/**
 * Declares the contract of one prepare() parameter.
 *
 * The parameters of a prepare() closure are the prop contract of a fluent
 * component call: PHP enforces their types and their default values. Some
 * facts about a prop cannot be read from a signature, and this attribute
 * states them so `pure check` verifies the intent instead of inferring it:
 *
 *     prepare: static function (
 *         #[Prop(slot: 'title')] string $text,
 *         #[Prop(item: 'value')] array $features,
 *         #[Prop(required: false)] ?string $class = null,
 *         #[Prop(deprecated: 'use size()')] ?string $width = null,
 *     ): array {
 *         return ['title' => $text, 'features' => ..., 'class' => $class];
 *     }
 *
 * - `slot` names the binding the prop fills; the parameter name is the
 *   default. When prepare() does not return one readable array literal, the
 *   declared slots are what the checker compares against the template.
 * - `required` states whether a caller must pass the prop; the default is the
 *   signature itself, where a parameter without a default value is required.
 *   A declaration that contradicts the signature is reported.
 * - `item` names the single slot each item of a list prop fills in the item
 *   shape of a `Slot::each` slot; the checker compares it with that shape.
 * - `deprecated` carries a migration hint; a call site that binds the prop is
 *   reported.
 *
 * The attribute is read by `pure check` only: it never takes part in
 * rendering, and a unit without declarations behaves exactly as before.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class Prop
{
    public function __construct(
        public readonly ?string $slot = null,
        public readonly ?bool $required = null,
        public readonly ?string $item = null,
        public readonly ?string $deprecated = null,
    ) {
    }
}
