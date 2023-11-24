<?php declare(strict_types=1);
namespace Tiny\Core;

class TDom {
    private string $tagName;

    private array $attrs = [];

    private array $children = [];

    private bool $selfClose = false;

    public function __construct(Tag $tag)
    {
        $this->tagName = $tag->getTagName();
        $this->attrs = $tag->getAttrs();
        $this->children = array_map(
            fn($child) => $child instanceof Tag
                ? new TDom($child)
                : $child,
            $tag->getChildren()
        );
    }

    private function buildAttrsStr(): string
    {
        $attrs = [];
        foreach ($this->attrs as $key => $value) {
            $attr = $this->buildAttrStr($key, $value);
            if (is_string($attr)) {
                $attrs[] = $attr;
            }
        }
        return join(' ', $attrs);
    }

    private function buildAttrStr(string $key, array $value): string|null
    {
        if (count($value) === 1) {
            $v = current($value);
            if (is_null($v) || $v === false) {
                return null;
            }

            if ($v === true) {
                return $key;
            }

            $v = (string)$v;
            return "{$key}=\"{$v}\"";
        }

        $attr = join(' ', $value);
        return "{$key}=\"{$attr}\"";
    }

    private function buildChildrenStr(): string
    {
        $children = array_map(fn($child) => (string)$child, $this->children);
        return join($children);
    }

    public function __toString(): string
    {
        $attrs = $this->buildAttrsStr();
        $attrs = empty($attrs) ? '' : " $attrs";
        
        if ($this->selfClose) {
            return "<{$this->tagName}{$attrs} />";
        }

        $content = $this->buildChildrenStr();
        return "<{$this->tagName}{$attrs}>{$content}</{$this->tagName}>";
    }
}
