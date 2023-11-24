<?php declare(strict_types=1);
namespace Tiny\Core;

use DOMElement;
use DOMDocument;

class PDom {
    private string $tagName;

    private array $attrs = [];

    private array $children = [];

    private DOMElement $dom;

    private static DOMDocument $document;

    private bool $isXML = false;

    private static function getDocument()
    {
        if (!isset(PDom::$document)) {
            PDom::$document = new DOMDocument('1.0');
        }
        return PDom::$document;
    }

    public function __construct(Tag $tag)
    {
        $this->isXML = $tag->getIsXML();
        $this->tagName = $tag->getTagName();
        $this->attrs = $tag->getAttrs();
        $this->children = array_map(
            fn($child) => $child instanceof Tag
                ? new PDom($child)
                : $child,
            $tag->getChildren()
        );
        $this->createDom();
        $this->appendAttrNodes();
        $this->appendChildren();
    }

    private function createDom()
    {
        $dom = PDom::getDocument()->createElement($this->tagName);
        if (!$dom) {
            throw new \Error("tag {$this->tagName} is invalid.");
        }
        $this->dom = $dom;
    }

    private function appendChildren()
    {
        for ($i = 0, $size = count($this->children); $i < $size; $i++) {
            $child = $this->children[$i];
            $this->appendChild($child);
        }
    }

    private function appendChild(string|PDom $child)
    {
        if (is_string($child)) {
            $textNode = PDom::getDocument()->createTextNode($child);
            $this->dom->appendChild($textNode);
            return;
        }

        if ($child instanceof PDom) {
            $this->dom->appendChild($child->toDom());
        }
    }

    private function appendAttrNodes()
    {
        foreach ($this->attrs as $key => $value) {
            $this->appendAttrNode($key, $value);
        }
    }

    private function appendAttrNode(string $key, array $value)
    {
        if (count($value) === 1) {
            $v = current($value);
            if (is_null($v) || $v === false) {
                return;
            }

            $attrNode = PDom::getDocument()->createAttribute($key);
            $attrNode->value = $v === true ? $v : (string)$v;
            $this->dom->appendChild($attrNode);
            return;
        }

        $attrNode = PDom::getDocument()->createAttribute($key);
        $attrNode->value = join(' ', $value);
        $this->dom->appendChild($attrNode);
    }

    public function toDom(): DOMElement
    {
        return $this->dom;
    }

    public function __toString(): string
    {
        $html = $this->isXML
            ? PDom::getDocument()->saveXML($this->dom)
            : PDom::getDocument()->saveHTML($this->dom);
        if ($html === false) {
            return '';
        }

        return $html;
    }
}
