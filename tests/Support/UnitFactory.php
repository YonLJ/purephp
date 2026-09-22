<?php

declare(strict_types=1);

/**
 * Test support: the factories of generated unit files delegate here, so a
 * test can supply a closure that counts calls or captures $this while the
 * unit file itself shows the recommended register(Call(...), factory: ...)
 * form.
 */
final class UnitFactory
{
    /** @var array<string, callable(): mixed> */
    private static array $factories = [];

    /**
     * @param callable(): mixed $factory
     */
    public static function set(string $name, callable $factory): void
    {
        self::$factories[$name] = $factory;
    }

    public static function run(string $name): mixed
    {
        if (!isset(self::$factories[$name])) {
            throw new RuntimeException("no test factory is registered for unit '{$name}'.");
        }

        return (self::$factories[$name])();
    }

    public static function reset(): void
    {
        self::$factories = [];
    }
}
