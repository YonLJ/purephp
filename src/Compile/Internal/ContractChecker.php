<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

use Pure\Component\Binds;
use Pure\Component\Prop;
use Pure\Component\Trusted;
use Pure\Core\SlotKind;
use Pure\Core\Suggestion;
use Pure\Core\Tag;
use ReflectionFunction;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use Stringable;
use Traversable;

/**
 * The contract checks behind `pure check`.
 *
 * A unit's contract is three things that must agree: the slots its template
 * reads, the bindings its component function passes to `render()`, and the
 * typed parameters of that function. The checker compares them statically and
 * reports errors (a mismatch that fails at runtime), warnings (a latent or
 * suspicious pairing) and info notes (what could not be read statically).
 *
 * @internal
 */
final class ContractChecker
{
    /**
     * Check one unit (or one shape file, with $name and $function null).
     *
     * @param string $file The unit file path, for messages.
     * @param ?string $name The registered component name.
     * @param Tag $tree The shape tree the unit builds.
     * @param ?ReflectionFunction $function The component function, when the file defines one.
     * @param ?ReflectionFunction $prepare The registered prepare() hook of a fluent unit.
     * @return list<Finding> The findings, in report order.
     */
    public function check(string $file, ?string $name, Tag $tree, ?ReflectionFunction $function, ?ReflectionFunction $prepare = null): array
    {
        $findings = [];
        $contract = RootSlots::manifest($tree);

        foreach ($contract as $slot => $info) {
            if (self::conflicting($info['kinds'])) {
                $findings[] = Finding::error(
                    "slot '{$slot}' is used as a value or raw slot and as a child or list scope; one data key cannot be both"
                );
            }
        }

        if ($name === null) {
            return $findings;
        }

        if ($prepare !== null) {
            return array_merge($findings, self::checkPrepare($file, $name, $contract, $prepare, $tree));
        }

        if ($function !== null && self::returnsCall($function)) {
            // A fluent call factory: the props are the slots, and the call
            // sites are checked against the manifest.
            $findings[] = Finding::info(
                'fluent unit: its props are the template slots, so the call sites are checked instead'
            );

            return $findings;
        }

        $parameters = [];

        if ($function === null) {
            $findings[] = Finding::info(
                "no function named '{$name}' is defined in this file; parameter types are not checked"
            );
        } else {
            foreach ($function->getParameters() as $parameter) {
                $parameters[$parameter->getName()] = $parameter;
            }

            foreach ($contract as $slot => $info) {
                if (self::conflicting($info['kinds'])) {
                    continue;
                }

                $parameter = $parameters[$slot] ?? null;

                if ($parameter === null) {
                    continue;
                }

                foreach (self::typeFindings($slot, $info, $parameter) as $finding) {
                    $findings[] = $finding;
                }
            }
        }

        $bindings = $function === null
            ? Bindings::inFile($file, $name)
            : Bindings::of($function, $name, $file);

        if (!$bindings['found'] || $bindings['dynamic']) {
            $findings[] = Finding::info(
                'slots are not compared: '
                . ($bindings['found'] ? 'render() unpacks its bindings' : 'no render() call targets this component')
            );
        } else {
            foreach (array_keys($bindings['keys']) as $key) {
                if (isset($contract[$key])) {
                    continue;
                }

                $nearest = Suggestion::nearest($key, array_keys($contract));
                $hint = $nearest === null ? '' : " (did you mean '{$nearest}'?)";

                $findings[] = Finding::error("render() binds '{$key}' but the template does not read it{$hint}");
            }

            foreach ($contract as $slot => $info) {
                if ($info['required'] && !isset($bindings['keys'][$slot])) {
                    $findings[] = Finding::error("required slot '{$slot}' is not bound by render()");
                }
            }

            if ($bindings['positional']) {
                $findings[] = Finding::error('render() passes data by position; slot values must be named');
            }
        }

        if ($bindings['scanned']) {
            foreach (array_keys($parameters) as $parameterName) {
                if (!isset($bindings['variables'][$parameterName]) && !isset($contract[$parameterName])) {
                    $findings[] = Finding::warning(
                        "parameter \${$parameterName} is neither used by the function nor a slot of the template"
                    );
                }
            }
        }

        return $findings;
    }

