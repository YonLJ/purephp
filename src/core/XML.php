<?php

declare(strict_types=1);

namespace Pure\Core;

class XML extends Tag
{
    /** @param array<int, mixed> $children */
    public static function __callStatic(string $tag, array $children): XML
    {
        return new XML($tag, $children);
    }

    protected function defaultHeader(): string
    {
        return '<?xml version="1.0"?>';
    }
}
