<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

use Closure;
use Pure\Compile\Compile;
use Pure\Compile\Shape;
use Pure\Component\Prop;
use Pure\Component\Registry;
use Pure\Core\Suggestion;
use ReflectionFunction;
use ReflectionParameter;
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
    /**
     * The Call methods that are not props; a chain entry naming one is only
     * reported when the target has a prop of that name.
     */
    private const CALL_METHODS = ['props', 'render'];

    private const USAGE = <<<'USAGE'
        Pure contract checker.

        Usage:
          pure check <path>... [--strict]

        Checks every *.cmp.php unit: the slots its template reads against the
        named bindings of its component function's render() call (a binding the
        template does not read, a required slot the call does not bind), or
        against the prepare() parameters and returned keys of a fluent unit,
        where a #[Prop] declaration on a parameter (slot, item, required,
        deprecated) is verified against the signature and the template; the
        function's parameter types against the slot kinds (a list slot needs an
        iterable, a child scope an array, a text slot a stringable).
        Also checks the fluent calls in every file: a `->prop(...)` the target
        does not accept is an error. Reports a slot that one template uses as
        both a scalar and a scope, and checks *.shape.php templates for the
        same conflict. Directories are searched recursively.

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

        // Load every unit first: a fluent call in one file may target a
        // component registered by another one.
        $loaded = [];

        foreach (array_keys($files) as $file) {
            try {
                $loaded[$file] = $this->loader->unitsOf($file);
            } catch (Throwable $error) {
                $failed++;
                unset($files[$file]);
                fwrite($stderr, "pure: {$error->getMessage()}\n");
            }
        }

        foreach (array_keys($files) as $file) {
            try {
                $units = $loaded[$file] ?? null;

                if ($units === null) {
                    $checked++;
                    $shape = ArtifactCompiler::load($file);
                    self::report($stdout, $file, null, $this->checker->check($file, null, $shape->tree(), null), $errors, $warnings);
                } else {
                    if ($units === []) {
                        throw new RuntimeException('no component unit is registered here; `pure check` skips the file.');
                    }

                    foreach ($units as $name => $unit) {
                        $checked++;
                        $shape = Compile::toShape(($unit['factory'])());

                        if (!$shape instanceof Shape) {
                            throw new RuntimeException("component '{$name}': the factory must return a tag tree or Pure\\Compile\\Shape.");
                        }

                        $prepare = Registry::prepare($name);
                        $findings = $this->checker->check(
                            $file,
                            $name,
                            $shape->tree(),
                            $prepare === null ? FunctionFinder::of($name, $file) : null,
                            $prepare === null ? null : new ReflectionFunction($prepare)
                        );
                        self::report($stdout, $file, $name, $findings, $errors, $warnings);
                    }
                }

                self::reportCallSites($stdout, $file, $errors, $warnings);
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
     * Check the fluent component calls in one file: every `->prop(...)` of a
     * call must be accepted by its target, which is the prepare() parameter
     * list when the unit has one and its slot list otherwise.
     *
     * @param resource $stdout The output stream.
     */
    private static function reportCallSites($stdout, string $file, int &$errors, int &$warnings): void
    {
        foreach (CallSites::of($file, Registry::names()) as $site) {
            if ($site['dynamic']) {
                continue;
            }

            $expected = self::expectedProps($site['name']);

            if ($expected === null) {
                continue;
            }

            $deprecated = self::deprecatedProps($site['name']);

            foreach (array_keys($site['props']) as $prop) {
                if (in_array($prop, $expected, true) || in_array($prop, self::CALL_METHODS, true)) {
                    if (isset($deprecated[$prop])) {
                        fwrite($stdout, "warning: {$file}: component '{$site['name']}': the call binds '{$prop}', which is deprecated: {$deprecated[$prop]}\n");
                        $warnings++;
                    }

                    continue;
                }

                if ($prop === 'children') {
                    fwrite($stdout, "error: {$file}: component '{$site['name']}': pass children to the call itself, e.g. {$site['name']}(\$children)\n");
                    $errors++;

                    continue;
                }

                $nearest = Suggestion::nearest($prop, $expected);
                $hint = $nearest === null ? '' : " (did you mean '{$nearest}'?)";

                fwrite($stdout, "error: {$file}: component '{$site['name']}': the call binds '{$prop}', which the target does not accept{$hint}\n");
                $errors++;
            }
        }
    }

    /**
     * The props a fluent call may bind: the prepare() parameters of the unit,
     * or its root slots.
     *
     * @return list<string>|null
     */
    private static function expectedProps(string $name): ?array
    {
        $prepare = Registry::prepare($name);

        if ($prepare !== null) {
            return array_map(
                static fn (ReflectionParameter $parameter): string => $parameter->getName(),
                (new ReflectionFunction($prepare))->getParameters()
            );
        }

        return Registry::slots($name);
    }

    /**
     * The props a fluent call may bind but should not, per the `#[Prop]`
     * declarations of the target: prop name to migration hint.
     *
     * @return array<string, string>
     */
    private static function deprecatedProps(string $name): array
    {
        $prepare = Registry::prepare($name);

        if ($prepare === null) {
            return [];
        }

        $deprecated = [];

        foreach ((new ReflectionFunction($prepare))->getParameters() as $parameter) {
            foreach ($parameter->getAttributes(Prop::class) as $attribute) {
                $prop = $attribute->newInstance();

                if ($prop->deprecated !== null) {
                    $deprecated[$parameter->getName()] = $prop->deprecated;
                }
            }
        }

        return $deprecated;
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
