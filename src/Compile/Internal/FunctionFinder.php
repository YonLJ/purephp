<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

use Pure\Compile\Template;
use Pure\Component\Component;
use ReflectionFunction;

/**
 * Looks up the functions a unit file defined: the component call function and
 * the functions carrying a given attribute.
 *
 * The convention is that a unit's component function carries the component
 * name, and a unit may also define helpers (a bindings function, a shape
 * builder) next to it. Matching on the short name plus the defining file keeps
 * a same-named function from another unit out of the way; a `#[Component]`
 * mark makes the choice explicit so the call function may be renamed, and a
 * `#[Template]` mark keeps a builder that shares the component's name out of
 * the call-function slot.
 *
 * @internal
 */
final class FunctionFinder
{
    /**
     * @var array<string, list<ReflectionFunction>>|null The declared user functions grouped by defining file.
     */
    private static ?array $byFile = null;

    private static int $scanned = -1;

    private function __construct()
    {
    }

    /**
     * The component call function of a unit file: a function marked
     * `#[Component]` (no name, or naming this component), else a function
     * whose short name matches the component name and that is not marked
     * `#[Template]`.
     */
    public static function of(string $shortName, string $file): ?ReflectionFunction
    {
        foreach (self::attributed($file, Component::class) as $function) {
            foreach ($function->getAttributes(Component::class) as $attribute) {
                $declared = $attribute->newInstance()->name;

                if ($declared === null || strcasecmp($declared, $shortName) === 0) {
                    return $function;
                }
            }
        }

        $path = realpath($file) ?: $file;

        foreach (self::functionsByFile()[$path] ?? [] as $reflection) {
            $name = $reflection->getName();
            $position = strrpos($name, '\\');
            $short = $position === false ? $name : substr($name, $position + 1);

            // PHP function names are case-insensitive, so the component name
            // only has to match the short name.
            if (strcasecmp($short, $shortName) !== 0) {
                continue;
            }

            if ($reflection->getAttributes(Template::class) !== []) {
                continue;
            }

            return $reflection;
        }

        return null;
    }

    /**
     * The functions of one loaded file carrying a given attribute. The file
     * must already be declared: only functions that exist in this process are
     * visible, which the CLI loaders guarantee for every unit file.
     *
     * @param class-string $attribute The attribute class name.
     * @return list<ReflectionFunction>
     */
    public static function attributed(string $file, string $attribute): array
    {
        $path = realpath($file) ?: $file;
        $found = [];

        foreach (self::functionsByFile()[$path] ?? [] as $function) {
            if ($function->getAttributes($attribute) !== []) {
                $found[] = $function;
            }
        }

        return $found;
    }

    /**
     * The declared user functions grouped by their defining file. Functions
     * are never undeclared in a process, so the map is rebuilt only when the
     * number of declared functions grows (a newly required file).
     *
     * @return array<string, list<ReflectionFunction>>
     */
    private static function functionsByFile(): array
    {
        $functions = get_defined_functions()['user'];

        if (self::$byFile !== null && self::$scanned === count($functions)) {
            return self::$byFile;
        }

        self::$scanned = count($functions);
        self::$byFile = [];

        foreach ($functions as $function) {
            $reflection = new ReflectionFunction($function);
            $source = $reflection->getFileName();

            if ($source === false) {
                continue;
            }

            self::$byFile[realpath($source) ?: $source][] = $reflection;
        }

        return self::$byFile;
    }
}
