<?php

declare(strict_types=1);

namespace Pure\Core;

use BadMethodCallException;
use InvalidArgumentException;
use LogicException;

use function Pure\Utils\clx;
use function Pure\Utils\sty;

use Stringable;

abstract class Tag implements ShapeContract
{
    private string $tagName;

    /** @var array<string, string|Slot> */
    private array $attrs = [];

    /** @var array<int, mixed> */
    private array $children = [];

    private bool $selfClose = false;

    /**
     * A tag is already a data-free tree, so it satisfies the shape contract by
     * returning itself: `Slot::each('items', li(Slot::value('value')))` needs no
     * `Compile::shape()` wrapper. Wrap a tree in a shape when it is built and
     * memoized separately.
     *
     * @internal
     */
    public function tree(): Tag
    {
        return $this;
    }

    /**
     * @param string $tagName The HTML tag name.
     * @param array<int, mixed> $children
     */
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
     *
     * @return string The rendered HTML string.
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
            } elseif ($child instanceof Markup) {
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

    /**
     * Magic method for fluent attribute setting via unknown method names.
     *
     * @param string $key The attribute name.
     * @param array<int, mixed> $args
     * @return self
     */
    public function __call(string $key, array $args): self
    {
        if (count($args) !== 1) {
            throw new BadMethodCallException("'{$key}()' accepts exactly one parameter, " . count($args) . ' given.');
        }

        $this->setAttr($key, $args[0]);

        return $this;
    }

    /**
     * Set the class attribute. Alias of class().
     *
     * @param array<int, array<int|string, mixed>|bool|int|float|string|Slot|null> $args
     * @return self
     */
    public function className(array|bool|int|float|string|Slot|null ...$args): self
    {
        return $this->class(...$args);
    }

    /**
     * Set the class attribute. Accepts strings, arrays, or Slot values.
     *
     * @param array<int, array<int|string, mixed>|bool|int|float|string|Slot|null> $args
     * @return self
     */
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

    /**
     * Set the style attribute.
     *
     * Accepts a string, an array of style declarations, or a Slot.
     *
     * @param string|array<string, mixed>|Slot|null $value
     * @return self
     */
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

    /**
     * Whether this tag is a document root, whose header belongs to a complete
     * document (`<!DOCTYPE html>`, the XML declaration).
     *
     * Fragments — a div, an inline SVG icon — have none, so a plain view of a
     * fragment starts with its markup. The vocabulary classes override this:
     * an HTML tree is a document when its root is `<html>`, and an XML tree
     * always is; an SVG tree is a fragment, whose standalone-file header stays
     * available through `documentHeader()` / `save()`.
     *
     * @return bool Whether the document header belongs before this tag.
     */
    public function isDocumentRoot(): bool
    {
        return false;
    }

    /**
     * Check whether this tag is marked as self-closing.
     *
     * @return bool Whether the tag is self-closing.
     */
    public function getSelfClose(): bool
    {
        return $this->selfClose;
    }

    /**
     * Set whether this tag is self-closing.
     *
     * @param bool $value Whether the tag should be self-closing.
     * @return self
     */
    public function setSelfClose(bool $value): self
    {
        if ($value && !empty($this->children)) {
            throw new LogicException("Self-closing element '{$this->tagName}' cannot have child elements.");
        }

        $this->selfClose = $value;

        return $this;
    }

    /**
     * Get the tag name.
     *
     * @return string The tag name.
     */
    public function getTagName(): string
    {
        return $this->tagName;
    }

    /**
     * Get all attributes.
     *
     * @return array<string, string|Slot>
     */
    public function getAttrs(): array
    {
        return $this->attrs;
    }

    /**
     * Get an attribute by key. Returns null when the attribute is not set.
     *
     * @param string $key The normalized attribute key.
     * @return string|Slot|null The attribute value, or null if not set.
     */
    public function getAttr(string $key): string|Slot|null
    {
        return $this->attrs[self::normalizeAttrKey($key)] ?? null;
    }

    /**
     * Get all children.
     *
     * @return array<int, mixed>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Create a Tag instance and set multiple attributes at once.
     *
     * @param array<string, mixed> $attrs
     * @return self
     */
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
     *
     * @param string $key The attribute name.
     * @param callable(mixed|null): (string|Slot|null) $callback
     * @return self
     */
    public function setAttrByCb(string $key, callable $callback): self
    {
        $key = self::normalizeAttrKey($key);
        $this->guardAttributeName($key);
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
            throw new InvalidArgumentException("Element '{$this->tagName}': attribute name must not be numeric, got '{$key}'.");
        }
        if (empty($key)) {
            throw new InvalidArgumentException("Element '{$this->tagName}': attribute name must not be empty.");
        }

        $key = self::normalizeAttrKey((string)$key);
        $this->guardAttributeName($key);

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
     * Development guard hook: a vocabulary class warns when a setter carries a
     * near-miss standard attribute name (see AttributeNames). The base class
     * accepts any attribute name, so this is a no-op; HTML and SVG override it
     * with guardStandardAttribute().
     *
     * @param string $key The normalized attribute name.
     */
    protected function guardAttributeName(string $key): void
    {
    }

    /**
     * Warn once per attribute name when a setter carries a name one edit away
     * from a standard attribute, so `->clas(...)` or `->hreff(...)` is not a
     * silent custom attribute. Development guard only, off by default.
     *
     * @param string $key The normalized attribute name.
     */
    protected function guardStandardAttribute(string $key): void
    {
        if (!(DevMode::$enabled ?? DevMode::resolve())) {
            return;
        }

        $nearest = AttributeNames::nearest($key);

        if ($nearest === null) {
            return;
        }

        DevMode::warn(
            'attribute:' . $key,
            "Element '{$this->getTagName()}' has no standard attribute '{$key}'; did you mean '{$nearest}'? "
            . 'Ignore this warning for a custom attribute, or disable the guard with Compile::guard(false).'
        );
    }

    /**
     * Attribute names are stored with hyphens; `className` is an alias of
     * `class`, so get/set round-trip like `__call` does.
     *
     * @param string $key The attribute key to normalize.
     * @return string The normalized attribute key.
     */
    private static function normalizeAttrKey(string $key): string
    {
        $key = str_replace('_', '-', $key);

        return $key === 'className' ? 'class' : $key;
    }

    /**
     * Validate and stringify an attribute value.
     *
     * Attribute values must be scalar, bool, Stringable, Slot or null.
     *
     * @param string $tagName The element tag name, for error messages.
     * @param string $key The attribute name, for error messages.
     * @param mixed $value The value to stringify.
     * @return string The stringified value.
     * @throws InvalidArgumentException When the value is not valid.
     */
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

    /**
     * Append children to this tag.
     *
     * @param array<int, mixed> $children
     */
    private function appendChildren(array $children): void
    {
        if (empty($children)) {
            return;
        }

        for ($i = 0, $size = count($children); $i < $size; $i++) {
            $this->appendChild($children[$i]);
        }
    }

    /**
     * A Markup child (Raw, a component call) is kept as an object so it is
     * emitted verbatim and rendered lazily with the tree; every other value is
     * frozen to text now and escaped at render time.
     */
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

        if (is_string($child) || $child instanceof Markup || $child instanceof Tag || $child instanceof Slot) {
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
                    $child instanceof Raw => $child->value,
                    $child instanceof Markup => ['markup' => get_class($child)],
                    $child instanceof Tag => $child->toJSON(),
                    default => $child,
                },
                $this->children
            ),
        ];
    }

    public function print(): void
    {
        echo $this->__toString();
    }

    /**
     * Write the rendered tree to a file. Subclasses provide their default
     * document header; pass $header to override it.
     *
     * @param string $path The file path to write to.
     * @param string|null $header Optional document header to prepend.
     * @return int|false The number of bytes written, or false on failure.
     */
    public function save(string $path, ?string $header = null): int|false
    {
        return file_put_contents($path, ($header ?? $this->defaultHeader()) . $this->render());
    }

    /**
     * Cross-class accessor for the compiled path: Shape::save() reads the
     * document header of the root tag, which defaultHeader() keeps protected.
     *
     * @internal Subclasses customize the header by overriding defaultHeader().
     *
     * @return string The default document header.
     */
    public function documentHeader(): string
    {
        return $this->defaultHeader();
    }

    /**
     * Get the default document header. Override in subclasses.
     *
     * @return string The default document header (empty for the base class).
     */
    protected function defaultHeader(): string
    {
        return '';
    }
}
