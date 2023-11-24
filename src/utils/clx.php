<?php
namespace Tiny\Utils;

function clx(...$args): string
{
    $classList = [];
    foreach ($args as $className) {
        if (is_string($className)) {
            $classList[] = $className;
            continue;
        }

        if (is_array($className) || is_object($className)) {
            filterClassList($className);
        }
    }

    return join(' ', $classList);
}

function filterClassList(array $classList)
{
    foreach ($classList as $key => $val) {
        if (is_int($key) && is_string($val)) {
            $classList[] = $val;
            continue;
        }

        if (is_string($key) && is_bool($val)) {
            $classList[] = $key;
        }
    }
}
