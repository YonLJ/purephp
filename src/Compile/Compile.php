<?php

declare(strict_types=1);

namespace Pure\Compile;

use Pure\Compile\Internal\CodeGenerator;
use Pure\Compile\Internal\RendererCache;
use Pure\Compile\Internal\RootSlots;
use Pure\Compile\Internal\ShapeGuard;
use Pure\Compile\Internal\ShapeIndex;
use Pure\Core\DevMode;
use Pure\Core\Tag;

final class Compile
{
    /**
     * Bump when the generated-code format, the fingerprint composition or a
     * class name referenced by generated code changes.
     */
    public const CACHE_VERSION = 13;

    private static int $generation = 0;

    private static ?string $cachePath = null;

    /**
     * Default byte budget for the in-memory source memo, overridable with
     * PURE_COMPILE_MEMO_BYTES (0 disables the memo). Entries are pure
     * (fingerprint -> generated source), so evicting one only costs code
     * generation time; a structure that varies per request cannot grow the
     * memo without limit.
     */
    private const MEMO_BYTES = 4194304;

    /**
     * Generated sources memoized per fingerprint, so a tree rebuilt in the
     * same process reuses the code instead of regenerating it. Bounded: the
     * oldest entries are dropped once the byte budget is exceeded.
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
     * Wrap a tag tree in a Shape, or pass a Shape through unchanged.
     *
     * Shared by the component registry and the artifact compiler, which accept
     * either form from a unit factory or a `*.shape.php` template. Callers
     * memoize their shapes, so the development guard is not consulted here:
     * every bare-tree wrap would otherwise count as one call site.
     *
     * @internal
     * @param mixed $result A factory or template result.
     * @return Shape|null The shape, or null when the result is neither a tag tree nor a Shape.
     */
    public static function toShape(mixed $result): ?Shape
    {
        if ($result instanceof Shape) {
            return $result;
        }

        if ($result instanceof Tag) {
            return new Shape($result);
        }

        return null;
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
     * Enable the development guard: repeated Compile::shape() calls from one
     * call site warn once (usually a shape rebuilt per request), and renderer
     * bindings whose keys the template does not read are reported once, as are
     * near-miss attribute names. Off unless enabled here or by
     * PURE_COMPILE_GUARD=1.
     *
     * @param bool $enabled Whether to enable the guard (default true).
     */
    public static function guard(bool $enabled = true): void
    {
        ShapeGuard::enable($enabled);
        DevMode::enable($enabled);
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
        $slots = RootSlots::of($tree);
        $dir = self::$cachePath;

        if ($dir !== null) {
            $file = $dir . '/' . $id . '.php';
            $cached = RendererCache::load($file, $id, $slots);
            if ($cached !== null) {
                self::memoize($id, $cached->source);

                return $cached;
            }
        }

        $source = self::$sources[$id] ?? null;
        if ($source !== null) {
            $compiled = CodeGenerator::fromSource($source, $id, $slots);
        } else {
            $compiled = CodeGenerator::compile($tree, $index, $slots);
            self::memoize($id, $compiled->source);
        }

        if ($dir !== null) {
            RendererCache::write($dir . '/' . $id . '.php', $compiled->source, $id);
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
        $limit = self::memoLimit();
        if ($limit <= 0 || isset(self::$sources[$id])) {
            return;
        }

        self::$sources[$id] = $source;
        self::$memoBytes += strlen($source);

        while (self::$memoBytes > $limit) {
            $oldest = array_key_first(self::$sources);
            if ($oldest === null) {
                break;
            }

            self::$memoBytes -= strlen(self::$sources[$oldest]);
            unset(self::$sources[$oldest]);
        }
    }

    /**
     * The byte budget of the source memo: MEMO_BYTES unless
     * PURE_COMPILE_MEMO_BYTES overrides it (0 disables the memo).
     */
    private static function memoLimit(): int
    {
        $raw = getenv('PURE_COMPILE_MEMO_BYTES');
        if (!is_string($raw) || $raw === '') {
            return self::MEMO_BYTES;
        }

        $limit = filter_var($raw, FILTER_VALIDATE_INT);

        return $limit === false ? self::MEMO_BYTES : $limit;
    }
}
