<?php

declare(strict_types=1);

namespace Pure\Compile;

use Pure\Compile\Internal\CodeGenerator;
use Pure\Compile\Internal\RendererCache;
use Pure\Compile\Internal\ShapeGuard;
use Pure\Compile\Internal\ShapeIndex;
use Pure\Core\Tag;

final class Compile
{
    /**
     * Bump when the generated-code format, the fingerprint composition or a
     * class name referenced by generated code changes.
     */
    public const CACHE_VERSION = 5;

    private static int $generation = 0;

    private static ?string $cachePath = null;

    /**
     * Byte budget for the in-memory source memo. Entries are pure
     * (fingerprint -> generated source), so evicting one only costs code
     * generation time; a structure that varies per request cannot grow the
     * memo without limit.
     */
    private const MEMO_BYTES = 4194304;

    /**
     * Generated sources memoized per fingerprint, so a tree rebuilt in the
     * same process reuses the code instead of regenerating it. Bounded: the
     * oldest entries are dropped once MEMO_BYTES is exceeded.
     *
     * @var array<string, string>
     */
    private static array $sources = [];

    private static int $memoBytes = 0;

    /**
     * Wrap a data-free shape tree for compiled rendering.
     *
     * Build shapes once per process (for example with a static variable inside
     * a component function), never on every request. The tree is read live on
     * every (re)compile, so mutating it after rendering has no effect until
     * Compile::flush() is called.
     *
     * @param Tag $shape The shape tree to compile.
     * @return Shape The compiled shape wrapper.
     */
    public static function shape(Tag $shape): Shape
    {
        ShapeGuard::check();

        return new Shape($shape);
    }

    /**
     * Enable the on-disk renderer cache, or disable it with null (default).
     *
     * The directory must be a private directory owned by the current user and
     * not writable by group or others (0700 is created when missing).
     *
     * @param string|null $dir The cache directory path, or null to disable.
     */
    public static function cachePath(?string $dir): void
    {
        self::$cachePath = $dir === null ? null : RendererCache::prepare($dir);
    }

    /**
     * Delete cached renderer files written by this library. Returns the number removed.
     *
     * @return int The number of cache files removed.
     */
    public static function clearCache(): int
    {
        return self::$cachePath === null ? 0 : RendererCache::clear(self::$cachePath);
    }

    /**
     * Invalidate in-memory renderers so every shape recompiles on next use.
     */
    public static function flush(): void
    {
        self::$generation++;
        self::$sources = [];
        self::$memoBytes = 0;
    }

    /**
     * Warn once per call site when Compile::shape() is called repeatedly from
     * the same place, which usually means the shape is rebuilt per request.
     *
     * @param bool $enabled Whether to enable the warning (default true).
     */
    public static function guard(bool $enabled = true): void
    {
        ShapeGuard::enable($enabled);
    }

    /**
     * @internal
     *
     * @return int The current compile generation counter.
     */
    public static function generation(): int
    {
        return self::$generation;
    }

    /**
     * @internal
     *
     * @param Tag $tree The shape tree to compile.
     * @return Renderer The compiled renderer.
     */
    public static function renderer(Tag $tree): Renderer
    {
        // A fresh index per compile keeps the fingerprint and the generated
        // code derived from the same tree state: a memoized index could
        // describe an older tree after a mutation and poison the cache file.
        $index = ShapeIndex::of($tree);
        $id = $index->id();
        $maps = array_values($index->maps());
        $dir = self::$cachePath;

        if ($dir !== null) {
            $file = $dir . '/' . $id . '.php';
            $cached = RendererCache::load($file, $id, $index->maps());
            if ($cached !== null) {
                self::memoize($id, $cached->source);

                return $cached;
            }
        }

        $source = self::$sources[$id] ?? null;
        if ($source !== null) {
            $compiled = CodeGenerator::fromSource($source, $id, $maps);
        } else {
            $compiled = CodeGenerator::compile($tree, $index);
            self::memoize($id, $compiled->source);
        }

        if ($dir !== null) {
            RendererCache::write($dir . '/' . $id . '.php', $compiled->source, $id, count($maps));
        }

        return $compiled;
    }

    /**
     * Remember a generated source, evicting the oldest entries once the byte
     * budget is exceeded. A source larger than the whole budget is not kept.
     *
     * @param string $id The shape fingerprint.
     * @param string $source The generated PHP source.
     */
    private static function memoize(string $id, string $source): void
    {
        if (isset(self::$sources[$id])) {
            return;
        }

        self::$sources[$id] = $source;
        self::$memoBytes += strlen($source);

        while (self::$memoBytes > self::MEMO_BYTES) {
            $oldest = array_key_first(self::$sources);
            if ($oldest === null) {
                break;
            }

            self::$memoBytes -= strlen(self::$sources[$oldest]);
            unset(self::$sources[$oldest]);
        }
    }
}
