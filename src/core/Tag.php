<?php declare(strict_types=1);
namespace Pure\Core;

use ErrorException;
use Exception;

abstract class Tag
{
    private string $tagName;

    /**
     * [string => string]
     */
    private array $attrs = [];

    private array $children = [];

    private bool $selfClose = false;

    protected function __construct(string $tagName, array $children)
    {
        $this->tagName = $tagName;
        $this->appendChildren($children);
    }

    public function _props(array $props): Tag
    {
        if (empty($props)) {
            return $this;
        }

        foreach ($props as $key => $value) {
            $this->appendAttr($key, $value);
        }

        return $this;
    }

    public function _selfClose(bool $value): Tag
    {
        $this->selfClose = $value;
        if ($this->selfClose && !empty($this->children)) {
            throw new ErrorException("Self-closing element '{$this->tagName}' cannot have child elements.");
        }
        return $this;
    }

    public function __call(string $key, array $args): Tag
    {
        if (empty($args)) {
            throw new ErrorException("'{$key}()' accepts one parameter. '{$this->tagName}()->{$key}() is invalid.'");
        }

        if (count($args) !== 1) {
            $argsStr = join(',', $args);
            throw new Exception("'{$key}()' only accepts one parameter. '{$this->tagName}()->{$key}({$argsStr}) is invalid.'");
        }

        $this->appendAttr($key, $args[0]);
        return $this;
    }

    public function isSelfClose(): bool
    {
        return $this->selfClose;
    }

    public function getTagName(): string
    {
        return $this->tagName;
    }

    public function getAttributes(): array
    {
        return $this->attrs;
    }

    public function getAttribute(string $key): string
    {
        return $this->attrs[$key];
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    private function appendAttr(string|int $key, mixed $value): void
    {
        if (is_null($value)) {
            return;
        }

        if (is_numeric($key)) {
            throw new ErrorException("Element '{$this->tagName}' attribute name cannot be numbers '{$key}'.");
        }
        if (empty($key)) {
            throw new ErrorException("Element '{$this->tagName}' attribute name cannot be empty '{$key}'.");
        }

        $key = str_replace('_', '-', $key);

        if (is_bool($value)) {
            if ($value === false) {
                return;
            }
            $value = $key;
        }

        $this->attrs[$key] = (string)$value;
    }

    private function appendChildren(array $children): void
    {
        if (empty($children)) {
            return;
        }

        for ($i = 0, $size = count($children); $i < $size; $i++) {
            $this->appendChild($children[$i]);
        }
    }

    private function appendChild(mixed $child): void
    {
        if (is_null($child)) {
            return;
        }

        if (is_array($child)) {
            $this->appendChildren($child);
            return;
        }

        if (is_string($child) || $child instanceof Raw || $child instanceof Tag) {
            $this->children[] = $child;
            return;
        }

        $this->children[] = (string)$child;
    }

    public function toJSON(): array
    {
        return array_merge([
            'tagName' => $this->tagName,
            'children' => array_map(
                fn ($child) => $child instanceof Tag || $child instanceof Raw
                    ? $child->toJSON()
                    : $child,
                $this->children
            ),
        ], $this->attrs);
    }

    public function toTDom(): TDom
    {
        return new TDom($this);
    }

    public function toPDom(): NDom
    {
        return new NDom($this);
    }

    public function __toString(): string
    {
        return (string)$this->toTDom();
    }

    abstract public function save(string $path): int|false;
}
