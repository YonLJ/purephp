<?php

declare(strict_types=1);

namespace Pure\Core;

const SELF_CLOSE_HTML_TAGS = [
    'area',
    'base',
    'br',
    'col',
    'embed',
    'hr',
    'img',
    'input',
    'link',
    'meta',
    'source',
    'track',
    'wbr',
];

class HTML extends Tag
{
    /** @param array<int, mixed> $children */
    public function __construct(string $tagName, array $children = [])
    {
        parent::__construct($tagName, $children);
        if (in_array(strtolower($tagName), SELF_CLOSE_HTML_TAGS)) {
            $this->setSelfClose(true);
        }
    }

    /** @param array<int, mixed> $children */
    public static function __callStatic(string $tag, array $children): HTML
    {
        return new HTML($tag, $children);
    }

    public function toSave(string $path, string $header = '<!DOCTYPE html>'): int|false
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
