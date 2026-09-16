<?php

declare(strict_types=1);

namespace Pure\Compile;

use Closure;
use InvalidArgumentException;
use Pure\Core\Tag;
use Throwable;

final class Compile
{
    /** Bump when the generated-code format or fingerprint composition changes. */
    public const CACHE_VERSION = 2;

    private const GUARD_THRESHOLD = 20;

    private const CACHE_HEADER = "<?php\n// purephp-shape ";

    private static int $generation = 0;

    private static ?string $cachePath = null;

    private static ?bool $guard = null;

    /** @var array<string, int> */
    private static array $shapeCalls = [];

    /**
     * Wrap a data-free shape tree for compiled rendering.
     *
     * Build shapes once per process (for example with a static variable inside
     * a component function), never on every request.
     */
    public static function shape(Tag $shape): Shape
    {
        self::guardShapeCall();

        return new Shape($shape);
    }

    /**
     * Enable the on-disk renderer cache, or disable it with null (default).
     *
     * The directory must be writable and should live outside the web root:
     * cached files are plain PHP and rely on the `<?php` header for safety.
     */
    /**
     * Enable the on-disk renderer cache, or disable it with null (default).
     *
     * The directory must be a private directory owned by the current user and
     * not writable by group or others (0700 is created when missing): cached
     * files are plain PHP and rely on directory permissions for safety.
     */
    public static function cachePath(?string $dir): void
    {
        if ($dir === null) {
            self::$cachePath = null;

            return;
        }

        if (!is_dir($dir) && !@mkdir($dir, 0o700, true) && !is_dir($dir)) {
            throw new InvalidArgumentException("compile cache directory '{$dir}' could not be created.");
        }

        $perms = @fileperms($dir);
        if ($perms !== false && ($perms & 0o022) !== 0) {
            throw new InvalidArgumentException("compile cache directory '{$dir}' must not be writable by group or others; use a private directory such as 0700.");
        }

        if (function_exists('posix_geteuid') && ($owner = @fileowner($dir)) !== false && $owner !== posix_geteuid()) {
            throw new InvalidArgumentException("compile cache directory '{$dir}' is not owned by the current user.");
        }

        if (!is_writable($dir)) {
            throw new InvalidArgumentException("compile cache directory '{$dir}' is not writable.");
        }

        self::$cachePath = rtrim($dir, '/\\');
    }

    /** Delete cached renderer files written by this library. Returns the number removed. */
    public static function clearCache(): int
    {
        $dir = self::$cachePath;
        if ($dir === null) {
            return 0;
        }

        $removed = 0;
        foreach (glob($dir . '/*.php') ?: [] as $file) {
            $handle = @fopen($file, 'rb');
            if ($handle === false) {
                continue;
            }

            $header = (string)fread($handle, strlen(self::CACHE_HEADER));
            fclose($handle);

            if ($header !== self::CACHE_HEADER) {
                continue;
            }

            if (@unlink($file)) {
                $removed++;
            }
        }

        return $removed;
    }

    /** Invalidate in-memory renderers so every shape recompiles on next use. */
    public static function flush(): void
    {
        self::$generation++;
    }

    /**
     * Warn once per call site when Compile::shape() is called repeatedly from
     * the same place, which usually means the shape is rebuilt per request.
     */
    public static function guard(bool $enabled = true): void
    {
        self::$guard = $enabled;
    }

    /** @internal */
    public static function generation(): int
    {
        return self::$generation;
    }

    /**
     * @internal
     *
     * @param array{id: string, maps: array<string, Closure>}|null $index
     */
    public static function renderer(Tag $tree, ?array $index = null): Renderer
    {
        $index ??= ShapeIndex::of($tree);
        $dir = self::$cachePath;

        if ($dir === null) {
            return CodeGenerator::compile($tree, $index);
        }

        $file = $dir . '/' . $index['id'] . '.php';
        $cached = self::loadCached($file, $index['id'], $index['maps']);
        if ($cached !== null) {
            return $cached;
        }

        $compiled = CodeGenerator::compile($tree, $index);
        self::writeCached($file, $compiled->source(), $index['id'], count($index['maps']));

        return $compiled;
    }

    /**
     * @param array<string, Closure> $maps
     */
    private static function loadCached(string $file, string $id, array $maps): ?Renderer
    {
        $contents = @file_get_contents($file);
        if ($contents === false) {
            return null;
        }

        $pattern = '/\A<\?php\n\/\/ purephp-shape id=([0-9a-f]{40}) maps=(\d+) v=(\d+) php=([0-9]+\.[0-9]+)\n/';
        if (preg_match($pattern, $contents, $matches) !== 1
            || $matches[1] !== $id
            || (int)$matches[2] !== count($maps)
            || (int)$matches[3] !== self::CACHE_VERSION
            || $matches[4] !== PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION
        ) {
            @unlink($file);

            return null;
        }

        try {
            $renderer = (static function (string $path): mixed {
                return require $path;
            })($file);
        } catch (Throwable) {
            @unlink($file);

            return null;
        }

        if (!$renderer instanceof Closure) {
            @unlink($file);

            return null;
        }

        $body = trim(substr($contents, strlen($matches[0])));
        if (str_starts_with($body, 'return ') && str_ends_with($body, ';')) {
            $body = substr($body, 7, -1);
        }

        return new Renderer($renderer, $body, $id, array_values($maps));
    }

    private static function writeCached(string $file, string $source, string $id, int $mapCount): void
    {
        $contents = self::CACHE_HEADER . "id={$id} maps={$mapCount} v=" . self::CACHE_VERSION
            . ' php=' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . "\nreturn {$source};\n";

        $tmp = @tempnam(dirname($file), 'shape-');
        if ($tmp === false) {
            return;
        }

        if (@file_put_contents($tmp, $contents) !== strlen($contents)) {
            @unlink($tmp);

            return;
        }

        @chmod($tmp, 0o600);
        if (!@rename($tmp, $file)) {
            @unlink($tmp);
        }
    }

    private static function guardShapeCall(): void
    {
        if (!self::guardEnabled()) {
            return;
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? [];
        $file = $trace['file'] ?? '?';
        $line = $trace['line'] ?? 0;
        $key = $file . ':' . $line;

        $count = self::$shapeCalls[$key] = (self::$shapeCalls[$key] ?? 0) + 1;
        if ($count === self::GUARD_THRESHOLD) {
            trigger_error(
                "Pure\\Compile\\Compile::shape() was called {$count} times from {$key}; build shapes once per process and memoize them (static \$shape ??= Compile::shape(...)).",
                E_USER_WARNING
            );
        }
    }

    private static function guardEnabled(): bool
    {
        if (self::$guard === null) {
            $env = getenv('PURE_COMPILE_GUARD');
            self::$guard = is_string($env) && ($env === '1' || strtolower($env) === 'true');
        }

        return self::$guard;
    }
}
