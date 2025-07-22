<?php

declare(strict_types=1);

namespace Pure\Core;

use DOMDocument;
use DOMElement;

class NDom extends Dom
{
    private DOMElement $dom;

    private static DOMDocument $document;

    private bool $isXML = false;

    private static function document(): DOMDocument
    {
        if (!isset(NDom::$document)) {
            NDom::$document = new DOMDocument('1.0');
        }

        return NDom::$document;
    }

    public function __construct(Tag $tag)
    {
        $this->isXML = $tag instanceof XML;
        $this->tagName = $tag->getTagName();
        $this->attrs = $tag->getAttrs();
        $this->children = array_map(
            fn (mixed $child) => $child instanceof Tag
                ? new NDom($child)
                : $child,
            $tag->getChildren()
        );
        $this->createDom();
        $this->appendAttrNodes();
        $this->appendChildren();
    }

    public function __toString(): string
    {
        $html = $this->isXML
            ? NDom::document()->saveXML($this->dom)
            : NDom::document()->saveHTML($this->dom);
        if ($html === false) {
            return '';
        }

        return $html;
    }

    private function createDom(): void
    {
        $dom = NDom::document()->createElement($this->tagName);
        if (!$dom) {
            throw new \Error("tag {$this->tagName} is invalid.");
        }
        $this->dom = $dom;
    }

    private function appendChildren(): void
    {
        if (empty($this->children)) {
            return;
        }

        $size = count($this->children);
        for ($i = 0; $i < $size; $i++) {
            $child = $this->children[$i];
            $this->appendChild($child);
        }
    }

    private function appendChild(mixed $child): void
    {
        if (is_null($child)) {
            return;
        }

        if (is_string($child)) {
            $textNode = NDom::document()->createTextNode($child);
            $this->dom->appendChild($textNode);

            return;
        }

        if ($child instanceof Raw) {
            $fragment = NDom::document()->createDocumentFragment();
            if ($child->type === RawType::HTML) {
                $fragment->append((string)$child);
            } else {
                $fragment->appendXML((string)$child);
            }
            $this->dom->appendChild($fragment);
        }

        if ($child instanceof NDom) {
            $this->dom->appendChild($child->toDom());
        }
    }

    private function appendAttrNodes(): void
    {
        foreach ($this->attrs as $key => $value) {
            $this->appendAttrNode($key, $value);
        }
    }

    private function appendAttrNode(string $key, string $value): void
    {
        $attrNode = NDom::document()->createAttribute($key);
        $attrNode->value = $value;
        $this->dom->appendChild($attrNode);
    }

    public function toDom(): DOMElement
    {
        return $this->dom;
    }
}
