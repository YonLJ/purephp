<?php declare(strict_types=1);
namespace Pure\Utils;

function clx(array|string|null ...$args): string|null
{
    if (empty($args)) {
        return null;
    }

    $classList = [];
    foreach ($args as $className) {
        if (is_string($className) && !empty($className)) {
            $classList[] = $className;
            continue;
        }

        if (is_array($className)) {
            $classList = array_merge($classList, filterClassList($className));
        }
    }

    if (empty($classList)) {
        return null;
    }
    return join(' ', $classList);
}

function filterClassList(array $classList)
{
    $classes = [];
    foreach ($classList as $key => $val) {
        if (is_int($key) && is_string($val) && !empty($val)) {
            $classes[] = $val;
            continue;
        }

        if (is_string($key) && !empty($key) && !empty($val)) {
            $classes[] = $key;
        }
    }
    return $classes;
}
