<?php declare(strict_types=1);
namespace Tiny\Core;

use DOMElement;
use DOMDocument;

class PDom extends Dom
{
    private DOMElement $dom;

    private static DOMDocument $document;

    private bool $isXML = false;

    private static function document(): DOMDocument
    {
        if (!isset(PDom::$document)) {
            PDom::$document = new DOMDocument('1.0');
        }
        return PDom::$document;
    }

    public function __construct(Tag $tag)
    {
        $this->isXML = $tag instanceof XML;
        $this->tagName = $tag->getTagName();
        $this->attrs = $tag->getAttributes();
        $this->children = array_map(
            fn ($child) => $child instanceof Tag
                ? new PDom($child)
                : $child,
            $tag->getChildren()
        );
        $this->createDom();
        $this->appendAttrNodes();
        $this->appendChildren();
    }

    private function createDom(): void
    {
        $dom = PDom::document()->createElement($this->tagName);
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

    private function appendChild(null|string|PDom|Raw $child): void
    {
        if (is_null($child)) {
            return;
        }

        if (is_string($child)) {
            $textNode = PDom::document()->createTextNode($child);
            $this->dom->appendChild($textNode);
            return;
        }

        if ($child instanceof Raw) {
            $fragment = PDom::document()->createDocumentFragment();
            if ($child->type === RawType::HTML) {
                $fragment->append((string)$child);
            } else {
                $fragment->appendXML((string)$child);
            }
            $this->dom->appendChild($fragment);
        }

        if ($child instanceof PDom) {
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
        $attrNode = PDom::document()->createAttribute($key);
        $attrNode->value = $value;
        $this->dom->appendChild($attrNode);
    }

    public function toDom(): DOMElement
    {
        return $this->dom;
    }

    public function __toString(): string
    {
        $html = $this->isXML
            ? PDom::document()->saveXML($this->dom)
            : PDom::document()->saveHTML($this->dom);
        if ($html === false) {
            return '';
        }

        return $html;
    }
}
