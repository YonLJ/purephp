<?php

declare(strict_types=1);

namespace Pure\Component;

use Closure;
use InvalidArgumentException;
use Pure\Compile\Compile;
use Pure\Compile\Internal\ArtifactCompiler;
use Pure\Compile\Renderer;
use Pure\Compile\Shape;
use Pure\Core\Raw;
use RuntimeException;

/**
 * Registry of component units, referenced by name or by file path.
 *
 * A unit is a `*.cmp.php` file that registers a lazy factory and defines the
 * component function next to it:
 *
 *     register('Icon', __FILE__, static fn (): Shape => Compile::shape(
 *         svg(svgUse()->href(Slot::attr('href')))
 *     ));
 *
 *     function Icon(string $href): Raw
 *     {
 *         return render('Icon', href: $href);
 *     }
 *
 * Registration stores the closure only: no I/O, no tree building. At render
 * time the unit is served by its precompiled artifact when that is at least as
 * new as the unit file; only otherwise is the factory called (once per compile
 * generation) and the shape compiled. A name and the path of its unit file
 * resolve to the same binder.
 *
 * @internal
 */
final class Registry
{
    /** @var array<string, array{file: string, factory: Closure(): mixed, document: bool}> */
    private static array $units = [];

    /** @var array<string, string> realpath(file) => name */
    private static array $files = [];

    /** @var array<string, string> the name or path as given => cache key */
    private static array $keys = [];

    /** @var array<string, array{binder: Closure(array<int|string, mixed>): Raw, generation: int}> */
    private static array $binders = [];

    /** @var array<string, array{binder: Closure(array<int|string, mixed>): Raw, generation: int}> */
    private static array $pageBinders = [];

    /** @var array<string, array{shape: Shape, generation: int}> */
    private static array $shapes = [];


    private function __construct()
    {
    }

    /**
     * Register one component unit.
     *
     * Registering the same name for the same file again is a no-op; a name
     * owned by another file or a file that already owns another name throws
     * unless `$override` is true.
     *
     * @param string $name The component name used by render().
     * @param string $file The unit file, normally `__FILE__`.
     * @param Closure(): mixed $factory Builds the template; called lazily, must return a Shape.
     * @param bool $document Whether the binder prepends the document header.
     * @param bool $override Replace an existing registration.
     * @return void
     */
    public static function register(
        string $name,
        string $file,
        Closure $factory,
        bool $document = false,
        bool $override = false
    ): void {
        if ($name === '') {
            throw new InvalidArgumentException('component name must not be empty.');
        }

        $path = realpath($file);

        if ($path === false) {
            throw new InvalidArgumentException("component '{$name}' unit file '{$file}' does not exist.");
        }

        $registered = self::$units[$name] ?? null;

        if ($registered !== null && $registered['file'] === $path) {
            return;
        }

        if ($registered !== null && !$override) {
            throw new RuntimeException(
                "component '{$name}' is already registered by '{$registered['file']}'; pass override: true to replace it."
            );
        }

        $owner = self::$files[$path] ?? null;

        if ($owner !== null && $owner !== $name && !$override) {
            throw new RuntimeException("'{$path}' is already registered as '{$owner}'; a unit file registers one component.");
        }

        if ($registered !== null) {
            unset(self::$files[$registered['file']]);
            self::forget($name);
        }

        if ($owner !== null && $owner !== $name) {
            unset(self::$units[$owner]);
            self::forget($owner);
        }

        self::$units[$name] = ['file' => $path, 'factory' => $factory, 'document' => $document];
        self::$files[$path] = $name;
        self::$keys = [];
    }

    /**
     * The binder of a registered unit or of a `*.shape.php` template path.
     *
     * @param string $nameOrPath A component name or a unit/shape file path.
     * @return Closure(array<int|string, mixed>): Raw
     */
    public static function component(string $nameOrPath): Closure
    {
        return self::binder($nameOrPath, false);
    }

    /**
     * The binder of a registered page unit or of a template path, including
     * the document header of the root tag.
     *
     * @param string $nameOrPath A page name or a unit/shape file path.
     * @return Closure(array<int|string, mixed>): Raw
     */
    public static function page(string $nameOrPath): Closure
    {
        return self::binder($nameOrPath, true);
    }

    /**
     * The registered component names, in registration order.
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::$units);
    }

    /**
     * The units registered by one file, for `pure compile`.
     *
     * @param string $file The unit file path.
     * @return array<string, array{factory: Closure(): mixed, document: bool}>
     */
    public static function unitsFor(string $file): array
    {
        $path = realpath($file) ?: $file;
        $units = [];

        foreach (self::$units as $name => $unit) {
            if ($unit['file'] === $path) {
                $units[$name] = ['factory' => $unit['factory'], 'document' => $unit['document']];
            }
        }

        return $units;
    }

    /**
     * Drop every registration and cache; for tests.
     */
    public static function reset(): void
    {
        self::$units = [];
        self::$files = [];
        self::$keys = [];
        self::$binders = [];
        self::$pageBinders = [];
        self::$shapes = [];
    }

