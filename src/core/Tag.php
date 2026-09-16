<?php

declare(strict_types=1);

namespace Pure\Core;

use BadMethodCallException;
use InvalidArgumentException;
use LogicException;

use function Pure\Utils\clx;
use function Pure\Utils\sty;

use Stringable;

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

        $attrs = '';
        foreach ($this->attrs as $key => $value) {
            if ($value instanceof Slot) {
                throw new LogicException(self::slotError());
            }

            $attrs .= Escaper::attribute($key, $value);
        }

        return $attrs;
    }

    /** @param array<int, mixed> $args */
    public function __call(string $key, array $args): self
    {
        if (count($args) !== 1) {
            throw new BadMethodCallException("'{$key}()' accepts exactly one parameter, " . count($args) . ' given.');
        }

        $this->setAttr($key, $args[0]);

        return $this;
    }

    /** @param array<int, array<int|string, mixed>|bool|int|float|string|Slot|null> $args */
    public function className(array|bool|int|float|string|Slot|null ...$args): self
    {
        return $this->class(...$args);
    }

    /** @param array<int, array<int|string, mixed>|bool|int|float|string|Slot|null> $args */
    public function class(array|bool|int|float|string|Slot|null ...$args): self
    {
        if (count($args) === 1) {
            $arg = $args[0];
            if ($arg instanceof Slot) {
                return $this->setAttr('class', $arg);
            }

            // Single strings go through clx too, so class('') behaves like
            // class(null) and class(['']) instead of emitting class="".
            return $this->setAttr('class', clx($arg));
        }

        /** @var array<int, array<int|string, mixed>|bool|int|float|string|null> $classes */
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
            throw new LogicException("Self-closing element '{$this->tagName}' cannot have child elements.");
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

    /** Returns null when the attribute is not set. */
    public function getAttr(string $key): string|Slot|null
    {
        return $this->attrs[self::normalizeAttrKey($key)] ?? null;
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

    /**
     * Transform an attribute value with a callback. A null result removes the
     * attribute, a Slot result stores the slot, anything else is stringified.
     * A missing attribute passes null to the callback.
     */
    public function setAttrByCb(string $key, callable $callback): self
    {
        $key = self::normalizeAttrKey($key);
        $value = $callback($this->attrs[$key] ?? null);
        if (is_null($value)) {
            unset($this->attrs[$key]);
        } elseif ($value instanceof Slot) {
            $this->attrs[$key] = $value;
        } else {
            $this->attrs[$key] = self::stringifyAttrValue($this->tagName, $key, $value);
        }

        return $this;
    }

    private function setAttr(string|int $key, mixed $value): self
    {
        if (is_null($value)) {
            return $this;
        }

        if (is_numeric($key)) {
            throw new InvalidArgumentException("Element '{$this->tagName}' attribute name cannot be numbers '{$key}'.");
        }
        if (empty($key)) {
            throw new InvalidArgumentException("Element '{$this->tagName}' attribute name cannot be empty '{$key}'.");
        }

        $key = self::normalizeAttrKey((string)$key);

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

        $this->attrs[$key] = self::stringifyAttrValue($this->tagName, $key, $value);

        return $this;
    }

    /**
     * Attribute names are stored with hyphens; `className` is an alias of
     * `class`, so get/set round-trip like `__call` does.
     */
    private static function normalizeAttrKey(string $key): string
    {
        $key = str_replace('_', '-', $key);

        return $key === 'className' ? 'class' : $key;
    }

    /** Attribute values must be scalar, bool, Stringable, Slot or null. */
    private static function stringifyAttrValue(string $tagName, string $key, mixed $value): string
    {
        if (is_scalar($value) || $value instanceof Stringable) {
            return (string)$value;
        }

        throw new InvalidArgumentException(
            "Element '{$tagName}' attribute '{$key}' must be a scalar, Stringable, Slot or null, "
            . get_debug_type($value) . ' given; use class()/style() for arrays.'
        );
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

        if (is_string($child) || $child instanceof Raw || $child instanceof Tag || $child instanceof Slot) {
            $this->children[] = $child;

            return;
        }

        $this->children[] = (string)$child;
    }

    /**
     * Structural snapshot: tag name, attributes and children. Attributes live
     * under their own key so they cannot collide with the structural keys.
     *
     * @return array{tagName: string, attrs: array<string, mixed>, children: array<int, mixed>}
     */
    public function toJSON(): array
    {
        $attrs = [];
        foreach ($this->attrs as $key => $value) {
            $attrs[$key] = $value instanceof Slot ? ['slot' => $value->name] : $value;
        }

        return [
            'tagName' => $this->tagName,
            'attrs' => $attrs,
            'children' => array_map(
                fn ($child) => match (true) {
                    $child instanceof Slot => ['slot' => $child->name],
                    $child instanceof Tag || $child instanceof Raw => $child->toJSON(),
                    default => $child,
                },
                $this->children
            ),
        ];
    }

    public function toPrint(): void
    {
        echo $this->__toString();
    }

    /**
     * Write the rendered tree to a file. Subclasses provide their default
     * document header; pass $header to override it.
     */
    public function toSave(string $path, ?string $header = null): int|false
    {
        return file_put_contents($path, ($header ?? $this->defaultHeader()) . $this->render());
    }

    protected function defaultHeader(): string
    {
        return '';
    }
}
