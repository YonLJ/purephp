<?php

declare(strict_types=1);

namespace Pure\Compile\Internal;

use Pure\Core\MissingSlotException;

/**
 * Data accessors used by template artifacts.
 *
 * A template artifact reads its slots through these helpers, so the generated
 * template stays readable (`TemplateRuntime::text($v, 'title')`) while keeping
 * the flat renderer's semantics: a required slot throws MissingSlotException
 * when the data does not provide it, an optional slot falls back to the
 * compiled default, and values are coerced by SlotRuntime, so both sources
 * render byte-identical output.
 *
 * The `$path` argument is only passed when the slot path differs from the slot
 * key (slots inside `Slot::each()` and `Slot::child()` scopes); it is the path
 * used in error messages. The default is detected by the argument count, so
 * `default: null` and "no default" stay distinguishable.
 *
 * @internal
 */
final class TemplateRuntime
{
    /**
     * Escape a text slot value.
     *
     * @param array<array-key, mixed> $scope The data scope to read from.
     * @param string $key The slot key.
     * @param ?string $path The slot path for error messages, when it differs from the key.
     * @param mixed $default The compiled default of an optional slot.
     * @return string The escaped text content.
     */
    public static function text(array $scope, string $key, ?string $path = null, mixed $default = null): string
    {
        return SlotRuntime::text(self::read($scope, $key, $path, func_num_args() < 4, $default), $path ?? $key);
    }

    /**
     * Coerce a raw slot value.
     *
     * @param array<array-key, mixed> $scope The data scope to read from.
     * @param string $key The slot key.
     * @param ?string $path The slot path for error messages, when it differs from the key.
     * @param mixed $default The compiled default of an optional slot.
     * @return string The verbatim output.
     */
    public static function raw(array $scope, string $key, ?string $path = null, mixed $default = null): string
    {
        return SlotRuntime::raw(self::read($scope, $key, $path, func_num_args() < 4, $default), $path ?? $key);
    }

    /**
     * Build one `name="value"` attribute chunk, omitting it for null values.
     *
     * @param array<array-key, mixed> $scope The data scope to read from.
     * @param string $key The slot key.
     * @param ?string $name The attribute name, when it differs from the slot key.
     * @param ?string $path The slot path for error messages, when it differs from the key.
     * @param mixed $default The compiled default of an optional slot.
     * @return string The serialized attribute chunk, or empty string if null.
     */
    public static function attr(array $scope, string $key, ?string $name = null, ?string $path = null, mixed $default = null): string
    {
        $value = self::read($scope, $key, $path, func_num_args() < 5, $default);

        return SlotRuntime::attrOpen($name ?? $key, $value, $path ?? $key);
    }

    /**
     * Read a child component value as a nested data scope.
     *
     * @param array<array-key, mixed> $scope The data scope to read from.
     * @param string $key The slot key.
     * @param ?string $path The slot path for error messages, when it differs from the key.
     * @param mixed $default The compiled default of an optional slot.
     * @return array<array-key, mixed>
     */
    public static function child(array $scope, string $key, ?string $path = null, mixed $default = null): array
    {
        return SlotRuntime::scope(self::read($scope, $key, $path, func_num_args() < 4, $default), $path ?? $key);
    }

    /**
     * Read a list slot value as an iterable.
     *
     * @param array<array-key, mixed> $scope The data scope to read from.
     * @param string $key The slot key.
     * @param ?string $path The slot path for error messages, when it differs from the key.
     * @param mixed $default The compiled default of an optional slot.
     * @return iterable<array-key, mixed>
     */
    public static function items(array $scope, string $key, ?string $path = null, mixed $default = null): iterable
    {
        return SlotRuntime::items(self::read($scope, $key, $path, func_num_args() < 4, $default), $path ?? $key);
    }

    /**
     * Validate a mapped child or list item value as a nested data scope.
     *
     * @param mixed $value The mapped value.
     * @param string $path The slot path for error messages.
     * @return array<array-key, mixed>
     */
    public static function scope(mixed $value, string $path): array
    {
        return SlotRuntime::scope($value, $path);
    }

    /**
     * Validate a heterogeneous list item and return its discriminator.
     *
     * @param mixed $item The list item to validate.
     * @param string $kindKey The key used to dispatch items by kind.
     * @param string $path The slot path for error messages.
     * @param array<int, string> $allowed The kind discriminators the slot accepts.
     * @return string The validated kind discriminator.
     */
    public static function kind(mixed $item, string $kindKey, string $path, array $allowed): string
    {
        return SlotRuntime::kind($item, $kindKey, $path, $allowed);
    }

    /**
     * Read one slot value: required slots throw, optional ones fall back to the
     * compiled default (also when the value is null, exactly like `??`).
     *
     * @param array<array-key, mixed> $scope The data scope to read from.
     * @param string $key The slot key.
     * @param ?string $path The slot path for error messages, when it differs from the key.
     * @param bool $required Whether the slot is required.
     * @param mixed $default The compiled default of an optional slot.
     * @return mixed The slot value.
     */
    private static function read(array $scope, string $key, ?string $path, bool $required, mixed $default): mixed
    {
        if (!$required) {
            return $scope[$key] ?? $default;
        }

        return array_key_exists($key, $scope) ? $scope[$key] : throw MissingSlotException::forPath($path ?? $key);
    }
}