    /**
     * Check a fluent unit: the prepare() parameters are the prop contract, and
     * its returned array literal is what binds the template. Children bind the
     * reserved `children` slot from the call, not from prepare(), so a missing
     * `children` is not reported.
     *
     * A `#[Prop]` declaration makes the contract explicit: `slot` names the
     * binding (the parameter name by default), `required` states the caller
     * obligation, `item` the single slot each item of a list prop fills, and
     * `deprecated` a migration hint. Declarations are compared with the
     * signature and the template, and when prepare() does not return one
     * readable array literal they are what the required slots are checked
     * against.
     *
     * @param array<string, array{required: bool, kinds: array<string, true>}> $contract
     * @return list<Finding>
     */
    private static function checkPrepare(string $file, string $name, array $contract, ReflectionFunction $prepare, Tag $tree): array
    {
        $findings = [];
        $parameters = [];
        $declared = [];
        $slots = [];
        $trusted = [];

        foreach ($prepare->getParameters() as $parameter) {
            $parameterName = $parameter->getName();
            $parameters[$parameterName] = $parameter;
            $declaration = self::declaration($parameter);
            $markup = $parameter->getAttributes(Trusted::class) !== [];

            if ($declaration === null && !$markup) {
                continue;
            }

            if ($declaration !== null) {
                $declared[$parameterName] = $declaration;
            }

            if ($markup) {
                $trusted[$parameterName] = true;
            }

            $slots[$parameterName] = $declaration->slot ?? $parameterName;
        }

        $bySlot = [];
        $owner = [];

        foreach ($slots as $parameterName => $slot) {
            if (isset($owner[$slot])) {
                $findings[] = Finding::error("props \${$owner[$slot]} and \${$parameterName} declare the same slot '{$slot}'");
            }

            $owner[$slot] = $parameterName;
            $bySlot[$slot] = $parameters[$parameterName];
        }

        foreach ($contract as $slot => $info) {
            if (self::conflicting($info['kinds'])) {
                continue;
            }

            $parameter = $bySlot[$slot] ?? $parameters[$slot] ?? null;

            if ($parameter === null) {
                continue;
            }

            foreach (self::typeFindings($slot, $info, $parameter) as $finding) {
                $findings[] = $finding;
            }
        }

        foreach ($declared as $parameterName => $declaration) {
            foreach (self::declarationFindings($parameterName, $declaration, $parameters[$parameterName], $contract, $slots[$parameterName], $tree) as $finding) {
                $findings[] = $finding;
            }
        }

        foreach (array_keys($trusted) as $parameterName) {
            foreach (self::trustedFindings($parameterName, $parameters[$parameterName], $slots[$parameterName], $contract) as $finding) {
                $findings[] = $finding;
            }
        }

        $binds = self::bindings($prepare);
        $keys = Bindings::literalKeys($prepare);
        $covered = array_flip($slots);

        if ($binds !== null) {
            foreach ($binds->keys as $key) {
                $covered[$key] = $key;
            }
        }

        if ($keys === null) {
            if ($covered === []) {
                $findings[] = Finding::info('prepare() does not return one array literal; its bindings are not compared');
            } else {
                $findings[] = Finding::info('prepare() does not return one array literal; its bindings are read from the ' . self::declarationNames($binds, $slots !== []) . ' declarations');

                foreach ($contract as $slot => $info) {
                    if (!$info['required'] || $slot === 'children' || isset($covered[$slot])) {
                        continue;
                    }

                    $findings[] = Finding::error("required slot '{$slot}' is not covered by any declaration and prepare() does not return a readable array literal");
                }
            }
        } else {
            foreach (array_keys($keys) as $key) {
                if (isset($contract[$key])) {
                    continue;
                }

                $nearest = Suggestion::nearest($key, array_keys($contract));
                $hint = $nearest === null ? '' : " (did you mean '{$nearest}'?)";

                $findings[] = Finding::error("prepare() returns '{$key}' but the template does not read it{$hint}");
            }

            foreach ($contract as $slot => $info) {
                if ($info['required'] && $slot !== 'children' && !isset($keys[$slot])) {
                    $findings[] = Finding::error("required slot '{$slot}' is not returned by prepare()");
                }
            }

            foreach ($slots as $parameterName => $slot) {
                if (isset($keys[$slot])) {
                    continue;
                }

                $findings[] = Finding::error("prop \${$parameterName} declares slot '{$slot}', which prepare() does not return");
            }

            if ($binds !== null) {
                foreach ($binds->keys as $key) {
                    if (isset($keys[$key])) {
                        continue;
                    }

                    $findings[] = Finding::error("#[Binds] declares '{$key}', which prepare() does not return");
                }
            }
        }

        $bindings = Bindings::of($prepare, $name, $file);

        if ($bindings['scanned']) {
            foreach (array_keys($parameters) as $parameterName) {
                if (!isset($bindings['variables'][$parameterName]) && !isset($contract[$parameterName])) {
                    $findings[] = Finding::warning(
                        "parameter \${$parameterName} is neither used by prepare() nor a slot of the template"
                    );
                }
            }
        }

        return $findings;
    }

