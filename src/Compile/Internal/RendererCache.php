<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

use Closure;
use InvalidArgumentException;
use Pure\Compile\Compile;
use Pure\Compile\Renderer;
use Throwable;

/**
 * On-disk storage for compiled renderers.
 *
 * Cache files are content-addressed by the shape fingerprint, written
 * atomically and validated by a header before they are included. Directory
 * permissions are the trust boundary: the directory must be private and owned
 * by the current user.
 *
 * @internal
 */
final class RendererCache
{
    private const HEADER_PREFIX = "<?php\n// purephp-shape ";

    /**
     * Validate an existing cache directory, creating it with 0700 when missing.
     *
     * @param string $dir The directory path to validate or create.
     * @return string The normalized directory path
     */
    public static function prepare(string $dir): string
    {
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

        return rtrim($dir, '/\\');
    }

    /**
     * Delete the renderer files written by this library.
     *
     * @param string $dir The cache directory.
     * @return int The number of files removed.
     */
    public static function clear(string $dir): int
    {
        $removed = 0;
        foreach (glob($dir . '/*.php') ?: [] as $file) {
            $handle = @fopen($file, 'rb');
            if ($handle === false) {
                continue;
            }

            $header = (string)fread($handle, strlen(self::HEADER_PREFIX));
            fclose($handle);

            if ($header !== self::HEADER_PREFIX) {
                continue;
            }

            if (@unlink($file)) {
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * @param string $file The cache file path.
     * @param string $id The expected fingerprint.
     * @param list<string>|null $slots The root slot names of the shape, for the
     *     development guard; the cached file stores only the closure.
     * @return Renderer|null The cached renderer, or null if invalid or missing.
     */
    public static function load(string $file, string $id, ?array $slots = null): ?Renderer
    {
        $contents = @file_get_contents($file);
        if ($contents === false) {
            return null;
        }

        $pattern = '/\A<\?php\n\/\/ purephp-shape id=([0-9a-f]{40}) v=(\d+) php=([0-9]+\.[0-9]+)\n/';
        if (preg_match($pattern, $contents, $matches) !== 1
            || $matches[1] !== $id
            || (int)$matches[2] !== Compile::CACHE_VERSION
            || $matches[3] !== PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION
        ) {
            @unlink($file);

            return null;
        }

        try {
            $closure = (static function (string $path): mixed {
                return require $path;
            })($file);
        } catch (Throwable) {
            @unlink($file);

            return null;
        }

        if (!$closure instanceof Closure) {
            @unlink($file);

            return null;
        }

        $body = trim(substr($contents, strlen($matches[0])));
        if (str_starts_with($body, 'return ') && str_ends_with($body, ';')) {
            $body = substr($body, 7, -1);
        }

        return new Renderer($closure, $body, $id, $slots);
    }

    /**
     * Write a compiled renderer to the on-disk cache.
     *
     * @param string $file The cache file path.
     * @param string $source The generated PHP source code.
     * @param string $id The shape fingerprint.
     */
    public static function write(string $file, string $source, string $id): void
    {
        $contents = self::HEADER_PREFIX . "id={$id} v=" . Compile::CACHE_VERSION
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
}
