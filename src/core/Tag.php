<?php

declare(strict_types=1);

namespace Pure\Core;

use ErrorException;
use Exception;
use LogicException;

use function Pure\Utils\clx;
use function Pure\Utils\sty;

abstract class Tag
{
    private string $tagName;

    /** @var array<string, string|Slot> */
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
        return $this->render();
    }

    /**
     * Render this subtree to an HTML string, without materializing an intermediate DOM copy.
     *
     * Attribute values and text children are escaped while rendering; Raw
     * children are emitted verbatim. Trees containing slots must be compiled
     * with Pure\Compile\Compile::shape() before rendering.
     */
    public function render(): string
    {
        $tagName = $this->tagName;
        $attrs = $this->buildAttrsStr();

        if ($this->selfClose) {
            return "<{$tagName}{$attrs} />";
        }

        $content = '';
        foreach ($this->children as $child) {
            if ($child instanceof Tag) {
                $content .= $child->render();
            } elseif ($child instanceof Raw) {
                $content .= (string)$child;
            } elseif ($child instanceof Slot) {
                throw new LogicException(self::slotError());
            } else {
                // Escape text children. double_encode=false keeps entities the
                // caller already escaped (e.g. "&copy;") intact while encoding
                // bare special characters.
                $content .= Escaper::text((string)$child);
            }
        }

        return "<{$tagName}{$attrs}>{$content}</{$tagName}>";
    }

    private static function slotError(): string
    {
        return 'Tag trees containing slots cannot be rendered directly; use Pure\Compile\Compile::shape() and render with data.';
    }

    /**
     * Reject slot placeholders on the paths that cannot bind data.
     *
     * @internal
     */
    public function assertNoSlots(): void
    {
        foreach ($this->attrs as $value) {
            if ($value instanceof Slot) {
                throw new LogicException(self::slotError());
            }
        }

        foreach ($this->children as $child) {
            if ($child instanceof Slot) {
                throw new LogicException(self::slotError());
            }

            if ($child instanceof Tag) {
                $child->assertNoSlots();
            }
        }
    }

    /**
     * Structural snapshot used by the compiled renderer.
     *
     * @internal
     *
     * @return array{tagName: string, attrs: array<string, string|Slot>, children: array<int, mixed>, selfClose: bool}
     */
    public function export(): array
    {
        return [
            'tagName' => $this->tagName,
            'attrs' => $this->attrs,
            'children' => $this->children,
            'selfClose' => $this->selfClose,
        ];
    }

    /**
     * Build the escaped attribute string for this node once. Produces a leading
     * space plus `key="value"` pairs joined by spaces, or an empty string when
     * there are no attributes.
     */
    private function buildAttrsStr(): string
    {
        if (empty($this->attrs)) {
            return '';
        }

        $parts = [];
        foreach ($this->attrs as $key => $value) {
            if ($value instanceof Slot) {
                throw new LogicException(self::slotError());
            }

            $parts[] = "{$key}=\"" . Escaper::attr($value) . "\"";
        }

        return ' ' . implode(' ', $parts);
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

    /** @param array<int, string|array<int|string, mixed>|Slot|null> $args */
    public function className(string|array|Slot|null ...$args): self
    {
        return $this->class(...$args);
    }

    /** @param array<int, string|array<int|string, mixed>|Slot|null> $args */
    public function class(string|array|Slot|null ...$args): self
    {
        if (count($args) === 1) {
            $arg = $args[0];
            if (is_string($arg) || is_null($arg) || $arg instanceof Slot) {
                return $this->setAttr('class', $arg);
            }

            return $this->setAttr('class', clx($arg));
        }

        /** @var array<int, string|array<int|string, mixed>|null> $classes */
        $classes = [];
        foreach ($args as $arg) {
            if ($arg instanceof Slot) {
                throw new LogicException("Slot values cannot be combined with other 'class' arguments.");
            }

            $classes[] = $arg;
        }

        return $this->setAttr('class', clx(...$classes));
    }

    /** @param string|array<string, mixed>|Slot|null $value */
    public function style(string|array|Slot|null $value): self
    {
        if ($value instanceof Slot) {
            return $this->setAttr('style', $value);
        }

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
        if ($value && !empty($this->children)) {
            throw new ErrorException("Self-closing element '{$this->tagName}' cannot have child elements.");
        }

        $this->selfClose = $value;

        return $this;
    }

    public function getTagName(): string
    {
        return $this->tagName;
    }

    /** @return array<string, string|Slot> */
    public function getAttrs(): array
    {
        return $this->attrs;
    }

    public function getAttr(string $key): string|Slot
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
        } elseif ($value instanceof Slot) {
            $this->attrs[$key] = $value;
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

        if ($value instanceof Slot) {
            $this->attrs[$key] = $value;

            return $this;
        }

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

        if ($child instanceof Raw || $child instanceof Tag || $child instanceof Slot) {
            $this->children[] = $child;

            return;
        }

        $this->children[] = (string)$child;
    }

    /** @return array<string, mixed> */
    public function toJSON(): array
    {
        $attrs = [];
        foreach ($this->attrs as $key => $value) {
            $attrs[$key] = $value instanceof Slot ? ['slot' => $value->name] : $value;
        }

        return array_merge([
            'tagName' => $this->tagName,
            'children' => array_map(
                fn ($child) => match (true) {
                    $child instanceof Slot => ['slot' => $child->name],
                    $child instanceof Tag || $child instanceof Raw => $child->toJSON(),
                    default => $child,
                },
                $this->children
            ),
        ], $attrs);
    }

    public function toDom(): Dom
    {
        $this->assertNoSlots();

        return new Dom($this);
    }

    public function toPrint(): void
    {
        echo $this->__toString();
    }

    abstract public function toSave(string $path): int|false;
}
