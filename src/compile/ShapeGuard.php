<?php

declare(strict_types=1);

namespace Pure\Compile;

/**
 * Development guard against building shapes on every request.
 *
 * When enabled, repeated Compile::shape() calls from one call site in a single
 * process trigger one E_USER_WARNING suggesting the static memoization
 * pattern.
 *
 * @internal
 */
final class ShapeGuard
{
    private const THRESHOLD = 20;

    private static ?bool $enabled = null;

    /** @var array<string, int> */
    private static array $calls = [];

    public static function enable(bool $enabled = true): void
    {
        self::$enabled = $enabled;
    }

    public static function check(): void
    {
        if (!self::enabled()) {
            return;
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? [];
        $file = $trace['file'] ?? '?';
        $line = $trace['line'] ?? 0;
        $key = $file . ':' . $line;

        $count = self::$calls[$key] = (self::$calls[$key] ?? 0) + 1;
        if ($count === self::THRESHOLD) {
            trigger_error(
                "Pure\\Compile\\Compile::shape() was called {$count} times from {$key}; build shapes once per process and memoize them (static \$shape ??= Compile::shape(...)).",
                E_USER_WARNING
            );
        }
    }

    private static function enabled(): bool
    {
        if (self::$enabled === null) {
            $env = getenv('PURE_COMPILE_GUARD');
            self::$enabled = is_string($env) && ($env === '1' || strtolower($env) === 'true');
        }

        return self::$enabled;
    }
}
