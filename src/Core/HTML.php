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
    /**
     * Initialize an HTML element. Self-closing tags are detected automatically.
     *
     * @param string $tagName The HTML tag name.
     * @param array<int, mixed> $children
     */
    public function __construct(string $tagName, array $children = [])
    {
        parent::__construct($tagName, $children);
        if (in_array(strtolower($tagName), SELF_CLOSE_HTML_TAGS)) {
            $this->setSelfClose(true);
        }
    }

    /**
     * Factory method for creating HTML elements via static method calls.
     *
     * @param string $tag The tag name.
     * @param array<int, mixed> $children
     */
    public static function __callStatic(string $tag, array $children): HTML
    {
        return new HTML($tag, $children);
    }

    protected function defaultHeader(): string
    {
        return '<!DOCTYPE html>';
    }
}
