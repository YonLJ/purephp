<?php

declare(strict_types=1);

namespace Pure\Component;

use Attribute;

/**
 * Declares that a prepare() parameter carries already-rendered markup.
 *
 * A raw slot (`Slot::raw()`) emits its value verbatim, so the prop feeding it
 * is a trust boundary: whatever the caller passes is emitted as markup. The
 * attribute makes that boundary explicit on the prop:
 *
 *     prepare: static function (
 *         #[Trusted] string $icon,
 *     ): array {
 *         return ['icon' => $icon];
 *     }
 *
 * `pure check` verifies that the prop binds a raw slot (markup bound to a text
 * slot would be escaped), and the development guard warns when a call passes
 * something that is not `Pure\Core\Markup` — a plain string, for instance —
 * because that is exactly where untrusted input can reach the output. The
 * attribute never changes rendering: it only states the intent that the
 * checks enforce.
 *
 * A prop that carries markup and its slot name is not the parameter name also
 * needs `#[Prop(slot: '...')]`.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class Trusted
{
}