    private static function binder(string $nameOrPath, bool $document): Closure
    {
        $key = self::key($nameOrPath);
        $generation = Compile::generation();
        $cached = $document ? (self::$pageBinders[$key] ?? null) : (self::$binders[$key] ?? null);

        if ($cached !== null && $cached['generation'] === $generation) {
            return $cached['binder'];
        }

        if (isset(self::$units[$key])) {
            [$renderer, $header] = self::unitRenderer($key);
        } else {
            [$renderer, $header] = self::templateRenderer(substr($key, strlen('path:')));
        }

        $binder =
            /** @param array<string, mixed> $data */
            static fn (array $data): Raw => Raw::of(
                ($document ? $header : '') . $renderer->render($data)
            );

        $entry = ['binder' => $binder, 'generation' => $generation];

        if ($document) {
            self::$pageBinders[$key] = $entry;
        } else {
            self::$binders[$key] = $entry;
        }

        return $binder;
    }

    /**
     * Resolve a name or path to a cache key: a registered name, or a
     * `path:`-prefixed canonical path for a bare template.
     */
    private static function key(string $nameOrPath): string
    {
        if (isset(self::$units[$nameOrPath])) {
            return $nameOrPath;
        }

        if (isset(self::$keys[$nameOrPath])) {
            return self::$keys[$nameOrPath];
        }

        if (self::isPath($nameOrPath)) {
            $path = realpath($nameOrPath) ?: $nameOrPath;

            return self::$keys[$nameOrPath] = self::$files[$path] ?? 'path:' . $path;
        }

        $known = self::names();
        $hint = $known === []
            ? 'no components are registered'
            : 'known components: ' . implode(', ', $known);

        throw new RuntimeException("unknown component '{$nameOrPath}'; {$hint}.");
    }

    private static function isPath(string $nameOrPath): bool
    {
        return str_contains($nameOrPath, '/')
            || str_contains($nameOrPath, '\\')
            || str_ends_with($nameOrPath, '.php');
    }

    /**
     * @return array{0: Renderer, 1: string} The renderer and the document header.
     */
    private static function unitRenderer(string $name): array
    {
        $unit = self::$units[$name];
        $artifact = ArtifactCompiler::artifactPath($unit['file']);
        $artifactTime = is_file($artifact) ? filemtime($artifact) : false;
        $unitTime = filemtime($unit['file']);

        if ($artifactTime !== false && $unitTime !== false && $artifactTime >= $unitTime) {
            $renderer = require $artifact;

            if (!$renderer instanceof Renderer) {
                throw new RuntimeException(
                    "component artifact '{$artifact}' must return a Renderer; run `pure compile`."
                );
            }

            return [$renderer, $renderer->header];
        }

        $shape = self::shape($name);

        return [$shape->compile(), $shape->tree()->documentHeader()];
    }

    /**
     * The memoized template of a unit; the factory runs at most once per
     * compile generation.
     */
    private static function shape(string $name): Shape
    {
        $generation = Compile::generation();
        $cached = self::$shapes[$name] ?? null;

        if ($cached !== null && $cached['generation'] === $generation) {
            return $cached['shape'];
        }

        $shape = (self::$units[$name]['factory'])();

        if (!$shape instanceof Shape) {
            throw new RuntimeException(
                "component '{$name}' factory must return a Pure\\Compile\\Shape, got " . get_debug_type($shape) . '.'
            );
        }

        self::$shapes[$name] = ['shape' => $shape, 'generation' => $generation];

        return $shape;
    }

    /**
     * The renderer of a bare `*.shape.php` path: the precompiled artifact when
     * it is fresh, the compiled shape file otherwise.
     *
     * @return array{0: Renderer, 1: string} The renderer and the document header.
     */
    private static function templateRenderer(string $shapeFile): array
    {
        if (!is_file($shapeFile)) {
            throw new RuntimeException("component template '{$shapeFile}' does not exist.");
        }

        $artifact = ArtifactCompiler::artifactPath($shapeFile);
        $artifactTime = is_file($artifact) ? filemtime($artifact) : false;
        $shapeTime = filemtime($shapeFile);

        if ($artifactTime !== false && $shapeTime !== false && $artifactTime >= $shapeTime) {
            $renderer = require $artifact;

            if (!$renderer instanceof Renderer) {
                throw new RuntimeException(
                    "component artifact '{$artifact}' must return a Renderer; run `pure compile`."
                );
            }

            return [$renderer, $renderer->header];
        }

        $shape = require $shapeFile;

        if (!$shape instanceof Shape) {
            throw new RuntimeException("component template '{$shapeFile}' must return a Shape.");
        }

        return [$shape->compile(), $shape->tree()->documentHeader()];
    }

    private static function forget(string $name): void
    {
        unset(
            self::$binders[$name],
            self::$pageBinders[$name],
            self::$shapes[$name],
        );
    }
}
