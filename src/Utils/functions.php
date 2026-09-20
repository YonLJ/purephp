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
    $classList = [];

    foreach ($args as $className) {
        if (is_array($className)) {
            foreach ($className as $key => $value) {
                if (is_int($key)) {
                    // List entry: keep non-empty strings and numbers.
                    if (is_string($value) && $value !== '') {
                        $classList[] = $value;
                    } elseif (is_int($value) || is_float($value)) {
                        $classList[] = (string)$value;
                    }

                    continue;
                }

                // Map entry: a truthy value keeps the key as a class name.
                if ($key !== '' && !empty($value)) {
                    $classList[] = $key;
                }
            }

            continue;
        }

        if (is_bool($className) || $className === null || $className === '') {
            continue;
        }

        $classList[] = is_string($className) ? $className : (string)$className;
    }

    return $classList === [] ? null : implode(' ', $classList);
}

/**
 * Join style declarations into an inline style attribute value.
 *
 * @param array<array-key, mixed>|null $list
 * @return string|null The joined style string, or null if empty.
 */
function sty(array|null $list): string|null
{
    if (empty($list)) {
        return null;
    }

    /** @var string[] */
    $styleList = [];
    foreach ($list as $key => $val) {
        if (is_string($key) && (is_string($val) || is_numeric($val))) {
            $styleList[] = "$key: $val";
        }
    }

    if (empty($styleList)) {
        return null;
    }

    return join('; ', $styleList) . ';';
}
