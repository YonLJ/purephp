<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

use ReflectionFunction;

/**
 * Looks up a function by short name among the functions a unit file defined.
 *
 * The convention is that a unit's component function carries the component
 * name, and a unit may also define helpers (a bindings function, a shape
 * builder) next to it. Matching on the short name plus the defining file keeps
 * a same-named function from another unit out of the way.
 *
 * @internal
 */
final class FunctionFinder
{
    private function __construct()
    {
    }

    public static function of(string $shortName, string $file): ?ReflectionFunction
    {
        $path = realpath($file) ?: $file;

        foreach (get_defined_functions()['user'] as $function) {
            $position = strrpos($function, '\\');
            $short = $position === false ? $function : substr($function, $position + 1);

            // get_defined_functions() lowercases user functions, and PHP
            // function names are case-insensitive, so the component name only
            // has to match the short name.
            if (strcasecmp($short, $shortName) !== 0) {
                continue;
            }

            $reflection = new ReflectionFunction($function);
            $source = $reflection->getFileName();

            if ($source !== false && (realpath($source) ?: $source) === $path) {
                return $reflection;
            }
        }

        return null;
    }
}
