<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Discovery of the unit files a CLI path argument names.
 *
 * Shared by `pure compile` and `pure check`, so both commands see the same
 * files in the same order and report the same discovery errors.
 *
 * @internal
 */
final class UnitFinder
{
    private function __construct()
    {
    }

    /**
     * @param string $path A file or directory argument.
     * @return list<string> The `*.shape.php` and `*.cmp.php` files to process.
     */
    public static function discover(string $path): array
    {
        if (is_file($path)) {
            ArtifactCompiler::artifactPath($path);

            return [$path];
        }

        if (is_dir($path)) {
            $found = [];
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file instanceof SplFileInfo || !$file->isFile()) {
                    continue;
                }

                foreach (ArtifactCompiler::UNIT_SUFFIXES as $suffix) {
                    if (str_ends_with($file->getPathname(), $suffix)) {
                        $found[] = $file->getPathname();

                        break;
                    }
                }
            }

            if ($found === []) {
                throw new InvalidArgumentException(
                    "no " . implode(' or ', array_map(static fn (string $s): string => '*' . $s, ArtifactCompiler::UNIT_SUFFIXES)) . " files found in '{$path}'."
                );
            }

            sort($found);

            return $found;
        }

        throw new InvalidArgumentException("'{$path}' does not exist.");
    }
}
