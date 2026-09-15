<?php

declare(strict_types=1);

namespace Pure\Core;

use DOMDocument;
use DOMElement;
use DOMException;
use DOMNode;
use Error;

class Dom
{
    private DOMElement $dom;

    private static DOMDocument $document;

    private string $tagName;

    /** @var array<string, string> */
    private array $attrs = [];

    private bool $isXML = false;

    private static function document(): DOMDocument
    {
        if (!isset(Dom::$document)) {
            Dom::$document = new DOMDocument('1.0', 'UTF-8');
        }

        return Dom::$document;
    }

    public function __construct(Tag $tag)
    {
        $this->isXML = $tag instanceof XML;
        $this->tagName = $tag->getTagName();
        $this->attrs = $tag->getAttrs();
        $this->createDom();
        $this->appendAttrNodes();
        $this->appendChildren(array_map(
            fn (mixed $child) => $child instanceof Tag
                ? new Dom($child)
                : $child,
            $tag->getChildren()
        ));
    }

    public function __toString(): string
    {
        $html = $this->isXML
            ? Dom::document()->saveXML($this->dom)
            : Dom::document()->saveHTML($this->dom);
        if ($html === false) {
            return '';
        }

        return $html;
    }

    private function createDom(): void
    {
        try {
            $this->dom = Dom::document()->createElement($this->tagName);
        } catch (DOMException $e) {
            throw new Error("tag {$this->tagName} is invalid.", 0, $e);
        }
    }

    /** @param array<int, mixed> $children */
    private function appendChildren(array $children): void
    {
        if (empty($children)) {
            return;
        }

        $size = count($children);
        for ($i = 0; $i < $size; $i++) {
            $this->appendChild($children[$i]);
        }
    }

    private function appendChild(mixed $child): void
    {
        if (is_null($child)) {
            return;
        }

        if (is_string($child)) {
            $textNode = Dom::document()->createTextNode($child);
            $this->dom->appendChild($textNode);

            return;
        }

        if ($child instanceof Raw) {
            $this->appendRawChild($child);

            return;
        }

        if ($child instanceof Dom) {
            $this->dom->appendChild($child->toDom());
        }
    }

    private function appendRawChild(Raw $child): void
    {
        if ($child->type === RawType::HTML) {
            foreach (Dom::importHtmlNodes((string)$child) as $node) {
                $this->dom->appendChild($node);
            }

            return;
        }

        $raw = (string)$child;
        if ($raw === '') {
            return;
        }

        $fragment = Dom::document()->createDocumentFragment();
        $previous = libxml_use_internal_errors(true);
        $appended = $fragment->appendXML($raw);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($appended === false) {
            throw new Error('raw XML content is not well-formed: ' . $raw);
        }
        $this->dom->appendChild($fragment);
    }

    /**
     * Parse an HTML fragment and import it into the shared document.
     *
     * PHP has no HTML fragment parser before PHP 8.4 (Dom\HTMLDocument), so the
     * fragment is loaded through a temporary document and its nodes are
     * imported. Malformed markup is repaired by the HTML parser.
     *
     * @return array<int, DOMNode>
     */
    private static function importHtmlNodes(string $html): array
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><div>' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $loaded === false ? null : $document->documentElement;
        if ($root === null) {
            throw new Error('raw HTML content could not be parsed: ' . $html);
        }

        $nodes = [];
        foreach ($root->childNodes as $node) {
            $nodes[] = Dom::document()->importNode($node, true);
        }

        return $nodes;
    }

    private function appendAttrNodes(): void
    {
        foreach ($this->attrs as $key => $value) {
            $this->appendAttrNode($key, $value);
        }
    }

    /**
     * Set an attribute through DOMElement::setAttribute() so that the value is
     * escaped once during serialization. Assigning DOMAttr::$value instead would
     * parse the value as XML entities and drop anything containing a raw '&'.
     */
    private function appendAttrNode(string $key, string $value): void
    {
        $this->dom->setAttribute($key, $value);
    }

    public function toDom(): DOMElement
    {
        return $this->dom;
    }
}
