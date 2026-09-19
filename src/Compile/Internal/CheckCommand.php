<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

use Closure;
use Pure\Compile\Compile;
use Pure\Compile\Shape;
use RuntimeException;
use Throwable;

/**
 * The `pure check` command: validates component contracts statically.
 *
 * For every `*.cmp.php` unit it compares the slots its template reads, the
 * bindings its component function passes to `render()` and the typed
 * parameters of that function; `*.shape.php` templates are checked for slots
 * bound to incompatible kinds. This is not `pure compile --check`, which
 * reports stale or missing artifacts.
 *
 * @internal
 */
final class CheckCommand
{
    private const USAGE = <<<'USAGE'
        Pure contract checker.

        Usage:
          pure check <path>... [--strict]

        Checks every *.cmp.php unit: the slots its template reads against the
        named bindings of its component function's render() call (a binding the
        template does not read, a required slot the call does not bind), and
        the function's parameter types against the slot kinds (a list slot
        needs an iterable, a child scope an array, a text slot a stringable).
        Reports a slot that one template uses as both a scalar and a scope, and
        checks *.shape.php templates for the same conflict. Directories are
        searched recursively.

          --strict     exit 1 on warnings too
          -h, --help   show this help

        This is not `pure compile --check`, which reports stale artifacts.

        USAGE;

    private readonly UnitLoader $loader;
    private readonly ContractChecker $checker;

    /**
     * @param (Closure(string): array<string, array{factory: Closure(): mixed}>)|null $units
     *     Resolves the units registered by a `*.cmp.php` file; null disables
     *     unit support (plain ArtifactCompiler use).
     */
    public function __construct(?Closure $units = null)
    {
        $this->loader = new UnitLoader($units);
        $this->checker = new ContractChecker();
    }

    /**
     * @param list<string> $argv The raw argv, with the command at index 1.
     * @param resource|null $stdout The output stream (defaults to STDOUT).
     * @param resource|null $stderr The error stream (defaults to STDERR).
     * @return int The exit code.
     */
    public function run(array $argv, $stdout = null, $stderr = null): int
    {
        $stdout ??= STDOUT;
        $stderr ??= STDERR;
        $arguments = array_slice($argv, 2);
        $paths = [];
        $strict = false;

        foreach ($arguments as $argument) {
            if ($argument === '--strict') {
                $strict = true;

                continue;
            }

            if ($argument === '-h' || $argument === '--help') {
                fwrite($stdout, self::USAGE);

                return 0;
            }

            if (str_starts_with($argument, '-')) {
                fwrite($stderr, "pure: unknown option '{$argument}'\n\n" . self::USAGE);

                return 1;
            }

            $paths[] = $argument;
        }

        if ($paths === []) {
            fwrite($stderr, "pure: check needs at least one file or directory\n\n" . self::USAGE);

            return 1;
        }

        $files = [];
        $failed = 0;

        foreach ($paths as $path) {
            try {
                foreach (UnitFinder::discover($path) as $file) {
                    $files[$file] = true;
                }
            } catch (Throwable $error) {
                $failed++;
                fwrite($stderr, "pure: {$error->getMessage()}\n");
            }
        }

        $checked = 0;
        $errors = 0;
        $warnings = 0;

        foreach (array_keys($files) as $file) {
            try {
                $units = $this->loader->unitsOf($file);
            } catch (Throwable $error) {
                $failed++;
                fwrite($stderr, "pure: {$error->getMessage()}\n");

                continue;
            }

            try {
                if ($units === null) {
                    $checked++;
                    $shape = ArtifactCompiler::load($file);
                    self::report($stdout, $file, null, $this->checker->check($file, null, $shape->tree(), null), $errors, $warnings);

                    continue;
                }

                if ($units === []) {
                    throw new RuntimeException('no component unit is registered here; `pure check` skips the file.');
                }

                foreach ($units as $name => $unit) {
                    $checked++;
                    $shape = Compile::toShape(($unit['factory'])());

                    if (!$shape instanceof Shape) {
                        throw new RuntimeException("component '{$name}': the factory must return a tag tree or Pure\\Compile\\Shape.");
                    }

                    $findings = $this->checker->check($file, $name, $shape->tree(), FunctionFinder::of($name, $file));
                    self::report($stdout, $file, $name, $findings, $errors, $warnings);
                }
            } catch (Throwable $error) {
                $failed++;
                fwrite($stderr, "pure: {$file}: {$error->getMessage()}\n");
            }
        }

        fwrite(
            $stdout,
            "checked {$checked} unit(s): {$errors} error(s), {$warnings} warning(s).\n"
        );

        if ($failed > 0 || $errors > 0 || ($strict && $warnings > 0)) {
            return 1;
        }

        return 0;
    }

    /**
     * @param resource $stdout The output stream.
     * @param list<Finding> $findings The findings of one unit.
     */
    private static function report($stdout, string $file, ?string $name, array $findings, int &$errors, int &$warnings): void
    {
        $label = $name === null ? "{$file} (shape)" : "component '{$name}' -> {$file}";

        if ($findings === []) {
            fwrite($stdout, "ok: {$label}\n");

            return;
        }

        foreach ($findings as $finding) {
            fwrite($stdout, "{$finding->level}: {$label}: {$finding->message}\n");

            if ($finding->level === 'error') {
                $errors++;
            } elseif ($finding->level === 'warning') {
                $warnings++;
            }
        }
    }
}
