<?php

declare(strict_types=1);

namespace Pure\Core;

/**
 * Process-wide switch for the development guard.
 *
 * The guard powers the warnings that help while developing and are too noisy
 * or too costly for production: near-miss attribute names and data keys the
 * bound template never reads. It is off unless enabled with
 * `Compile::guard(true)` or `PURE_COMPILE_GUARD=1`, and every warning fires at
 * most once per subject per process.
 *
 * The switch is a property so that hot paths can read it with
 * `DevMode::$enabled ?? DevMode::resolve()`: an explicit setting costs no call,
 * and the environment is consulted once when nothing set it.
 *
 * @internal
 */
final class DevMode
{
    /**
     * Explicit switch state; null means "resolve from the environment".
     */
    public static ?bool $enabled = null;

    private const ENV = 'PURE_COMPILE_GUARD';

    /** @var array<string, true> */
    private static array $warned = [];

    private function __construct()
    {
    }

    public static function enable(bool $enabled = true): void
    {
        self::$enabled = $enabled;
    }

    /**
     * Forget the switch state and the once-per-subject bookkeeping, so the
     * environment is consulted again; for tests.
     *
     * @internal
     */
    public static function reset(): void
    {
        self::$enabled = null;
        self::$warned = [];
    }

    /**
     * Resolve the switch from the environment and cache the answer.
     */
    public static function resolve(): bool
    {
        $env = getenv(self::ENV);

        return self::$enabled = is_string($env) && ($env === '1' || strtolower($env) === 'true');
    }

    /**
     * Claim one warning slot: true only the first time a subject is seen.
     */
    public static function mark(string $subject): bool
    {
        if (isset(self::$warned[$subject])) {
            return false;
        }

        self::$warned[$subject] = true;

        return true;
    }

    /**
     * Emit one development warning.
     *
     * @param string $subject The dedupe key; repeat calls stay silent.
     */
    public static function warn(string $subject, string $message): void
    {
        if (self::mark($subject)) {
            self::emit($message);
        }
    }

    /**
     * Emit one development warning; the caller has deduped already.
     */
    public static function emit(string $message): void
    {
        trigger_error($message, E_USER_WARNING);
    }
}
