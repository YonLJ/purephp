<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

use Closure;
use RuntimeException;

/**
 * Loading of `*.cmp.php` unit files for the CLI commands.
 *
 * A unit file registers its factories as a side effect of being required, so
 * both `pure compile` and `pure check` load it the same way: with the registry
 * wired in, and with anything the file echoes discarded.
 *
 * @internal
 */
final class UnitLoader
{
    /**
     * @param (Closure(string): array<string, array{factory: Closure(): mixed}>)|null $units
     *     Resolves the units registered by a `*.cmp.php` file; null disables
     *     unit support (plain ArtifactCompiler use).
     */
    public function __construct(private readonly ?Closure $units = null)
    {
    }

    /**
     * The units a file registers: null for a shape file, the unit map for a
     * `*.cmp.php` file.
     *
     * @param string $file The discovered file.
     * @return array<string, array{factory: Closure(): mixed}>|null
     */
    public function unitsOf(string $file): ?array
    {
        if (!str_ends_with($file, '.cmp.php')) {
            return null;
        }

        if ($this->units === null) {
            throw new RuntimeException('unit files need the component registry; run `pure compile` through bin/pure.');
        }

        $level = ob_get_level();
        ob_start();

        try {
            (static fn (string $path): mixed => require_once $path)($file);
        } finally {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }

        return ($this->units)($file);
    }
}
