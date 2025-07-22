<?php

declare(strict_types=1);

namespace Pure\Utils;

/** @param array<string, mixed>|null $list */
function sty(array|null $list): string|null
{
    if (empty($list)) {
        return null;
    }

    /** @var string[] */
    $styleList = [];
    foreach ($list as $key => $val) {
        // @phpstan-ignore function.alreadyNarrowedType
        if (is_string($key) && (is_string($val) || is_numeric($val))) {
            $styleList[] = "$key: $val";
        }
    }

    if (empty($styleList)) {
        return null;
    }

    return join('; ', $styleList) . ';';
}
