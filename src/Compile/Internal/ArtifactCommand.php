<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

/**
 * The `pure compile` command: turns shape files into artifacts.
 *
 * @internal
 */
final class ArtifactCommand
{
    private const USAGE = <<<'USAGE'
        Pure shape compiler.

        Usage:
          pure compile <path>... [--check] [--plain]

        Compiles every *.shape.php file that returns a Pure\Compile\Shape into a
        sibling *.pure.php artifact. Directories are searched recursively.

          --check      report stale or missing files without writing (exit 1)
          --plain      also write a *.plain.php view: markup and native PHP that
                       renders without purephp installed
          -h, --help   show this help

        USAGE;

    /**
     * @param list<string> $argv The raw arguments, including the program name.
     * @param resource|null $stdout The output stream, defaults to STDOUT.
     * @param resource|null $stderr The error stream, defaults to STDERR.
     * @return int The exit code.
     */
    public function run(array $argv, $stdout = null, $stderr = null): int
    {
        $stdout ??= STDOUT;
        $stderr ??= STDERR;

        $arguments = array_slice($argv, 1);
        $command = array_shift($arguments);

        if ($command === null) {
            fwrite($stderr, self::USAGE);

            return 1;
        }

        if ($command === '-h' || $command === '--help') {
            fwrite($stdout, self::USAGE);

            return 0;
        }

        if ($command !== 'compile') {
            fwrite($stderr, "pure: unknown command '{$command}'.\n\n" . self::USAGE);

            return 1;
        }

        $check = false;
        $plain = false;
        $paths = [];

        foreach ($arguments as $argument) {
            if ($argument === '--check') {
                $check = true;

                continue;
            }

            if ($argument === '--plain') {
                $plain = true;

                continue;
            }

            if ($argument === '-h' || $argument === '--help') {
                fwrite($stdout, self::USAGE);

                return 0;
            }

            if (str_starts_with($argument, '-')) {
                fwrite($stderr, "pure: unknown option '{$argument}'.\n");

                return 1;
            }

            $paths[] = $argument;
        }

        if ($paths === []) {
            fwrite($stderr, "pure: compile needs at least one file or directory.\n\n" . self::USAGE);

            return 1;
        }

        $failed = 0;
        $stale = 0;

        foreach ($paths as $path) {
            try {
                $files = self::shapeFiles($path);
            } catch (Throwable $error) {
                $failed++;
                fwrite($stderr, "pure: {$error->getMessage()}\n");

                continue;
            }

            foreach ($files as $file) {
                try {
                    if ($check) {
                        $sources = ArtifactCompiler::buildAll($file, $plain);
                        $targets = [ArtifactCompiler::artifactPath($file) => $sources['artifact']];

                        if ($sources['plain'] !== null) {
                            $targets[ArtifactCompiler::plainPath($file)] = $sources['plain'];
                        }

                        foreach ($targets as $target => $expected) {
                            $current = @file_get_contents($target);

                            if ($current === false) {
                                $stale++;
                                fwrite($stdout, "missing: {$target}\n");
                            } elseif ($current === $expected) {
                                fwrite($stdout, "up to date: {$target}\n");
                            } else {
                                $stale++;
                                fwrite($stdout, "stale: {$target}\n");
                            }
                        }

                        continue;
                    }

                    $written = ArtifactCompiler::writeAll($file, $plain);
                    $targets = $written['artifact'] . ($written['plain'] === null ? '' : ', ' . $written['plain']);
                    fwrite($stdout, "compiled: {$file} -> {$targets}\n");
                } catch (Throwable $error) {
                    $failed++;
                    fwrite($stderr, "pure: {$file}: {$error->getMessage()}\n");
                }
            }
        }

        if ($stale > 0) {
            fwrite($stderr, "pure: {$stale} artifact(s) need recompiling.\n");
        }

        return $failed > 0 || $stale > 0 ? 1 : 0;
    }

    /**
     * @param string $path A file or directory argument.
     * @return list<string> The `*.shape.php` files to compile.
     */
    private static function shapeFiles(string $path): array
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
                if ($file instanceof SplFileInfo && $file->isFile() && str_ends_with($file->getPathname(), ArtifactCompiler::SUFFIX)) {
                    $found[] = $file->getPathname();
                }
            }

            if ($found === []) {
                throw new InvalidArgumentException("no *" . ArtifactCompiler::SUFFIX . " files found in '{$path}'.");
            }

            sort($found);

            return $found;
        }

        throw new InvalidArgumentException("'{$path}' does not exist.");
    }
}
