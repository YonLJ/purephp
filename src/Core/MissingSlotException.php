<?php

declare(strict_types=1);

namespace Pure\Core;

use RuntimeException;

/**
 * A required slot could not be resolved: the key was absent from its scope, or
 * it was provided as null, which only an optional slot accepts.
 */
final class MissingSlotException extends RuntimeException
{
    /**
     * The maximum number of scope keys listed in the message.
     */
    private const HINT_KEYS = 8;

    /**
     * No value was available for the slot at $path.
     *
     * The scope makes the message actionable: when the slot key is present it
     * was explicitly null, and when other keys were provided the message
     * suggests the closest one (a typo in the bindings) or lists them.
     *
     * @param array<array-key, mixed> $scope The scope the slot was read from.
     */
    public static function forPath(string $path, array $scope = []): self
    {
        $key = self::keyOf($path);

        if (array_key_exists($key, $scope)) {
            return new self("slot '{$path}' is required but was null.");
        }

        return new self("slot '{$path}' is required but was not provided" . self::hint($key, $scope));
    }

    /**
     * The sentence tail of the message: the closest key when a typo is likely,
     * the provided keys otherwise, or a plain period.
     *
     * @param array<array-key, mixed> $scope The scope the slot was read from.
     */
    private static function hint(string $key, array $scope): string
    {
        if ($scope === []) {
            return '.';
        }

        $keys = array_map(static fn (int|string $name): string => (string)$name, array_keys($scope));
        $nearest = Suggestion::nearest($key, $keys);

        if ($nearest !== null) {
            return "; did you mean '{$nearest}'?";
        }

        $listed = array_slice($keys, 0, self::HINT_KEYS);
        $list = implode(', ', array_map(static fn (string $name): string => "'{$name}'", $listed));

        if (count($keys) > self::HINT_KEYS) {
            $list .= ', ...';
        }

        return "; provided keys: {$list}.";
    }

    /**
     * The slot key of a dotted path: `items[].title` reads the key `title`.
     */
    private static function keyOf(string $path): string
    {
        $position = strrpos($path, '.');

        return $position === false ? $path : substr($path, $position + 1);
    }
}