    /**
     * The `#[Binds]` declaration of a bindings hook, if it has one.
     */
    private static function bindings(ReflectionFunction $prepare): ?Binds
    {
        foreach ($prepare->getAttributes(Binds::class) as $attribute) {
            return $attribute->newInstance();
        }

        return null;
    }

    /**
     * The declaration attributes named in the info line, in reading order.
     */
    private static function declarationNames(?Binds $binds, bool $props): string
    {
        $names = [];

        if ($binds !== null) {
            $names[] = '#[Binds]';
        }

        if ($props) {
            $names[] = '#[Prop]';
        }

        return implode(' and ', $names);
    }

    /**
     * Check a `#[Trusted]` declaration: the prop must bind a raw slot, so the
     * markup is emitted verbatim, and the slot must not also be read as a text
     * slot, which would escape the same value elsewhere.
     *
     * @param array<string, array{required: bool, kinds: array<string, true>}> $contract
     * @return list<Finding>
     */
    private static function trustedFindings(string $parameterName, ReflectionParameter $parameter, string $slot, array $contract): array
    {
        $findings = [];
        $type = $parameter->getType();

        if ($type !== null) {
            [$names, , $mixed] = self::typeInfo($type);

            if (!$mixed && self::scalarOnly($names)) {
                $findings[] = Finding::warning(
                    "prop \${$parameterName} is declared as markup (#[Trusted]) but typed " . (string)$type
                    . '; type it Markup|Stringable (or mixed) to accept markup, or drop the attribute and wrap the value in Raw::of() at the call site'
                );
            }
        }

        $kinds = $contract[$slot]['kinds'] ?? [];

        if ($kinds === []) {
            $findings[] = Finding::error("prop \${$parameterName} is declared as markup (#[Trusted]) but slot '{$slot}' is not read by the template");

            return $findings;
        }

        if (!isset($kinds[SlotKind::Raw->name])) {
            $kind = self::kindOf($kinds);

            $findings[] = Finding::error(
                "prop \${$parameterName} is declared as markup (#[Trusted]) but slot '{$slot}' is a "
                . ($kind === null ? 'scope' : self::label($kind))
                . '; markup bound to it would be escaped or interpreted as data'
            );

            return $findings;
        }

        if (isset($kinds[SlotKind::Value->name])) {
            $findings[] = Finding::error("prop \${$parameterName} is declared as markup (#[Trusted]) but slot '{$slot}' is also read as a text slot, which would escape the same value");
        }

        return $findings;
    }

