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
        $dir = self::$cachePath;

        if ($dir === null) {
            return CodeGenerator::compile($tree, $index);
        }

        $file = $dir . '/' . $index->id() . '.php';
        $cached = RendererCache::load($file, $index->id(), $index->maps());
        if ($cached !== null) {
            return $cached;
        }

        $compiled = CodeGenerator::compile($tree, $index);
        RendererCache::write($file, $compiled->source, $index->id(), count($index->maps()));

        return $compiled;
    }
}
