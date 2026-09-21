<?php

declare(strict_types=1);

namespace Pure\Core;

/**
 * Void elements, keyed as a set for O(1) membership tests.
 *
 * @var array<string, true>
 */
const SELF_CLOSE_HTML_TAGS = [
    'area' => true,
    'base' => true,
    'br' => true,
    'col' => true,
    'embed' => true,
    'hr' => true,
    'img' => true,
    'input' => true,
    'link' => true,
    'meta' => true,
    'source' => true,
    'track' => true,
    'wbr' => true,
];

class HTML extends Tag
{
    /**
     * The header that belongs before an HTML document.
     */
    public const DOCUMENT_HEADER = '<!DOCTYPE html>';

    /**
     * @internal Use the Pure\HTML functions or HTML::customTag() instead.
     *
     * @param string $tagName The HTML tag name.
     * @param array<int, mixed> $children
     */
    public function __construct(string $tagName, array $children = [])
    {
        parent::__construct($tagName, $children);
        if (isset(SELF_CLOSE_HTML_TAGS[strtolower($tagName)])) {
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

    /**
     * The document element: only an `<html>` root heads a complete document.
     */
    public function isDocumentRoot(): bool
    {
        return strtolower($this->getTagName()) === 'html';
    }

    /**
     * Warn once per attribute name when a setter carries a near-miss standard
     * attribute. Development guard only, off by default.
     */
    protected function guardAttributeName(string $key): void
    {
        $this->guardStandardAttribute($key);
    }

    protected function defaultHeader(): string
    {
        return self::DOCUMENT_HEADER;
    }
}
