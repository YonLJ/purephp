<?php declare(strict_types=1);
namespace Tiny;

class Tag
{
    private string $tagName;

    private array $attrs = [];

    private array $children = [];

    private bool $isXML;

    public static function __callStatic(string $tag, array $children): Tag
    {
        return new Tag($tag, $children);
    }

    private function __construct(string $tagName, array $children)
    {
        $this->tagName = $tagName;
        $this->appendChildren($children);
    }

    public function __call(string $attr, array $args): Tag
    {
        $attr = str_replace('_', '-', $attr);
        $value = current($args);

        if(!array_key_exists($attr, $this->attrs)) {
            $this->attrs[$attr] = [];
        }
        $this->attrs[$attr][] = $value;

        return $this;
    }

    public function getTagName(): string
    {
        return $this->tagName;
    }

    public function getAttrs(): array
    {
        return $this->attrs;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function getIsXML(): bool
    {
        return $this->isXML;
    }

    private function appendChildren(array $children)
    {
        for($i = 0, $size = count($children); $i < $size; $i++) {
            $child = $children[$i];

            if(is_null($child)) {
                continue;
            }

            if(is_array($child)) {
                $this->appendChildren($child);
                continue;
            }

            if($child instanceof Tag) {
                $this->children[] = $child;
                continue;
            }

            $this->children[] = (string)$child;
        }
    }

    public function toJSON(): array
    {
        return array_merge([
            'tagName' => $this->tagName,
            'children' => array_map(
                fn($child) => $child instanceof Tag
                    ? $child->toJSON()
                    : $child,
                $this->children
            ),
        ], $this->attrs);
    }

    public function toTDom(bool $isXML = false): TDom
    {
        $this->isXML = $isXML;
        return new TDom($this);
    }

    public function toPDom(bool $isXML = false): PDom
    {
        $this->isXML = $isXML;
        return new PDom($this);
    }

    public function save(string $path, bool $isXML = false): int|false
    {
        $handle = fopen($path, 'w');
        if($handle === false) {
            return false;
        }

        $header = $isXML
            ? '<?xml version="1.0"?>'
            : '<!DOCTYPE html>';

        $result = fwrite($handle, $header . (string)$this->toTDom());
        fclose($handle);
        return $result;
    }
}
