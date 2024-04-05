<?php declare(strict_types=1);
namespace Pure\Utils;

function sty(array|null $list): string|null
{
    if (empty($list)) {
        return null;
    }

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
