<?php

declare(strict_types=1);

namespace Pure\Core;

class XML extends Tag
{
    /**
     * The header that belongs before a standalone XML document.
     */
    public const DOCUMENT_HEADER = '<?xml version="1.0"?>';

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

    /**
     * Every XML tree the generic class builds is document-shaped, so its
     * header (the XML declaration) belongs before it.
     */
    public function isDocumentRoot(): bool
    {
        return true;
    }

    /**
     * An XML tree names its own elements and attributes, so no standard
     * attribute list applies and the development guard stays quiet.
     */
    protected function guardAttributeName(string $key): void
    {
    }

    protected function defaultHeader(): string
    {
        return self::DOCUMENT_HEADER;
    }
}
