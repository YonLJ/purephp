<?php declare(strict_types=1);
namespace Pure\Core;

class XML extends Tag {
    public static function __callStatic(string $tag, array $children): XML
    {
        return new XML($tag, $children);
    }

    public function save(string $path, string $header = '<?xml version="1.0"?>'): int|false
    {
        $handle = fopen($path, 'w');
        if ($handle === false) {
            return false;
        }

        $result = fwrite($handle, $header . (string)$this->toTDom());
        fclose($handle);
        return $result;
    }
}
