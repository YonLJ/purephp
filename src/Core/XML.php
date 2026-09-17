<?php

declare(strict_types=1);

namespace Pure\Core;

class XML extends Tag
{
    /**
     * @internal Use the magic static surface, e.g. XML::customer(...).
     *
     * @param string $tagName The XML tag name.
     * @param array<int, mixed> $children
     */
    public function __construct(string $tagName, array $children = [])
    {
        parent::__construct($tagName, $children);
    }

    /**
     * Factory method for creating XML elements via static method calls.
     *
     * @param string $tag The tag name.
     * @param array<int, mixed> $children
     */
    public static function __callStatic(string $tag, array $children): XML
    {
        return new XML($tag, $children);
    }

    protected function defaultHeader(): string
    {
        return '<?xml version="1.0"?>';
    }
}
