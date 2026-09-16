<?php

declare(strict_types=1);

namespace Pure\Utils;

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
