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

    public function toSave(string $path, string $header = '<?xml version="1.0"?>'): int|false
    {
        $handle = fopen($path, 'w');
        if ($handle === false) {
            return false;
        }

        $result = fwrite($handle, $header . (string)$this->toPDom());
        fclose($handle);

        return $result;
    }
}
