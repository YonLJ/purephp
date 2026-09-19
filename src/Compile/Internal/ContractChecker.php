<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

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
            return array_merge($findings, self::checkPrepare($file, $name, $contract, $prepare));
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
     * @param array<string, array{required: bool, kinds: array<string, true>}> $contract
     * @return list<Finding>
     */
    private static function checkPrepare(string $file, string $name, array $contract, ReflectionFunction $prepare): array
    {
        $findings = [];
        $parameters = [];

        foreach ($prepare->getParameters() as $parameter) {
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

        $keys = Bindings::literalKeys($prepare);

        if ($keys === null) {
            $findings[] = Finding::info('prepare() does not return one array literal; its bindings are not compared');
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
