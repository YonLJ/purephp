<?php

declare(strict_types=1);

namespace Pure\Utils;

/**
 * Join class name arguments into a class attribute value.
 *
 * Kept: non-empty strings (including the string "0") and numbers.
 * Dropped: null, booleans, and empty strings. Arrays contribute their
 * string/number list entries, or their keys for map entries with a truthy
 * value.
 *
 * @param array<int|string, mixed>|bool|int|float|string|null ...$args
 */
function clx(array|bool|int|float|string|null ...$args): string|null
{
    if ($args === []) {
        return null;
    }

    $classList = [];
    foreach ($args as $className) {
        if (is_bool($className)) {
            continue;
        }

        if (is_string($className)) {
            if ($className !== '') {
                $classList[] = $className;
            }

            continue;
        }

        if (is_int($className) || is_float($className)) {
            $classList[] = (string)$className;

            continue;
        }

        if (is_array($className)) {
            foreach ($className as $key => $value) {
                if (is_int($key)) {
                    if (is_string($value)) {
                        if ($value !== '') {
                            $classList[] = $value;
                        }
                    } elseif (is_int($value) || is_float($value)) {
                        $classList[] = (string)$value;
                    }

                    continue;
                }

                if ($key !== '' && !empty($value)) {
                    $classList[] = $key;
                }
            }
        }
    }

    return $classList === [] ? null : implode(' ', $classList);
}