    /**
     * Whether every type name is a scalar, so the parameter cannot receive a
     * `Pure\Core\Markup` value.
     *
     * @param list<string> $names
     */
    private static function scalarOnly(array $names): bool
    {
        if ($names === []) {
            return false;
        }

        foreach ($names as $name) {
            if (!in_array($name, ['string', 'int', 'float', 'bool', 'true', 'false'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The `#[Prop]` declaration of a parameter, if it has one.
     */
    private static function declaration(ReflectionParameter $parameter): ?Prop
    {
        foreach ($parameter->getAttributes(Prop::class) as $attribute) {
            return $attribute->newInstance();
        }

        return null;
    }

    /**
     * Check one declaration against the signature and the template.
     *
     * @param array<string, array{required: bool, kinds: array<string, true>}> $contract
     * @param string $slot The declared slot name of the parameter.
     * @return list<Finding>
     */
    private static function declarationFindings(string $parameterName, Prop $prop, ReflectionParameter $parameter, array $contract, string $slot, Tag $tree): array
    {
        $findings = [];

        if (!isset($contract[$slot])) {
            $nearest = Suggestion::nearest($slot, array_keys($contract));
            $hint = $nearest === null ? '' : " (did you mean '{$nearest}'?)";

            $findings[] = Finding::error("prop \${$parameterName} declares slot '{$slot}', which the template does not read{$hint}");
        }

        if ($prop->required !== null) {
            $optional = $parameter->isDefaultValueAvailable() || $parameter->isVariadic();

            if ($prop->required && $optional) {
                $findings[] = Finding::warning("prop \${$parameterName} is declared required but its parameter has a default value; callers may omit it");
            } elseif (!$prop->required && !$optional) {
                $findings[] = Finding::error("prop \${$parameterName} is declared optional but its parameter has no default value; callers must pass it");
            }
        }

        if ($prop->item !== null) {
            foreach (self::itemFindings($parameterName, $prop->item, $slot, $contract, $tree) as $finding) {
                $findings[] = $finding;
            }
        }

        return $findings;
    }

    /**
     * Check an `item:` declaration against the item shape of a list slot: the
     * shape must read exactly the declared slot, as a scalar.
     *
     * @param array<string, array{required: bool, kinds: array<string, true>}> $contract
     * @return list<Finding>
     */
    private static function itemFindings(string $parameterName, string $item, string $slot, array $contract, Tag $tree): array
    {
        if (!isset($contract[$slot]['kinds'][SlotKind::Each->name])) {
            return [
                Finding::error("prop \${$parameterName} declares item: '{$item}' but slot '{$slot}' is not a list slot"),
            ];
        }

        $items = RootSlots::itemSlots($tree, $slot);
        $names = array_keys($items);

        if ($names === []) {
            return [
                Finding::error("prop \${$parameterName} declares item: '{$item}' but the item shape of slot '{$slot}' reads no slots"),
            ];
        }

        if (!in_array($item, $names, true) || count($names) > 1) {
            $nearest = Suggestion::nearest($item, $names);
            $hint = !in_array($item, $names, true) && $nearest !== null ? " (did you mean '{$nearest}'?)" : '';

            return [
                Finding::error("prop \${$parameterName} declares one item slot '{$item}' but the item shape of slot '{$slot}' reads " . self::names($names) . $hint),
            ];
        }

        $kind = self::kindOf($items[$item]['kinds']);

        if ($kind !== null && $kind !== SlotKind::Value && $kind !== SlotKind::Raw) {
            return [
                Finding::error("prop \${$parameterName} declares item: '{$item}' but the item shape reads it as a " . self::label($kind)),
            ];
        }

        return [];
    }

    /**
     * @param list<string> $names
     */
    private static function names(array $names): string
    {
        return "'" . implode("', '", $names) . "'";
    }

    /**
     * Whether a component function is a fluent call factory, i.e. declares the
     * `Pure\Component\Call` return type of the documented one-liner.
     */
    private static function returnsCall(ReflectionFunction $function): bool
    {
        $type = $function->getReturnType();

        if (!$type instanceof ReflectionNamedType) {
            return false;
        }

        $name = $type->getName();
        $position = strrpos($name, '\\');

        return ($position === false ? $name : substr($name, $position + 1)) === 'Call';
    }

    /**
     * Whether a slot name mixes a scalar-ish kind with a scope-ish kind.
     *
     * @param array<string, true> $kinds
     */
    private static function conflicting(array $kinds): bool
    {
        $scalar = isset($kinds[SlotKind::Value->name]) || isset($kinds[SlotKind::Raw->name]);
        $scope = isset($kinds[SlotKind::Child->name]) || isset($kinds[SlotKind::Each->name]);

        return $scalar && $scope;
    }

    /**
     * @param array{required: bool, kinds: array<string, true>} $info
     * @return list<Finding>
     */
    private static function typeFindings(string $slot, array $info, ReflectionParameter $parameter): array
    {
        $kind = self::kindOf($info['kinds']);

        if ($kind === null || $kind === SlotKind::If) {
            return [];
        }

        $type = $parameter->getType();

        if ($type === null) {
            return [];
        }

        [$names, $allowsNull, $mixed] = self::typeInfo($type);
        $name = $parameter->getName();

        if (!self::accepts($names, $kind)) {
            return [
                Finding::error(
                    "slot '{$slot}' is a " . self::label($kind) . " but parameter \${$name} is typed " . (string)$type
                ),
            ];
        }

        if ($info['required'] && $allowsNull && !$mixed) {
            return [
                Finding::warning(
                    "parameter \${$name} is nullable but slot '{$slot}' is required; binding null throws MissingSlotException"
                ),
            ];
        }

        return [];
    }

    /**
     * The kind to compare a parameter type against: a scope kind wins over a
     * scalar kind (mixed kinds are reported as conflicts and skipped).
     *
     * @param array<string, true> $kinds
     */
    private static function kindOf(array $kinds): ?SlotKind
    {
        foreach ([SlotKind::Child, SlotKind::Each, SlotKind::Raw, SlotKind::Value, SlotKind::If] as $kind) {
            if (isset($kinds[$kind->name])) {
                return $kind;
            }
        }

        return null;
    }

    /**
     * @param list<string> $names The type names of the parameter.
     * @param SlotKind $kind The slot kind.
     */
    private static function accepts(array $names, SlotKind $kind): bool
    {
        $stringable = false;
        $iterable = false;
        $array = false;

        foreach ($names as $name) {
            if (in_array($name, ['string', 'int', 'float', 'bool', 'true', 'false', 'mixed'], true)) {
                $stringable = true;
            }

            if (in_array($name, ['array', 'iterable', 'mixed'], true)) {
                $iterable = true;
            }

            if (in_array($name, ['array', 'mixed'], true)) {
                $array = true;
            }

            if (class_exists($name) || interface_exists($name)) {
                $stringable = $stringable || is_a($name, Stringable::class, true);
                $iterable = $iterable || is_a($name, Traversable::class, true);
            }
        }

        return match ($kind) {
            SlotKind::Value => $stringable,
            SlotKind::Raw => $stringable || $iterable,
            SlotKind::Child => $array,
            SlotKind::Each => $iterable,
            SlotKind::If => true,
        };
    }

    /**
     * @param ReflectionType $type The parameter type.
     * @return array{0: list<string>, 1: bool, 2: bool} The type names, whether
     *     null is allowed, and whether `mixed` is among them.
     */
    private static function typeInfo(ReflectionType $type): array
    {
        $names = [];

        if ($type instanceof ReflectionNamedType) {
            $names[] = $type->getName();
        } elseif ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $member) {
                foreach (self::typeInfo($member)[0] as $name) {
                    $names[] = $name;
                }
            }
        } elseif ($type instanceof ReflectionIntersectionType) {
            foreach ($type->getTypes() as $member) {
                foreach (self::typeInfo($member)[0] as $name) {
                    $names[] = $name;
                }
            }
        }

        return [$names, $type->allowsNull(), in_array('mixed', $names, true)];
    }

    private static function label(SlotKind $kind): string
    {
        return match ($kind) {
            SlotKind::Value => 'text slot',
            SlotKind::Raw => 'raw slot',
            SlotKind::Child => 'child scope',
            SlotKind::Each => 'list slot',
            SlotKind::If => 'condition',
        };
    }
}
