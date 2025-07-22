<?php

declare(strict_types=1);

namespace Pure\Core;

class PDom extends Dom
{
    private bool $selfClose = false;

    public function __construct(Tag $tag)
    {
        $this->tagName = $tag->getTagName();
        $this->selfClose = $tag->getSelfClose();
        $this->attrs = $tag->getAttrs();
        $this->children = array_map(
            fn ($child) => $child instanceof Tag
                ? new PDom($child)
                : $child,
            $tag->getChildren()
        );
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

    private function buildAttrsStr(): string
    {
        $attrs = [];
        foreach ($this->attrs as $key => $value) {
            $attrs[] = $this->buildAttrStr($key, $value);
        }

        return join(' ', $attrs);
    }

    private function buildAttrStr(string $key, string $value): string
    {
        return "{$key}=\"{$value}\"";
    }

    private function buildChildrenStr(): string
    {
        $children = array_map(fn ($child) => (string)$child, $this->children);

        return join($children);
    }
}
