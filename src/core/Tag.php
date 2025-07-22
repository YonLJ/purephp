<?php

declare(strict_types=1);

namespace Pure\Core;

use ErrorException;
use Exception;

use function Pure\Utils\clx;
use function Pure\Utils\sty;

abstract class Tag
{
    private string $tagName;

    /** @var array<string, string> */
    private array $attrs = [];

    /** @var array<int, mixed> */
    private array $children = [];

    private bool $selfClose = false;

    /** @param array<int, mixed> $children */
    public function __construct(string $tagName, array $children = [])
    {
        $this->tagName = $tagName;
        $this->appendChildren($children);
    }

    public function __toString(): string
    {
        return (string)$this->toPDom();
    }

    /** @param array<int, mixed> $args */
    public function __call(string $key, array $args): self
    {
        if (empty($args)) {
            throw new ErrorException("'{$key}()' accepts one parameter. '{$this->tagName}()->{$key}() is invalid.'");
        }

        if (count($args) !== 1) {
            $argsStr = join(',', $args);

            throw new Exception("'{$key}()' only accepts one parameter. '{$this->tagName}()->{$key}({$argsStr}) is invalid.'");
        }

        $this->setAttr($key, $args[0]);

        return $this;
    }

    /** @param array<int, string|array<int|string, mixed>|null> $args */
    public function className(string|array|null ...$args): self
    {
        return $this->class(...$args);
    }

    /** @param array<int, string|array<int|string, mixed>|null> $args */
    public function class(string|array|null ...$args): self
    {
        $value = count($args) === 1 && is_string($args[0])
            ? $args[0]
            : clx(...$args);

        return $this->setAttr('class', $value);
    }

    /** @param string|array<string, mixed>|null $value */
    public function style(string|array|null $value): self
    {
        if (!is_string($value)) {
            $value = sty($value);
        }

        return $this->setAttr('style', $value);
    }

    public function getSelfClose(): bool
    {
        return $this->selfClose;
    }

    public function setSelfClose(bool $value): self
    {
        $this->selfClose = $value;
        if ($this->selfClose && !empty($this->children)) {
            throw new ErrorException("Self-closing element '{$this->tagName}' cannot have child elements.");
        }

        return $this;
    }

    public function getTagName(): string
    {
        return $this->tagName;
    }

    /** @return array<string, string> */
    public function getAttrs(): array
    {
        return $this->attrs;
    }

    public function getAttr(string $key): string
    {
        if ($key === 'className') {
            $key = 'class';
        }

        return $this->attrs[$key];
    }

    /** @return array<int, mixed> */
    public function getChildren(): array
    {
        return $this->children;
    }

    /** @param array<string, mixed> $attrs */
    public function setAttrs(array $attrs): self
    {
        if (empty($attrs)) {
            return $this;
        }

        foreach ($attrs as $key => $value) {
            $this->setAttr($key, $value);
        }

        return $this;
    }

    public function setAttrByCb(string $key, callable $callback): self
    {
        $value = $callback($this->attrs[$key]);
        if (is_null($value)) {
            unset($this->attrs[$key]);
        } else {
            $this->attrs[$key] = (string)$value;
        }

        return $this;
    }

    private function setAttr(string|int $key, mixed $value): self
    {
        if (is_null($value)) {
            return $this;
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
                return $this;
            }
            $value = $key;
        }

        $this->attrs[$key] = (string)$value;

        return $this;
    }

    /** @param array<int, mixed> $children */
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
            /** @var array<int, mixed> $child */
            $this->appendChildren($child);

            return;
        }

        if (is_string($child)) {
            $this->children[] = strip_tags($child);

            return;
        }

        if ($child instanceof Raw || $child instanceof Tag) {
            $this->children[] = $child;

            return;
        }

        $this->children[] = (string)$child;
    }

    /** @return array<string, mixed> */
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

    public function toPDom(): PDom
    {
        return new PDom($this);
    }

    public function toNDom(): NDom
    {
        return new NDom($this);
    }

    public function toPrint(): void
    {
        echo $this->__toString();
    }

    abstract public function toSave(string $path): int|false;
}
